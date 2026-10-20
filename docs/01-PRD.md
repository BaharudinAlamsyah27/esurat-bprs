# 01 — Product Requirements Document (v2, disederhanakan)

## 1. Tujuan
Memindahkan pencatatan surat BPRS Sragen dari buku/spreadsheet ke satu aplikasi web yang **mudah dipakai semua orang**, termasuk yang kurang akrab teknologi:
1. Mencatat **surat masuk** dan **surat keluar** lengkap dengan file scan.
2. Membuat **Lembar Disposisi** untuk surat masuk dengan satu form dan cetak sekali klik.
3. Mencari surat dengan cepat dan mencetak **laporan** per periode.
4. Menampilkan ringkasan di **Dashboard**.

## 2. Ruang Lingkup
**Termasuk:** 9 modul di bawah. **Tidak termasuk (ditunda fase 2, arsip di `docs/future/`):** persetujuan berjenjang, tanda tangan digital & QR, surat internal/nota dinas/edaran, delegasi, notifikasi/pengingat, kerahasiaan per surat, hierarki unit, disposisi berpelacakan/berjenjang, OCR, enkripsi file, integrasi core banking.

## 3. Peran Pengguna (hanya dua level)
| Level | Siapa | Kemampuan utama |
|---|---|---|
| `admin` | Sekretariat/IT | Semua modul, termasuk data master, pengguna, hapus surat, buat disposisi |
| `user` | Pegawai/pimpinan | Melihat & mencari surat, menambah surat, mencetak laporan & disposisi, mengubah profil sendiri |
Matriks lengkap di `03-ALUR-DAN-HAK-AKSES.md`.

## 4. Modul & Field

### A. Data Klasifikasi (Admin)
Kode* (unik, mis. `500`), Judul/Nama*, Keterangan. Seeder awal: klasifikasi arsip umum — 100 Umum, 200 Politik, 300 Pemerintahan, 400 Kesejahteraan Rakyat, 500 Perekonomian, 600 Pekerjaan Umum & Ketenagakerjaan, 700 Pengawasan, 800 Kepegawaian, 900 Keuangan (bisa diubah Admin).

### B. Data Instansi (Admin) — satu data saja
Nama instansi*, alamat, telepon, email, kota (untuk tanggal cetak), logo (PNG/JPG), **nama pimpinan** dan **jabatan pimpinan** (dipakai pada tanda tangan Lembar Disposisi). Dipakai sebagai kop pada laporan dan lembar disposisi. Isi awal: "BPRS Sragen" (placeholder).

### C. Data Pengguna (Admin)
Username* (unik), Nama*, Email (opsional), Level* (`admin`/`user`), Status aktif. Password diisi saat membuat; **tidak pernah ditampilkan**; tersedia tombol "Atur Ulang Password". Pengguna tidak bisa menghapus dirinya sendiri atau admin terakhir.

### D. Data Surat Masuk (Admin & User)
| Field | Keterangan |
|---|---|
| Nomor Agenda | Otomatis, format `0001/2026`, urut per tahun |
| Nomor Surat* | Nomor dari pengirim (diketik) |
| Asal Surat* | Pengirim (instansi/orang) |
| Perihal / Isi Ringkas* | Pokok surat |
| Keterangan | Catatan bebas (opsional) |
| Klasifikasi* | Pilih dari Data Klasifikasi |
| Tanggal Surat* | Tanggal pada surat |
| Tanggal Diterima* | Default hari ini |
| File Surat | Scan PDF/JPG/PNG (maks 10 MB), satu file |
Halaman daftar: tabel (No Agenda, Nomor Surat, Asal Surat, Perihal, Tanggal Surat, status disposisi, Aksi) + kotak **Cari** (nomor surat, asal surat, perihal) + filter Tahun/Klasifikasi/Disposisi. Halaman detail: semua field + pratinjau/unduh file + tombol **Kembali**. Peringatan bila nomor surat + asal + tanggal sama sudah ada.

### E. Disposisi Sederhana — "Lembar Disposisi" (bagian dari Surat Masuk)
Dibuat dari tombol **"Disposisi"** pada surat masuk. **Satu surat = satu lembar** (dapat diubah).
| Field | Keterangan |
|---|---|
| Sifat* | pilihan besar: Biasa / Penting / Segera / Sangat Segera |
| Diteruskan Kepada* | satu atau beberapa tujuan (ketik atau pilih dari daftar pengguna/jabatan; boleh teks bebas) |
| Isi Disposisi* | centang: Untuk diketahui · Segera tindak lanjuti · Koordinasikan · Siapkan konsep jawaban · Mohon pendapat · Arsipkan · **Lainnya (ketik)** |
| Catatan | teks bebas |
| Tanggal Disposisi | default hari ini |
Tombol **"Simpan & Cetak"** (utama) dan "Simpan saja". Hasil cetak = PDF satu halaman A4 **LEMBAR DISPOSISI**: kop instansi, tabel (Asal Surat, Diterima Tgl., No. Surat, No. Agenda, Tgl. Surat, Kode Klasifikasi, Sifat, Perihal, Diteruskan Kepada, Isi Disposisi, Catatan), dan blok tanda tangan pimpinan (jabatan + nama dari Data Instansi, ruang kosong untuk tanda tangan basah). Daftar surat menampilkan lencana **"✔ Sudah didisposisi"**/"Belum". **Tanpa** pelacakan, status kerja, batas waktu, notifikasi, atau akun penerima.

### F. Data Surat Keluar (Admin & User)
| Field | Keterangan |
|---|---|
| Nomor Agenda | Otomatis `0001/2026` per tahun |
| Nomor Surat* | Otomatis dari format yang diatur (default `{seq:3}/{kode_klasifikasi}/BPRS-SRG/{bulan_romawi}/{tahun}` → `045/500/BPRS-SRG/X/2026`), bisa ditimpa manual oleh Admin (harus unik) |
| Tujuan Surat* | Penerima (instansi/orang) |
| Perihal / Isi Ringkas* | |
| Keterangan | opsional |
| Klasifikasi* | |
| Tanggal Surat* | default hari ini |
| Tanggal Keluar | tanggal dikirim (opsional) |
| File Surat | scan/PDF surat final (maks 10 MB) |
Daftar, pencarian (nomor surat, tujuan, perihal), detail, dan filter sama seperti surat masuk.

### G. Laporan Surat Masuk & Laporan Surat Keluar (Admin & User)
Pilih **Tanggal Awal** dan **Tanggal Akhir** → tombol **"Tampilkan"** → tabel hasil + judul "Laporan Surat Masuk 01-10-2026 s/d 31-10-2026". Tombol **Cetak** (tampilan siap cetak dengan kop instansi), **Unduh PDF**, **Unduh Excel**. Kolom surat masuk: No, No. Surat, No. Agenda, Perihal, Asal Surat, Kode Klasifikasi, Tgl Surat, Tgl Diterima, Keterangan. Kolom surat keluar: No, No. Surat, No. Agenda, Perihal, Tujuan, Kode Klasifikasi, Tgl Surat, Tgl Keluar, Keterangan. Opsi filter klasifikasi.

### H. Profil (semua pengguna)
Ubah nama, email, dan **ganti password** (wajib password lama). Tidak ada pengaturan lain.

### I. Riwayat Aktivitas (Admin, menu kecil)
Tabel baca-saja: waktu, pengguna, aksi, objek (login, tambah/ubah/hapus surat, buat/cetak disposisi, perubahan pengguna).

### J. Dashboard
Kartu (klik = buka daftar terfilter): **Surat Masuk**, **Surat Keluar**, **Sudah Didisposisi**, **Belum Didisposisi**; khusus Admin ditambah **Data Klasifikasi** dan **Data Pengguna**. Dua grafik sederhana: **Surat masuk & keluar 6 bulan terakhir** (batang) dan **Surat per klasifikasi (5 teratas)** (donat). Tabel **Surat Terbaru** (5 masuk + 5 keluar). Semua label berbahasa Indonesia, besar, dan terbaca.

## 5. Kebutuhan Non-Fungsional
- **Mudah:** sesuai `07-PANDUAN-UX-SEDERHANA.md`; target uji: pengguna 50+ dapat menambah surat masuk dan mencetak disposisi tanpa bantuan.
- **Aman:** sesuai AGENTS.md §5 (password hash, file privat, throttle login, audit).
- **Kinerja:** daftar 10.000+ surat tetap responsif (index, paginasi server-side).
- **Cadangan:** backup harian DB + file; restore teruji.
- **Kompatibilitas:** Chrome/Edge/Firefox terbaru; nyaman di laptop dan tablet.
- **Data:** tidak ada hard delete (soft delete).

## 6. Kriteria Keberhasilan
- Surat masuk → isi form → dapat lembar disposisi cetak dalam ≤ 6 klik.
- Laporan per periode tampil, tercetak rapi, dan terunduh (PDF/Excel).
- Nomor agenda & nomor surat tidak pernah ganda (diuji paralel).
- User tidak dapat membuka menu Admin maupun menghapus data (diuji).
