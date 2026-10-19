<?php

namespace Tests\Feature;

use App\Livewire\CountryIndex;
use App\Livewire\ForeignCityIndex;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Models\Country;
use App\Models\ForeignCity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ForeignLocationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'super_admin']);
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function cityOf(string $country, string $city): bool
    {
        return ForeignCity::where('name', $city)
            ->whereHas('country', fn ($q) => $q->where('name', $country))
            ->exists();
    }

    public function test_migration_mengisi_negara_dan_kota(): void
    {
        $this->assertSame(197, Country::count());
        $this->assertGreaterThan(5000, ForeignCity::count());
        $this->assertSame('JP', Country::where('name', 'Jepang')->value('iso2'));

        $this->assertTrue($this->cityOf('Jepang', 'Tokyo'));
        $this->assertTrue($this->cityOf('Arab Saudi', 'Makkah'));
        $this->assertTrue($this->cityOf('Arab Saudi', 'Madinah'));
        $this->assertTrue($this->cityOf('Vatikan', 'Vatican City'));

        // Setiap negara punya minimal satu kota (ibu kotanya).
        $this->assertSame(0, Country::doesntHave('cities')->count());
    }

    public function test_nama_lain_negara_untuk_pencarian(): void
    {
        $china = Country::where('name', 'Tiongkok')->first();
        $this->assertStringContainsString('Cina', $china->aliases);
        $this->assertStringContainsString('China', $china->aliases);

        // Opsi dropdown: nilai & label tetap nama resmi, nama lain jadi kata kunci.
        $option = collect(Country::selectOptions())->firstWhere('value', 'Tiongkok');
        $this->assertSame('Tiongkok', $option['label']);
        $this->assertStringContainsString('Cina', $option['keywords']);

        $page = Livewire::actingAs($this->superAdmin())
            ->test(CountryIndex::class)
            ->set('search', 'cina');
        $this->assertSame(['Tiongkok'], $page->viewData('countries')->pluck('name')->all());

        // Nama lain dirapikan saat disimpan (spasi & duplikat dibuang).
        $page->call('openEditModal', $china->id)
            ->set('aliases', ' China ,cina, Cina,, RRT ')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('China, cina, RRT', $china->fresh()->aliases);
    }

    public function test_negara_yang_masih_punya_kota_tidak_bisa_dihapus(): void
    {
        $japan = Country::where('name', 'Jepang')->first();

        Livewire::actingAs($this->superAdmin())
            ->test(CountryIndex::class)
            ->call('confirmDelete', $japan->id)
            ->call('delete');

        $this->assertDatabaseHas('countries', ['id' => $japan->id]);
    }

    public function test_tambah_negara_dengan_kode_iso_huruf_besar(): void
    {
        Livewire::actingAs($this->superAdmin())
            ->test(CountryIndex::class)
            ->call('openCreateModal')
            ->set('name', 'Negara Uji')
            ->set('iso2', 'xx')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('countries', ['name' => 'Negara Uji', 'iso2' => 'XX']);
    }

    public function test_kota_duplikat_dalam_satu_negara_dilewati(): void
    {
        $japan = Country::where('name', 'Jepang')->first();
        $before = $japan->cities()->count();

        Livewire::actingAs($this->superAdmin())
            ->test(ForeignCityIndex::class)
            ->call('openCreateModal')
            ->set('country_id', (string) $japan->id)
            ->set('names', ['tokyo', 'Kota Uji', 'kota uji'])
            ->call('save');

        // "tokyo" sudah ada; "Kota Uji"/"kota uji" dihitung satu kota.
        $this->assertSame($before + 1, $japan->cities()->count());
        $this->assertTrue($japan->cities()->whereRaw('LOWER(name) = ?', ['kota uji'])->exists());
    }

    public function test_pilihan_kota_di_form_sppd(): void
    {
        $form = new SppdCreateDetails;
        $form->destinations = [['country' => 'Jepang', 'city' => '', 'city_choice' => '']];

        $this->assertContains('Tokyo', $form->getCitiesForCountry('Jepang'));
        $this->assertSame([], $form->getCitiesForCountry(''));

        // Kota dari daftar langsung mengisi `city`.
        $form->destinations[0]['city_choice'] = 'Tokyo';
        $form->updated('destinations.0.city_choice', 'Tokyo');
        $this->assertSame('Tokyo', $form->destinations[0]['city']);

        // "Kota lain" mengosongkan `city` untuk diketik manual.
        $form->updated('destinations.0.city_choice', SppdCreateDetails::OTHER_CITY);
        $this->assertSame('', $form->destinations[0]['city']);

        // Ganti negara mengosongkan kota.
        $form->destinations[0]['city'] = 'Tokyo';
        $form->updated('destinations.0.country', 'Korea Selatan');
        $this->assertSame('', $form->destinations[0]['city']);
        $this->assertSame('', $form->destinations[0]['city_choice']);

        // Data tersimpan: kota dari daftar dikenali, kota lain jadi isian manual.
        $choice = (new \ReflectionMethod($form, 'cityChoiceFor'));
        $this->assertSame('Tokyo', $choice->invoke($form, 'Jepang', 'tokyo'));
        $this->assertSame(SppdCreateDetails::OTHER_CITY, $choice->invoke($form, 'Jepang', 'Desa Antah'));
        $this->assertSame('', $choice->invoke($form, 'Jepang', null));
    }
}
