<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('users', function (Blueprint $table) {
      $table->id();
      $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
      $table->string('name');
      $table->string('username')->unique()->nullable();
      $table->string('nip')->nullable();
      $table->string('nik')->nullable()->comment('Nomor Induk Kependudukan');
      $table->string('email')->unique()->nullable();
      $table->timestamp('email_verified_at')->nullable();
      $table->string('password');
      $table->string('phone')->nullable()->unique();
      $table->boolean('phone_verified')->default(false);
      $table->boolean('whatsapp_notify')->default(true);
      $table->string('changelog_seen_version')->nullable();
      $table->string('employee_type')->default('pns');
      $table->foreignId('rank_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
      $table->string('dprd_jabatan')->nullable();
      $table->string('partai')->nullable();
      $table->string('photo')->nullable();
      $table->boolean('is_active')->default(true);
      $table->rememberToken();
      $table->timestamps();
    });

    Schema::create('password_reset_tokens', function (Blueprint $table) {
      $table->string('email')->primary();
      $table->string('token');
      $table->timestamp('created_at')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('users');
    Schema::dropIfExists('password_reset_tokens');
  }
};
