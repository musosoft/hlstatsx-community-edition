# Pages Frontend

Toutes les pages sont accessibles sans authentification.

---

## Accueil — `/`

Vue d'ensemble globale du site.

**Contenu :**
- Section "Voice Server" (Discord, Steam Group)
- Carte mondiale Leaflet (marqueurs serveurs + top 500 joueurs)
- Tableau des jeux configurés (top joueur, top clan, joueurs connectés)
- Statistiques globales (kills totaux, dernier kill, nombre de joueurs/clans/jeux/serveurs)

---

## Joueurs — `/players`

Classement général des joueurs.

**Filtres disponibles :**

| Paramètre | Description |
|-----------|-------------|
| `game`    | Filtrer par code de jeu |
| `search`  | Recherche par pseudo |
| `sort`    | `skill` (défaut), `kills`, `deaths`, `headshots`, `connection_time` |
| `country` | Filtrer par code pays |
| `view`    | `total` ou autre vue |

**Colonnes affichées :** rang, drapeau, pseudo, clan, kills, deaths, headshots, skill, activité (barre colorée).

### Profil joueur — `/players/{id}`

**Onglets du profil :**

| Onglet | URL | Contenu |
|--------|-----|---------|
| Profil | `/players/{id}` | Stats complètes, avatar Steam, armes, maps, victimes, tueurs, équipes, actions, serveurs, graphique skill |
| Événements | `/players/{id}/events` | Historique des frags récents |
| Sessions | `/players/{id}/sessions` | Historique des sessions de jeu |
| Awards | `/players/{id}/awards` | Prix remportés |
| Chat | `/players/{id}/chat` | Messages en jeu |

---

## Clans — `/clans`

Classement des clans.

**Tri disponible :** `avg_skill` (défaut), `kills`, `deaths`, `headshots`, `members_count`, `total_connection_time`, `name`, `tag`.

### Détail clan — `/clans/{id}`

**Onglets :**
- **Membres** : liste des joueurs du clan
- **Armes** : armes les plus utilisées par le clan
- **Maps** : performances par map
- **Équipes** : équipes les plus jouées
- **Actions** : actions bonus réalisées

---

## Serveurs — `/servers`

Liste des serveurs de jeu avec statut en ligne/hors-ligne.

### Détail serveur — `/servers/{id}`

- Informations du serveur (map actuelle, joueurs connectés)
- Liste des joueurs actuellement connectés
- Graphique d'activité (kills par heure via Chart.js)

### Statut JSON — `/servers/{id}/status`

Retourne en JSON : `online`, `act_players`, `max_players`, `act_map`.

---

## Armes — `/weapons`

Classement des armes par kills, headshots, pourcentages.

### Détail arme — `/weapons/{code}`

- Top joueurs avec cette arme (frags, headshots, HPK)

---

## Maps — `/maps`

Classement des maps par kills, headshots.

### Détail map — `/maps/{map}`

- Top joueurs sur cette map (frags, headshots, HPK)
- Nombre de joueurs uniques

---

## Chat — `/chat`

Historique des messages en jeu, paginés (100/page), filtrables par jeu.

---

## Pays — `/countries`

Classement des pays par joueurs.

- `/countries/clans` — Classement des pays par clans
- `/countries/clans/{flag}` — Détail des clans d'un pays

---

## Awards — `/awards`

Affichage des prix journaliers et globaux par jeu.

- `/awards/{id}/detail` — Classement global pour un award
- `/awards/rank/{id}` — Joueurs ayant atteint ce rang
- `/awards/ribbon/{id}` — Joueurs ayant ce ruban

---

## Rôles — `/roles`

Liste des rôles en jeu (sniper, médic, etc.).

- `/roles/{code}` — Top joueurs pour ce rôle

---

## Actions — `/actions`

Liste des actions bonus (plant bombe, rescue, etc.) avec nombre total.

- `/actions/{id}` — Top joueurs pour cette action

---

## Bans — `/bans`

Liste publique des joueurs bannis.

---

## Aide — `/help`

Documentation utilisateur :
- Présentation de HLStatsX
- Tableau de navigation
- Explication du système de skill
- Commandes in-game (`!statsme`, `!stats`, `!rank`, `!session`, `!top10`)

---

## Jeu — `/game/{code}`

Page dédiée à un jeu (top joueurs, serveurs, stats).

---

## Recherche — `/search`

Recherche globale par pseudo, clan, serveur.

---

## Signature forum — `/players/{id}/signature.png`

Image PNG 400×75 générée à la volée (GD + FreeType), destinée aux signatures de
forum. Elle affiche le logo du jeu, le drapeau du pays, le pseudo (préfixé du tag
de clan), le rang, puis skill / kills / deaths / K:D / précision / % headshot.

- Fond : `hlstatsimg/games/{game}/sig/{1..11}.png`, avec repli sur
  `hlstatsimg/sig/{1..11}.png`. Le numéro est dérivé du `playerId`, ou forcé via
  `?background=N` (`?bg=N` accepté, valeur bornée à 1–11).
- Réponse mise en cache 5 minutes côté serveur et côté client
  (`Cache-Control: public, max-age=300`).
- Le BBCode prêt à copier est affiché sur la fiche joueur.

> Nécessite l'extension PHP **GD compilée avec FreeType**. Si elle est absente, la
> route répond `503` et journalise une erreur explicite plutôt que de servir une
> image cassée.

---

## Redirection legacy — `/hlstats.php`

Les URLs de l'ancienne interface HLstatsX sont toujours indexées par Google et
présentes dans les signatures de forum. Elles répondent donc en **301** (redirection
permanente, pour que l'index se consolide sur la nouvelle URL) ou en **404** pour
les modes supprimés — jamais par un renvoi vers l'accueil, que Search Console
signale comme *soft 404* / « Page avec redirection ».

Le paramètre `mode` est insensible à la casse. Les paramètres `game`, `q`, `sort`
et `country` sont conservés lors de la redirection.

### Pages de listing (301)

| `mode` | Destination |
|---|---|
| *(vide)*, `home`, `main`, `index` | `/` |
| `search` | `/search` |
| `players`, `top10` | `/players` |
| `clans` | `/clans` |
| `servers`, `livestats` | `/servers` |
| `weapons` | `/weapons` |
| `maps` | `/maps` |
| `actions` | `/actions` |
| `awards`, `ribbons` | `/awards` |
| `chat` | `/chat` |
| `countries` | `/countries` |
| `bans`, `cheaters` | `/bans` |
| `roles` | `/roles` |
| `help` | `/help` |

### Pages de détail (301)

| `mode` | Paramètre | Destination |
|---|---|---|
| `playerinfo`, `player` | `player` | `/players/{id}` |
| `claninfo`, `clan` | `clan` | `/clans/{id}` |
| `serverinfo`, `server` | `server` | `/servers/{id}` |
| `awardinfo` | `award` | `/awards/{id}/detail` |
| `weaponinfo`, `weapon` | `weapon` | `/weapons/{code}` |
| `roleinfo`, `role` | `role` | `/roles/{code}` |
| `mapinfo`, `map` | `map` | `/maps/{map}` |
| `gamepage`, `game` | `game` | `/game/{code}` |

Un identifiant absent, vide, non numérique ou négatif donne un **404**.

### Cas particuliers

- `mode=playersig&player={id}` n'est **pas** une redirection : il sert directement
  l'image de signature (voir section ci-dessus).
- Tout autre `mode` (`rss`, `trend`, `herotracker`, `statsme`…) répond **404**.

```
/hlstats.php?mode=players&game=cstrike   →  301  /players?game=cstrike
/hlstats.php?mode=playerinfo&player=7212 →  301  /players/7212
/hlstats.php?mode=playersig&player=7212  →  200  image/png
/hlstats.php?mode=rss                    →  404
```

---

## Pages d'erreur

Vues personnalisées dans `resources/views/errors/`, toutes en `noindex` :

| Code | Vue | Rendu |
|---|---|---|
| 403 | `errors/403` | layout complet |
| 404 | `errors/404` | layout complet + champ de recherche |
| 410 | `errors/410` | layout complet |
| 419 | `errors/419` | layout complet |
| 429 | `errors/429` | layout complet |
| 500 | `errors/500` | **autonome** |
| 503 | `errors/503` | **autonome** |

Les pages 500 et 503 sont volontairement autonomes : elles n'utilisent ni la base
de données, ni le cache, ni le manifeste Vite. Le layout principal interroge
`hlstats_Options` via l'en-tête ; s'en servir ici déclencherait une seconde
exception au moment précis où ces dépendances sont en panne, et Laravel
retomberait sur sa page brute.

---

## `robots.txt`

Servi par une route (`SitemapController@robots`) et non par un fichier statique,
afin que la ligne `Sitemap:` porte une URL absolue — une valeur relative est
invalide et silencieusement ignorée par Search Console.

`/admin`, `/install`, `/account`, `/auth/`, `/api/` et `/ingame/` sont exclus du
crawl. `/hlstats.php` reste volontairement crawlable : le bloquer empêcherait
Google de constater les 301 et les 404 qui résolvent les signalements
« Page avec redirection ».

---

## Changement de langue — `/language/{locale}`

Bascule la langue de l'interface (`en` ou `fr`) et redirige vers la page précédente.
