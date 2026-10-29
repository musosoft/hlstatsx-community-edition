# Panneau Admin

Accessible à `/admin`. Requiert une authentification via le guard `admin` (table `hlstats_Admins`).

---

## Connexion

```
GET  /admin/login
POST /admin/login
POST /admin/logout
```

Si vous possédez un ancien compte `hlstats_Users` (mot de passe MD5), la migration vers bcrypt est disponible à `/admin/migrate-password`.

---

## Dashboard — `/admin`

Vue d'ensemble avec compteurs (joueurs, clans, serveurs, jeux, bans actifs) et statistiques globales.

---

## Joueurs — `/admin/players`

| Action | Description |
|--------|-------------|
| Liste | Tous les joueurs, triés, paginés |
| Édition | Modifier pseudo, clan, skill, flag, hideranking |
| Suppression | Supprimer un profil joueur |
| Reset skill | Remet le skill à la valeur par défaut du jeu |
| Fusion | Fusionner deux profils (kills, deaths, SteamIDs, chat, armes transférés) |

---

## Clans — `/admin/clans`

| Action | Description |
|--------|-------------|
| Liste | Tous les clans |
| Édition | Modifier nom, tag, homepage, jeu |
| Suppression | Supprimer un clan |

---

## Serveurs — `/admin/servers`

| Action | Description |
|--------|-------------|
| Liste | Tous les serveurs |
| Création | Ajouter un nouveau serveur |
| Édition | IP, port, nom, jeu, adresse publique, RCON |
| Suppression | Retirer un serveur |

---

## Jeux — `/admin/games`

| Action | Description |
|--------|-------------|
| Liste | Tous les jeux |
| Création | Créer un nouveau code de jeu |
| Édition | Modifier nom, `realgame`, visibilité |
| Suppression | Supprimer un jeu |

---

## Armes — `/admin/weapons`

Édition du nom, du modificateur de dommages, suppression.

---

## Bannissements — `/admin/bans`

| Action | Description |
|--------|-------------|
| Liste | Bans actifs et expirés |
| Création | Bannir un joueur (SteamID, raison, durée optionnelle) |
| Suppression | Lever un ban (réactive le classement si aucun autre ban actif) |

---

## Thèmes — `/admin/themes`

Voir la page [Système de Thèmes](Themes).

---

## Options — `/admin/options`

Modification des options globales stockées dans `hlstats_Options` (ex : `sitename`, `theme_active`).

---

## Utilisateurs Admin — `/admin/admin-users`

Gestion des comptes admin (`hlstats_Admins` + `hlstats_Users`).

**Niveaux d'accès (`acclevel`) :**

| Valeur | Rôle |
|--------|------|
| `100`  | Super Admin |
| `80`   | Admin |
| `0`    | Lecture seule |

---

## Grades — `/admin/ranks`

Création et gestion des grades attribués selon le nombre de kills (ex : "Private", "General").

---

## Équipes — `/admin/teams`

Gestion des équipes en jeu (CT, T, etc.) avec couleurs d'affichage.

---

## Rôles — `/admin/roles`

Gestion des rôles en jeu.

---

## Actions — `/admin/actions`

Gestion des actions bonus (plant bombe, rescue hostage, etc.) avec le bonus de skill associé.

---

## Awards — `/admin/awards`

Gestion des prix journaliers/globaux.

**Types :**

| Type | Description |
|------|-------------|
| `W`  | Award arme (meilleur joueur avec une arme donnée) |
| `1`  | Award action joueur (`hlstats_Events_PlayerActions`) |
| `2`  | Award action joueur-joueur (`hlstats_Events_PlayerPlayerActions`) |

---

## Rubans — `/admin/ribbons`

Création et gestion des rubans (achievements visuels).

---

## Tags de clan — `/admin/clan-tags`

Gestion des patterns de détection automatique de clan dans les pseudos.

---

## Groupes d'hôtes — `/admin/host-groups`

Groupement d'IPs/plages d'IPs sous un nom d'hôte logique.

---

## Config serveur — `/admin/server-configs`

Paramètres de configuration spécifiques à un serveur.

---

## Outils — `/admin/tools`

Outils d'administration divers (à venir / selon version).
