<?php
  
  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;
  
  return new class extends Migration
  {
    public function up(): void
    {
      Schema::table('sppd_requests', function (Blueprint $table) {
        $table->string('recipient')->nullable()->after('category_id')->comment('Kepada');
        $table->text('problem')->nullable()->after('purpose')->comment('Persoalan');
        $table->text('facts')->nullable()->after('problem')->comment('Fakta yang mempengaruhi');
        $table->text('analysis')->nullable()->after('facts')->comment('Analisis');
        $table->string('transport_type')->nullable()->after('end_date')->comment('Jenis Angkutan');
        $table->string('transport_name')->nullable()->after('transport_type')->comment('Angkutan');
        $table->string('departure_place')->nullable()->after('transport_name')->comment('Tempat Berangkat');
        $table->string('urgency')->nullable()->after('domain')->comment('Kecepatan Telaah');
        $table->date('sppd_date')->nullable()->after('urgency');
        $table->date('spt_date')->nullable()->after('sppd_date');
      });
    }
  
    public function down(): void
    {
      Schema::table('ssppd_requests', function (Blueprint $table) {
        $table->dropColumn([
          'recipient', 'problem', 'facts', 'analysis', 
          'transport_type', 'transport_name', 'departure_place', 
          'urgency', 'sppd_date', 'spt_date'
        ]);
      });
    }
  };