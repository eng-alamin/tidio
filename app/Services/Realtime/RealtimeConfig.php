<?php

namespace App\Services\Realtime;

use App\Models\Visitor;

/**
 * Everything about "is real-time (Reverb) switched on, and how do clients connect".
 * Real-time is ON only when BROADCAST_CONNECTION=reverb, the Reverb app key + secret exist,
 * and WIDGET_REALTIME_ENABLED is not false. Otherwise everything quietly stays on polling.
 */
class RealtimeConfig
{
    public function enabled(): bool
    {
        return (bool) config('widget.realtime.enabled', true)
            && config('broadcasting.default') === 'reverb'
            && $this->key() !== ''
            && $this->secret() !== '';
    }

    public function key(): string
    {
        return (string) config('broadcasting.connections.reverb.key', '');
    }

    private function secret(): string
    {
        return (string) config('broadcasting.connections.reverb.secret', '');
    }

    public function workspaceChannel(int $workspaceId): string
    {
        return 'workspace.'.$workspaceId;
    }

    public function visitorChannel(int $visitorId): string
    {
        return 'widget.visitor.'.$visitorId;
    }

    /** Connection details a BROWSER needs (never the secret). */
    public function client(): array
    {
        $scheme = strtolower((string) config('widget.realtime.public_scheme', 'http')) === 'https' ? 'https' : 'http';

        return [
            'key' => $this->key(),
            'host' => (string) config('widget.realtime.public_host', 'localhost'),
            'port' => (int) config('widget.realtime.public_port', 8080),
            'scheme' => $scheme,
        ];
    }

    /** What the chat frame gets in the /init response, or null when real-time is off. */
    public function forVisitor(Visitor $visitor): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        return $this->client() + ['channel' => 'private-'.$this->visitorChannel($visitor->id)];
    }

    /** Origin to whitelist in the widget frame's Content-Security-Policy `connect-src`, or ''. */
    public function connectSrc(): string
    {
        if (! $this->enabled()) {
            return '';
        }

        $c = $this->client();

        return ($c['scheme'] === 'https' ? 'wss' : 'ws').'://'.$c['host'].':'.$c['port'];
    }

    /** Pusher-protocol signature for a private channel: "key:hmac_sha256(socket_id:channel, secret)". */
    public function signature(string $socketId, string $channelName): string
    {
        return $this->key().':'.hash_hmac('sha256', $socketId.':'.$channelName, $this->secret());
    }
}
