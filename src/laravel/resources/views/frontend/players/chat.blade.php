<x-layouts.app
    :title="($player->lastName ?? 'Player') . ' — Chat'"
    :breadcrumb="['HLStatsX' => route('home'), 'Players' => route('players.index', ['game' => $player->game]), $player->lastName => route('players.show', $player->playerId), 'Chat' => null]"
    :gameNav="$player->game"
    activeTab="players">

<div style="margin-bottom:16px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <x-ui.flag :code="$player->flag ?? ''" size="24" />
    <span style="font-size:18px; font-weight:700; color:var(--text-heading);">{{ $player->lastName }}</span>
    <span class="hlx-muted" style="font-size:13px;">— {{ __('Chat History') }}</span>
</div>

{{-- Sub-nav --}}
<div style="margin-bottom:16px; display:flex; gap:6px; flex-wrap:wrap; font-size:12px;">
    <a href="{{ route('players.show', $player->playerId) }}" class="hlx-link">← {{ __('Back to Profile') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.events', $player->playerId) }}" class="hlx-link">{{ __('Events') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.sessions', $player->playerId) }}" class="hlx-link">{{ __('Sessions') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.awards', $player->playerId) }}" class="hlx-link">{{ __('Awards') }}</a>
    <span class="hlx-muted">|</span>
    <span style="color:var(--accent-primary); font-weight:600;">{{ __('Chat') }}</span>
</div>

{{-- Filter form --}}
<form method="GET" action="{{ route('players.chat', $player->playerId) }}" style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
    <input type="text" name="filter" value="{{ $filter ?? '' }}"
        placeholder="{{ __('Filter messages…') }}"
        style="padding:5px 10px; font-size:12px; background:var(--bg-surface-alt); border:1px solid var(--border); color:var(--text-primary); border-radius:4px; min-width:220px;">
    <button type="submit" style="padding:5px 14px; font-size:12px; background:var(--accent-primary); color:var(--bg-body); border:none; border-radius:4px; cursor:pointer; font-weight:600;">
        {{ __('Filter') }}
    </button>
    @if($filter)
        <a href="{{ route('players.chat', $player->playerId) }}" class="hlx-link" style="font-size:12px;">{{ __('✕ Clear') }}</a>
    @endif
</form>

<div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <x-ui.section-title :title="__('Chat History') . ' (' . $chat->total() . ' ' . ($chat->total() !== 1 ? __('messages') : __('message')) . ')'" />
    @if($chat->isEmpty())
        <div class="hlx-muted" style="padding:16px; font-size:13px;">{{ __('No chat messages found.') }}</div>
    @else
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:14%">{{ __('Date') }}</th>
                    <th>{{ __('Message') }}</th>
                    <th style="width:24%">{{ __('Server') }}</th>
                    <th style="width:12%">{{ __('Map') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chat as $msg)
                    @php
                        $isTeam  = str_starts_with($msg->message, '(Team)');
                        $isSquad = str_starts_with($msg->message, '(Squad)');
                    @endphp
                    <tr>
                        <td class="hlx-muted" style="font-size:11px; white-space:nowrap;">{{ $msg->eventTime }}</td>
                        <td style="font-size:12px; color:{{ $isTeam ? '#60a5fa' : ($isSquad ? '#a78bfa' : 'var(--text-primary)') }};">
                            {{ $msg->message }}
                        </td>
                        <td class="hlx-muted" style="font-size:11px; white-space:nowrap;">
                            <x-ui.game-logo :game="$realgame" :size="14" />
                            {{ $msg->serverName }}
                        </td>
                        <td class="hlx-muted" style="font-size:11px;">{{ $msg->map }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:10px 12px;">
            {{ $chat->withQueryString()->links() }}
        </div>
    @endif
</div>

</x-layouts.app>
