<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            // Lyro > Setup checklist: when the owner first ran a real Playground test,
            // and when Lyro was first switched on for customers (is_active already exists).
            $table->timestamp('playground_tested_at')->nullable()->after('is_active');
            $table->timestamp('went_live_at')->nullable()->after('playground_tested_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->dropColumn(['playground_tested_at', 'went_live_at']);
        });
    }
};
