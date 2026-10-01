<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->string('agent_name')->default('Lyro')->after('workspace_id');
            $table->string('audience_answer_for')->default('everyone')->after('channel_rules');
            $table->string('audience_exclude_tag')->nullable()->after('audience_answer_for');
            $table->boolean('copilot_suggest_replies')->default(true)->after('audience_exclude_tag');
            $table->boolean('copilot_summarize')->default(true)->after('copilot_suggest_replies');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->dropColumn([
                'agent_name', 'audience_answer_for', 'audience_exclude_tag',
                'copilot_suggest_replies', 'copilot_summarize',
            ]);
        });
    }
};
