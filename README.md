# Berbagi.or.id

Platform berbagi konten berbasis **PHP 8** dan **MySQL/MariaDB** — tempat siapa saja membagikan ilmu, cerita, dan pengalaman.

## Fitur

- Registrasi dan login pengguna (password di-hash dengan `password_hash`)
- Menulis, mengedit, dan menghapus konten (draf / terbit)
- Kategori konten, pencarian, dan paginasi
- Statistik jumlah dilihat dan daftar konten terpopuler
- Komentar pada konten yang telah terbit
- Proteksi CSRF pada semua form
- Query database menggunakan prepared statement (PDO)

## Struktur Project

```
.
├── autoload.php          # Loader .env + autoload kelas App\
├── config/config.php     # Konfigurasi database & aplikasi
├── public/               # Web root (entry point: index.php)
│   ├── index.php         # Front controller / routing
│   ├── css/style.css
│   └── .htaccess         # Rewrite untuk Apache
├── src/                  # Kelas inti (Auth, Post, Comments, Database)
├── sql/schema.sql        # Skema database + seed kategori
├── storage/              # Penyimpanan upload (cadangan)
└── templates/            # Tampilan (layout, halaman, error)
```

## Prasyarat

- PHP >= 8.1 dengan ekstensi `pdo_mysql`, `mbstring`
- MySQL atau MariaDB
- Composer (opsional)

## Instalasi

```bash
# 1. Buat database
mysql -u root < sql/schema.sql

# 2. Siapkan konfigurasi
cp .env.example .env
#   lalu sesuaikan DB_USER dan DB_PASS

# 3. Jalankan (built-in server PHP)
composer run serve
# atau:
php -S 0.0.0.0:8000 public/index.php
```

Akses aplikasi di `http://localhost:8000`.

## Konfigurasi Environment

| Variabel | Default | Keterangan |
|----------|---------|------------|
| `APP_ENV` | `development` | `development` / `production` |
| `APP_URL` | `http://localhost:8000` | URL publik aplikasi |
| `DB_HOST` | `127.0.0.1` | Host database |
| `DB_PORT` | `3306` | Port database |
| `DB_NAME` | `berbagi` | Nama database |
| `DB_USER` | `berbagi` | User database |
| `DB_PASS` | (kosong) | Password database |

## Catatan Keamanan

- Jangan pernah commit file `.env` berisi password asli.
- Di environment produksi, set `APP_ENV=production`.
- File `.env` dan `storage/` sudah masuk `.gitignore`.

## Lisensi

MIT
