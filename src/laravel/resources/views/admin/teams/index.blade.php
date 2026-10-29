<x-layouts.admin title="Teams">
    @if(session('success'))
        <div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>
    @endif
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
        <x-admin.game-filter :games="$games" :current="$game" route="admin.teams.index" />
        <a href="{{ route('admin.teams.create', ['game' => $game]) }}" class="hlx-btn-gold">+ Add Team</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead><tr><th>Code</th><th>Name</th><th>Colors</th><th>Hidden</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($teams as $team)
                    <tr>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $team->code }}</td>
                        <td class="hlx-text">{{ $team->name }}</td>
                        <td>
                            @if($team->playerlist_bgcolor)
                                <span style="display:inline-block; width:14px; height:14px; background:{{ $team->playerlist_bgcolor }}; border:1px solid var(--border); border-radius:2px; vertical-align:middle; margin-right:4px;"></span>
                                <span class="hlx-muted" style="font-size:11px;">{{ $team->playerlist_bgcolor }}</span>
                            @else
                                <span class="hlx-muted">—</span>
                            @endif
                        </td>
                        <td class="hlx-muted">{{ $team->hidden === '1' ? 'Yes' : 'No' }}</td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.teams.edit', $team->teamId) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.teams.destroy', $team->teamId) }}" onsubmit="return confirm('Delete team?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">No teams for this game.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
