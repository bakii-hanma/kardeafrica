# Setup CI/CD GitHub Actions → SiteGround

Guide pour activer le déploiement automatique de KardAfrica sur SiteGround à chaque push sur `main`.

## Architecture

```
git push main
   ↓
GitHub Actions runner (Ubuntu)
   ↓ (SSH avec clé privée)
SiteGround (ssh.kardafrica.com:18765)
   ↓ (exécute scripts/deploy.sh)
git pull + composer install + migrate + cache clear
```

Workflow : [.github/workflows/deploy.yml](workflows/deploy.yml)
Script serveur : [scripts/deploy.sh](../scripts/deploy.sh)

## Setup initial (à faire UNE seule fois)

### 1. Génère une clé SSH dédiée au déploiement

Sur ta machine locale :

```bash
ssh-keygen -t ed25519 -f ~/.ssh/kardafrica-deploy -C "github-actions-deploy" -N ""
```

Tu obtiens :
- `~/.ssh/kardafrica-deploy`     → **clé privée** (à mettre dans GitHub Secrets, NE JAMAIS commit)
- `~/.ssh/kardafrica-deploy.pub` → **clé publique** (à autoriser sur SiteGround)

### 2. Autorise la clé publique sur SiteGround

Copie le contenu de `~/.ssh/kardafrica-deploy.pub` :

```bash
cat ~/.ssh/kardafrica-deploy.pub
```

Connecte-toi à SiteGround → **Site Tools** → **Devs** → **SSH Keys Manager** → **Import** → colle la clé publique.

Vérifie la connexion :

```bash
ssh -i ~/.ssh/kardafrica-deploy -p 18765 u12-j8mjnyhzepvh@ssh.kardafrica.com 'echo OK'
```

### 3. Configure le repo serveur (1ère fois uniquement)

Si le code n'est pas encore un repo git sur SiteGround :

```bash
ssh -i ~/.ssh/kardafrica-deploy -p 18765 u12-j8mjnyhzepvh@ssh.kardafrica.com
cd ~/htdocs/kardafrica.com   # adapte au chemin réel
git init
git remote add origin git@github.com:bakii-hanma/kardafrica-app.git   # (ou HTTPS si pas de clé deploy GitHub→SG)
git fetch origin
git reset --hard origin/main
```

Vérifie que `scripts/deploy.sh` est présent et exécutable :

```bash
ls -la scripts/deploy.sh
chmod +x scripts/deploy.sh
bash scripts/deploy.sh   # test manuel
```

### 4. Configure les secrets GitHub

Va sur GitHub → ton repo → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**.

Crée ces 5 secrets :

| Nom               | Valeur                                              |
|-------------------|-----------------------------------------------------|
| `SSH_HOST`        | `ssh.kardafrica.com`                                |
| `SSH_PORT`        | `18765`                                             |
| `SSH_USER`        | `u12-j8mjnyhzepvh` (ton user SiteGround)            |
| `SSH_PRIVATE_KEY` | Contenu **entier** de `~/.ssh/kardafrica-deploy` (de `-----BEGIN OPENSSH PRIVATE KEY-----` jusqu'à `-----END OPENSSH PRIVATE KEY-----` inclus) |
| `DEPLOY_PATH`     | `~/htdocs/kardafrica.com` (chemin absolu, sans slash final) |

Pour récupérer la clé privée :

```bash
cat ~/.ssh/kardafrica-deploy
```

⚠️ Copie tout, y compris les lignes `-----BEGIN ...-----` et `-----END ...-----`. Pas d'espace en début/fin.

### 5. Active le workflow

Push le code (workflow + script) :

```bash
git add .github/workflows/deploy.yml scripts/deploy.sh
git commit -m "ci: GitHub Actions deploy to SiteGround"
git push origin main
```

Le 1er run va se déclencher automatiquement. Vérifie sur GitHub → **Actions** → tu vois le run en cours.

## Utilisation quotidienne

À partir de maintenant, chaque `git push origin main` déclenche le deploy. Tu peux suivre les logs en temps réel sur GitHub → **Actions**.

### Déclencher un deploy manuel (sans push)

GitHub → **Actions** → **Deploy to SiteGround** → bouton **Run workflow** → branche `main` → **Run**.

Utile pour rejouer un deploy si quelque chose a foiré sans avoir à faire un commit vide.

### Déclencher un deploy depuis ta machine

Pas besoin de GitHub Actions, tu peux SSH directement :

```bash
ssh -i ~/.ssh/kardafrica-deploy -p 18765 u12-j8mjnyhzepvh@ssh.kardafrica.com
cd ~/htdocs/kardafrica.com
bash scripts/deploy.sh
```

## Customisations courantes

### Activer le mode maintenance pendant le deploy

Dans `scripts/deploy.sh`, décommente :

```bash
php artisan down --retry=60 --secret="..."
# ...
php artisan up
```

Le site répondra 503 pendant ~30s mais aucun user ne touchera du code partiellement déployé.

### Restart d'un queue worker

Si tu as Supervisor qui tourne un `queue:work` :

```bash
php artisan queue:restart
```

(décommente dans `scripts/deploy.sh`)

### Ajouter des tests avant le deploy

Édite `.github/workflows/deploy.yml` et ajoute avant le job `deploy` :

```yaml
test:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with: { php-version: '8.2' }
    - run: composer install --no-interaction --prefer-dist
    - run: cp .env.example .env && php artisan key:generate
    - run: php artisan test

deploy:
  needs: test  # ← bloque le deploy si test échoue
  ...
```

## Troubleshooting

### "Permission denied (publickey)" dans GHA

La clé privée dans `SSH_PRIVATE_KEY` n'a pas été copiée correctement, ou la clé publique correspondante n'est pas autorisée sur SiteGround.

```bash
# Vérifie depuis ta machine que tu peux te connecter avec cette clé
ssh -i ~/.ssh/kardafrica-deploy -p 18765 u12-j8mjnyhzepvh@ssh.kardafrica.com 'whoami'
```

Si ça marche localement mais pas en GHA, recopie le contenu de la clé privée dans le secret (souvent un retour à la ligne manquant en début/fin).

### "Host key verification failed"

Le `ssh-keyscan` du workflow récupère normalement le host key. Si SiteGround a changé sa clé, le run plante. Solution : relance le workflow, le keyscan se refera.

### Le deploy passe mais le site sert l'ancienne version

Les caches OPcache de PHP ne sont pas vidés par les commandes artisan. Sur SiteGround :

```bash
# Dans scripts/deploy.sh, ajoute après le cache rebuild :
php -r "if (function_exists('opcache_reset')) opcache_reset();"
```

Ou via Site Tools → **Speed** → **Caching** → **Dynamic Cache** → Flush.

### Migration qui plante en prod (rollback)

Le deploy s'arrête (set -e). Le serveur est dans un état partiellement déployé : le code peut être à jour mais pas les migrations. Pour rollback manuel :

```bash
ssh -i ~/.ssh/kardafrica-deploy -p 18765 u12-j8mjnyhzepvh@ssh.kardafrica.com
cd ~/htdocs/kardafrica.com
git log --oneline -5             # voir les derniers commits
git reset --hard <commit-précédent>
php artisan migrate:rollback     # si une migration a partiellement passé
php artisan cache:clear
```
