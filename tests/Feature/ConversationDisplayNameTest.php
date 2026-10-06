<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_falls_back_from_contact_name_to_email_to_visitor_to_id(): void
    {
        $visitor = Visitor::factory()->create();

        $anonymous = Conversation::factory()->create([
            'workspace_id' => $visitor->workspace_id,
            'visitor_id' => $visitor->id,
            'contact_id' => null,
        ]);
        $this->assertSame('Visitor #'.$visitor->id, $anonymous->displayName());

        $emailOnly = Contact::factory()->create(['workspace_id' => $visitor->workspace_id, 'name' => null, 'email' => 'ana@example.com']);
        $anonymous->update(['contact_id' => $emailOnly->id]);
        $this->assertSame('ana@example.com', $anonymous->fresh()->displayName());

        $emailOnly->update(['name' => 'Ana']);
        $this->assertSame('Ana', $anonymous->fresh()->displayName());

        $noOne = Conversation::factory()->create(['workspace_id' => $visitor->workspace_id, 'visitor_id' => null, 'contact_id' => null]);
        $this->assertSame('#'.$noOne->id, $noOne->displayName());
    }
}
