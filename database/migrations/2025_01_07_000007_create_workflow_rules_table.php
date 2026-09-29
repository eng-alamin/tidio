<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Settings > General > Workflows: internal automation rules (auto-assign,
    // auto-tag, auto-close). Distinct from customer-facing `flows`, which run
    // in the widget to capture leads/sales.
    public function up(): void
    {
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_event'); // "conversation.created", "conversation.unassigned_15min"
            $table->json('conditions')->nullable(); // e.g. [{"field":"channel_type","op":"=","value":"whatsapp"}]
            $table->json('actions'); // e.g. [{"type":"assign_department","value":"support"}]
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0); // rules run top to bottom
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rules');
    }
};
