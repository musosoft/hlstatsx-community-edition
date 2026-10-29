<x-layouts.admin title="Server Config">
    @if(session('success'))<div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>@endif

    {{-- Server filter --}}
    <div style="margin-bottom:16px;">
        <form method="GET" action="{{ route('admin.server-config.index') }}" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <label class="hlx-muted" style="font-size:var(--font-size-sm);">Server:</label>
            <select name="server_id" onchange="this.form.submit()"
                    style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:5px 10px; font-size:var(--font-size-sm);">
                @foreach($servers as $srv)
                    <option value="{{ $srv->serverId }}" {{ $srv->serverId == $serverId ? 'selected' : '' }}>{{ $srv->name }} ({{ $srv->address }}:{{ $srv->port }})</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($serverId)
    {{-- Add new param --}}
    <form method="POST" action="{{ route('admin.server-config.store') }}" style="margin-bottom:20px;">
        @csrf
        <input type="hidden" name="server_id" value="{{ $serverId }}">
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end;">
            <div>
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Parameter</label>
                <input type="text" name="parameter" required maxlength="50" placeholder="e.g. bot_quota"
                       style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm); width:200px;">
            </div>
            <div>
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Value</label>
                <input type="text" name="value" maxlength="128"
                       style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm); width:200px;">
            </div>
            <button type="submit" class="hlx-btn-gold">Add / Update</button>
        </div>
    </form>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Value</th>
                    <th style="text-align:right;">
                        <form method="POST" action="{{ route('admin.server-config.populate-defaults') }}" style="display:inline;"
                              onsubmit="return confirm('Peupler depuis les défauts du jeu ?\n\n✔ Seuls les paramètres MANQUANTS seront ajoutés.\n✘ Vos réglages existants ne seront PAS modifiés ni supprimés.')">
                            @csrf
                            <input type="hidden" name="server_id" value="{{ $serverId }}">
                            <button type="submit" style="background:none; border:none; color:var(--hlx-gold,#c9a84c); cursor:pointer; font-size:var(--font-size-sm); padding:0; white-space:nowrap;"
                                    title="Importe uniquement les paramètres manquants — vos réglages existants ne sont pas modifiés">
                                &#8659; Peupler depuis les défauts du jeu
                            </button>
                        </form>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($configs as $cfg)
                    <tr>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $cfg->parameter }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.server-config.update', $cfg->serverConfigId) }}" style="display:flex; gap:6px; align-items:center;">
                                @csrf @method('PUT')
                                <input type="text" name="value" value="{{ $cfg->value }}" maxlength="128"
                                       style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:4px 8px; font-size:var(--font-size-sm); width:180px;">
                                <button type="submit" class="hlx-btn-gold" style="padding:4px 10px; font-size:12px;">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.server-config.destroy', $cfg->serverConfigId) }}" onsubmit="return confirm('Delete this config entry?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="server_id" value="{{ $serverId }}">
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="hlx-muted" style="text-align:center; padding:20px;">No config entries for this server.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</x-layouts.admin>
