<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\PositionScope;
use App\Enums\PositionTier;
use App\Enums\SppdStatus;
use App\Livewire\PositionFormModal;
use App\Livewire\Sppd\PptkSelector;
use App\Livewire\Sppd\SppdCreate;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Livewire\UserForm;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tenaga Mitra: orang dari luar OPD (mis. akademisi) yang hanya diikutkan sebagai pengikut
 * perjalanan dinas. Didaftarkan lewat mode khusus di form Tambah Pegawai di unit kerja mana pun
 * (hanya nama, NIP, NIK, jabatan), tidak pernah menjadi pelaksana, dan tidak punya akun login.
 */
class TenagaMitraTest extends TestCase
{
    use RefreshDatabase;

    private Department $puskesmas;
    private Department $dinas;
    private User $superAdmin;
    private User $admin;
    private User $staf;
    private Position $kader;
    private Position $bidan;
    private Position $kapus;

    protected function setUp(): void
    {
        parent::setUp();

        // Filter tahun Daftar SPPD memakai YEAR() MySQL; padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
        }

        $this->seed(RoleAndPermissionSeeder::class);
        $this->withoutVite();

        $dinkes = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::DINKES, 'level' => 1]);
        $this->puskesmas = Department::create(['name' => 'Puskesmas Poasia', 'type' => DepartmentType::PUSKESMAS, 'level' => 2, 'parent_id' => $dinkes->id]);
        $this->dinas = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
        $this->admin = $this->pegawai('admin_opd');
        $this->staf = $this->pegawai('staf', ['name' => 'Staf Puskesmas']);

        $this->kader = $this->jabatan('Kader Posyandu', User::TENAGA_MITRA_ROLE);
        $this->bidan = $this->jabatan('Bidan', 'staf');
        $this->kapus = $this->jabatan('Kepala Puskesmas Poasia', 'kepala_opd', PositionScope::DEPARTMENT, 20);
    }

    private function pegawai(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(['department_id' => $this->puskesmas->id, ...$attributes]);
        $user->assignRole($role);

        return $user;
    }

    private function tenagaMitra(string $name = 'Kader Luar'): User
    {
        return $this->pegawai(User::TENAGA_MITRA_ROLE, [
            'name' => $name,
            'username' => null,
            'nip' => null,
            'position_id' => $this->kader->id,
        ]);
    }

    private function jabatan(string $name, ?string $role, PositionScope $scope = PositionScope::NONE, int $level = Position::DEFAULT_LEVEL): Position
    {
        return Position::create(['name' => $name, 'default_role' => $role, 'uniqueness_scope' => $scope->value, 'level' => $level]);
    }

    /** SPPD milik staf Puskesmas, dengan pengikut bila diberikan. */
    private function sppd(string $purpose, array $pengikut = [], SppdStatus $status = SppdStatus::APPROVED): SppdRequest
    {
        $budget = new Budget(['department_id' => $this->puskesmas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $sppd = SppdRequest::create([
            'user_id' => $this->staf->id,
            'creator_id' => $this->admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => $purpose,
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
            'status' => $status,
        ]);

        foreach ($pengikut as $user) {
            $sppd->followers()->create(['user_id' => $user->id]);
        }

        return $sppd;
    }

    /** Form Tambah Pegawai sebagai Admin Puskesmas (unit kerja terisi otomatis). */
    private function formTambah()
    {
        return Livewire::actingAs($this->admin)->test(UserForm::class);
    }

    public function test_role_tenaga_mitra_tanpa_hak_akses(): void
    {
        $role = Role::findByName(User::TENAGA_MITRA_ROLE, 'web');

        $this->assertSame('Tenaga Mitra', $role->label);
        $this->assertSame([], $role->permissions->pluck('name')->all());
    }

    public function test_mode_tersedia_di_semua_unit_kerja(): void
    {
        // Super Admin: pemilih mode langsung tampil, juga sebelum unit kerja dipilih.
        $form = Livewire::actingAs($this->superAdmin)->test(UserForm::class)
            ->assertViewHas('showModeSwitch', true)
            ->assertSee('Jenis Data')
            ->assertSee('Orang dari luar OPD sebagai pengikut dalam perjalanan dinas.')
            ->set('department_id', $this->dinas->id)
            ->call('setTenagaMitra', true)
            ->assertSet('tenagaMitra', true)
            ->assertSet('role', User::TENAGA_MITRA_ROLE)
            ->set('name', 'Prof. Andi Akademisi')
            ->set('nip', '197001011995031001')
            ->set('jabatanTenagaMitra', 'Dosen Universitas Halu Oleo')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('name', 'Prof. Andi Akademisi')->firstOrFail();
        $this->assertTrue($user->isTenagaMitra());
        $this->assertSame($this->dinas->id, $user->department_id);
        $this->assertSame('197001011995031001', $user->nip);

        // Ganti unit kerja tidak mematikan mode.
        $form = Livewire::actingAs($this->superAdmin)->test(UserForm::class)
            ->set('department_id', $this->puskesmas->id)
            ->call('setTenagaMitra', true)
            ->set('department_id', $this->dinas->id)
            ->assertSet('tenagaMitra', true);

        // Menu Anggota DPRD dan Walikota & Wakil Walikota tidak menawarkan mode ini.
        foreach (['dprd', 'kepala_daerah'] as $type) {
            Livewire::actingAs($this->superAdmin)->withQueryParams(['type' => $type])->test(UserForm::class)
                ->assertViewHas('showModeSwitch', false)
                ->call('setTenagaMitra', true)
                ->assertSet('tenagaMitra', false);
        }
    }

    public function test_form_mode_tenaga_mitra_hanya_menampilkan_nama_nip_nik_jabatan(): void
    {
        $this->formTambah()
            ->assertSee('Akun Sistem')->assertSee('Username')->assertSee('Email Resmi')
            ->assertSee('Golongan / Pangkat')->assertSee('Role Otentikasi Sistem')->assertSee('Notifikasi Telegram')
            ->call('setTenagaMitra', true)
            ->assertSee('Nama Lengkap')->assertSee('NIP (Nomor Induk Pegawai)')->assertSee('NIK (Nomor Induk Kependudukan)')->assertSee('Jabatan')
            ->assertDontSee('Akun Sistem')->assertDontSee('Username')->assertDontSee('Password')->assertDontSee('Email Resmi')
            ->assertDontSee('Golongan / Pangkat')->assertDontSee('Role Otentikasi Sistem')->assertDontSee('Notifikasi Telegram');
    }

    public function test_menyimpan_tenaga_mitra_tanpa_akun_login(): void
    {
        $rank = Rank::create(['name' => 'Penata', 'group' => 'III/c']);

        $this->formTambah()
            // Isian dari mode Pegawai yang tersisa sebelum berpindah mode.
            ->set('nip', '198001012005011001')
            ->set('username', 'siti.kader')
            ->set('email', 'kader@contoh.com')
            ->set('rank_id', $rank->id)
            ->set('password', 'rahasia123')
            ->call('setTenagaMitra', true)
            ->set('name', 'Siti Kader')
            ->set('nik', '7471010101800001')
            ->call('pickJabatanTenagaMitra', $this->kader->id)
            ->assertSet('jabatanTenagaMitra', 'Kader Posyandu')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('name', 'Siti Kader')->firstOrFail();

        $this->assertTrue($user->isTenagaMitra());
        $this->assertSame([User::TENAGA_MITRA_ROLE], $user->getRoleNames()->all());
        $this->assertSame($this->puskesmas->id, $user->department_id);
        $this->assertSame($this->kader->id, $user->position_id);
        $this->assertSame('7471010101800001', $user->nik);
        $this->assertSame('198001012005011001', $user->nip);
        $this->assertNull($user->username);
        $this->assertNull($user->email);
        $this->assertNull($user->rank_id);
        // Tanpa akun login: password acak, bukan password bawaan maupun isian form.
        $this->assertFalse(Hash::check(config('users.default_password'), $user->password));
        $this->assertFalse(Hash::check('rahasia123', $user->password));
    }

    public function test_nip_tenaga_mitra_tetap_divalidasi(): void
    {
        $this->formTambah()
            ->call('setTenagaMitra', true)
            ->set('name', 'Siti Kader')
            ->set('nip', '12345')
            ->set('jabatanTenagaMitra', 'Kader Posyandu')
            ->call('save')
            ->assertHasErrors(['nip']);

        // NIP boleh dikosongkan (mis. akademisi swasta).
        $this->formTambah()
            ->call('setTenagaMitra', true)
            ->set('name', 'Siti Kader')
            ->set('jabatanTenagaMitra', 'Kader Posyandu')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(User::where('name', 'Siti Kader')->value('nip'));
    }

    public function test_jabatan_wajib_dan_tidak_boleh_struktural(): void
    {
        $this->jabatan('Wali Kota', 'walikota', PositionScope::SYSTEM, 1);

        $form = $this->formTambah()
            ->call('setTenagaMitra', true)
            ->set('name', 'Siti Kader');

        $form->call('save')->assertHasErrors(['jabatanTenagaMitra' => 'required']);

        // Nama jabatan struktural yang sudah terdaftar ditolak (tanpa membedakan huruf besar/kecil
        // maupun spasi, jadi "walikota" juga dikenali sebagai "Wali Kota").
        foreach (['kepala puskesmas poasia', 'walikota'] as $nama) {
            $form->set('jabatanTenagaMitra', $nama)
                ->assertViewHas('jabatanStatus', 'struktural')
                ->call('save')
                ->assertHasErrors(['jabatanTenagaMitra']);
        }

        // position_id kiriman form diabaikan: jabatan selalu ditentukan dari nama yang diketik.
        $form->set('position_id', $this->kapus->id)->call('save')->assertHasErrors(['jabatanTenagaMitra']);
        $this->assertDatabaseMissing('users', ['name' => 'Siti Kader']);

        // Jabatan staf yang sudah ada (mis. Bidan) boleh dipakai, cukup diketik namanya.
        $form->set('jabatanTenagaMitra', '  bidan ')
            ->assertSet('position_id', $this->bidan->id)
            ->assertViewHas('jabatanStatus', 'existing')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('name', 'Siti Kader')->firstOrFail();
        $this->assertTrue($user->isTenagaMitra());
        $this->assertSame($this->bidan->id, $user->position_id);
        $this->assertSame(4, Position::count());
    }

    public function test_saran_jabatan_menampilkan_semua_jabatan(): void
    {
        $waliKota = $this->jabatan('Wali Kota', 'walikota', PositionScope::SYSTEM, 1);
        $form = $this->formTambah()->call('setTenagaMitra', true);
        $names = fn ($s) => $s->pluck('name')->all();

        // Semua jabatan tampil: jabatan Tenaga Mitra, lalu staf, lalu struktural (redup, tidak bisa dipilih).
        $form->assertViewHas('jabatanSuggestions', fn ($s) => $names($s) === ['Kader Posyandu', 'Bidan', 'Wali Kota', 'Kepala Puskesmas Poasia']
            && $s->whereNull('unavailableReason')->pluck('name')->all() === ['Kader Posyandu', 'Bidan']);

        $form->set('jabatanTenagaMitra', 'kad')
            ->assertViewHas('jabatanSuggestions', fn ($s) => $names($s) === ['Kader Posyandu'])
            ->assertSee('Jabatan yang sudah terdaftar')
            ->set('jabatanTenagaMitra', 'Kepala')
            ->assertViewHas('jabatanSuggestions', fn ($s) => $names($s) === ['Kepala Puskesmas Poasia'])
            ->assertSee('Jabatan struktural, tidak untuk Tenaga Mitra')
            // Pencarian tanpa membedakan spasi: "walikota" menemukan "Wali Kota".
            ->set('jabatanTenagaMitra', 'walikota')
            ->assertViewHas('jabatanSuggestions', fn ($s) => $names($s) === ['Wali Kota']);

        // Jabatan yang redup tidak bisa dipilih walau dipanggil langsung.
        $form->set('jabatanTenagaMitra', '')
            ->call('pickJabatanTenagaMitra', $waliKota->id)
            ->assertSet('jabatanTenagaMitra', '')
            ->assertSet('position_id', '');
    }

    public function test_nama_jabatan_baru_ditambahkan_saat_disimpan(): void
    {
        $this->formTambah()
            ->call('setTenagaMitra', true)
            ->set('name', 'Andi Relawan')
            ->set('jabatanTenagaMitra', 'kader   PDIP')
            ->assertSet('position_id', '')
            ->assertViewHas('jabatanStatus', 'new')
            ->assertSee('akan ditambahkan sebagai jabatan baru')
            ->assertSee('Kader PDIP')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $position = Position::findByName('Kader PDIP');
        $this->assertNotNull($position);
        $this->assertSame('Kader PDIP', $position->name);
        $this->assertSame(User::TENAGA_MITRA_ROLE, $position->default_role);
        $this->assertSame(PositionScope::NONE, $position->uniqueness_scope);
        $this->assertSame($this->admin->id, $position->created_by);
        $this->assertSame($position->id, User::where('name', 'Andi Relawan')->value('position_id'));

        // Tenaga Mitra berikutnya dengan nama yang sama memakai jabatan itu, tanpa membuat duplikat.
        $this->formTambah()
            ->call('setTenagaMitra', true)
            ->set('name', 'Budi Relawan')
            ->set('jabatanTenagaMitra', 'KADER pdip')
            ->assertSet('position_id', $position->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Position::whereRaw('LOWER(name) = ?', ['kader pdip'])->count());
        $this->assertSame($position->id, User::where('name', 'Budi Relawan')->value('position_id'));
    }

    public function test_role_tenaga_mitra_tidak_ditawarkan_dan_tidak_bisa_dipaksa(): void
    {
        $form = Livewire::actingAs($this->superAdmin)->test(UserForm::class)
            ->set('department_id', $this->puskesmas->id)
            ->assertViewHas('roleOptions', fn ($options) => ! in_array(User::TENAGA_MITRA_ROLE, array_column($options, 'value'), true));

        $form->set('name', 'Budi Biasa')
            ->set('username', 'budi.biasa')
            ->set('role', User::TENAGA_MITRA_ROLE)
            ->call('save')
            ->assertHasErrors(['role']);

        $this->assertDatabaseMissing('users', ['username' => 'budi.biasa']);
    }

    public function test_mode_terkunci_saat_edit(): void
    {
        // Pegawai biasa tidak bisa diubah menjadi Tenaga Mitra.
        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->staf])
            ->assertViewHas('showModeSwitch', false)
            ->call('setTenagaMitra', true)
            ->assertSet('tenagaMitra', false);

        // Tenaga Mitra tetap Tenaga Mitra, tetap tanpa akun login.
        $tenagaMitra = $this->tenagaMitra();
        $password = $tenagaMitra->password;
        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $tenagaMitra])
            ->assertSet('tenagaMitra', true)
            ->assertSet('jabatanTenagaMitra', 'Kader Posyandu')
            ->assertSee('Edit Tenaga Mitra')
            ->assertDontSee('Akun Sistem')
            ->assertDontSee('Password Baru')
            ->call('setTenagaMitra', false)
            ->assertSet('tenagaMitra', true)
            ->set('name', 'Kader Luar Baru')
            ->set('nip', '198001012005011001')
            ->call('save')
            ->assertHasNoErrors();

        $tenagaMitra->refresh();
        $this->assertSame('Kader Luar Baru', $tenagaMitra->name);
        $this->assertSame('198001012005011001', $tenagaMitra->nip);
        $this->assertTrue($tenagaMitra->isTenagaMitra());
        $this->assertNull($tenagaMitra->username);
        $this->assertSame($password, $tenagaMitra->password);
    }

    public function test_jabatan_baru_dari_mode_tenaga_mitra_tanpa_memilih_tingkat(): void
    {
        // Hanya nama jabatan: tanpa pilihan tingkat jabatan maupun ringkasan role.
        Livewire::actingAs($this->admin)->test(PositionFormModal::class)
            ->call('open', null, 'Tokoh Masyarakat', User::TENAGA_MITRA_ROLE)
            ->assertSet('tier', PositionTier::TENAGA_MITRA->value)
            ->assertSee('Nama Jabatan')
            ->assertDontSee('Tingkat Jabatan')
            ->assertDontSee('Role pegawai:')
            ->call('save')
            ->assertHasNoErrors();

        $position = Position::findByName('Tokoh Masyarakat');
        $this->assertSame(User::TENAGA_MITRA_ROLE, $position->default_role);
        $this->assertSame(PositionScope::NONE, $position->uniqueness_scope);

        // Tanpa mode Tenaga Mitra, tingkat Tenaga Mitra tidak bisa dikirim manual.
        $this->assertNotContains(PositionTier::TENAGA_MITRA, PositionTier::selectable());
        Livewire::actingAs($this->admin)->test(PositionFormModal::class)
            ->call('open', null, 'Relawan')
            ->set('tier', PositionTier::TENAGA_MITRA->value)
            ->call('save')
            ->assertHasErrors(['tier']);
        $this->assertNull(Position::findByName('Relawan'));
    }

    public function test_edit_jabatan_tenaga_mitra_hanya_nama(): void
    {
        // Super Admin biasanya juga melihat kolom Level; untuk jabatan Tenaga Mitra keduanya disembunyikan.
        Livewire::actingAs($this->superAdmin)->test(PositionFormModal::class)
            ->call('open', $this->kader->id)
            ->assertSee('Edit Jabatan')
            ->assertSee('Nama Jabatan')
            ->assertDontSee('Tingkat Jabatan')
            ->assertDontSee('Level')
            // Tingkat tidak bisa diganti walau dikirim langsung.
            ->set('tier', PositionTier::STAF->value)
            ->assertSet('tier', PositionTier::TENAGA_MITRA->value)
            ->set('name', 'Kader Posyandu Kelurahan')
            ->call('save')
            ->assertHasNoErrors();

        $this->kader->refresh();
        $this->assertSame('Kader Posyandu Kelurahan', $this->kader->name);
        $this->assertSame(User::TENAGA_MITRA_ROLE, $this->kader->default_role);

        // Jabatan staf biasa tetap menampilkan pilihan tingkat & level.
        Livewire::actingAs($this->superAdmin)->test(PositionFormModal::class)
            ->call('open', $this->bidan->id)
            ->assertSee('Tingkat Jabatan')
            ->assertSee('Level');
    }

    public function test_tenaga_mitra_tidak_bisa_menjadi_pelaksana(): void
    {
        $tenagaMitra = $this->tenagaMitra();

        // Tidak ada di daftar pilihan pelaksana, dan pilihan paksa dibatalkan.
        Livewire::actingAs($this->admin)->test(SppdCreate::class)
            ->assertViewHas('users', fn ($users) => $users->contains('id', $this->staf->id) && ! $users->contains('id', $tenagaMitra->id))
            ->call('selectUser', $tenagaMitra->id)
            ->assertSet('user_id', null)
            ->assertSet('errorMessage', User::TENAGA_MITRA_NOT_PELAKSANA)
            ->set('user_id', $tenagaMitra->id)
            ->assertSet('user_id', null);

        // Lewat URL Tahap 1 (user_id berupa hashid).
        Livewire::actingAs($this->admin)->withQueryParams(['user_id' => $tenagaMitra->getRouteKey(), 'domain' => 'dalam_daerah'])
            ->test(SppdCreate::class)
            ->assertSet('user_id', null);

        // Lewat URL Tahap 2 langsung.
        Livewire::actingAs($this->admin)->withQueryParams(['user_id' => $tenagaMitra->id, 'domain' => 'dalam_daerah'])
            ->test(SppdCreateDetails::class)
            ->assertForbidden();

        // Mengganti pelaksana di tengah pengisian form (di sini form Edit Perbaikan).
        $sppd = $this->sppd('Rapat koordinasi', [], SppdStatus::IN_PROGRESS);
        Livewire::actingAs($this->admin)->withQueryParams(['sppd_id' => $sppd->id])
            ->test(SppdCreateDetails::class)
            ->assertOk()
            ->set('user_id', $tenagaMitra->id)
            ->assertForbidden();

        $this->assertSame($this->staf->id, $sppd->refresh()->user_id);
        $this->assertFalse(SppdRequest::where('user_id', $tenagaMitra->id)->exists());
    }

    public function test_tenaga_mitra_bisa_dipilih_sebagai_pengikut(): void
    {
        $tenagaMitra = $this->tenagaMitra();

        $sppd = $this->sppd('Rapat koordinasi', [], SppdStatus::IN_PROGRESS);

        Livewire::actingAs($this->admin)->withQueryParams(['sppd_id' => $sppd->id])
            ->test(SppdCreateDetails::class)
            ->assertViewHas('users', fn ($users) => $users->contains('id', $tenagaMitra->id))
            ->assertSee('Tenaga Mitra');
    }

    public function test_tenaga_mitra_bukan_calon_pptk(): void
    {
        $tenagaMitra = $this->tenagaMitra();
        $sppd = $this->sppd('Rapat koordinasi');

        $result = Livewire::actingAs($this->admin)->test(PptkSelector::class, ['sppd' => $sppd])
            ->instance()
            ->searchPptk('');

        $ids = array_column($result['options'], 'value');
        $this->assertContains((string) $this->staf->id, $ids);
        $this->assertNotContains((string) $tenagaMitra->id, $ids);
    }

    public function test_tenaga_mitra_tidak_punya_akun_login(): void
    {
        $tenagaMitra = $this->tenagaMitra();

        // Halaman detail: tanpa username dan tanpa tautan login sebagai pengguna.
        $this->actingAs($this->superAdmin)->get(route('master.users.show', $tenagaMitra))
            ->assertOk()
            ->assertSee('Tidak ada. Tenaga Mitra hanya dicatat sebagai pengikut perjalanan dinas.')
            ->assertDontSee('Salin tautan login sebagai pengguna');

        // Tautan impersonasi yang dibuat manual tetap ditolak.
        $url = URL::temporarySignedRoute('users.impersonate', now()->addMinutes(5), [
            'user' => $tenagaMitra,
            'by' => $this->superAdmin->id,
            'nonce' => (string) Str::uuid(),
        ]);
        $this->get($url)->assertForbidden();
    }

    /** Daftar jabatan form Pegawai biasa: semua jabatan bisa dicari, tetap dengan validasinya. */
    public function test_daftar_jabatan_pegawai_menampilkan_semua_jabatan(): void
    {
        $waliKota = $this->jabatan('Wali Kota', 'walikota', PositionScope::SYSTEM, 1);
        $direktur = $this->jabatan('Direktur RSUD', 'kepala_opd', PositionScope::DEPARTMENT, 20);
        $names = fn ($p) => $p->pluck('name')->all();

        $form = Livewire::actingAs($this->superAdmin)->test(UserForm::class)
            ->set('department_id', $this->dinas->id)
            ->set('searchPosition', 'direktur')
            ->assertViewHas('positions', fn ($p) => $names($p) === ['Direktur RSUD'] && $p->first()->unavailableReason === null)
            // "wali kota" maupun "walikota" menemukan Wali Kota, tetapi redup karena diatur di menunya sendiri.
            ->set('searchPosition', 'walikota')
            ->assertViewHas('positions', fn ($p) => $names($p) === ['Wali Kota'] && $p->first()->unavailableReason !== null)
            ->assertSee('Diatur di menu Walikota & Wakil Walikota')
            ->set('searchPosition', 'wali kota')
            ->assertViewHas('positions', fn ($p) => $names($p) === ['Wali Kota']);

        // Tanpa pencarian, jabatan yang redup ada di urutan terakhir.
        $form->set('searchPosition', '')
            ->assertViewHas('positions', fn ($p) => $p->last()->is($waliKota));

        // Tidak bisa dipilih, dan ditolak bila tetap dikirim.
        $form->call('selectPosition', $waliKota->id)
            ->assertSet('position_id', '')
            ->call('selectPosition', $direktur->id)
            ->assertSet('position_id', $direktur->id)
            ->set('position_id', $waliKota->id)
            ->set('name', 'Budi Biasa')
            ->set('username', 'budi.biasa')
            ->set('role', 'staf')
            ->call('save')
            ->assertHasErrors(['position_id']);
        $this->assertDatabaseMissing('users', ['username' => 'budi.biasa']);

        // Menu Walikota & Wakil Walikota tetap hanya menampilkan kedua jabatan itu.
        Livewire::actingAs($this->superAdmin)->withQueryParams(['type' => 'kepala_daerah'])->test(UserForm::class)
            ->assertViewHas('positions', fn ($p) => $names($p) === ['Wali Kota']);
    }
}
