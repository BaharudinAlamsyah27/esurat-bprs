# 03 — Alur, Aturan, dan Hak Akses (v2)

## 1. Alur Surat Masuk
1. **+ Tambah Surat Masuk** → isi form satu halaman → Simpan. Nomor agenda otomatis (transaksi + `lockForUpdate`). Peringatan duplikat (nomor surat + asal + tanggal) dengan opsi lanjut.
2. Di daftar/detail: **Lihat · Ubah · Disposisi · Hapus (Admin)**.
3. **Disposisi:** Admin memilih "Disposisi" → satu form berisi Sifat, Diteruskan kepada (satu tujuan per baris), Isi Disposisi, Catatan, Tanggal → **Simpan & Cetak** atau **Simpan saja**. PDF A4 terbuka untuk dicetak. Jika sudah ada: Admin dapat "Ubah Disposisi"; Admin dan User dapat "Cetak Disposisi". Setiap cetak menambah `print_count` dan tercatat di audit.
4. Tidak ada status lanjutan. Satu-satunya status turunan: Sudah/Belum didisposisi.

## 2. Alur Surat Keluar
1. **+ Tambah Surat Keluar** → form satu halaman. Nomor agenda dan nomor surat dibuat otomatis saat disimpan sesuai pola; Admin boleh mengisi nomor surat manual (harus unik). Nomor urut memakai transaksi dan `lockForUpdate`, berdasarkan tahun tanggal surat.
2. Unggah file surat final (opsional tetapi disarankan). Berkas disimpan privat, dengan hash SHA-256, dan hanya bisa dibuka setelah login.
3. Di daftar/detail: **Lihat · Ubah · Hapus (Admin)**. User hanya dapat mengubah surat yang dicatatnya; hanya Admin dapat menghapus dan mengubah nomor manual.
4. Tidak ada persetujuan berjenjang/tanda tangan digital/QR (ditunda).

## 3. Laporan
Pilih rentang tanggal (default: bulan berjalan) → Tampilkan → Cetak / PDF / Excel. Rentang berdasarkan **Tanggal Surat** (default) atau pilihan "Tanggal Diterima/Tanggal Keluar". Maksimal rentang 366 hari. Hanya data yang tidak terhapus (soft delete).

## 4. Aturan File
Satu file per surat. Disimpan di disk private; nama disimpan acak, nama asli di `file_name`. Ganti file = file lama dihapus dari penyimpanan (hash baru). Unduh/pratinjau lewat `SecureFileController` (butuh login).

## 5. Matriks Hak Akses
Legenda: ✔ penuh · ◐ terbatas · — tidak.
| Kemampuan | admin | user |
|---|:-:|:-:|
| Lihat & cari surat masuk/keluar | ✔ | ✔ |
| Tambah surat masuk/keluar | ✔ | ✔ |
| Ubah surat | ✔ semua | ◐ hanya yang ia buat |
| Hapus surat (soft delete) | ✔ | — |
| Buat/ubah Lembar Disposisi | ✔ | — |
| Cetak Lembar Disposisi | ✔ | ✔ (jika sudah dibuat Admin) |
| Laporan (tampil, cetak, PDF, Excel) | ✔ | ✔ |
| Data Klasifikasi, Data Instansi, Data Pengguna | ✔ | — |
| Riwayat Aktivitas | ✔ | — |
| Dashboard | ✔ (6 kartu) | ✔ (4 kartu) |
| Profil sendiri (ubah nama/email/password) | ✔ | ✔ |
Catatan: ini ASUMSI default; konfirmasi di `OPEN_QUESTIONS.md`. Semua pengguna aktif dapat melihat semua surat (belum ada pembatasan rahasia).

## 6. Audit (activitylog)
Login/logout/gagal login; tambah/ubah/hapus surat; ganti file; buat/ubah/cetak disposisi; perubahan pengguna & atur ulang password; perubahan data instansi/klasifikasi.
