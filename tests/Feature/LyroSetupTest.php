<?php

namespace Tests\Feature;

use App\Enums\AiDataSourceStatus;
use App\Livewire\App\Lyro;
use App\Models\AiAgentSetting;
use App\Models\AiDataSource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use App\Models\Workspace;
use App\Services\Lyro\LyroSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class LyroSetupTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private LyroSetupService $setup;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.anthropic.api_key' => 'test-key']);

        $this->workspace = Workspace::factory()->create();
        $this->setup = app(LyroSetupService::class);
    }

    private function makeSource(string $status = 'synced'): AiDataSource
    {
        return AiDataSource::factory()->create(['workspace_id' => $this->workspace->id, 'status' => $status]);
    }

    private function saveSettings(array $attrs = []): AiAgentSetting
    {
        return AiAgentSetting::query()->updateOrCreate(
            ['workspace_id' => $this->workspace->id],
            $attrs + ['agent_name' => 'Lyro', 'tone' => 'friendly', 'default_language' => 'en'],
        );
    }

    private function stepMap(): array
    {
        return collect($this->setup->overview($this->workspace)['steps'])->keyBy('key')->all();
    }

    // ------------------------------------------------------------- the checklist reflects real data

    public function test_a_fresh_workspace_has_nothing_done_and_cannot_go_live(): void
    {
        $o = $this->setup->overview($this->workspace);

        $this->assertSame(0, $o['done']);
        $this->assertSame(4, $o['total']);
        $this->assertSame(0, $o['percent']);
        $this->assertFalse($o['live']);
        $this->assertFalse($o['can_go_live']);
        $this->assertSame(['sources', 'playground', 'channels', 'live'], array_column($o['steps'], 'key'));
        $this->assertSame(['todo', 'todo', 'todo', 'todo'], array_column($o['steps'], 'state'));
        $this->assertStringContainsString('data source', $o['blockers'][0]);
    }

    public function test_sources_step_follows_the_status_of_the_real_data_sources(): void
    {
        $this->makeSource('pending');
        $this->assertSame('working', $this->stepMap()['sources']['state']);
        $this->assertSame(1, $this->setup->overview($this->workspace)['syncing']);

        AiDataSource::query()->delete();
        $this->makeSource('failed');
        $this->assertSame('attention', $this->stepMap()['sources']['state']);

        $this->makeSource('synced');
        $step = $this->stepMap()['sources'];
        $this->assertSame('done', $step['state']);
        $this->assertSame('1 of 2 sources ready, 1 failed to sync.', $step['detail']);
    }

    public function test_sources_of_other_workspaces_are_ignored(): void
    {
        AiDataSource::factory()->create(['workspace_id' => Workspace::factory()->create()->id, 'status' => 'synced']);

        $this->assertSame('todo', $this->stepMap()['sources']['state']);
    }

    public function test_channels_step_needs_saved_rules_with_something_turned_on(): void
    {
        $this->assertSame('todo', $this->stepMap()['channels']['state']);

        $this->saveSettings(['channel_rules' => ['live_answer_enabled' => true, 'live_outside_hours_only' => true, 'email_answer_enabled' => true, 'email_draft_only' => true]]);
        $step = $this->stepMap()['channels'];
        $this->assertSame('done', $step['state']);
        $this->assertStringContainsString('Live chat on (outside operating hours only)', $step['detail']);
        $this->assertStringContainsString('Email drafts only', $step['detail']);

        $this->saveSettings(['channel_rules' => ['live_answer_enabled' => false, 'email_answer_enabled' => false]]);
        $this->assertSame('attention', $this->stepMap()['channels']['state']);
        $this->assertContains('Turn on at least one channel.', $this->setup->overview($this->workspace)['blockers']);
    }

    public function test_playground_and_live_steps_follow_the_settings_row(): void
    {
        $this->saveSettings(['playground_tested_at' => now()->subHour(), 'is_active' => true, 'went_live_at' => now()->subDay()]);

        $steps = $this->stepMap();
        $this->assertSame('done', $steps['playground']['state']);
        $this->assertSame('done', $steps['live']['state']);
        $this->assertTrue($this->setup->overview($this->workspace)['live']);
        $this->assertSame(2, $this->setup->overview($this->workspace)['done']);
    }

    public function test_progress_counts_completed_steps(): void
    {
        $this->makeSource();
        $this->saveSettings(['channel_rules' => ['live_answer_enabled' => true], 'playground_tested_at' => now()]);

        $o = $this->setup->overview($this->workspace);
        $this->assertSame(3, $o['done']);
        $this->assertSame(75, $o['percent']);
        $this->assertTrue($o['can_go_live']);
    }

    public function test_warnings_cover_untested_lyro_missing_widget_and_email(): void
    {
        Website::factory()->create(['workspace_id' => $this->workspace->id, 'installed_at' => null]);
        $this->saveSettings(['channel_rules' => ['live_answer_enabled' => true, 'email_answer_enabled' => true]]);

        $texts = collect($this->setup->overview($this->workspace)['warnings'])->pluck('text')->implode(' | ');

        $this->assertStringContainsString('not tested', $texts);
        $this->assertStringContainsString('widget installed', $texts);
        $this->assertStringContainsString('Email replies are not processed yet', $texts);
    }

    // ------------------------------------------------------------- Go live / Pause

    public function test_go_live_is_refused_until_a_source_is_synced(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('data source');

        try {
            $this->setup->goLive($this->workspace);
        } finally {
            $this->assertNull(AiAgentSetting::where('workspace_id', $this->workspace->id)->value('is_active'));
        }
    }

    public function test_go_live_is_refused_without_the_ai_engine(): void
    {
        $this->makeSource();
        config(['services.anthropic.api_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY');

        app(LyroSetupService::class)->goLive($this->workspace);
    }

    public function test_go_live_is_refused_when_the_plan_has_no_ai_conversations(): void
    {
        $this->makeSource();
        $plan = Plan::factory()->create(['limits' => ['ai_conversations' => 0]]);
        Subscription::factory()->create(['workspace_id' => $this->workspace->id, 'plan_id' => $plan->id, 'plan_name' => $plan->name, 'status' => 'active']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('plan does not include');

        $this->setup->goLive($this->workspace->fresh());
    }

    public function test_go_live_switches_the_flag_the_responder_checks_and_logs_it(): void
    {
        $this->makeSource();

        $this->setup->goLive($this->workspace);

        $setting = AiAgentSetting::where('workspace_id', $this->workspace->id)->first();
        $this->assertTrue($setting->is_active);
        $this->assertNotNull($setting->went_live_at);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'lyro', 'description' => 'Lyro went live']);

        // Going live twice neither fails nor logs twice, and keeps the original timestamp.
        $first = $setting->went_live_at;
        $this->setup->goLive($this->workspace);
        $this->assertTrue($first->equalTo($setting->fresh()->went_live_at));
        $this->assertSame(1, \DB::table('activity_log')->where('description', 'Lyro went live')->count());
    }

    public function test_pause_turns_lyro_off_even_if_requirements_are_gone(): void
    {
        $this->saveSettings(['is_active' => true, 'went_live_at' => now()]);

        $this->setup->pause($this->workspace);

        $this->assertFalse(AiAgentSetting::where('workspace_id', $this->workspace->id)->first()->is_active);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'lyro', 'description' => 'Lyro paused']);
    }

    public function test_playground_test_is_recorded_only_once(): void
    {
        $this->setup->markPlaygroundTested($this->workspace);
        $at = AiAgentSetting::where('workspace_id', $this->workspace->id)->value('playground_tested_at');
        $this->assertNotNull($at);

        $this->travel(2)->hours();
        $this->setup->markPlaygroundTested($this->workspace);

        $this->assertEquals($at, AiAgentSetting::where('workspace_id', $this->workspace->id)->value('playground_tested_at'));
        $this->assertSame(1, \DB::table('activity_log')->where('description', 'Lyro playground tested')->count());
    }

    public function test_overview_query_count_is_small_and_constant(): void
    {
        foreach (range(1, 6) as $_) {
            $this->makeSource();
        }

        \DB::enableQueryLog();
        $this->setup->overview($this->workspace);
        $queries = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThanOrEqual(10, $queries, "overview ran {$queries} queries");
    }

    // ------------------------------------------------------------- the Livewire tab

    private function openLyroPage()
    {
        $user = User::factory()->create();
        $this->workspace->users()->attach($user->id, ['status' => 'active']);
        app()->instance('currentWorkspace', $this->workspace);

        return Livewire::actingAs($user)->test(Lyro::class);
    }

    public function test_the_page_opens_on_the_setup_tab_and_shows_the_steps(): void
    {
        $this->openLyroPage()
            ->assertSet('tab', 'setup')
            ->assertSee('Set up Lyro AI Agent')
            ->assertSee('0 of 4 done')
            ->assertSee('Add data sources')
            ->assertSee('Go live');
    }

    public function test_go_live_button_shows_the_reason_when_requirements_are_missing(): void
    {
        $this->openLyroPage()
            ->call('goLive')
            ->assertDispatched('toast', message: 'Add at least one data source and wait for it to finish syncing.', type: 'err');

        $this->assertNull(AiAgentSetting::where('workspace_id', $this->workspace->id)->first()?->is_active);
    }

    public function test_go_live_and_pause_work_from_the_page(): void
    {
        $this->makeSource();

        $this->openLyroPage()
            ->call('goLive')
            ->assertDispatched('toast', message: 'Lyro is live and will now answer customers.')
            ->assertSee('2 of 4 done') // the synced source + Go live
            ->assertSee('Pause')
            ->call('pauseLyro')
            ->assertSee('Not live');

        $this->assertFalse(AiAgentSetting::where('workspace_id', $this->workspace->id)->first()->is_active);
    }

    public function test_a_real_playground_reply_completes_the_playground_step(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'We open at 9.']]])]);

        $this->openLyroPage()
            ->set('playground_question', 'When do you open?')
            ->call('sendTestMessage');

        $this->assertNotNull(AiAgentSetting::where('workspace_id', $this->workspace->id)->value('playground_tested_at'));
    }

    public function test_a_failed_ai_call_or_missing_engine_does_not_count_as_tested(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $this->openLyroPage()->set('playground_question', 'Hi?')->call('sendTestMessage');
        $this->assertNull(AiAgentSetting::where('workspace_id', $this->workspace->id)->value('playground_tested_at'));

        config(['services.anthropic.api_key' => null]);
        $this->openLyroPage()->set('playground_question', 'Hi again?')->call('sendTestMessage');
        $this->assertNull(AiAgentSetting::where('workspace_id', $this->workspace->id)->value('playground_tested_at'));
    }
}