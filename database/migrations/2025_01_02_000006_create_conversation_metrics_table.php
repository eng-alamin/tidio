<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->timestamp('first_response_at')->nullable();
            $table->unsignedInteger('first_response_seconds')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('resolution_seconds')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->timestamps();

            $table->unique('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_metrics');
    }
};
