<x-layouts.admin title="Bans">

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:24px;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Player') }}</th>
                    <th>{{ __('Reason') }}</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bans as $ban)
                    <tr>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">
                            {{ \Carbon\Carbon::parse($ban->created)->format('Y-m-d') }}
                        </td>
                        <td>
                            @if($ban->player)
                                <a href="{{ route('admin.players.edit', $ban->player->playerId) }}" class="hlx-link">{{ $ban->player->lastName }}</a>
                            @else
                                <span class="hlx-muted">—</span>
                            @endif
                        </td>
                        <td class="hlx-text" style="font-size:var(--font-size-sm);">{{ $ban->reason ?: '—' }}</td>
                        <td class="hlx-muted" style="font-size:var(--font-size-sm);">
                            {{ $ban->is_permanent ? 'Permanent' : \Carbon\Carbon::parse($ban->ban_end)->format('Y-m-d') }}
                        </td>
                        <td style="font-size:var(--font-size-sm);">
                            @if($ban->is_active)
                                <span style="color:var(--status-offline);">Active</span>
                            @else
                                <span style="color:var(--status-online);">Expired</span>
                            @endif
                        </td>
                        <td>
                            @if($ban->is_active)
                                <form action="{{ route('admin.bans.destroy', $ban->banId) }}" method="POST" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="hlx-muted" style="text-align:center; padding:20px;">No bans found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$bans" />

</x-layouts.admin>
