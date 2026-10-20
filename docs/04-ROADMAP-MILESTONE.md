# 04 — Roadmap & Milestone

Kerjakan berurutan. Satu milestone = satu branch = satu PR. Setiap milestone selesai bila **Kriteria Selesai** terpenuhi + `pest`/`pint`/`phpstan` hijau + `PROGRESS.md` diperbarui.

## M0 — Fondasi Proyek
**Isi:** install Laravel + Filament, tema kustom orisinal sesuai docs/07 (sidebar terang berpil hijau, kartu ringkasan, huruf 18px, tombol besar, pilihan ukuran huruf, docs/LICENSES.md), konfigurasi locale `id`/timezone, MySQL, Pest, Pint, Larastan, struktur folder (Bagian 1 doc 02), CI sederhana (script `composer ci`), layout panel + branding dasar (nama "E-Surat BPRS Sragen"), halaman login.
**Selesai bila:** aplikasi jalan, login bawaan tampil bahasa Indonesia, `composer ci` hijau, README cara setup.

## M1 — Auth, Pengguna, Peran, Master Data
**Isi:** model User diperluas (nip, unit, jabatan, aktif, file tanda tangan/paraf), Unit (hierarki), Position, spatie permission + seeder peran/izin sesuai matriks, Resource: User, Unit, Position, Classification, LetterType, DispositionInstruction, Setting; kebijakan password; throttle login; idle timeout; activitylog aktif; seeder user demo.
**Selesai bila:** tiap peran login dan hanya melihat menu sesuai izin; test otorisasi menu/resource lulus; perubahan master tercatat di audit.

## M2 — Surat Masuk
**Isi:** migration/model/enum `incoming_letters`, `attachments`, `letter_sequences`; Action `RegisterIncomingLetter` (no. agenda transaksional), cek duplikat, upload scan (private disk + hash + validasi), Resource list/detail/filter/pencarian, aksi "Teruskan", cetak lembar disposisi (PDF), `VisibleToUserScope` untuk kerahasiaan, `SecureFileController`.
**Selesai bila:** admin dapat registrasi & meneruskan; nomor agenda tidak ganda (test konkurensi); surat rahasia tak terlihat pengguna tak berhak (test); file tak bisa diakses tanpa login/izin.

## M3 — Disposisi Berjenjang
**Isi:** `dispositions`, `disposition_recipients`, Action `CreateDisposition`, sub-disposisi, penerima ubah status + laporan + lampiran, timeline pohon disposisi di halaman surat, auto-complete surat, penutupan manual, widget "Disposisi untuk saya".
**Selesai bila:** skenario Direksi→Kepala Divisi→Staf→selesai berjalan dan surat otomatis `completed`; hak akses ditegakkan (test).

## M4 — Surat Keluar: Draft, Template, Penomoran
**Isi:** `outgoing_letters`, `outgoing_letter_versions`, `letter_templates`, `numbering_formats`; Resource draft (rich editor + template + lampiran + tembusan + tautan balasan); `LetterNumberGenerator` (+ test konkurensi); pratinjau PDF draft (tanpa nomor, watermark "DRAFT").
**Selesai bila:** pembuat dapat membuat/ubah draft; generator menghasilkan nomor sesuai pola & benar saat dipanggil paralel.

## M5 — Persetujuan Berjenjang + Delegasi
**Isi:** `approval_flows`, `approval_flow_steps`, `approval_steps`, `delegations`; Action `SubmitOutgoingLetter`, `ApproveStep`, `RejectStep`, `RequestRevision`; `ApprovalService` + `DelegationResolver`; UI antrean "Menunggu persetujuan saya"; halaman riwayat approval; pengelolaan alur (super_admin).
**Selesai bila:** alur review→approve→sign berjalan; revisi menaikkan `round`; pembuat tak dapat menyetujui sendiri; delegat dapat bertindak & tercatat "a.n."; semua tercakup test.

## M6 — Tanda Tangan, QR, PDF Final, Verifikasi Publik
**Isi:** upload tanda tangan/paraf pejabat, `QrService`, `LetterPdfService` + `GenerateFinalPdf` job, hash SHA-256, penomoran saat sign, route `/verifikasi/{token}` (tanpa login, rate-limited, data minimal, status Sah/Dibatalkan), pembatalan surat signed, aksi "Tandai dikirim" + bukti kirim, arsip.
**Selesai bila:** surat sign menghasilkan PDF dengan nomor + TTD + QR yang valid saat dipindai; halaman verifikasi benar untuk surat sah/batal/token salah; PDF final tak dapat diubah via UI.

## M7 — Notifikasi & Pengingat
**Isi:** semua Notification di doc 03 (database + mail, queued), lonceng in-app (polling), preferensi email per pengguna, command `letters:send-reminders` + schedule, anti-duplikasi.
**Selesai bila:** tiap peristiwa mengirim ke penerima yang benar (test `Notification::fake`); pengingat tidak terkirim ganda.

## M8 — Dashboard, Pencarian, Laporan
**Isi:** Beranda sesuai docs/07 §3 (sapaan, kartu ringkasan, daftar "Perlu Tindakan Saya"; tanpa grafik rumit), pencarian global/arsip + filter, laporan Buku Agenda masuk/keluar, rekap disposisi, ekspor PDF/Excel, audit log viewer (super_admin/auditor).
**Selesai bila:** angka dashboard cocok dengan data seed (test); ekspor terbuka & benar; auditor hanya-baca.

## M9 — Hardening, UAT, Deployment
**Isi:** tinjau keamanan (checklist AGENTS.md §5), header keamanan, uji beban ringan (10k surat), seeder data uji besar, dokumentasi pengguna singkat (PDF/MD), skrip backup/restore, panduan deploy on-premise, UAT script.
**Selesai bila:** checklist keamanan lulus, restore backup teruji, UAT disetujui perwakilan BPRS.

## Urutan Ketergantungan
M0 → M1 → M2 → M3 ; M1 → M4 → M5 → M6 ; M2/M3/M5/M6 → M7 → M8 → M9.
