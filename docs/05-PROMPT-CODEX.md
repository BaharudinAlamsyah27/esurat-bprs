# 05 — Prompt Siap Pakai

Salin-tempel prompt ke Codex (atau agent coding lain). Satu prompt = satu sesi = satu milestone.
Ganti `{…}` bila ada. Jangan gabungkan beberapa milestone dalam satu sesi.

---
## PROMPT PEMBUKA (dipakai di SETIAP sesi, tempel di awal)
```
Kamu adalah engineer senior Laravel yang bekerja di repo "E-Surat BPRS Sragen".
Sebelum menulis kode:
1. Baca AGENTS.md sampai habis dan patuhi semua aturannya.
2. Baca docs/PROGRESS.md untuk tahu posisi terakhir.
3. Baca docs/07-PANDUAN-UX-SEDERHANA.md — semua tampilan HARUS mengikutinya (desain orisinal BPRS Sragen: sederhana, huruf & tombol besar, bahasa sehari-hari, form satu halaman; jangan menyalin desain/kode/aset pihak lain).
4. Baca dokumen yang relevan di docs/ (01-PRD, 02-ARSITEKTUR-DAN-DATABASE, 03-ALUR-BISNIS-DAN-RBAC, 04-ROADMAP-MILESTONE).
Aturan sesi: kerjakan HANYA milestone yang kusebut. Jika ada yang ambigu, tulis di docs/OPEN_QUESTIONS.md, pilih asumsi paling aman, tandai // ASSUMPTION:. Jangan mengganti stack. Jangan hard-code teks Indonesia di kode (pakai lang/id). Akhiri dengan menjalankan: composer ci (setara pest + pint --test + phpstan; di Windows/Laragon jangan memakai ./vendor/bin), lalu perbarui docs/PROGRESS.md dan commit. Beri ringkasan: file yang dibuat/diubah, cara menjalankan, apa yang belum selesai.
```

---
## M0 — Fondasi
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M0 (docs/04-ROADMAP-MILESTONE.md).
- Jika Laravel BELUM terpasang: inisialisasi Laravel 12.x (`laravel/laravel:^12.0`) di root repo ini (jangan buat subfolder); PHP lokal 8.2 jadi jangan memakai Laravel 13 maupun fitur PHP 8.3+. Jika SUDAH terpasang (pemeriksaan: ada `artisan` dan `composer.json`), lewati langkah ini. Lalu pasang Filament, Pest, Pint, Larastan.
- Konfigurasi: APP_LOCALE=id, timezone Asia/Jakarta, DB MySQL (.env.example), queue driver database, session driver database.
- Buat panel Filament di path "/" bernama "E-Surat BPRS Sragen" dengan login bawaan dan terjemahan Indonesia.
- Buat tema kustom Filament orisinal sesuai docs/07 §1, §5, §9: sidebar terang dengan item aktif "pil" hijau dan grup menu (SURAT, ARSIP & LAPORAN, PENGELOLAAN, LANJUTAN), bilah atas dengan notifikasi + menu pengguna, Beranda berisi sapaan + kartu ringkasan (data sementara boleh dummy), token warna dari §5, font 18px dibundel lokal, tombol ≥ 48px, pilihan ukuran huruf. Tulis CSS sendiri. Buat docs/LICENSES.md untuk mencatat lisensi font/ikon.
- Buat struktur folder sesuai docs/02 §1 (folder kosong boleh diberi .gitkeep).
- Tambahkan script composer: "ci" (pint --test, phpstan, pest).
- Perbarui README.md dengan langkah setup lokal.
- Buat 1 test smoke: halaman login dapat dibuka.
Kriteria selesai: lihat M0 di roadmap. Branch: feature/m0-fondasi dari develop.
```

## M1 — Auth, Pengguna, Peran, Master Data
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M1.
- Migration & model: units (hierarki), positions, perluasan users (docs/02 "Pengguna & organisasi"), classifications, letter_types, disposition_instructions, settings.
- Pasang spatie/laravel-permission dan spatie/laravel-activitylog. Buat seeder RolesAndPermissionsSeeder persis mengikuti matriks docs/03 §5 (peran: super_admin, admin_persuratan, direksi, kepala_unit, staf, auditor).
- Filament Resource (dengan Policy) untuk: User, Unit, Position, Classification, LetterType, DispositionInstruction, Setting. Menu dikelompokkan "Master Data" dan "Administrasi".
- User: upload tanda tangan & paraf PNG ke disk private, validasi, hanya pemilik/super_admin yang boleh mengganti.
- Keamanan: validasi password kuat (min 10, huruf besar/kecil, angka), throttle login, idle timeout 30 menit (dari settings), nonaktifkan user is_active=false tidak bisa login.
- Seeder demo (hanya environment local): unit Direksi/Operasional/Pembiayaan/Dana & Jasa/Kepatuhan/Cabang, 1 user per peran + beberapa staf.
- Tulis test Pest: otorisasi tiap peran terhadap tiap Resource, user nonaktif ditolak, audit log tercatat saat master diubah.
Kriteria selesai: lihat M1.
```

## M2 — Surat Masuk
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M2 (docs/01 F3, docs/02, docs/03 §1 & §4).
- Migration/model/enum untuk incoming_letters, attachments (polimorfik), letter_sequences.
- Action RegisterIncomingLetter: nomor agenda via DB::transaction + lockForUpdate pada letter_sequences (category incoming_agenda, scope tahun). Cek duplikat (letter_number+sender_name+letter_date) → beri peringatan di form tetapi izinkan lanjut dengan konfirmasi.
- Upload scan: disk private, validasi MIME asli (pdf/jpg/png), max 10MB, simpan sha256. SecureFileController untuk unduh/pratinjau dengan pengecekan Policy.
- IncomingLetterResource: form registrasi, tabel (filter tahun, status, sifat, kerahasiaan, jenis, klasifikasi; pencarian perihal/pengirim/nomor), halaman detail, aksi "Teruskan ke penerima awal" (ubah status via Action, bukan update langsung).
- VisibleToUserScope + IncomingLetterPolicy sesuai tabel kerahasiaan docs/03 §4.
- Cetak lembar disposisi (PDF, dompdf) berisi kop (dari settings), data surat, kolom instruksi & tanda tangan.
- Test Pest: konkurensi nomor agenda (jalankan generator berulang/paralel dan pastikan unik berurutan), visibilitas per tingkat kerahasiaan per peran, akses file tanpa izin = 403, validasi upload menolak file berbahaya (mis. .php diberi ekstensi .pdf).
Kriteria selesai: lihat M2.
```

## M3 — Disposisi Berjenjang
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M3 (docs/01 F4, docs/03 §1).
- Migration/model/enum: dispositions (parent_id untuk sub-disposisi), disposition_recipients, pivot instruksi.
- Action: CreateDisposition (penerima pengguna dan/atau unit, instruksi checklist, catatan, urgensi, due_at), UpdateRecipientStatus (pending→read→in_progress→done + report_notes + lampiran), CloseDispositionManually (wajib alasan). Penerima yang membuka detail otomatis ditandai read.
- Surat masuk berubah status otomatis: forwarded→in_disposition (disposisi pertama), →in_progress, →completed (semua penerima aktif done) lewat DispositionService.
- UI: komponen timeline pohon disposisi di halaman IncomingLetter; widget "Disposisi untuk saya" (terurut due_at, penanda terlambat); form sub-disposisi hanya untuk penerima.
- Policy: hanya pemegang surat yang boleh mendisposisikan; penerima hanya mengubah statusnya sendiri.
- Test: skenario Direksi→Kepala Divisi→Staf→selesai ⇒ surat completed; pengguna tak berkaitan tidak bisa mendisposisi/melihat; penutupan manual tanpa alasan ditolak.
Kriteria selesai: lihat M3.
```

## M4 — Surat Keluar: Draft & Penomoran
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M4 (docs/01 F5, docs/02, docs/03 §2 "Penomoran").
- Migration/model/enum: outgoing_letters, outgoing_letter_versions, letter_templates, numbering_formats (+ seeder pola default).
- OutgoingLetterResource: form draft SATU halaman, bukan wizard, dengan dua tombol "Simpan Konsep" dan "Kirim untuk Disetujui" (tujuan, perihal, jenis, klasifikasi, sifat, kerahasiaan, penandatangan (hanya user dengan position.is_signer_eligible), tembusan, lampiran, tautan "balasan atas surat masuk", editor isi dengan pilihan template). Pembuat hanya bisa edit draft/revision miliknya.
- Service LetterNumberGenerator: render pola (token {seq:N}, {classification}, {unit}, {roman_month}, {year}), reset tahunan, transaksi + lockForUpdate. JANGAN dipanggil saat draft; hanya disediakan untuk M6.
- Pratinjau PDF draft dengan watermark "DRAFT" (belum ada nomor/QR).
- Test: render pola benar untuk berbagai tanggal; 50 pemanggilan berturut-turut menghasilkan nomor unik berurutan; tahun baru reset ke 1; otorisasi edit draft.
Kriteria selesai: lihat M4.
```

## M5 — Persetujuan Berjenjang + Delegasi
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M5 (docs/03 §2–§3).
- Migration/model: approval_flows, approval_flow_steps, approval_steps, delegations (+ seeder alur default: review Kepala Unit → sign Penandatangan; alur panjang: review → approve Direktur → sign Direktur Utama).
- Action: SubmitOutgoingLetter (validasi kelengkapan, snapshot versi, pilih alur, resolve approver, buat approval_steps round ke-n, aktifkan langkah 1), ApproveStep, RequestRevision (catatan wajib), RejectStep (alasan wajib). Hanya satu langkah pending; langkah berikutnya diaktifkan setelah approve. Pembuat tidak boleh menyetujui suratnya sendiri.
- DelegationResolver + dukungan acting_for pada approval_steps dan disposisi.
- UI: halaman "Menunggu persetujuan saya", panel riwayat approval di detail surat, aksi setujui/revisi/tolak dengan modal catatan, pengelolaan alur untuk super_admin (Resource ApprovalFlow dengan repeater langkah).
- Pada langkah type=sign: untuk M5 cukup ubah status ke `signed` dengan placeholder TODO (penomoran/PDF/QR dikerjakan M6) — beri tanda jelas.
- Test: alur 2 dan 3 langkah; revisi menaikkan round; penolakan final; self-approval ditolak; delegat bisa bertindak dan tercatat; non-approver ditolak.
Kriteria selesai: lihat M5.
```

## M6 — Tanda Tangan, QR, PDF Final, Verifikasi
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M6 (docs/01 F6, docs/03 §2).
- Saat langkah sign disetujui, dalam SATU transaksi: LetterNumberGenerator → letter_number + letter_date, buat verification_token (random 40 char, unik), status=signed, signed_at; setelah commit dispatch job GenerateFinalPdf.
- QrService membuat QR (URL /verifikasi/{token}). LetterPdfService merender PDF final dari blade `resources/views/pdf/outgoing-letter.blade.php`: kop (settings), nomor, isi, gambar tanda tangan penandatangan (dari users.signature_path), QR di pojok bawah, kalimat pengesahan elektronik. Simpan ke disk private, hitung & simpan final_pdf_hash (sha256). Tombol "Buat ulang PDF" untuk admin_persuratan/super_admin jika job gagal.
- Route publik GET /verifikasi/{token} (tanpa login, throttle 30/menit/IP): tampilkan nomor, tanggal, perihal, penandatangan, status SAH atau DIBATALKAN, hash dokumen; token tak dikenal ⇒ halaman "tidak ditemukan" yang tidak membocorkan informasi. Tidak menampilkan isi surat. Catat ke verification_logs.
- Fitur tambahan: unggah PDF di halaman verifikasi untuk mencocokkan hash.
- Aksi: "Tandai dikirim" (tanggal, cara kirim, bukti kirim) → sent; "Arsipkan"; "Batalkan" untuk surat signed (hanya super_admin/direksi, alasan wajib) → QR menampilkan DIBATALKAN.
- Setelah signed: form isi & lampiran read-only; PDF final tidak dapat diganti lewat UI.
- Test: nomor+token terbentuk pada sign; hash PDF tersimpan dan cocok; halaman verifikasi untuk sah/batal/salah; throttle bekerja; isi surat tidak bocor di halaman publik; surat signed tak dapat diedit.
Kriteria selesai: lihat M6.
```

## M7 — Notifikasi & Pengingat
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M7 (docs/03 §6).
- Buat class Notification (ShouldQueue, channel database + mail) untuk SETIAP peristiwa di tabel docs/03 §6; teks di lang/id. Aktifkan Filament database notifications dengan polling 30 detik.
- Panggil notifikasi dari Action terkait (bukan dari Resource).
- Preferensi pengguna: toggle email on/off (in-app selalu aktif).
- Command letters:send-reminders: pengingat H-1, overdue harian, approval menggantung > N hari (N dari settings). Jadwalkan di routes/console.php (07:30 & 13:00 WIB). Tambahkan last_reminded_at agar tidak ganda dalam hari yang sama.
- Test dengan Notification::fake dan Carbon::setTestNow: penerima benar tiap peristiwa, tak ada kiriman ganda, preferensi email dihormati.
Kriteria selesai: lihat M7.
```

## M8 — Dashboard, Pencarian, Laporan
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M8 (docs/01 F8–F10).
- Beranda sesuai docs/07 §3: sapaan, kartu ringkasan per peran (klik = daftar terfilter), daftar "Perlu Tindakan Saya" (maks 5 baris). Tanpa grafik rumit.
- Halaman Arsip & Pencarian (FULLTEXT) dengan filter tahun/jenis/klasifikasi/status/unit/sifat; hormati kerahasiaan.
- Laporan: Buku Agenda Surat Masuk, Buku Agenda Surat Keluar, Rekap Disposisi, Kinerja Tindak Lanjut (rata-rata waktu, % tepat waktu). Ekspor PDF (dompdf) dan Excel (maatwebsite/excel).
- Audit Log viewer (super_admin & auditor) dengan filter pengguna/aksi/tanggal.
- Index DB sesuai kebutuhan query; uji dengan seeder 10.000 surat (buat factory + seeder terpisah `LargeDatasetSeeder`, tidak jalan di produksi).
- Test: angka widget = data seed; laporan tidak memuat surat yang tak boleh dilihat peminta; auditor tidak punya aksi tulis.
Kriteria selesai: lihat M8.
```

## M9 — Hardening & Deploy
```
[PROMPT PEMBUKA]

Tugas: Kerjakan Milestone M9.
- Audit kode terhadap checklist keamanan AGENTS.md §5 dan OWASP Top 10; perbaiki temuan. Tambahkan header keamanan (middleware: X-Frame-Options, X-Content-Type-Options, Referrer-Policy, CSP wajar untuk Filament/Livewire).
- Pastikan APP_DEBUG=false di contoh produksi, cookie secure/httponly/samesite, session timeout.
- Buat skrip `deploy/backup.sh` (mysqldump + tar storage, retensi 30 hari, enkripsi opsional) dan `deploy/restore.md` (prosedur pemulihan), contoh konfigurasi Nginx, supervisor (queue worker), cron (schedule:run), dan `deploy/INSTALL.md` untuk Ubuntu LTS sesuai docs/06 §4.
- Buat docs/USER-GUIDE.md ringkas per peran dan docs/UAT-SCRIPT.md (skenario uji penerimaan).
- Jalankan seluruh test, laporkan cakupan area kritis yang belum diuji.
Kriteria selesai: lihat M9.
```

---
## PROMPT REVIEW (untuk Claude di claude.ai — tempel diff/ringkasan PR)
```
Berikut hasil kerja Codex untuk milestone {Mx}. Tolong review terhadap AGENTS.md dan docs/. Periksa: (1) kesesuaian spesifikasi & kriteria selesai, (2) keamanan (otorisasi, kerahasiaan, upload, SQL/XSS/mass-assignment), (3) race condition pada penomoran/approval, (4) kualitas & kelengkapan test, (5) hal yang terlewat. Beri daftar temuan berprioritas (Kritis/Tinggi/Sedang/Rendah) dan prompt perbaikan yang bisa langsung kutempel ke Codex.
[tempel diff / daftar file / ringkasan Codex]
```

## PROMPT PERBAIKAN GENERIK
```
[PROMPT PEMBUKA]

Perbaiki temuan review berikut pada milestone {Mx}, tanpa mengubah hal lain:
{daftar temuan}
Tambahkan test regresi untuk tiap temuan. Jalankan pest/pint/phpstan lalu perbarui docs/PROGRESS.md.
```
