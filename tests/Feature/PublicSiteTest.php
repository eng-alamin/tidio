<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\ContactSalesLead;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_static_page_renders(): void
    {
        foreach (config('site_pages.static') as $slug) {
            $this->get('/'.$slug)->assertOk();
        }
    }

    public function test_static_pages_have_no_leftover_html_links(): void
    {
        $body = $this->get('/features')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/href="[a-z0-9\-]+\.html"/', $body);
    }

    public function test_unknown_page_is_a_404(): void
    {
        $this->get('/definitely-not-a-page')->assertNotFound();
    }

    public function test_contact_sales_form_renders_and_saves_a_lead(): void
    {
        $this->get('/contact-sales')->assertOk()->assertSee('Request a call');

        $this->post('/contact-sales', [
            'name' => 'Jane Doe',
            'email' => 'jane@acme.test',
            'company' => 'Acme',
            'team_size' => '11–50',
            'message' => 'We need SSO.',
        ])->assertRedirect(route('site.contact-sales'))->assertSessionHas('lead_status');

        $lead = ContactSalesLead::firstOrFail();
        $this->assertSame('Jane Doe', $lead->name);
        $this->assertSame('/contact-sales', $lead->source_page);
        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertStringContainsString('Team size: 11–50', $lead->message);
        $this->assertStringContainsString('We need SSO.', $lead->message);
    }

    public function test_contact_sales_validates_input(): void
    {
        $this->post('/contact-sales', ['name' => '', 'email' => 'nope', 'team_size' => '9999'])
            ->assertSessionHasErrors(['name', 'email', 'team_size']);

        $this->assertDatabaseCount('contact_sales_leads', 0);
    }

    public function test_contact_form_saves_topic_and_message(): void
    {
        $this->post('/contact', [
            'name' => 'Sam', 'email' => 'sam@acme.test',
            'topic' => 'Technical support', 'message' => 'Widget does not load.',
        ])->assertRedirect(route('site.contact'));

        $lead = ContactSalesLead::firstOrFail();
        $this->assertSame('/contact', $lead->source_page);
        $this->assertStringContainsString('Topic: Technical support', $lead->message);
    }

    public function test_filled_honeypot_is_silently_dropped(): void
    {
        $this->post('/contact-sales', ['name' => 'Bot', 'email' => 'bot@spam.test', 'website' => 'http://spam.test'])
            ->assertRedirect(route('site.contact-sales'))->assertSessionHas('lead_status');
        $this->post('/newsletter', ['email' => 'bot@spam.test', 'website' => 'x'])
            ->assertSessionHas('newsletter_status');

        $this->assertDatabaseCount('contact_sales_leads', 0);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_newsletter_subscribe_dedupes_and_resubscribes(): void
    {
        $this->post('/newsletter', ['email' => ' Jane@Acme.test '])->assertSessionHas('newsletter_status');
        $this->post('/newsletter', ['email' => 'jane@acme.test'])->assertSessionHas('newsletter_status');

        $this->assertSame(1, NewsletterSubscriber::count());
        $subscriber = NewsletterSubscriber::firstOrFail();
        $this->assertSame('jane@acme.test', $subscriber->email);
        $this->assertNotNull($subscriber->subscribed_at);

        $this->get(URL::signedRoute('site.newsletter.unsubscribe', $subscriber))->assertRedirect(route('home'));
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);

        $this->post('/newsletter', ['email' => 'jane@acme.test']);
        $this->assertNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_newsletter_rejects_bad_email_and_unsigned_unsubscribe(): void
    {
        $this->post('/newsletter', ['email' => 'not-an-email'])->assertSessionHasErrors('email', null, 'newsletter');

        $subscriber = NewsletterSubscriber::factory()->create();
        $this->get(route('site.newsletter.unsubscribe', $subscriber))->assertForbidden();
    }
}
