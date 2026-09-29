<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Settings > Customer satisfaction: the survey CONFIG, as opposed to
    // `csat_ratings` which stores the RESULTS.
    public function up(): void
    {
        Schema::create('csat_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->enum('trigger', ['after_resolution', 'manual'])->default('after_resolution');
            $table->string('survey_question')->default('How would you rate this conversation?');
            $table->string('follow_up_question')->nullable();
            $table->timestamps();

            $table->unique('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csat_settings');
    }
};
