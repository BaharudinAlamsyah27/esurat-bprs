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
Kamu adalah QA engineer. Repo: E-Surat BPRS Sragen (Laravel + Filament). Baca AGENTS.md dan docs/03-ALUR-BISNIS-DAN-RBAC.md.
1. Siapkan aplikasi: composer install, cp .env.example .env, key:generate, migrate --seed, jalankan server + queue worker.
2. Uji milestone {Mx} di browser memakai user demo tiap peran (daftar di seeder/README). Untuk setiap skenario di bawah, catat LULUS/GAGAL, langkah reproduksi, dan screenshot bila gagal.
3. Uji kemudahan sesuai docs/07 §10 (jumlah klik, istilah, ukuran huruf/tombol) seolah-olah kamu pengguna lansia yang gaptek.
4. Fokus khusus: hak akses (coba akses URL langsung dengan peran yang tidak berhak), tingkat kerahasiaan, validasi form, upload file tidak valid, tampilan di lebar tablet/ponsel, teks bahasa Indonesia yang salah/hilang.
5. Jangan memperbaiki kode. Hasilkan laporan docs/qa/{Mx}-laporan.md berisi: ringkasan, tabel skenario, daftar bug (Kritis/Tinggi/Sedang/Rendah), saran.
Skenario {Mx}: {salin dari daftar di bawah}
```

### Daftar skenario uji ringkas
- **M1:** login tiap peran; menu sesuai peran; user nonaktif ditolak; password lemah ditolak; unggah tanda tangan non-PNG ditolak; akses `/users` oleh staf = ditolak.
- **M2:** registrasi surat; nomor agenda berurutan; duplikat memunculkan peringatan; surat rahasia tak terlihat oleh staf; unduh file via URL langsung tanpa login = ditolak; cetak lembar disposisi.
- **M3:** Direksi disposisi ke Kepala Divisi; Kepala Divisi sub-disposisi ke Staf; Staf selesai → surat completed; staf lain tak bisa mendisposisi; penutupan manual wajib alasan.
- **M4:** buat draft dengan template; pratinjau PDF ber-watermark DRAFT; staf tak bisa mengubah draft orang lain.
- **M5:** submit → review → sign; minta revisi lalu submit ulang; tolak; coba menyetujui surat sendiri; delegasi cuti.
- **M6:** nomor + PDF + QR terbit; pindai QR → halaman verifikasi benar; batalkan → status DIBATALKAN; token ngawur; edit surat signed ditolak.
- **M7:** tiap peristiwa memunculkan notifikasi ke orang yang tepat; email tidak ganda; pengingat jatuh tempo.
- **M8:** angka dashboard cocok; ekspor laporan; filter pencarian; auditor tidak bisa ubah data.
- **M9:** UAT end-to-end dengan perwakilan BPRS; uji restore backup.

## 4. Deployment On-Premise (panduan ringkas)

**Server:** Ubuntu Server LTS (22.04/24.04), 4 vCPU, 8 GB RAM, 100 GB SSD (cukup untuk awal); VM di jaringan internal.
**Komponen:** Nginx, PHP-FPM 8.3 (ext: mbstring, xml, intl, gd, zip, mysql, bcmath), MySQL 8, Supervisor, Composer, (opsional) ClamAV untuk memindai upload.
**Langkah umum:**
1. Buat user non-root `esurat`; clone repo ke `/var/www/esurat`.
2. `composer install --no-dev --optimize-autoloader`; salin `.env` produksi (`APP_ENV=production`, `APP_DEBUG=false`, DB, SMTP internal, `APP_URL=https://esurat.bprs-sragen.local` — sesuaikan).
3. `php artisan key:generate`, `migrate --force`, `db:seed --class=ProductionSeeder` (peran, izin, super admin — BUKAN seeder demo), `storage:link` TIDAK diperlukan untuk file private, `config:cache`, `route:cache`, `view:cache`, `filament:optimize` bila tersedia.
4. Nginx: root `public/`, HTTPS dengan sertifikat CA internal, `client_max_body_size 12M`, blok akses ke dotfiles.
5. Supervisor: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
6. Cron: `* * * * * php /var/www/esurat/artisan schedule:run`.
7. Backup harian 01:00: dump DB + arsip `storage/app/private`; simpan salinan di media/server terpisah; **uji restore berkala**.
8. Hardening: firewall hanya 80/443 dari LAN, SSH key-only, update keamanan otomatis, batasi akses DB ke localhost, rotasi log.
9. Verifikasi QR: pastikan domain internal dalam URL QR dapat dibuka dari perangkat yang akan memindai (mis. ponsel di Wi-Fi kantor). Bila surat dikirim ke pihak luar, pertimbangkan domain publik terbatas khusus `/verifikasi/*`.

## 5. Risiko & Mitigasi
| Risiko | Mitigasi |
|---|---|
| Agent salah paham spesifikasi | Milestone kecil, kriteria selesai jelas, review Claude + QA Antigravity |
| Nomor surat ganda | Lock transaksi + test konkurensi |
| Kebocoran surat rahasia | Global scope + policy + test per peran + pengaman file |
| Keabsahan hukum TTD | Konfirmasi Kepatuhan/Legal; rencana integrasi PSrE bila diwajibkan |
| QR tak bisa dipindai dari luar | Rencanakan domain/verifikasi publik terbatas |
| Kehilangan data | Backup terjadwal + uji restore |
| Ketergantungan versi paket (Filament/Laravel) | Kunci `composer.lock`, upgrade terencana |
