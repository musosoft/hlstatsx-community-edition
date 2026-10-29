<x-layouts.admin title="Add Team">
    @if($errors->any())<div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-offline); font-size:var(--font-size-sm);">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.teams.store') }}" style="max-width:480px;">
        @csrf
        @include('admin.teams._form', ['games' => $games, 'selectedGame' => $selectedGame])
        <div style="display:flex; gap:8px; margin-top:20px;">
            <button type="submit" class="hlx-btn-gold">Create</button>
            <a href="{{ route('admin.teams.index', ['game' => $selectedGame]) }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
