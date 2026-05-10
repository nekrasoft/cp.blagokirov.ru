#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    cat <<'EOF'
Usage: ./deploy.sh [options]

Options:
  --no-migrate      Skip "php artisan migrate --force"
  --skip-asset-check
                    Skip built asset freshness check
  --php BIN         PHP binary to use (default: php8.2)
  --help            Show this help

Environment variables:
  LARAVEL_DIR       Laravel app directory (default: script directory)
  PUBLIC_HTML_DIR   Public document root (default: ../public_html from LARAVEL_DIR)
  COMPOSER_BIN      Composer binary/path (default: ~/composer2.phar, fallback: composer)
  SKIP_ASSET_CHECK  Set to 1 to skip built asset freshness check
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

build_source_files() {
    (
        cd "$LARAVEL_DIR"

        # Deployment-only files such as deploy.sh are intentionally excluded
        # because they do not change generated Vite/Tailwind assets.
        find app/Filament resources/css resources/js resources/views \
            -type f \
            ! -path '*/storage/framework/views/*' \
            ! -path '*/public/build/*' \
            -print 2>/dev/null || true

        printf '%s\n' package.json vite.config.js

        if [[ -f package-lock.json ]]; then
            if command -v git >/dev/null 2>&1 && git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
                git ls-files --error-unmatch package-lock.json >/dev/null 2>&1 && printf '%s\n' package-lock.json
            else
                printf '%s\n' package-lock.json
            fi
        fi
    ) | sort
}

calculate_normalized_file_hash() {
    "$PHP_BIN" -r 'echo str_replace(["\r\n", "\r"], "\n", file_get_contents($argv[1]));' "$1" \
        | sha256sum \
        | sed 's/[[:space:]].*$//'
}

calculate_build_source_fingerprint() {
    local file

    build_source_files | while IFS= read -r file; do
        [[ -f "$LARAVEL_DIR/$file" ]] || continue

        printf '%s  %s\n' "$(calculate_normalized_file_hash "$LARAVEL_DIR/$file")" "$file"
    done | sha256sum | sed 's/[[:space:]].*$//'
}

read_stored_build_source_fingerprint() {
    local fingerprint_file="$1"

    sed -n 's/^[[:space:]]*"fingerprint":[[:space:]]*"\([a-f0-9]\{64\}\)",\{0,1\}[[:space:]]*$/\1/p' "$fingerprint_file" | sed -n '1p'
}

check_built_assets_current() {
    local fingerprint_file="$1"
    local stored_fingerprint
    local current_fingerprint

    if [[ ! -f "$fingerprint_file" ]]; then
        fail "$(basename "$fingerprint_file") is missing. Run npm run build locally, commit/deploy updated public/build assets, then rerun deploy."
    fi

    stored_fingerprint="$(read_stored_build_source_fingerprint "$fingerprint_file")"
    [[ -n "$stored_fingerprint" ]] || fail "Could not read build source fingerprint from ${fingerprint_file#$LARAVEL_DIR/}. Run npm run build locally."

    current_fingerprint="$(calculate_build_source_fingerprint)"

    if [[ "$stored_fingerprint" != "$current_fingerprint" ]]; then
        log "Stored build fingerprint:  $stored_fingerprint"
        log "Current build fingerprint: $current_fingerprint"

        if command -v git >/dev/null 2>&1 && git -C "$LARAVEL_DIR" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
            local git_status

            git_status="$(git -C "$LARAVEL_DIR" status --short -- \
                app/Filament \
                resources/css \
                resources/js \
                resources/views \
                package.json \
                package-lock.json \
                vite.config.js \
                public/build/build-fingerprint.json \
                public/build/manifest.json \
                | sed -n '1,40p')"

            if [[ -n "$git_status" ]]; then
                log "Git status for build inputs:"
                printf '%s\n' "$git_status" | sed 's/^/  /' >&2
            else
                log "Git status for build inputs is clean"
            fi
        fi

        fail "Built assets fingerprint is stale. Run npm run build locally, commit/deploy updated public/build assets, then rerun deploy. Use --skip-asset-check only for emergency PHP-only deploys."
    fi

    log "Built assets fingerprint is current"
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LARAVEL_DIR="${LARAVEL_DIR:-$SCRIPT_DIR}"
PUBLIC_HTML_DIR="${PUBLIC_HTML_DIR:-$(cd "$LARAVEL_DIR/.." && pwd)/public_html}"
PHP_BIN="${PHP_BIN:-php8.2}"
RUN_MIGRATIONS=1
RUN_ASSET_CHECK=1
BUILD_MANIFEST_RELATIVE_PATH="public/build/manifest.json"
BUILD_FINGERPRINT_RELATIVE_PATH="public/build/build-fingerprint.json"
TAILADMIN_THEME_ENTRY="resources/css/filament/tailadmin/theme.css"

if [[ "${SKIP_ASSET_CHECK:-0}" == "1" ]]; then
    RUN_ASSET_CHECK=0
fi

while [[ $# -gt 0 ]]; do
    case "$1" in
        --no-migrate)
            RUN_MIGRATIONS=0
            shift
            ;;
        --skip-asset-check)
            RUN_ASSET_CHECK=0
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
require_cmd grep
require_cmd ln
require_cmd sed
require_cmd sha256sum

if [[ ! -d "$LARAVEL_DIR" ]]; then
    fail "Laravel directory does not exist: $LARAVEL_DIR"
fi

if [[ ! -f "$LARAVEL_DIR/artisan" ]]; then
    fail "artisan not found in: $LARAVEL_DIR"
fi

if [[ ! -d "$PUBLIC_HTML_DIR" ]]; then
    fail "public_html directory does not exist: $PUBLIC_HTML_DIR"
fi

BUILD_MANIFEST="$LARAVEL_DIR/$BUILD_MANIFEST_RELATIVE_PATH"
BUILD_FINGERPRINT="$LARAVEL_DIR/$BUILD_FINGERPRINT_RELATIVE_PATH"

if [[ ! -f "$BUILD_MANIFEST" ]]; then
    fail "$BUILD_MANIFEST_RELATIVE_PATH is missing. Run npm run build locally and deploy built assets."
fi

if ! grep -q "$TAILADMIN_THEME_ENTRY" "$BUILD_MANIFEST"; then
    fail "TailAdmin Filament theme is missing from $BUILD_MANIFEST_RELATIVE_PATH. Run npm run build locally."
fi

if [[ "$RUN_ASSET_CHECK" -eq 1 ]]; then
    check_built_assets_current "$BUILD_FINGERPRINT"
else
    log "Skipping built asset freshness check"
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

PUBLIC_BUILD_MANIFEST="$PUBLIC_HTML_DIR/build/manifest.json"

if [[ ! -f "$PUBLIC_BUILD_MANIFEST" ]]; then
    fail "public_html/build/manifest.json is missing after public asset sync"
fi

if ! grep -q "$TAILADMIN_THEME_ENTRY" "$PUBLIC_BUILD_MANIFEST"; then
    fail "TailAdmin Filament theme is missing from public_html/build/manifest.json after sync"
fi

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
