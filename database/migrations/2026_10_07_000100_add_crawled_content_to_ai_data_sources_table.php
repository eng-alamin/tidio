<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_data_sources', function (Blueprint $table) {
            $table->string('title')->nullable()->after('source');
            $table->longText('content')->nullable()->after('title');   // text Lyro reads (crawled page(s) / PDF)
            $table->string('file_path')->nullable()->after('content'); // uploaded PDF on the private disk
            $table->string('error', 500)->nullable()->after('status'); // why a sync failed (shown to the owner)
            $table->char('content_hash', 64)->nullable()->after('error');
            $table->unsignedSmallInteger('pages_count')->default(0)->after('content_hash');
        });
    }

    public function down(): void
    {
        Schema::table('ai_data_sources', function (Blueprint $table) {
            $table->dropColumn(['title', 'content', 'file_path', 'error', 'content_hash', 'pages_count']);
        });
    }
};
