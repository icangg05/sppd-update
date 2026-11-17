<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Models\Budget;
use App\Models\Country;
use App\Models\Department;
use App\Models\ForeignCity;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tombol "Isi Data Uji" di Detail SPPD hanya ada di APP_ENV local/development. Sekali klik,
 * seluruh formulir terisi data realistis sesuai domain yang langsung lolos validasi.
 */
class IsiDataUjiSppdTest extends TestCase
{
  use RefreshDatabase;

  private User $pelaksana;

  protected function setUp(): void
  {
    parent::setUp();

    $sultra = Province::create(['name' => 'Sulawesi Tenggara']);
    Regency::create(['province_id' => $sultra->id, 'name' => 'Kota Kendari']);
    Regency::create(['province_id' => $sultra->id, 'name' => 'Kab. Kolaka']);
    Regency::create(['province_id' => $sultra->id, 'name' => 'Kab. Wakatobi']);
    $dki = Province::create(['name' => 'DKI Jakarta']);
    Regency::create(['province_id' => $dki->id, 'name' => 'Kota Jakarta Pusat']);
    // Daftar negara sudah diisi migrasi; kota cukup dipastikan ada.
    $malaysia = Country::firstOrCreate(['name' => 'Malaysia'], ['iso2' => 'MY']);
    ForeignCity::firstOrCreate(['country_id' => $malaysia->id, 'name' => 'Kuala Lumpur']);

    foreach (['Koordinasi', 'Konsultasi', 'Sosialisasi', 'Monitoring & Evaluasi', 'Bimbingan Teknis', 'Rapat/Pertemuan', 'Studi Banding', 'Pengawasan'] as $name) {
      SppdCategory::create(['name' => $name]);
    }
  }

  public static function domains(): array
  {
    return [
      'dalam daerah' => ['dalam_daerah'],
      'LDDP' => ['lddp'],
      'LDLP' => ['ldlp'],
      'luar negeri' => ['luar_negeri'],
    ];
  }

  #[DataProvider('domains')]
  public function test_sekali_klik_mengisi_formulir_yang_lolos_validasi(string $domain): void
  {
    app()->detectEnvironment(fn () => 'local');
    $form = $this->formSppd($domain, 'Dinas Sosial');

    $form->assertSee('Isi Data Uji')
      ->call('fillDemoData')
      ->call('openConfirmModal')
      ->assertHasNoErrors()
      ->assertSet('showConfirmModal', true);

    $this->assertNotSame('', $form->get('purpose'));
    $this->assertNotSame('', $form->get('problem'));
    $this->assertNotSame('', $form->get('facts'));
    $this->assertNotSame('', $form->get('analysis'));
    $this->assertNotEmpty($form->get('followers'));
    $this->assertNotSame('', $form->get('departure_place'));

    $destination = $form->get('destinations')[0];
    match ($domain) {
      'dalam_daerah' => $this->assertNotSame('', $destination['address_only']),
      // Kota tujuan dari data master dan milik negara yang dipilih.
      'luar_negeri' => $this->assertTrue(ForeignCity::where('name', $destination['city'])
        ->whereHas('country', fn ($q) => $q->where('name', $destination['country']))->exists()),
      // LDDP tidak pernah bertujuan ke tempat kedudukan (Kota Kendari).
      'lddp' => $this->assertNotSame('Kota Kendari', Regency::find($destination['regency_id'])->name),
      'ldlp' => $this->assertSame('Kota Jakarta Pusat', Regency::find($destination['regency_id'])->name),
    };

    if ($domain === 'dalam_daerah') {
      $this->assertFalse(Carbon::parse($form->get('start_date'))->isSunday());
      $this->assertFalse(Carbon::parse($form->get('end_date'))->isSunday());
    }
  }

  public function test_tanggal_dipilih_melewati_sppd_lain_pelaksana(): void
  {
    app()->detectEnvironment(fn () => 'local');
    $form = $this->formSppd('lddp', 'Dinas Sosial');

    // Pelaksana sudah bertugas sampai 30 hari ke depan.
    $sibuk = $this->sppdFor($this->pelaksana, 'lddp', now()->toDateString(), now()->addDays(30)->toDateString());

    $form->call('fillDemoData');

    $this->assertTrue(Carbon::parse($form->get('start_date'))->gt($sibuk->end_date));
    $form->call('openConfirmModal')->assertHasNoErrors()->assertSet('showConfirmModal', true);
  }

  public function test_inspektorat_dalam_daerah_mengisi_jabatan_tim_dan_nomor_surat_tugas(): void
  {
    app()->detectEnvironment(fn () => 'local');
    $form = $this->formSppd('dalam_daerah', 'Inspektorat Daerah');

    $form->call('fillDemoData');

    $this->assertSame('Ketua Tim', $form->get('travel_position'));
    $followers = $form->get('followers');
    $this->assertNotEmpty($followers);
    $this->assertSame(array_fill_keys($followers, 'Anggota'), $form->get('follower_positions'));
    $this->assertMatchesRegularExpression('#^090 / \d{3} / ST / INSP\./ \d{4}$#', $form->get('document_number'));
    $form->call('openConfirmModal')->assertHasNoErrors()->assertSet('showConfirmModal', true);
  }

  public function test_tidak_tersedia_di_produksi(): void
  {
    app()->detectEnvironment(fn () => 'production');

    $this->formSppd('dalam_daerah', 'Dinas Sosial')
      ->assertDontSee('Isi Data Uji')
      ->call('fillDemoData')
      ->assertStatus(404);
  }

  /** Form Edit Perbaikan SPPD (tanpa syarat alur lengkap), dibuka sebagai Super Admin. */
  private function formSppd(string $domain, string $opdName)
  {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);
    $this->grantDefaultPermissions();
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    $this->actingAs($superAdmin);

    $opd = Department::create(['name' => $opdName, 'type' => DepartmentType::OPD, 'level' => 1]);
    $this->pelaksana = User::factory()->create(['department_id' => $opd->id]);
    // Calon pengikut sezona.
    User::factory()->count(2)->create(['department_id' => $opd->id]);
    (new Budget(['department_id' => $opd->id, 'description' => 'Perjalanan dinas', 'year' => now()->year, 'total_amount' => 50_000_000]))->save();

    $sppd = $this->sppdFor($this->pelaksana, $domain, '2020-01-06', '2020-01-07');

    return Livewire::withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class);
  }

  private function sppdFor(User $user, string $domain, string $start, string $end): SppdRequest
  {
    return SppdRequest::create([
      'user_id' => $user->id,
      'creator_id' => $user->id,
      'budget_id' => Budget::first()->id,
      'category_id' => SppdCategory::first()->id,
      'purpose' => 'Rapat koordinasi',
      'start_date' => $start,
      'end_date' => $end,
      'domain' => $domain,
      'status' => SppdStatus::IN_PROGRESS,
    ]);
  }
}
