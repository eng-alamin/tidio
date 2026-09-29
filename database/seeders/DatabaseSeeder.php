<?php

namespace Database\Seeders;

use App\Models\AdminNote;
use App\Models\AiAction;
use App\Models\AiAgentSetting;
use App\Models\AiConversationMeta;
use App\Models\AiDataSource;
use App\Models\AiProcedure;
use App\Models\Announcement;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\ContactSegment;
use App\Models\Conversation;
use App\Models\ConversationMetric;
use App\Models\Coupon;
use App\Models\CsatRating;
use App\Models\CsatSetting;
use App\Models\CustomField;
use App\Models\Department;
use App\Models\FeatureFlag;
use App\Models\Flow;
use App\Models\FlowRun;
use App\Models\FlowTemplate;
use App\Models\ImpersonationLog;
use App\Models\Invoice;
use App\Models\Macro;
use App\Models\Mention;
use App\Models\Message;
use App\Models\NotificationPreference;
use App\Models\OnboardingProgress;
use App\Models\OperatingHour;
use App\Models\Plan;
use App\Models\Role;
use App\Models\SavedView;
use App\Models\Sla;
use App\Models\Subscription;
use App\Models\SuperAdmin;
use App\Models\SystemSetting;
use App\Models\Tag;
use App\Models\TrackingSetting;
use App\Models\UsageCounter;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Webhook;
use App\Models\WidgetSetting;
use App\Models\WidgetTranslation;
use App\Models\Website;
use App\Models\Workspace;
use App\Models\WorkflowRule;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Platform-level (not tied to any single workspace) ----
        $plans = collect(['Free', 'Basic', 'Plus', 'Premium'])->map(function (string $name, int $i) {
            return Plan::create([
                'name' => $name,
                'slug' => strtolower($name),
                'price_monthly' => [0, 2900, 5900, 9900][$i],
                'price_yearly' => [0, 29000, 59000, 99000][$i],
                'features' => ['live_chat', 'help_desk', $name !== 'Free' ? 'ai_agent' : null],
                'limits' => ['operators' => [1, 3, 10, 50][$i], 'ai_conversations' => [0, 50, 500, 2000][$i]],
                'sort_order' => $i,
            ]);
        });

        SystemSetting::create(['key' => 'maintenance_mode', 'value' => 'false']);
        SystemSetting::create(['key' => 'default_trial_days', 'value' => '7']);

        FeatureFlag::create(['workspace_id' => null, 'key' => 'beta_flow_builder_v2', 'is_enabled' => false]);

        $superAdmin = SuperAdmin::factory()->create(['email' => 'super@tidio-clone.test', 'role' => 'super_admin']);

        $coupon = Coupon::factory()->create(['code' => 'WELCOME20']);

        Announcement::factory()->create([
            'created_by' => $superAdmin->id,
            'title' => 'Scheduled maintenance this weekend',
        ]);

        // Built-in flow template library (workspace_id null = system template)
        FlowTemplate::factory(4)->create(['workspace_id' => null]);

        // ---- Two sample workspaces, each fully populated ----
        foreach (['Bella Boutique', 'Northwind Electronics'] as $workspaceName) {
            $owner = User::factory()->create(['name' => 'Owner of '.$workspaceName]);

            $workspace = Workspace::factory()->create([
                'owner_id' => $owner->id,
                'name' => $workspaceName,
                'plan' => 'basic',
            ]);

            $ownerRole = Role::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Owner']);
            $operatorRole = Role::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Operator']);

            $workspace->users()->attach($owner->id, ['role_id' => $ownerRole->id, 'status' => 'active', 'joined_at' => now()]);

            $operators = User::factory(2)->create();
            foreach ($operators as $operator) {
                $workspace->users()->attach($operator->id, ['role_id' => $operatorRole->id, 'status' => 'active', 'joined_at' => now()]);
            }

            $salesDept = Department::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Sales']);
            $supportDept = Department::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Support']);
            $salesDept->users()->attach($operators->first()->id);
            $supportDept->users()->attach($operators->last()->id);

            foreach (range(0, 6) as $day) {
                OperatingHour::factory()->create(['workspace_id' => $workspace->id, 'day_of_week' => $day]);
            }

            $website = Website::factory()->create(['workspace_id' => $workspace->id]);
            WidgetSetting::factory()->create(['website_id' => $website->id]);

            $channel = Channel::factory()->create(['workspace_id' => $workspace->id, 'type' => 'whatsapp']);

            $customField = CustomField::factory()->create(['workspace_id' => $workspace->id]);

            $tagNames = collect(['VIP', 'Lead', 'Refund', 'Bug Report', 'Urgent'])->shuffle()->take(3);
            $tags = $tagNames->map(fn ($name) => Tag::factory()->create([
                'workspace_id' => $workspace->id,
                'name' => $name,
            ]));

            $macro = Macro::factory()->create(['workspace_id' => $workspace->id, 'created_by' => $operators->first()->id]);

            $sla = Sla::factory()->create(['workspace_id' => $workspace->id]);

            SavedView::factory()->create(['workspace_id' => $workspace->id, 'user_id' => null, 'name' => 'Unassigned WhatsApp']);

            $aiSettings = AiAgentSetting::factory()->create(['workspace_id' => $workspace->id]);
            AiDataSource::factory(2)->create(['workspace_id' => $workspace->id]);

            $flow = Flow::factory()->create(['workspace_id' => $workspace->id, 'created_by' => $owner->id]);

            $subscription = Subscription::factory()->create([
                'workspace_id' => $workspace->id,
                'plan_id' => $plans[1]->id, // Basic
                'plan_name' => $plans[1]->name,
                'status' => 'active',
            ]);
            UsageCounter::factory()->create(['workspace_id' => $workspace->id]);
            Webhook::factory()->create(['workspace_id' => $workspace->id]);

            Invoice::factory()->create([
                'workspace_id' => $workspace->id,
                'subscription_id' => $subscription->id,
                'coupon_id' => $coupon->id,
                'amount' => $plans[1]->price_monthly,
            ]);

            // App panel completeness extension
            ContactSegment::factory()->create(['workspace_id' => $workspace->id]);
            AiProcedure::factory()->create(['workspace_id' => $workspace->id]);
            AiAction::factory()->create(['workspace_id' => $workspace->id]);
            WidgetTranslation::factory()->create(['website_id' => $website->id]);
            WorkflowRule::factory()->create(['workspace_id' => $workspace->id]);
            CsatSetting::factory()->create(['workspace_id' => $workspace->id]);
            TrackingSetting::factory()->create(['workspace_id' => $workspace->id]);
            foreach (['install_widget', 'connect_mailbox', 'invite_team'] as $step) {
                OnboardingProgress::factory()->create(['workspace_id' => $workspace->id, 'step_key' => $step]);
            }
            NotificationPreference::factory()->create([
                'user_id' => $owner->id,
                'workspace_id' => $workspace->id,
                'event_type' => 'new_conversation',
                'channel' => 'email',
            ]);

            // Contacts + visitors + conversations
            $contacts = Contact::factory(5)->create(['workspace_id' => $workspace->id]);

            foreach ($contacts as $contact) {
                $visitor = Visitor::factory()->create([
                    'workspace_id' => $workspace->id,
                    'contact_id' => $contact->id,
                ]);

                FlowRun::factory()->create([
                    'flow_id' => $flow->id,
                    'visitor_id' => $visitor->id,
                    'contact_id' => $contact->id,
                ]);

                $conversation = Conversation::factory()->create([
                    'workspace_id' => $workspace->id,
                    'channel_type' => 'widget',
                    'contact_id' => $contact->id,
                    'visitor_id' => $visitor->id,
                    'assigned_operator_id' => $operators->random()->id,
                    'department_id' => $supportDept->id,
                    'sla_id' => $sla->id,
                ]);

                $contact->tags()->attach($tags->random()->id);
                $conversation->tags()->attach($tags->random()->id);

                // A short back-and-forth thread per conversation
                Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'visitor',
                    'sender_id' => $visitor->id,
                    'body' => 'Hi, I have a question about my order.',
                ]);
                $operatorMessage = Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'operator',
                    'sender_id' => $operators->random()->id,
                    'body' => 'Hi! Happy to help — could you share your order ID?',
                ]);
                Mention::factory()->create([
                    'message_id' => $operatorMessage->id,
                    'mentioned_user_id' => $operators->last()->id,
                ]);
                Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'bot',
                    'sender_id' => null,
                    'body' => 'I found your order — it shipped yesterday and should arrive in 2 days.',
                ]);

                if ($conversation->status === \App\Enums\ConversationStatus::Solved) {
                    CsatRating::factory()->create(['conversation_id' => $conversation->id]);
                }

                $metricData = ConversationMetric::factory()->make(['conversation_id' => $conversation->id])->toArray();
                unset($metricData['conversation_id']);
                ConversationMetric::updateOrCreate(['conversation_id' => $conversation->id], $metricData);

                AiConversationMeta::factory()->create(['conversation_id' => $conversation->id]);
            }

            AdminNote::factory()->create([
                'super_admin_id' => $superAdmin->id,
                'notable_type' => Workspace::class,
                'notable_id' => $workspace->id,
                'note' => 'Onboarded via self-serve signup, no red flags.',
            ]);
        }

        // One impersonation event, for the audit-log example
        ImpersonationLog::factory()->create([
            'super_admin_id' => $superAdmin->id,
            'workspace_id' => Workspace::first()->id,
            'user_id' => User::first()->id,
        ]);

        // Part B — the public marketing website (tidio.com), completely
        // separate from the workspace/tenant data seeded above.
        $this->call(WebsiteContentSeeder::class);
    }
}
