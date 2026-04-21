<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('sppd_reports', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sppd_request_id')->constrained()->cascadeOnDelete();
      $table->text('report_text')->nullable()->comment('Isi laporan kegiatan');
      $table->string('receipt_file')->nullable()->comment('Path file bukti nota utama');
      $table->string('documentation_file')->nullable()->comment('Path file foto dokumentasi');
      $table->decimal('total_expense', 15, 2)->default(0)->comment('Total pengeluaran riil keseluruhan');
      $table->string('verification_status')->default('pending')->comment('pending, verified, returned');
      $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamp('verified_at')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sppd_reports');
  }
};
