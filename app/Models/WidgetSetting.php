<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id', 'background_color', 'action_color', 'welcome_image_type',
        'header', 'welcome_message', 'online_status_text', 'offline_status_text',
        'position', 'default_language', 'advanced',
    ];

    protected $casts = ['advanced' => 'array'];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
