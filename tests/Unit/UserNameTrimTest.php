<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

/** Nama pegawai disimpan tanpa spasi di awal/akhir (input Livewire tidak melewati TrimStrings). */
class UserNameTrimTest extends TestCase
{
    public function test_nama_dirapikan_saat_diisi(): void
    {
        $this->assertSame('Kaisar Pelangko', (new User(['name' => '  Kaisar Pelangko ']))->name);

        $user = new User();
        $user->name = "\tSukri Latif, S.H ";
        $this->assertSame('Sukri Latif, S.H', $user->name);
    }
}
