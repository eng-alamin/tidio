<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Internal permission tiers for platform staff — not every super admin
    // should be able to issue refunds or delete a workspace.
    public function up(): void
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'support_staff', 'billing_admin'])
                ->default('support_staff')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
