<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('position_requests', function (Blueprint $table) {
      $table->id();
      $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
      $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
      $table->string('name');
      $table->text('reason')->nullable();
      $table->string('status')->default('pending');
      $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
      $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamp('reviewed_at')->nullable();
      $table->text('review_note')->nullable();
      $table->timestamps();

      $table->index('status');
      $table->index('name');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('position_requests');
  }
};
