<x-layouts.admin title="Edit Voice Server — {{ $server->name }}">
    <form method="POST" action="{{ route('admin.voicecomm.update', $server->serverId) }}" style="max-width:520px;"
          x-data="{ type: '{{ old('serverType', (string)$server->serverType) }}' }">
        @csrf
        @method('PUT')

        {{-- Type --}}
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Server Type</label>
            <select name="serverType" x-model="type"
                    style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                <option value="0" @selected((int)old('serverType', $server->serverType) === 0)>TeamSpeak 3</option>
                <option value="1" @selected((int)old('serverType', $server->serverType) === 1)>Steam Group</option>
                <option value="2" @selected((int)old('serverType', $server->serverType) === 2)>Discord</option>
            </select>
            @error('serverType') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        {{-- Name --}}
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Server Name</label>
            <input type="text" name="name" value="{{ old('name', $server->name) }}"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            @error('name') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        {{-- Address / Guild ID --}}
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">
                <span x-show="type === '0'">Server IP / Hostname</span>
                <span x-show="type === '1'">Steam Group URL</span>
                <span x-show="type === '2'">Discord Guild ID</span>
            </label>
            <input type="text" name="addr" value="{{ old('addr', $server->addr) }}"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            <div x-show="type === '2'" style="font-size:11px; color:var(--text-secondary); margin-top:4px;">
                Enable the server widget in Discord → Server Settings → Widget, and paste the Server ID here.
            </div>
            <div x-show="type === '1'" style="font-size:11px; color:var(--text-secondary); margin-top:4px;">
                Enter the Steam Group custom URL (the part after steamcommunity.com/groups/).
            </div>
            @error('addr') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        {{-- UDP Port + Query Port (TeamSpeak only) --}}
        <div x-show="type === '0'" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
            <div>
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">UDP Port (Voice)</label>
                <input type="number" name="UDPPort" value="{{ old('UDPPort', $server->UDPPort) }}" min="1" max="65535"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                @error('UDPPort') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Query Port (ServerQuery TCP)</label>
                <input type="number" name="queryPort" value="{{ old('queryPort', $server->queryPort) }}" min="1" max="65535"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                @error('queryPort') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- Password (TeamSpeak) / Invite Code (Discord) --}}
        <div x-show="type === '0' || type === '2'" style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">
                <span x-show="type === '2'">Invite Code <span style="color:var(--text-secondary); font-weight:400;">(e.g. <code>rmg</code> — for stats fallback when widget is off)</span></span>
                <span x-show="type !== '2'">Server Password (optional)</span>
            </label>
            <input type="text" name="password" value="{{ old('password', $server->password) }}"
                   :placeholder="type === '2' ? 'e.g. rmg' : ''"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            @error('password') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        {{-- Description --}}
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Notes / Description (optional)</label>
            <input type="text" name="descr" value="{{ old('descr', $server->descr) }}" maxlength="255"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            @error('descr') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        <div style="display:flex; gap:8px; margin-top:8px;">
            <button type="submit" class="hlx-btn-gold">Save Changes</button>
            <a href="{{ route('admin.voicecomm.index') }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
