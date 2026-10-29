<x-install.layout>
    <h1>Installation — Étape 2 / 4</h1>
    <p class="subtitle">Import du schéma de base de données</p>

    <div class="steps">
        <div class="step done"></div>
        <div class="step active"></div>
        <div class="step"></div>
        <div class="step"></div>
    </div>

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    @if(session('sql_imported'))
        <div class="success">{{ session('sql_imported') }}</div>
    @endif

    @if($tablesExist)
        <div class="info">
            Les tables HLStatsX (<code>hlstats_Games</code>, etc.) semblent déjà exister dans la base.
            Vous pouvez importer à nouveau (les tables existantes seront conservées grâce à <code>IF NOT EXISTS</code>)
            ou passer cette étape.
        </div>
    @else
        <p style="font-size:14px; color:#8b949e; margin-top:12px;">
            Aucune table HLStatsX détectée. L'import va créer toutes les tables nécessaires à partir de
            <code>database/install.sql</code> (schéma HLStatsX:CE 1.6.2).
        </p>
    @endif

    <form method="POST" action="{{ route('install.step2.post') }}">
        @csrf
        <input type="hidden" name="action" value="import">
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Importer le schéma SQL →</button>
            <button type="submit" name="action" value="skip" class="btn btn-secondary">Passer (base déjà prête)</button>
        </div>
    </form>
</x-install.layout>
