<x-layouts.app
    :title="'Discord — ' . $server->name . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Voice Servers' => route('voicecomm.index'), $server->name => null]"
    activeTab="voicecomm">

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
        <x-ui.section-title title="Discord — {{ $server->name }}" />

        @if($error)
            <div style="padding:20px; color:var(--text-secondary); font-size:13px;">
                ⚠️ {{ $error }}
            </div>
        @elseif(!$widget)
            <div style="padding:20px; color:var(--text-secondary); font-size:13px;">
                {{ __('No data available.') }}
            </div>
        @else
            @php
                $statusColors = [
                    'online' => '#43b581',
                    'idle'   => '#faa61a',
                    'dnd'    => '#f04747',
                ];
                $voiceMembers  = [];
                $onlineMembers = [];
                foreach ($widget['members'] ?? [] as $member) {
                    if (isset($member['channel_id'])) {
                        $voiceMembers[$member['channel_id']][] = $member;
                    } else {
                        $onlineMembers[] = $member;
                    }
                }
            @endphp

            <div style="padding:12px 14px; display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                {{-- Voice Channels --}}
                <div>
                    <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                        {{ __('Voice Channels') }}
                    </div>
                    @forelse($widget['channels'] ?? [] as $channel)
                        <div style="margin-bottom:6px;">
                            <div style="font-size:12px; font-weight:600; color:var(--accent-primary); margin-bottom:3px;">
                                🔊 {{ $channel['name'] }}
                            </div>
                            @if(!empty($voiceMembers[$channel['id']]))
                                @foreach($voiceMembers[$channel['id']] as $member)
                                    @php $dot = $statusColors[$member['status'] ?? 'online'] ?? '#43b581'; @endphp
                                    <div style="display:flex; align-items:center; gap:6px; padding:2px 0 2px 12px; font-size:12px; color:var(--text-heading);">
                                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:{{ $dot }}; flex-shrink:0;"></span>
                                        {{ $member['username'] ?? 'Unknown' }}
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @empty
                        <div class="hlx-muted" style="font-size:12px;">{{ __('No voice channels visible.') }}</div>
                    @endforelse
                </div>

                {{-- Online Members --}}
                <div>
                    <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                        {{ __('Online Members') }} ({{ $widget['presence_count'] ?? count($onlineMembers) }})
                    </div>
                    @forelse($onlineMembers as $member)
                        @php $dot = $statusColors[$member['status'] ?? 'online'] ?? '#43b581'; @endphp
                        <div style="display:flex; align-items:center; gap:6px; padding:2px 0; font-size:12px; color:var(--text-heading);">
                            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:{{ $dot }}; flex-shrink:0;"></span>
                            <span>{{ $member['username'] ?? 'Unknown' }}</span>
                            @if(!empty($member['game']['name']))
                                <span class="hlx-muted">&mdash; {{ $member['game']['name'] }}</span>
                            @endif
                        </div>
                    @empty
                        <div class="hlx-muted" style="font-size:12px;">{{ __('No members currently online.') }}</div>
                    @endforelse
                </div>
            </div>

            @if(!empty($widget['instant_invite']))
            <div style="padding:0 14px 12px; font-size:12px;">
                <a href="{{ $widget['instant_invite'] }}" target="_blank" rel="noopener noreferrer" class="hlx-btn-green" style="font-size:12px; padding:4px 14px;">
                    🔗 {{ __('Join Server') }}
                </a>
            </div>
            @endif
        @endif
    </div>

    <div style="text-align:right; font-size:11px; color:var(--text-secondary);">
        <a href="{{ route('voicecomm.index') }}" class="hlx-link">&laquo; {{ __('Back to Voice Servers') }}</a>
    </div>

</x-layouts.app>
