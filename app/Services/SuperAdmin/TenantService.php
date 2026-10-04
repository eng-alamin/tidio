<?php

namespace App\Services\SuperAdmin;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * All write operations on tenants (workspaces) made from the Super Admin panel.
 * Every multi-table change runs in one transaction and is audit-logged.
 */
class TenantService
{
    private const TRIAL_DAYS = 14;

    /**
     * @param  array{name:string, owner_name:string, owner_email:string, owner_password:string, plan_id:int}  $data
     *
     * @throws Throwable
     */
    public function create(SuperAdmin $actor, array $data): Workspace
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $trialEndsAt = now()->addDays(self::TRIAL_DAYS);

        DB::beginTransaction();

        try {
            $owner = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => $data['owner_password'], // hashed by the model cast
            ]);
            $owner->forceFill(['email_verified_at' => now()])->save();

            $workspace = Workspace::create([
                'owner_id' => $owner->id,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'plan' => $plan->slug,
                'trial_ends_at' => $trialEndsAt,
            ]);

            $ownerRole = Role::create(['workspace_id' => $workspace->id, 'name' => 'Owner', 'is_system' => true]);
            Role::create(['workspace_id' => $workspace->id, 'name' => 'Operator', 'is_system' => true]);

            $workspace->users()->attach($owner->id, [
                'role_id' => $ownerRole->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            Subscription::create([
                'workspace_id' => $workspace->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'status' => SubscriptionStatus::Trialing,
                'seats' => 1,
                'renews_at' => $trialEndsAt,
            ]);

            activity('tenant')
                ->causedBy($actor)
                ->performedOn($workspace)
                ->withProperties(['plan' => $plan->slug, 'owner_email' => $owner->email])
                ->log('Tenant created');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $workspace;
    }

    /**
     * @throws Throwable
     */
    public function update(SuperAdmin $actor, Workspace $workspace, string $name, int $planId): Workspace
    {
        $plan = Plan::findOrFail($planId);
        $oldName = $workspace->name;
        $oldPlan = $workspace->plan;

        DB::beginTransaction();

        try {
            $workspace->update(['name' => $name, 'plan' => $plan->slug]);

            $subscription = $workspace->subscriptions()->latest('id')->first();

            if ($subscription) {
                $subscription->update(['plan_id' => $plan->id, 'plan_name' => $plan->name]);
            } else {
                Subscription::create([
                    'workspace_id' => $workspace->id,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'status' => SubscriptionStatus::Trialing,
                    'seats' => 1,
                    'renews_at' => $workspace->trial_ends_at ?? now()->addDays(self::TRIAL_DAYS),
                ]);
            }

            activity('tenant')
                ->causedBy($actor)
                ->performedOn($workspace)
                ->withProperties([
                    'old' => ['name' => $oldName, 'plan' => $oldPlan],
                    'new' => ['name' => $name, 'plan' => $plan->slug],
                ])
                ->log('Tenant updated');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $workspace;
    }

    /**
     * @throws Throwable
     */
    public function suspend(SuperAdmin $actor, Workspace $workspace, ?string $reason): void
    {
        DB::beginTransaction();

        try {
            $workspace->update([
                'is_suspended' => true,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);

            activity('tenant')
                ->causedBy($actor)
                ->performedOn($workspace)
                ->withProperties(['reason' => $reason])
                ->log('Tenant suspended');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function unsuspend(SuperAdmin $actor, Workspace $workspace): void
    {
        DB::beginTransaction();

        try {
            $workspace->update([
                'is_suspended' => false,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);

            activity('tenant')
                ->causedBy($actor)
                ->performedOn($workspace)
                ->log('Tenant reactivated');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Soft-deletes the workspace and cancels its live subscriptions so the
     * tenant stops counting towards MRR. Data stays recoverable.
     *
     * @throws Throwable
     */
    public function delete(SuperAdmin $actor, Workspace $workspace): void
    {
        DB::beginTransaction();

        try {
            $workspace->subscriptions()
                ->whereIn('status', [
                    SubscriptionStatus::Trialing->value,
                    SubscriptionStatus::Active->value,
                    SubscriptionStatus::PastDue->value,
                ])
                ->update(['status' => SubscriptionStatus::Cancelled->value, 'updated_at' => now()]);

            $workspace->delete();

            activity('tenant')
                ->causedBy($actor)
                ->performedOn($workspace)
                ->withProperties(['name' => $workspace->name])
                ->log('Tenant deleted');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $i = 2;

        while (Workspace::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
