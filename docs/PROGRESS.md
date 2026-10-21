# Log Progres

## Status saat ini

- Milestone aktif: **M4 — Data Surat Keluar** (implementasi + CI selesai; menunggu migrasi dan UAT Laragon)
- M0: **Selesai untuk kode dan smoke test**; tampilan admin juga sudah terlihat pada screenshot browser pengguna. Tidak ada klaim UAT dengan pegawai akhir.
- Lingkup v2: aplikasi surat masuk/keluar sederhana; disposisi satu lembar cetak adalah M3.
- Database lama tidak dihapus. Migration baru diatur agar instalasi baru tidak membuat tabel fitur lama yang dibatalkan.

## Roadmap dan status

| Milestone | Status | Catatan |
|---|---|---|
| M0 — Fondasi & Login | Selesai (kode + smoke test) | Login username, level admin/user, seeder awal, timeout 30 menit, font lokal; `composer ci` lulus pada 2026-10-21 (6 test/28 assertion). Screenshot pengguna menunjukkan admin berhasil membuka aplikasi lokal. |
| M1 — Data Master, Pengguna, Profil | Selesai (implementasi + CI + migrasi Laragon) | Data Instansi tunggal dengan logo privat, 9 klasifikasi, kelola pengguna/reset kata sandi, profil, audit baca-saja, policy dan proteksi Admin aktif terakhir. `composer ci` lulus; pengguna mengonfirmasi langkah M1 sudah dijalankan. |
| M2 — Surat Masuk | Selesai (implementasi + CI) | CRUD, agenda 4 digit/tahun, file privat, pencarian tiga kolom sesuai PRD, filter tahun/klasifikasi, duplikat dengan konfirmasi, soft delete Admin dan hak ubah pemilik. 6 tes M2 lulus. Perlu UAT browser oleh pengguna. |
| M3 — Lembar Disposisi sederhana | Implementasi + CI selesai; migrasi/UAT Laragon menunggu | Satu lembar per surat masuk, satu form, PDF A4 satu halaman, filter/lencana, penghitung cetak. Tabel alur lama diarsipkan tanpa menghapus data. |
| M4 — Surat Keluar | Implementasi + CI dan migrasi Laragon selesai; UAT browser menunggu | CRUD, nomor agenda/surat terkunci per tahun tanggal surat, pola tersimpan di `settings`, nomor manual Admin, lampiran privat, policy pemilik, soft delete Admin. `composer ci` lulus: 37 tes/183 assertion. |
| M5 — Laporan | Belum dimulai | Laporan surat masuk/keluar, cetak, PDF, Excel. |
| M6 — Dashboard & Penyelesaian | Sebagian tersedia | Sapaan, kartu placeholder, pengaturan ukuran huruf, dan font lokal tersedia; grafik, ringkasan nyata, bantuan, hardening, backup, panduan, dan UAT tersisa. |

## Riwayat

### 2026-10-21 — M4 Data Surat Keluar

- **Selesai:** membuat tabel/model surat keluar, pengaturan pola nomor, generator nomor surat dan agenda yang dikunci dengan `lockForUpdate`, CRUD Filament, pencarian tiga kolom, filter tahun/klasifikasi, halaman rincian, policy, audit perubahan, dan penghapusan lunak Admin.
- **Berkas:** memakai ulang tabel attachment polymorfik dan route privat terautentikasi; validasi PDF/JPG/PNG maks. 10 MB, SHA-256, penggantian berkas menghapus berkas lama setelah transaksi berhasil.
- **Nomor:** pola awal `{seq:3}/{kode_klasifikasi}/BPRS-SRG/{bulan_romawi}/{tahun}`. Admin dapat mengisi nomor manual yang unik; User selalu mendapat nomor otomatis. Tahun dan bulan mengikuti tanggal surat.
- **Validasi kode:** `composer ci` lulus (Pint, PHPStan, 37 tes/183 assertion); `git diff --check` bersih. Tes mencakup 50 nomor berurutan, pola dari settings, pergantian tahun, nomor manual dan duplikat, nomor otomatis yang melewati benturan manual, form User, upload privat/hash, penggantian file, batas akses dan audit.
- **Selesai:** migration M4 berhasil diterapkan di database MySQL Laragon. Database sudah memiliki tabel `settings` lama dengan skema yang cocok, sehingga migration disesuaikan agar tabel tersebut dipakai tanpa dibuat ulang atau dihapus saat rollback. `outgoing_letters` sekarang tersedia; migration berstatus `Ran`.
- **Belum diverifikasi:** UAT browser oleh pengguna. Uji sebagai Admin dan User: catat surat, cocokkan perkiraan dan nomor final, cari/filter, lihat rincian/berkas, ubah data, ganti berkas, pastikan User tidak dapat mengubah surat orang lain dan Admin dapat hapus lunak.
- **Perintah Laragon:** migration tidak perlu diulang; `composer ci` lulus. Setelah UAT, commit branch M4 sebelum menyiapkan M5.

### 2026-10-21 — Verifikasi login pengguna

- **Selesai:** menambahkan tes regresi yang memastikan akun level Pengguna dengan username dan kata sandi valid bukan hanya berhasil autentikasi, tetapi juga dapat membuka halaman utama aplikasi. Tes autentikasi M0 lulus (5 test/27 assertion).
- **Hasil pemeriksaan:** implementasi reset kata sandi menyimpan hash melalui cast Laravel; kata sandi lama memang tidak bisa dibaca. Seeder demo hanya membuat akun baru dan tidak mengubah kata sandi akun yang sudah ada. Nama pengguna pada daftar adalah nilai yang harus dimasukkan di kolom login.
- **Belum diverifikasi:** login aktual tiap akun di browser pengguna. Butuh UAT setelah Admin mengatur ulang kata sandi di Data Pengguna untuk satu akun yang diuji.
- **Langkah UAT:** Admin pilih Data Pengguna → Atur Ulang Kata Sandi pada satu akun Pengguna aktif, simpan kata sandi baru (minimal 10 karakter, huruf besar/kecil dan angka), keluar, lalu masuk memakai username persis dari tabel dan kata sandi yang baru disetel. Jika akun kosong username (mis. Test User), Admin harus mengisi username unik terlebih dahulu.

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

### 2026-10-21 — M2 Data Surat Masuk

- **Selesai:** audit dan pelengkapan M2. Nomor agenda kini mengikuti format PRD `0001/YYYY`; pencarian hanya mencakup nomor surat, nama pengirim, dan perihal; placeholder pencarian diperjelas.
- **Selesai:** perubahan surat masuk tercatat di riwayat aktivitas dengan daftar nama kolom yang berubah saja (tanpa menyalin isi surat ke log). Peringatan duplikat tetap meminta konfirmasi sebelum penyimpanan.
- **Validasi:** `composer ci` lulus (Pint, PHPStan, 23 test/102 assertion). Enam tes M2 mencakup nomor agenda berurutan, duplikat, upload privat + SHA-256, penolakan tipe file, akses login untuk berkas, dan hak ubah/hapus/audit.
- **Migrasi Laragon:** `php artisan migrate:status` menunjukkan semua migrasi sudah berjalan; tidak ada migrasi baru pada sesi M2.
- **Belum diverifikasi:** pengguna perlu mencoba daftar/cari surat, catat/edit surat, pratinjau file, dan hapus sebagai Admin dari browser Laragon. Rendering tabel Livewire tidak dapat diuji di runtime ini karena ekstensi PHP `intl` tidak tersedia; `composer ci` tetap lulus.
- **Berikutnya:** setelah pengguna menyimpan perubahan M2 pada Git, lanjutkan M3 — Lembar Disposisi sederhana.

### 2026-10-21 — M3 Lembar Disposisi sederhana

- **Selesai:** menambahkan satu form disposisi pada daftar Surat Masuk, termasuk Sifat, tujuan (satu nama/jabatan per baris), checklist instruksi, instruksi lainnya, catatan, dan tanggal. Admin dapat membuat/mengubah; Admin dan User dapat mencetak lembar yang telah dibuat.
- **Selesai:** membuat PDF A4 satu halaman berisi kop Data Instansi, semua data surat, isi disposisi, blok tanda tangan pimpinan, serta penghitung dan audit setiap cetak. Daftar Surat Masuk menampilkan status dan filter Sudah/Belum didisposisi.
- **Keamanan/data:** validasi dilakukan di action, satu lembar dijamin unik per surat, dan log tidak menyimpan tujuan/catatan. Tabel lama `dispositions` (skema berjenjang) akan diubah nama menjadi `legacy_dispositions`; semua datanya tetap disimpan.
- **Validasi kode:** 4 tes M3 lulus (25 assertion), termasuk batas Admin/User, satu lembar per surat, isi PDF, satu halaman, penghitung cetak, dan audit.
- **Perintah Laragon:** jalankan `php artisan migrate` lalu `composer ci`. Setelah migrasi, UAT sebagai Admin: buat disposisi, simpan saja, ubah, simpan & cetak; sebagai User: pastikan tombol buat/ubah tidak ada dan tombol cetak berfungsi. Jangan jalankan `migrate:fresh` atau `db:wipe`.
- **Belum diverifikasi:** migrasi M3 dan tampilan tombol/modal di browser Laragon. Database saat ini memiliki tabel disposisi lama; migrasi M3 dirancang untuk mengarsipkannya sebelum membuat tabel baru.
- **Berikutnya:** setelah pengguna menjalankan migrasi dan UAT, commit M3 lalu lanjutkan M4 — Surat Keluar.
