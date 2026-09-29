<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Notifications</h2>

        <div class="card" style="padding:0;max-width:640px">
            <table>
                <tr>
                    <th>Event</th>
                    @foreach (\App\Enums\NotificationChannel::cases() as $channel)
                        <th style="text-align:center">{{ ucfirst($channel->value) }}</th>
                    @endforeach
                </tr>
                @foreach (\App\Livewire\App\SettingsNotifications::EVENTS as $event => $label)
                    <tr wire:key="ev-{{ $event }}">
                        <td>{{ $label }}</td>
                        @foreach (\App\Enums\NotificationChannel::cases() as $channel)
                            <td style="text-align:center">
                                <input type="checkbox"
                                       @checked($this->prefs[$event][$channel->value])
                                       wire:click="toggle('{{ $event }}', '{{ $channel->value }}')">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
