<?php

namespace App\Services\SuperAdmin;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SuperAdmin;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Write operations on subscriptions made from the Super Admin panel.
 * The denormalised `workspaces.plan` slug is kept in sync in the same transaction.
 */
class SubscriptionService
{
    /**
     * @param  array{plan_id:int, billing_cycle:string, seats:int, status:string, renews_at:?CarbonInterface}  $data
     *
     * @throws Throwable
     */
    public function update(SuperAdmin $actor, Subscription $subscription, array $data): Subscription
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $workspace = $subscription->workspace()->firstOrFail();

        $old = [
            'plan' => $subscription->plan_name,
            'billing_cycle' => $subscription->billing_cycle?->value,
            'seats' => $subscription->seats,
            'status' => $subscription->status?->value,
            'renews_at' => $subscription->renews_at?->toDateString(),
        ];

        DB::beginTransaction();

        try {
            $subscription->update([
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'billing_cycle' => $data['billing_cycle'],
                'seats' => $data['seats'],
                'status' => $data['status'],
                'renews_at' => $data['renews_at'],
            ]);

            $workspaceChanges = ['plan' => $plan->slug];

            // A trial's end date lives on the workspace too — keep both in step.
            if ($data['status'] === SubscriptionStatus::Trialing->value && $data['renews_at']) {
                $workspaceChanges['trial_ends_at'] = $data['renews_at'];
            }

            $workspace->update($workspaceChanges);

            activity('subscription')
                ->causedBy($actor)
                ->performedOn($subscription)
                ->withProperties([
                    'workspace' => $workspace->name,
                    'old' => $old,
                    'new' => [
                        'plan' => $plan->name,
                        'billing_cycle' => $data['billing_cycle'],
                        'seats' => $data['seats'],
                        'status' => $data['status'],
                        'renews_at' => $data['renews_at']?->toDateString(),
                    ],
                ])
                ->log('Subscription updated');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $subscription;
    }

    /**
     * @throws Throwable
     */
    public function cancel(SuperAdmin $actor, Subscription $subscription): void
    {
        $workspace = $subscription->workspace()->firstOrFail();

        DB::beginTransaction();

        try {
            $subscription->update(['status' => SubscriptionStatus::Cancelled]);

            activity('subscription')
                ->causedBy($actor)
                ->performedOn($subscription)
                ->withProperties(['workspace' => $workspace->name, 'plan' => $subscription->plan_name])
                ->log('Subscription cancelled');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
