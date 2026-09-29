<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Internal-only notes a super admin leaves on a workspace or a user,
    // e.g. "refunded manually on 2026-09-01, see ticket #4821".
    public function up(): void
    {
        Schema::create('admin_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->constrained('super_admins')->cascadeOnDelete();
            $table->morphs('notable'); // notable_type, notable_id -> Workspace or User
            $table->text('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notes');
    }
};
