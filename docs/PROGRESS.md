# Log Progres

> **PER 2026-11-02:** proyek dilanjutkan ke Codex (ChatGPT). Bacalah `docs/HANDOVER-CODEX.md` lebih dulu — berisi ringkasan pekerjaan, langkah selanjutnya, dan masalah yang diketahui.

## Status saat ini

- **2026-11-18 — Audit menyeluruh & pembersihan repositori (Web & Mobile Flutter):**
  - **Penghapusan proyek duplikat & artefak tidak terpakai:** menghapus seluruh folder warisan `mobile/` (pembungkus Capacitor WebView lama), `public/downloads/esurat-bprs.apk` (APK debug Capacitor lama), `resources/js/mobile-bridge.js`, serta runner desktop C++ `mobile_flutter/windows/` yang tidak diperlukan untuk APK Android.
  - **Pembersihan file sampah & dead code:** menghapus `bootstrap/cache/codextest.tmp`, `mobile_flutter/build-apk.log`, `database/database.sqlite` (0 byte), `app/Http/Resources/Api/IncomingLetterCollection.php`, `resources/views/welcome.blade.php`, `tests/Unit/ExampleTest.php`, folder kosong (`app/Jobs`, `app/Support`, `resources/views/verification`, `tests/Unit`), 15 file `.gitkeep` mubazir, serta membersihkan cache kompilasi sementara (`mobile_flutter/build`, `mobile_flutter/.dart_tool`, `storage/framework/cache/phpstan`, `storage/framework/views`).
  - **Perapihan arsitektur & lokalisasi Web:** menggabungkan `MasterDataResource` langsung ke `ClassificationResource`, memindahkan seluruh teks hardcode pada `MyDispositionResource` dan `RegisteredDeviceResource` ke `lang/id/disposition.php` & `lang/id/m1.php`, menghapus key ganda di `lang/id/app.php`, serta menyamakan pola tabel Filament.
  - **Penguatan integritas data & Audit Trail API:** melengkapi `User::hasUsageHistory()` agar memeriksa `cooperation_agreements` dan `dispositions`; melengkapi `Classification::usageCount()` dengan `withTrashed()`; menambahkan pencatatan `activity('authentication')` untuk login, gagal login, dan logout pada API mobile (`Api\AuthController`).
  - **Perbaikan aplikasi Flutter (`mobile_flutter/`):** menghapus 3 dependensi tidak terpakai di `pubspec.yaml` (`cupertino_icons`, `flutter_spinkit`, `url_launcher`), menghapus method mati di `ApiService`, mendukung pratinjau lampiran gambar (`jpg/png`) & penghapusan file sementara di `PdfViewerScreen`, serta melengkapi form Buat/Ubah Disposisi di HP (`_DispositionFormSheet`) dengan pratinjau data disposisi lama dan kolom input `instruction_other` saat memilih instruksi "Lainnya".
  - **Normalisasi format:** menyeragamkan akhir baris (line endings) `CRLF` menjadi `LF` sesuai `.gitattributes`.
- **2026-11-16 — Penyelesaian 5 celah fungsional Web & Mobile (poin 2):**
  - **Disposisi Saya (`MyDispositionResource`):** filter `user_id` kini berlaku untuk semua role termasuk Admin/Superadmin, sehingga lencana dan daftar hanya menampilkan disposisi milik akun yang sedang login.
  - **PKS di Beranda & Laporan:** ditambahkan kartu ringkasan **Perjanjian Kerjasama** di `SummaryCardsWidget` serta halaman **Laporan Kerjasama (PKS)** lengkap dengan cetak, unduh PDF, dan ekspor Excel (`CooperationAgreementsReport`, `CooperationReportController`, `CooperationAgreementsExport`).
  - **Konsistensi label di Flutter (`mobile_flutter/`):** tab **Surat** disamakan menjadi **Arsip** pada `MainShell` dan `LettersTab`.
  - **Buat/Ubah Disposisi dari HP:** ditambahkan `GET /api/disposition-options` & `POST /api/incoming-letters/{incomingLetter}/disposition` beserta tombol/sheet disposisi di `IncomingLetterView` khusus Admin/Superadmin.
  - **Ringkasan Beranda APK Akurat:** ditambahkan `GET /api/dashboard/summary` (`DashboardController`) dan `HomeProvider` kini memakai angka langsung dari server (tanpa batas proksi 50 data).
  - Verifikasi: `composer ci` **LULUS** (Pint, PHPStan 0 galat, **79 tes / 386 assertion**); `flutter analyze` **0 isu**; `flutter test` **14/14 lulus**.
- **2026-11-16 — Penyesuaian menu `ARSIP`, tabel & filter `Surat Masuk`, serta tanda tangan digital instansi:**
  - Grup menu sidebar `SURAT` diganti menjadi **`ARSIP`** (mencakup *Disposisi Saya*, *Surat Masuk*, *Surat Keluar*, *Perjanjian Kerjasama*).
  - Tabel **Surat Masuk** (`IncomingLetterResource`): kolom *No. Agenda* dan *Nomor surat* digabung menjadi 1 kolom (**Nomor Surat & Agenda**), dan ditambahkan kolom **Tanggal diterima** (`received_at`) di samping **Tanggal surat** (`letter_date`).
  - Filter **Surat Masuk** ditingkatkan sesuai praktik terbaik arsip kantor: filter **Klasifikasi** (`kode — nama`, searchable & preload), **Tahun agenda**, **Status disposisi**, dan **Rentang tanggal diterima** (`received_date_range`).
  - `MasterDataSeeder` diperkaya dengan keterangan kantor/perbankan pada 9 klasifikasi standar (`100`–`900`).
  - **Data Instansi** (`InstitutionSettings` + migrasi `signature_path`): ditambah upload **Tanda tangan digital pimpinan** (disimpan di disk `private`), yang otomatis ditampilkan di PDF **Lembar Disposisi** (`DispositionPdfService` + `pdf/disposition.blade.php`).
  - Verifikasi: `composer ci` **LULUS** (Pint, PHPStan 0 galat, **76 tes / 366 assertion**).
- **2026-11-16 — Penambahan modul Perjanjian Kerjasama (PKS) di Web, API Mobile, dan APK Flutter:**
  - Tabel baru `cooperation_agreements` (`bss_pks_number`, `partner_pks_number`, `partner_npwp`, `agreement_date`, `partner_name`, `subject`, `pic_name`, `note`, `SoftDeletes`).
  - Penyimpanan privat untuk **Berkas PKS** (`kind=pks_file`) dan **Legalitas Mitra** seperti KTP/NIB/Akta (`kind=partner_legality`) dengan validasi MIME (`pdf, jpg, jpeg, png`, maks 10 MB), hash SHA-256, dan rute terautentikasi web & API.
  - Policy `CooperationAgreementPolicy` (semua user aktif dapat melihat & mencatat; ubah oleh pemilik/Admin; soft delete hanya Admin) dan tercatat di Audit Trail.
  - API `/api/cooperation-agreements` (`index`, `show`) & `AttachmentController` diperluas untuk melayani berkas PKS/legalitas ke mobile.
  - Aplikasi Flutter (`mobile_flutter/`): ditambah tab **Kerjasama** pada menu **Surat** beserta pencarian, rincian 9 field PKS, serta tombol buka **Berkas PKS** dan **Legalitas**.
  - Verifikasi: `composer ci` **LULUS** (Pint, PHPStan 0 galat, **76 tes / 365 assertion**); `flutter analyze` **0 isu**; `flutter test` **14/14 lulus**. Pengguna perlu menjalankan `php artisan migrate` di Laragon serta build ulang APK jika ingin menguji di HP.
- **2026-11-16 — Perbaikan inti logika disposisi (Web & API Mobile):**
  - `DispositionController` (API mobile) kini memakai action resmi `MarkDispositionRead` dan `CompleteDispositionRecipient`, sehingga baca/selesai lewat HP tercatat di Audit Trail dan mengirim notifikasi selesai ke pembuat disposisi.
  - Batas panjang `note` di API diselaraskan menjadi `max:255` agar cocok dengan kolom `disposition_recipients.done_note` (`varchar(255)`).
  - `MarkDispositionRead` mendukung status legacy `pending`, dan `CompleteDispositionRecipient` menjaga agar disposisi yang sudah selesai tidak menimpa waktu selesai atau mengirim notifikasi ganda.
  - Form ubah disposisi di `IncomingLetterResource` kini mempertahankan isian `other_recipients` (penerima eksternal) meski penerima akun pegawai juga terisi.
  - Verifikasi: `composer ci` **LULUS** (Pint, PHPStan 0 galat, **70 tes / 332 assertion**).
- **2026-11-06 — Pengaturan alamat server APK selama pengujian:** layar login Flutter kini menyediakan tautan untuk mengubah URL server tanpa membangun ulang APK. Alamat disimpan lokal di perangkat, dipakai oleh seluruh permintaan API, dan bisa menerima URL `http://host:port` (path `/api` ditambahkan otomatis) atau HTTPS. Alamat bawaan tetap mengikuti `API_BASE_URL`/IP lama. Tes widget diperbarui untuk alur ubah alamat; fitur ini sementara untuk masa tes. Belum diverifikasi dengan `flutter analyze` dan `flutter test` oleh pengguna.
- **Tindak lanjut kegagalan test Akun:** `ensureVisible` gagal karena finder mengabaikan tombol Keluar yang masih di luar viewport ListView. Test kini menggulir `Scrollable` milik `AccountTab` sampai tombol terlihat sebelum menekannya. Menunggu verifikasi ulang pengguna.
- **Perbaikan stabilitas test Flutter:** interaksi test yang membuka dialog alamat server dan memfokuskan TextField dihapus karena memicu assertion `_FocusInheritedScope` antar-test pada runner Flutter. Test dialog UI dasar tetap memeriksa tombol; penyimpanan, normalisasi `/api`, persistensi, dan validasi URL sekarang diuji langsung di `ApiServerSettings` tanpa membuka dialog.
- **Perintah untuk dijalankan pengguna:** di PowerShell buka `C:\laragon\www\esurat\mobile_flutter`, lalu jalankan `flutter analyze`, `flutter test`, dan bila lulus `flutter build apk --release`. URL bisa diubah dari layar login APK tanpa mengulang build.
- **Belum bisa diverifikasi:** build APK dan uji koneksi alamat baru perlu dijalankan pengguna. Fitur pengaturan URL perlu dilepas atau dinonaktifkan sebelum rilis produksi sesuai permintaan.

- Milestone aktif: **Fase 3 — Ekosistem Hybrid Terpadu (Mobile APK Android Flutter + Web Core & PWA iPhone)** (dokumen acuan: `docs/fase 3/`)
- M0–M6 (Fase 1): **Selesai dan terverifikasi penuh** (41 test / 211 assertion).
- Fase 2: **Selesai dan terverifikasi penuh** (56 test / 276 assertion Pest lulus).
- Fase 3: API mobile berjalan dan pengguna telah mengonfirmasi pop-up FCM muncul setelah kunci Firebase server ditempatkan pada path yang sesuai. Firebase lokal masih memakai proyek uji pribadi; sebelum deploy perlu beralih ke proyek kantor.
- **Status verifikasi terakhir yang tercatat (2026-10-30):** `composer ci` **LULUS** (Pint, PHPStan 0 galat, **67 tes / 317 assertion**); `flutter analyze` **tanpa masalah**; `flutter build apk --release` **berhasil**. Hasil ini mendahului migrasi/perbaikan tanggal 2026-11-02, jadi CI perlu dijalankan ulang.
- **Tersisa sebelum serah terima:** pastikan migrasi FK tercatat `Ran`; jalankan ulang CI PHP dan pemeriksaan Flutter; bangun serta uji APK terbaru; ganti Firebase uji dengan proyek kantor; siapkan deployment HTTPS dan backup.
- **Perubahan akses terbaru:** tiga level (`superadmin`, `admin`, `user`) telah diterapkan pada kode dan dokumentasi. Sebelum presentasi/deploy, pengguna perlu menjalankan migrasi baru dan `composer ci`, lalu memastikan akun yang seharusnya menjadi Superadmin sesuai aturan promosi migrasi.

## Roadmap dan status

| Milestone | Status | Catatan |
|---|---|---|
| M0 — Fondasi & Login | Selesai; pemisahan role terbaru menunggu migrasi/CI | Login username, level superadmin/admin/user, seeder awal, timeout 30 menit, font lokal; `composer ci` lama mendahului pemisahan role. |
| M1 — Data Master, Pengguna, Profil | Selesai; hierarki role baru menunggu migrasi/CI | Data Instansi tunggal dengan logo privat, 9 klasifikasi, kelola pengguna/reset kata sandi, profil, audit baca-saja, policy dan proteksi Superadmin aktif terakhir. |
| M2 — Surat Masuk | Selesai (implementasi + CI) | CRUD, agenda 4 digit/tahun, file privat, pencarian tiga kolom sesuai PRD, filter tahun/klasifikasi, duplikat dengan konfirmasi, soft delete Admin dan hak ubah pemilik. 6 tes M2 lulus. Perlu UAT browser oleh pengguna. |
| M3 — Lembar Disposisi sederhana | Implementasi selesai; perbaikan FK ditambahkan dan pengguna melaporkan simpan disposisi kembali berjalan | Satu lembar per surat masuk, satu form, PDF A4 satu halaman, filter/lencana, penghitung cetak. Migrasi mengarsipkan tabel penerima lama dan mengarahkan FK aktif ke `dispositions`; status penerapan perlu dipastikan sebelum serah terima. |
| M4 — Surat Keluar | Implementasi + CI, migrasi Laragon, dan commit selesai; UAT browser menunggu | CRUD, nomor agenda/surat terkunci per tahun tanggal surat, pola tersimpan di `settings`, nomor manual Admin, lampiran privat, policy pemilik, soft delete Admin. `composer ci` lulus: 37 tes/183 assertion. |
| M5 — Laporan | Implementasi + CI + UAT browser + commit selesai | Pengguna mengonfirmasi laporan berhasil diuji di browser dan `composer ci` lulus. Komit `19b147d` telah masuk ke `develop`. Test otomatis khusus laporan belum ditambahkan. |
| M6 — Dashboard & Penyelesaian | Implementasi + CI selesai; UAT/staging menunggu | Kartu dinamis dan tautan filter, dua grafik enam bulan/klasifikasi, surat terbaru, bantuan kontekstual, header keamanan, cookie produksi, panduan pengguna/instalasi/backup/restore, skenario UAT, dan seeder data uji 10.000 surat tersedia. `composer ci` lulus (41 tes/211 assertion). Uji beban, restore staging, dan UAT belum dilakukan. |
| Fase 2 — Disposisi Digital & Mobile APK | Implementasi selesai (56 tes lulus) | Disposisi digital berbasis akun, status baru/dibaca/selesai, menu Disposisi Saya, notifikasi lonceng web & push FCM HTTP v1, endpoint token HP. Pembungkus Capacitor lama diarsipkan; aplikasi mobile aktif memakai Flutter. |
| Fase 3 — Ekosistem Hybrid Terpadu | Pengguna mengonfirmasi pop-up FCM muncul di HP; uji dan paket rilis akhir masih perlu dibereskan | APK Flutter memakai API Bearer token. Token perangkat didaftarkan/dicabut melalui API, notifikasi membuka rincian disposisi setelah autentikasi, dan payload FCM tetap umum. Konfigurasi lokal memakai Firebase uji; sebelum serah terima, pindahkan ke proyek Firebase milik kantor. |

## Riwayat

### 2026-11-03 — Audit jalur push FCM setelah laporan pop-up tidak muncul

- **Pemeriksaan kode:** jalur server menyaring akun aktif, pilihan `push_enabled`, token per akun, dan sesi API yang masih aktif. Permintaan FCM memakai payload `notification`, prioritas Android `high`, serta channel ID `disposisi_high_priority`; ID itu sama dengan deklarasi manifest dan channel ber-importance `HIGH` pada APK. Izin Android `POST_NOTIFICATIONS` diminta setelah login. Saat APK foreground, handler saat ini sengaja menampilkan SnackBar dalam aplikasi, bukan notifikasi sistem.
- **Bukti log pengguna (3 Nov 2026):** Firebase menerima pengiriman untuk `device_id` 5, 6, dan 7. Pada dua peristiwa ada penerima lain tanpa token HP. `accepted by Firebase` berarti endpoint FCM menerima permintaan, bukan bukti Android mengirimkan notifikasi ke tray atau menampilkan banner.
- **Temuan diagnostik:** versi APK yang dikirim ke server dikodekan tetap `1.0.0`, sehingga tabel Perangkat Terdaftar tidak bisa membuktikan apakah APK terakhir telah dipasang. Kolom `last_used_at` juga diperbarui saat Firebase menerima permintaan; label “Terakhir Aktif” bukan tanda perangkat mengakui menerima notifikasi.
- **Kesimpulan sementara:** tidak ditemukan ketidakcocokan channel ID, prioritas, atau alur pemilihan token pada pemeriksaan statis. Belum dapat dipastikan apakah kasus tersisa berada di penerimaan Android, izin/channel per aplikasi di perangkat, atau APK lama karena belum ada bukti dari notification history/logcat atau uji langsung Firebase ke token perangkat. Token FCM jangan dibagikan di chat.
- **Langkah pemisah sumber masalah:** kirim satu pesan uji langsung dari Firebase Console ke token perangkat (jangan kirim token ke Codex). Jika tidak muncul, fokus pemeriksaan di APK/perangkat/Firebase client; jika muncul, fokus pada penerima/token yang dipilih aplikasi dan payload dari Laravel. Pastikan APK berada di latar belakang (bukan force-stop), dan uji penerima yang sama dengan akun pemilik token.
- **Belum dijalankan:** tidak ada pengiriman uji langsung, pemeriksaan Android Notification History/Logcat, build ulang APK, atau tes kode pada sesi audit ini.

### 2026-11-02 — Perbaikan PHPStan query UserResource

- Pengguna menjalankan `composer ci`: Pint lulus untuk 183 file, tetapi PHPStan menemukan `getEloquentQuery()` belum menyebut tipe model generik. Return type kini didokumentasikan sebagai `Builder<User>`.
- **Belum diverifikasi:** hasil `composer ci` setelah perbaikan ini; silakan jalankan ulang.

### 2026-11-02 — Pemisahan akses Superadmin, Admin Persuratan, dan Pegawai

- **Dikerjakan:** menambah level `superadmin`; helper `isAdmin()` kini mencakup Superadmin dan Admin Persuratan sehingga Superadmin mewarisi fitur admin yang sudah ada.
- **Pembatasan khusus:** Perangkat Terdaftar hanya terlihat dan dapat dibuka Superadmin. Admin Persuratan tidak melihat akun Superadmin pada daftar pengguna, tidak dapat membuka/mengubah akun itu melalui URL, dan tidak dapat membuat atau menaikkan akun ke level Superadmin. Pegawai tetap pada akses Pegawai.
- **Migrasi aman:** akun admin lama tetap ada; satu admin aktif dipromosikan secara deterministik (akun bernama `superadmin`, jika tidak ada maka akun admin aktif tertua). Instalasi baru membuat akun awal Superadmin. Superadmin aktif terakhir dilindungi dari perubahan yang menghilangkan akses terakhir.
- **Dokumentasi:** memperbarui cakupan dan matriks hak akses serta label peran di aplikasi web dan halaman Akun APK.
- **Belum diverifikasi:** `composer ci`, `npm run build` tidak diperlukan untuk perubahan ini, dan APK/CI Flutter belum diperiksa; migrasi, akses web, dan label APK perlu dikonfirmasi pengguna sebelum presentasi/deploy. Jangan commit sebelum migrasi diterapkan dan CI lulus.
- **Perintah untuk dijalankan pengguna:** jalankan `php artisan migrate`, lalu `composer ci`; dari `mobile_flutter`, jalankan `flutter analyze` dan `flutter test`. Setelah lolos, masuk sebagai Admin Persuratan dan pastikan menu Perangkat Terdaftar tidak muncul dan akses URL langsung ditolak; masuk sebagai Superadmin dan pastikan menu serta semua fitur admin tetap tersedia. Buat ulang APK hanya jika label level di Akun APK ingin diperbarui.
- **Belum bisa diverifikasi:** database lokal, hasil CI, dan dua alur role di browser/HP belum diuji pada sesi ini.

### 2026-11-02 — Perbaikan Flutter widget test setelah perubahan label akun

- Test akun diperbarui untuk label Pegawai dan callback wajib ubah kata sandi. Karena tombol Keluar berada di bawah `ListView` yang membangun item secara bertahap, test kini menggulir sampai tombol dibuat sebelum menekannya.
- **Perintah untuk dijalankan pengguna:** dari `mobile_flutter`, jalankan `flutter analyze` lalu `flutter test`.
- **Belum bisa diverifikasi:** hasil ulang kedua perintah tersebut belum diterima.

### 2026-11-02 — Penyelarasan catatan serah terima

- Memperbarui status M3/Fase 3 berdasarkan laporan pengguna: penyimpanan disposisi kembali berjalan dan pop-up FCM sudah diterima di HP. Menegaskan bahwa CI lama mendahului migrasi terakhir dan APK publik belum diperbarui.
- Memperbarui `docs/HANDOVER-CODEX.md` dan panduan Firebase untuk membedakan konfigurasi lokal uji dari persiapan produksi. Menghapus kata sandi akun uji dari dokumen.
- `.env`, konfigurasi `google-services.json`, dan kredensial privat memiliki aturan abaikan Git. Status Git/staging tidak diperiksa; pengguna perlu memastikan tidak ada rahasia yang sudah masuk daftar commit.
- **Belum dilakukan:** penggantian proyek Firebase pribadi dengan proyek kantor, sesuai arahan pengguna untuk menundanya.

### 2026-11-02 — Tindak lanjut pemeriksaan Composer CI

- Pengguna melaporkan `composer ci` berhenti pada pemeriksaan format Pint untuk migrasi perbaikan foreign key disposisi dan urutan import di `routes/api.php` (182 file diperiksa). Ini adalah kegagalan format, bukan laporan kegagalan tes atau PHPStan; tahap-tahap itu belum berjalan pada eksekusi tersebut.
- Perintah berikutnya untuk pengguna: jalankan `composer lint` untuk merapikan format otomatis, lalu `composer ci` kembali. Status akhir CI belum diketahui.
- Setelah format lulus, PHPStan melaporkan pemeriksaan jenis token Sanctum dianggap selalu benar karena model `User` belum menyatakan tipe token sementara SPA. Anotasi generik Sanctum pada model kini menyebut token API dan token sementara secara akurat, sehingga pemeriksaan `PersonalAccessToken` di endpoint logout dan pendaftaran perangkat tetap aman serta dapat dibedakan oleh PHPStan. Hasil `composer ci` setelah perubahan ini belum diverifikasi.
- Pengguna kemudian melaporkan Pint dan PHPStan lolos, tetapi satu tes FCM gagal karena konfigurasi Firebase lokal dapat aktif dan memiliki kredensial, sementara tes tersebut mengharapkan push dilewati. Tes kini secara eksplisit mematikan FCM agar tidak bergantung pada `.env` lokal dan tidak mencoba menghubungi Firebase. Hasil Pest/`composer ci` setelah perubahan tes belum diverifikasi.
- Saat peninjauan pra-commit, seluruh placeholder `.gitignore` di bawah `storage/` ditemukan terhapus dan direktori `storage/` tidak tersedia di workspace. Placeholder Laravel dipulihkan agar direktori runtime tetap ada di checkout tanpa memasukkan isi storage ke Git. Pengguna perlu meninjau `git status --short --untracked-files=all` sebelum staging.
- Investigasi push terbaru: `.env` memakai `FCM_ENABLED=true`. File kredensial server ditemukan di `storage/app/private`, tetapi namanya berakhiran `.json.json`, sehingga tidak cocok dengan path `.env`. Nama file lokal sudah dikoreksi agar sesuai; file tidak dibuka atau diubah isinya. Pengguna perlu menjalankan `php artisan config:clear` lalu menguji disposisi baru ketika APK di latar belakang. Log sebelum koreksi menunjukkan FCM dilewati karena file tidak ditemukan.
- Audit lanjutan kehilangan file Firebase: file sekarang berada di path yang benar (2376 byte); hanya nama `.json.json` yang berubah menjadi `.json` melalui `Move-Item` oleh Codex, tanpa membuka/mengubah isi. File JSON diabaikan oleh `storage/app/private/.gitignore`, jadi `git add/commit` normal tidak menghapus atau mencatatnya. Riwayat PowerShell yang tersedia tidak menunjukkan perintah penghapusan pada `storage/app/private`; perintah `rmdir` yang pengguna tunjukkan menargetkan `storage/framework/composer-cache` dan melaporkan path tidak ditemukan. Tidak ada audit Windows lengkap untuk memastikan apakah proses/orang lain pernah memindahkan atau menyalin file.
- Pemeriksaan ulang setelah pengguna melaporkan push tidak muncul: `FCM_ENABLED=true`, `QUEUE_CONNECTION=sync`, dan file kredensial pada path `.env` ada. `laravel.log` tidak memiliki entri FCM setelah 11:28; implementasi saat ini mencatat skip/gagal tetapi tidak mencatat sukses. UAT perlu menguji APK di latar belakang dan penerima yang baru ditambahkan ke disposisi baru; penyimpanan ulang disposisi dengan penerima lama tidak memicu notifikasi ulang.
- Menambahkan pencatatan diagnostik yang aman pada channel FCM: penerima nonaktif, push dimatikan, token perangkat kosong/sesi usang, konfigurasi server tidak lengkap, dan pesan yang diterima Firebase kini dapat dibedakan di `laravel.log`. Token perangkat, isi surat, dan kunci tidak dicatat. Perubahan belum diverifikasi dengan `composer ci` atau tes notifikasi baru.
- Log terbaru menunjukkan Firebase menerima pesan untuk device ID 4; percobaan lain mencatat penerima tanpa token perangkat. Kode Android sebelumnya membuat notification channel dengan `IMPORTANCE_DEFAULT`; kanal baru `disposisi_high_priority` memakai `HIGH`. APK harus dibangun ulang/dipasang agar perubahan berlaku. Pengguna kemudian mengonfirmasi pop-up berhasil diuji berulang kali di HP.
- UAT lanjutan 2026-11-02: pengguna mengonfirmasi pop-up masuk beberapa kali saat surat baru/disposisi ditujukan kepada akun yang sedang terdaftar pada APK (device ID 5; log FCM accepted pukul 13:38–13:44). Mengubah penerima disposisi ke pegawai lain tidak menampilkan pop-up; log mencatat penerima tanpa token perangkat. Pengguna memang berganti-ganti akun pegawai pada satu HP, dan logout mencabut token akun sebelumnya. Kesimpulan UAT: push berfungsi; untuk menerima push tiap pegawai perlu mendaftarkan akun/perangkatnya sendiri. Perubahan penerima yang sudah ada tidak mengirim ulang kepada penerima yang sama.
- Menambahkan ubah kata sandi mandiri di tab Akun APK: kata sandi lama, baru, dan konfirmasi; mengikuti kebijakan server minimal 10 karakter dengan huruf besar/kecil dan angka, membatasi percobaan endpoint, mencatat audit tanpa data kata sandi. Belum diverifikasi; pengguna perlu menjalankan `composer ci`, `flutter analyze`, `flutter test`, lalu membangun APK baru untuk presentasi.
- **Temuan CI terbaru:** pengguna melaporkan Pint menemukan masalah format pada migrasi FK dan urutan import API. Import API sudah dirapikan; jalankan `composer lint` untuk merapikan migrasi, lalu ulangi pemeriksaan.
- **Perintah untuk dijalankan pengguna:** pastikan migrasi FK tercatat `Ran`; jalankan `composer lint`, `composer ci`, `flutter analyze`, dan `flutter test`; periksa `git status` sebelum commit.
- **Belum bisa diverifikasi:** hasil pemeriksaan terminal dan status Git masih menunggu pengguna.

### 2026-11-02 — Perbaikan konfigurasi Firebase push lokal

- Penyebab pop-up tidak terkirim ditemukan di `.env`: `FCM_ENABLED=true` dan `QUEUE_CONNECTION=sync` sudah benar, tetapi `FIREBASE_PROJECT_ID` masih berupa placeholder dan `FIREBASE_CREDENTIALS` menunjuk ke nama berkas yang tidak ada.
- ID proyek dicocokkan dengan metadata berkas service account lokal tanpa membaca/menampilkan private key. Nilai ID proyek dan path kredensial di `.env` lokal telah diselaraskan. Jangan commit `.env` atau berkas service account.
- **Perintah untuk dijalankan pengguna:** jalankan `php artisan config:clear`, lalu restart server API (`Ctrl+C`, kemudian `php artisan serve --host=0.0.0.0 --port=8000`). Login ulang ke APK, minimalkan aplikasi, lalu uji disposisi baru ke akun tersebut.
- **Verifikasi pengguna:** setelah memperbaiki `.env`, pengguna mengonfirmasi pop-up sudah diterima. Pop-up sistem diuji saat aplikasi di latar belakang; skenario logout, izin ditolak, dan pemulihan token belum diuji ulang.
- **Belum bisa diverifikasi:** hasil `composer ci`/Flutter setelah rangkaian perubahan terbaru belum dilaporkan; build Codex tidak menghasilkan APK baru.

### 2026-11-02 — Perbaikan FK penerima disposisi

- Akar masalah: FK `disposition_recipients.disposition_id` pada database lama masih menunjuk `legacy_dispositions` setelah tabel workflow lama diganti nama, sehingga ID disposisi sederhana yang baru ditulis tidak ditemukan.
- Menambahkan migrasi perbaikan yang mengarsipkan tabel penerima lama (tanpa menghapus isinya), membuat tabel aktif dengan FK ke `dispositions`, dan menyalin penerima lama hanya bila bisa dicocokkan secara aman lewat surat yang sama. Data yang tak dapat dicocokkan tetap utuh di tabel arsip.
- **Perintah untuk dijalankan pengguna:** `php artisan migrate`, lalu coba simpan disposisi lagi. Jika hendak menjalankan pemeriksaan lengkap, jalankan `composer ci`.
- **Verifikasi pengguna:** setelah perbaikan, pengguna menyatakan masalah disposisi selesai dan melanjutkan pengujian notifikasi. Tabel lama diarsipkan dengan nama `legacy_disposition_recipients_20261102`.
- **Belum bisa diverifikasi:** status migrasi dan `composer ci` tidak dilihat langsung; pastikan migrasi berstatus `Ran` sebelum menyerahkan ke mentor.

### 2026-11-02 — Perbaikan konfigurasi build Android untuk FCM

- Menetapkan Android NDK `26.1.10909125` dan `minSdk` 23 sesuai kebutuhan plugin Firebase Messaging yang muncul pada build APK.
- Panduan setup Firebase kini mencatat kebutuhan NDK dan batas minimum Android 6.0. Alamat LAN terbaru `192.168.10.197:8000` sudah dipasang sebagai bawaan Flutter dan diselaraskan di contoh konfigurasi/panduan; alamat lama `192.168.10.157` dan `192.168.10.199` sudah tidak tersisa di berkas sumber dan dokumentasi.
- APK yang sudah terpasang/tersimpan tidak otomatis berubah; perlu dibangun ulang dan dipasang kembali. Untuk emulator, gunakan `10.0.2.2:8000`, sedangkan HP fisik memakai IP LAN `192.168.10.197:8000`.
- **Perintah untuk dijalankan pengguna:** dari `mobile_flutter`, jalankan `flutter clean`, `flutter pub get`, lalu `flutter build apk --release --dart-define=API_BASE_URL=http://192.168.10.197:8000/api` untuk HP fisik. Untuk emulator, ganti alamat API menjadi `http://10.0.2.2:8000/api`.
- **Percobaan build Codex:** perintah Flutter tidak mengeluarkan log dan berhenti; waktu berkas APK yang ada tidak berubah. APK lama tidak dihapus/ditimpa karena belum ada APK baru yang berhasil dibangun.
- **Belum bisa diverifikasi:** build ulang dan pemasangan APK menunggu dijalankan pengguna melalui CMD/Laragon; peringatan SDK XML perlu ditinjau terpisah dengan memperbarui Android SDK Command-line Tools di SDK Manager jika masih muncul.

### 2026-11-02 — Integrasi FCM APK Flutter (implementasi kode, konfigurasi menunggu)

- **Backend API:** menambahkan endpoint terautentikasi untuk mendaftarkan dan mencabut token Android. Token terikat pada ID sesi Sanctum dan dicabut saat logout; token dari pengguna lain tidak dapat dicabut.
- **Aplikasi Flutter:** menambahkan Firebase Core/Messaging, permintaan izin notifikasi setelah login, pendaftaran/penyegaran token, penanganan notifikasi saat aplikasi di latar atau ditutup, serta navigasi ke rincian disposisi yang dimuat ulang dari API. Lencana Beranda mengikuti jumlah disposisi belum dibaca yang sudah tersedia.
- **Privasi dan keandalan:** payload hanya berisi pemberitahuan umum serta ID disposisi; FCM client memakai batas waktu dan menyimpan sementara token OAuth agar permintaan berulang lebih hemat.
- **Dokumentasi:** menambahkan keputusan scope yang telah disetujui pengguna, langkah setup Firebase, alur UAT, dan peta dokumen di `AGENTS.md`.
- **Perintah untuk dijalankan pengguna:** `composer ci` dari root proyek. Di `mobile_flutter`: `flutter pub get`, `flutter analyze`, `flutter test`, lalu build APK setelah `google-services.json` tersedia. Ikuti `docs/fase 3/03-SETUP-FIREBASE.md` untuk konfigurasi Firebase.
- **Belum bisa diverifikasi:** CI PHP dan pemeriksaan Flutter tidak dijalankan sesuai arahan pengguna agar perintah Laragon dijalankan sendiri; pengiriman FCM juga menunggu file konfigurasi dan kredensial dari Admin/IT serta pengujian di HP.

### 2026-10-30 — Rombak tampilan aplikasi mobile (bottom navigation)

- **Latar:** tampilan lama hanya satu layar berisi 4 kartu menu berwarna-warni, tanpa bottom navigation. Tampilan dirombak mengikuti panduan UX: satu warna aksen hijau BPRS `#0F5C46` (ikon `#1D9E75`), latar `#F5F7F6`, kartu putih bergaris `#E3E8E5` sudut 16, font Plus Jakarta Sans dibundel lokal (`assets/fonts`, lisensi OFL).
- **Struktur baru:** `main_shell.dart` dengan 4 tab (Beranda, Disposisi, Surat, Akun) memakai `NavigationBar`; tab dimuat saat pertama dibuka. Beranda: app bar logo + lonceng, sapaan per jam, kartu hijau "Disposisi untuk saya", dua kartu ringkasan, dan "Surat terbaru" (maksimal 5). Surat: sub-tab Masuk/Keluar dengan pencarian, tarik-ke-bawah, skeleton muat, pesan kosong, dan pesan jaringan kantor + tombol "Coba Lagi". Akun: nama, level, ukuran huruf (Normal/Besar/Sangat besar, disimpan lokal), menu Laporan, dan tombol Keluar dengan dialog "Keluar dari aplikasi?".
- **Data ringkasan:** `HomeProvider` memakai endpoint yang sudah ada (`/incoming-letters`, `/outgoing-letters`, `/dispositions` dengan `per_page`) dan membaca `meta.total`. Tidak ada endpoint baru.
- **Placeholder (TODO):** (1) angka lonceng notifikasi masih `0` karena belum ada endpoint notifikasi; (2) jumlah disposisi belum dibaca dihitung dari maksimal 50 disposisi terbaru (API belum punya filter/hitungan); (3) lencana "Baru" pada surat memakai tanggal terima ≤ 7 hari karena belum ada penanda sudah dibaca per surat; (4) menu Laporan di aplikasi hanya memberi keterangan untuk membuka versi web.
- **Hak akses:** aplikasi menampilkan menu yang sama untuk semua pengguna aktif (Superadmin, Admin Persuratan, dan Pegawai); fitur khusus pengelolaan web tidak tersedia di APK.
- **Verifikasi:** `flutter analyze` tanpa masalah; `flutter test` 11 tes lulus (tema, format tanggal/sapaan, penyimpanan ukuran huruf, Beranda, keadaan offline, layar masuk, tab Akun); APK release x86_64 (21,2 MB) dipasang di emulator 360x800 dan alur login, Beranda, Surat, Disposisi, Akun berjalan.
- **Belum bisa diverifikasi:** tangkapan layar resmi 4 tab pada skala huruf 1.0 dan 1.3 belum tersimpan seluruhnya (pemeriksaan visual dilakukan langsung di emulator), serta uji di HP fisik.
- **Firebase:** belum ada berkas `google-services.json`, `firebase_options.dart`, atau dependensi Firebase di proyek mobile. Untuk notifikasi nanti diperlukan `firebase_core` + `firebase_messaging`, berkas `android/app/google-services.json`, plugin `com.google.gms.google-services` di Gradle, dan endpoint pendaftaran token di API.

### 2026-10-30 — Melanjutkan APK Flutter: perbaikan API + build APK berhasil

- **Latar:** melanjutkan pekerjaan pembuatan APK Flutter yang sebelumnya dirintis memakai alat lain (kuota habis). Kondisi awal: `php artisan test` 56 tes lulus, tetapi `flutter analyze` masih 2 galat, dan API hasil rintisan memiliki beberapa bug yang membuat aplikasi HP tidak bisa memakai data.
- **Perbaikan API:**
  1. `DispositionController` memakai `$this->authorize()` padahal `Controller` dasar tidak memuat `AuthorizesRequests` → diganti `Gate::authorize()`.
  2. `DispositionResource` membaca relasi `sender` yang tidak ada (relasi sebenarnya `creator`) → pengirim kini diambil dari nama pembuat lembar disposisi, ditambah `instructions`, `nature`, `forwarded_to`, dan `status`.
  3. URL lampiran menunjuk route web berbasis sesi sehingga aplikasi HP selalu 401 → dibuat route API `GET /api/attachments/{attachment}` (nama route `api.attachments.show`) dengan Bearer token, wajib disk `private`, dan tercatat di audit.
  4. Pencarian surat keluar memakai kolom `recipient_name` yang tidak ada → diganti `recipient`.
  5. Login API diberi `throttle:5,1`; akun yang dinonaktifkan langsung kehilangan akses token lama melalui middleware `EnsureApiUserIsActive` (token database ikut dihapus).
  6. `GET /api/me` hanya mengembalikan id, nama, username, level, dan jabatan. Pesan API dipindah ke `lang/id/api.php`.
- **Perbaikan aplikasi Flutter:** `provider.hasMore` → `hasMoreIncoming`, `shape:` pada `ElevatedButton` diperbaiki, model surat keluar membaca `recipient`, menu "Disposisi Saya" tersambung, menu "Laporan" memberi keterangan, konfirmasi keluar, pesan galat login/jaringan lebih jelas, dan layar tunggu saat memulihkan sesi.
- **Alamat server bisa diatur tanpa ubah kode:** `ApiService.baseUrl` memakai `String.fromEnvironment('API_BASE_URL')` dengan bawaan LAN lokal `http://192.168.10.197:8000/api`. Untuk emulator gunakan `http://10.0.2.2:8000/api`.
- **Android:** manifest diberi izin `INTERNET` dan `usesCleartextTraffic="true"`, label aplikasi "E-Surat BPRS". Proyek dinaikkan ke AGP 8.6.0, Gradle 8.11.1, Kotlin 2.1.10, dan `compileSdk 35` karena `flutter_pdfview` menuntut SDK 35. Plugin yang memaksa AGP 8.11 disamakan lewat `resolutionStrategy` di `android/build.gradle` (Gradle 8.13 belum tersedia di laptop).
- **Tes baru:** `tests/Feature/Api/AuthApiTest.php` (5 tes) dan `tests/Feature/Api/DispositionApiTest.php` (6 tes), ditambah `DispositionFactory` dan `DispositionRecipientFactory`.
- **Validasi:** `composer ci` **LULUS** (Pint, PHPStan 0 galat, 67 tes/317 assertion); `flutter analyze` tanpa masalah; `flutter build apk --release` menghasilkan APK 34 MB dan disalin ke `public/downloads/esurat-bprs-flutter.apk`.
- **Perintah untuk dijalankan pengguna:** jalankan Laragon (Apache + MySQL), lalu `php artisan migrate` (tabel `personal_access_tokens` perlu ada untuk token HP), `composer ci`, dan build APK dengan alamat server kantor:
  ```powershell
  cd C:\laragon\www\esurat\mobile_flutter
  flutter build apk --release --dart-define=API_BASE_URL=http://IP-SERVER-KANTOR:8000/api
  ```
- **Belum bisa diverifikasi:** pemasangan APK di HP fisik dan login ke server kantor, tampilan PDF ber-token di HP, notifikasi FCM (kredensial Firebase belum dipasang), serta UAT pengguna. APK 34 MB sebaiknya tidak di-commit langsung ke Git.
- **Uji di emulator Android (laptop dev):** sistem image `system-images/android-34/google_apis/x86_64` dipasang lewat CLI baru `android sdk install`, AVD `esurat_uji` (Pixel 5) dibuat dengan `hw.keyboard=yes` agar keyboard laptop bisa dipakai. APK yang dipasang adalah build khusus emulator (`flutter build apk --release --target-platform android-x64 --dart-define=API_BASE_URL=http://10.0.2.2:8000/api`, 21 MB) karena APK debug 103 MB gagal ditransfer ke emulator. Hasil: login, daftar surat masuk/keluar, pencarian, dan halaman "Disposisi Saya" berjalan dengan data asli dari `php artisan serve`. Akun uji sementara `mobile.uji` dibuat lewat tinker dan **harus dihapus** sebelum serah terima.

### 2026-10-30 — Perbaikan hambatan CI (Pint, PHPStan, test) — hijau

- **Selesai:** mengaktifkan ekstensi `pdo_sqlite` (dan `sqlite3`) pada `php.ini` Laragon (`C:\laragon\bin\php\php-8.4.27-Win32-vs17-x64\php.ini`) sehingga `php artisan test` yang memakai sqlite `:memory:` dapat berjalan.
- **Selesai:** memperbaiki **8 galat PHPStan** (level 6):
  1. [MyDispositionResource.php](file:///c:/laragon/www/esurat/app/Filament/Resources/MyDispositionResource.php) — menambah anotasi `@return Builder<DispositionRecipient>`, menghapus nullsafe berlebih di sisi kiri operator `??` (PHP sudah menangani null lewat `??`), dan memakai akses `->` yang disarankan.
  2. [IncomingLetter.php](file:///c:/laragon/www/esurat/app/Models/IncomingLetter.php#L17-L23) — menambah anotasi `@property Carbon|null $letter_date` dan `@property Carbon|null $received_at` agar `translatedFormat()` dikenali sebagai pemanggilan pada `Carbon`, bukan `string`.
  3. [AppServiceProvider.php](file:///c:/laragon/www/esurat/app/Providers/AppServiceProvider.php#L70-L84) — menyimpan `$event->user` ke variabel lokal sebelum `instanceof` agar penyempitan tipe tidak hilang setelah pemanggilan `handle($event)` pada listener logout.
- **Validasi:** `composer ci` **LULUS** — Pint 161 file, PHPStan 0 galat, **56 tes / 276 assertion** hijau (durasi ~44 detik).
- **Catatan lingkungan:** pengaktifan `pdo_sqlite` mengubah `php.ini` Laragon (di luar folder proyek). Jika Laragon diperbarui dan `php.ini` ditimpa, ekstensi perlu diaktifkan ulang.
- **Belum bisa diverifikasi:** UAT browser dan pengiriman push FCM (tertunda karena akun Google/Firebase belum tersedia).

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
- **Validasi:** skrip dijalankan dengan alamat LAN lokal terbaru `-ServerUrl http://192.168.10.197:8000` → `BUILD SUCCESSFUL` dan APK tersalin. Pint lulus untuk file yang diubah.
- **Belum diverifikasi:** `composer ci` belum lulus di lingkungan ini karena ekstensi `pdo_sqlite` tidak aktif (phpunit memakai sqlite `:memory:`) dan PHPStan melaporkan 8 galat lama di `MyDispositionResource` serta `AppServiceProvider` yang tidak terkait perubahan ini.
- **Tersisa (penghambat notifikasi):** `mobile/android/app/google-services.json` belum ada sehingga plugin Firebase tidak dipasang saat build; server juga belum punya `storage/app/private/firebase-service-account.json` dan `FCM_ENABLED=true`.
- **Perintah:** `powershell -ExecutionPolicy Bypass -File mobile\build-apk.ps1 -ServerUrl <alamat-server>`.
