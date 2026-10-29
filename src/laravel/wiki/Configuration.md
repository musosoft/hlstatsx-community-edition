# Configuration

Toutes les options se configurent dans le fichier `.env` à la racine du projet.

---

## Base de données

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hlstats          # Nom de la base HLStatsX existante
DB_USERNAME=hlstats
DB_PASSWORD=secret
```

---

## Redis (cache & sessions)

```dotenv
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
CACHE_STORE=redis
SESSION_DRIVER=redis
```

---

## Steam API

Requis pour la synchronisation des avatars et pseudos Steam.

```dotenv
STEAM_API_KEY=VOTRE_CLE_STEAM
```

Sans clé, le système tente un fallback via le profil XML public (pas d'authentification nécessaire, mais limité).

---

## GeoIP (MaxMind)

```dotenv
GEOIP_DB_PATH=/chemin/vers/GeoLite2-Country.mmdb
GEOIP_CITY_DB_PATH=/chemin/vers/GeoLite2-City.mmdb
```

Les fichiers `.mmdb` sont fournis dans `database/maxmind/` ou téléchargeables depuis MaxMind.
Mise à jour automatique mensuelle via la commande `location:update`.

---

## Options du site

```dotenv
HLSTATS_SITE_NAME="HLstatsX | My Community"
HLSTATS_HISTORY_DAYS=28      # Nombre de jours d'historique affiché
```

Le nom du site peut aussi être défini via **Admin → Options**.

---

## Application

```dotenv
APP_NAME="HLStatsX CE"
APP_ENV=production            # local | production
APP_DEBUG=false               # Mettre true uniquement en développement
APP_URL=https://stats.example.com
APP_LOCALE=fr                 # Langue par défaut (en | fr)
```

---

## Mail (optionnel)

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
```

---

## Optimisation en production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Pour vider le cache :

```bash
php artisan optimize:clear
```
