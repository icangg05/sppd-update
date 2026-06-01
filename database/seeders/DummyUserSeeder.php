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

    $rankIIb  = Rank::where('group', 'II/b')->first();
    $rankIId  = Rank::where('group', 'II/d')->first();
    $rankIIIa = Rank::where('group', 'III/a')->first();
    $rankIIId = Rank::where('group', 'III/d')->first();
    $rankIVa  = Rank::where('group', 'IV/a')->first();
    $rankIVb  = Rank::where('group', 'IV/b')->first();
    $rankIVc  = Rank::where('group', 'IV/c')->first();
    $rankIVd  = Rank::where('group', 'IV/d')->first();

    $kadin                = Position::where('name', 'Kepala Dinas')->first();
    $kabag                = Position::where('name', 'Kepala Bagian')->first();
    $sekdin               = Position::where('name', 'Sekretaris Dinas')->first();
    $bendaharaPengeluaran = Position::where('name', 'Bendahara Pengeluaran')->first();
    $staf                 = Position::where('name', 'Staf')->first();
    $inspektur            = Position::where('name', 'Inspektur')->first();
    $sekrin               = Position::where('name', 'Sekretaris Inspektur')->first();
    $sekwan               = Position::where('name', 'Sekretaris Dewan')->first();
    $sekda                = Position::where('name', 'Sekretaris Daerah')->first();

    $walikota             = Position::where('name', 'Walikota')->first();
    $wakilWalikota        = Position::where('name', 'Wakil Walikota')->first();

    $asisten1             = Position::where('name', 'Asisten Pemerintahan dan Kesejahteraan Rakyat')->first();
    $asisten2             = Position::where('name', 'Asisten Administrasi Umum')->first();
    $asisten3             = Position::where('name', 'Asisten Perekonomian dan Pembangunan')->first();


    // ─── Pejabat Seeder ───
    $users = [
      // Walikota
      [
        'name'          => 'dr. Hj. SISKA KARINA, SKM.',
        'username'      => 'walikota',
        'email'         => null,
        'nik'           => '7471086212880002',
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 3,
        'rank_id'       => null,
        'position_id'   => $walikota?->id,
        'role'          => 'walikota',
      ],
      // Wakil Walikota
      [
        'name'          => 'SUDIRMAN, S.I.Kom.',
        'username'      => 'wakil_walikota',
        'email'         => null,
        'nik'           => null,
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 3,
        'rank_id'       => null,
        'position_id'   => $wakilWalikota?->id,
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
        'position_id'   => $sekda?->id,
        'role'          => 'sekda',
      ],

      // Asisten 1
      [
        'name'          => 'Adriana Musaruddin, S.Sos, M.Si.',
        'username'      => 'asisten1',
        'email'         => null,
        'nik'           => null,
        'nip'           => '197201031993131009',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 3,
        'rank_id'       => $rankIVc?->id,
        'position_id'   => $asisten1?->id,
        'role'          => 'asisten',
      ],
      // Asisten 2
      [
        'name'          => 'La Ode Abd. Manas Salihin, S.Sos, M.Si.',
        'username'      => 'asisten2',
        'email'         => null,
        'nik'           => null,
        'nip'           => '197201031193031009',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 3,
        'rank_id'       => $rankIVc?->id,
        'position_id'   => $asisten2?->id,
        'role'          => 'asisten',
      ],
      // Asisten 3
      [
        'name'          => 'Ir. Nismawati, M.Si.',
        'username'      => 'asisten3',
        'email'         => null,
        'nik'           => null,
        'nip'           => '197201031933031009',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 3,
        'rank_id'       => $rankIVc?->id,
        'position_id'   => $asisten3?->id,
        'role'          => 'asisten',
      ],
      // Sekda
      [
        'name'          => 'Andri Irdas Pangeran, S.Kom.',
        'username'      => 'admin_setda',
        'email'         => null,
        'nik'           => null,
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 3,
        'rank_id'       => null,
        'position_id'   => $staf?->id,
        'role'          => 'admin_opd',
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

      // Ketua DPRD
      [
        'name'          => 'LAODE MUH. INARTO, ST',
        'username'      => 'ketua_dprd',
        'email'         => null,
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 2,
        'rank_id'       => null,
        'position_id'   => null,
        'role'          => 'pimpinan_dprd',
        'dprd_jabatan'  => 'Ketua DPRD KOTA KENDARI',
        'partai'        => 'F. NASDEM',
      ],
      // Anggota DPRD
      [
        'name'          => 'H. ISHAK ISMAIL,S.H',
        'username'      => 'anggota_dprd',
        'email'         => null,
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 2,
        'rank_id'       => null,
        'position_id'   => null,
        'role'          => 'anggota_dprd',
        'dprd_jabatan'  => 'ANGGOTA KOMISI III',
        'partai'        => 'F. PDI-P',
      ],
      // Sekwan
      [
        'name'          => 'M. IBRAHIM MUIS, S.Sos',
        'username'      => 'sekwan',
        'email'         => null,
        'nik'           => '7471021011700001',
        'nip'           => '197011162000031004',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 2,
        'rank_id'       => $rankIVb?->id,
        'position_id'   => $sekwan?->id,
        'role'          => 'sekwan',
      ],
      // Kabag Umum DPRD
      [
        'name'          => 'DASRIL, S.STP',
        'username'      => 'kabag_dprd',
        'email'         => null,
        'nik'           => '7471050706830005',
        'nip'           => '198306072003121002',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 2,
        'rank_id'       => $rankIVa?->id,
        'position_id'   => $kabag?->id,
        'role'          => 'kabid_irban_kabag',
      ],
      // Kabag Keuangan DPRD
      [
        'name'          => "Drs. ASMAN SA'ABY",
        'username'      => 'kabag2_dprd',
        'email'         => null,
        'nik'           => '7471060503020011',
        'nip'           => '196803011988101001',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 2,
        'rank_id'       => $rankIVa?->id,
        'position_id'   => $kabag?->id,
        'role'          => 'kabid_irban_kabag',
      ],
      // Bendahara DPRD
      [
        'name'          => 'KARTIKA PRATIWI, SE',
        'username'      => 'bendahara_dprd',
        'email'         => null,
        'nip'           => '198605122011012020',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 2,
        'rank_id'       => $rankIIId?->id,
        'position_id'   => $bendaharaPengeluaran?->id,
        'role'          => 'staf',
      ],
      // Staff DPRD
      [
        'name'          => 'ILHAM WIJAYA SAPUTRA LAPAI',
        'username'      => 'staf_dprd',
        'email'         => null,
        'nip'           => '198208072009011010',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 2,
        'rank_id'       => $rankIIb?->id,
        'position_id'   => $staf?->id,
        'role'          => 'staf',
      ],
      [
        'name'          => 'DIVANTI PRISILYA PUNARA, S.Ak',
        'username'      => 'admin_dprd',
        'email'         => null,
        'nik'           => '7471016003990001',
        'nip'           => '200003282025212006',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 2,
        'rank_id'       => $rankIIIa?->id,
        'position_id'   => $staf?->id,
        'role'          => 'admin_opd',
      ],

      // Kabag Bagian Adm. Pembangunan
      [
        'name'          => 'M. IBRAHIM MUIS, S.Sos',
        'username'      => 'kabag_bagpembangunan',
        'email'         => null,
        'nik'           => '7471021011700001',
        'nip'           => '197011162000031004',
        'employee_type' => EmployeeType::PNS,
        'department_id' => 153,
        'rank_id'       => $rankIVb?->id,
        'position_id'   => $kabag?->id,
        'role'          => 'kabid_irban_kabag',
      ],
      // Admin Bagian Adm. Pembangunan
      [
        'name'          => 'ASRID S.Pd., M.M',
        'username'      => 'admin_bagpembangunan',
        'email'         => null,
        'nik'           => '7471092303880003',
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 153,
        'rank_id'       => null,
        'position_id'   => $staf?->id,
        'role'          => 'admin_opd',
      ],
      // Staf Bagian Adm. Pembangunan
      [
        'name'          => 'ASHIDAYAT FEBRIADHI.A',
        'username'      => 'staf_bagpembangunan',
        'email'         => null,
        'nik'           => null,
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 153,
        'rank_id'       => null,
        'position_id'   => $staf?->id,
        'role'          => 'staf',
      ],
      // Staf Sub Bagian Sosial Budaya
      [
        'name'          => 'IWAN EFENDI',
        'username'      => 'iwan_efendi',
        'email'         => null,
        'nik'           => null,
        'nip'           => null,
        'employee_type' => EmployeeType::LAINNYA,
        'department_id' => 161,
        'rank_id'       => null,
        'position_id'   => $staf?->id,
        'role'          => 'staf',
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
