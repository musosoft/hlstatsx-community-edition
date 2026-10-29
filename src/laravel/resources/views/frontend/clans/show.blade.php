<x-layouts.app
    :title="($clan->name ?? 'Clan') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Clans' => route('clans.index', ['game' => $clan->game]), $clan->name => null]"
    :gameNav="$clan->game"
    activeTab="clans">

    <x-slot:head>
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $clan->name,
            'alternateName' => $clan->tag,
            'url' => route('clans.show', $clan->clanId),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    </x-slot:head>

<div x-data="{ tab: 'members' }">

{{-- Clan header --}}
<div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:16px; margin-bottom:16px;">
    <h2 style="margin:0 0 4px; color:var(--text-heading);">{{ $clan->name }}</h2>
    <div class="hlx-muted" style="font-size:var(--font-size-sm);">
        {{ __('Tag') }}: <strong class="hlx-text">{{ $clan->tag }}</strong> &nbsp;&bull;&nbsp;
        {{ __('Game') }}: <strong class="hlx-text">{{ $game }}</strong>
    </div>
    @php $totalKillsHeader = $members->sum('kills'); $totalDeathsHeader = $members->sum('deaths'); $totalHSHeader = $members->sum('headshots'); @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(100px, 1fr)); gap:8px; margin-top:12px;">
        @foreach([__('Kills') => number_format($totalKillsHeader), __('Deaths') => number_format($totalDeathsHeader), __('Headshots') => number_format($totalHSHeader)] as $label => $value)
            <div style="background-color:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 8px; text-align:center;">
                <div class="hlx-muted" style="font-size:10px; text-transform:uppercase; margin-bottom:2px;">{{ $label }}</div>
                <div class="hlx-text" style="font-size:14px; font-weight:600; font-family:var(--font-family-mono);">{{ $value }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- Tab bar --}}
<div style="margin-bottom:16px; display:flex; gap:6px; flex-wrap:wrap; font-size:12px; align-items:center;">
    @foreach(['members' => __('Members'), 'weapons' => __('Weapons'), 'maps' => __('Maps'), 'teams' => __('Teams'), 'actions' => __('Actions')] as $key => $label)
        @unless($loop->first)<span class="hlx-muted">|</span>@endunless
        <button @click="tab='{{ $key }}'"
            style="background:none;border:none;padding:0;cursor:pointer;font-size:12px;transition:color .15s;"
            :style="tab==='{{ $key }}' ? 'color:var(--accent-primary);font-weight:600;' : 'color:var(--link);'">
            {{ $label }}
        </button>
    @endforeach
</div>

{{-- ============ MEMBERS ============ --}}
<div x-show="tab==='members'" x-cloak>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <x-ui.section-title :title="__('Members') . ' (' . count($members) . ')'" />
        <table class="hlx-table">
            <thead><tr>
                <th>{{ __('Player') }}</th><th>{{ __('Skill') }}</th><th>{{ __('Kills') }}</th>
                <th>{{ __('Deaths') }}</th><th>{{ __('K:D') }}</th><th>{{ __('HS%') }}</th>
            </tr></thead>
            <tbody>
                @forelse($members as $player)
                <tr>
                    <td><x-ui.player-link :player="$player" /></td>
                    <td class="hlx-text">{{ number_format($player->skill) }}</td>
                    <td class="hlx-text">{{ number_format($player->kills) }}</td>
                    <td class="hlx-text">{{ number_format($player->deaths) }}</td>
                    <td class="hlx-text">{{ $player->deaths > 0 ? number_format($player->kills / $player->deaths, 2) : $player->kills }}</td>
                    <td class="hlx-text">{{ $player->kills > 0 ? number_format($player->headshots / $player->kills * 100, 1) : 0 }}%</td>
                </tr>
                @empty
                    <tr><td colspan="6" class="hlx-muted" style="text-align:center;padding:20px;">{{ __('No members found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ============ WEAPONS ============ --}}
<div x-show="tab==='weapons'" x-cloak>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <x-ui.section-title :title="__('Weapons')" />
        @if($weapons->isEmpty())
            <div class="hlx-muted" style="padding:20px;text-align:center;">{{ __('No data.') }}</div>
        @else
        <table class="hlx-table" style="font-size:12px;">
            <thead><tr>
                <th style="width:40px;"></th>
                <th>{{ __('Weapon') }}</th>
                <th style="text-align:right;">{{ __('Kills') }}</th>
                <th style="text-align:right;width:90px;">{{ __('Kill%') }}</th>
                <th style="text-align:right;">{{ __('Headshots') }}</th>
                <th style="text-align:right;width:60px;">{{ __('Hpk') }}</th>
            </tr></thead>
            <tbody>
                @foreach($weapons as $w)
                <tr>
                    <td style="text-align:center;">
                        <img src="{{ asset('hlstatsimg/games/'.$realgame.'/weapon_'.strtolower($w->weapon).'.png') }}"
                             alt="{{ $w->weapon_name }}" style="height:20px;max-width:60px;object-fit:contain;"
                             onerror="this.onerror=null;this.style.display='none'">
                    </td>
                    <td><a href="{{ route('weapons.show', [$w->weapon, 'game' => $game]) }}" class="hlx-link">{{ $w->weapon_name }}</a></td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($w->kills) }}</td>
                    <td style="text-align:right;">
                        <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                            <div style="width:50px;background:#1a1a2e;border-radius:2px;height:8px;overflow:hidden;">
                                <div style="background:var(--accent-primary);height:100%;width:{{ min(100,$w->kpercent) }}%;"></div>
                            </div>
                            <span class="hlx-muted" style="font-size:10px;width:34px;text-align:right;">{{ $w->kpercent }}%</span>
                        </div>
                    </td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($w->headshots) }}</td>
                    <td class="hlx-muted" style="text-align:right;font-size:11px;">{{ $w->hpk }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- ============ MAPS ============ --}}
<div x-show="tab==='maps'" x-cloak>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <x-ui.section-title :title="__('Maps')" />
        @if($maps->isEmpty())
            <div class="hlx-muted" style="padding:20px;text-align:center;">{{ __('No data.') }}</div>
        @else
        <table class="hlx-table" style="font-size:12px;">
            <thead><tr>
                <th>{{ __('Map') }}</th>
                <th style="text-align:right;">{{ __('Kills') }}</th>
                <th style="text-align:right;width:90px;">{{ __('Kill%') }}</th>
                <th style="text-align:right;">{{ __('Deaths') }}</th>
                <th style="text-align:right;">{{ __('K:D') }}</th>
                <th style="text-align:right;">{{ __('Headshots') }}</th>
                <th style="text-align:right;width:60px;">{{ __('Hpk') }}</th>
            </tr></thead>
            <tbody>
                @foreach($maps as $m)
                <tr>
                    <td><a href="{{ route('maps.show', [$m->map, 'game' => $game]) }}" class="hlx-link">{{ $m->map }}</a></td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($m->kills) }}</td>
                    <td style="text-align:right;">
                        <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                            <div style="width:50px;background:#1a1a2e;border-radius:2px;height:8px;overflow:hidden;">
                                <div style="background:var(--accent-primary);height:100%;width:{{ min(100,$m->kpercent) }}%;"></div>
                            </div>
                            <span class="hlx-muted" style="font-size:10px;">{{ $m->kpercent }}%</span>
                        </div>
                    </td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($m->deaths) }}</td>
                    <td class="hlx-muted" style="text-align:right;">{{ $m->kd }}</td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($m->headshots) }}</td>
                    <td class="hlx-muted" style="text-align:right;font-size:11px;">{{ $m->hpk }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- ============ TEAMS ============ --}}
<div x-show="tab==='teams'" x-cloak>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <x-ui.section-title :title="__('Teams')" />
        @if($teams->isEmpty())
            <div class="hlx-muted" style="padding:20px;text-align:center;">{{ __('No data.') }}</div>
        @else
        <table class="hlx-table" style="font-size:12px;">
            <thead><tr>
                <th>{{ __('Team') }}</th>
                <th style="text-align:right;">{{ __('Joined') }}</th>
                <th style="text-align:right;width:90px;">{{ __('%') }}</th>
            </tr></thead>
            <tbody>
                @foreach($teams as $t)
                <tr>
                    <td class="hlx-text">{{ $t->name ?: '—' }}</td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($t->teamcount) }}</td>
                    <td style="text-align:right;">
                        <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                            <div style="width:60px;background:#1a1a2e;border-radius:2px;height:8px;overflow:hidden;">
                                <div style="background:var(--accent-primary);height:100%;width:{{ min(100,$t->percent) }}%;"></div>
                            </div>
                            <span class="hlx-muted" style="font-size:10px;">{{ $t->percent }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- ============ ACTIONS ============ --}}
<div x-show="tab==='actions'" x-cloak>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <x-ui.section-title :title="__('Actions')" />
        @if(empty($actions))
            <div class="hlx-muted" style="padding:20px;text-align:center;">{{ __('No data.') }}</div>
        @else
        <table class="hlx-table" style="font-size:12px;">
            <thead><tr>
                <th>{{ __('Action') }}</th>
                <th style="text-align:right;">{{ __('Achieved') }}</th>
                <th style="text-align:right;">{{ __('Points Bonus') }}</th>
            </tr></thead>
            <tbody>
                @foreach($actions as $a)
                <tr>
                    <td><a href="{{ route('actions.show', $a->action_id) . '?game=' . $game }}" class="hlx-link">{{ $a->description }}</a></td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($a->obj_count) }}</td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($a->obj_bonus ?? 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

</div>{{-- end x-data --}}
</x-layouts.app>

