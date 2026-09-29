<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The system-wide template library (Flows > Templates, "40+ ecommerce
    // templates"). workspace_id nullable: null = built-in Tidio template,
    // set = a workspace saved their own flow as a reusable template.
    public function up(): void
    {
        Schema::create('flow_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable(); // "Generate leads", "Increase sales", "Solve problems"
            $table->text('description')->nullable();
            $table->json('canvas_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_templates');
    }
};
