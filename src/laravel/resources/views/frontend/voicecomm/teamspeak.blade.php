<x-layouts.app
    :title="'TeamSpeak 3 — ' . $server->name . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Voice Servers' => route('voicecomm.index'), $server->name => null]"
    activeTab="voicecomm">

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
        <x-ui.section-title title="TeamSpeak 3 — {{ $server->name }}" />

        @if($error)
            <div style="padding:20px; color:var(--text-secondary); font-size:13px;">
                ⚠️ {{ $error }}
            </div>
        @elseif(!$data)
            <div style="padding:20px; color:var(--text-secondary); font-size:13px;">
                {{ __('No data available.') }}
            </div>
        @else
            @php
                $info             = $data['info'];
                $channels         = $data['channels'];
                $clientsByChannel = $data['clientsByChannel'];
                $allClients       = $data['clients'];
                $totalSlots       = (int)($info['virtualserver_maxclients'] ?? 0);
                $usedSlots        = (int)($info['virtualserver_clientsonline'] ?? count($allClients));

                function tsClientIcon(array $c): string
                {
                    if (($c['client_away'] ?? 0) == 1)                     return '😴';
                    if (($c['client_flag_talking'] ?? 0) == 1)             return '🔊';
                    if (($c['client_output_hardware'] ?? 1) == 0)          return '🔇';
                    if (($c['client_output_muted'] ?? 0) == 1)             return '🔇';
                    if (($c['client_input_hardware'] ?? 1) == 0)           return '🎤';
                    if (($c['client_input_muted'] ?? 0) == 1)              return '🎤';
                    return '🎧';
                }
            @endphp

            {{-- Server summary --}}
            <div style="padding:10px 14px; border-bottom:1px solid var(--border); font-size:12px; color:var(--text-secondary);">
                {{ __('Slots:') }} <strong style="color:var(--text-heading);">{{ $usedSlots }} / {{ $totalSlots }}</strong>
                &nbsp;&nbsp;
                {{ __('Platform:') }} <strong style="color:var(--text-heading);">{{ $info['virtualserver_platform'] ?? '-' }}</strong>
                &nbsp;&nbsp;
                {{ __('Version:') }} <strong style="color:var(--text-heading);">{{ $info['virtualserver_version'] ?? '-' }}</strong>
            </div>

            <div style="padding:12px 14px;">
                @forelse($channels as $channel)
                    @php
                        $cid      = (int)($channel['cid'] ?? 0);
                        $name     = $channel['channel_name'] ?? 'Channel';
                        $members  = $clientsByChannel[$cid] ?? [];
                        $maxSlots = (int)($channel['channel_maxclients'] ?? -1);
                        $full     = $maxSlots > 0 && count($members) >= $maxSlots;
                    @endphp
                    <div style="margin-bottom:8px;">
                        <div style="font-size:12px; font-weight:600; color:{{ $full ? '#f87171' : 'var(--accent-primary)' }}; margin-bottom:3px;">
                            🔊 {{ $name }}
                            @if($maxSlots > 0)
                                <span class="hlx-muted">({{ count($members) }}/{{ $maxSlots }})</span>
                            @endif
                        </div>
                        @foreach($members as $client)
                            <div style="display:flex; align-items:center; gap:6px; padding:2px 0 2px 14px; font-size:12px; color:var(--text-heading);">
                                <span>{{ tsClientIcon($client) }}</span>
                                <span>{{ $client['client_nickname'] ?? 'Unknown' }}</span>
                                @if(!empty($client['client_country']))
                                    <x-ui.flag :code="strtolower($client['client_country'])" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="hlx-muted" style="font-size:12px;">{{ __('No channels available.') }}</div>
                @endforelse
            </div>

            {{-- Connect button --}}
            <div style="padding:0 14px 12px;">
                <a href="ts3server://{{ $server->addr }}?port={{ $server->UDPPort }}" class="hlx-btn-green" style="font-size:12px; padding:4px 14px;">
                    🔗 {{ __('Connect') }}
                </a>
            </div>
        @endif
    </div>

    <div style="text-align:right; font-size:11px; color:var(--text-secondary);">
        <a href="{{ route('voicecomm.index') }}" class="hlx-link">&laquo; {{ __('Back to Voice Servers') }}</a>
    </div>

</x-layouts.app>
