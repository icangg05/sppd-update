<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('user_department_assignments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('department_id')->constrained()->cascadeOnDelete();
      $table->string('assignment_type');
      $table->timestamps();

      $table->unique(['user_id', 'department_id', 'assignment_type'], 'uda_unique');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('user_department_assignments');
  }
};
