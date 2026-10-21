# 03 — Alur Bisnis, Aturan, RBAC, Notifikasi

## 1. Alur Surat Masuk (tanpa disposisi)

```
registered ──(admin teruskan ke penerima)──► forwarded ──(admin arsipkan)──► archived
```
Aturan:
1. Registrasi dan **Teruskan** hanya oleh `admin_persuratan`. Penerima: orang dan/atau unit, catatan singkat opsional. Satu surat boleh diteruskan berkali-kali (menambah penerima); setiap penerusan tercatat (siapa, kapan, ke siapa).
2. Penerima mendapat notifikasi dan melihat surat di menu Surat Masuk; saat dibuka `read_at` terisi otomatis. **Tidak ada** status kerja, batas waktu, laporan tindak lanjut, atau sub-penerusan (itu disposisi — fase 2).
3. Visibilitas mengikuti §4: penerima (langsung atau lewat unit), registrator, direksi, dan admin sesuai tingkat kerahasiaan.
4. `archived` bersifat read-only.
5. Peringatan duplikat saat registrasi: kombinasi (letter_number, sender_name, letter_date) sudah ada.
6. Nomor agenda: `{agenda_seq}/{agenda_year}` di-generate transaksional dengan `letter_sequences` (category `incoming_agenda`, scope = tahun).
> Disposisi DITUNDA: lihat `docs/future/DISPOSISI-FASE-2.md`. Jangan dibuat.

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
`DelegationResolver::effectiveApprovers($user, $date)`: jika pejabat X punya delegasi aktif ke Y, maka Y boleh bertindak atas langkah approval milik X dan aksi dicatat "Y a.n. X" (`acting_for_user_id`). Pejabat X tetap dapat bertindak.

## 4. Tingkat Kerahasiaan & Visibilitas
| Level | Boleh melihat |
|---|---|
| `biasa` | Pembuat/registrator, penerima/peserta alur, kepala unit terkait, direksi, admin_persuratan, auditor |
| `terbatas` | Pembuat, penerima/peserta alur, pejabat dalam rantai approval/direksi, admin_persuratan (metadata saja pada isi file bila ditetapkan) |
| `rahasia` | Hanya: pembuat, peserta alur, direksi. `super_admin` & `admin_persuratan` hanya melihat **metadata** (nomor, perihal disamarkan opsional, status), bukan isi/lampiran |
Implementasi lewat `VisibleToUserScope` + Policy `view`, dan `SecureFileController` untuk unduh file.

## 5. Matriks Hak Akses (permission → peran)
Legenda: ✔ penuh · ◐ terbatas (sesuai keterlibatan/unit) · — tidak.

| Kemampuan | super_admin | admin_persuratan | direksi | kepala_unit | staf | auditor |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Kelola pengguna/peran/unit/master | ✔ | — | — | — | — | — |
| Pengaturan sistem & alur approval | ✔ | ◐ (lihat) | — | — | — | — |
| Registrasi surat masuk | — | ✔ | — | — | — | — |
| Teruskan surat masuk ke penerima | — | ✔ | — | — | — | — |
| Lihat surat masuk | ◐ metadata | ✔ (sesuai kerahasiaan) | ✔ | ◐ | ◐ | ✔ baca |
| Buat draft surat keluar | — | ✔ | ✔ | ✔ | ✔ | — |
| Review / setujui surat keluar | — | — | ✔ | ◐ (langkahnya) | — | — |
| Tanda tangan (langkah sign) | — | — | ✔ (penandatangan) | ◐ jika ditetapkan | — | — |
| Tandai terkirim / arsipkan | — | ✔ | — | — | — | — |
| Batalkan surat sudah signed | ✔ | — | ✔ | — | — | — |
| Hapus (soft) data surat | ✔ | — | — | — | — | — |
| Lihat laporan | ✔ | ✔ | ✔ | ◐ unit | — | ✔ |
| Lihat audit log | ✔ | — | — | — | — | ✔ |

Permission granular (nama contoh): `incoming_letter.view_any|view|create|update|forward|archive`, `outgoing_letter.view_any|view|create|update_own|submit|cancel|send|archive`, `approval.act`, `report.view`, `audit.view`, `user.manage`, `master.manage`, `setting.manage`. Peran = kumpulan permission; seeder membuatnya.

## 6. Notifikasi
Saluran: in-app + email (queue). Pengingat via scheduler.

| Peristiwa | Penerima | Pesan ringkas |
|---|---|---|
| Surat masuk diteruskan | Penerima awal | "Surat masuk baru: {perihal}" |
| Approval menunggu | Approver langkah aktif | "Surat {perihal} menunggu persetujuan Anda" |
| Revisi diminta | Pembuat | "{nama} meminta revisi: {catatan}" |
| Ditolak | Pembuat | alasan |
| Surat signed & bernomor | Pembuat + admin_persuratan | "Nomor {no} terbit, siap dikirim" |
| Surat dikirim | Pembuat | konfirmasi |
| Approval menggantung > N hari (default 2) | Approver aktif (+ atasan) | pengingat |
| Delegasi dimulai/berakhir | Kedua pihak | info |

Command `letters:send-reminders` dijadwalkan: tiap hari 07:30 dan 13:00 WIB (konfigurabel). Hindari duplikasi (catat `last_reminded_at`).

## 7. Audit
Catat via activitylog: login/logout/gagal login, CRUD master, registrasi surat, setiap perubahan status, penerusan surat, tiap keputusan approval (dengan catatan), unduh file surat rahasia, pembatalan, pencabutan delegasi, pembuatan ulang PDF.


## 8. Surat Internal (Nota Dinas, Surat Edaran, Surat Tugas)

### Status & alur
```
draft ──(kirim untuk disetujui)──► in_approval ──(langkah sign disetujui)──► sent  (OTOMATIS, tanpa tahap kirim manual)
in_approval ──(minta revisi)──► revision ──(perbaiki & kirim ulang)──► in_approval (round+1)
in_approval ──(tolak)──► rejected
sent ──(arsipkan)──► archived
draft/revision ──(batalkan)──► cancelled ; sent ──(batalkan oleh super_admin/direksi + alasan)──► cancelled
```
### Aturan
1. Persetujuan memakai `ApprovalService` yang sama dengan surat keluar. Alur bawaan: **Nota Dinas** = review Kepala Unit → sign; **Surat Edaran** = review Kepala Unit → sign Direksi; **Surat Tugas** = review Kepala Unit → sign Direksi/Kepala Unit. Dapat diubah super_admin.
2. **Pengecualian pembuat = penandatangan:** bila pembuat sendiri adalah penandatangan (mis. Kepala Unit/Direksi), langkah `sign` boleh dilakukan pembuat (konfirmasi password); langkah `review/approve` tidak boleh disetujui pembuat.
3. Saat langkah sign disetujui, dalam SATU transaksi: nomor (`LetterNumberGenerator`, kategori `internal_nota_dinas|internal_surat_edaran|internal_surat_tugas`) + token verifikasi + status `sent` + `sent_at`; setelah commit: job `GenerateFinalPdf` dan job `ResolveAudienceAndDeliver`.
4. `AudienceResolver` mengubah target (`user/unit/role/all`) menjadi daftar user **aktif** pada saat terkirim (snapshot; pegawai baru sesudahnya tidak otomatis menerima). Jumlah besar dipecah per 200 baris lewat queue; duplikasi user dihapus (unik per surat, `to` mengalahkan `cc`).
5. **Balasan:** hanya untuk `nota_dinas`. Yang boleh membalas: penerima `to`/`cc` surat tersebut. Balasan = Nota Dinas baru dengan `reply_to_id` dan `thread_id` (akar rangkaian); penerima awal = pengirim surat asal. Balasan memiliki nomor dan alur persetujuannya sendiri. Rangkaian tampil sebagai garis waktu.
6. **Edaran massal:** `audience_mode=all|units|roles`. Bila `requires_ack=true`, penerima harus menekan "Saya Sudah Membaca & Mengerti"; pembuat melihat progres (n dari m, daftar belum). Pengingat harian ke penerima yang belum konfirmasi setelah N hari (default 2), maksimal 3 kali (`reminder_count`).
7. **Surat Tugas:** wajib mengisi petugas (`assignee`), `task_start_date`, `task_end_date`, `task_location`, `task_purpose`. Tampil di Beranda petugas sebagai "Tugas Dinas" sampai tanggal selesai.
8. Penerima membuka surat → `read_at` terisi otomatis. Surat `sent` tidak dapat diedit; koreksi = batalkan + buat baru.
9. Kerahasiaan mengikuti §4: hanya pembuat, peserta alur, dan penerima (+ direksi) yang melihat isi; `super_admin`/`admin_persuratan` hanya metadata untuk `rahasia`. Untuk `audience_mode=all` kerahasiaan `rahasia` tidak diizinkan.

### Hak akses tambahan
Permission: `internal_letter.view_any|view|create|create_circular|create_assignment|submit|cancel|archive|view_read_progress`.
| Kemampuan | super_admin | admin_persuratan | direksi | kepala_unit | staf | auditor |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Buat Nota Dinas | — | ✔ | ✔ | ✔ | ✔ | — |
| Buat Surat Edaran | — | ✔ | ✔ | ✔ | — | — |
| Buat Surat Tugas | — | ✔ | ✔ | ✔ | — | — |
| Balas Nota Dinas (sebagai penerima) | — | ✔ | ✔ | ✔ | ✔ | — |
| Lihat progres baca edaran | — | ✔ | ✔ | ◐ miliknya | ◐ miliknya | ✔ baca |
| Batalkan surat terkirim | ✔ | — | ✔ | — | — | — |
| Lihat Buku Agenda Surat Internal | ✔ | ✔ | ✔ | ◐ unit | — | ✔ |

### Notifikasi tambahan
| Peristiwa | Penerima |
|---|---|
| Surat internal terkirim | Semua penerima (to/cc/assignee); untuk edaran massal dikirim berkelompok lewat queue |
| Balasan diterima | Pengirim surat asal (+ penerima balasan) |
| Surat Tugas diterbitkan | Petugas |
| Pengingat belum konfirmasi baca | Penerima yang belum konfirmasi (hari ke-N, maks 3x) |
| Semua penerima sudah konfirmasi | Pembuat |
| Surat internal dibatalkan | Semua penerima + pembuat |
| Revisi/tolak/menunggu persetujuan | Sama seperti surat keluar (§6) |
