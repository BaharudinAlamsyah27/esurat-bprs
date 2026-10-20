# AGENTS.md — Panduan untuk AI Coding Agent (Codex / Antigravity)

> BACA FILE INI PERTAMA di setiap sesi. Lalu baca dokumen di `docs/` sesuai tugas.
> Proyek: **E-Surat BPRS Sragen** — aplikasi surat masuk & surat keluar (disposisi, persetujuan berjenjang, tanda tangan + QR, notifikasi).

## 1. Ringkasan Proyek
Aplikasi web internal untuk BPRS Sragen (bank pembiayaan rakyat syariah). Dijalankan **on-premise** di jaringan kantor. Pengguna: Direksi, Kepala Divisi/Bagian/Cabang, Sekretariat/Admin Persuratan, Staf, Auditor.
Konteks perbankan: keamanan, jejak audit, dan hak akses adalah prioritas utama di atas fitur "keren".

## 2. Stack (JANGAN diganti tanpa persetujuan di `docs/DECISIONS.md`)
| Lapisan | Pilihan |
|---|---|
| Bahasa | PHP 8.2+ (dev boleh 8.2; produksi target 8.3/8.4). Kode HARUS kompatibel PHP 8.2: jangan memakai fitur khusus 8.3+ (typed class constants, `#[\Override]`, `json_validate()`, dynamic class constant fetch) |
| Framework | Laravel **12.x** (`^12.0`; Laravel 13 butuh PHP 8.3+ sehingga belum dipakai sampai PHP dinaikkan), kunci di composer.lock |
| Admin UI | Filament (versi stabil terbaru yang mendukung PHP 8.2 + Laravel 12; catat versinya di DECISIONS.md) |
| Database | MySQL 8 (alternatif MariaDB 10.11+) |
| Queue | driver `database` (tanpa Redis di v1) |
| Cache/Session | `file` atau `database` |
| Hak akses | spatie/laravel-permission (+ Filament Shield bila cocok) |
| Audit log | spatie/laravel-activitylog |
| PDF | barryvdh/laravel-dompdf (atau spatie/laravel-pdf bila perlu) |
| QR | endroid/qr-code atau simplesoftwareio/simple-qrcode |
| Test | Pest + Laravel testing; Larastan (level ≥ 6); Laravel Pint |
| Frontend | Blade + Livewire (bawaan Filament). Tanpa SPA terpisah |

## 3. Perintah Standar
```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # seed: peran, unit, klasifikasi, user demo
php artisan serve                   # dev
php artisan queue:work              # worker notifikasi/PDF
php artisan schedule:work           # pengingat otomatis (dev)
composer ci                         # test + format check + static analysis (dipakai di Windows & Linux)
php artisan test                    # hanya test
composer lint                       # perbaiki format (pint)
```
Sebuah tugas dianggap SELESAI hanya jika `composer ci` lulus (pest, pint --test, dan phpstan). Script `ci` dan `lint` dibuat di M0.

## 4. Konvensi Kode
- Identifier kode & database: **bahasa Inggris**, `snake_case` untuk kolom/tabel, `PascalCase` untuk class.
- Teks antarmuka & pesan: **bahasa Indonesia**, simpan di `lang/id/*.php` (jangan hardcode di Blade/Resource). Locale default `id`, timezone `Asia/Jakarta`.
- Glosarium: `incoming_letters` = surat masuk · `outgoing_letters` = surat keluar · `dispositions` = disposisi · `approvals` = persetujuan · `units` = unit kerja · `classifications` = klasifikasi/kode arsip · `positions` = jabatan.
- Logika bisnis taruh di `app/Services` atau `app/Actions` (satu class satu tugas). Filament Resource hanya untuk tampilan/form/tabel — **tanpa logika bisnis berat**.
- Status memakai **PHP Backed Enum** di `app/Enums` (implement `HasLabel`, `HasColor` Filament).
- Otorisasi memakai **Policy** untuk setiap model. Jangan cek peran dengan `if ($user->role == ...)`; gunakan permission/policy.
- Operasi multi-tabel dibungkus `DB::transaction`.
- Penomoran surat HARUS memakai `lockForUpdate()` di dalam transaksi agar tidak ada nomor ganda.
- Tidak ada hard delete pada data surat: gunakan `SoftDeletes` + pembatasan hanya untuk Super Admin, dan selalu tercatat di audit log.
- Semua perubahan status penting wajib tercatat (activitylog + tabel riwayat terkait).
- Setiap fitur baru: migration + factory + seeder (jika perlu) + policy + test + terjemahan.

## 5. Aturan Keamanan (WAJIB)
1. File upload disimpan di disk **private** (`storage/app/private`), bukan `public`. Unduh lewat route terautentikasi + cek policy.
2. Validasi upload: hanya `pdf, jpg, jpeg, png`; maks 10 MB; periksa MIME asli (bukan hanya ekstensi); simpan hash SHA-256.
3. Tingkat kerahasiaan surat (`biasa`, `terbatas`, `rahasia`) membatasi siapa yang boleh melihat — terapkan di **query scope global + policy**, bukan hanya di UI.
4. Jangan log data sensitif (password, token, isi surat rahasia) ke file log.
5. Rate limit login; kebijakan password kuat; sesi timeout 30 menit idle (configurable).
6. Halaman verifikasi QR publik hanya menampilkan metadata minimal (nomor, tanggal, perihal, penandatangan, status keaslian) — **bukan isi surat**.
7. Jangan menaruh secret di repo. `.env` tidak di-commit.
8. Gunakan Eloquent/query builder berparameter; hindari `DB::raw` dengan input pengguna.

## 6. Alur Kerja Agent
1. Baca `docs/04-ROADMAP-MILESTONE.md` → kerjakan **hanya satu milestone** per sesi sesuai prompt.
2. Buat branch `feature/mX-nama-singkat` dari `develop`.
3. Jika spesifikasi ambigu atau bertentangan: **jangan menebak diam-diam**. Tulis pertanyaan di `docs/OPEN_QUESTIONS.md`, ambil asumsi paling aman, dan beri tanda `// ASSUMPTION:`.
4. Jangan mengubah file di luar cakupan milestone kecuali terpaksa; jelaskan di ringkasan.
5. Di akhir sesi: perbarui `docs/PROGRESS.md` (apa selesai, apa tersisa, cara menjalankan, catatan untuk agent berikutnya) lalu commit.
6. Format commit: `feat(surat-masuk): tambah registrasi surat masuk` (Conventional Commits).

## 7. Prinsip UX & Orisinalitas (WAJIB — baca `docs/07-PANDUAN-UX-SEDERHANA.md`)
Pengguna banyak yang lansia: tampilan bersih dan sederhana, huruf 18px, tombol besar (≥ 48px), menu di sisi kiri yang selalu terbuka dan dikelompokkan (SURAT / ARSIP & LAPORAN / PENGELOLAAN / LANJUTAN), Beranda berisi sapaan + kartu ringkasan + daftar "Perlu Tindakan Saya", form SATU halaman (bagian jarang dipakai di "Isian tambahan" tertutup, tanpa wizard), bahasa sehari-hari (lihat tabel istilah), konfirmasi dengan kalimat jelas, status = ikon + kata + warna. Menu yang tak berhak tidak ditampilkan. Jika konflik antara "fitur canggih" dan "mudah dipakai", **pilih mudah dipakai**.
**Orisinalitas & lisensi:** desain dibuat sendiri untuk BPRS Sragen. DILARANG menyalin tampilan, template, kode, ikon, logo, atau teks dari aplikasi/produk pihak lain. Hanya gunakan aset berlisensi terbuka (mis. Heroicons, font OFL) dan catat di `docs/LICENSES.md`. Font & aset dibundel lokal (tanpa CDN).

## 8. Catatan Lingkungan Pengembangan: Windows + Laragon
- Proyek berada di `C:\laragon\www\esurat`, URL lokal `http://esurat.test`, database MySQL `esurat` (user `root`, password kosong). Server produksi memakai Ubuntu — jangan menulis kode yang bergantung pada Windows (path dengan `\`, perintah `cmd`). Gunakan `DIRECTORY_SEPARATOR`/helper path Laravel.
- Di Windows, gunakan perintah ini (bukan `./vendor/bin/...`): `composer ci`, `php artisan test`, `vendor\bin\pint`, `vendor\bin\phpstan analyse`.
- Jangan menjalankan `migrate:fresh` / `db:wipe` tanpa persetujuan eksplisit pengguna.
- Jika `php`, `composer`, `node`, atau `mysql` tidak ditemukan di terminal, hentikan dan minta pengguna menjalankan "Laragon → Tools → Path → Add Laragon to Path" (lihat `docs/08-PANDUAN-SETUP-LARAGON.md`). Jangan menginstal ulang PHP/Composer sendiri.
- Akhir baris file: gunakan LF; atur `.gitattributes` (`* text=auto eol=lf`).

## 9. Peta Dokumen
| File | Isi |
|---|---|
| `docs/01-PRD.md` | Tujuan, peran, fitur, kebutuhan non-fungsional |
| `docs/02-ARSITEKTUR-DAN-DATABASE.md` | Struktur folder, service, skema tabel |
| `docs/03-ALUR-BISNIS-DAN-RBAC.md` | Status, alur, aturan penomoran, matriks hak akses, notifikasi |
| `docs/04-ROADMAP-MILESTONE.md` | Milestone M0–M9 + kriteria selesai |
| `docs/05-PROMPT-CODEX.md` | Prompt siap pakai tiap milestone |
| `docs/06-PANDUAN-KOLABORASI-DAN-DEPLOY.md` | Skema kerja Claude–Codex–Antigravity, QA, deploy on-premise |
| `docs/07-PANDUAN-UX-SEDERHANA.md` | Desain orisinal: arah tampilan, menu, Beranda, alur layar, token warna/font, istilah, uji kemudahan |
| `docs/08-PANDUAN-SETUP-LARAGON.md` | (untuk manusia) setup Windows + Laragon dari nol, Git, perintah harian, masalah umum |
| `docs/DECISIONS.md` | Catatan keputusan arsitektur |
| `docs/OPEN_QUESTIONS.md` | Pertanyaan/asumsi yang menunggu konfirmasi |
| `docs/PROGRESS.md` | Log progres antar sesi |
