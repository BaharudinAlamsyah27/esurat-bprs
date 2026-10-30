# Log Progres

## Status saat ini

- Milestone aktif: **Fase 3 — Ekosistem Hybrid Terpadu (Mobile APK Android + Web Core & PWA iPhone)** (dokumen acuan: `docs/fase 3/`)
- M0–M6 (Fase 1): **Selesai dan terverifikasi penuh** (41 test / 211 assertion).
- Fase 2: **Selesai dan terverifikasi penuh** (56 test / 276 assertion Pest lulus).
- Fase 3: **Dokumentasi blueprint arsitektur, PRD UX, topologi jaringan on-premise, dan SOP build APK telah selesai disusun di `docs/fase 3/`**.
- **Hambatan aktif (audit 2026-10-30):** `composer ci` belum hijau di lingkungan pengembangan karena ekstensi `pdo_sqlite` belum aktif; PHPStan masih 8 galat; pekerjaan Fase 2 & Fase 3 belum di-commit; push FCM belum dikonfigurasi.

## Roadmap dan status

| Milestone | Status | Catatan |
|---|---|---|
| M0 — Fondasi & Login | Selesai (kode + smoke test) | Login username, level admin/user, seeder awal, timeout 30 menit, font lokal; `composer ci` lulus pada 2026-10-21 (6 test/28 assertion). Screenshot pengguna menunjukkan admin berhasil membuka aplikasi lokal. |
| M1 — Data Master, Pengguna, Profil | Selesai (implementasi + CI + migrasi Laragon) | Data Instansi tunggal dengan logo privat, 9 klasifikasi, kelola pengguna/reset kata sandi, profil, audit baca-saja, policy dan proteksi Admin aktif terakhir. `composer ci` lulus; pengguna mengonfirmasi langkah M1 sudah dijalankan. |
| M2 — Surat Masuk | Selesai (implementasi + CI) | CRUD, agenda 4 digit/tahun, file privat, pencarian tiga kolom sesuai PRD, filter tahun/klasifikasi, duplikat dengan konfirmasi, soft delete Admin dan hak ubah pemilik. 6 tes M2 lulus. Perlu UAT browser oleh pengguna. |
| M3 — Lembar Disposisi sederhana | Implementasi + CI selesai; migrasi/UAT Laragon menunggu | Satu lembar per surat masuk, satu form, PDF A4 satu halaman, filter/lencana, penghitung cetak. Tabel alur lama diarsipkan tanpa menghapus data. |
| M4 — Surat Keluar | Implementasi + CI, migrasi Laragon, dan commit selesai; UAT browser menunggu | CRUD, nomor agenda/surat terkunci per tahun tanggal surat, pola tersimpan di `settings`, nomor manual Admin, lampiran privat, policy pemilik, soft delete Admin. `composer ci` lulus: 37 tes/183 assertion. |
| M5 — Laporan | Implementasi + CI + UAT browser + commit selesai | Pengguna mengonfirmasi laporan berhasil diuji di browser dan `composer ci` lulus. Komit `19b147d` telah masuk ke `develop`. Test otomatis khusus laporan belum ditambahkan. |
| M6 — Dashboard & Penyelesaian | Implementasi + CI selesai; UAT/staging menunggu | Kartu dinamis dan tautan filter, dua grafik enam bulan/klasifikasi, surat terbaru, bantuan kontekstual, header keamanan, cookie produksi, panduan pengguna/instalasi/backup/restore, skenario UAT, dan seeder data uji 10.000 surat tersedia. `composer ci` lulus (41 tes/211 assertion). Uji beban, restore staging, dan UAT belum dilakukan. |
| Fase 2 — Disposisi Digital & Mobile APK | Selesai (implementasi + 56 tes lulus) | Disposisi digital berbasis akun, status baru/dibaca/selesai, menu Disposisi Saya, notifikasi lonceng web & push FCM HTTP v1, token HP endpoint, pembungkus Capacitor mobile/ |
| Fase 3 — Ekosistem Hybrid Terpadu | Selesai (Kompilasi APK Sukses) | Blueprint lengkap di `docs/fase 3/`. Berkas Android APK (`esurat-bprs.apk`, 4.5 MB) berhasil dikompilasi via Gradle 8.2.1 & SDK 34 dan siap diunduh di `public/downloads/esurat-bprs.apk`. |

## Riwayat

### 2026-10-30 — Audit menyeluruh & penyiapan lanjutan

- **Dikerjakan:** mempelajari ulang `AGENTS.md`, roadmap, PRD/arsitektur/alur akses, panduan UX, dokumen Fase 2 & Fase 3, lalu memeriksa langsung kode, dokumentasi, dan kondisi lingkungan kerja.
- **Status kode:** Fase 1 (M0–M6) dan Fase 2 sudah terimplementasi. Pekerjaan Fase 2 & Fase 3 (disposisi digital, notifikasi lonceng & push FCM, token perangkat, pembungkus `mobile/` Capacitor, manifest PWA) **belum di-commit** — masih berada di branch `feature/m6-dashboard` sebagai perubahan belum tersimpan (42 file termodifikasi + sejumlah file baru belum dilacak).
- **Hambatan yang teridentifikasi:**
  1. PHP CLI lingkungan ini 8.4.27 **tanpa ekstensi `pdo_sqlite` aktif** → `php artisan test` gagal `could not find driver (Connection: sqlite)`. DLL `php_pdo_sqlite.dll` sebenarnya tersedia di folder `ext`. Akibatnya `composer ci` belum bisa hijau di lingkungan ini.
  2. PHPStan level 6 melaporkan **8 galat**: 7 di [MyDispositionResource.php](file:///c:/laragon/www/esurat/app/Filament/Resources/MyDispositionResource.php) (generik `Builder`, nullsafe berlebihan, `translatedFormat()` dipanggil pada string) dan 1 di [AppServiceProvider.php](file:///c:/laragon/www/esurat/app/Providers/AppServiceProvider.php#L77-L81) (`causedBy()` menerima `Authenticatable`).
  3. **Push FCM belum aktif:** `mobile/android/app/google-services.json` dan `storage/app/private/firebase-service-account.json` belum ada; `FCM_ENABLED` belum diisi pada `.env`.
- **Validasi:** `vendor/bin/pint --test` **LULUS** (161 file).
- **Belum bisa diverifikasi:** `composer ci` penuh (terhalang `pdo_sqlite`), UAT browser, dan pengiriman push FCM.

### 2026-10-27 — Fase 3 Ekosistem Hybrid Terpadu (Mobile APK Android + Web Core & PWA iPhone)

- **Selesai:**
  1. **Dokumentasi Lengkap:** Menyusun blueprint di [`docs/fase 3/00-RINGKASAN-FASE3.md`](file:///c:/laragon/www/esurat/docs/fase%203/00-RINGKASAN-FASE3.md), PRD UX di [`01-PRD-DAN-USER-EXPERIENCE-FASE3.md`](file:///c:/laragon/www/esurat/docs/fase%203/01-PRD-DAN-USER-EXPERIENCE-FASE3.md), dan arsitektur teknis di [`02-ARSITEKTUR-TEKNIS-DAN-INFRASTRUKTUR-FASE3.md`](file:///c:/laragon/www/esurat/docs/fase%203/02-ARSITEKTUR-TEKNIS-DAN-INFRASTRUKTUR-FASE3.md).
  2. **Inisialisasi Native Android (`mobile/android`):** Menyiapkan container Capacitor 6 dengan plugin `@capacitor/app`, `@capacitor/push-notifications`, dan `@capacitor/splash-screen`.
  3. **Keamanan Perbankan:** Mengonfigurasi `AndroidManifest.xml` (`allowBackup="false"`, `usesCleartextTraffic="true"`, dan izin `POST_NOTIFICATIONS` Android 13+).
  4. **Kompilasi Berkas APK:** Mengonfigurasi `local.properties` (Android SDK 34) dan Gradle 8.2.1. Berhasil menjalankan `assembleDebug` (160 task sukses, durasi 5 menit).
  5. **Penyediaan Berkas Siap Unduh:** File `app-debug.apk` (4,498,869 bytes) telah disalin ke [`public/downloads/esurat-bprs.apk`](file:///c:/laragon/www/esurat/public/downloads/esurat-bprs.apk) agar dapat diunduh langsung dari jaringan kantor melalui tautan `http://<IP-Server>:8080/downloads/esurat-bprs.apk`.

### 2026-10-27 — Fase 2 Disposisi Digital, Notifikasi Push & Pembungkus Android APK

- **Selesai:** implementasi backend dan frontend web untuk Fase 2 sesuai `docs/fase 2/00-RINGKASAN-FASE2.md`, `01-PRD-FASE2.md`, dan `02-ARSITEKTUR-DAN-API.md`:
  1. **Database & Migrasi:** membuat migrasi `2026_10_27_100000_fase2_disposition_recipients_and_device_tokens.php` (`position_title` dan `push_enabled` pada `users`, tabel `disposition_recipients`, tabel `device_tokens`, dan tabel standar `notifications`).
  2. **Model & Enum:** membuat enum `DispositionRecipientStatus` (`new`, `read`, `done`), model `DispositionRecipient`, model `DeviceToken`, relasi `recipients()` pada `Disposition`, serta relasi dan trait `Notifiable` pada `User`.
  3. **Action & Notifikasi:** memperbarui `SaveDisposition` agar menyinkronkan daftar penerima pegawai (dengan backward compatibility cetak PDF via `forwarded_to`), membuat action `MarkDispositionRead` dan `CompleteDispositionRecipient`, notifikasi `DispositionAssigned` dan `DispositionCompleted`, saluran `FcmChannel`, layanan `FcmClient` (FCM HTTP v1), serta action `RegisterDeviceToken` dan `RevokeDeviceToken`.
  4. **Filament Resource & Menu:**
     - Menu baru **"Disposisi Saya"** (`MyDispositionsResource`) untuk pegawai melihat lembar disposisi yang ditugaskan, otomatis menandai status `dibaca` saat dibuka, dan tombol modal `Tandai Selesai` dengan catatan tindak lanjut.
     - Lencana badge navigasi otomatis menampilkan jumlah disposisi berstatus `Baru`.
     - Lonceng notifikasi in-app Filament diaktifkan via `databaseNotifications()` dengan polling 30 detik.
     - Halaman admin **"Perangkat Terdaftar"** (`RegisteredDeviceResource`) untuk memantau dan mencabut token perangkat HP.
     - Form disposisi pada `IncomingLetterResource` diperbarui dengan pemilih multi-pegawai dan teks penerima luar, serta indikator progres tindak lanjut pada tabel surat.
     - Toggle profil pengguna "Terima notifikasi di HP" (`push_enabled`).
  5. **Endpoint Token HP & Listener Logout:** rute `POST /device-tokens` dan `DELETE /device-tokens` dengan validasi CSRF & throttle, serta event listener `RemoveDeviceTokensOnLogout` saat sesi berakhir.
  6. **Arsitektur Pembungkus Android (APK):** membuat folder `mobile/` dengan konfigurasi Capacitor (`capacitor.config.json`, `package.json`, `www/index.html`, `www/offline.html`) dan jembatan WebView `resources/js/mobile-bridge.js`.
  7. **Testing:** membuat test suite `tests/Feature/Fase2DigitalDispositionAndPushTest.php` (6 tes / 23 assertion lulus). Seluruh test suite (56 tes / 276 assertion) lulus tanpa regresi. Pint format check lulus.
  8. **Perbaikan Kompatibilitas Data Lama:** mengatasi error `ValueError: "pending" is not a valid backing value` dengan menambahkan case `Pending = 'pending'` dan `InProgress = 'in_progress'` pada `DispositionRecipientStatus`, serta memperbarui metode `markAsRead()` dan badge navigasi agar mendukung data riwayat terdahulu.
  9. **Perbaikan Kolom Database `done_note`:** mengatasi error `SQLSTATE[42S22]: Unknown column 'done_note'` saat menekan tombol "Tandai Selesai" dengan membuat migrasi patch `2026_10_27_103000_patch_disposition_recipients_columns.php` yang menambahkan kolom `done_note` (varchar 255), `user_id` (foreignId), dan `notified_at` (timestamp) ke tabel `disposition_recipients` serta mem-backfill data lama dari kolom `report_notes` & `recipient_user_id`. Migrasi telah berstatus `Ran` pada MySQL Laragon dan telah diuji sukses pada record disposisi lama. Cache aplikasi juga telah dibersihkan (`php artisan optimize:clear`).
  10. **Dukungan Standalone Web App (PWA) di iOS & Android:** menambahkan `manifest.json` dan meta tag `apple-mobile-web-app-capable`, `apple-touch-icon`, serta `apple-mobile-web-app-status-bar-style` pada `AdminPanelProvider` agar saat pengguna memilih "Tambah ke Layar Utama" di iPhone/iPad/Android, aplikasi terbuka penuh secara mandiri (fullscreen) tanpa bilah URL Safari.


### 2026-10-21 — M6 Dashboard & Penyelesaian (mulai)

- **Status awal:** branch aktif `feature/m6-dashboard`, worktree bersih. M5 (`19b147d`) dan M4 (`f944fbf`) sudah terintegrasi ke `develop`.
- **Dikerjakan:** dashboard dengan kartu yang terhubung ke daftar, grafik surat per bulan/klasifikasi, daftar surat terbaru, bantuan kontekstual; hardening header/cookie; seeder performa 10.000 surat dalam transaksi; panduan backup, instalasi, pengguna, dan UAT. Tes fitur M6 mencakup kartu, hak akses kartu Admin, data grafik, kondisi kosong, dan header keamanan.
- **Perbaikan dari CI:** model `OutgoingLetter` diberi trait factory yang sebelumnya belum terpasang; grafik bulanan kini memakai ekspresi tanggal SQLite saat tes berjalan di SQLite dan fungsi MySQL saat aplikasi berjalan di MySQL.
- **Polesan dashboard:** sumbu angka yang tidak relevan disembunyikan dari grafik donat; daftar surat terbaru diberi panel dan tata letak baris yang lebih rapi. CSS produksi dibangun dengan `npm run build` agar Laragon menyajikan tema terkini.
- **Ukuran dashboard:** kartu ringkasan dibuat satu baris pada layar desktop lebar, grafik ditampilkan berdampingan dengan tinggi maksimum 150 px, dan daftar surat terbaru dirapatkan agar isi utama lebih banyak terlihat tanpa menggulir pada layar desktop. `npm run build` dan `composer ci` lulus setelah perubahan terakhir (41 tes/211 assertion). Pada layar sempit, konten tetap menyesuaikan lebar dan dapat memanjang ke bawah.
- **Tata letak desktop:** widget kustom kini memakai pembungkus grid Filament supaya `columnSpan` berlaku; sapaan dipindah ke subjudul, kartu ringkasan mengisi satu baris pada layar lebar, dua grafik berdampingan, dan surat terbaru memakai satu baris penuh. Kartu, grafik, baris surat, dan jarak dipadatkan untuk mengurangi tinggi halaman.
- **Penyegaran tampilan akhir:** sidebar sekarang dapat ditutup sepenuhnya pada desktop dengan tombol hamburger; logo BPRS Sragen yang diberikan pengguna dipasang di header panel; mode terang/gelap bawaan Filament tersedia dengan mode terang sebagai pilihan awal, sementara pilihan pengguna tetap dapat diubah. Mode gelap memakai latar abu-abu kebiruan, header/sidebar lebih terang dari latar halaman, ikon menu berkontras jelas, dan kartu dashboard ikut menyesuaikan. Logo antarmuka ini terpisah dari logo Data Instansi yang dipakai pada PDF dan laporan. CSS tetap memakai font lokal dan tanpa dependensi baru.
- **Polesan daftar dan warna:** sidebar dipersempit menjadi 17rem agar ruang tabel lebih lega. Daftar surat, pengguna, klasifikasi, dan aktivitas memiliki petunjuk singkat serta baris selang-seling; laporan menampilkan jumlah hasil. Admin dapat memilih aksen Hijau BPRS, Biru, atau Merah Marun dari Data Instansi; pilihan tersimpan pada `settings` yang sudah ada dan berlaku untuk semua role setelah halaman dimuat ulang. Header/sidebar memakai rona pilihan, area kerja tetap netral, termasuk mode gelap.
- **Standar tabel daftar:** permintaan lanjutan memperjelas kolom data harus tetap di kiri dan aksi di kanan. Aksi hapus dipindahkan dari daftar ke bagian bawah halaman Ubah; daftar surat dibatasi lima kolom data agar total dengan aksi tidak lebih dari enam. Search dibuat lebar dan rata kiri, breadcrumb/petunjuk tabel ganda dihapus, tombol tambah dibuat spesifik, pagination dimulai dari 10/25, nilai kosong memakai tanda “—”, tombol tambah tersedia pada kondisi kosong, dan urutan menu ditetapkan. Pola ini ditambahkan ke §10 panduan UX. Tes Pest mencakup default sort, ketiadaan Hapus pada daftar, tombol tambah kondisi kosong, alasan blokir klasifikasi terpakai, visibilitas menu role, dan urutan DATA MASTER.
- **Pemeriksaan kode:** verifikasi melalui terminal Laragon belum dilakukan setelah revisi UI tabel. Pengguna akan menjalankan `composer ci` dan `npm run build`; hasil pemeriksaan yang berjalan saat sesi sebelumnya terputus belum dikonfirmasi.
- **Perintah untuk dijalankan pengguna:** dari terminal Laragon di folder proyek, jalankan `composer ci`, lalu `npm run build`. Jangan commit sebelum keduanya berhasil.
- **Belum bisa diverifikasi:** hasil CI/build terbaru dan tampilan tabel di browser Laragon, terutama posisi kolom aksi di kanan dan perilaku tampilan pada ukuran layar berbeda.
- **Perbaikan dari hasil CI pengguna:** akun baru sebelumnya terhitung memiliki riwayat hanya karena catatan audit pembuatan akun. Pengecekan kini mengabaikan event `created` pada akun itu sendiri, tetapi tetap melindungi pengguna yang melakukan aksi audit atau memiliki surat tercatat. Tes regresi ditambahkan; hasil harus diverifikasi ulang oleh pengguna dengan `composer ci`.
- **Polesan daftar surat:** tombol aksi Surat Masuk/Keluar dilepas dari gaya kotak dan dirapikan sebagai tautan berikon; ukuran teks dan jarak baris tabel dipadatkan, lebar kolom data surat dibatasi, dan label aksi disposisi diseragamkan. Pengguna perlu menjalankan `npm run build` dan memuat ulang halaman Laragon untuk melihat hasilnya.
- **Palet warna profesional:** latar halaman menjadi abu sangat terang dengan kartu putih; header/sidebar kembali netral. Pemilih warna kini berisi Hijau BPRS, Biru, Marun, dan Teal, semuanya hanya mengubah aksen. Nilai tema disimpan melalui `settings` yang sudah ada; Admin saja yang dapat mengubahnya. Lencana filter bernilai nol disembunyikan. Panduan token warna dan tes pilihan/simpan/otorisasi/default tema diperbarui.
- **Perintah untuk dijalankan pengguna:** `composer ci`, lalu `npm run build` dari terminal Laragon di folder proyek.
- **Belum bisa diverifikasi:** hasil `composer ci` dan build CSS untuk palet baru, serta tampilan terang/gelap di browser setelah tema disimpan dan halaman dimuat ulang.
- **Perbaikan tampilan lanjutan:** sidebar diberi latar opak agar tidak tembus oleh backdrop saat layar menyempit; aksi Ubah pada klasifikasi/pengguna kini tanpa bingkai; tinggi baris tabel dirapatkan.
- **Sidebar saat berbagi layar:** latar solid sidebar/header kini juga didefinisikan oleh hook tema server, bukan hanya aset Vite. Ini mencegah variabel latar kosong membuat sidebar transparan saat jendela masuk mode overlay pada ukuran sempit. Perlu dimuat ulang dan diperiksa pada jendela setengah layar.
- **Perbaikan PHPStan:** hitungan badge filter sekarang diambil dari `Filament\Tables\Table` yang memang menyediakan `getActiveFiltersCount()`, bukan dari kontrak Livewire `HasTable`. Minta pengguna mengulang `composer ci` untuk memastikan.
- **Belum diverifikasi:** uji 10.000 data pada database lokal, backup dan restore di staging, serta UAT dengan sedikitnya tiga pegawai senior.

### 2026-10-21 — M5 Laporan (implementasi awal)

- **Dikerjakan:** halaman laporan masuk/keluar dengan rentang tanggal (default bulan berjalan), pilihan tanggal surat/diterima/keluar, filter klasifikasi, batas rentang 366 hari, hasil berpaginasi, tampilan cetak berkop, PDF A4 lanskap, dan ekspor XLSX dengan heading berbahasa Indonesia.
- **Dependency:** pengguna menjalankan `composer require maatwebsite/excel`; versi 3.1.70 kini ada di `composer.lock` dan `vendor`. Composer menyimpan constraint wildcard `*` di `composer.json`; patok ke `^3.1.70` dengan `composer require "maatwebsite/excel:^3.1.70"` agar tidak mencoba versi 4 yang meminta PHP 8.3.
- **Perbaikan kualitas:** Pint dan PHPStan sempat menemukan masalah format serta 17 masalah tipe pada ekspor/paginasi/tanggal; sudah diperbaiki. Pengguna mengonfirmasi `composer ci` lulus setelah perbaikan.
- **UAT:** pengguna mengonfirmasi UAT laporan sudah berjalan dan berhasil. `composer ci` juga dikonfirmasi lulus.
- **Tersisa:** test otomatis khusus laporan belum ditambahkan. Pengguna kemudian mengonfirmasi M5 telah di-commit dan perintah Git untuk memulai M6 sudah dijalankan.
- **Catatan Git:** komit M5 `19b147d` berada di `develop`; M4 `f944fbf` menjadi induknya. Pekerjaan M6 berlangsung di branch `feature/m6-dashboard`.

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

### 2026-10-29 — Tahap A: APK Android berfungsi & alamat server bisa diatur

- **Selesai:** memuat `resources/js/app.js` di panel Filament agar `mobile-bridge.js` benar-benar berjalan di dalam APK (registrasi token FCM dan tombol kembali Android). Bukti: halaman login kini memuat `app-*.js`; sebelumnya hanya `theme-*.css`.
- **Selesai:** menambah `mobile/build-apk.ps1` yang menulis alamat server ke `capacitor.config.json`, menjalankan `cap sync`, membangun APK, lalu menyalinnya ke `public/downloads/esurat-bprs.apk`. IP kantor atau domain dapat diganti tanpa mengubah kode Laravel.
- **Validasi:** skrip dijalankan dengan `-ServerUrl http://192.168.10.199:8080` → `BUILD SUCCESSFUL` dan APK tersalin. Pint lulus untuk file yang diubah.
- **Belum diverifikasi:** `composer ci` belum lulus di lingkungan ini karena ekstensi `pdo_sqlite` tidak aktif (phpunit memakai sqlite `:memory:`) dan PHPStan melaporkan 8 galat lama di `MyDispositionResource` serta `AppServiceProvider` yang tidak terkait perubahan ini.
- **Tersisa (penghambat notifikasi):** `mobile/android/app/google-services.json` belum ada sehingga plugin Firebase tidak dipasang saat build; server juga belum punya `storage/app/private/firebase-service-account.json` dan `FCM_ENABLED=true`.
- **Perintah:** `powershell -ExecutionPolicy Bypass -File mobile\build-apk.ps1 -ServerUrl <alamat-server>`.
