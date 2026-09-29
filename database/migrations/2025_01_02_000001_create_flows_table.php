<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->enum('trigger_type', ['page_visit', 'time_on_page', 'exit_intent', 'cart_abandonment', 'manual']);
            $table->enum('status', ['draft', 'active', 'paused'])->default('draft');
            $table->json('canvas_json')->nullable(); // drag-and-drop builder nodes/edges
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flows');
    }
};
