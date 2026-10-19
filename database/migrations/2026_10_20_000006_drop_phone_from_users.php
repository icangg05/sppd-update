<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor telepon pegawai tidak lagi dipakai fitur apa pun sejak notifikasi
 * pindah ke Telegram (chat_id), jadi kolomnya dibuang.
 */
return new class extends Migration
{
  public function up(): void
  {
    // Index unik dari migrasi awal; tidak ada di DB yang dibangun dari dump lama.
    if (Schema::hasIndex('users', 'users_phone_unique')) {
      Schema::table('users', function (Blueprint $table) {
        $table->dropUnique('users_phone_unique');
      });
    }

    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('phone');
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('phone')->nullable()->after('password');
    });
  }
};
