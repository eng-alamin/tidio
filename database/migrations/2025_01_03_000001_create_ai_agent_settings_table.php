<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('tone')->default('friendly'); // friendly, formal, playful...
            $table->string('default_language', 5)->default('en');
            $table->json('handoff_rules')->nullable(); // when to hand off to a human operator
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_settings');
    }
};
