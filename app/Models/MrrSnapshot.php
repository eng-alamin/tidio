<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MrrSnapshot extends Model
{
    protected $fillable = ['snapshot_date', 'mrr_cents', 'active_subscriptions', 'tenants_count'];

    protected $casts = [
        // Stored as a plain Y-m-d string on every database driver, so the unique day key always matches.
        'snapshot_date' => 'date:Y-m-d',
        'mrr_cents' => 'integer',
        'active_subscriptions' => 'integer',
        'tenants_count' => 'integer',
    ];
}
