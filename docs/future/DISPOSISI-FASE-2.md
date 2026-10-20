> ARSIP (fase 2). Disposisi versi 1 adalah Lembar Disposisi sederhana — lihat docs/01-PRD.md F4. Jangan dikerjakan.

# FASE 2 — Disposisi (DITUNDA, JANGAN DIKERJAKAN SEKARANG)

> Atas arahan mentor, fitur **disposisi** tidak dipakai di versi 1. Dokumen ini hanya arsip spesifikasi untuk fase 2.
> **Agent coding: jangan membuat tabel, model, Resource, menu, permission, notifikasi, grafik, laporan, atau teks terjemahan terkait disposisi sampai Product Owner memerintahkan secara eksplisit.**
> Titik sambung yang sengaja disiapkan di v1: tabel `incoming_letter_recipients` (fitur "Teruskan") dapat menjadi dasar `disposition_recipients`.

## A. Spesifikasi produk (dulu F4 di PRD)
### F4 Disposisi
- Pembuat disposisi (Direksi/Kepala Unit) memilih satu/lebih penerima (pengguna atau unit), instruksi standar (checklist) + catatan, prioritas, batas waktu.
- **Berjenjang:** penerima dapat mendisposisikan lagi (sub-disposisi, `parent_id`) ke bawahannya.
- Penerima: tandai dibaca, ubah status (`pending → read → in_progress → done`), isi laporan tindak lanjut + lampiran.
- Pohon disposisi tampil sebagai timeline di halaman surat.
- Surat masuk berstatus `completed` otomatis saat semua penerima aktif `done` (atau ditutup manual oleh pembuat disposisi/admin dengan alasan).


## B. Skema database (dulu di docs/02)
**dispositions** (SD):
incoming_letter_id*, parent_id null (sub-disposisi), from_user_id*, notes, urgency*, due_at, status* (agregat), closed_at, closed_by.
**disposition_recipients**:
disposition_id*, recipient_user_id null, recipient_unit_id null (salah satu wajib), status* (`pending/read/in_progress/done`), read_at, started_at, done_at, report_notes, acting_for_user_id null (jika via delegasi).
**disposition_instruction_pivot** (`disposition_disposition_instruction`): disposition_id, instruction_id.


## C. Alur & aturan (dulu docs/03 §1)
## 1. Alur Surat Masuk

```
registered ──(admin teruskan ke Direksi/penerima awal)──► forwarded
forwarded ──(disposisi pertama dibuat)──► in_disposition
in_disposition ──(ada penerima mulai kerja)──► in_progress
in_progress ──(semua penerima aktif done / ditutup manual)──► completed
completed ──(admin arsipkan)──► archived
```
Aturan:
1. Hanya `admin_persuratan` yang boleh registrasi dan meneruskan.
2. Disposisi boleh dibuat oleh pemegang surat (penerima awal atau penerima disposisi sebelumnya) — membentuk sub-disposisi.
3. Surat `completed` otomatis bila semua `disposition_recipients` pada pohon berstatus `done`/dibatalkan. Penutupan manual wajib `completion_note`.
4. Surat `archived` bersifat read-only.
5. Peringatan duplikat saat registrasi: kombinasi (letter_number, sender_name, letter_date) sudah ada.
6. Nomor agenda: `{agenda_seq}/{agenda_year}` di-generate transaksional dengan `letter_sequences` (category `incoming_agenda`, scope = tahun).


## D. Milestone (dulu M3)
## M3 — Disposisi Berjenjang
**Isi:** `dispositions`, `disposition_recipients`, Action `CreateDisposition`, sub-disposisi, penerima ubah status + laporan + lampiran, timeline pohon disposisi di halaman surat, auto-complete surat, penutupan manual, widget "Disposisi untuk saya".
**Selesai bila:** skenario Direksi→Kepala Divisi→Staf→selesai berjalan dan surat otomatis `completed`; hak akses ditegakkan (test).


## E. Prompt Codex (dulu M3)
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


## F. Hal lain yang dulu terkait disposisi (untuk dipulihkan di fase 2)
- Notifikasi: disposisi dibuat/diteruskan/selesai, semua disposisi selesai, pengingat H-1 dan terlambat harian.
- Permission: `disposition.create|update_own|close`; peran direksi/kepala_unit membuat disposisi, staf menindaklanjuti.
- Beranda: kartu "Disposisi untuk Saya", grafik "Ketepatan Tindak Lanjut"; laporan "Rekap Disposisi" dan "Kinerja Tindak Lanjut".
- Delegasi: scope `disposition`. Master data: `disposition_instructions`. Status surat masuk: `in_disposition → in_progress → completed`.
