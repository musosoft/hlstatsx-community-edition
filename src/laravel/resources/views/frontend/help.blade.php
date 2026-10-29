<x-layouts.app
    :title="__('Help') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), __('Help') => null]">

<div style="max-width:760px; margin:0 auto; line-height:1.7;">

<x-ui.section-title :title="__('About HLStatsX')" />
<div style="margin-bottom:24px;">
    <p>{{ __('HLStatsX is a real-time stats tracking system for Half-Life and Source engine games. All statistics are generated in real time from game server logs.') }}</p>
</div>

<x-ui.section-title :title="__('Navigation')" />
<div style="margin-bottom:24px;">
    <table class="hlx-table" style="font-size:12px;">
        <thead><tr>
            <th>{{ __('Section') }}</th>
            <th>{{ __('Description') }}</th>
        </tr></thead>
        <tbody>
            <tr><td><a href="{{ route('home') }}" class="hlx-link">{{ __('Home') }}</a></td><td>{{ __('Overview and general statistics') }}</td></tr>
            <tr><td><a href="{{ route('players.index') }}" class="hlx-link">{{ __('Players') }}</a></td><td>{{ __('Ranked player list with kills, deaths, skill rating') }}</td></tr>
            <tr><td><a href="{{ route('clans.index') }}" class="hlx-link">{{ __('Clans') }}</a></td><td>{{ __('Clan rankings and member stats') }}</td></tr>
            <tr><td><a href="{{ route('maps.index') }}" class="hlx-link">{{ __('Maps') }}</a></td><td>{{ __('Map popularity and kill statistics') }}</td></tr>
            <tr><td><a href="{{ route('weapons.index') }}" class="hlx-link">{{ __('Weapons') }}</a></td><td>{{ __('Weapon usage and efficiency') }}</td></tr>
            <tr><td><a href="{{ route('awards.index') }}" class="hlx-link">{{ __('Awards') }}</a></td><td>{{ __('Daily/global awards, ranks and ribbons') }}</td></tr>
            <tr><td><a href="{{ route('servers.index') }}" class="hlx-link">{{ __('Servers') }}</a></td><td>{{ __('Active server status and info') }}</td></tr>
            <tr><td><a href="{{ route('countries.index') }}" class="hlx-link">{{ __('Countries') }}</a></td><td>{{ __('Player statistics grouped by country') }}</td></tr>
            <tr><td><a href="{{ route('chat.index') }}" class="hlx-link">{{ __('Chat') }}</a></td><td>{{ __('In-game chat log') }}</td></tr>
            <tr><td><a href="{{ route('bans.index') }}" class="hlx-link">{{ __('Bans') }}</a></td><td>{{ __('Player ban list') }}</td></tr>
        </tbody>
    </table>
</div>

<x-ui.section-title :title="__('Skill Rating')" />
<div style="margin-bottom:24px;">
    <p>{{ __('The skill rating is a dynamic score that increases when you get kills and decreases when you die. Kills against stronger opponents give more points; kills against weaker opponents give fewer points.') }}</p>
</div>

<x-ui.section-title :title="__('In-game Commands')" />
<div style="margin-bottom:24px;">
    <p>{{ __('You can type the following commands in the game chat (prefix with ! or say):') }}</p>
    <table class="hlx-table" style="font-size:12px;">
        <thead><tr>
            <th>{{ __('Command') }}</th>
            <th>{{ __('Description') }}</th>
        </tr></thead>
        <tbody>
            <tr><td><code>!statsme</code></td><td>{{ __('Show your own stats in chat') }}</td></tr>
            <tr><td><code>!stats</code></td><td>{{ __('Show top 5 players') }}</td></tr>
            <tr><td><code>!rank</code></td><td>{{ __('Show your current rank') }}</td></tr>
            <tr><td><code>!session</code></td><td>{{ __('Show your current session stats') }}</td></tr>
            <tr><td><code>!top10</code></td><td>{{ __('Show top 10 players') }}</td></tr>
        </tbody>
    </table>
</div>

</div>

</x-layouts.app>
