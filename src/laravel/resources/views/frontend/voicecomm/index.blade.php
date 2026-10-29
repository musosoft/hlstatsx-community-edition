<x-layouts.app
    :title="__('Voice Servers') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Voice Servers' => null]"
    activeTab="voicecomm">

    {{-- Discord Servers --}}
    @if($discordServers->isNotEmpty())
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
        <x-ui.section-title title="{{ __('Discord Servers') }}" />
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>{{ __('Server Name') }}</th>
                    <th>{{ __('Notes') }}</th>
                    <th style="width:100px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($discordServers as $server)
                <tr>
                    <td class="hlx-text" style="font-weight:600;">
                        <span style="margin-right:6px;">💬</span>{{ $server->name }}
                    </td>
                    <td class="hlx-muted">{{ $server->descr }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('voicecomm.discord', $server->serverId) }}" class="hlx-btn-green" style="font-size:12px; padding:2px 10px;">
                            {{ __('View') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- TeamSpeak Servers --}}
    @if($tsServers->isNotEmpty())
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
        <x-ui.section-title title="{{ __('TeamSpeak 3 Servers') }}" />
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>{{ __('Server Name') }}</th>
                    <th>{{ __('Address') }}</th>
                    <th>{{ __('Notes') }}</th>
                    <th style="width:100px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tsServers as $server)
                <tr>
                    <td class="hlx-text" style="font-weight:600;">
                        <span style="margin-right:6px;">🔊</span>{{ $server->name }}
                    </td>
                    <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:12px;">{{ $server->addr }}:{{ $server->UDPPort }}</td>
                    <td class="hlx-muted">{{ $server->descr }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('voicecomm.teamspeak', $server->serverId) }}" class="hlx-btn-green" style="font-size:12px; padding:2px 10px;">
                            {{ __('View') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Steam Groups --}}
    @if($steamServers->isNotEmpty())
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
        <x-ui.section-title title="{{ __('Steam Groups') }}" />
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>{{ __('Group Name') }}</th>
                    <th>{{ __('Steam URL') }}</th>
                    <th>{{ __('Notes') }}</th>
                    <th style="width:100px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($steamServers as $server)
                <tr>
                    <td class="hlx-text" style="font-weight:600;">
                        <span style="margin-right:6px;">🎮</span>{{ $server->name }}
                    </td>
                    <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:12px;">steamcommunity.com/groups/{{ $server->addr }}</td>
                    <td class="hlx-muted">{{ $server->descr }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('voicecomm.steam', $server->serverId) }}" class="hlx-btn-green" style="font-size:12px; padding:2px 10px;">
                            {{ __('View') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($discordServers->isEmpty() && $tsServers->isEmpty() && $steamServers->isEmpty())
        <div class="hlx-muted" style="text-align:center; padding:32px;">{{ __('No voice servers configured.') }}</div>
    @endif

</x-layouts.app>
