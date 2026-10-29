<x-install.layout>
    <h1 style="color:#3fb950;">Installation terminée !</h1>
    <p class="subtitle">HLStatsX:CE est prêt.</p>

    <div class="steps">
        <div class="step done"></div>
        <div class="step done"></div>
        <div class="step done"></div>
        <div class="step done"></div>
    </div>

    <div class="success" style="margin-bottom:20px;">
        La base de données est configurée, le schéma importé, les migrations appliquées et votre compte administrateur est créé.
    </div>

    <p style="font-size:14px; color:#8b949e; margin-bottom:20px;">
        Prochaines étapes recommandées :
    </p>
    <ol style="font-size:14px; color:#8b949e; padding-left:20px; line-height:2;">
        <li>Connectez-vous au <a href="{{ url('/admin') }}" style="color:#c9a84c;">panneau d'administration</a></li>
        <li>Configurez vos serveurs de jeu</li>
        <li>Installez le daemon HLStats.pl sur vos serveurs</li>
    </ol>

    <div class="btn-row" style="margin-top:28px;">
        <a href="{{ url('/admin') }}" class="btn btn-primary">Accéder au panneau admin</a>
        <a href="{{ url('/') }}" class="btn btn-secondary">Voir le site</a>
    </div>
</x-install.layout>
