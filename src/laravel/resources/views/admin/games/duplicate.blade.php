<x-layouts.admin :title="'Duplicate Game: ' . $game->code">
    <div style="max-width:520px;">
        <p class="hlx-muted" style="margin-bottom:20px; font-size:var(--font-size-sm);">
            Duplication de <strong style="color:var(--text-primary);">{{ $game->name }}</strong>
            (<code style="font-family:var(--font-family-mono);">{{ $game->code }}</code>).
            Les éléments suivants seront copiés (les statistiques sont remises à zéro) :
        </p>

        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px;">
            @foreach([
                'weapons'       => 'Armes',
                'ranks'         => 'Rangs',
                'teams'         => 'Équipes',
                'roles'         => 'Rôles',
                'actions'       => 'Actions',
                'awards'        => 'Récompenses',
                'ribbons'       => 'Rubans',
                'server_config' => 'Config Serveur (défauts)',
            ] as $key => $label)
                <span style="background-color:var(--bg-card); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:3px 10px; font-size:var(--font-size-sm);">
                    {{ $label }}
                    <span class="hlx-muted">({{ $counts[$key] }})</span>
                </span>
            @endforeach
        </div>

        @if($errors->any())
            <div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; font-size:var(--font-size-sm); color:var(--status-offline);">
                <ul style="margin:0; padding-left:16px;">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.games.duplicate.store', $game->code) }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">
                    Nouveau code <span style="color:var(--status-offline);">*</span>
                    <span class="hlx-muted" style="font-weight:normal;">(minuscules, chiffres, underscores uniquement)</span>
                </label>
                <input type="text" name="code" value="{{ old('code') }}" required maxlength="32"
                       placeholder="ex: css_dm"
                       pattern="[a-z0-9_]+"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            </div>
            <div style="margin-bottom:20px;">
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">
                    Nom du nouveau jeu <span style="color:var(--status-offline);">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $game->name . ' (Copy)') }}" required maxlength="128"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="hlx-btn-gold">Dupliquer</button>
                <a href="{{ route('admin.games.index') }}" class="hlx-muted" style="font-size:var(--font-size-sm); align-self:center;">Annuler</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
