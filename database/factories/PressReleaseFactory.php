<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PressReleaseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(8);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 9999),
            'body' => implode("\n\n", fake()->paragraphs(3)),
            'published_at' => now()->subDays(fake()->numberBetween(0, 400)),
        ];
    }
}
