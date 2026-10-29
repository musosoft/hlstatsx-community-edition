<?php
/*
 * HLStatsX Community Edition - Laravel Rebase
 * A modern Laravel 13 rewrite of the HLStatsX:CE web frontend, preserving the original MySQL schema.
 *
 * A long lineage of open-source stats for Half-Life & Source engine games:
 *   HLstats (Simon Garner, 2001) -> HLstatsX (Tobias Oetzel, 2005)
 *   -> HLstatsX:CE (Nicholas Hastings, 2008) -> This rebase (Royal-Multi-Gamers, 2026)
 *
 * Perl daemon sourced from SnipeZilla/HLSTATS-2.
 *
 * Copyright (C) 2025-2026 Royal-Multi-Gamers
 * Licensed under the GNU General Public License v2.0
 * https://www.gnu.org/licenses/gpl-2.0.html
 *
 * https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Editable static pages (privacy policy, cookie policy, legal notice, and any
 * page an administrator adds later). Kept in a dedicated table because the
 * legacy hlstats_Options.value column is far too short for page bodies.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hlstats_pages')) {
            Schema::create('hlstats_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64);
                $table->string('locale', 5);
                $table->string('title', 191);
                $table->mediumText('body');
                $table->boolean('is_published')->default(true);
                $table->boolean('show_in_footer')->default(true);
                $table->boolean('is_system')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['slug', 'locale']);
            });
        }

        $now = now();
        $rows = [];

        foreach (self::defaults() as $slug => $locales) {
            foreach ($locales as $locale => $page) {
                $rows[] = [
                    'slug'           => $slug,
                    'locale'         => $locale,
                    'title'          => $page['title'],
                    'body'           => $page['body'],
                    'is_published'   => true,
                    'show_in_footer' => true,
                    'is_system'      => true,
                    'sort_order'     => $page['sort_order'],
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        // Safe if MySQL completed CREATE TABLE but a previous run failed before
        // Laravel recorded the migration. Never overwrite operator-edited pages.
        DB::table('hlstats_pages')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('hlstats_pages');
    }

    /**
     * Default GDPR texts shipped with the installation. Placeholders
     * (%SITE_NAME%, %SITE_URL%, %CONTACT%) are resolved at render time.
     */
    private static function defaults(): array
    {
        return [
            'privacy' => [
                'en' => ['sort_order' => 10, 'title' => 'Privacy Policy', 'body' => <<<'HTML'
<p>This policy describes the personal data the %SITE_NAME% statistics site collects, why it collects it, how long it keeps it, and how to exercise your rights. It complements our legal notice.</p>

<h2>1. Data controller</h2>
<p>The site %SITE_NAME% (%SITE_URL%) is operated by the community running the tracked game servers. For any question or request about your data: %CONTACT%.</p>

<h2>2. Data collected</h2>
<p>We only collect what the site needs to work. No data is bought, sold or rented.</p>
<ul>
    <li><strong>Game data</strong> — when you play on a tracked server, its log stream is parsed: Steam ID (or equivalent game identifier), in-game name, clan tag, team, kills, deaths, weapons, maps, awards, connection and disconnection times, and time played.</li>
    <li><strong>In-game chat</strong> — public and team messages sent on a tracked server may be recorded and displayed, when the server administrator enables this option.</li>
    <li><strong>IP address</strong> — reported by the game server, used for ban management and to derive an approximate country.</li>
    <li><strong>Approximate location</strong> — country, region and city derived locally from the IP address through a GeoIP database. Your IP address is never sent to a third party for this.</li>
    <li><strong>Steam sign-in</strong> — if you use the Steam button, we keep the identifier Steam sends us, along with the public profile name and avatar attached to it. Steam never gives us your email address.</li>
    <li><strong>Administrator account</strong> — for site administrators only: username, password (never stored in clear text, only as a hash), and access level.</li>
    <li><strong>Technical data</strong> — a session identifier for each session opened, and the web server logs, which record accesses and errors.</li>
</ul>
<p>The site runs no audience measurement, displays no advertising, and uses no tracker for commercial purposes.</p>

<h2>3. Why this data, and on which basis</h2>
<ul>
    <li><strong>Publishing statistics</strong> — rankings, player profiles, weapon and map statistics are the whole purpose of the site. Legal basis: the legitimate interest of the community in running public game servers and their scoreboard.</li>
    <li><strong>Linking your Steam account</strong> — only if you sign in. Legal basis: the execution of the service you ask for when you sign in.</li>
    <li><strong>Protecting the servers</strong> — ban management, cheat detection, session security, technical logs. Legal basis: our legitimate interest in keeping the servers playable.</li>
</ul>

<h2>4. Who has access to your data</h2>
<p>Rankings, profiles and statistics are public by design and visible to any visitor. Beyond that, your data is accessible to the site administrators, and to the providers strictly needed to run the site:</p>
<ul>
    <li><strong>The hosting provider</strong> of the site and its database, named in the legal notice.</li>
    <li><strong>Valve (Steam)</strong> — only if you use Steam sign-in, and under its own privacy policy.</li>
</ul>
<p>No data is sold, and none is shared with an advertising or analytics provider.</p>

<h2>5. Retention periods</h2>
<ul>
    <li><strong>Detailed event history</strong> (frags, chat, connections) — deleted automatically after the retention period set by the administrator (<em>DeleteDays</em> setting).</li>
    <li><strong>Aggregated statistics</strong> (totals, rankings, profile) — kept as long as the player profile exists.</li>
    <li><strong>Bans</strong> — kept for the duration of the ban, and in the ban history afterwards.</li>
    <li><strong>Sessions</strong> — cleared automatically after a period of inactivity, and on sign-out.</li>
    <li><strong>Technical logs</strong> — kept for as long as needed to diagnose incidents.</li>
</ul>

<h2>6. Your rights</h2>
<p>Under the GDPR you may request access to your data, correction of inaccurate data, deletion of your player profile, restriction of processing, and you may object to the processing. Write to %CONTACT%, stating your Steam ID or your in-game name so the profile can be identified. If our answer does not satisfy you, you may lodge a complaint with your national data protection authority.</p>

<h2>7. Cookies</h2>
<p>The site only sets technical cookies needed to work: the session cookie, the cross-site request forgery protection cookie (CSRF), and the cookie that remembers you have read the cookie notice. None of them serves advertising or audience measurement. See the cookie policy for the detail.</p>

<h2>8. Security</h2>
<p>Passwords are stored as non-reversible hashes, exchanges with the site are encrypted (HTTPS) when the server is configured for it, and access to the administration area is restricted to administrator accounts.</p>

<h2>9. Updates</h2>
<p>This policy may change with the site. The date of the last change is shown at the bottom of this page.</p>
HTML],
                'fr' => ['sort_order' => 10, 'title' => 'Politique de confidentialité', 'body' => <<<'HTML'
<p>Cette politique décrit les données personnelles que le site de statistiques %SITE_NAME% collecte, pourquoi il les collecte, combien de temps il les conserve et comment exercer vos droits. Elle complète nos mentions légales.</p>

<h2>1. Responsable du traitement</h2>
<p>Le site %SITE_NAME% (%SITE_URL%) est exploité par la communauté qui administre les serveurs de jeu suivis. Pour toute question ou demande relative à vos données : %CONTACT%.</p>

<h2>2. Données collectées</h2>
<p>Nous ne collectons que ce dont le site a besoin pour fonctionner. Aucune donnée n'est achetée, revendue ou louée.</p>
<ul>
    <li><strong>Données de jeu</strong> — lorsque vous jouez sur un serveur suivi, ses logs sont analysés : Steam ID (ou identifiant de jeu équivalent), pseudo en jeu, tag de clan, équipe, frags, morts, armes, cartes, récompenses, heures de connexion et de déconnexion, temps de jeu.</li>
    <li><strong>Chat en jeu</strong> — les messages publics et d'équipe envoyés sur un serveur suivi peuvent être enregistrés et affichés, lorsque l'administrateur du serveur active cette option.</li>
    <li><strong>Adresse IP</strong> — transmise par le serveur de jeu, utilisée pour la gestion des bannissements et pour déduire un pays approximatif.</li>
    <li><strong>Localisation approximative</strong> — pays, région et ville déduits localement de l'adresse IP via une base GeoIP. Votre adresse IP n'est jamais transmise à un tiers pour cela.</li>
    <li><strong>Connexion Steam</strong> — si vous utilisez ce bouton, nous conservons l'identifiant que Steam nous transmet, ainsi que le pseudo public et l'avatar associés. Steam ne nous communique jamais votre adresse e-mail.</li>
    <li><strong>Compte administrateur</strong> — pour les administrateurs du site uniquement : identifiant, mot de passe (jamais stocké en clair, seulement sous forme d'empreinte) et niveau d'accès.</li>
    <li><strong>Données techniques</strong> — un identifiant de session pour chaque session ouverte, ainsi que les journaux du serveur web, qui enregistrent les accès et les erreurs.</li>
</ul>
<p>Le site ne pratique aucune mesure d'audience, n'affiche aucune publicité et n'utilise aucun traceur à des fins commerciales.</p>

<h2>3. Pourquoi ces données, et à quel titre</h2>
<ul>
    <li><strong>Publier les statistiques</strong> — classements, profils de joueurs, statistiques d'armes et de cartes sont l'objet même du site. Base légale : l'intérêt légitime de la communauté à exploiter des serveurs de jeu publics et leur tableau des scores.</li>
    <li><strong>Lier votre compte Steam</strong> — uniquement si vous vous connectez. Base légale : l'exécution du service que vous demandez en vous connectant.</li>
    <li><strong>Protéger les serveurs</strong> — gestion des bannissements, détection de triche, sécurité des sessions, journaux techniques. Base légale : notre intérêt légitime à garder les serveurs jouables.</li>
</ul>

<h2>4. Qui a accès à vos données</h2>
<p>Les classements, profils et statistiques sont publics par nature et visibles de tout visiteur. Au-delà, vos données sont accessibles aux administrateurs du site et aux prestataires strictement nécessaires à son fonctionnement :</p>
<ul>
    <li><strong>L'hébergeur</strong> du site et de sa base de données, indiqué dans les mentions légales.</li>
    <li><strong>Valve (Steam)</strong> — uniquement si vous utilisez la connexion Steam, et selon sa propre politique de confidentialité.</li>
</ul>
<p>Aucune donnée n'est vendue, ni transmise à un prestataire publicitaire ou de mesure d'audience.</p>

<h2>5. Durées de conservation</h2>
<ul>
    <li><strong>Historique détaillé des événements</strong> (frags, chat, connexions) — supprimé automatiquement après la durée fixée par l'administrateur (paramètre <em>DeleteDays</em>).</li>
    <li><strong>Statistiques agrégées</strong> (totaux, classements, profil) — conservées tant que le profil du joueur existe.</li>
    <li><strong>Bannissements</strong> — conservés pendant la durée de la sanction, puis dans l'historique des bannissements.</li>
    <li><strong>Sessions</strong> — effacées automatiquement après une période d'inactivité, et à la déconnexion.</li>
    <li><strong>Journaux techniques</strong> — conservés le temps nécessaire au diagnostic des incidents.</li>
</ul>

<h2>6. Vos droits</h2>
<p>Conformément au RGPD, vous pouvez demander l'accès à vos données, la rectification des données inexactes, la suppression de votre profil de joueur, la limitation du traitement, et vous opposer au traitement. Écrivez à %CONTACT% en indiquant votre Steam ID ou votre pseudo en jeu afin d'identifier le profil. Si notre réponse ne vous satisfait pas, vous pouvez saisir l'autorité de protection des données de votre pays (en France, la <a href="https://www.cnil.fr" target="_blank" rel="noopener">CNIL</a>).</p>

<h2>7. Cookies</h2>
<p>Le site dépose uniquement des cookies techniques, nécessaires à son fonctionnement : le cookie de session, le cookie de protection contre la falsification de requêtes (CSRF) et celui qui mémorise que vous avez lu l'information sur les cookies. Aucun ne sert à la publicité ni à la mesure d'audience. Voir la politique de cookies pour le détail.</p>

<h2>8. Sécurité</h2>
<p>Les mots de passe sont stockés sous forme d'empreintes non réversibles, les échanges avec le site sont chiffrés (HTTPS) lorsque le serveur est configuré pour, et l'accès à l'administration est restreint aux comptes administrateurs.</p>

<h2>9. Mise à jour</h2>
<p>Cette politique peut évoluer avec le site. La date de dernière modification est affichée en bas de cette page.</p>
HTML],
            ],

            'cookies' => [
                'en' => ['sort_order' => 20, 'title' => 'Cookie Policy', 'body' => <<<'HTML'
<p>%SITE_NAME% uses strictly necessary cookies only. There is no advertising cookie, no tracker and no third-party analytics script on this site. This page completes our privacy policy.</p>

<h2>1. What a cookie is</h2>
<p>A cookie is a small file your browser stores when you visit a site, and sends back on each later request. It lets the site recognise your session from one page to the next. Cookies that are strictly necessary to provide a service you asked for do not require your consent.</p>

<h2>2. Cookies used</h2>
<table class="hlx-table">
    <thead>
        <tr><th>Name</th><th>Purpose</th><th>Lifetime</th></tr>
    </thead>
    <tbody>
        <tr><td>hlstatsx_session</td><td>Keeps your session: language choice, Steam sign-in, admin sign-in.</td><td>2 hours</td></tr>
        <tr><td>XSRF-TOKEN</td><td>Protects forms against cross-site request forgery.</td><td>2 hours</td></tr>
        <tr><td>hlx_consent</td><td>Stores the fact that you acknowledged this notice, so the banner is not displayed again.</td><td>6 months</td></tr>
        <tr><td>remember_web_*</td><td>Set only if you ask to stay signed in.</td><td>5 years</td></tr>
    </tbody>
</table>

<h2>3. Local storage</h2>
<p>Your browser also stores a <code>hlx_nav_collapsed</code> entry in local storage, which remembers whether the navigation sidebar is folded. It is not a cookie, is never sent to the server, and contains no personal data.</p>

<h2>4. External resources</h2>
<p>Fonts, map and chart libraries are served from this site itself, so browsing here does not expose your IP address to a content delivery network. Map tiles, when a map is displayed, are loaded from the tile provider configured by the administrator.</p>

<h2>5. Managing cookies</h2>
<p>Strictly necessary cookies cannot be disabled without breaking sign-in and forms. You can delete all cookies for this site at any time from your browser settings.</p>
HTML],
                'fr' => ['sort_order' => 20, 'title' => 'Politique de cookies', 'body' => <<<'HTML'
<p>%SITE_NAME% n'utilise que des cookies strictement nécessaires. Aucun cookie publicitaire, aucun traceur et aucun script de mesure d'audience tiers n'est présent sur ce site. Cette page complète notre politique de confidentialité.</p>

<h2>1. Ce qu'est un cookie</h2>
<p>Un cookie est un petit fichier que votre navigateur enregistre lors de votre visite, puis renvoie à chaque requête suivante. Il permet au site de reconnaître votre session d'une page à l'autre. Les cookies strictement nécessaires à un service que vous avez demandé ne requièrent pas votre consentement.</p>

<h2>2. Cookies utilisés</h2>
<table class="hlx-table">
    <thead>
        <tr><th>Nom</th><th>Finalité</th><th>Durée</th></tr>
    </thead>
    <tbody>
        <tr><td>hlstatsx_session</td><td>Maintient votre session : choix de langue, connexion Steam, connexion admin.</td><td>2 heures</td></tr>
        <tr><td>XSRF-TOKEN</td><td>Protège les formulaires contre la falsification de requête (CSRF).</td><td>2 heures</td></tr>
        <tr><td>hlx_consent</td><td>Mémorise que vous avez pris connaissance de cette information, pour ne plus afficher le bandeau.</td><td>6 mois</td></tr>
        <tr><td>remember_web_*</td><td>Déposé uniquement si vous demandez à rester connecté.</td><td>5 ans</td></tr>
    </tbody>
</table>

<h2>3. Stockage local</h2>
<p>Votre navigateur conserve également une entrée <code>hlx_nav_collapsed</code> en stockage local, qui mémorise si la barre de navigation est repliée. Ce n'est pas un cookie, elle n'est jamais envoyée au serveur et ne contient aucune donnée personnelle.</p>

<h2>4. Ressources externes</h2>
<p>Les polices, la bibliothèque de cartes et celle des graphiques sont servies par ce site lui-même : votre adresse IP n'est donc pas exposée à un réseau de diffusion de contenu. Les tuiles de carte, lorsqu'une carte est affichée, proviennent du fournisseur configuré par l'administrateur.</p>

<h2>5. Gérer les cookies</h2>
<p>Les cookies strictement nécessaires ne peuvent pas être désactivés sans empêcher la connexion et les formulaires de fonctionner. Vous pouvez supprimer à tout moment tous les cookies de ce site depuis les réglages de votre navigateur.</p>
HTML],
            ],

            'legal' => [
                'en' => ['sort_order' => 30, 'title' => 'Legal Notice', 'body' => <<<'HTML'
<p><em>Administrators must complete this page with the details required in their country before opening the site to the public.</em></p>

<h2>1. Publisher</h2>
<p>%SITE_NAME% — %SITE_URL%<br>Publisher name, legal form and postal address: <em>to be completed</em>.<br>Contact: %CONTACT%</p>

<h2>2. Publication director</h2>
<p><em>To be completed.</em></p>

<h2>3. Hosting provider</h2>
<p>Name, address and phone number of the hosting provider: <em>to be completed</em>.</p>

<h2>4. Software</h2>
<p>This site runs HLStatsX: Community Edition (Laravel rebase), distributed under the GNU General Public License v2.0. Source code: <a href="https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel" target="_blank" rel="noopener">github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel</a>.</p>

<h2>5. Trademarks</h2>
<p>Steam is a trademark of Valve Corporation. Game names, logos and assets belong to their respective owners and are used here for identification only.</p>

<h2>6. Personal data</h2>
<p>The data this site collects, the reasons for collecting it and your rights over it are described in the privacy policy and the cookie policy.</p>

<h2>7. Contact</h2>
<p>For any question about this site or its content: %CONTACT%.</p>
HTML],
                'fr' => ['sort_order' => 30, 'title' => 'Mentions légales', 'body' => <<<'HTML'
<p><em>L'administrateur doit compléter cette page avec les informations exigées dans son pays avant d'ouvrir le site au public.</em></p>

<h2>1. Éditeur</h2>
<p>%SITE_NAME% — %SITE_URL%<br>Nom de l'éditeur, forme juridique et adresse postale : <em>à compléter</em>.<br>Contact : %CONTACT%</p>

<h2>2. Directeur de la publication</h2>
<p><em>À compléter.</em></p>

<h2>3. Hébergeur</h2>
<p>Nom, adresse et téléphone de l'hébergeur : <em>à compléter</em>.</p>

<h2>4. Logiciel</h2>
<p>Ce site fonctionne avec HLStatsX: Community Edition (rebase Laravel), distribué sous licence GNU General Public License v2.0. Code source : <a href="https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel" target="_blank" rel="noopener">github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel</a>.</p>

<h2>5. Marques</h2>
<p>Steam est une marque de Valve Corporation. Les noms de jeux, logos et contenus appartiennent à leurs propriétaires respectifs et ne sont utilisés ici qu'à des fins d'identification.</p>

<h2>6. Données personnelles</h2>
<p>Les données collectées par ce site, les raisons de cette collecte et vos droits sont décrits dans la politique de confidentialité et la politique de cookies.</p>

<h2>7. Contact</h2>
<p>Pour toute question sur ce site ou son contenu : %CONTACT%.</p>
HTML],
            ],
        ];
    }
};
