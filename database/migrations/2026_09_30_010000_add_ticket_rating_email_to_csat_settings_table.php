<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('csat_settings', function (Blueprint $table) {
            $table->boolean('ticket_rating_email')->default(true)->after('follow_up_question');
        });
    }

    public function down(): void
    {
        Schema::table('csat_settings', function (Blueprint $table) {
            $table->dropColumn('ticket_rating_email');
        });
    }
};
