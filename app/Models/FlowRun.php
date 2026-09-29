<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\FlowRunOutcome;
use App\Enums\FlowRunStatus;

class FlowRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id', 'visitor_id', 'contact_id', 'status', 'outcome', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => FlowRunStatus::class,
        'outcome' => FlowRunOutcome::class,
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
