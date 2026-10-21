# Instalasi E-Surat pada Ubuntu Server

Panduan ringkas untuk Admin IT. Gunakan Ubuntu Server LTS yang masih mendapat pembaruan keamanan dan hanya izinkan akses dari jaringan kantor.

## 1. Siapkan paket server

Pasang Nginx, MySQL 8, PHP-FPM 8.3, Composer, dan ekstensi PHP: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `mysql`, `simplexml`, `xml`, `xmlreader`, `xmlwriter`, `zip`, dan `intl`. Siapkan pengguna Linux khusus aplikasi dan database MySQL khusus aplikasi; jangan gunakan akun root MySQL dari aplikasi.

## 2. Pasang kode aplikasi

Tempatkan kode di `/var/www/esurat`, lalu dari folder itu jalankan:

```bash
composer install --no-dev --optimize-autoloader
cp deploy/.env.production.example .env
php artisan key:generate
php artisan migrate --force --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Sebelum perintah Artisan dijalankan, isi `.env` produksi dengan URL HTTPS internal, kredensial database khusus, dan kata sandi admin awal yang kuat melalui sarana rahasia server. `APP_DEBUG` harus `false`. Jangan commit `.env` atau mengirim kata sandi lewat chat.

## 3. Atur izin dan Nginx

Atur `storage/` dan `bootstrap/cache/` agar dapat ditulis pengguna PHP-FPM. Berkas surat tetap privat di `storage/app/private`; aplikasi tidak memerlukan `storage:link`. Sesuaikan `deploy/nginx-esurat.conf` dengan nama domain internal dan soket PHP-FPM pada server, aktifkan TLS menggunakan sertifikat CA internal, lalu aktifkan konfigurasi situs Nginx.

## 4. Operasional

- Pastikan firewall hanya membuka HTTPS untuk jaringan internal; batasi akses MySQL ke localhost.
- Driver sesi dan cache `file`, antrean `sync`; worker antrean tidak diperlukan.
- Jadwalkan `deploy/backup.sh` setiap hari pukul 01.00 dengan file lingkungan khusus `/etc/esurat/backup.env` berizin `0600`. Isi file tersebut dengan `APP_ROOT`, `BACKUP_DIR`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Jangan source `.env` Laravel dari skrip shell.
- Kirim salinan backup ke server/media lain. Jalankan uji pemulihan staging berkala menurut `deploy/restore.md`.
- Perbarui OS dan PHP sesuai jadwal pemeliharaan, dan pantau ruang disk serta log aplikasi.
