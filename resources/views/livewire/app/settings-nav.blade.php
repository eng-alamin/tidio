<nav class="snav" aria-label="Settings">
    <h4>General</h4>
    <a href="{{ route('app.settings.team') }}" class="{{ request()->routeIs('app.settings.team') ? 'on' : '' }}" wire:current.exact="on">Team</a>
    <a href="{{ route('app.settings.macros') }}" class="{{ request()->routeIs('app.settings.macros') ? 'on' : '' }}" wire:current.exact="on">Macros</a>
    <a href="{{ route('app.settings.workflows') }}" class="{{ request()->routeIs('app.settings.workflows') ? 'on' : '' }}" wire:current.exact="on">Workflows</a>
    <a href="{{ route('app.settings.tags') }}" class="{{ request()->routeIs('app.settings.tags') ? 'on' : '' }}" wire:current.exact="on">Tags</a>
    <a href="{{ route('app.settings.sla') }}" class="{{ request()->routeIs('app.settings.sla') ? 'on' : '' }}" wire:current.exact="on">SLA</a>
    <a href="{{ route('app.settings.csat') }}" class="{{ request()->routeIs('app.settings.csat') ? 'on' : '' }}" wire:current.exact="on">Customer satisfaction</a>
    <a href="{{ route('app.settings.fields') }}" class="{{ request()->routeIs('app.settings.fields') ? 'on' : '' }}" wire:current.exact="on">Fields</a>
    <a href="{{ route('app.settings.tracking') }}" class="{{ request()->routeIs('app.settings.tracking') ? 'on' : '' }}" wire:current.exact="on">Tracking</a>
    <a href="{{ route('app.settings.developer') }}" class="{{ request()->routeIs('app.settings.developer') ? 'on' : '' }}" wire:current.exact="on">Developer</a>
    <h4>Channels</h4>
    <a href="{{ route('app.settings.appearance') }}" class="{{ request()->routeIs('app.settings.appearance') ? 'on' : '' }}" wire:current.exact="on">Live chat appearance</a>
    <a href="{{ route('app.settings.chat-page') }}" class="{{ request()->routeIs('app.settings.chat-page') ? 'on' : '' }}" wire:current.exact="on">Chat page</a>
    <a href="{{ route('app.settings.translations') }}" class="{{ request()->routeIs('app.settings.translations') ? 'on' : '' }}" wire:current.exact="on">Translations</a>
    <a href="{{ route('app.settings.installation') }}" class="{{ request()->routeIs('app.settings.installation') ? 'on' : '' }}" wire:current.exact="on">Installation</a>
    <a href="{{ route('app.settings.email') }}" class="{{ request()->routeIs('app.settings.email') ? 'on' : '' }}" wire:current.exact="on">Email</a>
    <a href="{{ route('app.settings.social', 'messenger') }}" class="{{ request()->route('type') === 'messenger' ? 'on' : '' }}" wire:current.exact="on">Messenger</a>
    <a href="{{ route('app.settings.social', 'instagram') }}" class="{{ request()->route('type') === 'instagram' ? 'on' : '' }}" wire:current.exact="on">Instagram</a>
    <a href="{{ route('app.settings.social', 'whatsapp') }}" class="{{ request()->route('type') === 'whatsapp' ? 'on' : '' }}" wire:current.exact="on">WhatsApp</a>
    <h4>Project</h4>
    <a href="{{ route('app.settings.billing') }}" class="{{ request()->routeIs('app.settings.billing') ? 'on' : '' }}" wire:current.exact="on">Billing</a>
    <a href="{{ route('app.settings.usage') }}" class="{{ request()->routeIs('app.settings.usage') ? 'on' : '' }}" wire:current.exact="on">Usage</a>
    <a href="{{ route('app.settings.preferences') }}" class="{{ request()->routeIs('app.settings.preferences') ? 'on' : '' }}" wire:current.exact="on">Preferences</a>
    <h4>Personal</h4>
    <a href="{{ route('app.settings.account') }}" class="{{ request()->routeIs('app.settings.account') ? 'on' : '' }}" wire:current.exact="on">Account</a>
    <a href="{{ route('app.settings.operating-hours') }}" class="{{ request()->routeIs('app.settings.operating-hours') ? 'on' : '' }}" wire:current.exact="on">Operating hours</a>
    <a href="{{ route('app.settings.notifications') }}" class="{{ request()->routeIs('app.settings.notifications') ? 'on' : '' }}" wire:current.exact="on">Notifications</a>
    <a href="{{ route('app.settings.download-app') }}" class="{{ request()->routeIs('app.settings.download-app') ? 'on' : '' }}" wire:current.exact="on">Download app</a>
</nav>