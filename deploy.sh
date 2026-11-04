#!/usr/bin/env bash
#
# Deploy RSC20 Register to HestiaCP (rusf20.sru.ac.th).
#
# Usage (on the server, as user rusf20):
#   bash deploy.sh            # deploy branch main
#   bash deploy.sh my-branch  # deploy another branch
#
# Optional environment overrides:
#   PHP_BIN=/usr/bin/php8.4 COMPOSER_BIN=/usr/local/bin/composer bash deploy.sh
#   (composer is always run as "$PHP_BIN composer", so it uses the same PHP as artisan)
#   CLEAN_NODE_MODULES=1 bash deploy.sh   # delete node_modules after building to save disk space
#
# ---------------------------------------------------------------------------
# One-time setup
# ---------------------------------------------------------------------------
# 1. Hestia > Web > rusf20.sru.ac.th > Edit > Advanced options:
#      - Web template (nginx / apache): "laravel"  -> docroot becomes public_html/public
#      - Backend template / PHP-FPM: PHP-8_4 or newer (composer.lock needs PHP >= 8.4.1;
#        install it with: v-add-web-php 8.4)
#      - Enable SSL + Let's Encrypt
# 2. Hestia > Users > rusf20 > SSH access: bash
#    If the GitHub repo is private, add the server's SSH public key as a deploy key
#    and clone with git@github.com:pdnb/RSC20-Register.git instead.
# 3. Hestia > DB: create a MySQL/MariaDB database and user.
# 4. Over SSH:
#      cd /home/rusf20/web/rusf20.sru.ac.th/public_html
#      rm -f index.html robots.txt          # Hestia's default files (folder must be empty)
#      git clone https://github.com/pdnb/RSC20-Register.git .
#      cp .env.example .env
#      # edit .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://rusf20.sru.ac.th,
#      #            DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD,
#      #            Auth0 credentials (callback: {APP_URL}/admin/auth/auth0/callback)
#      php8.4 $(command -v composer) install --no-dev && php8.4 artisan key:generate
# 5. Make sure Node.js >= 20 and npm are installed (Vite 8 needs them).
# 6. bash deploy.sh
# ---------------------------------------------------------------------------

set -euo pipefail

APP_DIR="/home/rusf20/web/rusf20.sru.ac.th/public_html"
BRANCH="${1:-main}"
PHP="${PHP_BIN:-$(command -v php8.5 || command -v php8.4 || command -v php)}"
COMPOSER_PHAR="${COMPOSER_BIN:-$(command -v composer)}"
MIN_PHP_VERSION="8.4.1"

# Run composer with the same PHP binary as artisan, not whatever the composer
# wrapper's shebang points to (on Hestia that is often the system php8.3).
composer_run() {
    "$PHP" "$COMPOSER_PHAR" "$@"
}

step() {
    printf '\n\033[1;34m==> %s\033[0m\n' "$1"
}

cd "$APP_DIR"

if [[ ! -f .env ]]; then
    echo "ERROR: $APP_DIR/.env not found. Copy .env.example to .env and fill in production values first." >&2
    exit 1
fi

if [[ ! -d .git ]]; then
    echo "ERROR: $APP_DIR is not a git repository. Clone the repo into it first (see one-time setup above)." >&2
    exit 1
fi

if ! "$PHP" -r "exit(version_compare(PHP_VERSION, '$MIN_PHP_VERSION', '>=') ? 0 : 1);"; then
    echo "ERROR: $PHP is PHP $("$PHP" -r 'echo PHP_VERSION;'), but composer.lock needs PHP >= $MIN_PHP_VERSION." >&2
    echo "       Install PHP 8.4 in Hestia (v-add-web-php 8.4), switch the domain's backend to PHP-8_4," >&2
    echo "       then run: PHP_BIN=/usr/bin/php8.4 bash deploy.sh" >&2
    exit 1
fi

site_is_down=0

bring_site_up() {
    if [[ "$site_is_down" -eq 1 ]]; then
        echo "Deploy failed - bringing the site back up." >&2
        "$PHP" artisan up || true
    fi
}

trap bring_site_up ERR

step "Maintenance mode on"
if "$PHP" artisan down --retry=60; then
    site_is_down=1
fi

step "Pull latest code ($BRANCH)"
git fetch --prune origin "$BRANCH"
git reset --hard "origin/$BRANCH"

step "Install PHP dependencies"
composer_run install --no-dev --prefer-dist --optimize-autoloader --no-interaction

step "Build frontend assets"
npm ci --no-audit --no-fund
npm run build
if [[ "${CLEAN_NODE_MODULES:-0}" == "1" ]]; then
    rm -rf node_modules
fi

step "Publish WireKit assets"
"$PHP" artisan vendor:publish --tag=wirekit-assets --force --no-interaction
"$PHP" artisan wirekit:publish-fonts --no-interaction

step "Storage link"
if [[ ! -e public/storage ]]; then
    "$PHP" artisan storage:link --no-interaction
fi

step "Run migrations"
"$PHP" artisan migrate --force --no-interaction

step "Rebuild caches"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan queue:restart

step "Fix permissions"
chmod -R ug+rwX storage bootstrap/cache

step "Maintenance mode off"
"$PHP" artisan up
site_is_down=0

step "Deployed $(git rev-parse --short HEAD) - $(git log -1 --pretty=%s)"
