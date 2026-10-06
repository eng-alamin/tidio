<?php

namespace Tests\Feature;

use App\Enums\MessageSenderType;
use App\Models\AiAgentSetting;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageCounter;
use App\Models\WidgetSetting;
use App\Models\Website;
use App\Services\UsageLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UsageLimitsTest extends TestCase
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
        ]);

        $this->website = Website::factory()->create(['domain' => 'example.com', 'installed_at' => null]);
        WidgetSetting::factory()->create(['website_id' => $this->website->id]);
        AiAgentSetting::create(['workspace_id' => $this->website->workspace_id, 'agent_name' => 'Lyro', 'tone' => 'friendly', 'default_language' => 'en', 'is_active' => true]);

        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Hi!']]])]);
    }

    private function onPlan(array $limits): void
    {
        $plan = Plan::factory()->create(['limits' => $limits]);
        Subscription::factory()->create(['workspace_id' => $this->website->workspace_id, 'plan_id' => $plan->id, 'plan_name' => $plan->name, 'status' => 'active']);
    }

    private function newVisitorSays(string $body = 'Hello'): void
    {
        $key = $this->website->widget_key;
        $sid = $this->postJson("/widget-api/{$key}/init", ['page_url' => 'https://example.com/'])->assertOk()->json('session_id');
        $this->withHeaders(['X-Widget-Session' => $sid])->postJson("/widget-api/{$key}/messages", ['body' => $body])->assertCreated();
    }

    private function bots(): int
    {
        return Message::where('sender_type', MessageSenderType::Bot->value)->count();
    }

    private function counter(string $metric): int
    {
        return (int) UsageCounter::where('workspace_id', $this->website->workspace_id)->where('metric', $metric)->sum('count');
    }

    public function test_limiter_reads_plan_limits_and_treats_missing_as_unlimited(): void
    {
        $limiter = app(UsageLimiter::class);
        $workspace = $this->website->workspace;

        $this->assertSame(50, $limiter->limit($workspace, 'ai_conversations')); // no plan → default

        $this->onPlan(['operators' => 3, 'ai_conversations' => 0]);
        $workspace = $workspace->fresh();

        $this->assertSame(0, $limiter->limit($workspace, 'ai_conversations'));
        $this->assertNull($limiter->limit($workspace, 'conversations'));
        $this->assertFalse($limiter->hasRoom($workspace, 'ai_conversations'));
        $this->assertTrue($limiter->hasRoom($workspace, 'conversations'));
    }

    public function test_record_accumulates_in_one_monthly_row(): void
    {
        $limiter = app(UsageLimiter::class);
        $workspace = $this->website->workspace;

        $limiter->record($workspace, 'conversations');
        $limiter->record($workspace, 'conversations', 2);

        $this->assertSame(3, $limiter->used($workspace, 'conversations'));
        $this->assertSame(1, UsageCounter::where('workspace_id', $workspace->id)->where('metric', 'conversations')->count());
    }

    public function test_every_new_conversation_counts_toward_conversations(): void
    {
        $this->newVisitorSays();
        $this->newVisitorSays();

        $this->assertSame(2, $this->counter('conversations'));
    }

    public function test_lyro_counts_one_ai_conversation_even_with_several_replies(): void
    {
        $key = $this->website->widget_key;
        $sid = $this->postJson("/widget-api/{$key}/init", ['page_url' => 'https://example.com/'])->json('session_id');

        foreach (['one', 'two', 'three'] as $text) {
            $this->withHeaders(['X-Widget-Session' => $sid])->postJson("/widget-api/{$key}/messages", ['body' => $text])->assertCreated();
        }

        $this->assertSame(3, $this->bots());
        $this->assertSame(1, $this->counter('ai_conversations'));
    }

    public function test_lyro_is_silent_for_new_conversations_once_the_plan_limit_is_reached(): void
    {
        $this->onPlan(['ai_conversations' => 2]);

        $this->newVisitorSays();
        $this->newVisitorSays();
        $this->assertSame(2, $this->bots());

        $this->newVisitorSays(); // third conversation: over the limit
        $this->assertSame(2, $this->bots());
        $this->assertSame(3, Conversation::count()); // the visitor's chat itself still works
    }

    public function test_a_conversation_lyro_already_joined_may_finish_after_the_limit_is_hit(): void
    {
        $this->onPlan(['ai_conversations' => 1]);

        $key = $this->website->widget_key;
        $sid = $this->postJson("/widget-api/{$key}/init", ['page_url' => 'https://example.com/'])->json('session_id');
        $send = fn (string $t) => $this->withHeaders(['X-Widget-Session' => $sid])->postJson("/widget-api/{$key}/messages", ['body' => $t])->assertCreated();

        $send('first');  // uses the only allowed AI conversation
        $send('second'); // same conversation → still answered

        $this->assertSame(2, $this->bots());
    }

    public function test_plan_with_zero_ai_conversations_keeps_lyro_off(): void
    {
        $this->onPlan(['operators' => 1, 'ai_conversations' => 0]); // like the seeded Free plan

        $this->newVisitorSays();

        $this->assertSame(0, $this->bots());
        Http::assertNothingSent();
    }
}
