<x-layouts.admin title="Players">

    <form method="GET" style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap;">
        <input type="text" name="search" value="{{ $search }}"
               style="background-color:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:5px 10px; font-size:var(--font-size-sm); width:220px;"
               placeholder="Search by name...">
        <input type="text" name="game" value="{{ $game }}"
               style="background-color:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:5px 10px; font-size:var(--font-size-sm); width:100px;"
               placeholder="Game code">
        <button type="submit" class="hlx-btn-green">Search</button>
    </form>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('Player') }}</th>
                    <th>Game</th>
                    <th>{{ __('Skill') }}</th>
                    <th>Kills</th>
                    <th>Hidden</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($players as $player)
                    <tr>
                        <td class="hlx-muted">{{ $player->playerId }}</td>
                        <td>
                            <x-ui.flag :code="$player->country ?? ''" />
                            {{ $player->lastName }}
                        </td>
                        <td class="hlx-text">{{ $player->game }}</td>
                        <td class="hlx-text">{{ number_format($player->skill) }}</td>
                        <td class="hlx-text">{{ number_format($player->kills) }}</td>
                        <td class="hlx-muted">{{ $player->hideranking ? 'Yes' : 'No' }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('admin.players.edit', $player->playerId) }}" class="hlx-link" style="margin-right:8px;">Edit</a>
                            <form action="{{ route('admin.players.reset-skill', $player->playerId) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" style="background:none; border:none; color:var(--link); cursor:pointer; font-size:var(--font-size-sm); padding:0; margin-right:8px;">Reset Skill</button>
                            </form>
                            <form action="{{ route('admin.players.destroy', $player->playerId) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this player?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="hlx-muted" style="text-align:center; padding:20px;">No players found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$players" />

</x-layouts.admin>
