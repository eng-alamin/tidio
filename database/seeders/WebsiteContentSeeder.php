<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\CaseStudy;
use App\Models\ComparisonPage;
use App\Models\ContactSalesLead;
use App\Models\Faq;
use App\Models\Integration;
use App\Models\JobPosting;
use App\Models\NewsletterSubscriber;
use App\Models\PressRelease;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    // Seeds Part B — the public marketing website (tidio.com itself), which
    // has no workspace_id anywhere: plain CMS content, independent of the
    // app panel's tenant data seeded in DatabaseSeeder.
    public function run(): void
    {
        $author = User::first() ?? User::factory()->create();

        $categories = BlogCategory::factory(5)->create();

        BlogPost::factory(15)->create()->each(function (BlogPost $post) use ($categories, $author) {
            $post->update([
                'blog_category_id' => $categories->random()->id,
                'author_id' => $author->id,
            ]);
        });

        CaseStudy::factory(6)->create();

        collect(['Intercom', 'Zendesk', 'Gorgias', 'LiveChat', 'Manychat', 'Tawk.to'])
        ->each(fn ($competitor) => ComparisonPage::factory()->create([
            'competitor_name' => $competitor,
            'slug' => 'vs/'.\Illuminate\Support\Str::slug($competitor),
        ]));

        Faq::factory(12)->create();

        Integration::factory(10)->create();

        JobPosting::factory(5)->create();

        PressRelease::factory(4)->create();

        ContactSalesLead::factory(8)->create();

        NewsletterSubscriber::factory(20)->create();

        Testimonial::factory(10)->create();
    }
}
