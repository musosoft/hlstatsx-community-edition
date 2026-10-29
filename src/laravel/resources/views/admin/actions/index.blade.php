<x-layouts.admin title="Actions">
    @if(session('success'))<div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>@endif
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
        <x-admin.game-filter :games="$games" :current="$game" route="admin.actions.index" />
        <a href="{{ route('admin.actions.create', ['game' => $game]) }}" class="hlx-btn-gold">+ Add Action</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead><tr><th>Code</th><th>Description</th><th>Player Pts</th><th>Team Pts</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($actions as $action)
                    <tr>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $action->code }}</td>
                        <td class="hlx-text">{{ $action->description ?: '—' }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $action->reward_player > 0 ? '+' : '' }}{{ $action->reward_player }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $action->reward_team > 0 ? '+' : '' }}{{ $action->reward_team }}</td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.actions.edit', $action->id) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.actions.destroy', $action->id) }}" onsubmit="return confirm('Delete action?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">No actions for this game.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
