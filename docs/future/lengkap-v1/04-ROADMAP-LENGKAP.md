# 04 — Roadmap & Milestone

Kerjakan berurutan. Satu milestone = satu branch = satu PR. Setiap milestone selesai bila **Kriteria Selesai** terpenuhi + `pest`/`pint`/`phpstan` hijau + `PROGRESS.md` diperbarui.

## M0 — Fondasi Proyek
**Isi:** install Laravel + Filament, tema kustom orisinal sesuai docs/07 (sidebar terang berpil hijau, kartu ringkasan, huruf 18px, tombol besar, pilihan ukuran huruf, docs/LICENSES.md), konfigurasi locale `id`/timezone, MySQL, Pest, Pint, Larastan, struktur folder (Bagian 1 doc 02), CI sederhana (script `composer ci`), layout panel + branding dasar (nama "E-Surat BPRS Sragen"), halaman login.
**Selesai bila:** aplikasi jalan, login bawaan tampil bahasa Indonesia, `composer ci` hijau, README cara setup.

## M1 — Auth, Pengguna, Peran, Master Data
**Isi:** model User diperluas (nip, unit, jabatan, aktif, file tanda tangan/paraf), Unit (hierarki), Position, spatie permission + seeder peran/izin sesuai matriks, Resource: User, Unit, Position, Classification, LetterType, Setting; kebijakan password; throttle login; idle timeout; activitylog aktif; seeder user demo.
**Selesai bila:** tiap peran login dan hanya melihat menu sesuai izin; test otorisasi menu/resource lulus; perubahan master tercatat di audit.

## M2 — Surat Masuk
**Isi:** migration/model/enum `incoming_letters`, `attachments`, `letter_sequences`; Action `RegisterIncomingLetter` (no. agenda transaksional), cek duplikat, upload scan (private disk + hash + validasi), Resource list/detail/filter/pencarian, aksi "Teruskan", cetak tanda terima (PDF), `VisibleToUserScope` untuk kerahasiaan, `SecureFileController`.
**Selesai bila:** admin dapat registrasi & meneruskan; nomor agenda tidak ganda (test konkurensi); surat rahasia tak terlihat pengguna tak berhak (test); file tak bisa diakses tanpa login/izin.

## M3 — Teruskan Surat Masuk (distribusi sederhana)
**Isi:** tabel `incoming_letter_recipients`; Action `ForwardIncomingLetter` (pilih penerima orang/unit + catatan singkat opsional; status `registered → forwarded`); penerima melihat surat dan `read_at` terisi otomatis; riwayat penerusan sederhana di halaman surat (siapa, kapan, ke siapa, sudah dibaca/belum); kartu "Surat Baru untuk Saya" di Beranda; Policy/visibilitas. **Tanpa** batas waktu, status kerja, laporan, sub-penerusan (disposisi ditunda — fase 2).
**Selesai bila:** admin meneruskan surat ke 2 orang + 1 unit; penerima melihat surat dan `read_at` tercatat; non-penerima tidak melihat surat terbatas/rahasia (test); riwayat penerusan benar.

## M4 — Surat Keluar: Draft, Template, Penomoran
**Isi:** `outgoing_letters`, `outgoing_letter_versions`, `letter_templates`, `numbering_formats`; Resource draft (rich editor + template + lampiran + tembusan + tautan balasan); `LetterNumberGenerator` (+ test konkurensi); pratinjau PDF draft (tanpa nomor, watermark "DRAFT").
**Selesai bila:** pembuat dapat membuat/ubah draft; generator menghasilkan nomor sesuai pola & benar saat dipanggil paralel.

## M5 — Persetujuan Berjenjang + Delegasi
**Isi:** `approval_flows`, `approval_flow_steps`, `approval_steps`, `delegations`; Action `SubmitOutgoingLetter`, `ApproveStep`, `RejectStep`, `RequestRevision`; `ApprovalService` + `DelegationResolver`; UI antrean "Menunggu persetujuan saya"; halaman riwayat approval; pengelolaan alur (super_admin).
**Selesai bila:** alur review→approve→sign berjalan; revisi menaikkan `round`; pembuat tak dapat menyetujui sendiri; delegat dapat bertindak & tercatat "a.n."; semua tercakup test.

## M6 — Tanda Tangan, QR, PDF Final, Verifikasi Publik
**Isi:** upload tanda tangan/paraf pejabat, `QrService`, `LetterPdfService` + `GenerateFinalPdf` job, hash SHA-256, penomoran saat sign, route `/verifikasi/{token}` (tanpa login, rate-limited, data minimal, status Sah/Dibatalkan), pembatalan surat signed, aksi "Tandai dikirim" + bukti kirim, arsip.
**Selesai bila:** surat sign menghasilkan PDF dengan nomor + TTD + QR yang valid saat dipindai; halaman verifikasi benar untuk surat sah/batal/token salah; PDF final tak dapat diubah via UI.

## M6B — Surat Internal (Nota Dinas, Surat Edaran, Surat Tugas)
**Isi:** `internal_letters`, `internal_letter_targets`, `internal_letter_recipients`, `internal_letter_versions` (docs/02); memakai ulang `ApprovalService`, `LetterNumberGenerator` (3 kategori), `LetterPdfService`, `QrService`, verifikasi publik yang dibuat generik; `AudienceResolver` + job pemecahan penerima per 200; Resource "Surat Internal" dengan tab "Untuk Saya" / "Buatan Saya" (pembuatan lewat 3 tombol besar: Nota Dinas, Surat Edaran, Surat Tugas; form satu halaman per jenis); balasan Nota Dinas dalam rangkaian (thread) dengan garis waktu; baca otomatis + konfirmasi baca + progres + daftar belum baca; tampilan "Tugas Dinas" untuk Surat Tugas di Beranda; pembatalan; arsip; template bawaan per jenis; seeder alur & format nomor (ND, SE, ST).
**Selesai bila:** Nota Dinas end-to-end (draft → disetujui → terkirim otomatis → dibaca → dibalas dalam satu rangkaian); Surat Edaran ke seluruh pegawai demo (≥ 50 user) dengan progres baca benar dan tidak ada penerima ganda; Surat Tugas tampil di Beranda petugas; nomor ND/SE/ST unik dan terpisah (test konkurensi); kerahasiaan & hak membuat sesuai matriks docs/03 §8; QR verifikasi internal berfungsi; semua tercakup test.

## M7 — Notifikasi & Pengingat
**Isi:** semua Notification di doc 03 (termasuk peristiwa surat internal di §8, pengingat konfirmasi baca edaran) (database + mail, queued), lonceng in-app (polling), preferensi email per pengguna, command `letters:send-reminders` + schedule, anti-duplikasi.
**Selesai bila:** tiap peristiwa mengirim ke penerima yang benar (test `Notification::fake`); pengingat tidak terkirim ganda.

## M8 — Dashboard, Pencarian, Laporan
**Isi:** Beranda sesuai docs/07 §3 dan **§3B (grafik infografis: tren, status, jenis, surat masuk dibaca vs belum, beban unit, surat internal)** dengan filter periode, pencarian global/arsip + filter, laporan Buku Agenda masuk/keluar, rekap surat per unit, ekspor PDF/Excel, audit log viewer (super_admin/auditor).
**Selesai bila:** angka dashboard cocok dengan data seed (test); ekspor terbuka & benar; auditor hanya-baca.

## M9 — Hardening, UAT, Deployment
**Isi:** tinjau keamanan (checklist AGENTS.md §5), header keamanan, uji beban ringan (10k surat), seeder data uji besar, dokumentasi pengguna singkat (PDF/MD), skrip backup/restore, panduan deploy on-premise, UAT script.
**Selesai bila:** checklist keamanan lulus, restore backup teruji, UAT disetujui perwakilan BPRS.

## Urutan Ketergantungan
M0 → M1 → M2 → M3 ; M1 → M4 → M5 → M6 ; M6 → M6B (Surat Internal) ; M2/M3/M5/M6B → M7 → M8 → M9.
