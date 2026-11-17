<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * QR pada dokumen lama ber-TTE menunjuk ke https://sppd.kendarikota.go.id/telaah/...;
 * aplikasi lama sudah pindah domain, jadi tautan itu dialihkan ke config('sppd.legacy_url').
 */
class LegacySppdRedirectTest extends TestCase
{
    public function test_qr_tte_lama_dialihkan_ke_domain_lama_dengan_query_utuh(): void
    {
        config(['sppd.legacy_url' => 'https://sppd-old.kendarikota.go.id']);

        $this->get('/telaah/history/detail?telaah_id=QlROU05sTnZVbWRXTjFKZw==')
            ->assertRedirect('https://sppd-old.kendarikota.go.id/telaah/history/detail?telaah_id=QlROU05sTnZVbWRXTjFKZw==');
    }

    public function test_pola_qr_lama_lain_ikut_dialihkan(): void
    {
        config(['sppd.legacy_url' => 'https://sppd-old.kendarikota.go.id/']);

        $this->get('/telaah/laporan/qr3/sppd?telaah_id=abc')
            ->assertRedirect('https://sppd-old.kendarikota.go.id/telaah/laporan/qr3/sppd?telaah_id=abc');
    }

    public function test_tanpa_login_tetap_dialihkan(): void
    {
        config(['sppd.legacy_url' => 'https://sppd-old.kendarikota.go.id']);

        $this->get('/telaah')->assertRedirect('https://sppd-old.kendarikota.go.id/telaah');
    }

    public function test_mode_nonaktif_bila_domain_lama_dikosongkan(): void
    {
        config(['sppd.legacy_url' => '']);

        $this->get('/telaah/history/detail?telaah_id=abc')->assertNotFound();
    }
}
