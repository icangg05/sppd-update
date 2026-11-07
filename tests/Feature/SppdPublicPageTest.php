<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman publik SPPD (tanpa login): hanya terbuka dengan token yang benar dan
 * menampilkan kode QR halaman itu sendiri di kanan atas.
 */
class SppdPublicPageTest extends TestCase
{
    use RefreshDatabase;

    private function sppd(): SppdRequest
    {
        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $dinas->id, 'name' => 'Budi Staf']);
        $budget = new Budget(['department_id' => $dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $staf->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
            'status' => SppdStatus::APPROVED,
        ]);
    }

    public function test_halaman_publik_tampil_dengan_qr(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertSee('Detail Surat Perjalanan Dinas')
            ->assertSee('Budi Staf')
            ->assertSee('Rapat koordinasi')
            ->assertSee('Kode QR halaman publik SPPD ini')
            ->assertSee('<svg', false);
    }

    public function test_token_salah_ditolak(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();

        $this->get(route('sppd.public', ['sppd' => $sppd, 'token' => str_repeat('x', 64)]))->assertNotFound();
    }
}
