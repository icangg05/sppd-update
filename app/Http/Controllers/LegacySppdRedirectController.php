<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pengalihan tautan aplikasi SPPD lama. QR pada dokumen lama ber-TTE menunjuk ke
 * domain ini (mis. /telaah/history/detail?telaah_id=...), padahal aplikasi lama sudah
 * pindah ke config('sppd.legacy_url'). Path & query diteruskan apa adanya.
 */
class LegacySppdRedirectController extends Controller
{
  public function __invoke(Request $request): RedirectResponse
  {
    $legacyUrl = rtrim((string) config('sppd.legacy_url'), '/');

    abort_if($legacyUrl === '', 404);

    // getRequestUri() = path + query mentah, jadi nilai seperti "QlRO...g==" tidak berubah.
    return redirect()->away($legacyUrl . $request->getRequestUri());
  }
}
