<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_email_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('address');
            $table->timestamp('blocked_at')->useCurrent();
            $table->timestamps();

            $table->unique(['workspace_id', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_email_addresses');
    }
};
