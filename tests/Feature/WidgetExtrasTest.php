<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\MessageSenderType;
use App\Events\ConversationSignal;
use App\Models\AiAgentSetting;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Visitor;
use App\Models\WidgetSetting;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers: visitor country, hard conversation limit, file attachments, real-time signals.
 */
class WidgetExtrasTest extends TestCase
{
    use RefreshDatabase;

    private Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'widget.allow_any_origin' => false,
            'widget.lyro.enabled' => true,
            'widget.lyro.dispatch' => 'queue',
            'queue.default' => 'sync',
            'services.anthropic.api_key' => 'test-key',
            'widget.geo.headers' => ['CF-IPCountry'],
            'widget.geo.default_country' => null,
        ]);

        Storage::fake('local');

        $this->website = Website::factory()->create(['domain' => 'example.com', 'installed_at' => null]);
        WidgetSetting::factory()->create(['website_id' => $this->website->id]);

        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Hello!']]])]);
    }

    private function url(string $path): string
    {
        return '/widget-api/'.$this->website->widget_key.'/'.$path;
    }

    private function init(array $headers = []): string
    {
        return $this->withHeaders($headers)->postJson($this->url('init'), ['page_url' => 'https://example.com/'])
            ->assertOk()->json('session_id');
    }

    private function say(string $sid, string $body = 'Hi', array $files = [])
    {
        $payload = ['body' => $body] + ($files ? ['files' => $files] : []);

        return $this->withHeaders(['X-Widget-Session' => $sid])->post($this->url('messages'), $payload, ['Accept' => 'application/json']);
    }

    private function lyro(array $attrs): void
    {
        AiAgentSetting::updateOrCreate(
            ['workspace_id' => $this->website->workspace_id],
            $attrs + ['agent_name' => 'Lyro', 'tone' => 'friendly', 'default_language' => 'en', 'is_active' => true],
        );
    }

    private function bots(): int
    {
        return Message::where('sender_type', MessageSenderType::Bot->value)->count();
    }

    // ------------------------------------------------------------------ country

    public function test_visitor_country_is_saved_from_the_cdn_header_and_updates_when_it_changes(): void
    {
        $sid = $this->init(['CF-IPCountry' => 'bd']);
        $visitor = Visitor::where('session_id', $sid)->first();

        $this->assertSame('BD', $visitor->country_code);
        $this->assertSame('Bangladesh', $visitor->location);

        $this->postJson($this->url('init'), ['session_id' => $sid], ['CF-IPCountry' => 'US'])->assertOk();
        $this->assertSame('US', $visitor->fresh()->country_code);
    }

    public function test_unknown_or_invalid_country_header_is_ignored(): void
    {
        $sid = $this->init(['CF-IPCountry' => 'XX']);

        $this->assertNull(Visitor::where('session_id', $sid)->first()->country_code);
    }

    public function test_lyro_specific_countries_answers_only_listed_countries(): void
    {
        $this->lyro(['audience_answer_for' => 'specific_countries', 'audience_countries' => ['BD']]);

        $this->say($this->init(['CF-IPCountry' => 'US']))->assertCreated();
        $this->assertSame(0, $this->bots());

        $this->say($this->init(['CF-IPCountry' => 'BD']))->assertCreated();
        $this->assertSame(1, $this->bots());

        $this->flushHeaders(); // withHeaders() is sticky: drop the BD header from the previous call
        $this->say($this->init())->assertCreated(); // country unknown -> silent
        $this->assertSame(1, $this->bots());
    }

    public function test_lyro_specific_countries_with_an_empty_list_stays_silent(): void
    {
        $this->lyro(['audience_answer_for' => 'specific_countries', 'audience_countries' => []]);

        $this->say($this->init(['CF-IPCountry' => 'BD']))->assertCreated();
        $this->assertSame(0, $this->bots());
    }

    // ------------------------------------------------------------------ hard limit

    private function onPlan(array $limits): void
    {
        $plan = Plan::factory()->create(['limits' => $limits]);
        Subscription::factory()->create(['workspace_id' => $this->website->workspace_id, 'plan_id' => $plan->id, 'plan_name' => $plan->name, 'status' => 'active']);
    }

    public function test_new_conversation_is_blocked_when_the_monthly_limit_is_used_up(): void
    {
        $this->onPlan(['conversations' => 1, 'ai_conversations' => 50]);

        $this->say($this->init())->assertCreated();

        $blocked = $this->say($this->init());
        $blocked->assertStatus(403)->assertJsonPath('code', 'conversation_limit');

        $this->assertSame(1, Conversation::count());
        $this->assertSame(1, Message::where('sender_type', MessageSenderType::Visitor->value)->count());
    }

    public function test_an_open_conversation_can_keep_chatting_after_the_limit_is_reached(): void
    {
        $this->onPlan(['conversations' => 1, 'ai_conversations' => 50]);

        $sid = $this->init();
        $this->say($sid, 'first')->assertCreated();
        $this->say($sid, 'second')->assertCreated();

        $this->assertSame(1, Conversation::count());
    }

    public function test_a_solved_conversation_cannot_be_replaced_by_a_new_one_over_the_limit(): void
    {
        $this->onPlan(['conversations' => 1, 'ai_conversations' => 50]);

        $sid = $this->init();
        $this->say($sid)->assertCreated();
        Conversation::query()->update(['status' => ConversationStatus::Solved]);

        $this->say($sid)->assertStatus(403)->assertJsonPath('code', 'conversation_limit');
    }

    public function test_no_conversations_key_in_the_plan_means_unlimited(): void
    {
        $this->onPlan(['ai_conversations' => 50]);

        $this->say($this->init())->assertCreated();
        $this->say($this->init())->assertCreated();
        $this->assertSame(2, Conversation::count());
    }

    // ------------------------------------------------------------------ attachments

    public function test_visitor_can_send_an_image_and_download_it_through_a_signed_url(): void
    {
        $sid = $this->init();

        $res = $this->say($sid, '', [UploadedFile::fake()->image('photo.png', 20, 20)])->assertCreated();

        $file = $res->json('message.attachments.0');
        $this->assertSame('photo.png', $file['name']);
        $this->assertSame('image', $file['kind']);
        $this->assertArrayNotHasKey('path', $file);

        $stored = Message::first()->attachments[0];
        Storage::disk('local')->assertExists($stored['path']);

        $this->get($file['url'])->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(preg_replace('/signature=[^&]+/', 'signature=bad', $file['url']))->assertForbidden();
    }

    public function test_disallowed_and_oversized_files_are_rejected_and_nothing_is_saved(): void
    {
        $sid = $this->init();

        $this->say($sid, 'x', [UploadedFile::fake()->create('virus.php', 5, 'text/x-php')])->assertStatus(422);
        $this->say($sid, 'x', [UploadedFile::fake()->create('fake.png', 5, 'text/plain')])->assertStatus(422); // content is not a PNG
        $this->say($sid, 'x', [UploadedFile::fake()->create('big.pdf', 99999, 'application/pdf')])->assertStatus(422);

        $this->assertSame(0, Message::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_too_many_files_are_rejected(): void
    {
        $files = collect(range(1, 4))->map(fn ($i) => UploadedFile::fake()->image("p{$i}.png"))->all();

        $this->say($this->init(), 'x', $files)->assertStatus(422);
    }

    public function test_an_empty_message_without_files_is_rejected(): void
    {
        $this->say($this->init(), '')->assertStatus(422);
    }

    public function test_lyro_ignores_a_file_only_message(): void
    {
        $this->lyro([]);

        $this->say($this->init(), '', [UploadedFile::fake()->image('a.png')])->assertCreated();

        $this->assertSame(0, $this->bots());
    }

    public function test_private_note_attachments_are_never_served_to_visitors(): void
    {
        $sid = $this->init();
        $this->say($sid)->assertCreated();
        $conversation = Conversation::first();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Operator,
            'sender_id' => null,
            'body' => 'secret',
            'is_private_note' => true,
            'attachments' => [['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'n.txt', 'ext' => 'txt', 'mime' => 'text/plain', 'size' => 1, 'kind' => 'file', 'path' => 'attachments/1/1/x.txt']],
        ]);
        Storage::disk('local')->put('attachments/1/1/x.txt', 'secret');

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('widget.attachments.show', now()->addMinutes(5), ['message' => $message->id, 'attachment' => '11111111-1111-4111-8111-111111111111']);

        $this->get($url)->assertNotFound();
    }

    public function test_deleting_a_message_removes_its_files(): void
    {
        $this->say($this->init(), '', [UploadedFile::fake()->image('a.png')])->assertCreated();
        $message = Message::first();
        $path = $message->attachments[0]['path'];

        Storage::disk('local')->assertExists($path);
        $message->delete();
        Storage::disk('local')->assertMissing($path);
    }

    // ------------------------------------------------------------------ real-time

    private function realtimeOn(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'widget.realtime.enabled' => true,
            'widget.realtime.public_host' => 'localhost',
            'widget.realtime.public_port' => 8080,
            'widget.realtime.public_scheme' => 'http',
        ]);
    }

    public function test_init_returns_realtime_details_only_when_enabled(): void
    {
        $this->assertNull($this->postJson($this->url('init'), [])->assertOk()->json('realtime'));

        $this->realtimeOn();
        $res = $this->postJson($this->url('init'), [])->assertOk();

        $this->assertSame('test-key', $res->json('realtime.key'));
        $this->assertStringStartsWith('private-widget.visitor.', $res->json('realtime.channel'));
        $this->assertStringNotContainsString('test-secret', $res->getContent());
    }

    public function test_frame_csp_allows_the_socket_only_when_enabled(): void
    {
        $frame = '/widget-frame/'.$this->website->widget_key;

        $this->assertStringNotContainsString('ws://', $this->get($frame)->headers->get('Content-Security-Policy'));

        $this->realtimeOn();
        $this->assertStringContainsString('ws://localhost:8080', $this->get($frame)->headers->get('Content-Security-Policy'));
    }

    public function test_visitor_message_sends_a_signal_to_operators_and_the_visitor(): void
    {
        $this->realtimeOn();
        Event::fake([ConversationSignal::class]);

        $sid = $this->init();
        $this->say($sid)->assertCreated();

        Event::assertDispatched(ConversationSignal::class, function (ConversationSignal $e) {
            $names = collect($e->broadcastOn())->map(fn ($c) => $c->name)->all();

            return $e->reason === 'message'
                && in_array('private-workspace.'.$this->website->workspace_id, $names, true)
                && count($names) === 2
                && array_keys($e->broadcastWith()) === ['conversation_id', 'reason']; // no message content
        });
    }

    public function test_internal_notes_never_signal_the_visitor_channel(): void
    {
        $this->realtimeOn();

        $sid = $this->init();
        $this->say($sid)->assertCreated();
        $conversation = Conversation::first();

        Event::fake([ConversationSignal::class]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Operator,
            'sender_id' => null,
            'body' => 'internal',
            'is_private_note' => true,
        ]);

        Event::assertDispatched(ConversationSignal::class, function (ConversationSignal $e) {
            return count($e->broadcastOn()) === 1
                && $e->broadcastOn()[0]->name === 'private-workspace.'.$this->website->workspace_id;
        });
    }

    public function test_nothing_is_broadcast_when_realtime_is_off(): void
    {
        Event::fake([ConversationSignal::class]);

        $this->say($this->init())->assertCreated();

        Event::assertNotDispatched(ConversationSignal::class);
    }

    public function test_widget_channel_auth_only_signs_the_visitors_own_channel(): void
    {
        $this->realtimeOn();

        $sid = $this->init();
        $visitor = Visitor::where('session_id', $sid)->first();
        $own = 'private-widget.visitor.'.$visitor->id;
        $headers = ['X-Widget-Session' => $sid];

        $res = $this->withHeaders($headers)->post($this->url('broadcasting/auth'), ['socket_id' => '123.456', 'channel_name' => $own])->assertOk();
        $this->assertSame('test-key:'.hash_hmac('sha256', '123.456:'.$own, 'test-secret'), $res->json('auth'));

        $this->withHeaders($headers)->post($this->url('broadcasting/auth'), ['socket_id' => '123.456', 'channel_name' => 'private-widget.visitor.'.($visitor->id + 1)])->assertForbidden();
        $this->withHeaders($headers)->post($this->url('broadcasting/auth'), ['socket_id' => '123.456', 'channel_name' => 'private-workspace.'.$visitor->workspace_id])->assertForbidden();
        $this->withHeaders($headers)->post($this->url('broadcasting/auth'), ['socket_id' => 'bad', 'channel_name' => $own])->assertStatus(422);
        $this->flushHeaders(); // withHeaders() is sticky: now call WITHOUT the session header
        $this->post($this->url('broadcasting/auth'), ['socket_id' => '123.456', 'channel_name' => $own])->assertUnauthorized();
    }
}