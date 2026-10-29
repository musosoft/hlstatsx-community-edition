<x-install.layout>
    <h1>Installation — Étape 1 / 4</h1>
    <p class="subtitle">Configuration de la base de données</p>

    <div class="steps">
        <div class="step active"></div>
        <div class="step"></div>
        <div class="step"></div>
        <div class="step"></div>
    </div>

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('install.step1.post') }}">
        @csrf

        <label>Hôte MySQL</label>
        <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required>

        <label>Port</label>
        <input type="number" name="db_port" value="{{ old('db_port', 3306) }}" required min="1" max="65535" style="width:120px;">

        <label>Nom de la base de données</label>
        <input type="text" name="db_database" value="{{ old('db_database', 'hlstats') }}" required>

        <label>Utilisateur</label>
        <input type="text" name="db_username" value="{{ old('db_username', 'hlstats') }}" required>

        <label>Mot de passe</label>
        <input type="password" name="db_password" value="{{ old('db_password') }}">

        <div class="info" style="margin-top:20px;">
            La connexion sera testée avant de continuer. Les informations seront écrites dans votre fichier <code>.env</code>.
        </div>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Tester &amp; Continuer →</button>
        </div>
    </form>
</x-install.layout>
