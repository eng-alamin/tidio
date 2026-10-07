<?php

namespace Database\Seeders;

use App\Enums\SuperAdminRole;
use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates one platform staff login per role so the Super Admin panel can be tested.
 * Safe to run more than once: existing accounts are updated, not duplicated.
 *
 *   php artisan db:seed --class=SuperAdminSeeder
 *
 * Development only. The password is public, so it refuses to run in production.
 */
class SuperAdminSeeder extends Seeder
{
    private const PASSWORD = 'Password@12345';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('SuperAdminSeeder uses a well-known password and must not run in production.');
        }

        $accounts = [
            ['name' => 'Super Admin', 'email' => 'super@loop.com', 'role' => SuperAdminRole::SuperAdmin],
            ['name' => 'Billing Admin', 'email' => 'billing@loop.com', 'role' => SuperAdminRole::BillingAdmin],
            ['name' => 'Support Staff', 'email' => 'support@loop.com', 'role' => SuperAdminRole::SupportStaff],
        ];

        foreach ($accounts as $account) {
            SuperAdmin::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => self::PASSWORD, // hashed by the model cast
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
