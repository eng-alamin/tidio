<?php

namespace App\Models;

use App\Enums\TrackingProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingSetting extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'provider', 'snippet_id', 'custom_script', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'provider' => TrackingProvider::class];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
