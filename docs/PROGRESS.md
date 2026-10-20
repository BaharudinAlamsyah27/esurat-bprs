# Log Progres

## Status saat ini
- Milestone aktif: M1 selesai; siap direview/diintegrasikan sebelum M2

## Riwayat
### 2026-10-20 — M0 (selesai)
- Selesai: pasang Filament 3.3.57, Pest 3.8.7, Pest Laravel plugin 3.2.0, dan Larastan 3.13.0; panel Filament root dengan login Indonesia, navigasi grup, notifikasi, menu pengguna, dashboard sapaan + kartu ringkasan, tema kustom hijau/krem, font Plus Jakarta Sans lokal, tombol besar, pilihan ukuran huruf; konfigurasi `id`/`Asia/Jakarta`, MySQL, antrean dan sesi database; struktur direktori, README, lisensi, serta script `composer ci`/`composer lint`.
- Pemeriksaan: `composer ci` lulus (Pint, PHPStan level 6, 2 test / 7 assertions); `npm run build` lulus; `composer validate --no-check-publish` lulus; `php artisan route:list` menunjukkan dashboard `/` dan login `/login`.
- Cara menjalankan: `composer install`, `npm install`, salin `.env.example` ke `.env`, `php artisan key:generate`, `php artisan migrate --seed`, `npm run build`, lalu buka `http://esurat.test`.
- Catatan: kerja berada di branch `feature/m0-fondasi`. Pilihan ukuran huruf M0 disimpan di browser; ikuti pertanyaan terbuka dan pindahkan ke preferensi akun saat M1. User demo/seed peran dikerjakan pada M1.

### 2026-10-20 — M1 (selesai)
- Selesai: autentikasi email/nama pengguna dengan akun nonaktif ditolak; permission Spatie untuk enam peran; migration/model/factory/policy untuk unit, jabatan, klasifikasi, jenis surat, instruksi disposisi, pengaturan, dan profil pengguna; resource Filament tambah/lihat/ubah; unggah tanda tangan/paraf PNG pada disk privat oleh super admin atau pemilik melalui Profil; aturan kata sandi kuat, throttle bawaan login Filament (5 percobaan), timeout sesi idle configurable (default 30 menit), audit log autentikasi dan perubahan data, master data + akun demo lokal; ukuran huruf tersimpan per akun.
- Pemeriksaan: `composer ci` lulus (Pint, PHPStan level 6, 15 test / 68 assertion). Route `/login`, `/profil-saya`, dan `/users` terdaftar; dashboard, halaman profil, serta layar daftar/tambah user dan master berhasil dirender. Migration dan seluruh seeder M1 juga berhasil dijalankan pada SQLite in-memory; database MySQL Laragon tidak diubah.
- Cara menjalankan dan mencoba login di Laragon: pastikan database `esurat` sudah ada (lihat `docs/08-PANDUAN-SETUP-LARAGON.md` langkah 4); isi `DEMO_USER_PASSWORD` pada `.env` dengan kata sandi lokal minimal 10 karakter, huruf besar/kecil dan angka; dari root proyek jalankan `php artisan migrate --seed`; kemudian buka `http://esurat.test` atau `http://127.0.0.1:8000/login`. Akun demo `superadmin` atau `superadmin@esurat.test` memakai kata sandi yang Anda tentukan tadi.
- Catatan Git: cabang `feature/m1-auth-master-data` dibuat bertumpuk di atas commit M0 karena cabang `develop` belum tersedia pada checkout. Perubahan M0 tidak diubah.
- Catatan untuk tahap berikut: lanjutkan M2 Surat Masuk setelah perubahan M1 direview/diintegrasikan. Pilih implementasi Laravel yang sederhana dan mudah dirawat; hindari abstraksi, dependensi, atau otomasi tambahan yang tidak diperlukan. Jangan menjalankan `migrate:fresh` pada database pengguna.
- Perbaikan login setelah M1: aset CSS/JavaScript Filament belum dipublikasikan ke `public`, sehingga tampilan login polos dan tombol tidak tampak. Jalankan `php artisan filament:assets`; Composer kini menjalankan perintah ini otomatis setelah autoload/package discovery.
