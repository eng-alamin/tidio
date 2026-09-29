<?php

namespace Database\Factories;

use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

class WidgetSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'background_color' => fake()->randomElement(['#4C63D2', '#E74C3C', '#2ECC71']),
            'action_color' => '#4C63D2',
            'welcome_image_type' => 'agents_collage',
            'header' => 'Hi there 👋',
            'welcome_message' => 'Welcome to our website. Ask us anything!',
            'online_status_text' => 'We reply immediately',
            'offline_status_text' => 'We typically reply within a few minutes.',
            'position' => 'right',
            'default_language' => 'en',
            'advanced' => [],
        ];
    }
}
