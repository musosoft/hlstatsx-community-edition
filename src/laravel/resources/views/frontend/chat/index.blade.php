<x-layouts.app
    :title="__('Chat Log') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Chat' => null]"
    :gameNav="$game"
    activeTab="chat">

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:140px;">{{ __('Date') }}</th>
                    <th style="width:200px;">{{ __('Player') }}</th>
                    <th>{{ __('Message') }}</th>
                    <th style="width:80px;">{{ __('Type') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                    <tr>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm); white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($msg->eventTime)->format('Y-m-d H:i:s') }}
                        </td>
                        <td>
                            @if($msg->player)
                                <x-ui.player-link :player="$msg->player" />
                            @else
                                <span class="hlx-muted">Unknown</span>
                            @endif
                        </td>
                        <td class="hlx-text" style="word-break:break-word; max-width:600px;">
                            {{ $msg->message }}
                        </td>
                        <td class="hlx-muted" style="font-size:var(--font-size-sm);">
                            {{ $msg->messageMode ? 'Team' : 'All' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="hlx-muted" style="text-align:center; padding:20px;">No chat messages found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$messages" />

</x-layouts.app>
