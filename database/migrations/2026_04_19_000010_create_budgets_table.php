<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('budgets', function (Blueprint $table) {
      $table->id();
      $table->foreignId('department_id')->constrained()->cascadeOnDelete();
      $table->string('name')->comment('Nama kegiatan/mata anggaran');
      $table->unsignedSmallInteger('year')->comment('Tahun anggaran');
      $table->decimal('total_amount', 15, 2)->default(0)->comment('Pagu anggaran');
      $table->timestamps();

      $table->index(['department_id', 'year']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('budgets');
  }
};
