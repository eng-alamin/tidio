<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            // Free-text guidance shown on the Guidance tab ("Keep answers short.
            // Never promise refund dates."). Separate from handoff_rules (JSON),
            // which is structured data for the Handoff tab, not prose.
            $table->text('instructions')->nullable()->after('tone');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->dropColumn('instructions');
        });
    }
};
