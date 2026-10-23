<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * x-form.file: saat Livewire mengunggah, "Mengunggah..." menggantikan hint di baris
 * yang sama agar tata letak (mis. tombol modal) tidak melompat.
 */
class FormFileComponentTest extends TestCase
{
    public function test_indikator_unggah_menggantikan_hint_di_baris_yang_sama(): void
    {
        $this->withViewErrors([]);

        $this->blade('<x-form.file wire:model="editReceipt" label="Bukti" hint="Format gambar JPG, PNG · maks. 10 MB" />')
            ->assertSee('Format gambar JPG, PNG · maks. 10 MB')
            ->assertSee('wire:loading.remove wire:target="editReceipt"', false)
            ->assertSee('wire:loading wire:target="editReceipt"', false)
            ->assertSee('Mengunggah...');

        // Form biasa (tanpa Livewire): hint saja.
        $this->blade('<x-form.file name="berkas" hint="Maks. 10 MB" />')
            ->assertSee('Maks. 10 MB')
            ->assertDontSee('Mengunggah...');
    }
}
