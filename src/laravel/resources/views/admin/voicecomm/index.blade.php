<x-layouts.admin title="Voice Servers">
    <div style="margin-bottom:12px; text-align:right;">
        <a href="{{ route('admin.voicecomm.create') }}" class="hlx-btn-gold">+ Add Voice Server</a>
    </div>

    @if(session('success'))
        <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:12px; font-size:var(--font-size-sm); color:#4ade80;">
            {{ session('success') }}
        </div>
    @endif

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px;">ID</th>
                    <th style="width:80px;">Type</th>
                    <th>Name</th>
                    <th>Address / Guild ID</th>
                    <th style="width:70px;">UDP Port</th>
                    <th style="width:80px;">Query Port</th>
                    <th>Notes</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servers as $server)
                    @php
                        $typeLabel = match((int)$server->serverType) {
                            0 => 'TeamSpeak',
                            1 => 'Steam Group',
                            2 => 'Discord',
                            default => 'Unknown',
                        };
                        $typeColor = match((int)$server->serverType) {
                            2 => '#5865F2',
                            1 => '#1b2838',
                            default => '#4a9eff',
                        };
                        $typeBg = match((int)$server->serverType) {
                            2 => 'rgba(88,101,242,.15)',
                            1 => 'rgba(23,26,33,.5)',
                            default => 'rgba(74,158,255,.15)',
                        };
                    @endphp
                    <tr>
                        <td class="hlx-muted">{{ $server->serverId }}</td>
                        <td>
                            <span style="font-size:11px; font-weight:600; color:{{ $typeColor }}; background:{{ $typeBg }}; padding:2px 7px; border-radius:3px;">{{ $typeLabel }}</span>
                        </td>
                        <td class="hlx-text" style="font-weight:600;">{{ $server->name }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">{{ $server->addr }}</td>
                        <td class="hlx-muted" style="text-align:center;">
                            @if((int)$server->serverType === 0){{ $server->UDPPort }}@else -@endif
                        </td>
                        <td class="hlx-muted" style="text-align:center;">
                            @if((int)$server->serverType === 0){{ $server->queryPort }}@else -@endif
                        </td>
                        <td class="hlx-muted" style="font-size:11px;">{{ $server->descr }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('admin.voicecomm.edit', $server->serverId) }}" class="hlx-link" style="margin-right:8px;">Edit</a>
                            <form action="{{ route('admin.voicecomm.destroy', $server->serverId) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete {{ addslashes($server->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="hlx-muted" style="text-align:center; padding:20px;">No voice servers configured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$servers" />
</x-layouts.admin>
