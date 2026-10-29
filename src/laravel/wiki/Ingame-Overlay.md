# Overlay In-game

L'interface in-game permet d'afficher des statistiques directement dans le MOTD (Message Of The Day) ou le HUD des serveurs de jeu, via une page HTML minimaliste sans styles complexes.

Toutes les routes sont préfixées par `/ingame/`.

---

## Routes disponibles

| URL | Description |
|-----|-------------|
| `/ingame/players` | Top 20 joueurs (pseudo, K, D, Skill) |
| `/ingame/clans` | Top 20 clans (tag, nom, skill moyen) |
| `/ingame/maps` | Top 20 maps (map, kills) |
| `/ingame/servers` | Liste des serveurs (nom, map, joueurs) |
| `/ingame/weapons` | Top 20 armes (nom, kills) |
| `/ingame/statsme` | Stats personnelles du joueur (via IP ou SteamID) |
| `/ingame/motd` | Message MOTD générique |

---

## Paramètres communs

| Paramètre | Description |
|-----------|-------------|
| `game` | Filtrer par code de jeu (ex : `?game=cstrike`) |

---

## Page `statsme` — `/ingame/statsme`

Affiche les statistiques du joueur actuel.

| Champ affiché | Description |
|---------------|-------------|
| Nom | `lastName` |
| Kills | Total de kills |
| Deaths | Total de morts |
| K/D | Ratio kills/deaths |
| Skill | Score de skill actuel |
| Headshots | Nombre de headshots |

Un lien "Full Profile" pointe vers le profil complet sur le site.

---

## Layout

Le layout `ingame` (`resources/views/layouts/ingame.blade.php`) utilise un HTML minimaliste :
- Pas de JavaScript lourd
- CSS inline minimal
- Colonnes numériques alignées à droite (`.num`)
- Compatible avec les navigateurs embarqués dans les jeux Source

---

## Exemple de configuration serveur (CSGO/CSS)

Dans la config du plugin HLStatsX Perl, pointer le MOTD vers :

```
motdfile = "http://stats.example.com/ingame/motd?game=cstrike"
```

Pour les stats personnelles dans le chat (commande `!statsme`) :

```
statsme_url = "http://stats.example.com/ingame/statsme?game=cstrike"
```
