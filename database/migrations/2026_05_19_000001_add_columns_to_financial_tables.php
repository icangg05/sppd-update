<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('sppd_cost_details', function (Blueprint $table) {
      $table->string('cost_category')->default('lainnya')->after('user_id');
      $table->string('airline_name')->nullable()->after('description');
      $table->string('ticket_number')->nullable()->after('airline_name');
      $table->string('receipt_photo')->nullable()->after('total');
    });

    Schema::table('sppd_reports', function (Blueprint $table) {
      $table->date('report_date')->nullable()->after('report_text');
      $table->string('report_file')->nullable()->after('documentation_file');
    });
  }

  public function down(): void
  {
    Schema::table('sppd_cost_details', function (Blueprint $table) {
      $table->dropColumn(['cost_category', 'airline_name', 'ticket_number', 'receipt_photo']);
    });

    Schema::table('sppd_reports', function (Blueprint $table) {
      $table->dropColumn(['report_date', 'report_file']);
    });
  }
};
