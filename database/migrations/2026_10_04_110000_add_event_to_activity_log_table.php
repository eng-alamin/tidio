<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // spatie/laravel-activitylog v4+ writes an `event` column that the original
    // create_activity_log_table migration did not include. Without it every activity()->log() fails.
    public function up(): void
    {
        if (! Schema::hasColumn('activity_log', 'event')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->string('event')->nullable()->after('subject_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_log', 'event')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropColumn('event');
            });
        }
    }
};
