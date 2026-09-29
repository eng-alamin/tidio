<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'subscription_id' => Subscription::factory(),
            'coupon_id' => null,
            'invoice_number' => 'INV-'.fake()->unique()->numberBetween(10000, 99999),
            'amount' => fake()->randomElement([2900, 5900, 9900]),
            'currency' => 'USD',
            'status' => 'paid',
            'issued_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'due_at' => now()->subDays(fake()->numberBetween(1, 60))->addDays(14),
            'paid_at' => now()->subDays(fake()->numberBetween(0, 59)),
            'pdf_url' => null,
        ];
    }
}
