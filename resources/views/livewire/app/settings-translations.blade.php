<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Live chat · Translations</h2>

        <div class="tools">
            <select class="f" style="max-width:200px" wire:model.live="locale">
                @foreach ($available_locales as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <form wire:submit="save">
            <div class="card" style="padding:0">
                <table>
                    <tr><th>Text</th><th>Translation</th></tr>
                    <tr>
                        <td>Chat with us</td>
                        <td><input class="f" wire:model="keys.header"></td>
                    </tr>
                    <tr>
                        <td>Type a message…</td>
                        <td><input class="f" wire:model="keys.placeholder"></td>
                    </tr>
                    <tr>
                        <td>Send</td>
                        <td><input class="f" wire:model="keys.send_button"></td>
                    </tr>
                </table>
            </div>
            <br><button type="submit" class="btn pri">Save translations</button>
        </form>
    </div>
</div>
