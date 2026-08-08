#!/usr/bin/env bash
#
# Script de déploiement KardAfrica sur SiteGround
# ------
# Appelé par GitHub Actions (.github/workflows/deploy.yml) à chaque push sur main.
# Peut aussi se lancer à la main en SSH pour un deploy manuel :
#
#   ssh -i ~/.ssh/laravel-deploy -p 18765 u12-j8mjnyhzepvh@ssh.kardafrica.com
#   cd ~/htdocs/kardafrica.com && bash scripts/deploy.sh
#
# Étapes (toutes idempotentes) :
#   1. Active maintenance mode (optionnel — laissé en commentaire par défaut)
#   2. git fetch + reset --hard origin/main (= pull bulletproof, accepte les divergences)
#   3. composer install --no-dev --optimize-autoloader (deps prod uniquement)
#   4. php artisan migrate --force (--force = no prompt en non-TTY)
#   5. Cache clear (config, view, route)
#   6. Cache rebuild (config + route + view en cache pour la perf prod)
#   7. Désactive maintenance mode
#
# Arrêt en cas d'erreur (-e). Affichage de toutes les commandes (-x).
# ============================================================

set -euo pipefail

# ----- Couleurs (visibles dans les logs GitHub Actions) -----
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ----- Variables -----
COMMIT_SHA="${1:-HEAD}"
START_TIME=$(date +%s)

log() { echo -e "${BLUE}▶${NC} $*"; }
ok()  { echo -e "${GREEN}✓${NC} $*"; }
warn(){ echo -e "${YELLOW}⚠${NC} $*"; }
err() { echo -e "${RED}✗${NC} $*" >&2; }

# ----- Pré-checks -----
log "Deploy → commit ${COMMIT_SHA}"
log "Working dir : $(pwd)"
log "PHP : $(php -v | head -1)"
log "Composer : $(composer --version 2>&1 | head -1 || echo 'NOT FOUND')"

if [ ! -f "artisan" ]; then
    err "Pas d'artisan détecté — pas un projet Laravel ?"
    exit 1
fi

# ----- 1. (Optionnel) Maintenance mode pendant le deploy -----
# Décommente si tu veux que le site affiche une page "503 maintenance" pendant
# les ~30 secondes du déploiement. Sinon le site répond toujours, certains
# users peuvent toucher du code partiellement déployé.
#
# php artisan down --retry=60 --secret="deploy-bypass-token-$(date +%s)" || warn "down failed (peut-être déjà down)"

# ----- 2. Récupère le code -----
log "git fetch + reset --hard origin/main"
git fetch origin
git reset --hard origin/main
ok "Code à jour ($(git rev-parse --short HEAD))"

# ----- 3. Composer (deps prod uniquement) -----
log "composer install --no-dev --optimize-autoloader"
# --no-interaction : pas de prompts (CI safe)
# --prefer-dist    : packages depuis dist (zip) au lieu de source git → 5-10× plus rapide
# --no-dev         : exclut les deps de dev (phpunit, ide-helper, etc.) → ~30% plus léger
# --optimize-autoloader : génère un autoloader optimisé (PSR-4 résolu en lookup table)
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
ok "Dependencies installées"

# ----- 3b. Build des assets frontend (Vite) -----
# CRITIQUE : le layout principal utilise @vite(). Sans /public/build à jour,
# le rendu casse ("Vite manifest not found"). Cette étape manquait au pipeline.
if command -v npm >/dev/null 2>&1; then
  log "npm ci && npm run build (Vite)"
  npm ci --no-audit --no-fund || npm install --no-audit --no-fund
  npm run build
  # ⚠️ SiteGround : le doc root sert depuis ./build (racine), pas ./public/build
  # où Vite écrit. Sans cette synchro, le manifest pointe vers un CSS/JS absent
  # du doc root → 404 → site totalement dé-stylé.
  if [ -d public/build ]; then
    mkdir -p build
    cp -rf public/build/. build/
  fi
  ok "Assets Vite construits + synchronisés ($(ls -1 build/assets 2>/dev/null | wc -l) fichiers)"
else
  warn "npm introuvable — build Vite SAUTÉ ; /public/build doit être déployé autrement"
fi

# ----- 4. Migrations DB -----
log "php artisan migrate --force"
php artisan migrate --force
ok "Migrations OK"

# ----- 5. Cache clear (vide les anciens caches sérialisés) -----
log "Cache clear (config/view/route)"
php artisan view:clear   || warn "view:clear a échoué (ignoré)"
php artisan route:clear  || warn "route:clear a échoué (ignoré)"
php artisan config:clear || warn "config:clear a échoué (ignoré)"
ok "Caches vidés"

# ----- 6. Cache rebuild (pour la perf en prod) -----
log "Cache rebuild (config + route + view)"
# config:cache permet à Laravel de skip le parsing de tous les fichiers config
# à chaque requête (gain ~5-15ms). Idem route:cache.
# ⚠️ N'utilise PAS config:cache si tes routes ou config dépendent de closures
# non-cachables — Laravel le détectera et avortera proprement.
php artisan config:cache || warn "config:cache impossible (probablement closure dans config) — non bloquant"
php artisan route:cache  || warn "route:cache impossible (probablement closure dans routes) — non bloquant"
php artisan view:cache   || warn "view:cache a échoué"
ok "Caches reconstruits"

# ----- 7. (Optionnel) Restart queue worker -----
# Décommente si tu utilises Supervisor pour gérer un worker queue.
# Sans ça, le worker continue à tourner avec l'ancien code en mémoire.
#
# php artisan queue:restart && ok "Queue worker restart signalé"

# ----- 8. Désactive maintenance mode -----
# php artisan up || warn "up failed"

# ----- Récap -----
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
ok "Deploy terminé en ${DURATION}s"
echo ""
echo "Commit : $(git rev-parse --short HEAD)"
echo "Auteur : $(git log -1 --pretty=format:'%an <%ae>')"
echo "Date   : $(git log -1 --pretty=format:'%ad' --date=iso)"
echo "Msg    : $(git log -1 --pretty=format:'%s')"
