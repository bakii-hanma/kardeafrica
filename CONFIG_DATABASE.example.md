# Configuration Base de Données MySQL — Kardafrica (TEMPLATE)

> ⚠️ Ne mettez JAMAIS de vrais identifiants dans ce fichier ni dans le dépôt.
> Les secrets vont uniquement dans `.env` (déjà ignoré par git). Ce fichier
> d'exemple ne contient que des valeurs fictives.

## Informations de Connexion

```
Host: <DB_HOST>
Port: 3306
Database: <DB_DATABASE>
Username: <DB_USERNAME>
Password: <DB_PASSWORD>
```

## Configuration du fichier .env

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:VOTRE_CLÉ_ICI   # Générez avec: php artisan key:generate
APP_URL=https://votre-domaine.com

# Base de données MySQL
DB_CONNECTION=mysql
DB_HOST=<DB_HOST>
DB_PORT=3306
DB_DATABASE=<DB_DATABASE>
DB_USERNAME=<DB_USERNAME>
DB_PASSWORD=<DB_PASSWORD>

# Sessions / cache / files d'attente
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail (récupération de mot de passe)
MAIL_MAILER=smtp
MAIL_HOST=<MAIL_HOST>
MAIL_PORT=587
MAIL_USERNAME=<MAIL_USERNAME>
MAIL_PASSWORD=<MAIL_PASSWORD>
MAIL_ENCRYPTION=tls

# Logs
LOG_LEVEL=warning
```

## Étapes de Configuration

1. Copier ce template vers `.env` et remplir les vraies valeurs (jamais commitées).
2. `php artisan key:generate`
3. `php artisan migrate --force`
4. `php artisan storage:link`
5. Production : `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## Sécurité

- Restreindre l'accès MySQL aux IP du serveur applicatif.
- Utiliser un mot de passe d'application dédié pour le SMTP (jamais le mot de passe du compte).
- Faire tourner (rotation) tout secret qui aurait été exposé.
