<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeType;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use App\Services\KirimChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
  public function index(Request $request)
  {
    $query = User::with(['department', 'rank', 'position', 'roles']);

    // Filter berdasarkan instansi user jika bukan super admin
    // Termasuk semua pegawai di sub-department (bidang/subbidang)
    if (! auth()->user()->hasRole('super_admin')) {
      $dept = auth()->user()->department;
      if ($dept) {
        $relatedIds = $dept->getAllRelatedIds();
        $query->whereIn('department_id', $relatedIds);
      } else {
        $query->where('department_id', auth()->user()->department_id);
      }
    }

    if ($request->type === 'dprd') {
      $query->where(function ($q) {
        $q->whereHas('roles', fn($r) => $r->where('name', 'anggota_dprd'))
          ->orWhere('employee_type', 'dprd')
          ->orWhereNotNull('dprd_jabatan');
      });
    } else {
      $query->where(function ($q) {
        $q->whereDoesntHave('roles', fn($r) => $r->where('name', 'anggota_dprd'))
          ->where('employee_type', '!=', 'dprd')
          ->whereNull('dprd_jabatan');
      });
    }

    if ($request->filled('search')) {
      $s = $request->search;
      $query->where(function ($q) use ($s) {
        $q->where('name', 'like', "%{$s}%")
          ->orWhere('username', 'like', "%{$s}%")
          ->orWhere('nik', 'like', "%{$s}%")
          ->orWhere('nip', 'like', "%{$s}%")
          ->orWhere('email', 'like', "%{$s}%");
      });
    }

    if ($request->filled('department_id') && auth()->user()->hasRole('super_admin')) {
      $query->where('department_id', $request->department_id);
    }

    // Order by department from root to descendants, then by name
    $sortedDeptIds = Department::whereNull('parent_id')
      ->with('children')
      ->get()
      ->flatMap(function ($dept) {
        return $dept->getAllRelatedIds();
      })
      ->filter()
      ->unique()
      ->values()
      ->toArray();

    if (! empty($sortedDeptIds)) {
      $idsString = implode(',', $sortedDeptIds);
      $query->orderByRaw("FIELD(department_id, {$idsString}) = 0")
        ->orderByRaw("FIELD(department_id, {$idsString})");
    }

    $users = $query->orderBy('name')->paginate(20)->withQueryString();

    // Build a depth map for department indentation — avoids N+1 by using in-memory lookup
    $allDepts = Department::all()->keyBy('id');
    $deptDepthMap = $allDepts->mapWithKeys(function ($dept) use ($allDepts) {
      $depth = 0;
      $current = $dept;
      while ($current->parent_id && $allDepts->has($current->parent_id)) {
        $depth++;
        $current = $allDepts->get($current->parent_id);
      }

      return [$dept->id => $depth];
    });

    // Dropdown department hanya untuk super admin atau minimal tampilkan department sendiri
    $departments = $this->getHierarchicalDepartments();

    return view('master.users.index', compact('users', 'departments', 'deptDepthMap'));
  }

  public function create()
  {
    $departments = $this->getHierarchicalDepartments();
    $ranks = Rank::orderBy('group')->get();
    $positions = Position::orderBy('name')->get();

    return view('master.users.create', compact('departments', 'ranks', 'positions'));
  }

  private function getHierarchicalDepartments()
  {
    $user = auth()->user();

    if ($user->hasRole('super_admin')) {
      $roots = Department::whereNull('parent_id')->orderBy('name')->get();
    } else {
      // Admin OPD bisa melihat OPD induknya dan semua sub-department di bawahnya
      $dept = $user->department;
      $roots = $dept ? Department::where('id', $dept->id)->get() : collect();
    }

    $list = [];
    foreach ($roots as $root) {
      $this->flattenDepartment($root, 0, $list);
    }

    return $list;
  }

  private function flattenDepartment($dept, $level, &$list)
  {
    $dept->display_name = str_repeat('— ', $level) . $dept->name;
    $list[] = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list);
    }
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'username' => 'required|string|max:255|unique:users,username',
      'nik' => 'nullable|string|max:20|unique:users,nik',
      'email' => 'nullable|email|unique:users,email',
      'password' => 'required|string|min:6',
      'nip' => 'nullable|string|max:20',
      'phone' => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(EmployeeType::cases(), 'value')),
      'department_id' => 'nullable|exists:departments,id',
      'rank_id' => 'nullable|exists:ranks,id',
      'position_id' => 'nullable|exists:positions,id',
      'role' => 'required|string',
    ]);

    if (! auth()->user()->hasRole('super_admin')) {
      // Pastikan department yang dipilih berada dalam hierarki OPD admin
      $dept = auth()->user()->department;
      if ($dept && ! empty($validated['department_id'])) {
        $allowedIds = $dept->getAllRelatedIds();
        if (! $allowedIds->contains($validated['department_id'])) {
          $validated['department_id'] = auth()->user()->department_id;
        }
      } else {
        $validated['department_id'] = auth()->user()->department_id;
      }
    }

    $user = User::create([
      ...$validated,
      'password' => Hash::make($validated['password']),
      'is_active' => true,
    ]);

    $user->assignRole($validated['role']);

    return redirect()->route('master.users.index')->with('success', "Pegawai {$user->name} berhasil ditambahkan.");
  }

  public function show(User $user)
  {
    $user->load(['department', 'rank', 'position', 'roles']);

    return view('master.users.show', compact('user'));
  }

  public function edit(User $user)
  {
    $departments = $this->getHierarchicalDepartments();
    $ranks = Rank::orderBy('group')->get();
    $positions = Position::orderBy('level')->get();

    return view('master.users.edit', compact('user', 'departments', 'ranks', 'positions'));
  }

  public function update(Request $request, User $user)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'username' => 'required|string|max:255|unique:users,username,' . $user->id,
      'nik' => 'nullable|string|max:20',
      'email' => 'nullable',
      'password' => 'nullable|string|min:6',
      'nip' => 'nullable|string|max:20',
      'phone' => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(EmployeeType::cases(), 'value')),
      'department_id' => 'nullable|exists:departments,id',
      'rank_id' => 'nullable|exists:ranks,id',
      'position_id' => 'nullable|exists:positions,id',
      'role' => 'required|string',
    ]);

    if (! auth()->user()->hasRole('super_admin')) {
      // Pastikan department yang dipilih berada dalam hierarki OPD admin
      $dept = auth()->user()->department;
      if ($dept && ! empty($validated['department_id'])) {
        $allowedIds = $dept->getAllRelatedIds();
        if (! $allowedIds->contains($validated['department_id'])) {
          $validated['department_id'] = auth()->user()->department_id;
        }
      } else {
        $validated['department_id'] = auth()->user()->department_id;
      }
    }

    $data = $validated;
    if (! empty($data['password'])) {
      $data['password'] = Hash::make($data['password']);
    } else {
      unset($data['password']);
    }

    $user->update($data);
    $user->syncRoles([$validated['role']]);

    return redirect()->route('master.users.index')->with('success', "Pegawai {$user->name} berhasil diperbarui.");
  }

  public function destroy(User $user)
  {
    $name = $user->name;
    $user->delete();

    return redirect()->route('master.users.index')->with('success', "Pegawai {$name} berhasil dihapus.");
  }

  public function toggleActive(User $user)
  {
    $user->update(['is_active' => ! $user->is_active]);
    $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

    return back()->with('success', "Pegawai {$user->name} berhasil {$status}.");
  }

  /**
   * Kembalikan template pesan verifikasi WhatsApp.
   *
   * Alur verifikasi:
   * 1. User klik tombol "Verifikasi" → endpoint ini dipanggil
   * 2. Sistem kembalikan teks template siap kirim beserta nomor tujuan operator
   * 3. User menyalin/klik link deep-link WA untuk mengirim pesan ke operator
   */
  public function testWhatsApp(Request $request): JsonResponse
  {
    $request->validate([
      'phone' => 'required|string|max:20',
      'name' => 'nullable|string|max:255',
      'email' => 'nullable|string|max:255',
      'user_id' => 'nullable|integer',
    ]);

    $verificationNumber = config('kirimchat.verification_number', '6281376111919');
    $phone = $request->phone;
    $name = $request->name ?? 'Pegawai';
    $email = $request->email ?? '-';

    /** @var \App\Services\KirimChatService $kirimChat */
    $kirimChat = app(KirimChatService::class);
    $health = $kirimChat->healthCheck();

    // Generate token verifikasi unik
    $token = 'V-' . rand(10000, 99999);

    // Simpan data verifikasi di Cache selama 15 menit
    Cache::put("wa_verification:{$token}", [
      'phone' => $phone,
      'user_id' => $request->user_id,
      'name' => $name,
      'email' => $email,
    ], now()->addMinutes(15));

    // Format template pesan: kalimat formal + data ringkas (nomor & kode saja)
    $template = "Verifikasi WhatsApp SPPD Kendari:\n" .
      "📱 *Nomor:* {$phone}\n" .
      "🔑 *Kode:* {$token}\n\n" .
      "_Jangan ubah isi pesan ini. Silakan kirim, lalu cek status secara berkala di halaman browser Anda._";

    return response()->json([
      'success'             => true,
      'verification_number' => $verificationNumber,
      'template'            => $template,
      'token'               => $token,
      'phone_input'         => $phone,
      'service_healthy'     => $health['success'],
    ]);
  }

  /**
   * Cek status verifikasi WhatsApp berdasarkan token.
   *
   * @param  string  $token
   * @return JsonResponse
   */
  public function checkVerification(string $token): JsonResponse
  {
    $status = Cache::get("wa_verified_status:{$token}");

    if ($status && !empty($status['verified'])) {
      return response()->json([
        'verified' => true,
        'phone' => $status['phone'],
      ]);
    }

    $cached = Cache::get("wa_verification:{$token}");

    if ($cached) {
      $baseUrl = rtrim(config('kirimchat.base_url'), '/');
      $apiKey = config('kirimchat.api_key');

      if ($baseUrl && $apiKey) {
        try {
          $response = \Illuminate\Support\Facades\Http::timeout(5)
            ->withHeaders([
              'Authorization' => 'Bearer ' . $apiKey,
              'Accept' => 'application/json',
            ])
            ->get("{$baseUrl}/messages", [
              'limit' => 20,
            ]);

          if ($response->successful()) {
            $messages = $response->json('data') ?? [];
            $found = false;

            foreach ($messages as $msg) {
              $msgFrom = $msg['customer_phone'] ?? $msg['from'] ?? ($msg['raw']['message']['from'] ?? null);
              $msgBody = $msg['content'] ?? $msg['message'] ?? $msg['body'] ?? ($msg['raw']['message']['text']['body'] ?? null);
              $msgDirection = $msg['direction'] ?? null;

              if ($msgFrom && $msgBody && $msgDirection === 'inbound') {
                // Normalisasi nomor telepon
                $fromNormalized = preg_replace('/\D/', '', $msgFrom);
                if (str_starts_with($fromNormalized, '0')) {
                  $fromNormalized = '62' . substr($fromNormalized, 1);
                }

                $cachedNormalized = preg_replace('/\D/', '', $cached['phone']);
                if (str_starts_with($cachedNormalized, '0')) {
                  $cachedNormalized = '62' . substr($cachedNormalized, 1);
                }

                if ($fromNormalized === $cachedNormalized && str_contains(strtoupper($msgBody), strtoupper($token))) {
                  $found = true;
                  break;
                }
              }
            }

            if ($found) {
              // Simpan status sukses verifikasi di Cache untuk polling berikutnya
              Cache::put("wa_verified_status:{$token}", [
                'verified' => true,
                'phone' => $cached['phone'],
              ], now()->addMinutes(15));

              // Jika user_id ada (proses edit pegawai), update nomor telepon di database
              if (!empty($cached['user_id'])) {
                $user = User::find($cached['user_id']);
                if ($user) {
                  $user->update(['phone' => $cached['phone']]);
                  \Illuminate\Support\Facades\Log::info("KirimChatDirectCheck: Berhasil memperbarui nomor telepon user ID {$user->id}.");
                }
              }

              // Kirim balasan WhatsApp sukses
              $name = $cached['name'] ?? 'Pegawai';
              $reply = "✅ *VERIFIKASI BERHASIL!*\n\n" .
                "Halo *{$name}*, nomor WhatsApp Anda ({$cached['phone']}) telah sukses terverifikasi pada *Sistem SPPD Elektronik Kota Kendari*.\n\n" .
                "Anda sekarang akan menerima notifikasi perjalanan dinas secara otomatis di nomor ini. Terima kasih!";

              $kirimChatService = app(KirimChatService::class);
              $kirimChatService->send($cached['phone'], $reply);

              // Hapus token verifikasi dari cache agar satu kali pakai
              Cache::forget("wa_verification:{$token}");

              return response()->json([
                'verified' => true,
                'phone' => $cached['phone'],
              ]);
            }
          }
        } catch (\Throwable $e) {
          \Illuminate\Support\Facades\Log::error("KirimChatDirectCheck: Exception saat memeriksa pesan: " . $e->getMessage());
        }
      }
    }

    return response()->json([
      'verified' => false,
    ]);
  }
}
