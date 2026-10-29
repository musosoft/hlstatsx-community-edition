<x-layouts.app
    :title="($player->lastName ?? 'Player') . ' — Events'"
    :breadcrumb="['HLStatsX' => route('home'), 'Players' => route('players.index', ['game' => $player->game]), $player->lastName => route('players.show', $player->playerId), 'Events' => null]"
    :gameNav="$player->game"
    activeTab="players">

<div style="margin-bottom:16px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <x-ui.flag :code="$player->flag ?? ''" size="24" />
    <span style="font-size:18px; font-weight:700; color:var(--text-heading);">{{ $player->lastName }}</span>
    <span class="hlx-muted" style="font-size:13px;">— {{ __('Event History') }}</span>
</div>

{{-- Sub-nav --}}
<div style="margin-bottom:16px; display:flex; gap:6px; flex-wrap:wrap; font-size:12px;">
    <a href="{{ route('players.show', $player->playerId) }}" class="hlx-link">← {{ __('Back to Profile') }}</a>
    <span class="hlx-muted">|</span>
    <span style="color:var(--accent-primary); font-weight:600;">{{ __('Events') }}</span>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.sessions', $player->playerId) }}" class="hlx-link">{{ __('Sessions') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.awards', $player->playerId) }}" class="hlx-link">{{ __('Awards') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.chat', $player->playerId) }}" class="hlx-link">{{ __('Chat') }}</a>
</div>

<div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <x-ui.section-title :title="__('Event History')" />
    @if($events->isEmpty())
        <div class="hlx-muted" style="padding:16px; font-size:13px;">{{ __('No events found.') }}</div>
    @else
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:16%">{{ __('Date') }}</th>
                    <th style="width:10%; text-align:center;">{{ __('Type') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="width:22%">{{ __('Server') }}</th>
                    <th style="width:12%">{{ __('Map') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $ev)
                    <tr>
                        <td class="hlx-muted" style="font-size:11px; white-space:nowrap;">{{ $ev->eventTime }}</td>
                        <td style="text-align:center;">
                            @php
                                $typeColor = match($ev->eventType) {
                                    'Kill'       => '#4ade80',
                                    'Death'      => '#f87171',
                                    'Connect'    => '#60a5fa',
                                    'Disconnect' => '#94a3b8',
                                    default      => 'var(--text-secondary)',
                                };
                            @endphp
                            <span style="font-size:10px; font-weight:600; color:{{ $typeColor }}; padding:1px 5px; border:1px solid {{ $typeColor }}; border-radius:3px; white-space:nowrap;">
                                {{ $ev->eventType }}
                            </span>
                        </td>
                        <td class="hlx-text" style="font-size:12px;">{{ $ev->eventDesc }}</td>
                        <td class="hlx-muted" style="font-size:11px; white-space:nowrap;">
                            <x-ui.game-logo :game="$realgame" :size="14" />
                            {{ $ev->serverName }}
                        </td>
                        <td class="hlx-muted" style="font-size:11px;">{{ $ev->map }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:10px 12px;">
            {{ $events->links() }}
        </div>
    @endif
</div>

</x-layouts.app>
