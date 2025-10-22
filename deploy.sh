#!/bin/bash
# Magento 2 Deployment Script (DB via app/etc/env.php + Git helpers + Permission fixer)
# - Uses $PWD as Magento root
# - Reads DB creds dynamically from app/etc/env.php
# - Safe mysqldump via temporary my.cnf
# - Git init/remote/pull/push helpers (+ force pull/push) wrapped in perm flip
# - Deployment perms flip: cyberchunk:www-data -> www-data:www-data
# - Non-interactive admin password setter via ADMIN_PASS env
# - Practical defaults; keep it simple and robust

set -Eeuo pipefail

# -------- UI helpers --------
info()  { echo -e "\033[1;34m[INFO]\033[0m  $*"; }
warn()  { echo -e "\033[1;33m[WARN]\033[0m  $*"; }
error() { echo -e "\033[1;31m[ERROR]\033[0m $*" >&2; }
require_cmd() { command -v "$1" >/dev/null 2>&1 || { error "Required command '$1' not found."; exit 1; }; }
ask_yes_no() { local p="$1"; local d="${2:-N}"; read -r -p "$p [y/N]: " a; [[ "${a:-$d}" =~ ^[Yy]$ ]]; }
as_root() {
  if [[ $EUID -eq 0 ]]; then
    "$@"
  elif command -v sudo >/dev/null 2>&1; then
    sudo "$@"
  else
    error "Need root privileges for: $* (run as root or install sudo)"; exit 1
  fi
}

# -------- Magento root (use PWD) --------
MAGENTO_ROOT="${PWD}"
cd "$MAGENTO_ROOT"

# -------- Config --------
BACKUP_CODE_DIR="$MAGENTO_ROOT/backup/code"
BACKUP_DB_DIR="$MAGENTO_ROOT/backup/database"
MAGENTO_GROUP="${MAGENTO_GROUP:-magento-www}"

# Deployment permission flip config
DEV_OWNER_USER="cyberchunk"
DEV_OWNER_GROUP="www-data"
WEB_OWNER_USER="www-data"
WEB_OWNER_GROUP="www-data"

# -------- Pre-flight --------
require_cmd php
require_cmd zip
require_cmd mysqldump
require_cmd git
mkdir -p "$BACKUP_CODE_DIR" "$BACKUP_DB_DIR"

# -------- DB creds --------
load_db_from_env_php() {
  local env_file="$MAGENTO_ROOT/app/etc/env.php"
  [[ -f "$env_file" ]] || { error "env.php not found at $env_file"; exit 1; }
  eval "$(
    MAGENTO_ROOT="$MAGENTO_ROOT" php <<'PHP'
<?php
$f = getenv('MAGENTO_ROOT') . '/app/etc/env.php';
$env = include $f;
$c = $env['db']['connection']['default'] ?? [];
$kv = [
  'DB_NAME' => $c['dbname']   ?? '',
  'DB_USER' => $c['username'] ?? '',
  'DB_PASS' => $c['password'] ?? '',
  'DB_HOST' => $c['host']     ?? 'localhost',
];
foreach ($kv as $k => $v) {
  $v = str_replace("'", "'\\''", $v);
  echo "$k='$v'\n";
}
PHP
  )"
}

with_mysql_defaults() {
  local tmp; tmp="$(mktemp)"
  {
    echo "[client]"
    echo "user=$DB_USER"
    echo "password=$DB_PASS"
    echo "host=$DB_HOST"
  } > "$tmp"
  echo "$tmp"
}

# -------- Git helpers --------
is_git_repo() { git rev-parse --is-inside-work-tree >/dev/null 2>&1; }
show_remotes() { is_git_repo || return 1; git remote -v || return 1; }

ensure_git_repo() {
  if is_git_repo; then
    info "Git repository detected (branch: $(git symbolic-ref --quiet --short HEAD || echo 'unborn'))."
    return
  fi
  if ask_yes_no "Not a Git repo. Initialize one?"; then
    git init -q
    git symbolic-ref HEAD refs/heads/main || true
    info "Initialized Git repo on branch main."
  fi
}

ensure_gitignore() {
  local GI="$MAGENTO_ROOT/.gitignore"
  local patterns=(
    "/backup/"
    "/var/cache/"
    "/var/page_cache/"
    "/var/view_preprocessed/"
    "/generated/"
    "/pub/static/"
    "/pub/media/catalog/product/cache/"
    "/var/log/*.log"
    "/var/log/*.txt"
    ".DS_Store"
  )
  [[ -f "$GI" ]] || touch "$GI"
  for p in "${patterns[@]}"; do grep -qxF "$p" "$GI" || echo "$p" >> "$GI"; done
}

ensure_remote() {
  is_git_repo || return
  if ! git remote get-url origin >/dev/null 2>&1; then
    read -r -p "Enter remote URL for 'origin': " url
    [[ -n "$url" ]] && git remote add origin "$url"
  fi
}

remote_default_branch() { git remote show origin 2>/dev/null | sed -n 's/  HEAD branch: //p' | head -n1; }
current_branch() { git symbolic-ref --quiet --short HEAD 2>/dev/null || remote_default_branch || echo "main"; }

ensure_tracking_branch() {
  local cur; cur="$(current_branch)"
  git fetch --all --prune || true
  if ! git show-ref --verify --quiet "refs/heads/$cur"; then
    git checkout -B "$cur" "origin/$cur" 2>/dev/null || true
  fi
  if ! git rev-parse --abbrev-ref --symbolic-full-name @{u} >/dev/null 2>&1; then
    git branch --set-upstream-to="origin/$cur" "$cur" 2>/dev/null || true
  fi
}

git_status() { git -c color.ui=always status; }

git_pull_ff_rebase() {
  ensure_tracking_branch
  local branch; branch="$(current_branch)"
  info "Fetching from origin…"
  git fetch --all --prune
  # Safer than relying on git auto-stash (which can hit perms)
  git stash push -u -m "deploy-autostash $(date +%F_%T)" || true
  git pull --rebase origin "$branch"
  git stash pop --index || true
  info "Pull complete."
}

git_add_commit_push() {
  ensure_remote
  local branch; branch="$(current_branch)"
  local msg="${GIT_MSG:-Deploy $(date +%F_%T)}"
  git add .
  git commit -m "$msg" || true
  info "Pushing to origin/$branch …"
  git push -u origin "$branch"
  info "Push complete."
}

git_force_pull_hard() {
  ensure_tracking_branch
  local branch; branch="$(current_branch)"
  warn "FORCE PULL will discard local changes!"
  git fetch --all --prune
  git reset --hard "origin/$branch"
  git clean -fd
  info "Force pull complete."
}

git_force_push_lease() {
  ensure_tracking_branch
  local branch; branch="$(current_branch)"
  warn "FORCE PUSH may overwrite remote history!"
  git push --force-with-lease origin "$branch"
  info "Force push complete."
}

# -------- Backup --------
perform_backup() {
  load_db_from_env_php
  local TIMESTAMP; TIMESTAMP="$(date +"%Y%m%d_%H%M%S")"
  info "Creating code backup…"
  zip -rq "$BACKUP_CODE_DIR/code_$TIMESTAMP.zip" . -x "backup/*" ".git/*"
  info "Creating DB backup…"
  local tmp_cnf; tmp_cnf="$(with_mysql_defaults)"
  mysqldump --defaults-extra-file="$tmp_cnf" --single-transaction "$DB_NAME" > "$BACKUP_DB_DIR/db_$TIMESTAMP.sql"
  rm -f "$tmp_cnf"
  # keep last 5
  ls -1tr "$BACKUP_CODE_DIR"/*.zip 2>/dev/null | head -n -5 | xargs -r rm -f
  ls -1tr "$BACKUP_DB_DIR"/*.sql 2>/dev/null | head -n -5 | xargs -r rm -f
  info "Backup complete."
}

# -------- Permission flip helpers for deployment --------
pre_deploy_perms() {
  info "Setting ownership for deployment: ${DEV_OWNER_USER}:${DEV_OWNER_GROUP} on ${MAGENTO_ROOT}"
  as_root chown -R "${DEV_OWNER_USER}:${DEV_OWNER_GROUP}" "/var/www/html/"
}

post_deploy_perms() {
  info "Restoring web ownership: ${WEB_OWNER_USER}:${WEB_OWNER_GROUP} on ${MAGENTO_ROOT}"
  as_root chown -R "${WEB_OWNER_USER}:${WEB_OWNER_GROUP}" "/var/www/html/"
}

run_with_perm_flip() {
  pre_deploy_perms
  local restored=0
  trap '[[ $restored -eq 0 ]] && post_deploy_perms; restored=1' EXIT
  "$@"
  post_deploy_perms
  restored=1
  trap - EXIT
}

# -------- Admin password (non-interactive) --------
set_admin_password() {
  local u="${ADMIN_USER:-admin}"
  local p="${ADMIN_PASS:-}"
  if [[ -z "$p" ]]; then
    error "ADMIN_PASS is empty. Export ADMIN_PASS before running this (e.g., ADMIN_PASS='EQFQQ40H')."
    exit 1
  fi
  php bin/magento maintenance:disable >/dev/null 2>&1 || true
  php bin/magento admin:user:unlock --username="$u" || true
  php bin/magento admin:user:change-password --username="$u" --password="$p"
  php bin/magento cache:clean >/dev/null 2>&1 || true
  info "Admin password updated for user '$u'."
}

# -------- Menu --------
echo "Choose an action:"
echo "1) Frontend only"
echo "2) Code only"
echo "3) Full deployment"
echo "4) Backup only"
echo "5) Git status & pull (rebase)"
echo "6) Git add/commit/push"
echo "7) Git init / set origin remote / write .gitignore"
echo "8) Fix file & folder permissions (manual/optional)"
echo "9) Git FORCE pull"
echo "10) Git FORCE push"
echo "11) Set Admin password (via ADMIN_PASS)"
read -r -p "Enter choice [1-11]: " choice

# -------- Execute --------
case "$choice" in
  1)
    run_with_perm_flip bash -c '
      as_root rm -rf pub/static/* var/view_preprocessed/* generated/* var/cache/* var/page_cache*;
      php -d memory_limit=4G bin/magento setup:static-content:deploy -f;
      php bin/magento cache:clean;
      php bin/magento cache:flush
    '
    ;;
  2)
    run_with_perm_flip bash -c '
      as_root rm -rf pub/static/* var/view_preprocessed/* generated/* var/cache/* var/page_cache*;
      php -d memory_limit=4G bin/magento setup:upgrade;
      php -d memory_limit=4G bin/magento setup:di:compile;
      php bin/magento indexer:reindex;
      php bin/magento cache:clean;
      php bin/magento cache:flush
    '
    ;;
  3)
    run_with_perm_flip bash -c '
      as_root rm -rf pub/static/* var/view_preprocessed/* generated/* var/cache/* var/page_cache*;
      php -d memory_limit=4G bin/magento setup:upgrade;
      php -d memory_limit=4G bin/magento setup:di:compile;
      php bin/magento indexer:reindex;
      php -d memory_limit=4G bin/magento setup:static-content:deploy -f;
      php bin/magento cache:clean;
      php bin/magento cache:flush
    '
    ;;
  4)
    perform_backup
    ;;
  5)
    run_with_perm_flip bash -c '
      git -c color.ui=always status
      git fetch --all --prune
      git stash push -u -m "deploy-autostash $(date +%F_%T)" || true
      branch="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || echo main)"
      git pull --rebase origin "$branch"
      git stash pop --index || true
      info "Pull complete."
    '
    ;;
  6)
    run_with_perm_flip bash -c '
      branch="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || echo main)"
      msg="${GIT_MSG:-Deploy $(date +%F_%T)}"
      git add .
      git commit -m "$msg" || true
      info "Pushing to origin/$branch …"
      git push -u origin "$branch"
      info "Push complete."
    '
    ;;
  7)
    ensure_git_repo; ensure_gitignore; ensure_remote
    ;;
  8)
    warn "Run your custom fix_permissions here if needed (kept manual on purpose)."
    ;;
  9)
    run_with_perm_flip bash -c '
      branch="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || echo main)"
      echo "[WARN] FORCE PULL will discard local changes!"
      git fetch --all --prune
      git reset --hard "origin/$branch"
      git clean -fd
      info "Force pull complete."
    '
    ;;
  10)
    run_with_perm_flip bash -c '
      branch="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || echo main)"
      echo "[WARN] FORCE PUSH may overwrite remote history!"
      git push --force-with-lease origin "$branch"
      info "Force push complete."
    '
    ;;
  11)
    run_with_perm_flip bash -c 'set_admin_password'
    ;;
  *)
    error "Invalid choice"
    exit 1
    ;;
esac

info "Process completed."
