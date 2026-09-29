<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->string('background_color')->default('#4C63D2');
            $table->string('action_color')->default('#4C63D2');
            $table->enum('welcome_image_type', ['agents_collage', 'logo'])->default('agents_collage');
            $table->string('header')->nullable();
            $table->text('welcome_message')->nullable();
            $table->string('online_status_text')->nullable();
            $table->string('offline_status_text')->nullable();
            $table->enum('position', ['left', 'right'])->default('right');
            $table->string('default_language', 5)->default('en');
            $table->json('advanced')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_settings');
    }
};
