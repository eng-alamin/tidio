<?php

namespace App\Console\Commands;

use App\Enums\ConversationChannelType;
use App\Enums\ConversationStatus;
use App\Enums\ConversationType;
use App\Enums\MessageSenderType;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Mention;
use App\Models\Message;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Dev-only helper: seeds at least one demo conversation into every Inbox
 * folder (Live/Tickets/Mentions/Lyro/Spam/Views) so the UI can be tested
 * before the real channel integrations and AI pipeline exist.
 *
 * Usage: php artisan demo:inbox {workspace_id?}
 */
class SeedDemoInboxData extends Command
{
    protected $signature = 'demo:inbox {workspace_id? : Defaults to the first workspace}';

    protected $description = 'Seed demo conversations covering every Inbox folder (Live, Tickets, Mentions, Lyro, Spam, Views)';

    private Workspace $workspace;

    private User $operator;

    private User $secondOperator;

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('This command only runs in the local environment.');

            return self::FAILURE;
        }

        $this->workspace = $this->argument('workspace_id')
            ? Workspace::findOrFail($this->argument('workspace_id'))
            : Workspace::firstOrFail();

        $operators = $this->workspace->users()->wherePivot('status', 'active')->get();

        if ($operators->isEmpty()) {
            $this->error('This workspace has no active operators yet — invite one first.');

            return self::FAILURE;
        }

        $this->operator = $operators->first();
        $this->secondOperator = $operators->skip(1)->first() ?? $this->operator;

        $this->seedLiveConversations();
        $this->seedTickets();
        $this->seedMentions();
        $this->seedLyro();
        $this->seedSpam();
        $this->seedViews();

        $this->info('Done — every Inbox folder now has at least one conversation.');

        return self::SUCCESS;
    }

    /** @return array{0: Contact, 1: Visitor} */
    private function makeVisitor(string $name, ?string $source = null): array
    {
        $contact = Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => $name,
            'source' => $source,
        ]);

        $visitor = Visitor::create([
            'workspace_id' => $this->workspace->id,
            'contact_id' => $contact->id,
            'session_id' => (string) Str::uuid(),
            'is_online' => (bool) random_int(0, 1),
            'current_page' => '/pricing',
            'location' => 'Dhaka, Bangladesh',
            'browser' => 'Chrome',
            'first_seen_at' => now()->subMinutes(random_int(10, 180)),
            'last_seen_at' => now()->subMinutes(random_int(0, 5)),
        ]);

        return [$contact, $visitor];
    }

    private function makeConversation(array $attrs): Conversation
    {
        return Conversation::create(array_merge([
            'workspace_id' => $this->workspace->id,
            'last_message_at' => now(),
        ], $attrs));
    }

    /** @param array<int, array{0: string, 1: string}> $lines */
    private function makeMessages(Conversation $conversation, Visitor $visitor, array $lines): void
    {
        $total = count($lines);

        foreach ($lines as $i => [$from, $body]) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => $from === 'visitor' ? MessageSenderType::Visitor : MessageSenderType::Operator,
                'sender_id' => $from === 'visitor' ? $visitor->id : $this->operator->id,
                'body' => $body,
                'created_at' => now()->subMinutes($total - $i),
            ]);
        }
    }

    private function seedLiveConversations(): void
    {
        $this->info('Seeding Live conversations...');

        [$c, $v] = $this->makeVisitor('Rafiq Ahmed');
        $conv = $this->makeConversation([
            'channel_type' => ConversationChannelType::Widget,
            'contact_id' => $c->id, 'visitor_id' => $v->id,
            'type' => ConversationType::Chat, 'status' => ConversationStatus::Open,
        ]);
        $this->makeMessages($conv, $v, [['visitor', 'Hi, is anyone there?']]);

        [$c, $v] = $this->makeVisitor('Farzana Akter');
        $conv = $this->makeConversation([
            'channel_type' => ConversationChannelType::Widget,
            'contact_id' => $c->id, 'visitor_id' => $v->id,
            'assigned_operator_id' => $this->operator->id,
            'type' => ConversationType::Chat, 'status' => ConversationStatus::Open,
        ]);
        $this->makeMessages($conv, $v, [
            ['visitor', 'I need help changing my plan.'],
            ['operator', 'Sure — which plan would you like to move to?'],
        ]);

        [$c, $v] = $this->makeVisitor('Kamal Hossain');
        $conv = $this->makeConversation([
            'channel_type' => ConversationChannelType::Widget,
            'contact_id' => $c->id, 'visitor_id' => $v->id,
            'assigned_operator_id' => $this->operator->id,
            'type' => ConversationType::Chat, 'status' => ConversationStatus::Solved,
        ]);
        $this->makeMessages($conv, $v, [
            ['visitor', 'How do I reset my password?'],
            ['operator', 'Use the "Forgot password" link on the login page — you\'re all set!'],
        ]);
    }

    private function seedTickets(): void
    {
        $this->info('Seeding Tickets...');

        [$c, $v] = $this->makeVisitor('Shirin Sultana');
        $conv = $this->makeConversation([
            'channel_type' => ConversationChannelType::Email,
            'contact_id' => $c->id, 'visitor_id' => $v->id,
            'type' => ConversationType::Ticket, 'status' => ConversationStatus::Open,
            'subject' => 'Refund request for order #5521',
        ]);
        $this->makeMessages($conv, $v, [['visitor', 'I\'d like a refund for order #5521, it arrived damaged.']]);
    }

    private function seedMentions(): void
    {
        $this->info('Seeding Mentions...');

        [$c, $v] = $this->makeVisitor('Imran Chowdhury');
        $conv = $this->makeConversation([
            'channel_type' => ConversationChannelType::Widget,
            'contact_id' => $c->id, 'visitor_id' => $v->id,
            'assigned_operator_id' => $this->secondOperator->id,
            'type' => ConversationType::Chat, 'status' => ConversationStatus::Open,
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_type' => MessageSenderType::Operator,
            'sender_id' => $this->secondOperator->id,
            'body' => "@{$this->operator->name} can you take a look at this billing question?",
        ]);

        Mention::create([
            'message_id' => $msg->id,
            'mentioned_user_id' => $this->operator->id,
        ]);
    }

    private function seedLyro(): void
    {
        $this->info('Seeding Lyro AI Agent...');

        [$c, $v] = $this->makeVisitor('Mitu Akhter');
        $conv = $this->makeConversation([
            'channel_type' => ConversationChannelType::Widget,
            'contact_id' => $c->id, 'visitor_id' => $v->id,
            'type' => ConversationType::Chat, 'status' => ConversationStatus::Solved,
        ]);
        $this->makeMessages($conv, $v, [
            ['visitor', 'What are your business hours?'],
            ['operator', 'We\'re open 9am to 6pm, Saturday to Thursday.'],
        ]);

        $conv->aiMeta()->create([
            'resolved_by_ai' => true,
            'confidence_score' => 92.50, // 0-100 scale, not a 0-1 fraction
            'handed_off_at' => null,
        ]);
    }

    private function seedSpam(): void
    {
        $this->info('Seeding Spam...');

        [$c, $v] = $this->makeVisitor('Unknown Sender');
        $conv = $this->makeConversation([
            'channel_type' => ConversationChannelType::Email,
            'contact_id' => $c->id, 'visitor_id' => $v->id,
            'type' => ConversationType::Chat, 'status' => ConversationStatus::Spam,
        ]);
        $this->makeMessages($conv, $v, [['visitor', 'CONGRATULATIONS! You have won a prize, click here to claim now!!!']]);
    }

    private function seedViews(): void
    {
        $this->info('Seeding Views (Messenger / Instagram / WhatsApp)...');

        $channels = [
            ['type' => 'messenger', 'enum' => ConversationChannelType::Messenger, 'name' => 'Ayesha Rahman', 'opener' => 'Hi! Do you ship to Sylhet?'],
            ['type' => 'instagram', 'enum' => ConversationChannelType::Instagram, 'name' => 'Tanvir Hossain', 'opener' => 'Saw your story — is the sale still on?'],
            ['type' => 'whatsapp', 'enum' => ConversationChannelType::Whatsapp, 'name' => 'Nusrat Jahan', 'opener' => "My order #4821 hasn't arrived yet."],
        ];

        foreach ($channels as $data) {
            $channel = Channel::firstOrCreate(
                ['workspace_id' => $this->workspace->id, 'type' => $data['type']],
                ['status' => 'connected', 'connected_at' => now(), 'credentials' => ['handle' => 'demo']]
            );

            [$c, $v] = $this->makeVisitor($data['name'], $data['type']);
            $conv = $this->makeConversation([
                'channel_type' => $data['enum'],
                'channel_id' => $channel->id,
                'contact_id' => $c->id, 'visitor_id' => $v->id,
                'assigned_operator_id' => $this->operator->id,
                'type' => ConversationType::Chat, 'status' => ConversationStatus::Open,
            ]);
            $this->makeMessages($conv, $v, [['visitor', $data['opener']]]);
        }
    }
}