<?php

namespace App\Enums;

enum SuperAdminRole: string
{
    case SuperAdmin = 'super_admin';   // full access, including destructive actions
    case SupportStaff = 'support_staff'; // read + impersonate + notes, no billing/deletion
    case BillingAdmin = 'billing_admin'; // invoices, coupons, subscriptions
}
