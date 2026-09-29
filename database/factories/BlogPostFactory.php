<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogPostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'blog_category_id' => BlogCategory::factory(),
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 9999),
            'excerpt' => fake()->sentence(20),
            'body' => implode("\n\n", fake()->paragraphs(5)),
            'cover_image' => null,
            'status' => 'published',
            'seo_meta' => ['title' => $title, 'description' => fake()->sentence(15)],
            'published_at' => now()->subDays(fake()->numberBetween(0, 200)),
        ];
    }
}
