<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Basic, Plus, Premium
            $table->string('slug')->unique();
            $table->unsignedInteger('price_monthly')->default(0); // smallest currency unit
            $table->unsignedInteger('price_yearly')->default(0);
            $table->json('features')->nullable(); // e.g. ["ai_agent","flows","help_desk"]
            $table->json('limits')->nullable();   // e.g. {"ai_conversations":50,"operators":3}
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
