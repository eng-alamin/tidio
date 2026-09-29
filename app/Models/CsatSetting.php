<?php

namespace App\Models;

use App\Enums\CsatTrigger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsatSetting extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'is_enabled', 'trigger', 'survey_question', 'follow_up_question'];

    protected $casts = ['is_enabled' => 'boolean', 'trigger' => CsatTrigger::class];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
