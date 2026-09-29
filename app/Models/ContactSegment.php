<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactSegment extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'name', 'filters'];

    protected $casts = ['filters' => 'array'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
