<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingEvent extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'trigger',
        'count_30d',
    ];

    protected $casts = [
        'count_30d' => 'integer',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
