<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('sppd_requests', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('Pelaksana perjalanan')->constrained()->cascadeOnDelete();
      $table->foreignId('creator_id')->comment('Pembuat draft')->constrained('users')->cascadeOnDelete();
      $table->foreignId('pptk_id')->nullable()->comment('PPTK penanggungjawab')->constrained('users')->nullOnDelete();
      $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
      $table->foreignId('category_id')->constrained('sppd_categories')->cascadeOnDelete();
      $table->string('recipient')->nullable()->comment('Kepada');
      $table->text('purpose')->comment('Maksud perjalanan');
      $table->text('problem')->nullable()->comment('Persoalan');
      $table->text('facts')->nullable()->comment('Fakta yang mempengaruhi');
      $table->text('analysis')->nullable()->comment('Analisis');
      $table->date('start_date');
      $table->date('end_date');
      $table->string('transport_type')->nullable()->comment('Jenis Angkutan');
      $table->string('transport_name')->nullable()->comment('Angkutan');
      $table->string('departure_place')->nullable()->comment('Tempat Berangkat');
      $table->string('domain')->default('dalam_daerah')->comment('dalam_daerah, lddp, ldlp');

      $table->string('urgency')->nullable()->comment('Kecepatan Telaah');
      $table->date('sppd_date')->nullable()->comment('Tanggal SPPD');
      $table->date('spt_date')->nullable()->comment('Tanggal SPT');
      $table->string('status')->default('in_progress')->comment('in_progress, approved, rejected, completed');
      $table->string('document_number')->nullable()->comment('Nomor surat');
      $table->text('notes')->nullable();
      $table->boolean('is_secretariat')->default(false)->comment('Penanda telaah sekretariat');
      $table->timestamps();

      $table->index('status');
      $table->string('attachment')->nullable()->comment('Path file dokumen pendukung');
      $table->string('spt_path')->nullable()->comment('Path file SPT PDF');
      $table->string('sppd_path')->nullable()->comment('Path file SPPD Pelaksana Utama PDF');
      $table->index('domain');
      $table->index(['start_date', 'end_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sppd_requests');
  }
};
