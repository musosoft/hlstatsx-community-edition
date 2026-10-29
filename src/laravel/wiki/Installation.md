# Installation

## Prérequis

| Logiciel   | Version minimale |
|------------|-----------------|
| PHP        | 8.2             |
| Composer   | 2.x             |
| Node.js    | 18+             |
| MySQL      | 5.7+ (ou MariaDB 10.3+) |
| Redis      | 6+              |

### Extensions PHP

Outre celles requises par Laravel, l'extension **GD compilée avec FreeType** est
nécessaire pour générer les images de signature de forum
(`/players/{id}/signature.png`). Sans elle, cette route répond `503` ; le reste du
site fonctionne normalement.

```bash
# Debian / Ubuntu
sudo apt install php8.3-gd && sudo systemctl reload php8.3-fpm

# Vérification
php -r "var_dump(extension_loaded('gd'), gd_info()['FreeType Support']);"
```

La base de données doit déjà exister et contenir les tables `hlstats_*` générées par le daemon Perl (voir [SnipeZilla/HLSTATS-2](https://github.com/SnipeZilla/HLSTATS-2)).

---

## Installation

```bash
# 1. Cloner le dépôt
git clone https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel.git
cd hlstatsx-community-edition

# 2. Dépendances PHP
composer install --no-dev --optimize-autoloader

# 3. Dépendances JavaScript
npm install

# 4. Fichier d'environnement
cp .env.example .env
php artisan key:generate

# 5. Éditer .env (voir page Configuration)
nano .env

# 6. Build des assets
npm run build

# 7. Liaison du storage (si nécessaire)
php artisan storage:link

# 8. Migrations Laravel (obligatoire)
php artisan migrate

# 9. Sceller l'assistant d'installation (obligatoire)
echo "APP_INSTALLED=true" >> .env
php artisan config:clear
```

> **Sécurité — étape 9 obligatoire.** Les routes `/install/*` créent un compte
> superadmin et réécrivent les identifiants de base de données dans `.env`, sans
> aucune authentification. Tant que `APP_INSTALLED=true` n'est pas positionné,
> l'application les scelle en repli en vérifiant la présence d'un compte dans
> `hlstats_Admins` — mais ne comptez pas dessus : positionnez la variable.
>
> Vérification : `curl -o /dev/null -w '%{http_code}' https://votre-site/install`
> doit répondre `404`.

> **Important** : l'étape `migrate` initialise la version de l'application dans `hlstats_Options` (clé `version`). Sans cette étape, le panneau d'administration ne pourra pas détecter les mises à jour disponibles.

---

## Migration de mot de passe admin

Les comptes admin sont stockés dans `hlstats_Admins`. Lors de la première connexion avec un ancien compte `hlstats_Users` (mot de passe MD5), le système propose une migration automatique vers bcrypt :

```
/admin/migrate-password
```

---

## Planificateur cron

Ajouter dans le crontab du serveur :

```cron
* * * * * cd /var/www/hlstatsx && php artisan schedule:run >> /dev/null 2>&1
```

---

## Serveur de développement

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (hot reload)
npm run dev
```

---

## Mises à jour

Lors de chaque nouvelle version :

```bash
# 1. Extraire l'archive de la release et remplacer les fichiers
# 2. Mettre à jour les dépendances PHP
composer install --no-dev --optimize-autoloader

# 3. Appliquer les migrations (met à jour la version dans hlstats_Options)
php artisan migrate

# 4. Rebuilder les assets
npm run build

# 5. Vider les caches
php artisan optimize:clear
php artisan optimize
```

> Chaque release inclut une migration qui met à jour la clé `version` dans `hlstats_Options`. Le tableau de bord admin compare automatiquement cette valeur avec la dernière release GitHub.

---

## Vérification post-installation

| URL | Vérification |
|-----|-------------|
| `/` | Page d'accueil avec stats globales |
| `/players` | Liste des joueurs classés |
| `/admin` | Panneau d'administration |
| `/admin/login` | Authentification admin |
