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
      $table->string('program')->nullable();
      $table->string('activity')->nullable();
      $table->string('account_code')->nullable();
      $table->string('description');
      $table->string('type')->nullable();
      $table->string('source')->nullable()->comment('Sumber anggaran, misalnya APBD, APBN, Hibah, dll');
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
