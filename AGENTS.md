# AGENTS.md — Panduan untuk AI Coding Agent (Codex / Antigravity)

> BACA FILE INI PERTAMA di setiap sesi. Lalu baca dokumen di `docs/` sesuai tugas.
> Proyek: **E-Surat BPRS Sragen** — aplikasi **surat masuk & surat keluar yang sederhana**, dengan Lembar Disposisi cetak, laporan, dan dashboard.

## 1. Ringkasan Proyek
Aplikasi web internal BPRS Sragen (bank pembiayaan rakyat syariah), dijalankan **on-premise**. Pengguna: **Admin** (sekretariat/IT) dan **User** (pegawai). Sebagian pengguna berusia lanjut, jadi **kemudahan pakai adalah prioritas utama**. Konteks perbankan: keamanan dan jejak audit tetap penting.

### SCOPE v2 — rombakan total, FINAL (jangan diperluas)
**Modul yang DIBUAT**
- Admin: Data Klasifikasi · Data Instansi · Data Pengguna · Data Surat Masuk · Data Surat Keluar · Laporan Surat Masuk · Laporan Surat Keluar · Profil · Riwayat Aktivitas.
- User: Data Surat Masuk · Data Surat Keluar · Laporan Surat Masuk · Laporan Surat Keluar · Profil.
- Dashboard: kartu ringkasan + 2 grafik sederhana + surat terbaru.
- **Disposisi = SEDERHANA:** pada surat masuk, satu form "Lembar Disposisi" → Simpan & Cetak PDF (satu klik). Tanpa pelacakan, berjenjang, batas waktu, atau notifikasi.

**JANGAN dibuat (ditunda ke fase 2; arsip di `docs/future/`)**: persetujuan berjenjang, tanda tangan digital & QR, surat internal/nota dinas/edaran, delegasi, notifikasi/pengingat, tingkat kerahasiaan & pembatasan akses per surat, hierarki unit kerja, disposisi berjenjang/berpelacakan, OCR, enkripsi file. Jika ragu apakah sesuatu termasuk scope, **jangan dibuat** dan catat di `docs/OPEN_QUESTIONS.md`.

## 2. Stack (JANGAN diganti tanpa persetujuan di `docs/DECISIONS.md`)
| Lapisan | Pilihan |
|---|---|
| Bahasa | PHP 8.2+ (dev 8.2; produksi 8.3/8.4). Kode HARUS kompatibel PHP 8.2 (jangan pakai fitur khusus 8.3+: typed class constants, `#[\Override]`, `json_validate()`, dynamic class constant fetch) |
| Framework | Laravel **12.x** (`^12.0`), kunci di composer.lock |
| Admin UI | Filament (versi stabil terbaru yang mendukung PHP 8.2 + Laravel 12; catat versinya di DECISIONS.md) |
| Database | MySQL 8 (MariaDB 10.11+ kompatibel) |
| Hak akses | Kolom `users.level` (`admin`/`user`) + Laravel Policy. **Tanpa** spatie/laravel-permission |
| PDF | barryvdh/laravel-dompdf (lembar disposisi, laporan) |
| Excel | maatwebsite/excel (ekspor laporan) |
| Audit | spatie/laravel-activitylog |
| Grafik | Filament ChartWidget (Chart.js bawaan); jangan menambah library grafik lain |
| Test/kualitas | Pest `^3` (Pest 4 butuh PHP 8.3), Laravel Pint, Larastan |
| Queue/cache | Tidak diperlukan di v2 (driver `sync`/`file`) |

## 3. Perintah Standar
```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # admin, klasifikasi, data instansi contoh
php artisan serve                   # dev
composer ci                         # test + format check + static analysis (Windows & Linux)
php artisan test                    # hanya test
composer lint                       # perbaiki format (pint)
```
Tugas SELESAI hanya jika `composer ci` lulus. Script `ci` dan `lint` dibuat di M0.

## 4. Konvensi Kode
- Identifier kode & database: **bahasa Inggris**, `snake_case` kolom/tabel, `PascalCase` class. Teks antarmuka: **bahasa Indonesia** di `lang/id/*.php` (jangan hardcode). Locale `id`, timezone `Asia/Jakarta`.
- Glosarium: `incoming_letters` = surat masuk · `outgoing_letters` = surat keluar · `dispositions` = lembar disposisi · `classifications` = klasifikasi · `institutions` = data instansi.
- Logika bisnis di `app/Services` / `app/Actions`; Filament Resource hanya tampilan/form/tabel. Status/pilihan memakai PHP Backed Enum.
- Otorisasi dengan **Policy** (berdasarkan `users.level`); jangan cek peran dengan string di banyak tempat — buat helper `isAdmin()`.
- Operasi multi-tabel dalam `DB::transaction`. Penomoran (agenda & nomor surat keluar) WAJIB `lockForUpdate()` agar tidak ganda.
- Tanpa hard delete pada surat: `SoftDeletes`, hanya Admin yang boleh menghapus, dengan konfirmasi dan tercatat di audit.
- Setiap fitur: migration + factory + seeder (bila perlu) + policy + test + terjemahan.

## 5. Aturan Keamanan (WAJIB)
1. **Password:** selalu di-hash (`Hash::make`/cast `hashed`). **DILARANG** menampilkan, mencatat, atau mengekspor password dalam bentuk apa pun (tabel, form, log, laporan). Admin hanya bisa "Atur Ulang Password".
2. File upload di disk **private**; unduh/pratinjau lewat route terautentikasi. Validasi: `pdf, jpg, jpeg, png`, maks 10 MB, cek MIME asli, simpan hash SHA-256.
3. Login: throttle percobaan, sesi timeout idle 30 menit (configurable), pengguna nonaktif tidak bisa login.
4. Jangan log data sensitif. Tidak ada secret di repo; `.env` tidak di-commit.
5. Gunakan Eloquent/query builder berparameter; hindari `DB::raw` dengan input pengguna; escape output (anti-XSS); lindungi CSRF (bawaan).
6. Tidak ada halaman publik tanpa login (kecuali halaman login).
7. Riwayat Aktivitas mencatat: login, tambah/ubah/hapus surat, pembuatan/cetak disposisi, perubahan pengguna.

## 6. Alur Kerja Agent
1. Baca `docs/04-ROADMAP.md` → kerjakan **hanya satu milestone** per sesi sesuai prompt.
2. Branch `feature/mX-nama-singkat` dari `develop` (lihat aturan Git di §8).
3. Spesifikasi ambigu → jangan menebak diam-diam: tulis di `docs/OPEN_QUESTIONS.md`, ambil asumsi paling sederhana, tandai `// ASSUMPTION:`.
4. Jangan mengubah file di luar cakupan milestone kecuali terpaksa; jelaskan di ringkasan.
5. Akhir sesi: perbarui `docs/PROGRESS.md` (selesai, tersisa, cara menjalankan, catatan) lalu commit (bila Git dapat dipakai). Format commit: `feat(surat-masuk): tambah form registrasi`.

## 7. Prinsip UX & Orisinalitas (WAJIB — baca `docs/07-PANDUAN-UX-SEDERHANA.md`)
Tampilan bersih dan sederhana: menu di sisi kiri yang selalu terbuka, kartu ringkasan di Dashboard, tabel data dengan kotak cari dan tombol "+ Tambah", huruf 18px, tombol besar ≥ 48px, bahasa sehari-hari, form SATU halaman, konfirmasi dengan kalimat jelas. **Disposisi harus bisa diselesaikan dalam satu form + satu klik cetak.** Jika konflik antara "fitur canggih" dan "mudah dipakai", **pilih mudah dipakai**.
**Orisinalitas & lisensi:** struktur modul boleh terinspirasi aplikasi surat menyurat umum, tetapi DILARANG menyalin tampilan, template, kode, ikon, logo, nama, atau teks dari aplikasi/produk pihak lain. Pakai identitas BPRS Sragen. Hanya aset berlisensi terbuka (Heroicons, font OFL), dicatat di `docs/LICENSES.md`. Font & aset dibundel lokal (tanpa CDN).

## 8. Catatan Lingkungan Pengembangan: Windows + Laragon
- Proyek di `C:\laragon\www\esurat`, URL lokal `http://esurat.test`, database MySQL `esurat` (user `root`, password kosong). Produksi memakai Ubuntu — jangan menulis kode yang bergantung pada Windows; gunakan helper path Laravel.
- Di Windows gunakan: `composer ci`, `php artisan test`, `vendor\bin\pint`, `vendor\bin\phpstan analyse`.
- Jangan menjalankan `migrate:fresh` / `db:wipe` tanpa persetujuan eksplisit pengguna.
- Jika `php`, `composer`, `node`, atau `mysql` tidak ditemukan, berhenti dan minta pengguna menjalankan "Laragon → Tools → Path → Add Laragon to Path" (lihat `docs/08-PANDUAN-SETUP-LARAGON.md`). Jangan menginstal ulang PHP/Composer sendiri.
- **Pembagian tugas karena sandbox tanpa internet/izin Git:** agent menulis kode, test, dan dokumentasi. PENGGUNA menjalankan perintah yang butuh internet atau Git: `composer require/update`, `npm install`, pembuatan branch, `git add/commit/merge`. Jika error jaringan (`curl error 6`, `Could not resolve host`) atau izin `.git` muncul, JANGAN mengulang berkali-kali; catat di ringkasan. Bila butuh paket baru, tulis nama paket + perintah persisnya di bagian **"Perintah untuk dijalankan pengguna"** (berurutan, siap salin ke terminal Laragon) lalu lanjutkan menulis kode yang tidak bergantung pada paket itu.
- Akhir setiap tugas selalu berisi **"Perintah untuk dijalankan pengguna"** (mis. `composer ci`, `php artisan migrate --seed`, `git add .` + `git commit -m "..."`) dan **"Belum bisa diverifikasi"**.
- **Git gagal** (dubious ownership, setup refresh had errors, folder milik akun lain)? JANGAN mengubah konfigurasi Git, JANGAN `git init` ulang, JANGAN menghapus `.git`. Lanjutkan kode tanpa Git dan minta pengguna melakukan commit (sertakan pesan commit yang disarankan).
- Akhir baris file: LF; `.gitattributes` berisi `* text=auto eol=lf`.

## 9. Peta Dokumen
| File | Isi |
|---|---|
| `docs/01-PRD.md` | Tujuan, peran, semua modul & field, disposisi sederhana, laporan, dashboard |
| `docs/02-DATABASE-DAN-ARSITEKTUR.md` | Struktur folder, service, skema tabel |
| `docs/03-ALUR-DAN-HAK-AKSES.md` | Alur layar, aturan nomor & file, matriks hak akses |
| `docs/04-ROADMAP.md` | Milestone M0–M6 + kriteria selesai |
| `docs/05-PROMPT-CODEX.md` | Prompt siap pakai (termasuk Prompt Rombakan Total) |
| `docs/06-PANDUAN-KOLABORASI-DAN-DEPLOY.md` | (untuk manusia) skema kerja, QA, deploy on-premise |
| `docs/07-PANDUAN-UX-SEDERHANA.md` | Desain orisinal: menu, layar, lembar disposisi, token warna/font, istilah |
| `docs/08-PANDUAN-SETUP-LARAGON.md` | (untuk manusia) setup Windows + Laragon |
| `docs/DECISIONS.md` · `OPEN_QUESTIONS.md` · `PROGRESS.md` | Keputusan, pertanyaan, log progres |
| `docs/future/` | ARSIP fase 2 — JANGAN dikerjakan |
