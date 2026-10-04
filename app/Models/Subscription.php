<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id', 'plan_id', 'plan_name', 'status', 'billing_cycle', 'seats', 'renews_at',
    ];

    protected $casts = [
        'renews_at' => 'datetime',
        'status' => SubscriptionStatus::class,
        'billing_cycle' => BillingCycle::class,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Recurring revenue of this subscription per month, in the smallest currency
     * unit (cents). Yearly plans are spread over 12 months. Expects `plan` loaded.
     */
    public function monthlyAmountCents(): int
    {
        if (! $this->plan) {
            return 0;
        }

        return $this->billing_cycle === BillingCycle::Yearly
            ? (int) round($this->plan->price_yearly / 12)
            : (int) $this->plan->price_monthly;
    }
}
