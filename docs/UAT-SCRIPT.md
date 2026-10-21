# Skenario UAT M6

Minta sedikitnya tiga pegawai senior mencoba aplikasi tanpa diarahkan. Admin mencatat hasil **Lulus/Gagal**, bantuan yang diminta, dan masalah yang ditemui. Gunakan data uji yang aman.

## Dashboard

- Apakah jumlah Surat Masuk dan Surat Keluar sesuai daftar?
- Apakah kartu Sudah/Belum Didisposisi membuka daftar yang sesuai?
- Apakah Admin melihat kartu Data Klasifikasi dan Data Pengguna, sedangkan Pengguna tidak?
- Apakah grafik mudah dipahami dari judul, label, legenda, dan angka?
- Apakah bagian Surat Terbaru membuka rincian yang benar?
- Apakah tombol **? Bantuan** mudah ditemukan dan dipahami?

## Laporan dan akses

- Sebagai Pengguna, buka kedua laporan, pilih tanggal, tampilkan, cetak, dan unduh PDF/Excel.
- Pastikan data kosong menampilkan pesan yang mudah dimengerti.
- Ubah ukuran huruf menjadi Besar dan Sangat Besar; pastikan angka dan tombol tetap terbaca.
- Coba halaman utama pada laptop dan lebar tablet.

## Keamanan dan pemulihan

- Pastikan respons aplikasi memiliki header keamanan dan sesi keluar setelah masa diam yang ditentukan.
- Admin IT mengikuti `deploy/restore.md` untuk menguji backup pada database staging, bukan database produksi.

Catat waktu, peran akun (tanpa kata sandi), skenario, hasil, jumlah bantuan, dan saran pegawai. Jangan simpan kata sandi atau isi surat nyata pada laporan UAT.
