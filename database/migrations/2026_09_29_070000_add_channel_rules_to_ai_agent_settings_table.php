<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->json('channel_rules')->nullable()->after('handoff_rules');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->dropColumn('channel_rules');
        });
    }
};
