<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ranks', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('group')->nullable()->comment('Golongan: I, II, III, IV');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ranks');
  }
};
