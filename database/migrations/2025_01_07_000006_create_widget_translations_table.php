<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->string('locale', 5); // en, bn, es...
            $table->json('strings'); // {"welcome_header":"...","chat_placeholder":"..."}
            $table->timestamps();

            $table->unique(['website_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_translations');
    }
};
