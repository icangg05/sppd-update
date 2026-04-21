<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Add SPPD-specific foreign key columns to users table.
   * Separated from the initial users migration because departments, ranks,
   * and positions tables must exist first.
   */
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->foreignId('department_id')->nullable()->after('id')->constrained()->nullOnDelete();
      $table->foreignId('rank_id')->nullable()->after('employee_type')->constrained()->nullOnDelete();
      $table->foreignId('position_id')->nullable()->after('rank_id')->constrained()->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropConstrainedForeignId('department_id');
      $table->dropConstrainedForeignId('rank_id');
      $table->dropConstrainedForeignId('position_id');
    });
  }
};
