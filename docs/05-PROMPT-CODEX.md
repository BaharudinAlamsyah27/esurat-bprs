# 05 — Prompt Siap Pakai (v2 — disederhanakan)

Salin-tempel ke Codex. Satu prompt = satu sesi = satu milestone. Urutan: **Prompt Pembuka + Prompt Rombakan Total (sekali)** → M0 (bila belum selesai) → M1 → … → M6.

---
## PROMPT PEMBUKA (tempel di awal SETIAP sesi)
```
Kamu adalah engineer senior Laravel di repo "E-Surat BPRS Sragen".
Sebelum menulis kode:
1. Baca AGENTS.md sampai habis dan patuhi semua aturannya (terutama SCOPE v2, §5 Keamanan, §8 Windows/Sandbox).
2. Baca docs/PROGRESS.md untuk tahu posisi terakhir.
3. Baca docs/07-PANDUAN-UX-SEDERHANA.md — tampilan harus sederhana, huruf & tombol besar, bahasa sehari-hari, form satu halaman, desain orisinal (jangan menyalin pihak lain).
4. Baca docs/01-PRD.md, 02-DATABASE-DAN-ARSITEKTUR.md, 03-ALUR-DAN-HAK-AKSES.md, 04-ROADMAP.md.
Kerjakan HANYA milestone yang kusebut. Jika ambigu, tulis di docs/OPEN_QUESTIONS.md, ambil asumsi paling sederhana, tandai // ASSUMPTION:. Jangan mengganti stack. Jangan hardcode teks Indonesia di kode (pakai lang/id). Folder docs/future/ adalah ARSIP fase 2 — jangan dikerjakan. Akhiri dengan: composer ci, perbarui docs/PROGRESS.md, dan beri ringkasan: file yang dibuat/diubah, bagian "Perintah untuk dijalankan pengguna", dan bagian "Belum bisa diverifikasi".
```

---
## PROMPT ROMBAKAN TOTAL (pakai SEKALI sekarang)
```
[PROMPT PEMBUKA]

ROMBAKAN TOTAL SCOPE dari Product Owner atas arahan mentor. Alasan: rancangan lama (disposisi berjenjang, persetujuan berjenjang, tanda tangan + QR, surat internal, notifikasi, kerahasiaan, unit hierarkis) terlalu rumit dipakai. Versi 1 sekarang = aplikasi surat masuk & surat keluar yang SEDERHANA:
- Admin: Data Klasifikasi, Data Instansi (satu data), Data Pengguna, Data Surat Masuk, Data Surat Keluar, Laporan Surat Masuk, Laporan Surat Keluar, Profil, Riwayat Aktivitas.
- User: Data Surat Masuk, Data Surat Keluar, Laporan Surat Masuk, Laporan Surat Keluar, Profil.
- Dashboard: kartu ringkasan + 2 grafik sederhana + surat terbaru.
- DISPOSISI = SEDERHANA: dari surat masuk, tombol "Disposisi" membuka satu form (Sifat, Diteruskan kepada, Isi disposisi [checklist], Catatan) → "Simpan & Cetak" menghasilkan PDF A4 "LEMBAR DISPOSISI" (kop dari Data Instansi + tanda tangan pimpinan). Tanpa pelacakan status, berjenjang, batas waktu, notifikasi, atau akun penerima.
- Hak akses hanya dua level (users.level = admin | user) memakai Policy, TANPA spatie/laravel-permission.

SEMUA spesifikasi lama (persetujuan berjenjang, TTD digital/QR, surat internal, delegasi, notifikasi, kerahasiaan, unit hierarkis, disposisi berjenjang, OCR) sudah dipindah ke docs/future/ sebagai ARSIP. JANGAN dikerjakan dan JANGAN membuat tabel/model/menu/permission/seeder/teks terjemahan untuknya. Jika ragu apakah sesuatu termasuk scope, jangan dibuat; catat di docs/OPEN_QUESTIONS.md.

Baca ulang AGENTS.md dan docs/01 sampai docs/07 yang BARU. Lalu TUGAS SEKARANG (tanpa menulis fitur baru):
a. Audit kode yang sudah ada: cari sisa rancangan lama (mis. spatie/laravel-permission, tabel/model/enum untuk unit hierarkis, approval, disposisi berjenjang, surat internal, notifikasi, kerahasiaan, menu/lang terkait). Hapus/rapikan yang sudah terlanjur dibuat dan laporkan daftarnya. Pertahankan fondasi M0 yang masih berlaku (panel Filament, tema, script ci).
b. Perbarui docs/PROGRESS.md dengan roadmap baru (M0..M6) dan status tiap milestone berdasarkan kode yang ada.
c. Lanjutkan M0 versi baru bila belum selesai (login memakai USERNAME, kolom users.username/level/is_active, helper isAdmin()). Jika paket perlu dipasang, tulis perintah persisnya di "Perintah untuk dijalankan pengguna" (sandbox tanpa internet).
d. Tutup dengan "Perintah untuk dijalankan pengguna" dan "Belum bisa diverifikasi".
```

---
## M0 — Fondasi & Login
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M0 (docs/04-ROADMAP.md). Jika Laravel/Filament sudah terpasang, lewati instalasi dan sesuaikan.
- Laravel 12.x + Filament; panel di path "/" bernama "E-Surat BPRS Sragen"; bahasa Indonesia, timezone Asia/Jakarta, MySQL (.env.example), session/cache driver sederhana.
- Tema kustom orisinal sesuai docs/07 §1, §5, §8: sidebar terang dengan item aktif "pil" hijau, bilah atas (nama pengguna + avatar), font 18px dibundel lokal, tombol ≥ 48px, token warna, menu pengguna dengan pilihan Ukuran Huruf (users.ui_font_scale). Buat docs/LICENSES.md.
- Login memakai USERNAME (kustomisasi halaman login Filament), pesan error ramah. Kolom users: username (unik), level (enum admin|user), is_active, last_login_at, ui_font_scale. Pengguna nonaktif tidak bisa login. Throttle login, idle timeout 30 menit dari settings.
- Helper User::isAdmin(); Policy dasar; seeder admin (password dari .env/diminta saat setup — jangan hardcode password lemah) dan 1 user demo hanya di environment local.
- Script composer "ci" (pint --test, phpstan, pest) dan "lint". Test Pest smoke: halaman login terbuka, admin & user dapat login, nonaktif ditolak.
Kriteria selesai: lihat M0.
```

## M1 — Data Master, Pengguna, Profil
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M1 (docs/01 modul A,B,C,H,I; docs/03 §5).
- Data Klasifikasi: Resource (kode unik, nama, keterangan) + seeder 9 klasifikasi awal (docs/01 A). Klasifikasi yang dipakai surat tidak bisa dihapus (pesan ramah).
- Data Instansi: SATU data saja (halaman edit, bukan daftar): nama, alamat, telepon, email, kota, logo, nama & jabatan pimpinan. Logo disimpan di disk private/terkontrol dan tampil di menu & cetakan.
- Data Pengguna: Resource (username, nama, email opsional, level, aktif). Password di-hash; TIDAK ADA kolom/field yang menampilkan password; aksi "Atur Ulang Password" (Admin menetapkan password baru) dan Nonaktifkan; tidak boleh menghapus/menonaktifkan diri sendiri atau admin terakhir.
- Profil Saya: ubah nama, email, ganti password (wajib password lama), pilihan Ukuran Huruf.
- Riwayat Aktivitas: pasang spatie/laravel-activitylog; Resource baca-saja untuk Admin; catat login/logout/gagal login serta perubahan master & pengguna.
- Policy & menu per level: User tidak melihat grup DATA MASTER dan ditolak (403 ramah) bila membuka URL-nya.
- Test Pest: otorisasi tiap Resource per level, password tak pernah muncul di respons/tabel, admin terakhir terlindungi, audit tercatat.
Paket baru? tulis perintah di "Perintah untuk dijalankan pengguna". Kriteria selesai: lihat M1.
```

## M2 — Data Surat Masuk
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M2 (docs/01 modul D; docs/02; docs/03 §1, §4).
- Migration/model: incoming_letters, letter_sequences (docs/02). Action RegisterIncomingLetter: nomor agenda "0001/2026" via DB::transaction + lockForUpdate (category incoming_agenda, scope tahun).
- IncomingLetterResource: tabel (No Agenda, Nomor Surat, Asal Surat, Perihal, Tgl Surat, kolom Disposisi [placeholder sampai M3], Aksi berteks Lihat/Ubah/Hapus); kotak Cari (nomor surat, asal surat, perihal); filter Tahun & Klasifikasi; tombol "+ Tambah Surat Masuk"; form satu halaman (Keterangan di "Isian tambahan" tertutup); halaman detail dengan pratinjau/unduh file dan tombol Kembali; peringatan duplikat (nomor+asal+tanggal) dengan konfirmasi lanjut.
- File: disk private, validasi MIME asli (pdf/jpg/png) maks 10 MB, simpan sha256, nama acak; unduh/pratinjau lewat SecureFileController (login + Policy). Ganti file = hapus file lama.
- Policy: semua pengguna aktif boleh lihat & tambah; User hanya mengubah surat buatannya; hanya Admin menghapus (SoftDeletes, konfirmasi, tercatat di audit).
- Test Pest: agenda unik berurutan pada pemanggilan paralel & reset tahun baru; User tidak bisa mengubah surat orang lain/menghapus; file tanpa login = ditolak; upload .php berekstensi .pdf ditolak; duplikat memunculkan peringatan.
Kriteria selesai: lihat M2.
```

## M3 — Lembar Disposisi (sederhana)
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M3 (docs/01 modul E; docs/07 §4 "Lembar Disposisi"). Ini fitur kunci kemudahan: harus SANGAT mudah. TIDAK ada pelacakan status, penerima berakun, batas waktu, atau notifikasi.
- Migration/model dispositions (docs/02): incoming_letter_id unik, urgency, forwarded_to (JSON), instructions (JSON), instruction_other, note, disposition_date, created_by/updated_by, printed_at, print_count. Enum Urgency (biasa, penting, segera, sangat_segera) dan daftar instruksi baku di config/enum (bukan master data): Untuk diketahui, Segera tindak lanjuti, Koordinasikan, Siapkan konsep jawaban, Mohon pendapat, Arsipkan, Lainnya.
- Action modal "Disposisi" di surat masuk (baris & detail): Sifat (pilihan besar), Diteruskan kepada (TagsInput dengan saran dari nama/jabatan pengguna, boleh teks bebas, minimal 1), Isi disposisi (CheckboxList + "Lainnya" teks), Catatan. Tombol "Simpan & Cetak" (utama) dan "Simpan saja". Jika sudah ada, tombol berubah menjadi "Cetak Disposisi" dan "Ubah Disposisi".
- DispositionPdfService (dompdf): PDF A4 satu halaman "LEMBAR DISPOSISI" sesuai docs/07 (kop dari Data Instansi, tabel field, kotak ☑ instruksi, tanggal & kota, jabatan + nama pimpinan, ruang tanda tangan). Route cetak terautentikasi (DispositionPrintController); setiap cetak menambah print_count dan tercatat di audit.
- Kolom "Disposisi" di daftar: lencana "✔ Sudah didisposisi"/"Belum" + filter.
- Policy: hanya Admin membuat/mengubah; Admin & User boleh mencetak.
- Test Pest: simpan & cetak menghasilkan PDF (header application/pdf, tidak error); satu surat satu lembar (simpan kedua = ubah); User tidak dapat membuat/mengubah tetapi dapat mencetak; validasi (Diteruskan kepada & Isi disposisi wajib); print_count bertambah.
Paket baru (barryvdh/laravel-dompdf)? tulis perintahnya di "Perintah untuk dijalankan pengguna". Kriteria selesai: lihat M3.
```

## M4 — Data Surat Keluar
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M4 (docs/01 modul F; docs/02; docs/03 §2).
- Migration/model outgoing_letters; LetterNumberGenerator (dipakai agenda & nomor surat; transaksi + lockForUpdate; pola dari settings `outgoing_number_pattern`, token {seq:N}, {kode_klasifikasi}, {bulan_romawi}, {tahun}; reset tahunan).
- OutgoingLetterResource: tabel (No Agenda, Nomor Surat, Tujuan, Perihal, Tgl Surat, Aksi), pencarian (nomor surat, tujuan, perihal), filter Tahun & Klasifikasi, tombol "+ Tambah Surat Keluar", form satu halaman (Nomor Surat terisi otomatis dan hanya Admin boleh menimpa — harus unik; Tujuan, Perihal, Klasifikasi, Tanggal Surat, Tanggal Keluar, File Surat; Keterangan di "Isian tambahan"), detail + unduh file. Hak akses sama seperti surat masuk.
- File: sama seperti M2 (gunakan ulang FileStorageService/SecureFileController).
- Test Pest: 50 pemanggilan paralel/berurutan menghasilkan nomor surat & agenda unik; pola sesuai contoh "045/500/BPRS-SRG/X/2026" pada tanggal uji; tahun baru reset ke 1; timpa manual oleh User ditolak; nomor ganda ditolak.
Kriteria selesai: lihat M4.
```

## M5 — Laporan
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M5 (docs/01 modul G; docs/07 §4 "Laporan").
- Dua halaman: Laporan Surat Masuk dan Laporan Surat Keluar. Kartu "Pilih Tanggal" (Tanggal Awal, Tanggal Akhir, default bulan berjalan, opsi dasar tanggal: Tanggal Surat / Tanggal Diterima(Keluar), filter Klasifikasi opsional) + tombol "Tampilkan"; hasil tampil sebagai tabel dengan judul "Laporan Surat Masuk 01-10-2026 s/d 31-10-2026" (kolom sesuai docs/01 G).
- Tombol Cetak (halaman Blade siap cetak dengan kop Data Instansi, CSS @media print, A4), Unduh PDF (dompdf), Unduh Excel (maatwebsite/excel). Batas rentang 366 hari; rentang kosong → pesan "Belum ada data pada periode ini."
- ReportService satu sumber data untuk tabel, cetak, PDF, Excel. Hanya data non-terhapus. Admin & User boleh.
- Test Pest: hasil = data seed pada rentang; batas tanggal inklusif; PDF/Excel terunduh; rentang > 366 hari ditolak dengan pesan ramah.
Paket baru (maatwebsite/excel)? tulis perintahnya di "Perintah untuk dijalankan pengguna". Kriteria selesai: lihat M5.
```

## M6 — Dashboard & Penyelesaian
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M6 (docs/01 modul J; docs/07 §3, §7, §9).
- Dashboard: sapaan, kartu klik-terfilter (Surat Masuk, Surat Keluar, Sudah Didisposisi, Belum Didisposisi; Admin + Data Klasifikasi & Data Pengguna), 2 ChartWidget (Surat masuk & keluar 6 bulan terakhir — batang; Surat per klasifikasi 5 teratas — donat; judul + kalimat penjelas, legenda teks, angka tampil, tinggi ≥ 280px), TableWidget "Surat Terbaru". Kosong → "Belum ada data." Cache 60 detik.
- Tombol "? Bantuan" di halaman utama + docs/USER-GUIDE.md (1 halaman per level, huruf besar).
- LargeDatasetSeeder (10.000 surat, tidak jalan di produksi) dan cek kinerja daftar; index DB.
- Hardening: header keamanan (middleware), APP_DEBUG=false contoh produksi, cookie secure/httponly/samesite; audit kode terhadap AGENTS.md §5.
- deploy/backup.sh (mysqldump + tar storage, retensi 30 hari), deploy/restore.md, contoh Nginx, deploy/INSTALL.md (Ubuntu LTS, lihat docs/06 §4), docs/UAT-SCRIPT.md.
- Test Pest: angka kartu & data grafik = data seed; User tidak melihat kartu Admin; kondisi kosong aman.
Kriteria selesai: lihat M6.
```

---
## PROMPT REVIEW (untuk Claude — tempel diff/ringkasan)
```
Berikut hasil kerja Codex untuk milestone {Mx}. Review terhadap AGENTS.md dan docs/: (1) kesesuaian scope v2 dan kriteria selesai — pastikan TIDAK ada fitur dari docs/future/, (2) kemudahan pakai (docs/07), (3) keamanan (otorisasi per level, password, upload, XSS/SQL), (4) race condition penomoran, (5) kualitas & kelengkapan test. Beri temuan berprioritas (Kritis/Tinggi/Sedang/Rendah) + prompt perbaikan siap tempel.
[tempel diff / daftar file / ringkasan Codex]
```
## PROMPT PERBAIKAN
```
[PROMPT PEMBUKA]

Perbaiki temuan review berikut pada milestone {Mx}, tanpa mengubah hal lain:
{daftar temuan}
Tambahkan test regresi untuk tiap temuan. Jalankan composer ci lalu perbarui docs/PROGRESS.md.
```
