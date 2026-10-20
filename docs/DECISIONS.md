# Catatan Keputusan (ADR ringkas)

Format: tanggal — keputusan — alasan — status.

- 2026-10-19 — Stack Laravel + Filament + MySQL — cepat untuk aplikasi form/tabel/RBAC, mudah dirawat tim IT, dikenal baik oleh coding agent — DISETUJUI
- 2026-10-19 — Deploy on-premise (Ubuntu LTS + Nginx + PHP-FPM + MySQL + Supervisor) — kebijakan data bank — DISETUJUI
- 2026-10-19 — Queue memakai driver `database` (tanpa Redis) — meminimalkan komponen yang dirawat — DISETUJUI
- 2026-10-19 — "Tanda tangan" = gambar tanda tangan/paraf + QR verifikasi internal, BUKAN tanda tangan elektronik tersertifikasi (PSrE) — perlu konfirmasi kepatuhan dari Kepatuhan/Legal bank — MENUNGGU KONFIRMASI
- 2026-10-19 — Nomor surat keluar diberikan saat langkah tanda tangan final disetujui (bukan saat draft) agar tidak ada nomor bolong — DEFAULT, bisa diubah
- 2026-10-19 — Tampilan: desain ORISINAL BPRS Sragen (terinspirasi pola umum admin: menu kiri, kartu ringkasan, tabel), bukan salinan aplikasi/template pihak lain; form satu halaman tanpa wizard; aset hanya berlisensi terbuka — permintaan Product Owner — DISETUJUI
- 2026-10-19 — Laravel dikunci ke 12.x karena PHP lokal pengembang 8.2.29 (Laravel 13 butuh PHP 8.3+). Kode kompatibel PHP 8.2. Rencana: naikkan PHP ke 8.3/8.4 (produksi wajib) lalu pertimbangkan Laravel 13 sebelum dukungan keamanan Laravel 12 berakhir (Feb 2027) — DISETUJUI
- 2026-10-19 — Lingkungan dev pengembang: PHP 8.2.29, MySQL 8.0.30, Git 2.37.3; Composer & Node WAJIB dinaikkan (Composer 2.7+, Node 22 LTS). Produksi: PHP 8.3/8.4 dan MySQL 8.4 LTS atau MariaDB yang masih didukung (MySQL 8.0 sudah EOL) — DISETUJUI
- 2026-10-20 — Pola kerja Codex di Windows: sandbox tanpa internet/izin .git, jadi pengguna menjalankan composer require, npm install, dan perintah Git di Laragon; Codex menulis kode. Pest dikunci ^3 karena PHP 8.2 (Pest 4 butuh PHP 8.3+) — DISETUJUI
- 2026-10-20 — Surat Internal LENGKAP masuk v1 (Nota Dinas, Surat Edaran, Surat Tugas, balasan, edaran massal) sebagai milestone M6B; mesin persetujuan/nomor/PDF/QR/verifikasi dibuat generik-polimorfik — keputusan Product Owner — DISETUJUI
- 2026-10-20 — Beranda bergrafik infografis (Filament ChartWidget/Chart.js) masuk M8; pencarian hanya perihal, pengirim, nomor (tanpa OCR); enkripsi file ditunda — keputusan Product Owner — DISETUJUI
- 2026-10-20 — DISPOSISI DITUNDA ke fase 2 atas arahan mentor; v1 = surat masuk & keluar sederhana dengan fitur "Teruskan" (incoming_letter_recipients); spesifikasi lama diarsipkan di docs/future/DISPOSISI-FASE-2.md — DISETUJUI
- 2026-10-20 — ROMBAKAN TOTAL (arahan mentor + Product Owner): v2 mengikuti struktur aplikasi surat menyurat sederhana — 2 level (admin/user), modul Klasifikasi, Instansi (satu data), Pengguna, Surat Masuk, Surat Keluar, Laporan, Profil; disposisi = Lembar Disposisi cetak sederhana. Spesifikasi lengkap lama (persetujuan berjenjang, TTD+QR, surat internal, notifikasi, kerahasiaan, unit hierarkis, disposisi berjenjang) diarsipkan di docs/future/. Tanpa spatie/laravel-permission (pakai users.level + Policy). Desain visual tetap orisinal BPRS Sragen, bukan salinan aplikasi referensi — DISETUJUI
- 2026-10-20 — Catatan keamanan: aplikasi referensi menampilkan password polos di Data Pengguna; DILARANG di proyek ini (hash, tidak pernah ditampilkan) — DISETUJUI
