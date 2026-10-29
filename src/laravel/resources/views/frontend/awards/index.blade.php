<x-layouts.app
    :title="__('Awards') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Awards' => null]"
    :gameNav="$game"
    activeTab="awards">

<div x-data="{ tab: '{{ $tab }}' }">

    {{-- Tab bar --}}
    <div style="margin-bottom:16px; display:flex; gap:6px; flex-wrap:wrap; font-size:12px; align-items:center;">
        @foreach(['daily' => __('Daily Awards'), 'global' => __('Global Awards'), 'ranks' => __('Ranks'), 'ribbons' => __('Ribbons')] as $key => $label)
            @unless($loop->first)<span class="hlx-muted">|</span>@endunless
            <button
                @click="tab = '{{ $key }}'"
                style="background:none; border:none; padding:0; cursor:pointer; font-size:12px; transition:color .15s;"
                :style="tab === '{{ $key }}' ? 'color:var(--accent-primary); font-weight:600;' : 'color:var(--link);'">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ======================== DAILY AWARDS ======================== --}}
    <div x-show="tab === 'daily'" x-cloak>
        @if($dailyAwards->isEmpty())
            <div class="hlx-muted" style="padding:20px; text-align:center;">{{ __('No daily awards configured.') }}</div>
        @else
            <div style="margin-bottom:6px; font-size:11px; color:var(--text-secondary);">{{ __('Daily Awards') }} — {{ now()->locale(app()->getLocale())->translatedFormat('l d F') }}</div>
            <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:10px;">
                @foreach($dailyAwards as $award)
                    @php
                        $img      = asset('hlstatsimg/games/' . $realgame . '/dawards/' . strtolower($award->awardType . '_' . $award->code) . '.png');
                        $fallback = asset('hlstatsimg/games/' . $realgame . '/dawards/w_standard.png');
                        $hasWin   = $award->d_winner_id > 0 && $award->dailyWinner;
                    @endphp
                    <div onclick="window.location='{{ route('awards.detail', [$award->awardId, 'game' => $game]) }}'" style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:12px 8px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:5px; transition:border-color .15s; cursor:pointer;" onmouseover="this.style.borderColor='var(--accent-primary)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div style="font-size:12px; font-weight:700; color:{{ $hasWin ? 'var(--accent-secondary,#facc15)' : 'var(--text-heading)' }};">{{ $award->name }}</div>
                        <img src="{{ $img }}" alt="{{ $award->name }}" style="width:72px;height:72px;object-fit:contain;" onerror="this.onerror=null;this.src='{{ $fallback }}'">
                        @if($hasWin)
                            <div style="font-size:11px;"><x-ui.player-link :player="$award->dailyWinner" /></div>
                        @else
                            <div style="font-size:11px;font-style:italic;color:var(--text-secondary);">{{ __('No Award Winner') }}</div>
                        @endif
                        <div style="font-size:10px;color:var(--text-secondary);">{{ $award->d_winner_count ?? 0 }} {{ $award->verb }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ======================== GLOBAL AWARDS ======================== --}}
    <div x-show="tab === 'global'" x-cloak>
        @if($globalAwards->isEmpty())
            <div class="hlx-muted" style="padding:20px; text-align:center;">{{ __('No global awards configured.') }}</div>
        @else
            <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; margin-bottom:12px;">{{ __('Global Awards') }}</div>
            <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:10px;">
                @foreach($globalAwards as $award)
                    @php
                        $img      = asset('hlstatsimg/games/' . $realgame . '/gawards/' . strtolower($award->awardType . '_' . $award->code) . '.png');
                        $fallback = asset('hlstatsimg/games/' . $realgame . '/dawards/w_standard.png');
                        $hasWin   = ($award->g_winner_id ?? 0) > 0 && $award->globalWinner;
                    @endphp
                    <div onclick="window.location='{{ route('awards.detail', [$award->awardId, 'game' => $game]) }}'" style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:12px 8px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:5px; transition:border-color .15s; cursor:pointer;" onmouseover="this.style.borderColor='var(--accent-primary)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div style="font-size:12px; font-weight:700; color:{{ $hasWin ? 'var(--accent-secondary,#facc15)' : 'var(--text-heading)' }};">{{ $award->name }}</div>
                        <img src="{{ $img }}" alt="{{ $award->name }}" style="width:72px;height:72px;object-fit:contain;" onerror="this.onerror=null;this.src='{{ $fallback }}'">
                        @if($hasWin)
                            <div style="font-size:11px;"><x-ui.player-link :player="$award->globalWinner" /></div>
                        @else
                            <div style="font-size:11px;font-style:italic;color:var(--text-secondary);">{{ __('No Award Winner') }}</div>
                        @endif
                        <div style="font-size:10px;color:var(--text-secondary);">{{ $award->g_winner_count ?? 0 }} {{ $award->verb }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ======================== RANKS ======================== --}}
    <div x-show="tab === 'ranks'" x-cloak>
        @if($ranks->isEmpty())
            <div class="hlx-muted" style="padding:20px; text-align:center;">{{ __('No ranks configured.') }}</div>
        @else
            <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; margin-bottom:12px;">{{ __('Ranks') }}</div>
            <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:10px;">
                @foreach($ranks as $rank)
                    @php
                        $count    = $rankCounts[$rank->rankId] ?? 0;
                        $img      = asset('hlstatsimg/ranks/' . $rank->image . '.png');
                        $fallback = asset('hlstatsimg/ranks/default.png');
                    @endphp
                    <a href="{{ route('awards.rank', [$rank->rankId, 'game' => $game]) }}" style="text-decoration:none;">
                    <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:12px 8px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:4px; transition:border-color .15s;" onmouseover="this.style.borderColor='var(--accent-primary)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div style="font-size:12px; font-weight:700; color:var(--text-heading);">{{ $rank->rankName }}</div>
                        <div style="font-size:10px; color:var(--text-secondary);">({{ number_format($rank->minKills) }}–{{ number_format($rank->maxKills) }} {{ __('kills') }})</div>
                        @if($count > 0)
                            <div style="font-size:10px; color:var(--accent-primary);">{{ __('Achieved by :count players', ['count' => $count]) }}</div>
                        @else
                            <div style="font-size:10px; color:var(--text-secondary);">&nbsp;</div>
                        @endif
                        <img src="{{ $img }}" alt="{{ $rank->rankName }}" style="width:56px;height:56px;object-fit:contain;margin-top:4px;" onerror="this.onerror=null;this.src='{{ $fallback }}'">
                    </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ======================== RIBBONS ======================== --}}
    <div x-show="tab === 'ribbons'" x-cloak>
        @if(empty($ribbons))
            <div class="hlx-muted" style="padding:20px; text-align:center;">{{ __('No ribbons configured.') }}</div>
        @else
            @php
                $ribbonGroups = collect($ribbons)->groupBy('awardCount');
                $classNum     = 0;
            @endphp
            @foreach($ribbonGroups as $awardCount => $group)
                @php $classNum++ @endphp
                <div style="margin-bottom:20px;">
                    <div style="font-size:11px; font-weight:600; color:var(--text-secondary); border-bottom:1px solid var(--border); padding-bottom:5px; margin-bottom:10px;">
                        {{ __('Ribbon Class') }} #{{ $classNum }} ({{ $awardCount }} {{ $awardCount != 1 ? __('awards required') : __('award required') }})
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:10px;">
                        @foreach($group as $ribbon)
                            @php
                                $img      = asset('hlstatsimg/games/' . $realgame . '/ribbons/' . $ribbon->image);
                                $fallback = asset('hlstatsimg/award.png');
                            @endphp
                            <a href="{{ route('awards.ribbon', [$ribbon->ribbonId, 'game' => $game]) }}" style="text-decoration:none;">
                            <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:10px 8px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:5px; transition:border-color .15s;" onmouseover="this.style.borderColor='var(--accent-primary)'" onmouseout="this.style.borderColor='var(--border)'">
                                <div style="font-size:12px; font-weight:700; color:var(--text-heading);">{{ $ribbon->ribbonName }}</div>
                                @if($ribbon->achievedcount > 0)
                                    <div style="font-size:10px; color:var(--accent-primary);">{{ __('Achieved by :count players', ['count' => $ribbon->achievedcount]) }}</div>
                                @else
                                    <div style="font-size:10px; color:var(--text-secondary);">&nbsp;</div>
                                @endif
                                <img src="{{ $img }}" alt="{{ $ribbon->ribbonName }}" style="width:64px;height:64px;object-fit:contain;" onerror="this.onerror=null;this.src='{{ $fallback }}'">
                            </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

</div>

</x-layouts.app>

