<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Batch 2: money records. These should NEVER be hard-deleted for
    // accounting/compliance reasons — soft delete is the enforced floor,
    // and ideally the app layer never calls forceDelete() on these at all.
    protected array $tables = ['invoices', 'subscriptions', 'coupons'];

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
