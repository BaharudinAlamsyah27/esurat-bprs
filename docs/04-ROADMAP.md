# 04 — Roadmap (v2 — tujuh langkah kecil)

Satu milestone = satu branch = satu sesi Codex. Selesai bila kriteria terpenuhi + `composer ci` hijau + `docs/PROGRESS.md` diperbarui.

## M0 — Fondasi & Login
Laravel 12 + Filament panel di `/`, tema kustom orisinal (docs/07), login memakai **username**, kolom `users.username/level/is_active`, helper `isAdmin()`, seeder admin, script `composer ci`/`lint`, test smoke, `docs/LICENSES.md`.
**Selesai bila:** admin dan user dapat login; pengguna nonaktif ditolak; tampilan awal sesuai docs/07; `composer ci` hijau.

## M1 — Data Master, Pengguna, Profil
Data Klasifikasi (+seeder), Data Instansi (satu data), Data Pengguna (password hash, atur ulang password), Profil, Riwayat Aktivitas, Policy per level, menu per level.
**Selesai bila:** Admin mengelola ketiganya; user tidak melihat menu admin dan ditolak jika membuka URL-nya; password tidak tampil di mana pun; perubahan tercatat.

## M2 — Data Surat Masuk
CRUD, nomor agenda otomatis, upload file privat, detail + pratinjau, pencarian/filter, peringatan duplikat, soft delete (Admin).
**Selesai bila:** agenda tidak ganda pada pemanggilan paralel (test); file tak dapat diakses tanpa login; user hanya mengubah suratnya sendiri.

## M3 — Lembar Disposisi (sederhana)
Form disposisi dari surat masuk, PDF A4 "Lembar Disposisi", lencana Sudah/Belum didisposisi, filter, hitung cetak.
**Selesai bila:** dari daftar surat → Disposisi → Simpan & Cetak ≤ 4 klik; PDF memuat semua field & tanda tangan pimpinan dari Data Instansi; user tidak bisa membuat/mengubah disposisi tetapi dapat mencetak.

## M4 — Data Surat Keluar
CRUD, nomor surat otomatis (pola) + agenda otomatis, upload file, detail, pencarian/filter.
**Selesai bila:** nomor surat dan agenda unik pada pemanggilan paralel; pola nomor sesuai contoh; timpa nomor manual hanya Admin dan harus unik.

## M5 — Laporan
Laporan Surat Masuk & Surat Keluar: rentang tanggal, tabel, cetak (kop instansi), PDF, Excel.
**Selesai bila:** hasil sama dengan data seed (test); rentang kosong menampilkan pesan ramah; cetak rapi di A4.

## M6 — Dashboard & Penyelesaian
Kartu ringkasan (klik = daftar terfilter), 2 grafik, surat terbaru, tombol "? Bantuan", pilihan ukuran huruf, seeder data besar, hardening keamanan, backup/restore, panduan pengguna 1 halaman, UAT.
**Selesai bila:** angka dashboard = data seed; checklist keamanan AGENTS.md §5 lulus; UAT dengan ≥ 3 pengguna senior berhasil.
