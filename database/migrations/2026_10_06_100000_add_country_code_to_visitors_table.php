<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            // ISO 3166-1 alpha-2 (e.g. "BD"). `location` keeps the human-readable name.
            $table->string('country_code', 2)->nullable()->after('location');
            $table->index(['workspace_id', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'country_code']);
            $table->dropColumn('country_code');
        });
    }
};
