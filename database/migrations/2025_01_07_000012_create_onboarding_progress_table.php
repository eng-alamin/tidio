<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Dashboard's "Finalize your Tidio setup" checklist (e.g. "2/5").
    // One row per completed step; absence of a row = not completed yet.
    public function up(): void
    {
        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('step_key'); // "install_widget", "connect_mailbox", "invite_team", ...
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'step_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_progress');
    }
};
