<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'slug', 'timezone', 'plan', 'trial_ends_at',
        'is_suspended', 'suspended_at', 'suspension_reason', 'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot(['role_id', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function operatingHours(): HasMany
    {
        return $this->hasMany(OperatingHour::class);
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function macros(): HasMany
    {
        return $this->hasMany(Macro::class);
    }

    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class);
    }

    public function slas(): HasMany
    {
        return $this->hasMany(Sla::class);
    }

    public function savedViews(): HasMany
    {
        return $this->hasMany(SavedView::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function aiAgentSetting(): HasOne
    {
        return $this->hasOne(AiAgentSetting::class);
    }

    public function aiDataSources(): HasMany
    {
        return $this->hasMany(AiDataSource::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function usageCounters(): HasMany
    {
        return $this->hasMany(UsageCounter::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function adminNotes(): MorphMany
    {
        return $this->morphMany(AdminNote::class, 'notable');
    }

    public function contactSegments(): HasMany
    {
        return $this->hasMany(ContactSegment::class);
    }

    public function flowTemplates(): HasMany
    {
        return $this->hasMany(FlowTemplate::class);
    }

    public function aiProcedures(): HasMany
    {
        return $this->hasMany(AiProcedure::class);
    }

    public function aiActions(): HasMany
    {
        return $this->hasMany(AiAction::class);
    }

    public function workflowRules(): HasMany
    {
        return $this->hasMany(WorkflowRule::class);
    }

    public function csatSetting(): HasOne
    {
        return $this->hasOne(CsatSetting::class);
    }

    public function trackingSettings(): HasMany
    {
        return $this->hasMany(TrackingSetting::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function onboardingProgress(): HasMany
    {
        return $this->hasMany(OnboardingProgress::class);
    }
}
