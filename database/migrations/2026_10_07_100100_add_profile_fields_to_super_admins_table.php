<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Optional contact details shown on the Super Admin "My Profile" page.
    public function up(): void
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('timezone', 64)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->dropColumn(['phone', 'timezone']);
        });
    }
};
