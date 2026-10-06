<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Enums\MessageSenderType;
use App\Models\AiAgentSetting;
use App\Models\AiProcedure;
use App\Models\AiUnansweredQuestion;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tag;
use App\Models\User;
use App\Models\WidgetSetting;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WidgetLyroTest extends TestCase
{
    use RefreshDatabase;

    private Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'widget.allow_any_origin' => false,
            'widget.lyro.enabled' => true,
            // 'queue' + the sync driver runs the job inline, exactly once per message. With
            // 'after_response', Laravel's test app re-runs earlier requests' terminating callbacks
            // on every later request, which would replay old jobs and make counts misleading.
            'widget.lyro.dispatch' => 'queue',
            'queue.default' => 'sync',
            'services.anthropic.api_key' => 'test-key',
        ]);

        $this->website = Website::factory()->create(['domain' => 'example.com', 'installed_at' => null]);
        WidgetSetting::factory()->create(['website_id' => $this->website->id]);

        $this->lyro(['is_active' => true]);
    }

    private function lyro(array $attrs = []): AiAgentSetting
    {
        return AiAgentSetting::query()->updateOrCreate(
            ['workspace_id' => $this->website->workspace_id],
            $attrs + ['agent_name' => 'Lyro', 'tone' => 'friendly', 'default_language' => 'en'],
        );
    }

    private function fakeClaude(string $text): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => $text]]])]);
    }

    private function sid(): string
    {
        return $this->postJson('/widget-api/'.$this->website->widget_key.'/init', ['page_url' => 'https://example.com/'])
            ->assertOk()->json('session_id');
    }

    private function say(string $sid, string $body = 'What are your opening hours?'): void
    {
        $this->withHeaders(['X-Widget-Session' => $sid])
            ->postJson('/widget-api/'.$this->website->widget_key.'/messages', ['body' => $body])
            ->assertCreated();
    }

    private function botMessages()
    {
        return Message::query()->where('sender_type', MessageSenderType::Bot->value);
    }

    public function test_lyro_answers_after_the_visitor_sends_a_message(): void
    {
        $this->fakeClaude('We are open 9 to 5, Monday to Friday.');

        $sid = $this->sid();
        $this->say($sid);

        $bot = $this->botMessages()->first();
        $this->assertNotNull($bot);
        $this->assertSame('We are open 9 to 5, Monday to Friday.', $bot->body);

        // The visitor sees it on the next poll, with the agent's name.
        $poll = $this->withHeaders(['X-Widget-Session' => $sid])
            ->getJson('/widget-api/'.$this->website->widget_key.'/messages?after=0')->assertOk()->json('messages');

        $this->assertContains('bot', array_column($poll, 'from'));
        $this->assertContains('Lyro', array_column($poll, 'name'));
    }

    public function test_system_prompt_includes_guidance_and_active_procedures(): void
    {
        $this->lyro(['tone' => 'playful', 'guidance_instructions' => 'Never promise refund dates.']);
        AiProcedure::create(['workspace_id' => $this->website->workspace_id, 'title' => 'Refunds', 'trigger_condition' => 'asks about refunds', 'instructions' => "Ask for the order number\nExplain the 14-day policy", 'is_active' => true]);
        AiProcedure::create(['workspace_id' => $this->website->workspace_id, 'title' => 'Draft only', 'instructions' => 'SECRET DRAFT', 'is_active' => false]);
        $this->fakeClaude('Sure!');

        $this->say($this->sid());

        Http::assertSent(function ($request) {
            $system = $request['system'];

            return str_contains($system, 'Never promise refund dates.')
                && str_contains($system, 'Refunds')
                && str_contains($system, '14-day policy')
                && ! str_contains($system, 'SECRET DRAFT')
                && $request['messages'][0]['role'] === 'user';
        });
    }

    public function test_lyro_stays_silent_when_switched_off(): void
    {
        $this->lyro(['is_active' => false]);
        $this->fakeClaude('Hello');

        $this->say($this->sid());

        $this->assertSame(0, $this->botMessages()->count());
        Http::assertNothingSent();
    }

    public function test_lyro_stays_silent_when_live_chat_answering_is_disabled(): void
    {
        $this->lyro(['channel_rules' => ['live_answer_enabled' => false]]);
        $this->fakeClaude('Hello');

        $this->say($this->sid());

        $this->assertSame(0, $this->botMessages()->count());
    }

    public function test_lyro_stays_silent_without_an_api_key(): void
    {
        config(['services.anthropic.api_key' => null]);
        Http::fake();

        $this->say($this->sid());

        $this->assertSame(0, $this->botMessages()->count());
        Http::assertNothingSent();
    }

    public function test_lyro_stays_silent_after_a_human_replied(): void
    {
        $this->fakeClaude('Hello');
        $sid = $this->sid();
        $this->say($sid, 'First message');
        $this->assertSame(1, $this->botMessages()->count());

        $conversation = Conversation::first();
        $conversation->messages()->create(['sender_type' => MessageSenderType::Operator, 'sender_id' => User::factory()->create()->id, 'body' => 'Hi, I will take it from here.', 'is_private_note' => false]);

        $this->say($sid, 'Second message');

        $this->assertSame(1, $this->botMessages()->count()); // no new bot reply
    }

    public function test_lyro_stays_silent_when_the_conversation_is_assigned(): void
    {
        $this->fakeClaude('Hello');
        $sid = $this->sid();
        $this->say($sid, 'First message');

        Conversation::first()->update(['assigned_operator_id' => User::factory()->create()->id]);
        $this->say($sid, 'Second message');

        $this->assertSame(1, $this->botMessages()->count());
    }

    public function test_unknown_answer_is_logged_and_hands_off_to_the_team(): void
    {
        $this->lyro(['handoff_rules' => ['on_request' => true, 'on_low_confidence' => true, 'on_negative_sentiment' => false]]);
        $this->fakeClaude("I'm not sure about that. [[UNKNOWN]]");

        $sid = $this->sid();
        $this->say($sid, 'Do you ship to Mars?');
        $this->say($sid, 'Do you ship to Mars?');

        $bot = $this->botMessages()->first();
        $this->assertSame("I'm not sure about that.", $bot->body); // marker stripped

        $q = AiUnansweredQuestion::first();
        $this->assertSame('Do you ship to Mars?', $q->question);
        $this->assertSame(1, $q->asked_count); // second ask came after handoff, Lyro stayed silent

        $conversation = Conversation::first();
        $this->assertNotNull($conversation->aiMeta->handed_off_at);
        $this->assertSame(ConversationStatus::Open, $conversation->status);
        $this->assertSame(1, $this->botMessages()->count());
    }

    public function test_unknown_answer_does_not_hand_off_when_low_confidence_rule_is_off(): void
    {
        $this->lyro(['handoff_rules' => ['on_request' => true, 'on_low_confidence' => false, 'on_negative_sentiment' => false]]);
        $this->fakeClaude('Not sure. [[UNKNOWN]]');

        $this->say($this->sid(), 'Odd question');

        $this->assertSame(1, AiUnansweredQuestion::count());
        $this->assertNull(Conversation::first()->aiMeta);
    }

    public function test_handoff_marker_hands_the_chat_to_a_human(): void
    {
        $this->fakeClaude('Sure, bringing in a teammate. [[HANDOFF]]');

        $this->say($this->sid(), 'I want to talk to a person');

        $this->assertSame('Sure, bringing in a teammate.', $this->botMessages()->first()->body);
        $this->assertNotNull(Conversation::first()->aiMeta->handed_off_at);
        $this->assertSame(0, AiUnansweredQuestion::count());
    }

    public function test_audience_logged_in_only_ignores_anonymous_visitors(): void
    {
        $this->lyro(['audience_answer_for' => 'logged_in']);
        $this->fakeClaude('Hello');

        $sid = $this->sid();
        $this->say($sid, 'Hi anonymous');
        $this->assertSame(0, $this->botMessages()->count());

        $this->withHeaders(['X-Widget-Session' => $sid])
            ->postJson('/widget-api/'.$this->website->widget_key.'/identify', ['name' => 'Ana', 'email' => 'ana@example.com'])
            ->assertOk();

        $this->say($sid, 'Hi again');
        $this->assertSame(1, $this->botMessages()->count());
    }

    public function test_excluded_tag_keeps_lyro_away_from_tagged_contacts(): void
    {
        $this->lyro(['audience_exclude_tag' => 'VIP']);
        $this->fakeClaude('Hello');

        $workspaceId = $this->website->workspace_id;
        $contact = Contact::factory()->create(['workspace_id' => $workspaceId, 'email' => 'vip@example.com']);
        $tag = Tag::factory()->create(['workspace_id' => $workspaceId, 'name' => 'vip']);
        $contact->tags()->attach($tag->id);

        $sid = $this->sid();
        $this->withHeaders(['X-Widget-Session' => $sid])
            ->postJson('/widget-api/'.$this->website->widget_key.'/identify', ['name' => 'V', 'email' => 'vip@example.com'])
            ->assertOk();

        $this->say($sid);

        $this->assertSame(0, $this->botMessages()->count());
    }

    public function test_api_failure_leaves_the_visitor_message_and_no_bot_reply(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'overloaded']], 529)]);

        $this->say($this->sid());

        $this->assertSame(1, Message::query()->where('sender_type', MessageSenderType::Visitor->value)->count());
        $this->assertSame(0, $this->botMessages()->count());
    }

    public function test_master_switch_in_config_disables_lyro(): void
    {
        config(['widget.lyro.enabled' => false]);
        $this->fakeClaude('Hello');

        $this->say($this->sid());

        $this->assertSame(0, $this->botMessages()->count());
        Http::assertNothingSent();
    }
}