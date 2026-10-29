# Architecture

## Structure du projet

```
hlstatsx-community-edition/
├── app/
│   ├── Auth/                      # Provider d'authentification admin
│   ├── Console/Commands/          # Commandes Artisan planifiées
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/             # Contrôleurs du panneau admin
│   │   │   └── Frontend/          # Contrôleurs des pages publiques
│   │   ├── Middleware/
│   │   │   └── SetLocale.php      # Détection/changement de langue
│   │   └── Requests/              # Form Requests de validation
│   ├── Models/                    # Modèles Eloquent (tables hlstats_*)
│   ├── Providers/
│   │   └── AppServiceProvider.php # Singleton ThemeService + sitename
│   └── Services/
│       ├── AdminService.php       # Bans, merge, reset skill
│       ├── GeoIPService.php       # Résolution IP → pays/coordonnées
│       ├── ServerStatusService.php # Ping UDP A2S_INFO
│       ├── StatsService.php       # Requêtes statistiques complexes
│       ├── SteamService.php       # Avatar et pseudo Steam
│       └── ThemeService.php       # Chargement/activation des thèmes
├── resources/
│   ├── css/                       # CSS global (app.css)
│   ├── js/                        # JS global (app.js + Alpine)
│   ├── themes/                    # Thèmes intégrés (JSON)
│   └── views/
│       ├── admin/                 # Vues du panneau admin
│       ├── components/            # Composants Blade réutilisables
│       ├── frontend/              # Vues publiques
│       └── layouts/               # Layouts app.blade.php / admin.blade.php
├── routes/
│   ├── web.php                    # Routes publiques
│   ├── admin.php                  # Routes admin (/admin/*)
│   ├── auth.php                   # Routes Breeze (register, login...)
│   └── console.php                # Définition du scheduler
└── storage/app/themes/            # Thèmes personnalisés (runtime)
```

---

## Modèles Eloquent

Tous les modèles mappent les tables `hlstats_*` sans modifier le schéma.

| Modèle | Table | Description |
|--------|-------|-------------|
| `Player` | `hlstats_Players` | Joueur avec kills, deaths, skill, coordonnées |
| `Clan` | `hlstats_Clans` | Clan de joueurs |
| `Server` | `hlstats_Servers` | Serveur de jeu |
| `Game` | `hlstats_Games` | Jeu (code, nom, realgame) |
| `Weapon` | `hlstats_Weapons` | Arme avec statistiques |
| `Award` | `hlstats_Awards` | Prix journaliers/hebdo/globaux |
| `Ban` | `hlstats_Bans` | Bannissements |
| `Rank` | `hlstats_Ranks` | Grades par plage de kills |
| `Ribbon` | `hlstats_Ribbons` | Rubans (achievements) |
| `Role` | `hlstats_Roles` | Rôles en jeu (sniper, médic…) |
| `Team` | `hlstats_Teams` | Équipes (CT, T, etc.) |
| `Action` | `hlstats_Actions` | Actions bonus en jeu |
| `EventKill` | `hlstats_Events_Frags` | Événements de kill |
| `EventChat` | `hlstats_Events_Chat` | Messages en jeu |
| `EventConnect` | `hlstats_Events_Connects` | Connexions/déconnexions |
| `EventAction` | `hlstats_Events_Actions` | Actions joueur |
| `PlayerUniqueId` | `hlstats_PlayerUniqueIds` | SteamID des joueurs |
| `PlayerWeapon` | `hlstats_PlayerWeapons` | Stats arme par joueur |
| `PlayerMap` | `hlstats_PlayerMaps` | Stats map par joueur |
| `GameMap` | `hlstats_Maps_Counts` | Compteurs de kills par map |
| `Option` | `hlstats_Options` | Paires clé/valeur de configuration |
| `HostGroup` | `hlstats_HostGroups` | Groupes d'hôtes/IP |
| `ServerConfig` | `hlstats_ServerConfig` | Config par serveur |
| `ClanTag` | `hlstats_ClanTags` | Tags de détection de clan |
| `Country` | *(requête directe)* | Statistiques par pays |

---

## Services

### `StatsService`
Requêtes statistiques complexes : classement joueurs, profil complet (armes, maps, victimes, killers, équipes, actions, serveurs), historique de skill, sessions, awards.

### `ThemeService`
- Charge les thèmes depuis `resources/themes/` (intégrés) et `storage/app/themes/` (custom)
- Génère les variables CSS `:root { --var: value; }` injectées dans le `<head>`
- Import/export ZIP, duplication, validation de la structure `theme.json`
- Cache Redis de 1 heure sur le thème actif

### `SteamService`
- Conversion SteamID32 → SteamID64
- Récupération avatar et pseudo via Steam Web API (clé requise) ou fallback XML public
- Cache Redis de 1 heure par joueur

### `ServerStatusService`
- Ping UDP A2S_INFO pour vérifier si un serveur est en ligne
- Mise à jour du statut de tous les serveurs visibles

### `GeoIPService`
- Résolution IP → code pays, nom, coordonnées GPS via MaxMind GeoLite2

### `AdminService`
- Bannissement/débannissement de joueurs
- Fusion de profils (transfert kills, deaths, SteamIDs, chat, armes)
- Reset du skill à la valeur par défaut du jeu

---

## Authentification

Deux guards distincts :

| Guard | Table | Usage |
|-------|-------|-------|
| `web` | `users` | Utilisateurs Laravel (non utilisé en front) |
| `admin` | `hlstats_Admins` | Administrateurs du panneau `/admin` |

Les mots de passe admin sont stockés en **bcrypt** dans `hlstats_Admins`. Un outil de migration depuis l'ancien MD5 (`hlstats_Users`) est disponible à `/admin/migrate-password`.

---

## Internationalisation

- Fichiers de traduction : `lang/en.json` et `lang/fr.json`
- Middleware `SetLocale` : détecte la langue depuis la session, sinon depuis l'en-tête `Accept-Language`
- Changement via : `GET /language/{locale}` (ex : `/language/fr`)
- Langues supportées : `en`, `fr`
