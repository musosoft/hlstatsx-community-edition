<x-layouts.app
    :title="__('Bans') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Bans' => null]"
    :gameNav="$game"
    activeTab="bans">

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:140px;">{{ __('Date') }}</th>
                    <th>{{ __('Player') }}</th>
                    <th>{{ __('Reason') }}</th>
                    <th style="width:100px;">{{ __('Expires') }}</th>
                    <th style="width:70px;">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bans as $ban)
                    <tr>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm); white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($ban->created)->format('Y-m-d H:i') }}
                        </td>
                        <td>
                            @if($ban->player)
                                <x-ui.player-link :player="$ban->player" />
                            @else
                                <span class="hlx-muted">—</span>
                            @endif
                        </td>
                        <td class="hlx-text" style="word-break:break-word; max-width:400px;">{{ $ban->reason ?: '—' }}</td>
                        <td class="hlx-muted" style="font-size:var(--font-size-sm); white-space:nowrap;">
                            @if($ban->is_permanent)
                                <span style="color:var(--status-offline);">Permanent</span>
                            @else
                                {{ \Carbon\Carbon::parse($ban->ban_end)->format('Y-m-d') }}
                            @endif
                        </td>
                        <td>
                            @if($ban->is_active)
                                <span style="color:var(--status-offline); font-size:var(--font-size-sm);">Active</span>
                            @else
                                <span style="color:var(--status-online); font-size:var(--font-size-sm);">Expired</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">No bans found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$bans" />

</x-layouts.app>
