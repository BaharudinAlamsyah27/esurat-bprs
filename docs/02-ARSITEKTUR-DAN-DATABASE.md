# 02 — Arsitektur & Database

## 1. Arsitektur
Monolit Laravel dengan satu panel Filament di root (`/`). Satu route publik: `/verifikasi/{token}`.

```
app/
├── Enums/                 # LetterStatus, Confidentiality, Urgency, DispositionStatus, ApprovalStepType, ...
├── Models/
├── Policies/
├── Services/              # LetterNumberGenerator, LetterPdfService, QrService, DispositionService,
│                          # ApprovalService, DelegationResolver, ArchiveService
├── Actions/               # RegisterIncomingLetter, CreateDisposition, SubmitOutgoingLetter, ApproveStep,
│                          # RejectStep, RequestRevision, SignOutgoingLetter, MarkAsSent, ...
├── Notifications/         # satu class per peristiwa (database + mail, ShouldQueue)
├── Jobs/                  # GenerateFinalPdf, SendReminder
├── Console/Commands/      # letters:send-reminders (dijadwalkan harian/tiap jam)
├── Http/Controllers/      # VerificationController, SecureFileController
├── Filament/
│   ├── Resources/         # IncomingLetterResource, OutgoingLetterResource, UserResource, UnitResource, ...
│   ├── Pages/             # Dashboard, laporan
│   └── Widgets/
└── Support/               # helper romawi, terbilang, format tanggal
database/{migrations,factories,seeders}
lang/id/
resources/views/{pdf,verification}/
tests/{Feature,Unit}/
```

### Prinsip
- **Action/Service** menjalankan aturan bisnis; Resource memanggilnya. Test menargetkan Action/Service.
- Perubahan status hanya lewat Action (bukan update langsung di Resource).
- Notifikasi dikirim lewat event/listener atau dipanggil dari Action, selalu via queue.
- Kerahasiaan & hak lihat: Global Scope `VisibleToUserScope` + Policy.

## 2. Skema Database
Tipe umum: `id` bigint unsigned PK, `created_at/updated_at`, FK dengan index. `*` = wajib (NOT NULL). Soft delete (`deleted_at`) ditandai (SD).

### Pengguna & organisasi
**users** (SD): name*, nip, username* unik, email* unik, password*, phone, unit_id FK, position_id FK, is_active* default true, signature_path, paraf_path, last_login_at.
**units** (SD): name*, code* unik (mis. `DIR`, `OPS`, `CAB-SRG`), type (`direksi/divisi/bagian/cabang/kas/lainnya`), parent_id FK null, head_user_id FK null, is_active.
**positions**: name*, level* (angka, makin kecil makin tinggi), is_signer_eligible bool.
**delegations**: delegator_id*, delegate_id*, starts_at*, ends_at*, scope (`all/approval/disposition`), reason, created_by. Constraint: delegate ≠ delegator; tidak boleh tumpang tindih tak masuk akal.
Peran/izin: tabel bawaan spatie (`roles`, `permissions`, `model_has_roles`, dst).

### Master surat
**classifications**: code* unik, name*, parent_id, retention_years, is_active.
**letter_types**: name*, code*, category* (`incoming/outgoing/both`), default_approval_flow_id null.
**disposition_instructions**: text*, sort_order, is_active (mis. "Untuk diketahui", "Segera tindak lanjuti", "Koordinasikan", "Siapkan jawaban", "Arsipkan").
**numbering_formats**: category* (`outgoing`/`incoming_agenda`), pattern* (mis. `{seq:3}/{classification}/{unit}/BPRS-SRG/{roman_month}/{year}`), reset_period* (`yearly`), is_active.
**letter_sequences**: category*, scope_key* (mis. `2026` atau `2026-DIR`), last_number* default 0, unik(category, scope_key). Dikunci `lockForUpdate` saat generate.
**letter_templates**: name*, letter_type_id, body_html* (placeholder `{{recipient}}`, `{{subject}}`, dst), is_active.
**settings**: key* unik, value (JSON). (nama instansi, alamat, logo path, SLA default, jam kerja, durasi idle, dll.)

### Surat masuk
**incoming_letters** (SD):
agenda_number* (unik per tahun: unik(agenda_year, agenda_seq)), agenda_year*, agenda_seq*,
received_at*, letter_date*, letter_number* (dari pengirim), sender_name*, sender_institution,
subject*, summary, letter_type_id, classification_id,
urgency* (enum), confidentiality* (enum), delivery_method* (enum), attachments_count int default 0,
status* (enum), registered_by* FK users, initial_recipient_id FK users null (Direksi tujuan),
completed_at, completion_note, archive_location, archived_at.
Index: (agenda_year, agenda_seq), status, received_at, FULLTEXT(subject, summary, sender_name, letter_number).

**dispositions** (SD):
incoming_letter_id*, parent_id null (sub-disposisi), from_user_id*, notes, urgency*, due_at, status* (agregat), closed_at, closed_by.
**disposition_recipients**:
disposition_id*, recipient_user_id null, recipient_unit_id null (salah satu wajib), status* (`pending/read/in_progress/done`), read_at, started_at, done_at, report_notes, acting_for_user_id null (jika via delegasi).
**disposition_instruction_pivot** (`disposition_disposition_instruction`): disposition_id, instruction_id.

### Surat keluar
**outgoing_letters** (SD):
unit_id* (unit pembuat), created_by*, signer_id* (penandatangan), letter_type_id, classification_id,
recipient_name*, recipient_institution, recipient_address, cc (JSON array tembusan),
subject*, body_html, content_mode* (`composed/uploaded`), attachments_count,
urgency*, confidentiality*, reply_to_incoming_id null,
status*, letter_number null unik, letter_date null, numbered_at,
approval_flow_id, current_step_order null,
final_pdf_path null, final_pdf_hash null (SHA-256), verification_token null unik, signed_at,
sent_at, sent_via (enum), sent_proof_path, sent_by, archived_at, archive_location,
cancelled_at, cancel_reason.
Index: status, letter_number, created_by, unit_id, FULLTEXT(subject, recipient_name, letter_number).

**outgoing_letter_versions**: outgoing_letter_id*, version_no*, subject, body_html, changed_by*, change_note, created_at. (snapshot tiap submit/revisi)

### Persetujuan berjenjang
**approval_flows**: name*, letter_type_id null, unit_id null, is_default, is_active.
**approval_flow_steps**: flow_id*, step_order* (1..n), type* (`review/approve/sign`), approver_mode* (`role/position/unit_head/specific_user/signer_field`), approver_ref (id/nama peran/jabatan), label*, is_required default true.
**approval_steps** (instans per surat — snapshot dari flow saat submit):
outgoing_letter_id*, round* (putaran ke-n, naik tiap revisi), step_order*, type*, approver_user_id* (hasil resolve), label,
status* (`waiting/pending/approved/revision_requested/rejected/skipped`), acted_by (bisa delegat), acted_at, note, ip_address.
Aturan: hanya satu langkah `pending` pada satu waktu (sekuensial).

### File & verifikasi
**attachments** (polimorfik): attachable_type*, attachable_id*, disk*, path*, original_name*, mime*, size*, sha256*, kind (`scan/lampiran/laporan/bukti_kirim`), uploaded_by*.
**verification_logs**: outgoing_letter_id, token, ip, user_agent, verified_at (opsional, untuk memantau pemindaian).

### Notifikasi & audit
`notifications` (bawaan Laravel/Filament database notifications), `activity_log` (spatie), `jobs`/`failed_jobs`.

## 3. Relasi Kunci (ringkas)
- IncomingLetter 1—N Disposition (pohon via parent_id) 1—N DispositionRecipient
- OutgoingLetter 1—N ApprovalStep, 1—N OutgoingLetterVersion, 1—N Attachment
- OutgoingLetter N—1 IncomingLetter (reply_to_incoming_id)
- User N—1 Unit, N—1 Position; Unit pohon via parent_id

## 4. Ketentuan Data
- Enum disimpan sebagai string (bukan integer) dan di-cast ke PHP Enum.
- Semua FK `restrictOnDelete`, kecuali tabel pivot (`cascade`).
- Seeder wajib: roles+permissions, 1 super admin, unit contoh (Direksi, Operasional, Pembiayaan, Dana & Jasa, Kepatuhan, Cabang), klasifikasi contoh, instruksi disposisi, approval flow default, numbering format default, **user demo per peran** (password demo hanya di environment local).
