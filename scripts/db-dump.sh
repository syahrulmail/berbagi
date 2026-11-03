#!/usr/bin/env bash
#
# Dump database produksi berbagi.or.id.
#
# CATATAN: DB produksi hanya mendengarkan di "localhost" server cPanel,
# sehingga skrip ini harus dijalankan DI server produksi (cPanel Terminal /
# cron sekali jalan). Dari lingkungan dev, koneksi ke localhost:3306 akan
# ditolak (ERROR 2002).
#
# Sumber kredensial (urut prioritas):
#   1. $CRED bila di-set
#   2. .handover/credentials.env (lingkungan dev)
#   3. .env Laravel (langsung di server produksi)
#
# Pemakaian (di server produksi):
#   bash scripts/db-dump.sh /path/backup.sql.gz
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CRED="${CRED:-}"
OUT="${1:-$ROOT/backup_$(date +%Y%m%d_%H%M%S).sql.gz}"

if [[ -z "$CRED" ]]; then
  if [[ -f "$ROOT/.handover/credentials.env" ]]; then
    CRED="$ROOT/.handover/credentials.env"
  elif [[ -f "$ROOT/.env" ]]; then
    CRED="$ROOT/.env"
  fi
fi

if [[ -z "$CRED" || ! -f "$CRED" ]]; then
  echo "ERROR: kredensial tidak ditemukan (.handover/credentials.env atau .env)." >&2
  exit 1
fi

# shellcheck disable=SC1090
if [[ "$(basename "$CRED")" == ".env" ]]; then
  env_get() {
    grep -E "^${1}=" "$CRED" | tail -1 | cut -d= -f2- \
      | sed -E 's/^"(.*)"$/\1/; s/^'"'"'(.*)'"'"'$/\1/'
  }
  DB_HOST="$(env_get DB_HOST)"; DB_PORT="$(env_get DB_PORT)"
  DB_DATABASE="$(env_get DB_DATABASE)"
  DB_USERNAME="$(env_get DB_USERNAME)"
  DB_PASSWORD="$(env_get DB_PASSWORD)"
  : "${DB_HOST:=localhost}"; : "${DB_PORT:=3306}"
else
  set -a; . "$CRED"; set +a
fi

echo "Dumping ${DB_DATABASE} from ${DB_HOST}:${DB_PORT} -> ${OUT}"
MYSQL_PWD="$DB_PASSWORD" mysqldump \
  -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" \
  --single-transaction --quick --routines --triggers \
  "$DB_DATABASE" | gzip -9 > "$OUT"

echo "Selesai: $(du -h "$OUT" | awk '{print $1}') ${OUT}"
