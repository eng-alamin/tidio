<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Database\Seeders\BlogContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(array $attrs = []): BlogPost
    {
        return BlogPost::factory()->create($attrs + ['author_id' => null]);
    }

    public function test_index_lists_only_published_posts(): void
    {
        $this->makePost(['title' => 'Visible post']);
        $this->makePost(['title' => 'Draft post', 'status' => 'draft']);
        $this->makePost(['title' => 'Scheduled post', 'published_at' => now()->addWeek()]);

        $this->get('/blog')->assertOk()->assertSee('Visible post')->assertDontSee('Draft post')->assertDontSee('Scheduled post');
    }

    public function test_category_page_and_search_filter_the_list(): void
    {
        $a = BlogCategory::create(['name' => 'Playbooks', 'slug' => 'playbooks']);
        $b = BlogCategory::create(['name' => 'Product', 'slug' => 'product']);
        $this->makePost(['title' => 'Playbook article', 'blog_category_id' => $a->id, 'published_at' => now()->subDays(2)]);
        $this->makePost(['title' => 'Product article', 'blog_category_id' => $b->id, 'published_at' => now()->subDays(3)]);
        $this->makePost(['title' => 'Another product note', 'blog_category_id' => $b->id, 'published_at' => now()->subDays(4)]);

        $this->get('/blog/category/product')->assertOk()->assertSee('Product article')->assertDontSee('Playbook article');
        $this->get('/blog?q=Another')->assertOk()->assertSee('Another product note')->assertDontSee('Playbook article');
        $this->get('/blog?q=zzzz-nothing')->assertOk()->assertSee('No articles match your search.');
        $this->get('/blog/category/nope')->assertNotFound();
    }

    public function test_html_body_gets_a_table_of_contents(): void
    {
        $post = $this->makePost(['body' => '<h2 id="intro">Intro part</h2><p>Hello</p><h2 id="end">The end</h2>']);

        $this->get($post->url)->assertOk()->assertSee('On this page')->assertSee('href="#intro"', false)->assertSee('The end');
    }

    public function test_plain_text_body_is_escaped_into_paragraphs(): void
    {
        $post = $this->makePost(['body' => "First paragraph\n\nSecond <script>alert(1)</script>"]);

        $this->get($post->url)->assertOk()
            ->assertSee('<p>First paragraph</p>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_draft_and_scheduled_posts_are_404(): void
    {
        $draft = $this->makePost(['status' => 'draft']);
        $later = $this->makePost(['published_at' => now()->addDay()]);

        $this->get('/blog/'.$draft->slug)->assertNotFound();
        $this->get('/blog/'.$later->slug)->assertNotFound();
    }

    public function test_imported_articles_all_render_and_seeder_is_rerunnable(): void
    {
        $this->seed(BlogContentSeeder::class);
        $this->seed(BlogContentSeeder::class);

        $this->assertSame(8, BlogPost::count());
        $this->assertSame(5, BlogCategory::count());

        foreach (BlogPost::all() as $post) {
            $this->get($post->url)->assertOk()->assertSee($post->title)->assertSee('On this page');
        }

        $this->get('/blog')->assertOk()->assertSee('Featured');
    }
}
