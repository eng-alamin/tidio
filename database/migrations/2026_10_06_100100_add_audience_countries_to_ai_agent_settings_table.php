<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            // JSON list of ISO alpha-2 codes, used when audience_answer_for = "specific_countries".
            $table->json('audience_countries')->nullable()->after('audience_exclude_tag');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->dropColumn('audience_countries');
        });
    }
};
