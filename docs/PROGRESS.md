# Log Progres

## Status saat ini
- Milestone aktif: M0 selesai; siap direview sebelum M1

## Riwayat
### 2026-10-20 — M0 (selesai)
- Selesai: pasang Filament 3.3.57, Pest 3.8.7, Pest Laravel plugin 3.2.0, dan Larastan 3.13.0; panel Filament root dengan login Indonesia, navigasi grup, notifikasi, menu pengguna, dashboard sapaan + kartu ringkasan, tema kustom hijau/krem, font Plus Jakarta Sans lokal, tombol besar, pilihan ukuran huruf; konfigurasi `id`/`Asia/Jakarta`, MySQL, antrean dan sesi database; struktur direktori, README, lisensi, serta script `composer ci`/`composer lint`.
- Pemeriksaan: `composer ci` lulus (Pint, PHPStan level 6, 2 test / 7 assertions); `npm run build` lulus; `composer validate --no-check-publish` lulus; `php artisan route:list` menunjukkan dashboard `/` dan login `/login`.
- Cara menjalankan: `composer install`, `npm install`, salin `.env.example` ke `.env`, `php artisan key:generate`, `php artisan migrate --seed`, `npm run build`, lalu buka `http://esurat.test`.
- Catatan: kerja berada di branch `feature/m0-fondasi`. Pilihan ukuran huruf M0 disimpan di browser; ikuti pertanyaan terbuka dan pindahkan ke preferensi akun saat M1. User demo/seed peran dikerjakan pada M1.
