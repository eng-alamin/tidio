<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lyro AI Agent > Procedures/Guidance: step-by-step instructions for
    // handling a specific situation (e.g. "How to process a refund request").
    public function up(): void
    {
        Schema::create('ai_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions');
            $table->string('trigger_condition')->nullable(); // e.g. "when customer asks about refunds"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_procedures');
    }
};
