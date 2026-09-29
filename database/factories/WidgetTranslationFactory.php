<?php

namespace Database\Factories;

use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

class WidgetTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'locale' => 'bn',
            'strings' => [
                'welcome_header' => 'হাই সেখানে 👋',
                'chat_placeholder' => 'একটি বার্তা লিখুন...',
            ],
        ];
    }
}
