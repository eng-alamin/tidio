<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Imports the 8 articles + 5 categories that used to be static HTML (data/blog-posts.json).
 * Idempotent: safe to run again, matches on slug.
 */
class BlogContentSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/blog-posts.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($data['categories'] as $category) {
            BlogCategory::firstOrCreate(['slug' => $category['slug']], ['name' => $category['name']]);
        }

        $categoryIds = BlogCategory::pluck('id', 'slug');

        foreach ($data['posts'] as $row) {
            $post = BlogPost::withTrashed()->firstOrNew(['slug' => $row['slug']]);
            $post->fill([
                'blog_category_id' => $categoryIds[$row['category']] ?? null,
                'title' => $row['title'],
                'excerpt' => $row['excerpt'],
                'body' => $row['body'],
                'status' => PublishStatus::Published,
                'published_at' => Carbon::parse($row['published_at']),
                'seo_meta' => [
                    'title' => $row['title'],
                    'description' => $row['meta_description'],
                    'author' => $row['author'],
                    'icon' => $row['icon'],
                ],
            ]);
            $post->deleted_at = null;
            $post->save();
        }
    }
}
