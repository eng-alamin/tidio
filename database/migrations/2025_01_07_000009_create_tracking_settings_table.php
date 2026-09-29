<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->enum('provider', ['google_analytics', 'facebook_pixel', 'gtm', 'custom']);
            $table->string('snippet_id')->nullable(); // GA4 measurement ID, Pixel ID, GTM container ID...
            $table->text('custom_script')->nullable(); // for provider = custom
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_settings');
    }
};
