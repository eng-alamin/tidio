<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Live chat · Chat page</h2>

        <form wire:submit="save" class="card" style="max-width:560px">
            <p style="color:var(--soft);margin-top:0">A standalone page customers can open to chat with you.</p>

            <div class="step">
                <div>
                    <b>Enable chat page</b>
                    <p>Share the link anywhere</p>
                </div>
                <input type="checkbox" wire:model="enabled" style="width:20px;height:20px">
            </div>

            <label>Page link</label>
            <input class="f" value="{{ $page_link }}" readonly>

            <label>Page title</label>
            <input class="f" wire:model="page_title">
            @error('page_title') <small style="color:var(--bad)">{{ $message }}</small> @enderror

            <br><button type="submit" class="btn pri" style="margin-top:18px">Save</button>
        </form>
    </div>
</div>
