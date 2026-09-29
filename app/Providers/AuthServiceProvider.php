<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Coupon;
use App\Models\Macro;
use App\Models\Message;
use App\Models\Webhook;
use App\Models\Workspace;
use App\Policies\ContactPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CouponPolicy;
use App\Policies\MacroPolicy;
use App\Policies\MessagePolicy;
use App\Policies\WebhookPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

// Laravel 11+ note: if your app was created with `laravel new` on 11+, this
// provider isn't scaffolded by default and policies auto-discover by naming
// convention (App\Models\Foo -> App\Policies\FooPolicy) with NO registration
// needed at all. This file is only required if you're on Laravel 10 or
// earlier, or if you want explicit control instead of relying on discovery.
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Workspace::class => WorkspacePolicy::class,
        Conversation::class => ConversationPolicy::class,
        Message::class => MessagePolicy::class,
        Contact::class => ContactPolicy::class,
        Macro::class => MacroPolicy::class,
        Webhook::class => WebhookPolicy::class,
        Coupon::class => CouponPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
