<x-install.layout>
    <h1>Installation — Étape 4 / 4</h1>
    <p class="subtitle">Création du premier administrateur</p>

    <div class="steps">
        <div class="step done"></div>
        <div class="step done"></div>
        <div class="step done"></div>
        <div class="step active"></div>
    </div>

    @if($errors->any())
        <div class="error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('install.step4.post') }}">
        @csrf

        <label>Nom d'utilisateur admin</label>
        <input type="text" name="username" value="{{ old('username') }}" required maxlength="64"
               placeholder="admin" pattern="[a-zA-Z0-9_\-\.]+">

        <label>Mot de passe <span style="color:#8b949e; font-weight:normal;">(minimum 8 caractères)</span></label>
        <input type="password" name="password" required minlength="8">

        <label>Confirmer le mot de passe</label>
        <input type="password" name="password_confirmation" required minlength="8">

        <label>URL publique du site</label>
        <input type="url" name="app_url" value="{{ old('app_url', config('app.url')) }}" required
               placeholder="https://stats.example.com">
        <span style="font-size:12px; color:#8b949e;">Sera écrite dans <code>.env</code> comme <code>APP_URL</code></span>

        <div class="info" style="margin-top:20px;">
            Le compte sera créé avec le niveau <strong>superadmin</strong> (accès total).
            L'installation sera marquée comme terminée après cette étape.
        </div>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Finaliser l'installation →</button>
        </div>
    </form>
</x-install.layout>
