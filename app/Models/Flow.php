<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\FlowStatus;
use App\Enums\FlowTriggerType;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['workspace_id', 'created_by', 'name', 'trigger_type', 'status', 'canvas_json'];

    protected $casts = [
        'canvas_json' => 'array',
        'trigger_type' => FlowTriggerType::class,
        'status' => FlowStatus::class,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function runs(): HasMany
    {
        return $this->hasMany(FlowRun::class);
    }
}
