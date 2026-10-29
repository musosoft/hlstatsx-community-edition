<x-layouts.app
    :title="($player->lastName ?? 'Player') . ' — Awards'"
    :breadcrumb="['HLStatsX' => route('home'), 'Players' => route('players.index', ['game' => $player->game]), $player->lastName => route('players.show', $player->playerId), 'Awards' => null]"
    :gameNav="$player->game"
    activeTab="players">

<div style="margin-bottom:16px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <x-ui.flag :code="$player->flag ?? ''" size="24" />
    <span style="font-size:18px; font-weight:700; color:var(--text-heading);">{{ $player->lastName }}</span>
    <span class="hlx-muted" style="font-size:13px;">— {{ __('Awards History') }}</span>
</div>

{{-- Sub-nav --}}
<div style="margin-bottom:16px; display:flex; gap:6px; flex-wrap:wrap; font-size:12px;">
    <a href="{{ route('players.show', $player->playerId) }}" class="hlx-link">← {{ __('Back to Profile') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.events', $player->playerId) }}" class="hlx-link">{{ __('Events') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.sessions', $player->playerId) }}" class="hlx-link">{{ __('Sessions') }}</a>
    <span class="hlx-muted">|</span>
    <span style="color:var(--accent-primary); font-weight:600;">{{ __('Awards') }}</span>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.chat', $player->playerId) }}" class="hlx-link">{{ __('Chat') }}</a>
</div>

<div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <x-ui.section-title :title="__('Awards History') . ' (' . $awards->total() . ' ' . ($awards->total() !== 1 ? __('awards') : __('award')) . ')'" />
    @if($awards->isEmpty())
        <div class="hlx-muted" style="padding:16px; font-size:13px;">{{ __('No awards found.') }}</div>
    @else
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:16%">{{ __('Date Last Earned') }}</th>
                    <th style="width:22%">{{ __('Name') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="text-align:right; width:9%">{{ __('Count') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($awards as $a)
                    <tr>
                        <td class="hlx-muted" style="font-size:11px; white-space:nowrap;">{{ $a->awardTime }}</td>
                        <td style="font-size:12px; font-weight:600; color:var(--text-heading);">{{ $a->name }}</td>
                        <td class="hlx-text" style="font-size:12px;">{{ $a->verb }}</td>
                        <td style="text-align:right; font-size:12px; font-weight:700; color:var(--accent-primary);">{{ number_format($a->count) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:10px 12px;">
            {{ $awards->links() }}
        </div>
    @endif
</div>

</x-layouts.app>
