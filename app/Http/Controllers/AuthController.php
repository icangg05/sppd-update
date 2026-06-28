<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
  // Halaman login ditangani komponen Livewire App\Livewire\Auth\Login.

  public function logout(Request $request)
  {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')
      ->with('success', 'Anda berhasil keluar dari sistem.');
  }
}
