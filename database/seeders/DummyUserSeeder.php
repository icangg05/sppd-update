<?php

namespace Database\Seeders;

use App\Enums\EmployeeType;
use App\Models\Position;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyUserSeeder extends Seeder
{
  public function run(): void
  {
    $password = Hash::make('admin');

    $rankIId  = Rank::where('group', 'II/d')->first();
    $rankIIIa = Rank::where('group', 'III/a')->first();
    $rankIVa  = Rank::where('group', 'IV/a')->first();
    $rankIVb  = Rank::where('group', 'IV/b')->first();
    $rankIVc  = Rank::where('group', 'IV/c')->first();
    $rankIVd  = Rank::where('group', 'IV/d')->first();

    $kadin                = Position::where('name', 'Kepala Dinas')->first();
    $sekdin               = Position::where('name', 'Sekretaris Dinas')->first();
    $bendaharaPengeluaran = Position::where('name', 'Bendahara Pengeluaran')->first();
    $staf                 = Position::where('name', 'Staf')->first();
    $inspektur            = Position::where('name', 'Inspektur')->first();
    $sekrin               = Position::where('name', 'Sekretaris Inspektur')->first();

    // ─── Pejabat Seeder ───
    $users = [
      // Walikota
      [
        'name'          => 'dr. Hj. SISKA KARINA, SKM.',
        'username'      => 'walikota',
        'email'         => null,
        'nik'           => null,
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 45,
        'rank_id'       => null,
        'role'          => 'walikota',
      ],

      // Wakil Walikota
      [
        'name'          => 'SUDJIRMAN, S.I.Kom.',
        'username'      => 'wakil_walikota',
        'email'         => null,
        'nik'           => null,
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 45,
        'rank_id'       => null,
        'role'          => 'wakil_walikota',
      ],

      // Sekda
      [
        'name'          => 'AMIR HASAN, STP., SH., M.Si.',
        'username'      => 'sekda',
        'email'         => null,
        'nik'           => null,
        'nip'           => '197201031993031009',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 3,
        'rank_id'       => $rankIVd?->id,
        'role'          => 'sekda',
      ],

      // Kepala Kominfo
      [
        'name'          => 'SAHURIYANTO MERONDA, SP., MM.',
        'username'      => 'kadis_kominfo',
        'email'         => null,
        'nik'           => '7471020205860001',
        'nip'           => '198003252009011001',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 36,
        'rank_id'       => $rankIVb?->id,
        'position_id'   => $kadin?->id,
        'role'          => 'kepala_opd',
      ],
      // Sekretaris Kominfo
      [
        'name'          => 'WAWAN ASTANTO, S.Sos., M.Si',
        'username'      => 'sekretaris_kominfo',
        'email'         => null,
        'nik'           => null,
        'nip'           => '199611302020022001',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 36,
        'rank_id'       => $rankIVa?->id,
        'position_id'   => $sekdin?->id,
        'role'          => 'sekretaris_opd',
      ],
      // Bendahara Kominfo
      [
        'name'          => 'HARTINI, A.Md. Komp',
        'username'      => 'bendahara_kominfo',
        'email'         => null,
        'nik'           => '7471056512940001',
        'nip'           => '199412252019032002',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 36,
        'rank_id'       => $rankIId?->id,
        'position_id'   => $bendaharaPengeluaran?->id,
        'role'          => 'staf',
      ],
      // Admin OPD Kominfo
      [
        'name'          => 'ILMI FAIZAN, S.T.',
        'username'      => 'admin_kominfo',
        'email'         => 'ilmifaizan1112@gmail.com',
        'nip'           => null,
        'employee_type' => EmployeeType::HONORER,
        'department_id' => 36,
        'rank_id'       => null,
        'position_id'   => $staf?->id,
        'role'          => 'admin_opd',
      ],

      // Inspektur
      [
        'name'          => 'Dr. Sri Yusnita, ST., MM., CGCAE., CGRE.',
        'username'      => 'inspektur',
        'email'         => null,
        'nik'           => '7471075808760001',
        'nip'           => '197608182002122007',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 48,
        'rank_id'       => $rankIVc?->id,
        'position_id'   => $inspektur?->id,
        'role'          => 'kepala_opd',
      ],
      // Sekretaris Inspektorat
      [
        'name'          => 'Hj. Sennatang, SE.,MM',
        'username'      => 'sekretaris_inspektorat',
        'email'         => null,
        'nik'           => '7471040607780032',
        'nip'           => '196812071993032011',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 48,
        'rank_id'       => $rankIVa?->id,
        'position_id'   => $sekrin?->id,
        'role'          => 'sekretaris_opd',
      ],
      // Bendahara Inspektorat
      [
        'name'          => 'Gusti Ayu Putu Putri Satriadani, S.Tr.IP',
        'username'      => 'bendahara_inspektorat',
        'email'         => null,
        'nik'           => '7471041007020002',
        'nip'           => '199907242022082002',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 48,
        'rank_id'       => $rankIIIa?->id,
        'position_id'   => $bendaharaPengeluaran?->id,
        'role'          => 'staf',
      ],
      // Admin Inspektorat
      [
        'name'          => 'Innayah Maghfirah Patola, S.H',
        'username'      => 'admin_inspektorat',
        'email'         => null,
        'nip'           => '199912282025062006',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 48,
        'rank_id'       => $rankIIIa?->id,
        'position_id'   => $staf?->id,
        'role'          => 'admin_opd',
      ],
    ];

    foreach ($users as $_ => $userData) {
      $role = $userData['role'];
      unset($userData['role']);

      $userData['password']  = $password;
      $userData['is_active'] = true;

      $user = User::updateOrCreate(
        ['username' => $userData['username']],
        $userData
      );

      $user->syncRoles([$role]);
    }
  }
}
