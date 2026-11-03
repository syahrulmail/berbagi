#!/usr/bin/env bash
#
# Deploy berkas ke produksi berbagi.or.id via FTP (cPanel, tanpa git/SSH).
#
# Kredensial dibaca dari .handover/credentials.env (tidak di-commit).
#
# Pemakaian:
#   scripts/deploy.sh backup                 # unduh berkas yang akan ditimpa
#   scripts/deploy.sh push  path/ke/file.php # unggah 1 berkas + verifikasi md5
#   scripts/deploy.sh push-list daftar.txt   # unggah banyak berkas (1 path per baris)
#   scripts/deploy.sh get   path/ke/file.php # unduh 1 berkas untuk diff
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CRED="$ROOT/.handover/credentials.env"
BACKUP_DIR="${BACKUP_DIR:-/tmp/opencode/deploy_backup_$(date +%Y%m%d_%H%M%S)}"

if [[ ! -f "$CRED" ]]; then
  echo "ERROR: $CRED tidak ditemukan. Salin dari .handover/credentials.env.example." >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a; . "$CRED"; set +a

if ! command -v lftp >/dev/null 2>&1; then
  echo "ERROR: lftp tidak terpasang (apt-get install -y lftp)." >&2
  exit 1
fi

open_url="ftp://${FTP_HOST}"
lftp_base=(lftp -c)
conn="set ftp:ssl-allow no; set xfer:clobber on; set net:timeout 20; set net:max-retries 3; open -u '${FTP_USER}','${FTP_PASS}' ${open_url};"

remote_path() {
  local p="${1#/}"
  echo "/${p}"
}

cmd="${1:-}"; shift || true

case "$cmd" in
  get)
    f="$1"
    mkdir -p "$BACKUP_DIR"
    out="$BACKUP_DIR/$(basename "$f")"
    "${lftp_base[@]}" "${conn} get $(remote_path "$f") -o '$out'; bye"
    echo "downloaded: $out"
    ;;

  backup)
    mkdir -p "$BACKUP_DIR"
    for f in "$@"; do
      out="$BACKUP_DIR/$(echo "$f" | tr '/' '_')"
      "${lftp_base[@]}" "${conn} get $(remote_path "$f") -o '$out'; bye"
      echo "backup: $f -> $out"
    done
    ;;

  push)
    f="$1"
    if [[ ! -f "$ROOT/$f" ]]; then echo "ERROR: lokal tidak ada: $f" >&2; exit 1; fi
    local_md5="$(md5sum "$ROOT/$f" | awk '{print $1}')"
    "${lftp_base[@]}" "${conn} put '$ROOT/$f' -o $(remote_path "$f"); bye"
    tmp="$(mktemp)"
    "${lftp_base[@]}" "${conn} get $(remote_path "$f") -o '$tmp'; bye"
    remote_md5="$(md5sum "$tmp" | awk '{print $1}')"
    rm -f "$tmp"
    if [[ "$local_md5" == "$remote_md5" ]]; then
      echo "OK  $f  md5=$local_md5"
    else
      echo "MISMATCH $f  local=$local_md5 remote=$remote_md5" >&2
      exit 2
    fi
    ;;

  push-list)
    list="$1"
    while IFS= read -r f; do
      [[ -z "$f" || "$f" == \#* ]] && continue
      "$0" push "$f"
    done < "$list"
    ;;

  *)
    sed -n '2,12p' "$0"
    exit 1
    ;;
esac
