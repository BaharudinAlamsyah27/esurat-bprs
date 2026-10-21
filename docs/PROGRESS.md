# Log Progres

## Status saat ini

- Milestone aktif berikutnya: **M1 — Data Master, Pengguna, Profil**
- M0: **Selesai untuk kode dan smoke test**; tampilan admin juga sudah terlihat pada screenshot browser pengguna. Tidak ada klaim UAT dengan pegawai akhir.
- Lingkup v2: aplikasi surat masuk/keluar sederhana; disposisi satu lembar cetak adalah M3.
- Database lama tidak dihapus. Migration baru diatur agar instalasi baru tidak membuat tabel fitur lama yang dibatalkan.

## Roadmap dan status

| Milestone | Status | Catatan |
|---|---|---|
| M0 — Fondasi & Login | Selesai (kode + smoke test) | Login username, level admin/user, seeder awal, timeout 30 menit, font lokal; `composer ci` lulus pada 2026-10-21 (6 test/28 assertion). Screenshot pengguna menunjukkan admin berhasil membuka aplikasi lokal. |
| M1 — Data Master, Pengguna, Profil | Sebagian tersedia | Resource klasifikasi/pengguna dan profil ada; Data Instansi, atur ulang password khusus, audit terkelola, aturan klasifikasi terpakai, serta test belum lengkap. |
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
- **Validasi:** menjalankan `composer ci`; Pint, PHPStan, dan seluruh 6 test (28 assertion) lulus.
- **Status:** belum ada milestone fitur lanjutan yang ditandai selesai. Menu yang masih sedikit di browser karena M1–M6 belum lengkap; surat keluar, lembar disposisi sederhana, laporan, dan dashboard lengkap masih perlu dikerjakan sesuai roadmap.
- **Berikutnya:** kerjakan M1 (Data Master, Pengguna, Profil) sesuai urutan roadmap. Pengguna menjalankan operasi Git/branch sesuai aturan AGENTS.md §8; saat ini branch bernama `feature/m3-disposisi-berjenjang` dan working tree masih berisi perubahan rombakan v2.
