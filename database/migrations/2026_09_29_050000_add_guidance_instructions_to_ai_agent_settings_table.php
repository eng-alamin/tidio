<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->text('guidance_instructions')->nullable()->after('tone');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table) {
            $table->dropColumn('guidance_instructions');
        });
    }
};
