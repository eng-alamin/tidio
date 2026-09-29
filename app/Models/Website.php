<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Website extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['workspace_id', 'domain', 'widget_key', 'installed_at'];

    protected $casts = ['installed_at' => 'datetime'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function widgetSetting(): HasOne
    {
        return $this->hasOne(WidgetSetting::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(WidgetTranslation::class);
    }
}
