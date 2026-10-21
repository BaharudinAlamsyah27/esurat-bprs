# 07 — Panduan Tampilan (UI/UX): Sederhana, Orisinal, Mudah untuk Orang Tua

> Struktur modul dan alur mengikuti pola aplikasi surat menyurat yang umum dan mudah dipahami (menu di kiri, kartu ringkasan, tabel dengan kotak cari). Tampilan visual, warna, logo, ikon, kode, dan teks **dirancang sendiri untuk BPRS Sragen** — jangan menyalin dari aplikasi/template pihak lain.
> Prinsip: **satu layar, satu tujuan, satu tombol utama.** Jika ragu, pilih yang lebih mudah.

## 1. Tata Letak
- **Menu kiri** selalu terbuka (di ponsel menjadi ☰), latar terang, item aktif berupa "pil" hijau, ikon + teks, kelompok berlabel huruf kapital kecil.
- **Bilah atas:** judul halaman di kiri; kanan: nama pengguna + avatar → menu: Profil Saya, Ukuran Huruf, Keluar.
- **Halaman data:** judul + tombol hijau **"+ Tambah …"** di kanan atas, kotak **Cari**, tabel ≤ 6 kolom, paginasi, tombol aksi berteks.
- Logo BPRS Sragen di kiri atas menu (dari Data Instansi).

## 2. Menu
```
🏠 Dashboard
DATA MASTER                  ← hanya Admin
   Data Klasifikasi
   Data Instansi
   Data Pengguna
   Riwayat Aktivitas
SURAT
   Data Surat Masuk
   Data Surat Keluar
LAPORAN
   Laporan Surat Masuk
   Laporan Surat Keluar
Profil Saya  (juga di menu pengguna)
```
User biasa hanya melihat: Dashboard · SURAT · LAPORAN · Profil Saya. Menu yang tak berhak **tidak ditampilkan** (bukan abu-abu).

## 3. Dashboard
Sapaan teks ("Selamat pagi, {Nama}") → **kartu** (ikon dalam lingkaran berwarna, angka besar, label; klik = daftar terfilter): Surat Masuk · Surat Keluar · Sudah Didisposisi · Belum Didisposisi (+ Data Klasifikasi · Data Pengguna untuk Admin) → **2 grafik** (Surat masuk & keluar 6 bulan terakhir — batang; Surat per klasifikasi 5 teratas — donat; Filament ChartWidget; judul jelas + satu kalimat penjelas, legenda teks, angka tampil, tinggi ≥ 280px, huruf ≥ 14px, warna **dan** label) → tabel **Surat Terbaru** (5 masuk + 5 keluar, tombol "Lihat").

## 4. Layar Utama
**Data Surat Masuk** — tabel: No Agenda · Nomor Surat · Asal Surat · Perihal · Tgl Surat · **Disposisi** (✔ Sudah / Belum) · Aksi. Aksi berteks: **Lihat**, **Disposisi**, **Ubah**, **Hapus** (Admin saja, dengan konfirmasi "Hapus surat ini? Surat tidak bisa dikembalikan oleh pengguna biasa."). Tombol **"+ Tambah Surat Masuk"** → form satu halaman (Nomor Surat, Asal Surat, Perihal, Klasifikasi, Tanggal Surat, Tanggal Diterima, File Surat; "Keterangan" di bagian **Isian tambahan** tertutup; Nomor Agenda terlihat otomatis).

**Lembar Disposisi (kunci kemudahan)** — pada baris/detail surat: tombol hijau **"Disposisi"** → panel kecil berisi ringkasan surat (baca-saja) lalu:
1. **Sifat** — 4 tombol pilihan besar (Biasa · Penting · Segera · Sangat Segera).
2. **Diteruskan kepada** — kotak isian dengan saran nama/jabatan (boleh ketik bebas), bisa lebih dari satu.
3. **Isi disposisi** — kotak centang besar (Untuk diketahui, Segera tindak lanjuti, Koordinasikan, Siapkan konsep jawaban, Mohon pendapat, Arsipkan, Lainnya…).
4. **Catatan** (opsional).
Dua tombol: **"Simpan & Cetak"** (utama, besar) dan "Simpan saja". Setelah tersimpan, PDF terbuka otomatis; tombol di baris berganti **"Cetak Disposisi"** dan **"Ubah Disposisi"**.

**Cetakan Lembar Disposisi (A4, satu halaman)** — kop (logo + nama + alamat + telepon), garis, judul **LEMBAR DISPOSISI**, tabel dua kolom: Asal Surat · Diterima Tgl. · No. Surat · No. Agenda · Tgl. Surat · Kode · Sifat · Perihal · Diteruskan Kepada · Isi Disposisi (kotak ☑ untuk yang dipilih) · Catatan; di kanan bawah: "{Kota}, {tanggal}", jabatan pimpinan, ruang tanda tangan, nama pimpinan (dari Data Instansi).

**Data Surat Keluar** — tabel: No Agenda · Nomor Surat · Tujuan · Perihal · Tgl Surat · Aksi (Lihat · Ubah · Hapus-Admin). Form: Nomor Surat (terisi otomatis, bisa diedit Admin), Tujuan, Perihal, Klasifikasi, Tanggal Surat, Tanggal Keluar, File Surat.

**Laporan** — kartu "Pilih Tanggal": Tanggal Awal, Tanggal Akhir (kalender), tombol hijau **"Tampilkan"**; di bawahnya tabel hasil dengan judul rentang dan tombol **Cetak · Unduh PDF · Unduh Excel**. Cetakan memakai kop instansi.

**Data Pengguna** — tabel: Username · Nama · Level · Status · Aksi (Ubah · Atur Ulang Password · Nonaktifkan). **Tidak ada kolom password.**

**Profil Saya** — Nama, Email, tombol "Ganti Password" (password lama + baru + ulangi).

## 5. Gaya Visual (token — dapat disetel sesuai identitas resmi BPRS)
| Token | Nilai awal |
|---|---|
| Primer (hijau tua) | `#0F5C46` · hover `#0B4938` |
| Aksen (emas lembut) | `#B8912F` |
| Latar halaman / kartu | `#F7F5F0` / `#FFFFFF` |
| Teks utama / sekunder | `#1F2933` / `#52606D` |
| Sukses / Peringatan / Bahaya / Info | `#2F855A` / `#C77700` / `#C53030` / `#2B6CB0` |
- Font Plus Jakarta Sans atau Inter (OFL), **18px** dasar, judul 24px+, **dibundel lokal** (tanpa CDN). Tombol ≥ 48px, sudut membulat, jarak lega, tanpa animasi berlebihan.
- Status = ikon + kata + warna. Ikon hanya Heroicons (bawaan Filament). Catat lisensi di `docs/LICENSES.md`.
- Pilihan **Ukuran Huruf** (normal/besar/sangat besar) di menu pengguna, disimpan di `users.ui_font_scale`.
- Responsif laptop & tablet; kolom tabel tambahan disembunyikan secara default (`toggleable`).

## 6. Bahasa & Pesan (di `lang/id`)
| Hindari | Gunakan |
|---|---|
| Submit / Save | Simpan |
| Delete | Hapus |
| Validation error | "Mohon lengkapi: {kolom}" tepat di bawah kolom (merah + ikon) |
| Duplicate | "Surat dengan nomor, asal, dan tanggal yang sama sudah ada. Tetap simpan?" |
| 403 | "Maaf, Anda tidak memiliki akses ke halaman ini." + tombol Kembali |
| 500 | "Terjadi gangguan. Silakan coba lagi. Jika berulang, hubungi Admin IT." |
| Data kosong | "Belum ada data." + tombol + Tambah |
Konfirmasi memakai kalimat lengkap dengan tombol jelas, mis. "Hapus surat ini?" [Ya, Hapus] [Batal].

## 7. Bantuan
Tombol **"? Bantuan"** di tiap halaman utama (satu paragraf + 3 langkah), contoh isian pada kolom ("Contoh: Permohonan Data Nasabah"), dan `docs/USER-GUIDE.md` 1 halaman per level (huruf besar, bisa dicetak).

## 8. Arahan Teknis Filament
- Tema kustom (`php artisan make:filament-theme`) dengan token §5; tulis CSS sendiri. Sidebar terbuka di desktop; `NavigationGroup`: DATA MASTER, SURAT, LAPORAN.
- Resource: form satu halaman, `Section::make('Isian tambahan')->collapsed()`, tabel ≤ 6 kolom default, `paginated([10, 25])`, pencarian toolbar, `->requiresConfirmation()` pada hapus dengan teks §6.
- Disposisi: Action modal Filament (`Action::make('disposisi')`) dengan `Radio`/`ToggleButtons` (Sifat), `TagsInput` bersaran (Diteruskan kepada), `CheckboxList` (Isi disposisi), `Textarea` (Catatan); aksi submit membuka URL PDF (`openUrlInNewTab`).
- Laporan: halaman kustom dengan `DatePicker` Awal/Akhir + tabel; cetak = halaman Blade `print` dengan CSS `@media print`; PDF memakai dompdf; Excel memakai maatwebsite/excel.
- Dashboard: widget `StatsOverview` kustom + dua `ChartWidget` + `TableWidget`.
- Visibilitas menu/aksi lewat Policy dan `shouldRegisterNavigation()`.

## 9. Kriteria Uji Kemudahan (UAT)
Pengguna usia 50+ tanpa pelatihan harus bisa, **tanpa bantuan**: (1) menambah surat masuk; (2) membuat dan mencetak Lembar Disposisi dalam ≤ 4 klik dari daftar; (3) mencari sebuah surat; (4) mencetak laporan bulan ini; (5) memahami semua pesan error. Lolos bila ≥ 4 dari 5 berhasil pada ≥ 3 pengguna senior.
