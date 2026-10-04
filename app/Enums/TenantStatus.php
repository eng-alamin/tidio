<?php

namespace App\Enums;

use App\Models\Workspace;

/**
 * Display-level status of a tenant (workspace) in the Super Admin panel.
 * Derived: a suspended workspace always wins over its subscription status.
 */
enum TenantStatus: string
{
    case Active = 'active';
    case Trial = 'trial';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';

    /**
     * Expects the `activeSubscription` relation to be eager loaded to avoid N+1.
     */
    public static function fromWorkspace(Workspace $workspace): self
    {
        if ($workspace->is_suspended) {
            return self::Suspended;
        }

        return match ($workspace->activeSubscription?->status) {
            SubscriptionStatus::Active => self::Active,
            SubscriptionStatus::PastDue => self::PastDue,
            SubscriptionStatus::Cancelled => self::Cancelled,
            default => self::Trial, // trialing, or no subscription yet
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Trial => 'Trial',
            self::PastDue => 'Past due',
            self::Cancelled => 'Cancelled',
            self::Suspended => 'Suspended',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Active => 'status-active',
            self::Trial => 'status-trial',
            self::PastDue => 'status-past',
            self::Cancelled, self::Suspended => 'status-suspended',
        };
    }
}
