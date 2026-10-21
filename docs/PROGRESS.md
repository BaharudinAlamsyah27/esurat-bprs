# Log Progres

## Status saat ini

- Milestone aktif berikutnya: **M2 — Surat Masuk (audit kriteria dan pelengkapan yang tersisa)**
- M0: **Selesai untuk kode dan smoke test**; tampilan admin juga sudah terlihat pada screenshot browser pengguna. Tidak ada klaim UAT dengan pegawai akhir.
- Lingkup v2: aplikasi surat masuk/keluar sederhana; disposisi satu lembar cetak adalah M3.
- Database lama tidak dihapus. Migration baru diatur agar instalasi baru tidak membuat tabel fitur lama yang dibatalkan.

## Roadmap dan status

| Milestone | Status | Catatan |
|---|---|---|
| M0 — Fondasi & Login | Selesai (kode + smoke test) | Login username, level admin/user, seeder awal, timeout 30 menit, font lokal; `composer ci` lulus pada 2026-10-21 (6 test/28 assertion). Screenshot pengguna menunjukkan admin berhasil membuka aplikasi lokal. |
| M1 — Data Master, Pengguna, Profil | Selesai (implementasi + CI); migrasi Laragon menunggu | Data Instansi tunggal dengan logo privat, 9 klasifikasi, kelola pengguna/reset kata sandi, profil, audit baca-saja, policy dan proteksi Admin aktif terakhir. `composer ci` lulus. |
| M2 — Surat Masuk | Sebagian tersedia | Resource, nomor agenda, upload privat, detail, duplikat, policy tersedia. Belum seluruh kriteria M2/test diverifikasi. |
| M3 — Lembar Disposisi sederhana | Belum dimulai | Buat satu form + PDF satu halaman. Tidak ada alur status, penerima berakun, tenggat, atau notifikasi. |
| M4 — Surat Keluar | Belum dimulai | CRUD, agenda dan nomor otomatis, unggah privat. |
| M5 — Laporan | Belum dimulai | Laporan surat masuk/keluar, cetak, PDF, Excel. |
| M6 — Dashboard & Penyelesaian | Sebagian tersedia | Sapaan, kartu placeholder, pengaturan ukuran huruf, dan font lokal tersedia; grafik, ringkasan nyata, bantuan, hardening, backup, panduan, dan UAT tersisa. |

## Riwayat

### 2026-10-21 — Audit rombakan total dan M0

- **Selesai:** memeriksa ulang scope v2; menyederhanakan login ke username dan level `admin`/`user`; menambah migrasi level yang memetakan akun lama dari role admin; menyiapkan seeder admin tanpa password hardcode; membersihkan model, resource, service, factory, terjemahan, test, route, dan middleware fitur lama yang tidak termasuk scope. Disposisi satu lembar cetak tetap dijadwalkan pada M3.
- **Selesai:** tabel/model lama tidak dihapus dari database. Migration lama untuk unit/permission/notifikasi/disposisi berjenjang tidak lagi membuat tabel pada instalasi baru. Lembar Disposisi sederhana tetap direncanakan di M3.
- **Validasi kode:** `composer ci` lulus pada 2026-10-21 (Pint, PHPStan, 6 test/28 assertion). `composer validate --no-check-publish` tidak lagi melaporkan masalah lock setelah dependency dirapikan.
- **Tersisa:** UAT oleh pegawai akhir dilakukan pada tahap M6. Login admin lokal telah terlihat pada screenshot pengguna; login user dan penolakan akun nonaktif tercakup oleh test otomatis.
- **Cara menjalankan:** jalankan `php artisan serve` dari terminal Laragon dan buka `http://127.0.0.1:8000`. Jika database lokal belum memiliki admin, isi `INITIAL_ADMIN_PASSWORD` di `.env` dengan kata sandi kuat, lalu jalankan `php artisan migrate --seed`. Driver lokal yang disarankan: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`.
- **Catatan:** jangan jalankan `migrate:fresh`/`db:wipe`. Paket `spatie/laravel-permission` memang tidak digunakan pada scope v2; autoload dan cache Laravel lokal sudah dibuat ulang agar tidak merujuk provider paket yang sudah dilepas.

### 2026-10-21 — Verifikasi sesi marathon

- **Selesai:** membaca ulang `AGENTS.md`, roadmap, PRD, arsitektur, alur akses, panduan UX, dan referensi fitur di `docs/model/`. Referensi tersebut sejalan dengan modul v2; tampilan yang dikembangkan tetap harus orisinal dan profesional.
- **Validasi M0:** `composer ci` lulus; tampilan awal admin berhasil dibuka pada browser pengguna. Menu yang masih sedikit saat itu disebabkan milestone fitur belum selesai.
- **Status saat diperbarui:** M1 sudah selesai pada sesi berikutnya. Surat Masuk masih perlu audit M2; disposisi sederhana, surat keluar, laporan, dan dashboard lengkap masih perlu dikerjakan.
- **Berikutnya:** lanjutkan M2 — audit dan pelengkapan Surat Masuk. Setelah M1 di-commit dan digabung ke `develop`, branch M2 dibuat pengguna sesuai aturan Git proyek.

### 2026-10-21 — M1 Data Master, Pengguna, Profil

- **Selesai:** menambah halaman Data Instansi tunggal dengan logo PNG/JPG pada disk privat dan route logo yang memerlukan login; menambah seeder 9 klasifikasi dan data awal BPRS Sragen; memperbaiki halaman Data Pengguna agar kata sandi tidak muncul saat ubah, serta menyediakan reset kata sandi, aktivasi/nonaktifkan, dan hapus dengan proteksi akun sendiri/Admin aktif terakhir.
- **Selesai:** menambah halaman Riwayat Aktivitas baca-saja untuk Admin, audit perubahan master/pengguna/login, audit perubahan kata sandi tanpa menyimpan kata sandi, dan memastikan Profile memerlukan kata sandi lama untuk ganti kata sandi.
- **Keamanan:** logo dan berkas surat sama-sama memerlukan login dan tunduk pada timeout sesi; klasifikasi yang sudah dipakai surat tidak dapat dihapus.
- **Validasi:** `composer ci` lulus: Pint, PHPStan, 17 test/71 assertion termasuk 11 test M1. Test khusus M1 mencakup logo privat, proteksi akses, kata sandi, dan profil.
- **Tersisa:** jalankan migrasi dan seeder tambahan di Laragon; lengkapi alamat, telepon, email, logo, serta identitas pimpinan resmi pada Data Instansi sebelum dipakai mencetak dokumen. Nilai resmi tetap pertanyaan terbuka dan data awal hanya nama/kota.
- **Perintah Laragon:** `php artisan migrate --seed`, lalu `composer ci`. Jangan gunakan `migrate:fresh`/`db:wipe`.
- **Berikutnya:** M2 — audit kelengkapan Surat Masuk terhadap PRD, terutama pencarian, upload privat, nomor agenda, duplikat, soft delete, dan policy.
