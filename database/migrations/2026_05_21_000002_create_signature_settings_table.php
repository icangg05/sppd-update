<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
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
  }

  public function down(): void
  {
    Schema::dropIfExists('signature_settings');
  }
};
