<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lyro AI Agent > Proactive roles: a persona/goal Lyro can proactively
    // pursue with a visitor (e.g. "Sales assistant" nudging toward a plan),
    // as opposed to Actions/Procedures which are reactive, triggered mid-chat.
    public function up(): void
    {
        Schema::create('ai_proactive_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('goal');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_proactive_roles');
    }
};
