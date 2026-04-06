#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    cat <<'EOF'
Usage: ./deploy.sh [options]

Options:
  --no-migrate      Skip "php artisan migrate --force"
  --php BIN         PHP binary to use (default: php8.2)
  --help            Show this help

Environment variables:
  LARAVEL_DIR       Laravel app directory (default: script directory)
  PUBLIC_HTML_DIR   Public document root (default: ../public_html from LARAVEL_DIR)
  COMPOSER_BIN      Composer binary/path (default: ~/composer2.phar, fallback: composer)
EOF
}

log() {
    printf '[deploy] %s\n' "$*"
}

fail() {
    printf '[deploy] ERROR: %s\n' "$*" >&2
    exit 1
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "Command not found: $1"
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LARAVEL_DIR="${LARAVEL_DIR:-$SCRIPT_DIR}"
PUBLIC_HTML_DIR="${PUBLIC_HTML_DIR:-$(cd "$LARAVEL_DIR/.." && pwd)/public_html}"
PHP_BIN="${PHP_BIN:-php8.2}"
RUN_MIGRATIONS=1

while [[ $# -gt 0 ]]; do
    case "$1" in
        --no-migrate)
            RUN_MIGRATIONS=0
            shift
            ;;
        --php)
            [[ $# -ge 2 ]] || fail "Missing value after --php"
            PHP_BIN="$2"
            shift 2
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            fail "Unknown option: $1"
            ;;
    esac
done

require_cmd "$PHP_BIN"
require_cmd chmod
require_cmd find
require_cmd ln

if [[ ! -d "$LARAVEL_DIR" ]]; then
    fail "Laravel directory does not exist: $LARAVEL_DIR"
fi

if [[ ! -f "$LARAVEL_DIR/artisan" ]]; then
    fail "artisan not found in: $LARAVEL_DIR"
fi

if [[ ! -d "$PUBLIC_HTML_DIR" ]]; then
    fail "public_html directory does not exist: $PUBLIC_HTML_DIR"
fi

if [[ -n "${COMPOSER_BIN:-}" ]]; then
    COMPOSER_CMD=("$COMPOSER_BIN")
elif [[ -f "$HOME/composer2.phar" ]]; then
    COMPOSER_CMD=("$PHP_BIN" "$HOME/composer2.phar")
elif command -v composer >/dev/null 2>&1; then
    COMPOSER_CMD=("composer")
else
    fail "Composer not found. Set COMPOSER_BIN or install composer2.phar in HOME."
fi

if command -v rsync >/dev/null 2>&1; then
    SYNC_PUBLIC_CMD=(rsync -a --delete --exclude=index.php "$LARAVEL_DIR/public/" "$PUBLIC_HTML_DIR/")
else
    fail "rsync is required for syncing public assets (install rsync on host)."
fi

log "Laravel dir: $LARAVEL_DIR"
log "Public dir:  $PUBLIC_HTML_DIR"
log "PHP bin:     $PHP_BIN"

if [[ ! -f "$LARAVEL_DIR/.env" ]]; then
    if [[ -f "$LARAVEL_DIR/.env.example" ]]; then
        log "Creating .env from .env.example"
        cp "$LARAVEL_DIR/.env.example" "$LARAVEL_DIR/.env"
    else
        fail ".env missing and .env.example not found"
    fi
fi

pushd "$LARAVEL_DIR" >/dev/null

log "Installing PHP dependencies"
"${COMPOSER_CMD[@]}" install --no-dev --optimize-autoloader

if ! grep -q '^APP_KEY=base64:' .env; then
    log "Generating APP_KEY"
    "$PHP_BIN" artisan key:generate --force
fi

log "Clearing Laravel caches"
"$PHP_BIN" artisan optimize:clear

if [[ "$RUN_MIGRATIONS" -eq 1 ]]; then
    log "Running migrations"
    "$PHP_BIN" artisan migrate --force
else
    log "Skipping migrations (--no-migrate)"
fi

log "Syncing public/ to public_html/ (excluding index.php)"
"${SYNC_PUBLIC_CMD[@]}"

if [[ ! -f "$PUBLIC_HTML_DIR/index.php" ]]; then
    log "Creating public_html/index.php"
    cp "$LARAVEL_DIR/public/index.php" "$PUBLIC_HTML_DIR/index.php"
fi

log "Patching public_html/index.php for split layout"
sed -i "s|__DIR__.'/../vendor/autoload.php'|__DIR__.'/../laravel/vendor/autoload.php'|g" "$PUBLIC_HTML_DIR/index.php"
sed -i "s|__DIR__.'/../bootstrap/app.php'|__DIR__.'/../laravel/bootstrap/app.php'|g" "$PUBLIC_HTML_DIR/index.php"

if ! grep -q "__DIR__.'/../laravel/vendor/autoload.php'" "$PUBLIC_HTML_DIR/index.php"; then
    fail "index.php autoload path was not patched correctly"
fi

if ! grep -q "__DIR__.'/../laravel/bootstrap/app.php'" "$PUBLIC_HTML_DIR/index.php"; then
    fail "index.php bootstrap path was not patched correctly"
fi

log "Linking storage to document root"
ln -sfn "$LARAVEL_DIR/storage/app/public" "$PUBLIC_HTML_DIR/storage"

log "Setting permissions"
chmod -R 775 "$LARAVEL_DIR/storage" "$LARAVEL_DIR/bootstrap/cache"

if [[ -d "$PUBLIC_HTML_DIR/build" ]]; then
    find "$PUBLIC_HTML_DIR/build" -type d -exec chmod 755 {} +
    find "$PUBLIC_HTML_DIR/build" -type f -exec chmod 644 {} +
fi

log "Optimizing Laravel"
"$PHP_BIN" artisan optimize

popd >/dev/null

log "Done."
log "Check: $PUBLIC_HTML_DIR/build/manifest.json and open /admin"
