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
