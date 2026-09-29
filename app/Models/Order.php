<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sale assisted through a conversation. Used only by the Sales analytics
 * tab — this project has no storefront/checkout of its own, so this table
 * is meant to be written to by whatever e-commerce integration a tenant
 * connects (Shopify webhook, etc.). Amount is stored in cents.
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'contact_id', 'conversation_id', 'product_name', 'amount_cents'];

    protected $casts = ['amount_cents' => 'integer'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }
}
