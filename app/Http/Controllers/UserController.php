<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeType;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

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
    $allDepts     = Department::all()->keyBy('id');
    $deptDepthMap = $allDepts->mapWithKeys(function ($dept) use ($allDepts) {
      $depth   = 0;
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
    $ranks       = Rank::orderBy('group')->get();
    $positions   = Position::orderBy('name')->get();

    return view('master.users.create', compact('departments', 'ranks', 'positions'));
  }

  private function getHierarchicalDepartments()
  {
    $user = auth()->user();

    if ($user->hasRole('super_admin')) {
      $roots = Department::whereNull('parent_id')->orderBy('name')->get();
    } else {
      // Admin OPD bisa melihat OPD induknya dan semua sub-department di bawahnya
      $dept  = $user->department;
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
    $list[]             = $dept;

    foreach ($dept->children()->orderBy('name')->get() as $child) {
      $this->flattenDepartment($child, $level + 1, $list);
    }
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name'          => 'required|string|max:255',
      'username'      => 'required|string|max:255|unique:users,username',
      'nik'           => 'nullable|string|max:20|unique:users,nik',
      'email'         => 'nullable|email|unique:users,email',
      'password'      => 'required|string|min:6',
      'nip'           => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(EmployeeType::cases(), 'value')),
      'department_id' => 'nullable|exists:departments,id',
      'rank_id'       => 'nullable|exists:ranks,id',
      'position_id'   => 'nullable|exists:positions,id',
      'role'          => 'required|string',
    ]);

    if (! auth()->user()->hasRole('super_admin')) {
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
      'password'       => Hash::make($validated['password']),
      'is_active'      => true,
      'phone_verified' => false,
    ]);

    $user->assignRole($validated['role']);

    return redirect()->route('master.users.edit', $user)->with('success', "Pegawai {$user->name} berhasil ditambahkan. Silakan verifikasi nomor telepon.");
  }

  public function show(User $user)
  {
    $user->load(['department', 'rank', 'position', 'roles']);

    return view('master.users.show', compact('user'));
  }

  public function edit(User $user)
  {
    $departments = $this->getHierarchicalDepartments();
    $ranks       = Rank::orderBy('group')->get();
    $positions   = Position::orderBy('level')->get();

    return view('master.users.edit', compact('user', 'departments', 'ranks', 'positions'));
  }

  public function update(Request $request, User $user)
  {
    $validated = $request->validate([
      'name'          => 'required|string|max:255',
      'username'      => 'required|string|max:255|unique:users,username,' . $user->id,
      'nik'           => 'nullable|string|max:20',
      'email'         => 'nullable',
      'password'      => 'nullable|string|min:6',
      'nip'           => 'nullable|string|max:20',
      'phone'         => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(EmployeeType::cases(), 'value')),
      'department_id' => 'nullable|exists:departments,id',
      'rank_id'       => 'nullable|exists:ranks,id',
      'position_id'   => 'nullable|exists:positions,id',
      'role'          => 'required|string',
    ]);

    // Validasi: jika nomor telepon diisi dan belum terverifikasi, tolak
    if (! empty($validated['phone']) && ! $user->phone_verified) {
      return back()->withErrors(['phone' => 'Nomor telepon harus diverifikasi terlebih dahulu.'])->withInput();
    }

    if (! auth()->user()->hasRole('super_admin')) {
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

    // Jika phone sudah verified, ambil dari DB (readonly di frontend)
    if ($user->phone_verified) {
      unset($data['phone']);
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
   * Reset status verifikasi telepon — memungkinkan user mengganti nomor.
   */
  public function resetPhone(User $user): JsonResponse
  {
    $user->update([
      'phone' => null,
      'phone_verified' => false,
    ]);

    return response()->json(['success' => true]);
  }

  /**
   * Kembalikan template pesan verifikasi WhatsApp.
   *
   * Alur verifikasi:
   * 1. User klik tombol "Verifikasi" → endpoint ini dipanggil
   * 2. Sistem kembalikan teks template siap kirim beserta nomor tujuan operator
   * 3. User mengirim pesan ke operator via deep-link WA
   * 4. Webhook KirimChat cocokkan nomor pengirim dengan nomor terdaftar → kirim notif hasil
   */
  public function testWhatsApp(Request $request): JsonResponse
  {
    $request->validate([
      'phone'   => 'required|string|max:20',
      'name'    => 'nullable|string|max:255',
      'email'   => 'nullable|string|max:255',
      'user_id' => 'nullable|integer',
    ]);

    $verificationNumber = config('kirimchat.verification_number', '6281376111919');
    $phone              = $request->phone;
    $name               = $request->name ?? 'Pegawai';
    $email              = $request->email ?? '-';

    // Generate internal token untuk polling frontend (tidak ditampilkan di pesan)
    $token           = 'V-' . rand(10000, 99999);
    $normalizedPhone = $this->normalizePhone($phone);

    // Bersihkan verifikasi sebelumnya untuk user ini (mencegah stale cache)
    if ($request->user_id) {
      $prevToken = Cache::get("wa_verification_user:{$request->user_id}");
      if ($prevToken) {
        $prevCached = Cache::get("wa_verification:{$prevToken}");
        if ($prevCached) {
          $prevNormalized = $this->normalizePhone($prevCached['phone']);
          Cache::forget("wa_verification_phone:{$prevNormalized}");
        }
        Cache::forget("wa_verification:{$prevToken}");
        Cache::forget("wa_verified_status:{$prevToken}");
        Cache::forget("wa_verification_failed:{$prevToken}");
      }
      Cache::put("wa_verification_user:{$request->user_id}", $token, now()->addMinutes(15));
    }

    // Simpan data verifikasi di Cache selama 15 menit
    Cache::put("wa_verification:{$token}", [
      'phone'   => $phone,
      'user_id' => $request->user_id,
      'name'    => $name,
      'email'   => $email,
    ], now()->addMinutes(15));

    // Reverse lookup: nomor telepon → token (agar webhook bisa cari tanpa token di pesan)
    Cache::put("wa_verification_phone:{$normalizedPhone}", $token, now()->addMinutes(15));

    // Template pesan — nomor dalam format 62xxx + keterangan proses
    $template = "Verifikasi WhatsApp SPPD Kendari\n" .
      "📱 *Nomor:* {$normalizedPhone}\n\n" .
      "Kirim pesan ini untuk memverifikasi nomor WhatsApp Anda.\n" .
      "_Sistem akan otomatis mencocokkan nomor pengirim. Hasil verifikasi akan dikirim di chat ini._";

    return response()->json([
      'success'             => true,
      'verification_number' => $verificationNumber,
      'template'            => $template,
      'token'               => $token,
      'phone_input'         => $normalizedPhone,
    ]);
  }

  /**
   * Cek status verifikasi WhatsApp berdasarkan token.
   *
   * Hanya membaca status dari cache yang di-set oleh KirimChatWebhookController.
   * Mengembalikan 3 state: verified, failed, atau pending.
   *
   * @param  string  $token
   * @return JsonResponse
   */
  public function checkVerification(string $token): JsonResponse
  {
    // Cek apakah sudah berhasil diverifikasi oleh webhook
    $verified = Cache::get("wa_verified_status:{$token}");

    if ($verified && ! empty($verified['verified'])) {
      return response()->json([
        'verified' => true,
        'failed'   => false,
        'phone'    => $verified['phone'],
      ]);
    }

    // Cek apakah webhook melaporkan kegagalan
    $failed = Cache::get("wa_verification_failed:{$token}");

    if ($failed) {
      return response()->json([
        'verified' => false,
        'failed'   => true,
        'message'  => $failed['message'] ?? 'Verifikasi gagal.',
      ]);
    }

    // Masih menunggu — token belum diproses webhook
    return response()->json([
      'verified' => false,
      'failed'   => false,
    ]);
  }

  /**
   * Normalisasi nomor telepon ke format internasional tanpa + atau 0 di depan.
   */
  private function normalizePhone(string $phone): string
  {
    if (str_contains($phone, '@')) {
      [$phone] = explode('@', $phone, 2);
    }

    $phone = preg_replace('/\D/', '', $phone);

    if (str_starts_with($phone, '0')) {
      $phone = '62' . substr($phone, 1);
    } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9 && strlen($phone) <= 13) {
      $phone = '62' . $phone;
    }

    return $phone;
  }
}
