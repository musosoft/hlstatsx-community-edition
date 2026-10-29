<x-layouts.admin title="Ribbons">
    @if(session('success'))<div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>@endif
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
        <x-admin.game-filter :games="$games" :current="$game" route="admin.ribbons.index" />
        <a href="{{ route('admin.ribbons.create', ['game' => $game]) }}" class="hlx-btn-gold">+ Add Ribbon</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead><tr><th>Image</th><th>Name</th><th>Award Code</th><th>Count</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($ribbons as $ribbon)
                    <tr>
                        <td>
                            @if($ribbon->image)
                                <img src="/hlstatsimg/ribbons/{{ $ribbon->image }}" alt="{{ $ribbon->ribbonName }}" style="height:20px;" onerror="this.style.display='none'">
                            @else
                                <span class="hlx-muted">—</span>
                            @endif
                        </td>
                        <td class="hlx-text">{{ $ribbon->ribbonName }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $ribbon->awardCode }}</td>
                        <td class="hlx-muted">{{ $ribbon->awardCount }}</td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.ribbons.edit', $ribbon->ribbonId) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.ribbons.destroy', $ribbon->ribbonId) }}" onsubmit="return confirm('Delete ribbon?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">No ribbons for this game.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
