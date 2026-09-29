<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Site-wide or segmented banners, e.g. "Scheduled maintenance Sunday 2AM UTC"
    // — replaces ad-hoc use of system_settings for this purpose.
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->enum('audience', ['all', 'workspace_owners', 'trial_users'])->default('all');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
