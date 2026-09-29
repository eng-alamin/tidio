<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('domain');
            $table->enum('spf_status', ['pending', 'ok', 'failed'])->default('pending');
            $table->enum('dkim_status', ['pending', 'ok', 'failed'])->default('pending');
            $table->enum('status', ['verifying', 'verified', 'failed'])->default('verifying');
            $table->timestamps();

            $table->unique(['workspace_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_domains');
    }
};
