# Log Progres

## Status saat ini
- M0 Fondasi: selesai; sesuai urutan awal proyek siap ke M1. M1 dan M2 juga sudah dikerjakan, dan pengguna mengonfirmasi aplikasi M2 berjalan.
- Milestone aktif: M3 Disposisi Berjenjang (implementasi dan migration tersedia; Pest/UAT alur masih perlu dijalankan).
- Setelah M3 diverifikasi, tahap berikutnya M4 Surat Keluar. Tidak ada fitur baru yang dikerjakan pada sesi sinkronisasi spesifikasi ini.

## Roadmap terbaru (2026-10-20)
- **M0 — Fondasi Proyek:** selesai.
- **M1 — Auth, Pengguna, Peran, Master Data:** selesai.
- **M2 — Surat Masuk:** implementasi tersedia dan aplikasi dikonfirmasi berjalan oleh pengguna; verifikasi formal alur masih perlu dicatat.
- **M3 — Disposisi Berjenjang:** implementasi tersedia; uji alur Direksi → Kepala Unit → Staf dan otorisasi masih tertunda.
- **M4 — Surat Keluar: Draft & Penomoran:** belum dimulai. `LetterNumberGenerator` harus mendukung kategori surat keluar dan tiga kategori surat internal.
- **M5 — Persetujuan Berjenjang + Delegasi:** belum dimulai. `approval_steps` dan `ApprovalService` harus polimorfik/generik untuk surat keluar dan surat internal.
- **M6 — Tanda Tangan, QR, PDF Final, Verifikasi:** belum dimulai. `LetterPdfService`, `QrService`, `verification_logs`, dan verifikasi publik harus generik untuk kedua jenis surat.
- **M6B — Surat Internal:** milestone baru, belum dimulai; mencakup Nota Dinas, Surat Edaran, Surat Tugas, balasan, penerima massal, konfirmasi baca, dan pemakaian ulang mesin generik M4–M6.
- **M7 — Notifikasi & Pengingat:** belum dimulai; mencakup notifikasi dan pengingat untuk peristiwa surat internal.
- **M8 — Dashboard, Pencarian, Laporan:** belum dimulai; menambahkan grafik infografis Filament ChartWidget dan pencarian yang hanya mencocokkan perihal, pengirim, serta nomor surat. OCR tidak dibuat.
- **M9 — Hardening & Deploy:** belum dimulai.

## Dampak sinkronisasi spesifikasi
- M0–M1 tidak memerlukan perubahan lingkup dari pembaruan ini.
- M2 tetap pada pencarian perihal/pengirim/nomor surat; pencarian OCR berada di luar lingkup.
- M3 tidak berubah alur bisnisnya. Tabel `notifications` yang dibutuhkan Filament sudah ditambahkan dan migration-nya berhasil dijalankan saat memperbaiki error sebelumnya.
- M4–M6 perlu merancang generator nomor, persetujuan, PDF, QR, dan verifikasi agar dapat dipakai bersama oleh surat keluar dan surat internal.
- M6B menambah satu milestone sesudah M6; M7 perlu menampung peristiwa surat internal; M8 mencakup enam grafik dan batasan pencarian di atas.
- Tidak ada kode fitur yang ditulis dalam sesi sinkronisasi ini; hanya catatan progres/roadmap yang diperbarui.

## Riwayat
### 2026-10-20 — M3 Disposisi Berjenjang
- M2 sudah dikonfirmasi berjalan oleh pengguna. Implementasi M3 dibuat pada branch `feature/m3-disposisi-berjenjang` di atas M2.
- Selesai: migration tabel disposisi, penerima, dan pivot instruksi; model, enum status, policy, aksi membuat disposisi/sub-disposisi, tindak lanjut berurutan, lampiran laporan privat dengan MIME/hash, penutupan manual beralasan, status surat otomatis, kerahasiaan untuk peserta rantai disposisi, timeline di detail, serta widget "Disposisi untuk saya" yang mengurutkan tenggat dan menandai keterlambatan.
- Migration MySQL sempat gagal karena nama foreign key pivot terlalu panjang. Ketiga tabel M3 dipastikan kosong, hanya tabel-tabel baru itu yang dibersihkan, nama constraint dipendekkan, lalu migration berhasil dijalankan. Tidak ada data surat sebelumnya yang dihapus.
- Perbaikan lanjutan setelah UAT pengguna: tabel notifikasi Laravel yang dipakai Filament ternyata belum ada. Migration `create_notifications_table` ditambahkan dan berhasil dijalankan; daftar notifikasi kini punya tabel yang diperlukan.
- Pemeriksaan: Pint lulus; PHPStan level 6 lulus; migration M3 tercatat berhasil; `git diff --check` lulus; `http://esurat.test` merespons dan mengarahkan ke login. Pest dan uji browser alur disposisi belum dijalankan.
- Cara mencoba: buka `http://esurat.test`, masuk sebagai Direksi/Kepala Unit penerima surat, buka surat yang sudah diteruskan, buat disposisi; penerima membuka surat untuk menandai telah dibaca, lalu ubah menjadi dikerjakan dan selesai dengan laporan.
- Berikutnya: jalankan uji alur Direksi → Kepala Unit → Staf → selesai, termasuk hak akses dan penutupan manual, lalu lanjut M4 setelah disetujui.

## Perintah untuk dijalankan pengguna
- Tidak ada perintah migrasi baru untuk sinkronisasi spesifikasi ini. Migration M3 dan tabel notifikasi sudah dijalankan.
- Untuk memenuhi pemeriksaan penuh M3 setelah UAT, jalankan `composer ci` dari Terminal Laragon di folder proyek.

## Belum bisa diverifikasi
- Uji Pest dan UAT browser M3 belum dijalankan.
- Belum ada pemeriksaan end-to-end untuk M4–M9; milestone tersebut belum dimulai.

(Agent menambah entri baru DI ATAS bagian ini di akhir setiap sesi: tanggal, milestone, selesai, tersisa, cara menjalankan, catatan.)
