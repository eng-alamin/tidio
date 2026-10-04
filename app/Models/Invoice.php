<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id', 'subscription_id', 'coupon_id', 'invoice_number',
        'amount', 'currency', 'status', 'issued_at', 'due_at', 'paid_at', 'pdf_url',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'status' => InvoiceStatus::class,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function coupon(): BelongsTo
    {
        // withTrashed: a deleted coupon must still show on the invoices it was used on.
        return $this->belongsTo(Coupon::class)->withTrashed();
    }
}
