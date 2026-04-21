<?php

namespace Database\Seeders;

use App\Enums\EmployeeType;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyUserSeeder extends Seeder
{
  public function run(): void
  {
    $password = Hash::make('password');

    $rankIVb  = Rank::where('group', 'IV/b')->first();
    $rankIVa  = Rank::where('group', 'IV/a')->first();
    $rankIIId = Rank::where('group', 'III/d')->first();
    $rankIIIc = Rank::where('group', 'III/c')->first();
    $rankIIIb = Rank::where('group', 'III/b')->first();
    $rankIIIa = Rank::where('group', 'III/a')->first();

    $kadin     = Position::where('name', 'Kepala Dinas')->first();
    $sekdin    = Position::where('name', 'Sekretaris Dinas')->first();
    $kabid     = Position::where('name', 'Kepala Bidang')->first();
    $staf      = Position::where('name', 'Staf')->first();
    $bendahara = Position::where('name', 'Bendahara')->first();
    $pptk      = Position::where('name', 'PPTK')->first();
    $camat     = Position::where('name', 'Camat')->first();

    // ─── Pejabat Utama ───
    $users = [
      // Sekda
      [
        'name'          => 'Dr. H. Ahmad Fauzi, M.Si.',
        'email'         => 'sekda@sppd.test',
        'nip'           => '197205141998031005',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIVb?->id,
        'position_name' => 'Sekretaris Daerah',
        'role'          => 'sekda',
      ],
      // Kepala Dinas Pendidikan
      [
        'name'          => 'Drs. Budi Santoso, M.Pd.',
        'email'         => 'kadisdik@sppd.test',
        'nip'           => '196808231994031008',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIVb?->id,
        'position_id'   => $kadin?->id,
        'position_name' => 'Kepala Dinas Pendidikan',
        'role'          => 'kepala_opd',
      ],
      // Sekretaris Dinas Pendidikan
      [
        'name'          => 'Hj. Siti Nurhaliza, S.Pd., M.M.',
        'email'         => 'sekdisdik@sppd.test',
        'nip'           => '198001152005012003',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIVa?->id,
        'position_id'   => $sekdin?->id,
        'position_name' => 'Sekretaris Dinas Pendidikan',
        'role'          => 'sekretaris',
      ],
      // Kabid di Dinas Pendidikan
      [
        'name'          => 'Ir. Rahman Hakim, M.T.',
        'email'         => 'kabid_disdik@sppd.test',
        'nip'           => '198505202010011015',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIIId?->id,
        'position_id'   => $kabid?->id,
        'position_name' => 'Kabid Pembinaan SMA',
        'role'          => 'kasubag',
      ],
      // Staf Dinas Pendidikan
      [
        'name'          => 'Dewi Lestari, S.E.',
        'email'         => 'staff_disdik@sppd.test',
        'nip'           => '199203101019032004',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIIIb?->id,
        'position_id'   => $staf?->id,
        'position_name' => 'Staf Umum',
        'role'          => 'staff',
      ],
      // Bendahara Dinas Pendidikan
      [
        'name'          => 'Rina Wati, S.E.',
        'email'         => 'bendahara_disdik@sppd.test',
        'nip'           => '198807152012012005',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIIIa?->id,
        'position_id'   => $bendahara?->id,
        'position_name' => 'Bendahara',
        'role'          => 'bendahara',
      ],
      // PPTK Dinas Pendidikan
      [
        'name'          => 'Agus Setiawan, S.T.',
        'email'         => 'pptk_disdik@sppd.test',
        'nip'           => '199105252015011003',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIIIb?->id,
        'position_id'   => $pptk?->id,
        'position_name' => 'PPTK',
        'role'          => 'pptk',
      ],
      // Kepala Dinas Kesehatan
      [
        'name'          => 'dr. Maya Sari, Sp.A.',
        'email'         => 'kadinkes@sppd.test',
        'nip'           => '197512041999032001',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIVb?->id,
        'position_id'   => $kadin?->id,
        'position_name' => 'Kepala Dinas Kesehatan',
        'role'          => 'kepala_opd',
      ],
      // Staf Dinas Kesehatan
      [
        'name'          => 'Andi Pratama, S.KM.',
        'email'         => 'staff_dinkes@sppd.test',
        'nip'           => '199408182020011006',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIIIa?->id,
        'position_id'   => $staf?->id,
        'position_name' => 'Staf Pelayanan Kesehatan',
        'role'          => 'staff',
      ],
      // Kepala DPUPR
      [
        'name'          => 'Ir. H. Bambang Surya, M.T.',
        'email'         => 'kadpupr@sppd.test',
        'nip'           => '196901171995031003',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIVb?->id,
        'position_id'   => $kadin?->id,
        'position_name' => 'Kepala Dinas PUPR',
        'role'          => 'kepala_opd',
      ],
      // Kepala Bappeda
      [
        'name'          => 'Dr. Hendra Wijaya, S.E., M.Si.',
        'email'         => 'kabappeda@sppd.test',
        'nip'           => '197310051997031002',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIVb?->id,
        'position_id'   => $kadin?->id,
        'position_name' => 'Kepala Bappeda',
        'role'          => 'kepala_opd',
      ],
      // Admin OPD Dinas Pendidikan
      [
        'name'          => 'Fitri Handayani, A.Md.',
        'email'         => 'admin_disdik@sppd.test',
        'nip'           => '199611302020022001',
        'employee_type' => EmployeeType::PNS,
        'department_id' => rand(1, 143),
        'rank_id'       => $rankIIIa?->id,
        'position_id'   => $staf?->id,
        'position_name' => 'Admin SPPD',
        'role'          => 'admin_opd',
      ],
      [
        'name'          => 'Ilmi Faizan, S.T.',
        'email'         => 'admin_kominfo@sppd.test',
        'nip'           => null,
        'employee_type' => EmployeeType::HONORER,
        'department_id' => 36,
        'rank_id'       => null,
        'position_id'   => $staf?->id,
        'position_name' => 'Admin Kominfo',
        'role'          => 'admin_opd',
      ],
    ];

    foreach ($users as $index => $userData) {
      $role = $userData['role'];
      unset($userData['role']);

      $userData['username']  = strstr($userData['email'], '@', true);
      $userData['password']  = $password;
      $userData['is_active'] = true;

      $user = User::updateOrCreate(
        ['email' => $userData['email']],
        $userData
      );

      $user->syncRoles([$role]);
    }
  }
}
