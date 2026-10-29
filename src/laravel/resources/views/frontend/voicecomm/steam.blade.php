<x-layouts.app
    :title="$server->name . ' — Steam Group'"
    :breadcrumb="['HLStatsX' => route('home'), 'Voice Servers' => route('voicecomm.index'), $server->name => null]"
    activeTab="voicecomm">

    <div style="max-width:700px;">

        @if($error)
            <div style="background:rgba(240,71,71,.12); border:1px solid #f04747; border-radius:var(--border-radius-md); padding:14px 18px; color:#f04747; margin-bottom:20px; font-size:var(--font-size-sm);">
                ⚠️ {{ $error }}
            </div>
        @endif

        @if($group)
        {{-- Group Header --}}
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding:16px; background:var(--bg-widget); border:1px solid var(--border); border-radius:var(--border-radius-md);">
            @if($group['avatar'])
                <img src="{{ $group['avatar'] }}" alt="Group Avatar"
                     style="width:80px; height:80px; border-radius:4px; flex-shrink:0; object-fit:cover;">
            @else
                <div style="width:80px; height:80px; border-radius:4px; background:var(--bg-body); display:flex; align-items:center; justify-content:center; font-size:32px; flex-shrink:0;">🎮</div>
            @endif
            <div style="flex:1; min-width:0;">
                <div style="font-size:20px; font-weight:700; color:var(--text-heading); margin-bottom:4px;">
                    {{ $group['name'] }}
                </div>
                @if($group['headline'])
                    <div style="font-size:var(--font-size-sm); color:var(--text-secondary); margin-bottom:8px;">
                        {{ $group['headline'] }}
                    </div>
                @endif
                @if($group['url'])
                    <a href="https://steamcommunity.com/groups/{{ $group['url'] }}"
                       target="_blank" rel="noopener noreferrer"
                       class="hlx-btn-blue" style="font-size:12px; padding:3px 12px; text-decoration:none;">
                        🔗 View on Steam
                    </a>
                @endif
            </div>
        </div>

        {{-- Stats Bar --}}
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px;">
            @foreach([
                ['label' => 'Members',    'value' => number_format($group['memberCount']),   'color' => 'var(--text-heading)'],
                ['label' => 'Online',     'value' => number_format($group['membersOnline']),  'color' => '#43b581'],
                ['label' => 'In-Game',    'value' => number_format($group['membersInGame']),  'color' => '#7289da'],
                ['label' => 'In Chat',    'value' => number_format($group['membersInChat']),  'color' => '#faa61a'],
            ] as $stat)
            <div style="background:var(--bg-widget); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:12px; text-align:center;">
                <div style="font-size:22px; font-weight:700; color:{{ $stat['color'] }};">{{ $stat['value'] }}</div>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Summary --}}
        @if($group['summary'])
        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:20px;">
            <x-ui.section-title title="About" />
            <div style="padding:14px 18px; color:var(--text-primary); font-size:var(--font-size-sm); line-height:1.6;">
                {!! nl2br(e($group['summary'])) !!}
            </div>
        </div>
        @endif

        @elseif(!$error)
            <div class="hlx-muted" style="text-align:center; padding:40px;">Loading group data…</div>
        @endif

        {{-- Notes --}}
        @if($server->descr)
        <div style="font-size:var(--font-size-sm); color:var(--text-secondary); margin-top:4px;">
            📝 {{ $server->descr }}
        </div>
        @endif

    </div>

</x-layouts.app>
