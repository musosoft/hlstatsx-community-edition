# Commandes Artisan & Planificateur

## Commandes HLStatsX

### `hlstats:check-servers`

Ping tous les serveurs visibles via UDP (A2S_INFO) et affiche leur statut.

```bash
php artisan hlstats:check-servers
```

**Sortie exemple :**
```
192.168.1.10:27015 — online
192.168.1.11:27015 — offline
Done. 1/2 servers online.
```

---

### `hlstats:steam-sync`

Synchronise les avatars et pseudos Steam pour les joueurs ayant un SteamID.

```bash
php artisan hlstats:steam-sync
php artisan hlstats:steam-sync --limit=200   # Limiter à 200 joueurs (défaut: 500)
```

Les données sont mises en cache Redis pendant 1 heure par joueur.  
Nécessite une `STEAM_API_KEY` pour la récupération des pseudos. Les avatars peuvent être récupérés via le XML public sans clé.

---

### `hlstats:compute-awards`

Calcule les prix journaliers (meilleur joueur par arme ou par action).

```bash
php artisan hlstats:compute-awards
php artisan hlstats:compute-awards --date=2025-01-15   # Date spécifique
```

Mise à jour de `d_winner_id`, `d_winner_name`, `d_winner_count` dans `hlstats_Awards`.

**Types d'awards calculés :**
- `W` — Meilleur joueur avec une arme donnée (total kills dans `hlstats_PlayerWeapons`)
- `1` — Meilleur joueur pour une action (events du jour dans `hlstats_Events_PlayerActions`)
- `2` — Meilleur joueur pour une action joueur-joueur

---

### `hlstats:prune-events`

Supprime les anciens événements pour limiter la taille de la base de données.

```bash
php artisan hlstats:prune-events
php artisan hlstats:prune-events --days=60   # Défaut: 90 jours
```

**Tables purgées :**
- `hlstats_Events_Frags`
- `hlstats_Events_Connects`
- `hlstats_Events_Chat`
- `hlstats_Events_PlayerActions`

---

### `location:update`

Met à jour la base de données GeoIP MaxMind (fournie par le package `stevebauman/location`).

```bash
php artisan location:update
```

---

## Planificateur

Le planificateur doit être activé via le crontab système :

```cron
* * * * * cd /var/www/hlstatsx && php artisan schedule:run >> /dev/null 2>&1
```

**Tableau des tâches planifiées :**

| Commande | Fréquence | Description |
|----------|-----------|-------------|
| `hlstats:check-servers` | Toutes les 5 min | Vérifie le statut en ligne des serveurs |
| `hlstats:steam-sync` | Toutes les heures | Synchronise avatars et pseudos Steam |
| `hlstats:compute-awards` | Quotidien à 00:05 | Calcule les prix journaliers |
| `hlstats:prune-events` `--days=90` | Hebdomadaire | Purge les anciens événements |
| `location:update` | Mensuel | Met à jour la base GeoIP |

---

## Commandes Laravel utiles

```bash
# Vider tous les caches
php artisan optimize:clear

# Régénérer les caches de production
php artisan optimize

# Voir les tâches planifiées
php artisan schedule:list

# Exécuter le scheduler manuellement
php artisan schedule:run

# Ouvrir le REPL Tinker
php artisan tinker
```
