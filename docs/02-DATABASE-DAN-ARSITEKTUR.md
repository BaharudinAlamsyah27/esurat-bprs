# 02 — Database & Arsitektur (v2)

## 1. Arsitektur
Monolit Laravel + satu panel Filament di root (`/`). Tidak ada route publik selain login.
```
app/
├── Enums/        # UserLevel (admin,user), Urgency (biasa,penting,segera,sangat_segera), DispositionInstruction
├── Models/       # User, Institution, Classification, IncomingLetter, OutgoingLetter, Disposition, LetterSequence
├── Policies/     # satu per model (berdasarkan users.level)
├── Services/     # DispositionPdfService, ReportService, FileStorageService
├── Actions/      # RegisterIncomingLetter, RegisterOutgoingLetter, SaveDisposition
├── Exports/      # IncomingLettersExport, OutgoingLettersExport (maatwebsite/excel)
├── Http/Controllers/  # SecureFileController (unduh/pratinjau), DispositionPrintController, ReportPrintController
├── Filament/{Resources,Pages,Widgets}/
resources/views/{pdf,print}/   # lembar disposisi, laporan
lang/id/  database/{migrations,factories,seeders}  tests/
```

## 2. Skema Tabel
`*` wajib · `SD` = soft delete · semua tabel punya `created_at/updated_at`.

**users** (SD): username* unik, name*, email null unik, password* (hashed), level* (`admin`/`user`), is_active* default true, last_login_at.
**institutions** (hanya 1 baris): name*, address, phone, email, city, logo_path, leader_name, leader_position.
**classifications** (SD): code* unik, name*, description.
**incoming_letters** (SD): agenda_year*, agenda_seq* (unik bersama agenda_year), agenda_number* (teks `0001/2026`), letter_number*, sender*, subject*, note, classification_id* FK, letter_date*, received_date*, file_path, file_name, file_mime, file_size, file_sha256, created_by* FK.
 Index: (agenda_year, agenda_seq), letter_date, received_date, classification_id; FULLTEXT atau LIKE pada (letter_number, sender, subject).
**outgoing_letters** (SD): agenda_year*, agenda_seq* (unik bersama agenda_year), agenda_number*, letter_number* unik, recipient*, subject*, note, classification_id* FK, letter_date*, sent_date null, file_* (sama seperti surat masuk), created_by* FK.
**dispositions**: incoming_letter_id* **unik** (satu surat satu lembar), nature* (enum), forwarded_to* (JSON array teks), instructions* (JSON array kode instruksi), instruction_other null, note null, disposition_date*, created_by*, updated_by null, printed_at null, print_count default 0.
**letter_sequences**: category* (`incoming_agenda`/`outgoing_agenda`/`outgoing_number`), scope_key* (mis. `2026`), last_number* default 0; unik(category, scope_key). Dikunci `lockForUpdate` saat increment.
**settings**: key* unik, value JSON — mis. `outgoing_number_pattern`, `session_idle_minutes`.
**activity_log**: bawaan spatie/laravel-activitylog.

Relasi: Classification 1—N IncomingLetter/OutgoingLetter · IncomingLetter 1—1 Disposition · User 1—N surat (created_by).

## 3. Ketentuan
- Enum disimpan string. FK `restrictOnDelete`. Klasifikasi yang dipakai surat tidak boleh dihapus (hanya dinonaktifkan atau ditolak dengan pesan ramah).
- Migrasi M3 mengarsipkan tabel disposisi berjenjang lama menjadi `legacy_dispositions` tanpa menghapus datanya, lalu membuat tabel `dispositions` sederhana satu lembar per surat.
- Isi penerima dan catatan disposisi tidak disimpan di riwayat aktivitas. Riwayat hanya mencatat ID surat, nama kolom yang berubah, dan jumlah cetak.
- Format nomor surat keluar: token `{seq:N}`, `{kode_klasifikasi}`, `{bulan_romawi}`, `{tahun}`; seq reset tahunan (scope tahun) memakai `LetterSequence`.
- Seeder: 1 admin (`admin`, password diminta saat pertama setup / dari `.env`, **bukan** password lemah tetap), 1 user demo (hanya env local), 9 klasifikasi awal, 1 data instansi, pola nomor default. Data demo besar (`LargeDatasetSeeder`, 10.000 surat) terpisah dan tidak jalan di produksi.
