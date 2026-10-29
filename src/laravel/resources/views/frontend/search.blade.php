<x-layouts.app
    :title="__('Search') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Search' => null]">

    {{-- Search form --}}
    <form method="GET" action="{{ route('search') }}" style="margin-bottom:16px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <input type="hidden" name="game" value="{{ $game }}">
        <input type="text" name="q" value="{{ $query }}"
               style="background-color:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 12px; font-size:var(--font-size-sm); width:300px;"
               placeholder="Search player name..." autofocus>
        <button type="submit" class="hlx-btn-green" style="padding:6px 16px;">Search</button>
    </form>

    @if($query)
        <div class="hlx-muted" style="font-size:var(--font-size-sm); margin-bottom:8px;">
            {{ $results->count() }} result(s) for <strong class="hlx-text">"{{ $query }}"</strong>
        </div>

        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
            <table class="hlx-table">
                <thead>
                    <tr>
                        <th>{{ __('Player') }}</th>
                        <th style="width:80px;">{{ __('Skill') }}</th>
                        <th style="width:80px;">{{ __('Kills') }}</th>
                        <th style="width:80px;">{{ __('Deaths') }}</th>
                        <th style="width:55px;">{{ __('K:D') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $player)
                        <tr>
                            <td><x-ui.player-link :player="$player" /></td>
                            <td class="hlx-text">{{ number_format($player->skill) }}</td>
                            <td class="hlx-text">{{ number_format($player->kills) }}</td>
                            <td class="hlx-text">{{ number_format($player->deaths) }}</td>
                            <td class="hlx-text">{{ $player->kd_ratio }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No players found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

</x-layouts.app>
