<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Batch 1: people & the highest-value app-panel content. Hard-deleting
    // a user must not orphan every message/macro/conversation they touched,
    // and support needs to be able to "undo" a deleted conversation/macro/flow.
    protected array $tables = [
        'users', 'conversations', 'macros', 'flows', 'tags',
        'custom_fields', 'websites', 'webhooks',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
    }
};
