# 06 — Panduan Kolaborasi, QA, dan Deployment

## 1. Skema Kerja Sama

| Pihak | Peran | Keluaran |
|---|---|---|
| **Anda (Product Owner)** | Keputusan bisnis, relay antar alat, persetujuan merge | Jawaban `OPEN_QUESTIONS`, UAT |
| **Claude (claude.ai)** | Arsitek & reviewer: spesifikasi, desain, prompt, review diff/keamanan, tulis ulang dokumen | `docs/*`, prompt, laporan review |
| **Codex** | Implementator utama: menulis kode, migration, test per milestone | Branch + PR |
| **Antigravity** | Verifikator: menjalankan aplikasi, menguji alur di browser, mencari bug UI/UX, menulis laporan QA | `docs/qa/Mx-laporan.md` |

> Catatan: Claude dan Codex/Antigravity tidak terhubung langsung. Anda menjadi penghubung (relay): menyalin prompt dari dokumen ini, lalu membawa ringkasan/diff hasilnya kembali untuk direview. Repo Git + `docs/PROGRESS.md` menjadi "memori bersama".

### Siklus per milestone
1. **Siap:** Claude memastikan spesifikasi milestone cukup jelas (perbarui docs bila perlu).
2. **Bangun:** Codex menjalankan prompt Mx pada branch `feature/mX-*`.
3. **Verifikasi:** Antigravity menjalankan Prompt QA (lihat §3) terhadap branch.
4. **Review:** Anda menempelkan diff/ringkasan + laporan QA ke Claude (Prompt Review di doc 05).
5. **Perbaiki:** Codex menjalankan Prompt Perbaikan.
6. **Merge:** Anda merge ke `develop` setelah CI hijau; tag rilis tiap milestone besar (`v0.M`).

### Aturan agar tidak bentrok
- Satu agent aktif per branch pada satu waktu.
- Jangan edit `docs/` oleh agent coding kecuali `PROGRESS.md`, `OPEN_QUESTIONS.md`, `DECISIONS.md`, `qa/*`. Perubahan spesifikasi dilakukan lewat Claude/Anda.
- Keputusan teknis penting → catat di `docs/DECISIONS.md`.
- Git: `main` (rilis), `develop` (integrasi), `feature/mX-*` (kerja). PR wajib memuat ringkasan + hasil `composer ci`.

### Definition of Done (semua milestone)
- Kriteria selesai di roadmap terpenuhi.
- Test baru untuk logika bisnis & otorisasi; `composer ci` hijau.
- Tampilan lolos checklist `docs/07-PANDUAN-UX-SEDERHANA.md` (sesuai desain orisinal docs/07, huruf/tombol besar, istilah sehari-hari).
- Tidak ada teks Indonesia hardcode; migration reversible; seeder demo jalan.
- `PROGRESS.md` diperbarui; tidak ada secret di repo.

## 2. Template PR
```
## Milestone
Mx — nama
## Ringkasan perubahan
## Cara menguji (langkah manual)
## Hasil composer ci  (pest / pint / phpstan)
## Asumsi yang diambil (ASSUMPTION)
## Yang belum selesai / catatan berikutnya
```

## 3. Prompt QA untuk Antigravity
```
Kamu adalah QA engineer. Repo: E-Surat BPRS Sragen (Laravel + Filament). Baca AGENTS.md dan docs/03-ALUR-DAN-HAK-AKSES.md.
1. Siapkan aplikasi: composer install, salin `.env.example` menjadi `.env`, atur kata sandi Admin dengan aman, jalankan `php artisan key:generate`, `php artisan migrate --seed`, lalu jalankan server lokal.
2. Uji milestone {Mx} di browser memakai user demo tiap peran (daftar di seeder/README). Untuk setiap skenario di bawah, catat LULUS/GAGAL, langkah reproduksi, dan screenshot bila gagal.
3. Uji kemudahan sesuai docs/07 §9 (jumlah klik, istilah, ukuran huruf/tombol) seolah-olah kamu pengguna lansia yang gaptek.
4. Fokus khusus: hak akses Admin/User (coba akses URL Admin langsung sebagai User), validasi form, upload file tidak valid, tampilan di lebar tablet/ponsel, teks bahasa Indonesia yang salah/hilang. Kerahasiaan per surat dan notifikasi bukan scope v2.
5. Jangan memperbaiki kode. Hasilkan laporan docs/qa/{Mx}-laporan.md berisi: ringkasan, tabel skenario, daftar bug (Kritis/Tinggi/Sedang/Rendah), saran.
Skenario {Mx}: {salin dari daftar di bawah}
```

### Daftar skenario uji ringkas
- **M0:** login admin dan user dengan username; pengguna nonaktif ditolak; password salah menampilkan pesan ramah; tampilan huruf besar berfungsi.
- **M1:** menu user tidak memuat DATA MASTER dan membuka URL admin = ditolak ramah; Data Pengguna TIDAK menampilkan password; atur ulang password; admin terakhir tidak bisa dihapus; Data Instansi (satu data) tersimpan & logo tampil; riwayat aktivitas mencatat perubahan.
- **M2:** tambah surat masuk (agenda 0001/2026 lalu 0002/2026); duplikat memunculkan peringatan; cari berdasarkan nomor/asal/perihal; unggah file valid & file palsu (.php→.pdf) ditolak; unduh file via URL langsung tanpa login = ditolak; user tidak bisa mengubah surat orang lain atau menghapus.
- **M3:** dari daftar surat → Disposisi → isi → Simpan & Cetak dalam ≤ 4 klik; PDF A4 satu halaman memuat semua field + nama pimpinan; tombol berubah menjadi Cetak/Ubah Disposisi; lencana Sudah didisposisi; user bisa mencetak tetapi tidak bisa membuat/mengubah.
- **M4:** nomor surat keluar otomatis sesuai pola; agenda berurutan; nomor ganda ditolak; hanya admin yang menimpa nomor; pencarian & unduh file.
- **M5:** laporan per rentang tanggal cocok dengan data; cetak rapi di A4 dengan kop; unduh PDF & Excel terbuka; rentang kosong → pesan ramah; rentang > 366 hari ditolak.
- **M6:** angka dashboard & grafik cocok dengan data; grafik terbaca (label/legenda teks); kartu bisa diklik; tampil rapi di tablet; uji kemudahan docs/07 §9 dengan pengguna senior; uji restore backup.

## 4. Deployment On-Premise (panduan ringkas)

**Server:** Ubuntu Server LTS (22.04/24.04), 4 vCPU, 8 GB RAM, 100 GB SSD (cukup untuk awal); VM di jaringan internal.
**Komponen:** Nginx, PHP-FPM 8.3 (ext: mbstring, xml, intl, gd, zip, mysql, bcmath), MySQL 8, Composer, (opsional) ClamAV untuk memindai upload.
**Langkah umum:**
1. Buat user non-root `esurat`; clone repo ke `/var/www/esurat`.
2. `composer install --no-dev --optimize-autoloader`; salin `.env` produksi (`APP_ENV=production`, `APP_DEBUG=false`, DB, SMTP internal, `APP_URL=https://esurat.bprs-sragen.local` — sesuaikan).
3. `php artisan key:generate`, `php artisan migrate --force`, `php artisan db:seed` (akun Admin dari konfigurasi deployment yang aman; jangan memakai password demo), `storage:link` TIDAK diperlukan untuk file private, `config:cache`, `route:cache`, `view:cache`.
4. Nginx: root `public/`, HTTPS dengan sertifikat CA internal, `client_max_body_size 12M`, blok akses ke dotfiles.
5. V2 tidak memerlukan worker antrean atau scheduler.
7. Backup harian 01:00: dump DB + arsip `storage/app/private`; simpan salinan di media/server terpisah; **uji restore berkala**.
8. Hardening: firewall hanya 80/443 dari LAN, SSH key-only, update keamanan otomatis, batasi akses DB ke localhost, rotasi log.

## 5. Risiko & Mitigasi
| Risiko | Mitigasi |
|---|---|
| Agent salah paham spesifikasi | Milestone kecil, kriteria selesai jelas, review Claude + QA Antigravity |
| Nomor surat ganda | Lock transaksi + test konkurensi |
| Akses data/file tanpa hak | Policy per level + test + file privat lewat route terautentikasi |
| Kehilangan data | Backup terjadwal + uji restore |
| Ketergantungan versi paket (Filament/Laravel) | Kunci `composer.lock`, upgrade terencana |
