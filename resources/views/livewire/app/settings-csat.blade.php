<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Customer satisfaction</h2>
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('conversations')" class="{{ $tab === 'conversations' ? 'on' : '' }}">Conversations</a>
            <a href="#" wire:click.prevent="setTab('ticketing')" class="{{ $tab === 'ticketing' ? 'on' : '' }}">Ticketing</a>
        </div>

        <form wire:submit="save">
            @if ($tab === 'conversations')
                <div class="steps">
                    <div class="step">
                        <div>
                            <b>Ask for a rating after chats</b>
                            <p>Send a 1 to 5 star request when a conversation is solved</p>
                        </div>
                        <input type="checkbox" wire:model="ask_rating" style="width:20px;height:20px">
                    </div>
                    <div class="step">
                        <div>
                            <b>Ask for a comment</b>
                            <p>Let customers explain their rating</p>
                        </div>
                        <input type="checkbox" wire:model="ask_comment" style="width:20px;height:20px">
                    </div>
                </div>
            @elseif ($tab === 'ticketing')
                <div class="steps">
                    <div class="step">
                        <div>
                            <b>Send rating email after tickets</b>
                            <p>Sent 1 hour after a ticket is solved</p>
                        </div>
                        <input type="checkbox" wire:model="ticket_rating_email" style="width:20px;height:20px">
                    </div>
                </div>
            @endif

            <button type="submit" class="btn pri" style="margin-top:18px">Save changes</button>
        </form>
    </div>
</div>
