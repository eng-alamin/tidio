<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_data_sources', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_data_sources', 'hits_count')) {
                $table->unsignedInteger('hits_count')->default(0)->after('status');
            }
            if (! Schema::hasColumn('ai_data_sources', 'success_count')) {
                $table->unsignedInteger('success_count')->default(0)->after('hits_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_data_sources', function (Blueprint $table) {
            $table->dropColumn(['hits_count', 'success_count']);
        });
    }
};
