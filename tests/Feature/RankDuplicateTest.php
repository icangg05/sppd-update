<?php

namespace Tests\Feature;

use App\Livewire\RankIndex;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Nama pangkat boleh sama asal golongannya berbeda (mis. PNS & PPPK); pasangan nama + golongan
 * yang sama tetap ditolak, tanpa membedakan huruf besar/kecil.
 */
class RankDuplicateTest extends TestCase
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

    private function simpan(string $name, string $group, ?int $editingId = null)
    {
        $form = Livewire::test(RankIndex::class);
        if ($editingId) {
            $form->call('openEditModal', $editingId);
        }

        return $form->set('name', $name)->set('group', $group)->call('save');
    }

    public function test_nama_sama_boleh_bila_golongan_berbeda(): void
    {
        Rank::create(['name' => 'Penata Muda', 'group' => 'III/a']);

        $this->simpan('Penata Muda', 'IX')->assertHasNoErrors();

        $this->assertSame(2, Rank::where('name', 'Penata Muda')->count());
    }

    public function test_nama_dan_golongan_sama_ditolak(): void
    {
        Rank::create(['name' => 'Penata Muda', 'group' => 'III/a']);
        Rank::create(['name' => 'Tanpa Golongan', 'group' => null]);

        $this->simpan('penata muda', 'III/a')
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'error'
                && ($params['message'] ?? null) === 'Pangkat "Penata Muda (Gol. III/a)" sudah terdaftar.');
        $this->simpan('Tanpa Golongan', '');

        $this->assertSame(1, Rank::where('name', 'Penata Muda')->count());
        $this->assertSame(1, Rank::where('name', 'Tanpa Golongan')->count());
    }

    public function test_edit_tidak_bentrok_dengan_dirinya_sendiri_tapi_bentrok_dengan_yang_lain(): void
    {
        $pns = Rank::create(['name' => 'Penata', 'group' => 'III/c']);
        $pppk = Rank::create(['name' => 'Penata', 'group' => 'X']);

        // Simpan ulang tanpa perubahan: boleh.
        $this->simpan('Penata', 'III/c', $pns->id)->assertSet('showFormModal', false);

        // Golongan PPPK diubah menjadi III/c: bentrok dengan pangkat PNS, tidak tersimpan.
        $this->simpan('Penata', 'III/c', $pppk->id);
        $this->assertSame('X', $pppk->fresh()->group);
    }
}
