# 01 — PRD Fase 2

## 1. Perubahan terhadap Fase 1
| Hal | Fase 1 | Fase 2 |
|---|---|---|
| Penerima disposisi | Teks bebas "Diteruskan kepada" | **Akun pengguna** (multi-pilih) + opsi teks tambahan untuk pihak tanpa akun |
| Status disposisi | Tidak ada | Per penerima: `baru → dibaca → selesai` |
| Notifikasi | Tidak ada | Lonceng + push HP |
| Menu | — | **Disposisi Saya** (semua level) |
| Akses | Laptop/browser | + aplikasi Android; tampilan responsif HP |
| Pembuat disposisi | Admin | Tetap Admin (lihat pertanyaan terbuka) |
Lembar Disposisi cetak PDF (A4) tetap ada dan kini memuat nama penerima.

## 2. Cerita Pengguna & Kriteria Penerimaan

### F2-1 Disposisi digital sederhana
- **US-1 (Admin)** Saya memilih satu/lebih pegawai sebagai penerima saat membuat/mengubah disposisi.
  - Kriteria: pilihan berupa pencarian pengguna aktif (tampil nama + jabatan); wajib ≥1 penerima **atau** teks "tujuan lain"; menyimpan membuat satu baris penerima per orang berstatus `baru`; tidak bisa memilih orang yang sama dua kali.
- **US-2 (Admin)** Saya melihat siapa sudah membaca/menyelesaikan.
  - Kriteria: pada disposisi tampil tabel kecil (nama, status, waktu dibaca, waktu selesai, catatan); daftar Surat Masuk menampilkan ringkasan mis. "2 dari 3 selesai".
- **US-3 (Penerima)** Saya melihat daftar disposisi untuk saya.
  - Kriteria: menu **Disposisi Saya** menampilkan hanya disposisi miliknya, terbaru dulu; lencana menu = jumlah berstatus `baru`; kolom ≤5 (Perihal, Asal Surat, Sifat, Tanggal, Status).
- **US-4 (Penerima)** Membuka disposisi menandainya sudah dibaca.
  - Kriteria: pertama kali dibuka → `dibaca` + `read_at` (sekali saja); halaman memuat ringkasan surat, instruksi, catatan pimpinan, tombol lihat file surat.
- **US-5 (Penerima)** Saya menandai sudah ditindaklanjuti.
  - Kriteria: tombol **"Sudah Ditindaklanjuti"** → modal catatan singkat opsional (≤255) → `selesai` + `done_at`; tidak bisa dibatalkan oleh penerima; Admin dapat melihatnya.
- **US-6 (Admin)** Mengubah daftar penerima.
  - Kriteria: menambah penerima → baris baru `baru` (+ notifikasi); menghapus penerima hanya jika belum `selesai`; perubahan tercatat audit.

### F2-2 Notifikasi
- **US-7** Saya menerima notifikasi di HP saat ditunjuk sebagai penerima.
  - Kriteria: judul "E-Surat BPRS", isi **persis** "Anda menerima disposisi baru"; **tidak** memuat perihal, nama pengirim, atau nama pegawai; menekan notifikasi membuka halaman disposisi tersebut.
- **US-8** Saya melihat riwayat notifikasi di dalam aplikasi (lonceng) dan dapat menandainya dibaca.
- **US-9 (Admin/pembuat)** Saya diberi tahu saat penerima menyelesaikan disposisi (notifikasi lonceng + push).
- **US-10** Saya dapat mematikan notifikasi HP dari Profil ("Terima notifikasi di HP").
- **US-11** Setelah **logout**, HP itu tidak menerima notifikasi akun saya. Akun nonaktif tidak menerima notifikasi.
- **US-12 (Admin)** Halaman "Perangkat Terdaftar": pengguna, jumlah perangkat, terakhir aktif, versi aplikasi.

### F2-3 Aplikasi Android
- **US-13** Saya menekan ikon "E-Surat BPRS" dan aplikasi terbuka layar penuh (tanpa kolom alamat/tab) dengan layar pembuka berlogo BPRS.
- **US-14** Login sekali; sesi bertahan sampai batas idle server (30 menit) atau logout. Setelah sesi habis, saya diarahkan ke halaman login tanpa kesalahan teknis.
- **US-15** Saya dapat mengunggah scan surat dari galeri/kamera/berkas.
- **US-16** Saya dapat membuka file surat dan PDF Lembar Disposisi dengan aplikasi PDF di HP, serta membagikan/mencetaknya (sesuai kebijakan).
- **US-17** Bila HP tidak dapat menjangkau server, saya melihat pesan jelas: "Tidak dapat terhubung ke jaringan kantor. Nyalakan Wi-Fi kantor atau VPN, lalu coba lagi." + tombol "Coba Lagi".
- **US-18** Tombol Kembali HP berfungsi wajar; di halaman utama meminta tekan dua kali untuk keluar.
- **US-19** Saya mendapat panduan singkat agar notifikasi tidak terlambat (izin notifikasi, pengecualian penghemat baterai).

### F2-4 Responsif HP
- **US-20** Semua halaman utama (Beranda, Surat Masuk/Keluar, Disposisi Saya, Laporan, Profil, Data Master) nyaman di lebar 360–412 px: tanpa gulir horizontal pada isi utama, teks ≥16px, tombol ≥48px, daftar menjadi **kartu** (bukan tabel) di layar sempit.

## 3. Kebutuhan Non-Fungsional
- **Keamanan:** HTTPS wajib produksi; token FCM & endpoint perangkat dibatasi laju; kredensial Firebase di luar repo/web root; WebView hanya boleh membuka domain E-Surat; rilis tanpa cleartext/debug; `allowBackup=false`.
- **Keandalan:** pengiriman push lewat antrean dengan percobaan ulang (3×, backoff); token tidak sah dibersihkan otomatis; kegagalan FCM **tidak boleh** menggagalkan penyimpanan disposisi.
- **Kinerja:** notifikasi tiba ≤ 30 detik pada kondisi normal (target uji: 95%).
- **Kompatibilitas:** Android 8.0+ (API 26) sebisanya (ikuti minSdk Capacitor terpasang, catat di DECISIONS); Chrome WebView terbaru.
- **Kemudahan:** uji lansia (lihat doc 08): ≥4 dari 5 tugas tanpa bantuan pada ≥3 pegawai senior.
- **Audit:** perangkat terdaftar/dicabut, disposisi dibaca/selesai (tanpa isi) tercatat di Riwayat Aktivitas.

## 4. Metrik Keberhasilan
- 100% disposisi baru menghasilkan notifikasi lonceng; ≥95% push diterima < 30 detik pada perangkat uji.
- Penerima membuka disposisi dari notifikasi dalam ≤2 ketukan.
- Tidak ada kebocoran isi surat pada isi notifikasi (diuji otomatis).
- `composer ci` hijau; tidak ada regresi Fase 1.
