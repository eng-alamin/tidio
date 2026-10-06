<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\MessageSenderType;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Visitor;
use App\Models\WidgetSetting;
use App\Models\Website;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetTest extends TestCase
{
    use RefreshDatabase;

    private Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        config(['widget.allow_any_origin' => false]);

        $this->website = Website::factory()->create(['domain' => 'example.com', 'installed_at' => null]);
        WidgetSetting::factory()->create(['website_id' => $this->website->id, 'background_color' => '#14213D', 'position' => 'left', 'header' => 'Support Team']);
    }

    private function api(string $path = ''): string
    {
        return '/widget-api/'.$this->website->widget_key.$path;
    }

    private function openWidgetSession(?Website $website = null, array $headers = []): string
    {
        $key = ($website ?? $this->website)->widget_key;

        return $this->withHeaders($headers)
            ->postJson("/widget-api/{$key}/init", ['page_url' => 'https://shop.example.com/pricing?utm=1'])
            ->assertOk()
            ->json('session_id');
    }

    private function asVisitor(string $sid): static
    {
        return $this->withHeaders(['X-Widget-Session' => $sid]);
    }

    // ------------------------------------------------------------- loader & frame

    public function test_loader_is_public_javascript_with_the_widget_config(): void
    {
        $response = $this->get('/widget/'.$this->website->widget_key.'.js');

        $response->assertOk();
        $this->assertStringContainsString('application/javascript', $response->headers->get('Content-Type'));
        $this->assertStringContainsString($this->website->widget_key, $response->getContent());
        $this->assertStringContainsString('#14213D', $response->getContent());
        $this->assertStringContainsString('left', $response->getContent());
    }

    public function test_loader_marks_installed_only_for_a_page_on_the_registered_domain(): void
    {
        $this->withHeaders(['Referer' => 'https://evil.test/page'])->get('/widget/'.$this->website->widget_key.'.js')->assertOk();
        $this->assertNull($this->website->fresh()->installed_at);

        $this->withHeaders(['Referer' => 'https://www.example.com/page'])->get('/widget/'.$this->website->widget_key.'.js')->assertOk();
        $this->assertNotNull($this->website->fresh()->installed_at);
    }

    public function test_unknown_malformed_or_suspended_keys_are_refused(): void
    {
        $this->get('/widget/'.fake()->uuid().'.js')->assertNotFound();
        $this->get('/widget/not-a-uuid.js')->assertNotFound();

        $this->website->workspace->update(['is_suspended' => true]);
        $this->get('/widget/'.$this->website->widget_key.'.js')->assertForbidden();
    }

    public function test_frame_sets_csp_that_only_lets_the_registered_domain_embed_it(): void
    {
        $response = $this->get('/widget-frame/'.$this->website->widget_key);

        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('frame-ancestors', $csp);
        $this->assertStringContainsString('https://example.com', $csp);
        $this->assertStringContainsString('https://*.example.com', $csp);
        $this->assertStringNotContainsString('frame-ancestors *', $csp);
        $this->assertMatchesRegularExpression("/script-src 'nonce-[A-Za-z0-9]+'/", $csp);
        $this->assertStringContainsString('Support Team', $response->getContent()); // header from Appearance settings reaches the frame
    }

    // ------------------------------------------------------------- sessions

    public function test_init_creates_a_visitor_then_resumes_it(): void
    {
        $sid = $this->openWidgetSession();

        $this->assertDatabaseCount('visitors', 1);
        $visitor = Visitor::first();
        $this->assertSame($this->website->workspace_id, $visitor->workspace_id);
        $this->assertSame('https://shop.example.com/pricing', $visitor->current_page); // query string stripped
        $this->assertStringNotContainsString('utm', $visitor->current_page);
        $this->assertTrue($visitor->is_online);

        $this->postJson($this->api('/init'), ['session_id' => $sid])
            ->assertOk()
            ->assertJsonPath('session_id', $sid)
            ->assertJsonPath('messages', []);

        $this->assertDatabaseCount('visitors', 1);
    }

    public function test_init_with_unknown_session_id_issues_a_new_server_side_id(): void
    {
        $forged = fake()->uuid();

        $sid = $this->postJson($this->api('/init'), ['session_id' => $forged])->assertOk()->json('session_id');

        $this->assertNotSame($forged, $sid);
    }

    public function test_init_on_registered_domain_marks_the_site_installed_but_app_panel_pages_do_not(): void
    {
        $this->postJson($this->api('/init'), ['page_url' => 'https://example.com/app/widget-preview'])->assertOk();
        $this->assertNull($this->website->fresh()->installed_at);

        $this->postJson($this->api('/init'), ['page_url' => 'https://example.com/contact'])->assertOk();
        $this->assertNotNull($this->website->fresh()->installed_at);
    }

    // ------------------------------------------------------------- origin & tenancy

    public function test_api_rejects_browser_calls_from_a_foreign_origin(): void
    {
        $this->withHeaders(['Origin' => 'https://evil.test'])->postJson($this->api('/init'))->assertForbidden();
        $this->withHeaders(['Origin' => 'null'])->postJson($this->api('/init'))->assertForbidden();
    }

    public function test_api_accepts_the_registered_domain_subdomains_and_the_app_itself(): void
    {
        $this->withHeaders(['Origin' => 'https://example.com'])->postJson($this->api('/init'))->assertOk();
        $this->withHeaders(['Origin' => 'https://shop.example.com'])->postJson($this->api('/init'))->assertOk();
        $this->withHeaders(['Origin' => 'http://localhost'])->postJson($this->api('/init'))->assertOk(); // the frame itself (same host as the test app)
    }

    public function test_a_session_cannot_be_replayed_against_another_workspace(): void
    {
        $sid = $this->openWidgetSession();
        $other = Website::factory()->create(['domain' => 'other.test']);

        $this->asVisitor($sid)->getJson("/widget-api/{$other->widget_key}/messages")->assertUnauthorized();
        $this->asVisitor($sid)->postJson("/widget-api/{$other->widget_key}/messages", ['body' => 'hi'])->assertUnauthorized();
    }

    public function test_messages_endpoints_require_a_session(): void
    {
        $this->getJson($this->api('/messages'))->assertUnauthorized();
        $this->postJson($this->api('/messages'), ['body' => 'hi'])->assertUnauthorized();
        $this->withHeaders(['X-Widget-Session' => 'garbage'])->getJson($this->api('/messages'))->assertUnauthorized();
    }

    // ------------------------------------------------------------- messaging

    public function test_first_message_creates_one_widget_conversation_and_later_messages_reuse_it(): void
    {
        $sid = $this->openWidgetSession();

        $this->assertDatabaseCount('conversations', 0); // opening the widget alone creates nothing in the Inbox

        $first = $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => "  Hello\r\n\r\n\r\n\r\nthere  "])
            ->assertCreated()
            ->assertJsonPath('message.from', 'visitor')
            ->assertJsonPath('message.body', "Hello\n\nthere")
            ->assertJsonPath('conversation.status', 'open');

        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'Second'])->assertCreated();

        $this->assertDatabaseCount('conversations', 1);
        $conversation = Conversation::first();
        $this->assertSame('widget', $conversation->channel_type->value);
        $this->assertSame('chat', $conversation->type->value);
        $this->assertSame($this->website->workspace_id, $conversation->workspace_id);
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertSame(MessageSenderType::Visitor, $conversation->messages()->first()->sender_type);
        $this->assertNotNull($conversation->last_message_at);
        $this->assertNotNull($first->json('message.id'));
    }

    public function test_message_validation(): void
    {
        $sid = $this->openWidgetSession();

        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => '   '])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => str_repeat('a', 2001)])->assertUnprocessable();
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => ['x']])->assertUnprocessable();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_html_is_stored_verbatim_as_text_never_executed_server_side(): void
    {
        $sid = $this->openWidgetSession();

        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => '<script>alert(1)</script>'])->assertCreated();

        $this->assertSame('<script>alert(1)</script>', Message::first()->body); // escaped on output (Blade {{ }} / textContent)
    }

    public function test_poll_returns_operator_replies_but_never_private_notes(): void
    {
        $sid = $this->openWidgetSession();
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'Need help'])->assertCreated();

        $conversation = Conversation::first();
        $operator = User::factory()->create(['name' => 'Amanda Prohaska']);

        $reply = $conversation->messages()->create(['sender_type' => MessageSenderType::Operator, 'sender_id' => $operator->id, 'body' => 'Sure!']);
        $conversation->messages()->create(['sender_type' => MessageSenderType::Operator, 'sender_id' => $operator->id, 'body' => 'INTERNAL: refund risk', 'is_private_note' => true]);

        $response = $this->asVisitor($sid)->getJson($this->api('/messages?after=0'))->assertOk();

        $bodies = collect($response->json('messages'))->pluck('body');
        $this->assertTrue($bodies->contains('Sure!'));
        $this->assertFalse($bodies->contains('INTERNAL: refund risk'));
        $this->assertSame('Amanda', collect($response->json('messages'))->firstWhere('body', 'Sure!')['name']); // first name only

        $this->asVisitor($sid)->getJson($this->api('/messages?after='.$reply->id))->assertOk()->assertJsonPath('messages', []);
    }

    public function test_poll_query_count_does_not_grow_with_the_number_of_operators(): void
    {
        $sid = $this->openWidgetSession();
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'hi'])->assertCreated();
        $conversation = Conversation::first();

        foreach (User::factory()->count(5)->create() as $operator) {
            $conversation->messages()->create(['sender_type' => MessageSenderType::Operator, 'sender_id' => $operator->id, 'body' => 'reply']);
        }

        \DB::enableQueryLog();
        $this->asVisitor($sid)->getJson($this->api('/messages'))->assertOk();
        $queries = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(12, $queries, "poll ran {$queries} queries for 5 operators");
    }

    public function test_solved_conversation_is_reported_then_next_message_starts_a_new_one(): void
    {
        $sid = $this->openWidgetSession();
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'first'])->assertCreated();
        $conversation = Conversation::first();
        $conversation->update(['status' => ConversationStatus::Solved]);

        $this->asVisitor($sid)->getJson($this->api('/messages'))->assertOk()->assertJsonPath('conversation.status', 'solved');
        $this->postJson($this->api('/init'), ['session_id' => $sid])->assertOk()->assertJsonPath('conversation', null)->assertJsonPath('messages', []);

        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'again'])->assertCreated();

        $this->assertDatabaseCount('conversations', 2);
    }

    public function test_a_pending_conversation_reopens_when_the_visitor_replies(): void
    {
        $sid = $this->openWidgetSession();
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'first'])->assertCreated();
        Conversation::first()->update(['status' => ConversationStatus::Pending]);

        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'follow-up'])->assertCreated();

        $this->assertSame(ConversationStatus::Open, Conversation::first()->status);
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_spam_conversations_are_hidden_from_the_visitor(): void
    {
        $sid = $this->openWidgetSession();
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'buy pills'])->assertCreated();
        Conversation::first()->update(['status' => ConversationStatus::Spam]);

        $this->asVisitor($sid)->getJson($this->api('/messages'))->assertOk()->assertJsonPath('conversation', null);
    }

    // ------------------------------------------------------------- identify

    public function test_identify_creates_a_contact_and_links_visitor_and_conversation(): void
    {
        $sid = $this->openWidgetSession();
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'hello'])->assertCreated();

        $this->asVisitor($sid)->postJson($this->api('/identify'), ['name' => 'Rahim', 'email' => 'Rahim@Example.com'])
            ->assertOk()->assertJsonPath('identified', true);

        $contact = Contact::first();
        $this->assertSame('rahim@example.com', $contact->email);
        $this->assertSame('widget', $contact->source);
        $this->assertSame($contact->id, Visitor::first()->contact_id);
        $this->assertSame($contact->id, Conversation::first()->contact_id);
    }

    public function test_identify_never_overwrites_or_exposes_an_existing_contact(): void
    {
        $existing = Contact::factory()->create(['workspace_id' => $this->website->workspace_id, 'name' => 'Real Owner', 'email' => 'owner@example.com']);
        $sid = $this->openWidgetSession();

        $response = $this->asVisitor($sid)->postJson($this->api('/identify'), ['name' => 'Attacker', 'email' => 'owner@example.com'])->assertOk();

        $this->assertSame('Real Owner', $existing->fresh()->name);
        $this->assertSame(['identified' => true], $response->json()); // nothing about the contact comes back
    }

    public function test_identify_validation(): void
    {
        $sid = $this->openWidgetSession();

        $this->asVisitor($sid)->postJson($this->api('/identify'), [])->assertUnprocessable();
        $this->asVisitor($sid)->postJson($this->api('/identify'), ['email' => 'nope'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    // ------------------------------------------------------------- audit & housekeeping

    public function test_widget_actions_are_written_to_the_activity_log(): void
    {
        $sid = $this->openWidgetSession();
        $this->asVisitor($sid)->postJson($this->api('/messages'), ['body' => 'secret body text'])->assertCreated();

        $this->assertDatabaseHas('activity_log', ['log_name' => 'widget', 'description' => 'Widget visitor session started']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'widget', 'description' => 'Conversation started from chat widget']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'widget', 'description' => 'Visitor message sent from chat widget']);
        $this->assertSame(0, \DB::table('activity_log')->where('properties', 'like', '%secret body text%')->count());
    }

    public function test_stale_visitors_are_marked_offline(): void
    {
        $stale = Visitor::factory()->create(['is_online' => true, 'last_seen_at' => now()->subMinutes(10)]);
        $fresh = Visitor::factory()->create(['is_online' => true, 'last_seen_at' => now()]);

        $this->artisan('widget:mark-offline')->assertSuccessful();

        $this->assertFalse($stale->fresh()->is_online);
        $this->assertTrue($fresh->fresh()->is_online);
    }

    public function test_domain_normalisation_and_matching(): void
    {
        $this->assertSame('example.com', Website::normalizeDomain('https://www.Example.com:8080/pricing?x=1#a'));
        $this->assertSame('localhost', Website::normalizeDomain('http://localhost:8000'));

        $this->assertTrue($this->website->allowsHost('example.com'));
        $this->assertTrue($this->website->allowsHost('shop.example.com'));
        $this->assertFalse($this->website->allowsHost('badexample.com'));
        $this->assertFalse($this->website->allowsHost('example.com.evil.test'));
        $this->assertFalse($this->website->allowsHost(null));
    }
}