<x-layouts.app
    :title="($player->lastName ?? 'Player') . ' — Sessions'"
    :breadcrumb="['HLStatsX' => route('home'), 'Players' => route('players.index', ['game' => $player->game]), $player->lastName => route('players.show', $player->playerId), 'Sessions' => null]"
    :gameNav="$player->game"
    activeTab="players">

<div style="margin-bottom:16px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <x-ui.flag :code="$player->flag ?? ''" size="24" />
    <span style="font-size:18px; font-weight:700; color:var(--text-heading);">{{ $player->lastName }}</span>
    <span class="hlx-muted" style="font-size:13px;">— {{ __('Session History') }}</span>
</div>

{{-- Sub-nav --}}
<div style="margin-bottom:16px; display:flex; gap:6px; flex-wrap:wrap; font-size:12px;">
    <a href="{{ route('players.show', $player->playerId) }}" class="hlx-link">← {{ __('Back to Profile') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.events', $player->playerId) }}" class="hlx-link">{{ __('Events') }}</a>
    <span class="hlx-muted">|</span>
    <span style="color:var(--accent-primary); font-weight:600;">{{ __('Sessions') }}</span>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.awards', $player->playerId) }}" class="hlx-link">{{ __('Awards') }}</a>
    <span class="hlx-muted">|</span>
    <a href="{{ route('players.chat', $player->playerId) }}" class="hlx-link">{{ __('Chat') }}</a>
</div>

<div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <x-ui.section-title :title="__('Session History')" />
    @if($sessions->isEmpty())
        <div class="hlx-muted" style="padding:16px; font-size:13px;">{{ __('No sessions found.') }}</div>
    @else
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:14%">{{ __('Date') }}</th>
                    <th style="text-align:right; width:9%">{{ __('Skill Δ') }}</th>
                    <th style="text-align:right; width:8%">{{ __('Points') }}</th>
                    <th style="text-align:right; width:10%">{{ __('Time') }}</th>
                    <th style="text-align:right; width:7%">{{ __('Kills') }}</th>
                    <th style="text-align:right; width:7%">{{ __('Deaths') }}</th>
                    <th style="text-align:right; width:6%">{{ __('K:D') }}</th>
                    <th style="text-align:right; width:6%">{{ __('Headshots') }}</th>
                    <th style="text-align:right; width:6%">{{ __('HS:K') }}</th>
                    <th style="text-align:right; width:7%">{{ __('Suicides') }}</th>
                    <th style="text-align:right; width:6%">{{ __('TKs') }}</th>
                    <th style="text-align:right; width:8%">{{ __('Kill Streak') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $s)
                    @php
                        $ct   = (int)$s->connection_time;
                        $hrs  = str_pad((int)floor($ct/3600), 2, '0', STR_PAD_LEFT);
                        $mins = str_pad((int)floor(($ct%3600)/60), 2, '0', STR_PAD_LEFT);
                        $secs = str_pad((int)($ct%60), 2, '0', STR_PAD_LEFT);
                        $timeStr = "{$hrs}:{$mins}:{$secs}";
                        $skillChange = (int)$s->skill_change;
                    @endphp
                    <tr>
                        <td class="hlx-muted" style="font-size:11px; white-space:nowrap;">{{ $s->eventTime }}</td>
                        <td style="text-align:right; font-size:12px; font-weight:600; color:{{ $skillChange >= 0 ? '#4ade80' : '#f87171' }};">
                            {{ $skillChange >= 0 ? '+' : '' }}{{ $skillChange }}
                        </td>
                        <td class="hlx-text" style="text-align:right; font-size:12px;">{{ number_format($s->skill) }}</td>
                        <td class="hlx-muted" style="text-align:right; font-size:11px;">{{ $timeStr }}</td>
                        <td class="hlx-text" style="text-align:right; font-size:12px;">{{ number_format($s->kills) }}</td>
                        <td class="hlx-text" style="text-align:right; font-size:12px;">{{ number_format($s->deaths) }}</td>
                        <td class="hlx-muted" style="text-align:right; font-size:11px;">{{ $s->kpd }}</td>
                        <td class="hlx-text" style="text-align:right; font-size:12px;">{{ number_format($s->headshots) }}</td>
                        <td class="hlx-muted" style="text-align:right; font-size:11px;">{{ $s->hpk }}</td>
                        <td class="hlx-text" style="text-align:right; font-size:12px;">{{ number_format($s->suicides) }}</td>
                        <td class="hlx-text" style="text-align:right; font-size:12px;">{{ number_format($s->teamkills) }}</td>
                        <td class="hlx-text" style="text-align:right; font-size:12px;">{{ number_format($s->kill_streak) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:10px 12px;">
            {{ $sessions->links() }}
        </div>
    @endif
</div>

</x-layouts.app>
