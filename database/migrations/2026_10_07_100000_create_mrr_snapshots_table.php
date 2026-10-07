<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One row per day: the platform's monthly recurring revenue at that moment.
    // Powers the "Revenue Trend" chart on the Super Admin dashboard. Amounts are in
    // the smallest currency unit (cents), like plans.price_monthly.
    public function up(): void
    {
        Schema::create('mrr_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->unique();
            $table->unsignedBigInteger('mrr_cents')->default(0);
            $table->unsignedInteger('active_subscriptions')->default(0);
            $table->unsignedInteger('tenants_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrr_snapshots');
    }
};
