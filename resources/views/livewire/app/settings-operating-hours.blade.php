<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Operating hours</h2>
        <p style="color:var(--soft);margin-bottom:14px">Visitors are told you're offline outside these hours.</p>

        <div class="card" style="padding:0;max-width:640px">
            <table>
                <tr><th>Day</th><th>Open</th><th>Start</th><th>End</th></tr>
                @foreach ($this->hours as $i => $row)
                    <tr wire:key="oh-{{ $i }}">
                        <td>{{ $row->day_of_week === 0 || $row->day_of_week === 6 ? '🔸 ' : '' }}{{ $days[$row->day_of_week] }}</td>
                        <td>
                            <input type="checkbox" @checked($row->is_active) wire:click="toggleDay({{ $row->day_of_week }})">
                        </td>
                        <td>
                            <input type="time" class="f" style="padding:6px 8px"
                                   value="{{ substr($row->start_time, 0, 5) }}"
                                   wire:change="updateTime({{ $row->day_of_week }}, 'start_time', $event.target.value)"
                                   @disabled(! $row->is_active)>
                        </td>
                        <td>
                            <input type="time" class="f" style="padding:6px 8px"
                                   value="{{ substr($row->end_time, 0, 5) }}"
                                   wire:change="updateTime({{ $row->day_of_week }}, 'end_time', $event.target.value)"
                                   @disabled(! $row->is_active)>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
