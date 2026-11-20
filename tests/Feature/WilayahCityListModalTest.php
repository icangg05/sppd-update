<?php

namespace Tests\Feature;

use App\Livewire\CountryIndex;
use App\Livewire\ProvinceIndex;
use App\Models\Country;
use App\Models\ForeignCity;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Data Provinsi dan Data Negara: jumlah kota bisa diklik untuk melihat daftar kotanya di modal
 * (urut abjad). Daftar hanya dimuat selama modal terbuka.
 */
class WilayahCityListModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
    }

    public function test_modal_kabupaten_kota_provinsi(): void
    {
        $sultra = Province::create(['name' => 'Sulawesi Tenggara']);
        foreach (['Kota Kendari', 'Kabupaten Konawe', 'Kota Baubau'] as $name) {
            Regency::create(['province_id' => $sultra->id, 'name' => $name]);
        }
        Regency::create(['province_id' => Province::create(['name' => 'Bali'])->id, 'name' => 'Kota Denpasar']);

        $component = Livewire::test(ProvinceIndex::class)
            ->assertViewHas('citiesOf', null)
            ->call('openCitiesModal', $sultra->id)
            ->assertSet('showCitiesModal', true)
            ->assertSee('Kabupaten/Kota di Sulawesi Tenggara')
            ->assertSee('Kelola Kabupaten/Kota');

        $this->assertSame(
            ['Kabupaten Konawe', 'Kota Baubau', 'Kota Kendari'],
            $component->viewData('citiesOf')->regencies->pluck('name')->all(),
        );

        // Ditutup: daftar tidak dimuat lagi.
        $component->set('showCitiesModal', false)->assertViewHas('citiesOf', null);
    }

    public function test_modal_kota_negara(): void
    {
        // Nama fiktif: daftar negara sudah diisi lewat migrasi.
        $negara = Country::create(['name' => 'Negeri Uji']);
        foreach (['Kota Tengah', 'Kota Awal', 'Kota Batas'] as $name) {
            ForeignCity::create(['country_id' => $negara->id, 'name' => $name]);
        }

        $component = Livewire::test(CountryIndex::class)
            ->call('openCitiesModal', $negara->id)
            ->assertSet('showCitiesModal', true)
            ->assertSee('Kota di Negeri Uji')
            ->assertSee('3 kota terdaftar');

        $this->assertSame(['Kota Awal', 'Kota Batas', 'Kota Tengah'], $component->viewData('citiesOf')->cities->pluck('name')->all());
    }

    public function test_id_tak_dikenal_tidak_membuka_modal(): void
    {
        Livewire::test(CountryIndex::class)
            ->call('openCitiesModal', 999)
            ->assertSet('showCitiesModal', false)
            ->assertViewHas('citiesOf', null);
    }
}
