<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('roles', function (Blueprint $table) {
      $table->string('color')->default('slate')->after('label')
        ->comment('Token warna Tailwind untuk badge role (lihat App\\Support\\BadgeColor)');
    });
  }

  public function down(): void
  {
    Schema::table('roles', function (Blueprint $table) {
      $table->dropColumn('color');
    });
  }
};
