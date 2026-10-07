<?php

namespace Tests\Feature;

use App\Enums\SuperAdminRole;
use App\Livewire\SuperAdmin\Analytics;
use App\Livewire\SuperAdmin\Dashboard;
use App\Livewire\SuperAdmin\MyProfile;
use App\Livewire\SuperAdmin\Onboarding;
use App\Livewire\SuperAdmin\Settings;
use App\Models\Conversation;
use App\Models\Flow;
use App\Models\MrrSnapshot;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\Website;
use App\Models\Workspace;
use App\Services\SuperAdmin\AnalyticsService;
use App\Services\SuperAdmin\ImpersonationService;
use App\Services\SuperAdmin\MrrSnapshotService;
use App\Services\SuperAdmin\OnboardingReportService;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Services\SuperAdmin\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers the Super Admin pages added together: MRR snapshots + revenue trend, Analytics,
 * Onboarding report, Platform Settings (maintenance, impersonation, trial/timezone) and My Profile.
 */
class SuperAdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(PlatformSettingsService::CACHE_KEY);
    }

    private function admin(SuperAdminRole $role = SuperAdminRole::SuperAdmin): SuperAdmin
    {
        $admin = SuperAdmin::factory()->create(['role' => $role]);
        $this->actingAs($admin, 'super_admin');

        return $admin;
    }

    private function subscribe(Workspace $workspace, Plan $plan, string $status = 'active', string $cycle = 'monthly'): Subscription
    {
        return Subscription::factory()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'status' => $status,
            'billing_cycle' => $cycle,
        ]);
    }

    // ------------------------------------------------------------ MRR snapshots

    public function test_mrr_counts_active_subscriptions_only_and_spreads_yearly_over_twelve_months(): void
    {
        $plan = Plan::factory()->create(['price_monthly' => 3000, 'price_yearly' => 24000]);

        $this->subscribe(Workspace::factory()->create(), $plan, 'active', 'monthly');   // 3000
        $this->subscribe(Workspace::factory()->create(), $plan, 'active', 'yearly');    // 2000
        $this->subscribe(Workspace::factory()->create(), $plan, 'trialing');            // ignored
        $this->subscribe(Workspace::factory()->create(), $plan, 'cancelled');           // ignored

        $current = app(MrrSnapshotService::class)->current();

        $this->assertSame(5000, $current['mrr_cents']);
        $this->assertSame(2, $current['active_subscriptions']);
        $this->assertSame(4, $current['tenants_count']);
    }

    public function test_recording_twice_on_one_day_keeps_a_single_snapshot(): void
    {
        $service = app(MrrSnapshotService::class);

        $service->record();
        $service->record();

        $this->assertSame(1, MrrSnapshot::count());
    }

    public function test_snapshot_command_stores_todays_mrr(): void
    {
        $this->artisan('mrr:snapshot')->assertSuccessful();

        $this->assertDatabaseHas('mrr_snapshots', ['snapshot_date' => now()->toDateString()]);
    }

    public function test_dashboard_shows_collecting_state_then_chart_once_there_are_two_days(): void
    {
        $this->admin();

        Livewire::test(Dashboard::class)
            ->assertSee('Collecting data')
            ->assertDontSeeHtml('id="mrrFill"');

        MrrSnapshot::create(['snapshot_date' => now()->subDays(2)->toDateString(), 'mrr_cents' => 10000, 'active_subscriptions' => 1, 'tenants_count' => 1]);

        Livewire::test(Dashboard::class)->assertSeeHtml('id="mrrFill"');
    }

    // ---------------------------------------------------------------- Analytics

    public function test_weekly_signups_and_tickets_land_in_the_current_week(): void
    {
        $workspace = Workspace::factory()->create();
        Workspace::factory()->create();
        Conversation::factory()->create(['workspace_id' => $workspace->id, 'type' => 'ticket']);
        Conversation::factory()->create(['workspace_id' => $workspace->id, 'type' => 'chat']);

        $service = app(AnalyticsService::class);
        $signups = $service->weeklySignups();
        $tickets = $service->weeklyTickets();

        $this->assertCount(12, $signups);
        $this->assertSame(2, $signups[11]['count']);
        $this->assertSame(1, $tickets[11]['count']);
        $this->assertSame('1m 42s', $service->duration(102));
    }

    public function test_analytics_page_renders_for_every_staff_role(): void
    {
        foreach (SuperAdminRole::cases() as $role) {
            $this->admin($role);

            $this->get('/admin/analytics')->assertOk()->assertSee('Platform health');
        }
    }

    // --------------------------------------------------------------- Onboarding

    public function test_onboarding_report_counts_steps_from_live_data(): void
    {
        $started = Workspace::factory()->create(['name' => 'Started Co']);
        Workspace::factory()->create(['name' => 'Idle Co']);
        Website::factory()->create(['workspace_id' => $started->id, 'installed_at' => now()]);
        Flow::factory()->create(['workspace_id' => $started->id]);

        $report = app(OnboardingReportService::class);

        $this->assertSame([$started->id => 2], $report->progressCounts());

        $this->admin();

        Livewire::test(Onboarding::class)
            ->assertSee('Started Co')->assertSee('Idle Co')
            ->set('statusFilter', 'in_progress')
            ->assertSee('Started Co')->assertDontSee('Idle Co')
            ->set('statusFilter', 'not_started')
            ->assertSee('Idle Co')->assertDontSee('Started Co');
    }

    // ----------------------------------------------------------------- Settings

    public function test_only_super_admins_can_open_platform_settings(): void
    {
        $this->admin(SuperAdminRole::SupportStaff);
        $this->get('/admin/settings')->assertForbidden();

        $this->admin(SuperAdminRole::BillingAdmin);
        $this->get('/admin/settings')->assertForbidden();

        $this->admin(SuperAdminRole::SuperAdmin);
        $this->get('/admin/settings')->assertOk();
    }

    public function test_saving_general_settings_updates_the_store_and_writes_an_audit_entry(): void
    {
        $this->admin();

        Livewire::test(Settings::class)
            ->set('platformName', 'Acme Chat')
            ->set('supportEmail', 'help@acme.test')
            ->set('defaultTimezone', 'Asia/Dhaka')
            ->set('trialDays', 21)
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $settings = app(PlatformSettingsService::class);
        $this->assertSame('Acme Chat', $settings->platformName());
        $this->assertSame(21, $settings->trialDays());
        $this->assertSame('Asia/Dhaka', $settings->defaultTimezone());
        $this->assertDatabaseHas('activity_log', ['log_name' => 'settings', 'description' => 'Platform settings updated']);
    }

    public function test_settings_validation_rejects_bad_input(): void
    {
        $this->admin();

        Livewire::test(Settings::class)
            ->set('platformName', '')
            ->set('supportEmail', 'not-an-email')
            ->set('defaultTimezone', 'Mars/Olympus')
            ->set('trialDays', 0)
            ->call('saveGeneral')
            ->assertHasErrors(['platformName', 'supportEmail', 'defaultTimezone', 'trialDays']);
    }

    public function test_maintenance_mode_blocks_the_tenant_panel_until_it_is_switched_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->get('/app/dashboard')->assertStatus(403); // normal: not a member of any workspace

        $this->admin();
        Livewire::test(Settings::class)
            ->set('maintenanceMode', true)
            ->set('maintenanceMessage', 'Back in ten minutes.')
            ->call('saveAccess')
            ->assertHasNoErrors();

        $this->actingAs($user, 'web')->get('/app/dashboard')->assertStatus(503);

        Livewire::test(Settings::class)->set('maintenanceMode', false)->call('saveAccess');

        $this->actingAs($user, 'web')->get('/app/dashboard')->assertStatus(403);
    }

    public function test_switching_impersonation_off_stops_new_sessions(): void
    {
        $admin = $this->admin();
        app(PlatformSettingsService::class)->update($admin, ['allow_impersonation' => false]);

        $workspace = Workspace::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Impersonation is switched off');

        app(ImpersonationService::class)->start($admin, User::factory()->create(), $workspace, '127.0.0.1');
    }

    public function test_new_tenants_use_the_configured_trial_length_and_timezone(): void
    {
        $admin = $this->admin();
        app(PlatformSettingsService::class)->update($admin, ['trial_days' => 30, 'default_timezone' => 'Europe/Paris']);
        $plan = Plan::factory()->create();

        $workspace = app(TenantService::class)->create($admin, [
            'name' => 'Fresh Co',
            'owner_name' => 'Owner One',
            'owner_email' => 'owner@fresh.test',
            'owner_password' => 'Secret-pass-123',
            'plan_id' => $plan->id,
        ]);

        $this->assertSame('Europe/Paris', $workspace->timezone);
        $this->assertSame(30, (int) round(now()->diffInDays($workspace->trial_ends_at)));
    }

    // ---------------------------------------------------------------- My Profile

    public function test_profile_can_be_updated_and_email_must_stay_unique(): void
    {
        $admin = $this->admin();
        SuperAdmin::factory()->create(['email' => 'taken@loop.test', 'role' => SuperAdminRole::SupportStaff]);

        Livewire::test(MyProfile::class)
            ->set('name', 'New Name')
            ->set('email', 'taken@loop.test')
            ->call('saveProfile')
            ->assertHasErrors(['email']);

        Livewire::test(MyProfile::class)
            ->set('name', 'New Name')
            ->set('email', 'fresh@loop.test')
            ->set('phone', '+880 1700-000000')
            ->set('timezone', 'Asia/Dhaka')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $admin->refresh();
        $this->assertSame('New Name', $admin->name);
        $this->assertSame('fresh@loop.test', $admin->email);
        $this->assertSame('Asia/Dhaka', $admin->timezone);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'super-admin', 'description' => 'Profile updated']);
    }

    public function test_password_change_needs_the_current_password_and_a_strong_new_one(): void
    {
        $admin = $this->admin(); // factory password is "password"

        Livewire::test(MyProfile::class)
            ->set('currentPassword', 'wrong-password')
            ->set('newPassword', 'Brand-New-Pass-1')
            ->set('newPasswordConfirmation', 'Brand-New-Pass-1')
            ->call('changePassword')
            ->assertHasErrors(['currentPassword']);

        Livewire::test(MyProfile::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'weak')
            ->set('newPasswordConfirmation', 'weak')
            ->call('changePassword')
            ->assertHasErrors(['newPassword']);

        Livewire::test(MyProfile::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'Brand-New-Pass-1')
            ->set('newPasswordConfirmation', 'Brand-New-Pass-1')
            ->call('changePassword')
            ->assertHasNoErrors();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Brand-New-Pass-1', $admin->fresh()->password));
        $this->assertDatabaseHas('activity_log', ['log_name' => 'super-admin', 'description' => 'Password changed']);
    }
}
