<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversation_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->boolean('resolved_by_ai')->default(false);
            $table->decimal('confidence_score', 5, 2)->nullable(); // 0.00 - 100.00
            $table->timestamp('handed_off_at')->nullable();
            $table->timestamps();

            $table->unique('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_meta');
    }
};
