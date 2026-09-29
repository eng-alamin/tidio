<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lyro AI Agent > MCP Actions / Smart Actions: lets Lyro call out to an
    // external system (order lookup, booking API) mid-conversation.
    // Distinct from `webhooks`, which is one-way outbound notification only.
    public function up(): void
    {
        Schema::create('ai_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name'); // "Look up order status"
            $table->enum('type', ['mcp', 'webhook', 'internal_lookup']);
            $table->string('endpoint_url')->nullable();
            $table->json('config')->nullable(); // auth, parameters schema, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_actions');
    }
};
