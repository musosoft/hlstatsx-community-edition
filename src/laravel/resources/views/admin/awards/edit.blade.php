<x-layouts.admin :title="'Edit Award: ' . $award->name">
    @if($errors->any())<div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-offline); font-size:var(--font-size-sm);">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.awards.update', $award->awardId) }}" style="max-width:480px;">
        @csrf @method('PUT')
        @include('admin.awards._form', ['games' => $games, 'selectedGame' => $award->game, 'award' => $award])
        <div style="display:flex; gap:8px; margin-top:20px;">
            <button type="submit" class="hlx-btn-gold">Save</button>
            <a href="{{ route('admin.awards.index', ['game' => $award->game, 'type' => $award->awardType]) }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
