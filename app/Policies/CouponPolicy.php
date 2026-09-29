<?php

namespace App\Policies;

use App\Enums\SuperAdminRole;
use App\Models\Coupon;
use App\Models\SuperAdmin;

class CouponPolicy
{
    // Coupons are platform-level, not workspace-level — gated against the
    // SuperAdmin guard, not App\Models\User. Only billing_admin (or the top
    // super_admin tier) can create or edit discount codes; support_staff
    // can still view them (e.g. to explain a discount to a customer).
    public function view(SuperAdmin $admin, Coupon $coupon): bool
    {
        return true; // any authenticated platform staff member can view
    }

    public function create(SuperAdmin $admin): bool
    {
        return in_array($admin->role, [SuperAdminRole::BillingAdmin, SuperAdminRole::SuperAdmin], true);
    }

    public function update(SuperAdmin $admin, Coupon $coupon): bool
    {
        return $this->create($admin);
    }

    public function delete(SuperAdmin $admin, Coupon $coupon): bool
    {
        return $admin->role === SuperAdminRole::SuperAdmin; // deletion is the strictest tier
    }
}
