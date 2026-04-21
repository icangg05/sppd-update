<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'letterhead')) {
                $table->text('letterhead')->nullable()->after('name');
            }
            if (!Schema::hasColumn('departments', 'head_id')) {
                $table->foreignId('head_id')->nullable()->after('parent_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'head_id')) {
                $table->dropForeign(['head_id']);
                $table->dropColumn('head_id');
            }
            if (Schema::hasColumn('departments', 'letterhead')) {
                $table->dropColumn('letterhead');
            }
        });
    }
};
