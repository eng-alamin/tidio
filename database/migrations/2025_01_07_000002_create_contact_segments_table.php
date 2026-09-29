<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Customers > Segments (e.g. "Subscribers", "All contacts"). Distinct
    // from saved_views, which filters the Inbox rather than the Customers list.
    public function up(): void
    {
        Schema::create('contact_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->json('filters'); // e.g. {"match":"all","rules":[{"field":"tag","op":"has","value":"VIP"}]}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_segments');
    }
};
