<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use App\Services\OpenWAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{
  public function index(Request $request)
  {
    $query = User::with(['department', 'rank', 'position', 'roles']);

    // Filter berdasarkan instansi user jika bukan super admin
    // Termasuk semua pegawai di sub-department (bidang/subbidang)
    if (!auth()->user()->hasRole('super_admin')) {
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

    if (!empty($sortedDeptIds)) {
      $idsString = implode(',', $sortedDeptIds);
      $query->orderByRaw("FIELD(department_id, {$idsString}) = 0")
        ->orderByRaw("FIELD(department_id, {$idsString})");
    }

    $users = $query->orderBy('name')->paginate(20)->withQueryString();

    // Build a depth map for department indentation — avoids N+1 by using in-memory lookup
    $allDepts = Department::all()->keyBy('id');
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
      'name'          => 'required|string|max:255',
      'username'      => 'required|string|max:255|unique:users,username',
      'nik'           => 'nullable|string|max:20|unique:users,nik',
      'email'         => 'nullable|email|unique:users,email',
      'password'      => 'required|string|min:6',
      'nip'           => 'nullable|string|max:20',
      'phone'         => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(\App\Enums\EmployeeType::cases(), 'value')),
      'department_id' => 'nullable|exists:departments,id',
      'rank_id'       => 'nullable|exists:ranks,id',
      'position_id'   => 'nullable|exists:positions,id',
      'role'          => 'required|string',
    ]);

    if (!auth()->user()->hasRole('super_admin')) {
      // Pastikan department yang dipilih berada dalam hierarki OPD admin
      $dept = auth()->user()->department;
      if ($dept && !empty($validated['department_id'])) {
        $allowedIds = $dept->getAllRelatedIds();
        if (!$allowedIds->contains($validated['department_id'])) {
          $validated['department_id'] = auth()->user()->department_id;
        }
      } else {
        $validated['department_id'] = auth()->user()->department_id;
      }
    }

    $user = User::create([
      ...$validated,
      'password'  => Hash::make($validated['password']),
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
      'name'          => 'required|string|max:255',
      'username'      => 'required|string|max:255|unique:users,username,' . $user->id,
      'nik'           => 'nullable|string|max:20',
      'email'         => 'nullable',
      'password'      => 'nullable|string|min:6',
      'nip'           => 'nullable|string|max:20',
      'phone'         => 'nullable|string|max:20',
      'employee_type' => 'required|in:' . implode(',', array_column(\App\Enums\EmployeeType::cases(), 'value')),
      'department_id' => 'nullable|exists:departments,id',
      'rank_id'       => 'nullable|exists:ranks,id',
      'position_id'   => 'nullable|exists:positions,id',
      'role'          => 'required|string',
    ]);

    if (!auth()->user()->hasRole('super_admin')) {
      // Pastikan department yang dipilih berada dalam hierarki OPD admin
      $dept = auth()->user()->department;
      if ($dept && !empty($validated['department_id'])) {
        $allowedIds = $dept->getAllRelatedIds();
        if (!$allowedIds->contains($validated['department_id'])) {
          $validated['department_id'] = auth()->user()->department_id;
        }
      } else {
        $validated['department_id'] = auth()->user()->department_id;
      }
    }

    $data = $validated;
    if (!empty($data['password'])) {
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
    $user->update(['is_active' => !$user->is_active]);
    $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

    return back()->with('success', "Pegawai {$user->name} berhasil {$status}.");
  }

  /**
   * Kirim pesan WhatsApp percobaan ke nomor tertentu untuk verifikasi.
   *
   * Dibatasi 1 kali per 60 detik per pengguna untuk mencegah spam.
   */
  public function testWhatsApp(Request $request, OpenWAService $openwa)
  {
    $request->validate([
      'phone' => 'required|string|max:20',
    ]);

    $key = 'test-wa:user:' . auth()->id();
    $maxAttempts = 1;
    $decaySeconds = 60;

    if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
      $remaining = RateLimiter::availableIn($key);

      return response()->json([
        'success'   => false,
        'message'   => "Harap tunggu {$remaining} detik sebelum mengirim pesan percobaan lagi.",
        'remaining' => $remaining,
      ], 429);
    }

    RateLimiter::hit($key, $decaySeconds);

    $phone = $request->phone;
    $message = "🔔 *TES NOTIFIKASI SPPD*\n"
      . "*────────────────────────────────*\n\n"
      . "Halo! Ini adalah pesan percobaan dari sistem *SPPD Elektronik Kota Kendari*.\n\n"
      . "✅ Nomor WhatsApp Anda telah berhasil diverifikasi dan terdaftar sebagai penerima notifikasi.\n\n"
      . "Terima kasih.";

    $success = $openwa->send($phone, $message);

    if ($success) {
      return response()->json(['success' => true, 'message' => 'Pesan berhasil dikirim ke ' . $phone]);
    }

    return response()->json(['success' => false, 'message' => 'Gagal mengirim pesan. Pastikan nomor benar dan layanan OpenWA aktif.'], 422);
  }
}
