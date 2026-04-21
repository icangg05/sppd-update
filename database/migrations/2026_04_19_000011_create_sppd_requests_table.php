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
      $table->text('purpose')->comment('Maksud perjalanan');
      $table->date('start_date');
      $table->date('end_date');
      $table->string('domain')->default('dalam_daerah')->comment('dalam_daerah, luar_daerah, bimtek');
      $table->string('status')->default('draft')->comment('draft, in_progress, approved, rejected, completed');
      $table->string('document_number')->nullable()->comment('Nomor surat, diisi saat final');
      $table->text('notes')->nullable();
      $table->boolean('is_secretariat')->default(false)->comment('Penanda telaah sekretariat');
      $table->timestamps();

      $table->index('status');
      $table->index('domain');
      $table->index(['start_date', 'end_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sppd_requests');
  }
};
