<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (!Schema::hasColumn('workspaces', 'api_key')) {
                $table->string('api_key')->nullable()->unique()->after('id');
            }
        });

        // Backfill any existing workspaces with a generated key.
        DB::table('workspaces')->whereNull('api_key')->orderBy('id')->get(['id'])->each(function ($row) {
            DB::table('workspaces')->where('id', $row->id)->update([
                'api_key' => 'loop_live_'.Str::random(32),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (Schema::hasColumn('workspaces', 'api_key')) {
                $table->dropColumn('api_key');
            }
        });
    }
};
