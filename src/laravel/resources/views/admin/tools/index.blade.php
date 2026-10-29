<x-layouts.admin title="Tools">
    @if(session('success'))<div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:20px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>@endif
    @if(session('error'))<div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:20px; color:var(--status-offline); font-size:var(--font-size-sm);">{{ session('error') }}</div>@endif

    <div style="display:flex; flex-direction:column; gap:20px;">

        {{-- Optimize Database --}}
        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:20px; background-color:var(--bg-surface);">
            <h3 style="color:var(--text-heading); margin:0 0 8px 0; font-size:15px;">{{ __('Optimize Database') }}</h3>
            <p class="hlx-muted" style="margin:0 0 14px 0; font-size:var(--font-size-sm);">Runs OPTIMIZE TABLE on all major HLStatsX tables to reclaim space and improve performance.</p>
            <form method="POST" action="{{ route('admin.tools.optimize-db') }}" onsubmit="return confirm('Optimize all tables? This may take a moment on large databases.')">
                @csrf
                <button type="submit" class="hlx-btn-gold">{{ __('Optimize Database') }}</button>
            </form>
        </div>

        {{-- Reset Game Stats --}}
        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:20px; background-color:var(--bg-surface);">
            <h3 style="color:var(--text-heading); margin:0 0 8px 0; font-size:15px;">{{ __('Reset Game Statistics') }}</h3>
            <p class="hlx-muted" style="margin:0 0 14px 0; font-size:var(--font-size-sm);">Resets all player statistics and deletes all event records for the selected game. <strong style="color:var(--status-offline);">This is irreversible.</strong></p>
            <form method="POST" action="{{ route('admin.tools.reset-game') }}">
                @csrf
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end;">
                    <div>
                        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game</label>
                        <select name="game" style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                            @foreach($games as $g)
                                <option value="{{ $g->code }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Type <span style="color:var(--status-offline);">RESET</span> to confirm</label>
                        <input type="text" name="confirm" required placeholder="RESET"
                               style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm); width:120px;">
                    </div>
                    <button type="submit" style="padding:6px 14px; border:none; border-radius:var(--border-radius-sm); background:var(--status-offline); color:#fff; cursor:pointer; font-size:var(--font-size-sm); font-weight:600;">Reset Game</button>
                </div>
            </form>
        </div>

        {{-- Delete Zero-stat Players --}}
        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:20px; background-color:var(--bg-surface);">
            <h3 style="color:var(--text-heading); margin:0 0 8px 0; font-size:15px;">{{ __('Delete Inactive Players') }}</h3>
            <p class="hlx-muted" style="margin:0 0 14px 0; font-size:var(--font-size-sm);">Removes players with 0 kills and 0 deaths from the selected game. <strong style="color:var(--status-offline);">This is irreversible.</strong></p>
            <form method="POST" action="{{ route('admin.tools.delete-players') }}">
                @csrf
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end;">
                    <div>
                        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game</label>
                        <select name="game" style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                            @foreach($games as $g)
                                <option value="{{ $g->code }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Type <span style="color:var(--status-offline);">DELETE</span> to confirm</label>
                        <input type="text" name="confirm" required placeholder="DELETE"
                               style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm); width:120px;">
                    </div>
                    <button type="submit" style="padding:6px 14px; border:none; border-radius:var(--border-radius-sm); background:var(--status-offline); color:#fff; cursor:pointer; font-size:var(--font-size-sm); font-weight:600;">Delete Players</button>
                </div>
            </form>
        </div>

        {{-- Full or Partial Reset --}}
        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:20px; background-color:var(--bg-surface);">
            <h3 style="color:var(--text-heading); margin:0 0 4px 0; font-size:15px;">Full or Partial Reset</h3>
            <p class="hlx-muted" style="margin:0 0 16px 0; font-size:var(--font-size-sm);">Réinitialise ou supprime les données choisies, globalement ou pour un jeu. <strong style="color:var(--status-offline);">Irréversible.</strong></p>

            <form method="POST" action="{{ route('admin.tools.partial-reset') }}" id="resetform"
                  onsubmit="return confirm('Confirmer le reset des éléments sélectionnés ?')">
                @csrf

                {{-- Game filter --}}
                <div style="margin-bottom:16px;">
                    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Jeu (vide = tous les jeux)</label>
                    <select name="game" style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm); min-width:220px;">
                        <option value="">— Tous les jeux —</option>
                        @foreach($games as $g)
                            <option value="{{ $g->code }}">{{ $g->name }} ({{ $g->code }}){{ $g->hidden ? ' *' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                @php
                $sections = [
                    'Statistiques joueurs / serveurs' => [
                        'skill'       => 'Remettre Skill à 1000',
                        'pcounts'     => 'Réinitialiser compteurs joueurs (kills, deaths…)',
                        'scounts'     => 'Réinitialiser compteurs serveurs',
                        'wcounts'     => 'Réinitialiser compteurs armes',
                        'acounts'     => 'Réinitialiser compteurs actions',
                        'mcounts'     => 'Réinitialiser compteurs maps',
                        'rcounts'     => 'Réinitialiser compteurs rôles',
                    ],
                    'Historiques' => [
                        'awards'       => 'Réinitialiser récompenses & rubans',
                        'sessions'     => 'Supprimer historique sessions',
                        'names'        => 'Supprimer historique des noms',
                        'names_counts' => 'Réinitialiser compteurs des noms',
                    ],
                    'Événements' => [
                        'ev_frags'       => 'Events Frags',
                        'ev_chat'        => 'Events Chat',
                        'ev_connects'    => 'Events Connexions',
                        'ev_disconnects' => 'Events Déconnexions',
                        'ev_changename'  => 'Events ChangeName',
                        'ev_changerole'  => 'Events ChangeRole',
                        'ev_changeteam'  => 'Events ChangeTeam',
                        'ev_entries'     => 'Events Entries',
                        'ev_suicides'    => 'Events Suicides',
                        'ev_teamkills'   => 'Events TeamKills',
                        'ev_actions'     => 'Events Actions (PlayerActions / TeamBonuses)',
                        'ev_latency'     => 'Events Latency',
                        'ev_statsme'     => 'Events Statsme',
                        'ev_statsmetime' => 'Events StatsmeTime',
                        'ev_admin'       => 'Events Admin',
                        'ev_rcon'        => 'Events Rcon',
                    ],
                    'Suppression complète (DANGER)' => [
                        'delete_players' => '⚠ Supprimer tous les joueurs & clans du jeu',
                    ],
                ];
                @endphp

                @foreach($sections as $title => $items)
                    <div style="margin-bottom:14px;">
                        <div class="hlx-muted" style="font-size:var(--font-size-sm); font-weight:600; margin-bottom:6px; border-bottom:1px solid var(--border); padding-bottom:4px;">{{ $title }}</div>
                        <div style="display:flex; flex-wrap:wrap; gap:6px 20px;">
                            @foreach($items as $key => $label)
                                <label style="display:flex; align-items:center; gap:5px; font-size:var(--font-size-sm); cursor:pointer; {{ str_starts_with($key,'delete') ? 'color:var(--status-offline);' : '' }}">
                                    <input type="checkbox" name="ops[]" value="{{ $key }}">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div style="display:flex; gap:10px; margin-top:8px; flex-wrap:wrap;">
                    <button type="button" onclick="selectPreset('all')" style="background:none; border:1px solid var(--border); color:var(--text-primary); padding:4px 10px; border-radius:var(--border-radius-sm); cursor:pointer; font-size:var(--font-size-sm);">Tout sélectionner</button>
                    <button type="button" onclick="selectPreset('events')" style="background:none; border:1px solid var(--border); color:var(--text-primary); padding:4px 10px; border-radius:var(--border-radius-sm); cursor:pointer; font-size:var(--font-size-sm);">Events seulement</button>
                    <button type="button" onclick="selectPreset('none')" style="background:none; border:1px solid var(--border); color:var(--text-muted); padding:4px 10px; border-radius:var(--border-radius-sm); cursor:pointer; font-size:var(--font-size-sm);">Tout désélectionner</button>
                    <button type="submit" style="padding:6px 16px; border:none; border-radius:var(--border-radius-sm); background:var(--status-offline); color:#fff; cursor:pointer; font-size:var(--font-size-sm); font-weight:600; margin-left:auto;">Exécuter le reset</button>
                </div>
            </form>

            <script>
            function selectPreset(preset) {
                const boxes = document.querySelectorAll('#resetform input[type=checkbox]');
                boxes.forEach(cb => {
                    if (preset === 'all') cb.checked = true;
                    else if (preset === 'none') cb.checked = false;
                    else if (preset === 'events') cb.checked = cb.value.startsWith('ev_');
                });
            }
            </script>
        </div>

        {{-- Reset DB Collations --}}
        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:20px; background-color:var(--bg-surface);">
            <h3 style="color:var(--text-heading); margin:0 0 4px 0; font-size:15px;">Reset All DB Collations to UTF8</h3>
            <p class="hlx-muted" style="margin:0 0 14px 0; font-size:var(--font-size-sm);">
                Convertit toutes les tables et colonnes en <code>utf8mb4 / utf8mb4_unicode_ci</code>.<br>
                À utiliser en cas d'erreurs de collation après une migration depuis un ancien système HLstats(X).<br>
                <strong>Sauvegardez la base avant d'exécuter.</strong>
            </p>
            <form method="POST" action="{{ route('admin.tools.reset-collations') }}"
                  onsubmit="return confirm('Convertir toutes les tables en utf8mb4_unicode_ci ?\n\nAucune donnée ne sera perdue, mais faites une sauvegarde par précaution.')">
                @csrf
                <button type="submit" class="hlx-btn-gold">Convertir les collations → utf8mb4</button>
            </form>
        </div>

    </div>
</x-layouts.admin>
