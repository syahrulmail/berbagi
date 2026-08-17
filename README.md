# Berbagi — Sistem Manajemen Fundraising

Sistem manajemen fundraising untuk **BWA (Berbagi Wakaf & Amal)**, portal **berbagi.or.id**. Dibangun dengan **Laravel 8.83** + **PHP 7.4** + **MySQL/MariaDB**, siap produksi di **cPanel + LiteSpeed**.

> Project ini adalah migrasi dari Google Apps Script ke aplikasi web self-hosted.

## Fitur

- **4 role pengguna**: admin super, supervisor/TL, agen/freelancer, donatur (publik)
- **Manajemen cabang** dengan target fundraising masing-masing (default target nasional: Rp1,5 Miliar)
- **Manajemen donatur/kontak** dengan status follow-up (prospect, pending, committed, donated, dll.)
- **Pencatatan donasi** per program wakaf & campaign tag
- **Program & campaign tag** untuk pengelompokan fundraising
- **Pengumuman/banner** di halaman publik
- **Pengaturan sistem** (target global, pengingat WhatsApp) tanpa hardcode
- **WhatsApp API integration** dengan pengiriman antrian terjadwal via cron (tiap 5 menit)
- **Activity log** seluruh aktivitas penting pengguna

## Teknologi

| Komponen | Versi |
|----------|-------|
| PHP | 7.4.x (kompatibel cPanel LiteSpeed) |
| Laravel | 8.83.x |
| Database | MySQL 5.7+ / MariaDB 10.x |
| UI | Blade + CSS custom (glassmorphism, warna #1E3A5F & #2ECC71) |

## Instalasi Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate

# Konfigurasi koneksi DB di .env, lalu:
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Akses: `http://localhost:8000`

### Akun default (dari seeder)

| Role | Login | Password |
|------|-------|----------|
| Admin super | `admin` | `admin12345` |
| Supervisor | `supervisor_{kota}` (contoh: `supervisor_jakarta_pusat`) | `super12345` |
| Agen | `agen_{kode}` (contoh: `agen_BWA-01`) | `agen12345` |

> Segera ganti password default sebelum digunakan produksi.

## Kredensial & Config

Semua konfigurasi rahasia lewat `.env` (tidak dikomit):

```
APP_NAME=Berbagi
DB_DATABASE=berbagi
DB_USERNAME=berbagi
DB_PASSWORD=...

WHATSAPP_API_URL=https://gateway-provider.example.com
WHATSAPP_API_TOKEN=token-anda
```

Jangan lupa `storage:link` agar upload gambar banner tampil.

## Cron / Scheduler (penting untuk produksi)

Pengiriman WhatsApp berjalan via scheduler. Tambahkan baris berikut di **cron job cPanel** (sesuaikan path):

```bash
* * * * * /usr/bin/php7.4 /home/USERNAME/public_html/artisan schedule:run >> /dev/null 2>&1
```

Scheduler akan menjalankan `whatsapp:send` setiap 5 menit (didefinisikan di `app/Console/Kernel.php`).

## Catatan Produksi (cPanel + LiteSpeed)

1. Letakkan isi project di `public_html`; isi folder `public/` dipindah ke `public_html/`, lalu ubah `index.php` agar memuat `../bootstrap/app.php` (bootstrap dipindah ke folder induk) atau gunakan struktur standar Laravel dengan symlink `public_html` → `project/public`.
2. Pastikan folder `storage/` dan `bootstrap/cache/` writable.
3. Gunakan PHP 7.4 sebagai PHP version di cPanel (MultiPHP Manager).
4. Konfigurasi `.env` dengan kredensial database produksi, lalu `php artisan config:cache`.

## Struktur Penting

- `routes/web.php` — seluruh route + middleware role
- `app/Http/Middleware/CheckRole.php` — otorisasi role
- `app/Console/Kernel.php` — scheduler WhatsApp
- `app/Services/WhatsAppApiService.php` — integrasi WhatsApp API
- `database/migrations/` — skema 11 tabel
- `database/seeders/` — data awal (cabang, user, program, tags)
- `resources/views/` — seluruh view (layout sidebar, 3 dashboard, halaman publik, CRUD)

## Lisensi

Pengembangan internal BWA. Kode tidak dipublikasikan untuk umum.
