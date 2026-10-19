<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifikasi pindah dari WhatsApp (Kirim.Chat) ke bot Telegram. Bot hanya bisa
 * mengirim ke chat_id yang didapat saat pengguna menekan Start, jadi nomor HP
 * tidak lagi diverifikasi — kolom phone tetap ada sebagai data kontak biasa.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      // Tidak unique: satu orang boleh berakun di dua instansi dengan Telegram yang sama.
      $table->string('telegram_chat_id', 32)->nullable()->index()->after('phone');
      $table->string('telegram_username')->nullable()->after('telegram_chat_id');
      $table->timestamp('telegram_linked_at')->nullable()->after('telegram_username');
    });

    Schema::table('users', function (Blueprint $table) {
      // Preferensi notifikasi pengguna tetap terbawa.
      $table->renameColumn('whatsapp_notify', 'telegram_notify');
    });

    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('phone_verified');
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->boolean('phone_verified')->default(false)->after('phone');
    });

    Schema::table('users', function (Blueprint $table) {
      $table->renameColumn('telegram_notify', 'whatsapp_notify');
    });

    Schema::table('users', function (Blueprint $table) {
      $table->dropIndex(['telegram_chat_id']);
      $table->dropColumn(['telegram_chat_id', 'telegram_username', 'telegram_linked_at']);
    });
  }
};
