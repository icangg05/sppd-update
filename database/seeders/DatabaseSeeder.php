<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
  use WithoutModelEvents;

  public function run(): void
  {
    $this->call([
      RoleAndPermissionSeeder::class,
      ProvinceAndRegencySeeder::class,
      PositionAndRankSeeder::class,
      SppdCategorySeeder::class,
      DepartmentSeeder::class,
      BudgetSeeder::class,
    ]);

    // Create default super admin
    $admin = User::factory()->create([
      'name'          => 'Super Admin',
      'username'      => 'super_admin',
      'email'         => 'admin@sppd.test',
      'password'      => Hash::make('password'),
      'employee_type' => 'pns',
      'is_active'     => true,
    ]);
    $admin->assignRole('super_admin');

    // Create dummy users with various roles
    $this->call(DummyUserSeeder::class);
  }
}
