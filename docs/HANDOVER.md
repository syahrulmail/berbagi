# Handover Project berbagi.or.id ke OpenCode

Dokumen ini menjelaskan cara mengambil alih penuh pengembangan dan operasi
**berbagi.or.id** (Laravel 8.83 / PHP 8.4 / MySQL, hosting cPanel + LiteSpeed,
tanpa SSH/git di server) menggunakan OpenCode.

> Repo: `https://github.com/syahrulmail/berbagi` — branch default `master`.
> Produksi tidak punya `.git`; deploy dilakukan via FTP dengan verifikasi checksum.

Berkas rahasia **tidak** ditulis di dokumen ini. Simpan kredensial di
`.handover/credentials.env` (sudah di-gitignore). Template:
`scripts/templates/credentials.env.example`.

---

## 1. Ringkasan Lingkungan

| Komponen | Nilai |
|---|---|
| Aplikasi | Berbagi — Sistem Manajemen Fundraising (BWA) |
| Framework | Laravel 8.83.29 |
| Bahasa target | PHP 7.4 kompatibel (produksi berjalan PHP 8.4.27) |
| Database | MySQL / MariaDB (produksi: `hajc7791_berbagi`) |
| Hosting | cPanel + LiteSpeed, document root = root project (bukan `public/`) |
| Akses server | FTP saja (tanpa SSH, tanpa `.git`) |
| Scheduler | cron cPanel tiap menit → `artisan schedule:run` |
| Cache | `file` (bukan Redis/Memcached) |
| Queue | `sync` (bukan worker) |
| Git remote | GitHub `syahrulmail/berbagi` |

---

## 2. Akses GitHub

**Repo:** `https://github.com/syahrulmail/berbagi`

- Branch default/rilis: `master`.
- Push/PR memakai credential helper yang sudah terpasang di lingkungan
  OpenCode (`/app/agent/bin/agent git-credential-helper`). Tidak perlu token
  mentah di file.
- Alur kerja branch yang dipakai proyek: `YYMMDD-<tipe>-<ringkas>`, mis.
  `261103-fix-contact-status-sort`. Push membuat MR otomatis dengan opsi
  `-o merge_request.create`.

Perintah verifikasi akses:

```bash
git remote -v
GIT_TERMINAL_PROMPT=0 git ls-remote --heads origin
```

Perintah clone ulang (bila perlu):

```bash
git clone https://github.com/syahrulmail/berbagi.git
cd berbagi
composer install
```

---

## 3. Akses FTP (produksi)

Detail host/user/password ada di `.handover/credentials.env`
(kunci: `FTP_HOST`, `FTP_PORT`, `FTP_USER`, `FTP_PASS`).

- Protokol: FTP polos, port 21 (`set ftp:ssl-allow no`).
- Project Laravel berada di **root akun FTP** (bukan subfolder `public/`).
- Folder aplikasi: `/app`, `/routes`, `/resources`, `/public`, `/vendor`, dst.
- Document root domain diarahkan ke root project; `.htaccess` root me-rewrite
  ke `public/`.

Contoh perintah aman (tanpa menampilkan password di histori shell — baca dari file):

```bash
set -a; . .handover/credentials.env; set +a
lftp -c "set ftp:ssl-allow no; open -u '$FTP_USER','$FTP_PASS' ftp://$FTP_HOST; cls -1; bye"
```

Gunakan skrip terbantu:

```bash
scripts/deploy.sh get   app/Services/IntegrationCheckService.php   # unduh untuk diff
scripts/deploy.sh push  app/Services/IntegrationCheckService.php   # unggah + verifikasi md5
scripts/deploy.sh push-list daftar-file.txt                        # unggah banyak berkas
```

---

## 4. Akses Database (produksi)

Detail ada di `.handover/credentials.env` (`DB_HOST/PORT/DATABASE/USERNAME/PASSWORD`).

**Penting:** MySQL produksi hanya listen di `localhost` server cPanel. Dari
lingkungan dev, koneksi akan gagal (`ERROR 2002`). Jalankan perintah DB **di
server produksi** (cPanel Terminal atau cron sekali jalan).

Dump database:

- **Di server (fungsi shell dimatikan)**: pakai dumper PHP murni
  (tanpa `mysqldump`), tulis gzip bertahap:
  ```bash
  php scripts/db-dump-php.php /path/backup.sql.gz
  ```
- **Bila ada shell + mysqldump** (mis. dev/VPS): pakai
  `bash scripts/db-dump.sh /path/backup.sql.gz`.

Restore:

```bash
gunzip -c backup.sql.gz | mysql -h HOST -u USER -p DATABASE
```

> Produksi `berbagi.or.id`: `disable_functions` mencakup
> `exec`, `shell_exec`, `proc_open`, `popen`, `system` → `mysqldump` via shell
> **tidak bisa**; gunakan `scripts/db-dump-php.php`.
> Baseline dump diverifikasi: 23 tabel, 118.767 baris, ~2 MB gzip.

Migrasi di produksi (tanpa SSH):

```bash
php artisan migrate --force
```

Bila tidak ada terminal: jalankan migrasi via skrip web sementara di `public/`,
lalu **netralkan/hapus** skrip tersebut setelah selesai.

---

## 5. Setup Lingkungan Dev Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
# isi koneksi DB lokal di .env (default repo memakai sqlite untuk test)
php artisan migrate --seed
php artisan serve
```

Test (baseline harus hijau):

```bash
php artisan test
```

Baseline saat handover: **360 test lulus** (gunakan `database/testing.sqlite`).
Test memakai `DatabaseTransactions`; `tests/TestCase.php` memanggil
`Setting::flushMemo()` tiap `setUp()` untuk isolasi cache statis.

---

## 6. Struktur Penting

| Path | Isi |
|---|---|
| `routes/web.php` | seluruh route + middleware role |
| `app/Http/Middleware/CheckRole.php` | otorisasi role (admin/supervisor/agen) |
| `app/Console/Kernel.php` | scheduler WA (via `Artisan::call`, bukan sub-proses) |
| `app/Services/WhatsAppSenderService.php` | kirim pesan SS/CC |
| `app/Services/FollowupWaService.php` | antrean broadcast, warming, webhook URL |
| `app/Services/IntegrationCheckService.php` | status koneksi API SS/CC (indikator) |
| `app/Services/ImageWebpService.php` | konversi unggahan gambar ke WebP |
| `app/Services/ContactImportService.php` | impor kontak (xlsx/xls/csv) |
| `app/helpers.php` | helper foto (`optimize_uploaded_photo`, dll.) |
| `database/migrations/` | 30 migrasi |
| `database/seeders/` | data awal + akun default |
| `resources/views/` | seluruh view (desktop + mobile) |
| `tests/Feature/` | test fitur (WebP, scoping, integrasi, dsb.) |

Perintah artisan kustom:

```bash
php artisan images:webp     # konversi gambar lama ke WebP
php artisan images:optimize # buat varian WebP banner/testimoni/logo/popup
php artisan whatsapp:send   # kirim antrean WhatsApp pending
php artisan followup:starsender  # kirim broadcast follow-up
php artisan warming:run     # warming WhatsApp otomatis
```

---

## 7. Alur Deploy (FTP + verifikasi)

Karena produksi tidak punya git, ikuti disiplin ini:

1. **Unduh dulu** berkas produksi yang akan ditimpa, lalu diff terhadap
   baseline git untuk memastikan tidak ada editan manual di server:
   ```bash
   scripts/deploy.sh get app/Http/Controllers/ContactController.php
   git show HEAD~1:app/Http/Controllers/ContactController.php > /tmp/base.php
   diff /tmp/deploy_backup_*/ContactController.php /tmp/base.php
   ```
2. **Backup** berkas target ke folder bertanggal (skrip `backup`).
3. **Unggah** hanya berkas yang berubah (`push`), skrip otomatis
   memverifikasi **md5 lokal == md5 remote**.
4. **Reset opcache** (bila mengubah file PHP) via skrip web sementara, atau
   tunggu TTL, lalu uji halaman terkait.
5. **Smoke test** halaman/endpoint yang berubah (status HTTP + isi penting).
6. **Netralkan** skrip diagnostik sementara menjadi stub 404.

Perintah setelah mengubah `.env` produksi / route / config:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:clear
```

---

## 8. Hal Khas Produksi yang Wajib Diingat

- **Document root = root project**, diarahkan ke `public/` via `.htaccess` root.
  Jangan ganti dengan `.htaccess` versi sub-direktori (`/berbagi/`) — menyebabkan
  `/login/` 404 / redirect loop.
- **Tanpa symlink**: disk `public` di `config/filesystems.php` menunjuk ke
  folder nyata `public/storage` (bukan `storage/app/public`). Pastikan
  `public/storage/banners` ada dan writable.
- **Writable**: `storage/`, `storage/framework/{cache,sessions,views}`,
  `bootstrap/cache/`.
- **Scheduler**: cron cPanel tiap menit:
  ```bash
  * * * * * /usr/local/bin/php /home/USER/public_html/berbagi.or.id/artisan schedule:run >> /dev/null 2>&1
  ```
- **proc_open/exec dimatikan** di produksi → scheduler memakai `Artisan::call`
  di dalam closure, bukan `$schedule->command()`.
- **Video beranda** (menu "Video Beranda"): admin mengunggah berkas MP4/WebM;
  kompresi dilakukan di browser (MediaRecorder) karena `exec/proc_open` mati.
  Server hanya menerima video ≤10 MB dan sampul ≤2 MB, disimpan di
  `public/storage/home-videos` dan `home-video-posters`. `public/.user.ini`
  menaikkan `upload_max_filesize` (16M) dan `post_max_size` (24M).
- **CDN** men-cache `public/downloads` dan `css/*.css` lama; `assetv()` menambah
  `?v=filemtime` untuk cache-bust aset.
- **Jangan pernah menimpa unggahan user**; bila GD/WebP tidak tersedia,
  fallback ke file asli.

---

## 9. Akun & Keamanan

- Akun default seeder (WAJIB diganti di produksi):
  - admin: `admin` / `admin12345`
  - supervisor: `supervisor_<kota>` / `super12345`
  - agen: `agen_<kode>` / `agen12345`
- Kunci integrasi WhatsApp (API SS/CC) disimpan **per pengguna** di
  `Setting` (`agent_profile_<slug>`), bukan di `.env`.
- Webhook WhatsApp: `POST /wa/webhook/{provider}` (`starsender`/`cloudchat`).
- Rahasia (FTP, DB, APP_KEY) hanya di `.handover/credentials.env` dan `.env`
  produksi. Jangan commit.

---

## 10. Checklist Ambil Alih

- [ ] `git clone` repo dan `composer install`.
- [ ] Salin `scripts/templates/credentials.env.example` →
      `.handover/credentials.env`, isi nilai sebenarnya, `chmod 600`.
- [ ] Verifikasi GitHub: `git ls-remote --heads origin`.
- [ ] Verifikasi FTP: listing root via `lftp`.
- [ ] Siapkan dump DB dari server produksi (`scripts/db-dump.sh`).
- [ ] `php artisan test` → semua hijau (360).
- [ ] Diff produksi vs `master` untuk mendeteksi drift manual di server.
- [ ] Sinkronkan `.env` produksi (APP_URL https, DB, APP_KEY) — jangan
      menimpa variabel kustom yang sudah ada.
- [ ] Pastikan cron scheduler aktif dan `storage/` writable.

---

## 11. Status Pekerjaan Terakhir

Branch yang sudah dipush (lihat juga PR masing-masing):

- `261102-feat-image-webp` — konversi unggahan gambar ke WebP + migrasi data.
- `261102-chore-sync-prod-drift` — menyelaraskan kode lokal dengan produksi.
- `261103-feat-donation-contact-scope` — pembatasan kontak sesuai peran.
- `261103-fix-integration-status-cache` — perbaikan indikator merah API SS
  (kegagalan sementara tidak lagi di-cache sebagai fail permanen).
- `261103-fix-contact-status-sort` — urutan status sesuai alur kerja.

Catatan terbuka:

- Perf: halaman buat donasi merender seluruh kontak sebagai `<li>`
  (admin ~62k item). Kandidat optimasi: pencarian server-side.
