<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparison_pages', function (Blueprint $table) {
            $table->id();
            $table->string('competitor_name'); // Intercom, Zendesk, Gorgias...
            $table->string('slug')->unique();  // "vs/intercom"
            $table->json('comparison_table')->nullable(); // feature-by-feature rows
            $table->longText('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_pages');
    }
};
