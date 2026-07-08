<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
  public function create()
  {
    return view('master.users.create');
  }

  /**
   * Masuk sebagai pegawai lain (impersonasi). Otorisasi ditangani oleh signature
   * pada URL — tautan hanya dibuat pada halaman detail yang diguard super_admin —
   * sehingga rute ini tetap dapat diakses tanpa sesi (mis. jendela incognito).
   *
   * Pengamanan berlapis:
   *  - Signature (middleware `signed`) mencegah pemalsuan & memberi masa berlaku singkat.
   *  - Nonce sekali-pakai mencegah tautan diputar ulang bila bocor (referrer/riwayat).
   *  - Akun nonaktif ditolak, konsisten dengan aturan login normal.
   *  - Setiap penggunaan dicatat ke activity log lengkap dengan pelaku (super_admin).
   */
  public function impersonate(User $user)
  {
    abort_if(! $user->is_active, 403, 'Akun tidak aktif tidak dapat diakses.');

    // Sekali pakai: tandai nonce agar tautan yang sama tidak bisa dipakai dua kali.
    // TTL lebih panjang dari masa berlaku tautan agar tidak bisa diputar ulang.
    $nonce = (string) request('nonce');
    if ($nonce === '' || ! Cache::add("impersonate_used:{$nonce}", true, now()->addMinutes(15))) {
      abort(403, 'Tautan masuk sebagai pengguna sudah tidak berlaku.');
    }

    // Pelaku (super_admin) diambil dari parameter bertanda-tangan — tidak bisa
    // dipalsukan karena ikut dihitung dalam signature.
    $initiator = User::find((int) request('by'));

    Auth::login($user);
    request()->session()->regenerate();
    session(['impersonator_id' => $initiator?->id]);

    activity('impersonation')
      ->performedOn($user)
      ->causedBy($initiator)
      ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
      ->log("Masuk sebagai {$user->name} melalui impersonasi");

    return redirect()->route('dashboard')
      ->with('success', "Anda sekarang masuk sebagai {$user->name}.");
  }

  public function show(User $user)
  {
    $user->load(['department', 'rank', 'position', 'roles']);

    $search = request('search_trip');

    $tripsAsPelaksanaQuery = \App\Models\SppdRequest::with(['budget', 'category', 'destinations.province', 'destinations.regency'])
      ->where('user_id', $user->id)
      ->orderBy('start_date', 'desc');

    $tripsAsFollowerQuery = \App\Models\SppdRequest::with(['user', 'budget', 'category', 'destinations.province', 'destinations.regency'])
      ->whereHas('followers', function ($q) use ($user) {
        $q->where('user_id', $user->id);
      })
      ->orderBy('start_date', 'desc');

    if (! empty($search)) {
      $tripsAsPelaksanaQuery->where(function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('destinations.province', function ($pq) use ($search) {
            $pq->where('name', 'like', "%{$search}%");
          })
          ->orWhereHas('destinations.regency', function ($rq) use ($search) {
            $rq->where('name', 'like', "%{$search}%");
          });
      });

      $tripsAsFollowerQuery->where(function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('destinations.province', function ($pq) use ($search) {
            $pq->where('name', 'like', "%{$search}%");
          })
          ->orWhereHas('destinations.regency', function ($rq) use ($search) {
            $rq->where('name', 'like', "%{$search}%");
          });
      });
    }

    $tripsAsPelaksana = $tripsAsPelaksanaQuery->paginate(5, ['*'], 'page_pelaksana')->withQueryString();
    $tripsAsFollower = $tripsAsFollowerQuery->paginate(5, ['*'], 'page_pengikut')->withQueryString();

    return view('master.users.show', compact('user', 'tripsAsPelaksana', 'tripsAsFollower'));
  }

  public function edit(User $user)
  {
    return view('master.users.edit', compact('user'));
  }
}
