# API

Les endpoints API sont préfixés par `/api/v1` et retournent du JSON. Aucune authentification requise.

---

## Live Feed — `GET /api/v1/live-feed`

Retourne les derniers événements de kill en temps réel.

**Réponse exemple :**
```json
[
  {
    "killerName": "xXSniper42Xx",
    "killerId": 1234,
    "victimName": "noob_player",
    "victimId": 5678,
    "weapon": "awp",
    "headshot": true,
    "map": "de_dust2",
    "server": "RMG CS:GO #1",
    "time": "2025-01-15 14:32:01"
  }
]
```

---

## Graphique d'activité — `GET /api/v1/chart/activity/{serverId}`

Données d'activité (kills par heure) pour un serveur donné, utilisées par Chart.js.

**Paramètres :**

| Paramètre | Type | Description |
|-----------|------|-------------|
| `serverId` | int (path) | ID du serveur |

**Réponse exemple :**
```json
{
  "labels": ["00:00", "01:00", "02:00", "..."],
  "kills":  [12, 45, 78, "..."]
}
```

---

## Marqueurs de carte — `GET /api/v1/map/markers`

Liste des serveurs géolocalisés pour la carte Leaflet.

**Réponse exemple :**
```json
[
  {
    "lat": 48.8566,
    "lng": 2.3522,
    "name": "RMG CS:GO #1",
    "address": "192.168.1.10:27015",
    "online": true
  }
]
```

> Ce endpoint est également accessible à `GET /maps/markers` (alias frontend).

---

## Statut d'un serveur — `GET /servers/{id}/status`

> Voir aussi la section [Pages Frontend](Frontend-Pages#statut-json----serversidstatus).

```json
{
  "online": true,
  "act_players": 12,
  "max_players": 24,
  "act_map": "de_dust2"
}
```
