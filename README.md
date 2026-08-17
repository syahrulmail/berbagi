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

Di produksi cPanel (tanpa symlink), disk `public` sudah diarahkan ke `public/storage` (lihat `config/filesystems.php`); pastikan folder tersebut ada. Di lingkungan lokal tetap bisa `php artisan storage:link`.

## Cron / Scheduler (penting untuk produksi)

Pengiriman WhatsApp berjalan via scheduler. Tambahkan baris berikut di **cron job cPanel** (sesuaikan path dan versi PHP):

```bash
* * * * * /usr/local/bin/php /home/USERNAME/public_html/berbagi.or.id/berbagi/artisan schedule:run >> /dev/null 2>&1
```

Scheduler akan menjalankan `whatsapp:send` setiap 5 menit (didefinisikan di `app/Console/Kernel.php`). Sebelum pengiriman aktif, isi `WHATSAPP_API_URL` dan `WHATSAPP_API_TOKEN` di `.env` produksi.

## Catatan Produksi (cPanel + LiteSpeed)

Deployment aktual di `berbagi.or.id` memakai struktur berikut (root domain):

1. Seluruh project Laravel ditaruh di `public_html/berbagi.or.id/berbagi` (dokumen root project).
2. Di cPanel → **Domains**, Document Root `berbagi.or.id` diubah menunjuk ke folder `public`:
   ```
   /home/USERNAME/public_html/berbagi.or.id/berbagi/public
   ```
   Aplikasi langsung hidup di `http://berbagi.or.id/`. Saat mengubah Document Root, **jangan** memilih opsi "move content" — cukup arahkan, lalu upload project.
3. `APP_URL` di `.env` diisi `http://berbagi.or.id`. Dukungan sub-direktori tetap ada di `app/Providers/AppServiceProvider.php` (`configureSubDirectory`) untuk kasus pemasangan di sub-path bila suatu saat diperlukan; jika `APP_URL` tanpa path, logika ini otomatis di-skip.
4. Karena symlink tidak bisa dibuat via FTP, disk `public` di `config/filesystems.php` diarahkan ke folder sungguhan `public/storage` (bukan `storage/app/public`). Pastikan folder `public/storage/banners` ada dan writable.
5. Pastikan folder `storage/`, `storage/framework/{cache,sessions,views}`, dan `bootstrap/cache/` writable.
6. Gunakan PHP 7.4 sebagai PHP version di cPanel (MultiPHP Manager / LiteSpeed).
7. Tanpa SSH, jalankan migrasi via script web sementara di `public/` lalu hapus, atau via cPanel Terminal / cron sekali jalan: `php artisan migrate --force`.
8. Jangan lupa `php artisan config:cache` setelah `.env` produksi final (dengan koneksi DB dan `APP_URL` yang benar).

Catatan: file `.htaccess` di root project hanya diperlukan untuk skenario sub-direktori; pada deployment root domain (Document Root → `public/`) file tersebut tidak terpakai.

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
