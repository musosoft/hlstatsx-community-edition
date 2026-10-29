# Système de Thèmes

HLStatsX CE dispose d'un système de thèmes entièrement basé sur des variables CSS et des fichiers `theme.json`.

---

## Thèmes intégrés

Situés dans `resources/themes/` — **non modifiables** via l'admin (lecture seule).

| Slug | Nom | Description |
|------|-----|-------------|
| `hlstatsx-dark` | HLStatsX Dark | Fond noir, accents bleus — thème par défaut |
| `hlstatsx-classic` | HLStatsX Classic | Style inspiré de l'ancienne version PHP, fond gris anthracite |
| `midnight-blue` | Midnight Blue | Fond bleu nuit profond |
| `carbon` | Carbon | Style carbone sombre avec accents blancs |
| `neon-green` | Neon Green | Fond noir, vert néon style terminal Matrix |
| `arctic-light` | Arctic Light | Thème clair, fond blanc/gris clair |

---

## Thèmes personnalisés

Stockés dans `storage/app/themes/` — créés, modifiés et supprimés via l'admin.

### Créer un thème custom
1. Aller dans **Admin → Thèmes**
2. Cliquer sur **Copy** sur un thème existant
3. Modifier les couleurs via l'éditeur visuel
4. Cliquer **Activate** pour l'appliquer

---

## Structure d'un `theme.json`

```json
{
  "meta": {
    "slug": "mon-theme",
    "name": "Mon Thème",
    "description": "Description courte",
    "author": "Auteur",
    "version": "1.0.0"
  },
  "colors": {
    "bg-body":            "#0d1117",
    "bg-header":          "#161b22",
    "bg-surface":         "#1c2128",
    "bg-surface-alt":     "#21262d",
    "bg-row-even":        "#1c2128",
    "bg-row-odd":         "#21262d",
    "bg-nav-tab":         "#2d333b",
    "bg-nav-tab-active":  "#1f6feb",
    "border":             "#30363d",
    "text-primary":       "#c9d1d9",
    "text-secondary":     "#8b949e",
    "text-heading":       "#f0f6fc",
    "link":               "#58a6ff",
    "link-hover":         "#79c0ff",
    "accent-primary":     "#1f6feb",
    "accent-secondary":   "#388bfd",
    "btn-gold-bg":        "#d29922",
    "btn-gold-text":      "#000000",
    "btn-green-bg":       "#238636",
    "btn-green-text":     "#ffffff",
    "activity-high":      "#3fb950",
    "activity-mid":       "#d29922",
    "activity-low":       "#f85149",
    "status-online":      "#3fb950",
    "status-offline":     "#f85149",
    "chart-line":         "#1f6feb",
    "chart-grid":         "#30363d",
    "chart-bg":           "#0d1117",
    "map-marker":         "#1f6feb"
  },
  "typography": {
    "font-family-base":   "'Inter', sans-serif",
    "font-family-mono":   "'JetBrains Mono', monospace",
    "font-size-base":     "13px",
    "font-size-sm":       "11px",
    "font-size-lg":       "15px",
    "font-weight-normal": "400",
    "font-weight-bold":   "600",
    "line-height":        "1.5"
  },
  "layout": {
    "header-height":       "56px",
    "border-radius-sm":    "4px",
    "border-radius-md":    "6px",
    "border-radius-pill":  "100px",
    "row-height":          "28px",
    "table-padding-x":     "8px",
    "table-padding-y":     "5px",
    "container-max-width": "1400px",
    "sidebar-width":       "220px"
  },
  "logo": {
    "text":      "HLSTATSX: CE",
    "color":     "#58a6ff",
    "icon-bg":   "#1f6feb",
    "show-icon": true
  },
  "header": {
    "show-social-icons": true,
    "nav-buttons": [
      { "label": "Contents", "url": "/",       "color": "#1f6feb", "text-color": "#fff" },
      { "label": "Search",   "url": "/search", "color": "#238636", "text-color": "#fff" },
      { "label": "Help",     "url": "/help",   "color": "#1f6feb", "text-color": "#fff" }
    ]
  },
  "charts": {
    "style":     "dark",
    "map-tiles": "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
  }
}
```

---

## Import / Export ZIP

### Import

```
POST /admin/themes  (multipart: file=theme.zip)
```

Le ZIP doit contenir un dossier avec un `theme.json` valide à la racine. Les thèmes intégrés ne peuvent pas être écrasés.

### Export

```
GET /admin/themes/{slug}/export
```

Télécharge le thème sous forme de ZIP.

---

## Application du thème

Les variables CSS sont injectées dynamiquement dans le `<head>` de chaque page :

```html
<style>
  :root {
    --bg-body: #0d1117;
    --accent-primary: #1f6feb;
    /* ... */
  }
</style>
```

Le thème actif est mis en cache Redis pendant **1 heure**. Pour forcer le rechargement après une modification manuelle :

```bash
php artisan cache:forget theme.active
```

---

## Référence des variables CSS

| Variable | Usage |
|----------|-------|
| `--bg-body` | Fond général de la page |
| `--bg-surface` | Fond des tableaux/panneaux |
| `--bg-surface-alt` | Fond alternatif |
| `--bg-row-even` / `--bg-row-odd` | Alternance lignes tableau |
| `--accent-primary` | Couleur principale (boutons, liens actifs) |
| `--accent-secondary` | Couleur secondaire |
| `--text-primary` | Texte principal |
| `--text-secondary` | Texte secondaire/muted |
| `--text-heading` | Titres |
| `--link` / `--link-hover` | Liens |
| `--border` | Bordures |
| `--status-online` / `--status-offline` | Indicateurs de statut |
| `--activity-high/mid/low` | Barres d'activité |
| `--chart-line` / `--chart-bg` | Graphiques Chart.js |
| `--map-marker` | Marqueurs Leaflet |
| `--font-family-base` / `--font-family-mono` | Polices |
| `--font-size-base` / `--font-size-sm` / `--font-size-lg` | Tailles de police |
| `--header-height` | Hauteur du header |
| `--border-radius-sm/md/pill` | Rayons de bordure |
| `--container-max-width` | Largeur max du contenu |
| `--sidebar-width` | Largeur de la sidebar admin |
