# 08 — Panduan Setup dari Nol (Windows + Laragon)

Panduan ini untuk **Anda (manusia)**: menyiapkan komputer, membuat folder proyek lewat terminal, sampai siap menjalankan Codex/Antigravity. Ikuti urutannya. Semua perintah dijalankan di **Terminal Laragon**.

> Laragon hanya untuk pengembangan di komputer Anda. Server produksi nanti memakai Ubuntu (lihat `06-PANDUAN-KOLABORASI-DAN-DEPLOY.md` §4).

---
## Langkah 0 — Prasyarat (sekali saja)
Pasang, bila belum ada:
1. **Laragon Full** (sudah berisi PHP, MySQL, Composer, Node.js, Git).
2. **PHP 8.2 atau lebih baru (disarankan 8.3/8.4).** Cek di langkah 2. PHP 8.2 cukup untuk pengembangan dengan Laravel 12, tetapi dukungan keamanannya berakhir sekitar akhir 2026 — server produksi gunakan 8.3/8.4. Untuk menambah versi PHP di Laragon: unduh PHP 8.3 (zip, *Thread Safe*, x64) dari windows.php.net → ekstrak ke `C:\laragon\bin\php\php-8.3.x-Win32-vs16-x64` (versi lama tetap ada, bisa dipakai bergantian) → di Laragon klik kanan → **PHP → Version** → pilih versi itu.
3. **Composer 2.7+ dan Node.js 22 LTS** (versi lama? lihat Langkah 2B).
4. **Git for Windows** bila `git --version` di langkah 2 tidak dikenali.
5. Akun **GitHub/GitLab** (repo *private*) — diperlukan bila Codex berjalan di cloud, dan berguna sebagai cadangan.

## Langkah 1 — Buka Terminal Laragon
1. Buka **Laragon** → klik **Start All** (Apache/Nginx dan MySQL berjalan).
2. Klik tombol **Terminal**. Muncul jendela dengan tulisan seperti `C:\laragon\www>`.
   (Semua perintah berikut diketik di jendela ini lalu tekan Enter.)

## Langkah 2 — Cek alat
```bash
php -v
composer -V
node -v
npm -v
git --version
mysql --version
```
Semua harus menampilkan nomor versi. PHP harus ≥ 8.2, **Composer ≥ 2.7**, **Node ≥ 22.12 (atau 20.19+)**. Bila Composer/Node lebih lama, lakukan Langkah 2B dulu.

Cek ekstensi PHP yang dibutuhkan Filament/PDF:
```bash
php -m | findstr /i "intl zip gd mbstring pdo_mysql fileinfo"
```
Harus muncul semuanya (6 baris). Bila ada yang kurang: Laragon → klik kanan → **PHP → php.ini**, cari baris `;extension=intl` (dst), **hapus tanda `;` di depannya**, simpan, lalu **Stop All → Start All**.

## Langkah 2B — Perbarui Composer & Node (bila versinya lama)
**Composer** (contoh versi lama: 2.4.1):
```bash
composer self-update
composer -V
```
Bila gagal karena izin: tutup Laragon, buka lagi dengan klik kanan → *Run as administrator*, ulangi.

**Node.js** (contoh versi lama: 18.x — sudah habis masa dukung; Vite 7 dan Tailwind terbaru butuh Node **20.19+ atau 22.12+**):
> Pakai file **ZIP**, bukan installer `.msi` dan bukan `node.exe` tunggal (tanpa npm, tidak terdeteksi Laragon). Installer `.msi` mengubah PATH Windows dan bisa bentrok dengan Laragon.
1. Buka **https://nodejs.org/dist/latest-v22.x/** dan unduh **`node-v22.xx.x-win-x64.zip`** (versi 22.12 atau lebih baru; bukan `.msi`, `.exe`, atau `arm64`).
2. Klik kanan zip → *Extract All* ke `C:\laragon\bin\nodejs\` sehingga terbentuk `C:\laragon\bin\nodejs\node-v22.xx.x-win-x64\` yang berisi `node.exe`, `npm.cmd`, `npx.cmd` langsung di dalamnya (bukan di folder bersarang).
3. Hapus `node.exe` lepas bila ada di `C:\laragon\bin\nodejs\`.
4. Laragon → klik kanan → **Node.js → Version** → pilih versi 22 (bila belum muncul, tutup Laragon sepenuhnya lalu buka lagi).
5. Tutup lalu buka ulang Terminal Laragon, cek: `node -v` (v22.12+), `npm -v` (10+), dan `where node` (baris pertama harus `C:\laragon\bin\nodejs\node-v22...`).
Versi Node lama tetap ada di folder itu; berpindah versi lewat menu yang sama (berlaku untuk seluruh komputer, buka ulang terminal setelah berpindah).

## Langkah 3 — Buat folder & proyek Laravel
```bash
cd C:\laragon\www
composer create-project laravel/laravel:^12.0 esurat
cd esurat
```
- `:^12.0` mengunci ke Laravel 12 (Laravel 13 butuh PHP 8.3+). Perintah `composer create-project` **otomatis membuat folder `esurat`** berisi Laravel. Tunggu sampai selesai (beberapa menit).
- Folder proyek Anda sekarang: `C:\laragon\www\esurat`. Semua perintah berikutnya dijalankan **di dalam folder ini**.
- (Alternatif lewat menu: Laragon → klik kanan → **Quick app → Laravel**, beri nama `esurat`.)

## Langkah 4 — Buat database
```bash
mysql -u root -e "CREATE DATABASE esurat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```
(Password root bawaan Laragon kosong. Alternatif: buka **HeidiSQL** dari Laragon → buat database `esurat`.)

## Langkah 5 — Atur file `.env`
Buka file `.env` di folder proyek:
```bash
notepad .env
```
Ubah/pastikan baris berikut (cari barisnya lalu ganti):
```
APP_NAME="E-Surat BPRS Sragen"
APP_URL=http://esurat.test
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esurat
DB_USERNAME=root
DB_PASSWORD=
```
Simpan dan tutup Notepad. Lalu uji koneksi database:
```bash
php artisan migrate
```
Jika berhasil muncul daftar tabel yang dibuat (DONE). Bila error, lihat tabel "Masalah Umum" di bawah.

## Langkah 6 — Uji aplikasi jalan
Cara A (otomatis dari Laragon): **Stop All → Start All**, lalu buka browser ke **http://esurat.test** — halaman awal Laravel tampil.
Cara B (bila A tidak jalan):
```bash
php artisan serve
```
Buka **http://127.0.0.1:8000**. Hentikan dengan `Ctrl + C`.

## Langkah 7 — Masukkan file dokumentasi proyek
1. Ekstrak `surat-bprs-docs.zip` (mis. ke `C:\Users\NAMA\Downloads\surat-bprs`).
2. Salin **isinya** ke folder proyek. Lewat terminal (ganti `NAMA` sesuai nama pengguna Windows Anda):
```bash
copy C:\Users\NAMA\Downloads\surat-bprs\AGENTS.md .
copy C:\Users\NAMA\Downloads\surat-bprs\README.md .
xcopy C:\Users\NAMA\Downloads\surat-bprs\docs docs /E /I
```
   Bila ditanya menimpa `README.md`, jawab **Y** (README Laravel boleh diganti). Atau salin manual lewat File Explorer ke `C:\laragon\www\esurat`.
3. Pastikan sekarang ada: `AGENTS.md`, `README.md`, dan folder `docs` di dalam `esurat`:
```bash
dir
```

## Langkah 8 — Git: simpan sebagai repo
```bash
git config --global user.name "Nama Anda"
git config --global user.email "email@anda.com"

git init -b main
git add .
git commit -m "chore: inisialisasi proyek Laravel + dokumentasi"
git checkout -b develop
```
`.env` tidak ikut tersimpan (sudah diabaikan oleh Laravel) — itu benar dan memang harus begitu.

**Hubungkan ke GitHub/GitLab (disarankan):** buat repo *private* kosong di situs, salin alamatnya, lalu:
```bash
git remote add origin https://github.com/AKUN-ANDA/esurat.git
git push -u origin main
git push -u origin develop
```

## Langkah 9 — Agar Codex/Antigravity bisa memakai php & composer
Terminal Laragon hanya "mengenal" `php`/`composer` di jendelanya sendiri. Supaya alat lain juga bisa:
Laragon → klik kanan → **Tools → Path → Add Laragon to Path**. Tutup lalu buka ulang aplikasi Codex/Antigravity, kemudian uji di terminalnya: `php -v` dan `composer -V` harus tampil.

## Langkah 10 — Jalankan Codex untuk Milestone M0
1. Buka Codex pada folder `C:\laragon\www\esurat` (atau repo yang sama bila memakai Codex cloud, setelah `git push`).
2. Tempel **Prompt Pembuka + Prompt M0** dari `docs/05-PROMPT-CODEX.md`.
3. Tambahkan satu kalimat di akhir prompt:
   > "Catatan: Laravel sudah terpasang di repo ini dan database `esurat` sudah dibuat. Lewati langkah inisialisasi Laravel; lanjutkan dari pemasangan Filament dan seterusnya. Lingkungan: Windows + Laragon (lihat AGENTS.md bagian Catatan Windows)."
4. Setelah Codex selesai: jalankan di terminal Laragon
```bash
composer install
npm install
npm run build
php artisan migrate --seed
composer ci
```
   lalu buka **http://esurat.test** dan periksa hasilnya.
5. Bila sesuai: `git add . && git commit -m "feat: M0 fondasi"` (atau biarkan Codex yang commit), lalu merge ke `develop`.

## Langkah 11 — Siklus tiap milestone
```bash
git checkout develop
git pull
git checkout -b feature/m1-auth-master-data      # nama sesuai milestone
```
Jalankan prompt milestone di Codex → uji di browser → jalankan Prompt QA di Antigravity → bawa ringkasan/diff ke Claude untuk review → perbaiki → merge:
```bash
git checkout develop
git merge feature/m1-auth-master-data
git push
```

---
## Perintah Harian (contekan)
Buka Laragon → **Start All** → **Terminal** → `cd C:\laragon\www\esurat`

| Tujuan | Perintah |
|---|---|
| Jalankan tampilan (saat mengembangkan) | `npm run dev` (biarkan terbuka) |
| Bangun tampilan final | `npm run build` |
| Buat/ubah tabel + data awal | `php artisan migrate --seed` |
| Ulang database dari nol (data hilang!) | `php artisan migrate:fresh --seed` |
| Worker notifikasi & PDF | `php artisan queue:work` |
| Pengingat otomatis | `php artisan schedule:work` |
| Bersihkan cache | `php artisan optimize:clear` |
| Cek kualitas (test + format + analisis) | `composer ci` |
| Hanya test | `php artisan test` |
| Simpan perubahan | `git add .` lalu `git commit -m "pesan"` |

Tip: gunakan beberapa tab terminal Laragon — satu untuk `npm run dev`, satu untuk `queue:work`, satu untuk perintah lain.

## Masalah Umum
| Gejala | Penyebab & solusi |
|---|---|
| `php` / `composer` tidak dikenali | Buka lewat tombol **Terminal** Laragon; untuk alat lain lakukan Langkah 9. |
| `could not find driver` | Ekstensi `pdo_mysql` belum aktif → aktifkan di php.ini (Langkah 2), restart Laragon. |
| `Access denied for user 'root'` | Password MySQL Anda tidak kosong → isi `DB_PASSWORD` di `.env`. |
| `Unknown database 'esurat'` | Database belum dibuat → ulangi Langkah 4. |
| `Connection refused` ke MySQL | MySQL belum jalan → **Start All**; cek port 3306 tidak dipakai MySQL lain. |
| Apache/Nginx gagal start (port 80) | Port dipakai program lain (IIS, Skype, XAMPP) → matikan program itu atau ganti port di Laragon → Menu → Preferences. |
| Codex: `dubious ownership` / `setup refresh had errors` | Folder dimiliki akun Anda, Codex memakai akun sandbox lain. Tutup Codex → Laragon *Run as administrator* → `rmdir /s /q .git` (bila ada `.git` buatan Codex; bila access denied pakai `takeown /f .git /r /d y` + `icacls .git /grant %USERNAME%:F /t`) → `git config --system --add safe.directory C:/laragon/www/esurat` → `git init -b main`, `git add .`, `git commit` sendiri → buka ulang Codex. Bila masih gagal: biarkan Codex hanya menulis kode, Anda yang commit (aturan ada di AGENTS.md). |
| Codex: `curl error 6` (Composer/npm gagal), tak bisa membuat branch | Sandbox Codex tidak punya internet & izin `.git`. Pasang dependency dan buat branch/commit sendiri di Terminal Laragon biasa (bukan Administrator); Codex hanya menulis kode (aturan di AGENTS.md). |
| `esurat.test` tidak terbuka | Pastikan **Auto Virtual Hosts** aktif (Preferences → General), **Stop All → Start All**, atau pakai `php artisan serve`. |
| Perubahan `.env` tidak terbaca | Jalankan `php artisan config:clear`. |
| Tampilan berantakan/tanpa gaya | Jalankan `npm run build` atau biarkan `npm run dev` hidup. |
| `Class "Intl..." not found` | Ekstensi `intl` belum aktif (Langkah 2). |
| `composer` memakan waktu sangat lama | Periksa koneksi internet; coba ulang. Perintah aman diulang. |
| Salah hapus data saat uji | Ulangi `php artisan migrate:fresh --seed` (hanya di komputer pengembangan!). |

> **Jangan pernah** menjalankan `migrate:fresh` di server produksi. Perintah itu menghapus seluruh data.
