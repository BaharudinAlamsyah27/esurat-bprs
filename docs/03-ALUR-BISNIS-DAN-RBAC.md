# 03 — Alur Bisnis, Aturan, RBAC, Notifikasi

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

## 2. Alur Surat Keluar

```
draft ──(submit)──► in_approval
in_approval ──(step approve)──► [langkah berikutnya] ... ──(step sign approve)──► signed
in_approval ──(minta revisi)──► revision ──(perbaiki & submit lagi)──► in_approval (round+1)
in_approval ──(tolak)──► rejected   (final; pembuat boleh duplikat jadi draft baru)
signed ──(admin tandai kirim)──► sent ──(arsipkan)──► archived
draft/revision ──(batalkan)──► cancelled
signed ──(batalkan oleh super_admin/direksi + alasan)──► cancelled  (QR menampilkan "DIBATALKAN")
```
Aturan:
1. Submit memvalidasi kelengkapan (tujuan, perihal, isi/file, penandatangan) lalu **snapshot** alur ke `approval_steps` dan versi isi ke `outgoing_letter_versions`.
2. Pemilihan alur: cocokkan `approval_flows` berdasar `letter_type_id` + `unit_id`; fallback ke alur `is_default`. Resolusi pejabat: `unit_head` = kepala unit pembuat; `signer_field` = `outgoing_letters.signer_id`; dll.
3. Sekuensial: hanya satu langkah `pending`. Penyetuju hanya boleh bertindak pada langkahnya sendiri (atau delegat aktif).
4. Pembuat dilarang menyetujui suratnya sendiri.
5. Saat langkah `sign` disetujui, dalam **satu transaksi**: (a) generate `letter_number` + `letter_date` = hari itu, (b) buat `verification_token`, (c) set status `signed`, (d) dispatch job `GenerateFinalPdf`. Bila job PDF gagal, status tetap `signed` dengan flag perlu-regenerasi (tombol "Buat ulang PDF" untuk admin).
6. Setelah `signed`: isi tidak dapat diubah. Koreksi = batalkan + buat surat baru dengan referensi.
7. PDF final: kop surat, nomor, isi, tanda tangan penandatangan (gambar), QR di pojok bawah, teks "Dokumen ini disahkan secara elektronik dalam sistem E-Surat BPRS Sragen. Verifikasi: {url}". Simpan `final_pdf_hash` (SHA-256) ke DB.

### Penomoran
- Pola default (konfigurabel di `numbering_formats`): `{seq:3}/{classification}/{unit}/BPRS-SRG/{roman_month}/{year}` → contoh `045/DIR/SKR/BPRS-SRG/X/2026`.
- Urutan reset tiap tahun. Scope key default: `{year}`; opsi per-unit: `{year}-{unit_code}` (ikuti kebijakan bank).
- Implementasi: `DB::transaction` → `LetterSequence::lockForUpdate()` → increment → render pola. Test konkurensi wajib.
- Nomor tidak pernah dipakai ulang; surat batal tetap menyimpan nomornya.

## 3. Delegasi
`DelegationResolver::effectiveApprovers($user, $date)`: jika pejabat X punya delegasi aktif ke Y, maka Y boleh bertindak atas langkah/disposisi milik X dan aksi dicatat "Y a.n. X" (`acting_for_user_id`). Pejabat X tetap dapat bertindak.

## 4. Tingkat Kerahasiaan & Visibilitas
| Level | Boleh melihat |
|---|---|
| `biasa` | Pembuat/registrator, penerima/peserta alur, kepala unit terkait, direksi, admin_persuratan, auditor |
| `terbatas` | Pembuat, penerima/peserta alur, pejabat dalam rantai approval/disposisi, direksi, admin_persuratan (metadata saja pada isi file bila ditetapkan) |
| `rahasia` | Hanya: pembuat, peserta alur/disposisi, direksi. `super_admin` & `admin_persuratan` hanya melihat **metadata** (nomor, perihal disamarkan opsional, status), bukan isi/lampiran |
Implementasi lewat `VisibleToUserScope` + Policy `view`, dan `SecureFileController` untuk unduh file.

## 5. Matriks Hak Akses (permission → peran)
Legenda: ✔ penuh · ◐ terbatas (sesuai keterlibatan/unit) · — tidak.

| Kemampuan | super_admin | admin_persuratan | direksi | kepala_unit | staf | auditor |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Kelola pengguna/peran/unit/master | ✔ | — | — | — | — | — |
| Pengaturan sistem & alur approval | ✔ | ◐ (lihat) | — | — | — | — |
| Registrasi surat masuk | — | ✔ | — | — | — | — |
| Lihat surat masuk | ◐ metadata | ✔ (sesuai kerahasiaan) | ✔ | ◐ | ◐ | ✔ baca |
| Buat disposisi | — | — | ✔ | ◐ (jika pemegang) | — | — |
| Tindak lanjut disposisi | — | — | ✔ | ✔ | ✔ | — |
| Buat draft surat keluar | — | ✔ | ✔ | ✔ | ✔ | — |
| Review / setujui surat keluar | — | — | ✔ | ◐ (langkahnya) | — | — |
| Tanda tangan (langkah sign) | — | — | ✔ (penandatangan) | ◐ jika ditetapkan | — | — |
| Tandai terkirim / arsipkan | — | ✔ | — | — | — | — |
| Batalkan surat sudah signed | ✔ | — | ✔ | — | — | — |
| Hapus (soft) data surat | ✔ | — | — | — | — | — |
| Lihat laporan | ✔ | ✔ | ✔ | ◐ unit | — | ✔ |
| Lihat audit log | ✔ | — | — | — | — | ✔ |

Permission granular (nama contoh): `incoming_letter.view_any|view|create|update|forward|archive`, `disposition.create|update_own|close`, `outgoing_letter.view_any|view|create|update_own|submit|cancel|send|archive`, `approval.act`, `report.view`, `audit.view`, `user.manage`, `master.manage`, `setting.manage`. Peran = kumpulan permission; seeder membuatnya.

## 6. Notifikasi
Saluran: in-app + email (queue). Pengingat via scheduler.

| Peristiwa | Penerima | Pesan ringkas |
|---|---|---|
| Surat masuk diteruskan | Penerima awal | "Surat masuk baru: {perihal}" |
| Disposisi dibuat | Tiap penerima | "Anda menerima disposisi dari {nama}" |
| Disposisi diteruskan (sub) | Penerima baru + pembuat induk | idem |
| Disposisi selesai | Pembuat disposisi | "{nama} menyelesaikan tindak lanjut" |
| Semua disposisi selesai | Pembuat disposisi akar + admin | "Surat {no agenda} selesai ditindaklanjuti" |
| Approval menunggu | Approver langkah aktif | "Surat {perihal} menunggu persetujuan Anda" |
| Revisi diminta | Pembuat | "{nama} meminta revisi: {catatan}" |
| Ditolak | Pembuat | alasan |
| Surat signed & bernomor | Pembuat + admin_persuratan | "Nomor {no} terbit, siap dikirim" |
| Surat dikirim | Pembuat | konfirmasi |
| Pengingat H-1 batas disposisi | Penerima belum done | "Batas tindak lanjut besok" |
| Terlambat (overdue) | Penerima + pembuat disposisi | harian hingga selesai |
| Approval menggantung > N hari (default 2) | Approver aktif (+ atasan) | pengingat |
| Delegasi dimulai/berakhir | Kedua pihak | info |

Command `letters:send-reminders` dijadwalkan: tiap hari 07:30 dan 13:00 WIB (konfigurabel). Hindari duplikasi (catat `last_reminded_at`).

## 7. Audit
Catat via activitylog: login/logout/gagal login, CRUD master, registrasi surat, setiap perubahan status, disposisi, tiap keputusan approval (dengan catatan), unduh file surat rahasia, pembatalan, pencabutan delegasi, pembuatan ulang PDF.
