<x-layouts.admin title="Awards">
    @if(session('success'))<div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>@endif
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <x-admin.game-filter :games="$games" :current="$game" route="admin.awards.index" :extra="['type' => $type]" />
            <div style="display:flex; gap:4px;">
                @foreach([''=>'All','W'=>'Weapon','1'=>'Player Action','2'=>'Player-Player'] as $val => $label)
                    <a href="{{ route('admin.awards.index', ['game' => $game, 'type' => $val]) }}"
                       style="padding:4px 10px; border-radius:var(--border-radius-sm); font-size:var(--font-size-sm); text-decoration:none; border:1px solid var(--border);
                              background-color:{{ $type === $val ? 'var(--accent-primary)' : 'var(--bg-surface)' }};
                              color:{{ $type === $val ? '#000' : 'var(--text-secondary)' }};">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <a href="{{ route('admin.awards.create', ['game' => $game, 'type' => $type]) }}" class="hlx-btn-gold">+ Add Award</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead><tr><th>{{ __('Type') }}</th><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Verb') }}</th><th>{{ __('Actions') }}</th></tr></thead>
            <tbody>
                @forelse($awards as $award)
                    @php $typeLabels = ['W'=>'Weapon','1'=>'Player','2'=>'P-P']; @endphp
                    <tr>
                        <td class="hlx-muted" style="font-size:11px;">{{ $typeLabels[$award->awardType] ?? $award->awardType }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $award->code }}</td>
                        <td class="hlx-text">{{ $award->name }}</td>
                        <td class="hlx-muted">{{ $award->verb ?: '—' }}</td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.awards.edit', $award->awardId) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.awards.destroy', $award->awardId) }}" onsubmit="return confirm('Delete award?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">No awards found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
