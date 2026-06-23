<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Hapus tabel yang sudah tidak digunakan: kosong dan tanpa referensi kode.
   *
   * Catatan: sppd_advance_receipts (fitur panjar) & model_has_permissions
   * (inti Spatie permission) TIDAK dihapus karena masih aktif dipakai,
   * meskipun saat ini masih kosong.
   */
  public function up(): void
  {
    Schema::disableForeignKeyConstraints();

    foreach ([
      'document_signatories',
      'user_department_assignments',
      'signature_settings',
      'settings',
      'notifications',
      'sessions',
      'bank_accounts',
    ] as $table) {
      Schema::dropIfExists($table);
    }

    Schema::enableForeignKeyConstraints();
  }

  public function down(): void
  {
    Schema::create('bank_accounts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('bank_name');
      $table->string('account_number');
      $table->string('account_holder');
      $table->timestamps();
    });

    Schema::create('sessions', function (Blueprint $table) {
      $table->string('id')->primary();
      $table->foreignId('user_id')->nullable()->index();
      $table->string('ip_address', 45)->nullable();
      $table->text('user_agent')->nullable();
      $table->longText('payload');
      $table->integer('last_activity')->index();
    });

    Schema::create('notifications', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('type');
      $table->morphs('notifiable');
      $table->text('data');
      $table->timestamp('read_at')->nullable();
      $table->timestamps();
    });

    Schema::create('settings', function (Blueprint $table) {
      $table->id();
      $table->string('key')->unique();
      $table->text('value')->nullable();
      $table->timestamps();
    });

    Schema::create('signature_settings', function (Blueprint $table) {
      $table->id();
      $table->string('provider_name')->unique()->comment('Nama provider: local_proxy, bssn, dll');
      $table->string('api_endpoint')->comment('URL endpoint provider');
      $table->text('basic_auth_encoded')->nullable()->comment('Basic auth encoded untuk API');
      $table->integer('timeout_seconds')->default(30)->comment('Timeout request ke API (detik)');
      $table->boolean('is_active')->default(true)->comment('Provider aktif atau tidak');
      $table->json('config')->nullable()->comment('Custom config per provider');
      $table->text('description')->nullable();
      $table->timestamps();

      $table->index('is_active');
    });

    Schema::create('user_department_assignments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('department_id')->constrained()->cascadeOnDelete();
      $table->string('assignment_type');
      $table->timestamps();

      $table->unique(['user_id', 'department_id', 'assignment_type'], 'uda_unique');
    });

    Schema::create('document_signatories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('department_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('position_id')->constrained();
      $table->string('name');
      $table->string('nik')->nullable()->comment('NIK untuk mapping ke e-sign provider');
      $table->string('title')->comment('Jabatan yang tercetak di dokumen');
      $table->string('signature_image')->nullable()->comment('Path gambar tanda tangan basah');
      $table->string('signature_image_path')->nullable()->comment('Path file gambar tanda tangan digital atau cache tanda tangan');
      $table->text('signature_image_notes')->nullable()->comment('Catatan tentang gambar tanda tangan');
      $table->boolean('requires_passphrase')->default(true)->comment('Apakah memerlukan passphrase saat sign');
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }
};
