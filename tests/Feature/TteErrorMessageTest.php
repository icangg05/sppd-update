<?php

namespace Tests\Feature;

use App\Livewire\Sppd\SppdShow;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pesan TTE gagal yang tampil ke penanda tangan: pesan provider yang terbungkus
 * berlapis ("Provider request failed: {...}") dibuka sampai pesan aslinya.
 */
class TteErrorMessageTest extends TestCase
{
    public static function errors(): array
    {
        return [
            // Format asli tersimpan di sppd_digital_signatures (SPPD #98, 10 Nov 2026).
            'passphrase salah (dua lapis)' => [
                'Provider request failed: {"error":"Provider request failed: {\"error\":\"Passphrase anda salah\",\"status_code\":2031}"}',
                'passphrase', 'Passphrase anda salah',
            ],
            'NIK tidak terdaftar' => [
                'Provider request failed: {"error":"Provider request failed: {\"error\":\"User tidak ditemukan\",\"status_code\":2021}"}',
                'nik', 'User tidak ditemukan',
            ],
            'permintaan kedua' => [
                'Provider second request failed: {"error":"Provider request failed: {\"message\":\"Passphrase anda salah\"}"}',
                'passphrase', 'Passphrase anda salah',
            ],
            'tanpa JSON' => ['Provider returned no PDF output.', 'sistem', 'Provider returned no PDF output.'],
        ];
    }

    #[DataProvider('errors')]
    public function test_pesan_provider_dibuka_sampai_pesan_aslinya(string $raw, string $type, string $message): void
    {
        $parsed = app(SppdShow::class)->parseTteError($raw);

        $this->assertSame($type, $parsed['type']);
        $this->assertSame($message, $parsed['message']);
    }
}
