#!/usr/bin/env bash
#
# Dump database produksi berbagi.or.id.
#
# CATATAN: DB produksi hanya mendengarkan di "localhost" server cPanel,
# sehingga skrip ini harus dijalankan DI server produksi (cPanel Terminal /
# cron sekali jalan). Dari lingkungan dev, koneksi ke localhost:3306 akan
# ditolak (ERROR 2002).
#
# Kredensial dibaca dari .handover/credentials.env (tidak di-commit).
#
# Pemakaian (di server produksi):
#   bash scripts/db-dump.sh /path/backup.sql.gz
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CRED="${CRED:-$ROOT/.handover/credentials.env}"
OUT="${1:-$ROOT/backup_$(date +%Y%m%d_%H%M%S).sql.gz}"

if [[ ! -f "$CRED" ]]; then
  echo "ERROR: $CRED tidak ditemukan." >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a; . "$CRED"; set +a

echo "Dumping ${DB_DATABASE} from ${DB_HOST}:${DB_PORT} -> ${OUT}"
MYSQL_PWD="$DB_PASSWORD" mysqldump \
  -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" \
  --single-transaction --quick --routines --triggers \
  "$DB_DATABASE" | gzip -9 > "$OUT"

echo "Selesai: $(du -h "$OUT" | awk '{print $1}') ${OUT}"
