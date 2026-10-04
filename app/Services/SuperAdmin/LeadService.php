<?php

namespace App\Services\SuperAdmin;

use App\Enums\LeadStatus;
use App\Models\ContactSalesLead;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/** Status changes on contact-sales requests made from the Super Admin Support Inbox. */
class LeadService
{
    /**
     * @throws RuntimeException when the request no longer exists
     * @throws Throwable
     */
    public function setStatus(SuperAdmin $actor, int $id, LeadStatus $status): ContactSalesLead
    {
        return DB::transaction(function () use ($actor, $id, $status) {
            $lead = ContactSalesLead::query()->lockForUpdate()->find($id);

            if (! $lead) {
                throw new RuntimeException('That request no longer exists.');
            }

            $from = $lead->status;

            if ($from === $status) {
                return $lead;
            }

            $lead->update(['status' => $status]);

            activity('lead')
                ->causedBy($actor)
                ->performedOn($lead)
                ->withProperties([
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'from' => $from->value,
                    'to' => $status->value,
                ])
                ->log('Sales request marked '.$status->value);

            return $lead;
        });
    }
}
