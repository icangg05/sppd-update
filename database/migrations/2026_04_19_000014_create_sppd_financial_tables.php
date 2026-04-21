<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Create all three financial tables in one migration to keep them grouped.
   */
  public function up(): void
  {
    // Rincian biaya estimasi (sebelum berangkat)
    Schema::create('sppd_cost_details', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sppd_request_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('description')->comment('Uraian biaya, misal: Tiket pesawat, Uang harian');
      $table->decimal('unit_cost', 15, 2)->default(0);
      $table->unsignedInteger('quantity')->default(1);
      $table->decimal('total', 15, 2)->default(0)->comment('unit_cost * quantity');
      $table->timestamps();
    });

    // Pengeluaran riil (setelah pulang)
    Schema::create('sppd_actual_expenses', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sppd_request_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('description');
      $table->decimal('amount', 15, 2)->default(0);
      $table->string('receipt_file')->nullable()->comment('Path file bukti/nota');
      $table->timestamps();
    });

    // Kuitansi panjar / uang muka
    Schema::create('sppd_advance_receipts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sppd_request_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->decimal('amount', 15, 2)->default(0);
      $table->string('receipt_number')->nullable();
      $table->string('receipt_file')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sppd_advance_receipts');
    Schema::dropIfExists('sppd_actual_expenses');
    Schema::dropIfExists('sppd_cost_details');
  }
};
