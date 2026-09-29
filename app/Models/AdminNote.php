<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminNote extends Model
{
    use HasFactory;

    protected $fillable = ['super_admin_id', 'notable_type', 'notable_id', 'note'];

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
