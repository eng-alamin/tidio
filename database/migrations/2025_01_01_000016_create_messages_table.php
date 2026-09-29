<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->enum('sender_type', ['visitor', 'operator', 'bot', 'system']);
            $table->unsignedBigInteger('sender_id')->nullable(); // polymorphic-ish: user_id or visitor_id depending on sender_type
            $table->longText('body')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_private_note')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
