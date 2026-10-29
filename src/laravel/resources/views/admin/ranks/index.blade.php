<x-layouts.admin title="Ranks">
    @if(session('success'))
        <div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>
    @endif

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
        <x-admin.game-filter :games="$games" :current="$game" route="admin.ranks.index" />
        <a href="{{ route('admin.ranks.create', ['game' => $game]) }}" class="hlx-btn-gold">+ Add Rank</a>
    </div>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr><th>Image</th><th>Name</th><th>Min Kills</th><th>Max Kills</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($ranks as $rank)
                    <tr>
                        <td>
                            <img src="{{ asset('hlstatsimg/ranks/' . $rank->image) }}" alt="{{ $rank->rankName }}"
                                 style="height:24px;" onerror="this.style.display='none'">
                            <span class="hlx-muted" style="font-size:11px;">{{ $rank->image }}</span>
                        </td>
                        <td class="hlx-text">{{ $rank->rankName }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $rank->minKills }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $rank->maxKills }}</td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.ranks.edit', $rank->rankId) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.ranks.destroy', $rank->rankId) }}"
                                  onsubmit="return confirm('Delete rank?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">No ranks for this game.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
