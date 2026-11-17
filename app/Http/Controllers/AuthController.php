<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
  // Halaman login ditangani komponen Livewire App\Livewire\Auth\Login.

  public function logout(Request $request)
  {
    // Sesi impersonasi: jangan ganti remember_token pemilik akun, agar perangkatnya
    // sendiri yang memakai "Ingat saya" tidak ikut keluar.
    if ($request->session()->has(UserController::IMPERSONATOR_SESSION_KEY)) {
      Auth::logoutCurrentDevice();
    } else {
      Auth::logout();
    }
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')
      ->with('success', 'Anda berhasil keluar dari sistem.');
  }
}
