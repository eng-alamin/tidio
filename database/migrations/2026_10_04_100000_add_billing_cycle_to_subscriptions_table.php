<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'billing_cycle')) {
                // monthly | yearly — decides whether plans.price_monthly or plans.price_yearly applies.
                $table->string('billing_cycle', 10)->default('monthly')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'billing_cycle')) {
                $table->dropColumn('billing_cycle');
            }
        });
    }
};
