<x-layouts.app
    :title="__('Roles') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Roles' => null]"
    :gameNav="$game"
    activeTab="roles">

@if($roles->isEmpty())
    <div class="hlx-muted" style="padding:40px; text-align:center;">{{ __('No roles configured for this game.') }}</div>
@else
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px,1fr)); gap:12px;">
    @foreach($roles as $role)
        @php
            $img      = asset('hlstatsimg/games/' . $realgame . '/roles/' . strtolower($role->code) . '.png');
            $fallback = asset('hlstatsimg/games/' . $realgame . '/roles/default.png');
        @endphp
        <a href="{{ route('roles.show', [$role->code, 'game' => $game]) }}" style="text-decoration:none;">
        <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:14px 8px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:6px; transition:border-color .15s;"
             onmouseover="this.style.borderColor='var(--accent-primary)'" onmouseout="this.style.borderColor='var(--border)'">
            <img src="{{ $img }}" alt="{{ $role->name }}" style="width:56px;height:56px;object-fit:contain;"
                 onerror="this.onerror=null;this.src='{{ $fallback }}'">
            <div style="font-size:13px; font-weight:700; color:var(--text-heading);">{{ $role->name }}</div>
            <div style="font-size:11px; color:var(--text-secondary);">{{ $role->code }}</div>
        </div>
        </a>
    @endforeach
</div>
@endif

</x-layouts.app>
