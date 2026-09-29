<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['website_id', 'locale', 'strings'];

    protected $casts = ['strings' => 'array'];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
