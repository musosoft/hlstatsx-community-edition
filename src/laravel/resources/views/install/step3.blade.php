<x-install.layout>
    <h1>Installation — Étape 3 / 4</h1>
    <p class="subtitle">Migrations Laravel</p>

    <div class="steps">
        <div class="step done"></div>
        <div class="step done"></div>
        <div class="step active"></div>
        <div class="step"></div>
    </div>

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    @if(session('migrate_output'))
        <div class="success" style="white-space:pre-wrap; font-family:monospace; font-size:12px;">{{ session('migrate_output') }}</div>
    @endif

    <p style="font-size:14px; color:#8b949e; margin-top:12px;">
        Cette étape exécute <code>php artisan migrate</code> pour créer les tables Laravel nécessaires
        (<code>sessions</code>, <code>cache</code>, <code>jobs</code>, <code>hlstats_Admins</code>, <code>hlstats_Bans</code>)
        et initialise la version de l'application.
    </p>

    <div class="info" style="margin-top:16px;">
        Si les tables existent déjà, les migrations seront ignorées automatiquement.
    </div>

    <form method="POST" action="{{ route('install.step3.post') }}">
        @csrf
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Exécuter les migrations →</button>
        </div>
    </form>
</x-install.layout>
