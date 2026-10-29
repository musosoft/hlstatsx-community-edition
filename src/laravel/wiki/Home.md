# HLStatsX Community Edition — Laravel 13 Rebase

Bienvenue sur le wiki de **HLStatsX Community Edition — Laravel 13 Rebase**.

Ce projet est une réécriture complète du frontend PHP 5 de HLStatsX:CE vers **Laravel 13**, en conservant le schéma MySQL et le daemon Perl d'origine sans aucune modification.

> **Daemon Perl :** les scripts Perl utilisés sont ceux de [SnipeZilla/HLSTATS-2](https://github.com/SnipeZilla/HLSTATS-2).

---

## Stack technique

| Couche        | Technologie                                    |
|---------------|------------------------------------------------|
| Framework     | Laravel 13 (PHP 8.2+)                          |
| Frontend      | Blade + Vite + TailwindCSS v4 + Alpine.js      |
| Charts        | Chart.js                                       |
| Cartes        | Leaflet.js + OpenStreetMap                     |
| Auth          | Laravel Breeze (guard `admin` sur `hlstats_Admins`) |
| Cache         | Redis (predis)                                 |
| Queue         | Database                                       |
| GeoIP         | MaxMind GeoLite2 (geoip2/geoip2)               |
| i18n          | Fichiers JSON (EN / FR), détection auto        |

---

## Pages du wiki

| Page | Description |
|------|-------------|
| [Installation](Installation) | Prérequis, installation pas à pas |
| [Configuration](Configuration) | Variables `.env`, options importantes |
| [Architecture](Architecture) | Structure du projet, services, modèles |
| [Pages Frontend](Frontend-Pages) | Toutes les pages publiques |
| [Panneau Admin](Admin-Panel) | Gestion via `/admin` |
| [Système de Thèmes](Themes) | Thèmes intégrés, personnalisation, JSON |
| [Commandes Artisan](Artisan-Commands) | Tâches planifiées et commandes CLI |
| [API](API) | Endpoints JSON internes |
| [Overlay In-game](Ingame-Overlay) | Interface MOTD/HUD pour les serveurs |

---

## Principe fondamental

> Ce projet **ne modifie jamais** le schéma HLStatsX. Toutes les tables `hlstats_*` et leurs colonnes sont utilisées telles quelles.
