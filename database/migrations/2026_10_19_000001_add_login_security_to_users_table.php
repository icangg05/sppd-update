<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('is_active')
        ->comment('Jumlah percobaan login gagal berturut-turut; direset saat login berhasil');
      $table->timestamp('login_locked_until')->nullable()->after('failed_login_attempts')
        ->comment('Akun dikunci sementara sampai waktu ini');
      $table->timestamp('login_blocked_at')->nullable()->after('login_locked_until')
        ->comment('Akun diblokir dari login; hanya super_admin yang dapat membuka');
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['failed_login_attempts', 'login_locked_until', 'login_blocked_at']);
    });
  }
};
