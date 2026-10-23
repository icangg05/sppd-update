<?php

namespace Tests\Feature;

use App\Livewire\Sppd\SppdCreateDetails;
use Tests\TestCase;

class SppdTransportOptionTest extends TestCase
{
    public function test_nama_kendaraan_mengikuti_jenis_angkutan(): void
    {
        $form = new SppdCreateDetails;

        $this->assertSame([], $form->transportNames());

        $form->transport_type = 'Udara';
        $this->assertContains('Pesawat', $form->transportNames());
        $this->assertNotContains('Kapal', $form->transportNames());

        // Pilihan dari daftar langsung mengisi `transport_name`.
        $form->transport_choice = 'Pesawat';
        $form->updated('transport_choice', 'Pesawat');
        $this->assertSame('Pesawat', $form->transport_name);

        // Ganti jenis angkutan: kendaraan yang tidak cocok dikosongkan.
        $form->transport_type = 'Laut';
        $form->updated('transport_type', 'Laut');
        $this->assertSame('', $form->transport_choice);
        $this->assertSame('', $form->transport_name);
    }

    public function test_kendaraan_lainnya_diketik_manual(): void
    {
        $form = new SppdCreateDetails;
        $form->transport_type = 'Laut';

        // "Lainnya" mengosongkan `transport_name` untuk diketik manual.
        $form->transport_choice = SppdCreateDetails::OTHER_TRANSPORT;
        $form->updated('transport_choice', SppdCreateDetails::OTHER_TRANSPORT);
        $this->assertSame('', $form->transport_name);

        // Isian manual ikut dikosongkan bila jenis angkutan diganti.
        $form->transport_name = 'Kapal Kayu';
        $form->transport_type = 'Darat';
        $form->updated('transport_type', 'Darat');
        $this->assertSame('', $form->transport_choice);
        $this->assertSame('', $form->transport_name);

        // Data tersimpan: kendaraan dari daftar dikenali, selainnya jadi isian manual.
        $choice = new \ReflectionMethod($form, 'transportChoiceFor');
        $this->assertSame('Motor', $choice->invoke($form, 'Motor'));
        $this->assertSame(SppdCreateDetails::OTHER_TRANSPORT, $choice->invoke($form, 'Pedati'));
        $this->assertSame('', $choice->invoke($form, ''));
    }
}
