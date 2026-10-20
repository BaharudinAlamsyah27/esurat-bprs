# 07 — Panduan Tampilan (UI/UX): Sederhana, Orisinal, Mudah untuk Orang Tua

> Desain ini **milik proyek E-Surat BPRS Sragen** dan dirancang sendiri. Pola umum aplikasi admin (menu di sisi kiri, kartu ringkasan, tabel data) boleh dipakai karena sudah lazim, tetapi **jangan menyalin tampilan, warna persis, logo, ikon, teks, atau kode dari aplikasi/template pihak lain**.
> Prinsip: **lengkap di belakang layar, sederhana di depan layar.** Jika ragu antara "lebih canggih" dan "lebih mudah", pilih lebih mudah.

## 1. Arah Desain
- **Bersih, lapang, hangat.** Latar terang kekrem, banyak ruang kosong, satu aksi utama per layar.
- **Identitas BPRS Sragen:** pakai logo resmi BPRS Sragen (dari Admin, disimpan di Pengaturan), warna hijau tua + aksen emas lembut (nuansa syariah, tidak mencolok).
- **Navigasi:** menu di sisi kiri yang **selalu terbuka**, latar terang (putih/krem), item aktif berupa "pil" hijau. Di ponsel berubah menjadi tombol ☰.
- **Bilah atas:** nama halaman di kiri; kanan: lonceng notifikasi (lencana jumlah), nama + jabatan pengguna, menu pengguna (Profil, Ukuran Huruf, Keluar).
- **Pengguna lansia:** huruf besar, tombol besar, teks pada tombol penting (tidak ikon saja), kontras tinggi.

## 2. Struktur Menu (rancangan sendiri)
```
🏠 Beranda
SURAT
   📥 Surat Masuk
   📤 Surat Keluar
   ✉ Surat Internal         (lencana: belum dibaca)
   ✍ Persetujuan           (lencana: menunggu saya)
ARSIP & LAPORAN
   🗂 Arsip
   📊 Laporan
PENGELOLAAN                ← hanya Admin / Super Admin
   Pengguna · Unit Kerja · Klasifikasi Surat
LANJUTAN                   ← hanya Super Admin (kecil, paling bawah)
   Alur Persetujuan · Template Surat · Format Nomor · Jabatan · Jenis Surat · Jejak Aktivitas · Pengaturan Umum
```
Menu yang tidak berhak **tidak ditampilkan sama sekali** (bukan abu-abu).

| Peran | Menu yang terlihat |
|---|---|
| Direksi | Beranda · Surat Masuk · Surat Keluar · Surat Internal · Persetujuan · Arsip · Laporan |
| Kepala Unit | Beranda · Surat Masuk · Surat Keluar · Surat Internal · Persetujuan · Arsip · Laporan (unitnya) |
| Staf | Beranda · Surat Masuk · Surat Keluar · Surat Internal · Arsip |
| Admin Persuratan | Beranda · Surat Masuk · Surat Keluar · Surat Internal · Arsip · Laporan · Pengelolaan (Klasifikasi) |
| Auditor | Beranda · Arsip · Laporan · Jejak Aktivitas |
| Super Admin | Semua, termasuk PENGELOLAAN dan LANJUTAN |

## 3. Beranda
Urutan dari atas ke bawah:
1. **Sapaan** teks sederhana: "Selamat pagi/siang/sore, {Nama}" + peran di bawahnya (tanpa banner besar). Kalimat sapaan bisa diubah di Pengaturan.
2. **Kartu ringkasan** (4–6 kartu, ikon dalam lingkaran berwarna di kiri, angka besar, label di bawahnya; klik = buka daftar terfilter):
   - Admin: Surat Masuk Bulan Ini · Surat Keluar Bulan Ini · Surat Internal Bulan Ini · Menunggu Dikirim · Menunggu Persetujuan.
   - Direksi/Kepala/Staf: Surat Baru untuk Saya · Menunggu Persetujuan Saya · Surat Saya yang Diproses · Surat Internal Belum Dibaca.
3. **"Perlu Tindakan Saya"**: daftar maksimal 5 baris (jenis, perihal, batas waktu, tombol **Buka**). Jika kosong: "Tidak ada yang perlu dikerjakan hari ini 👍".

### 3B. Grafik infografis di Beranda (dikerjakan di M8)
Tujuan: Beranda tampak hidup dan informatif seperti dashboard aplikasi modern, tetapi tetap mudah dipahami. Gunakan **Filament ChartWidget (Chart.js bawaan)** — tanpa library grafik tambahan.
Susunan: sapaan → kartu ringkasan (dengan garis mini tren 7 hari) → grafik (2 kolom, 1 kolom di layar kecil) → "Perlu Tindakan Saya".
| # | Grafik | Bentuk | Isi |
|---|---|---|---|
| 1 | Tren Surat | garis/batang | masuk vs keluar vs internal; pilihan 3 / 6 / 12 bulan |
| 2 | Status Surat | donat | komposisi status; total di tengah |
| 3 | Surat per Jenis | batang horizontal | 5 jenis terbanyak |
| 4 | Surat Masuk Dibaca | donat | sudah dibaca vs belum dibaca (surat yang diteruskan) |
| 5 | Beban per Unit | batang | surat/tugas aktif per unit (Admin/Direksi) |
| 6 | Surat Internal | donat kecil | per jenis (Nota Dinas/Edaran/Tugas) + progres baca edaran terakhir |
Per peran: Admin/Direksi semua; Kepala Unit hanya unitnya; Staf grafik 1–2 miliknya; Auditor agregat saja.
Aturan tampilan: judul jelas + satu kalimat penjelas di bawahnya ("Surat masuk lebih banyak dari bulan lalu"); legenda berupa teks; angka tampil pada grafik/tooltip; warna dari token §5 **dan** label (jangan warna saja); huruf grafik ≥ 14px; tinggi ≥ 280px; tanpa animasi berlebihan; satu dropdown **"Periode"** untuk semua grafik; kosong → "Belum ada data". Data hanya dari surat yang boleh dilihat pengguna; di-cache 60 detik.


## 4. Alur Layar (satu halaman, tanpa wizard)
**Surat Masuk** — tabel (No. Agenda, Tanggal Terima, Pengirim, Perihal, Status) + kotak **Cari** + tombol hijau **"+ Catat Surat Masuk"** → form SATU halaman: Pengirim, Perihal, Tanggal Surat, Nomor Surat, Tujuan, Unggah Scan. Bagian **"Isian tambahan"** (sifat, klasifikasi, ringkasan, jumlah lampiran) tertutup default. Nomor agenda otomatis. Aksi per baris: **Lihat · Ubah · Teruskan · Cetak** (hanya yang berhak).

**Teruskan Surat** — di baris/halaman surat masuk (hanya Admin) tombol **"Teruskan"** → pilih penerima (kotak cari orang/unit), catatan singkat (opsional) → **Kirim**. Penerima mendapat notifikasi dan surat otomatis tercatat "sudah dibaca" saat dibuka. Halaman surat menampilkan riwayat sederhana: siapa meneruskan, kapan, ke siapa, sudah/belum dibaca. *(Disposisi dengan instruksi & batas waktu ditunda ke fase 2.)*

**Surat Keluar** — **"+ Buat Surat Keluar"** → form SATU halaman: Kepada, Perihal, Isi Surat (pilih contoh/template), Penandatangan, Lampiran; "Isian tambahan" tertutup. Dua tombol: **Simpan Konsep** dan **Kirim untuk Disetujui**. Alur persetujuan dipilih otomatis.

**Persetujuan** — daftar menunggu → buka → surat tampil besar dan hanya **3 tombol**: ✅ **Setujui** · ↩ **Kembalikan untuk Diperbaiki** · ❌ **Tolak**. Langkah penandatangan: **"Setujui & Tanda Tangan"** (konfirmasi password sekali). Nomor, tanda tangan, QR terpasang otomatis.

**Surat Internal** — menu "Surat Internal" punya dua tab besar: **Untuk Saya** dan **Buatan Saya**. Tombol **"+ Buat Surat Internal"** menampilkan 3 tombol besar: **Nota Dinas · Surat Edaran · Surat Tugas**, lalu form SATU halaman (Kepada, Perihal, Isi; Surat Edaran: pilih **Semua pegawai / Unit / Orang** + opsi "Wajib konfirmasi sudah membaca"; Surat Tugas: nama petugas, tujuan, tanggal, lokasi). Dua tombol bawah: **Simpan Konsep** dan **Kirim untuk Disetujui**. Setelah ditandatangani surat otomatis terkirim. Penerima membuka surat → tombol **"Balas"** (Nota Dinas) atau **"Saya Sudah Membaca & Mengerti"** (bila diminta). Pembuat edaran melihat progres "120 dari 150 sudah membaca" dan tombol **"Ingatkan yang belum"**.

**Mengirim** — Admin: pada surat bernomor, tombol **"Tandai Sudah Dikirim"**.

## 5. Gaya Visual (token desain — dapat disetel di tema)
| Token | Nilai awal (boleh disesuaikan dengan identitas resmi BPRS) |
|---|---|
| Primer (hijau tua) | `#0F5C46` · hover `#0B4938` |
| Aksen (emas lembut) | `#B8912F` |
| Latar halaman | `#F7F5F0` (krem muda) · Kartu `#FFFFFF` |
| Teks utama / sekunder | `#1F2933` / `#52606D` |
| Sukses / Peringatan / Bahaya / Info | `#2F855A` / `#C77700` / `#C53030` / `#2B6CB0` |
- Font: **Plus Jakarta Sans** atau **Inter** (lisensi terbuka OFL), **18px** dasar, judul 24px+. **Dibundel lokal** (server on-premise mungkin tanpa internet), tanpa CDN.
- Tombol tinggi ≥ 48px, sudut membulat, jarak lega; tanpa animasi berlebihan.
- Status = **ikon + kata + warna**: ✔ Selesai · ⏳ Menunggu · ⚠ Terlambat · ● Konsep.
- Ikon: hanya set berlisensi terbuka (Heroicons bawaan Filament). Dilarang memakai ikon/gambar/logo berhak cipta pihak lain.
- **Ukuran huruf** (normal / besar / sangat besar) di menu pengguna, disimpan di `users.ui_font_scale`.
- Responsif untuk laptop dan tablet.

## 6. Bahasa & Pesan (di `lang/id`)
| Hindari | Gunakan |
|---|---|
| Submit | Kirim untuk Disetujui |
| Approve / Reject | Setujui / Tolak |
| Request revision | Kembalikan untuk Diperbaiki |
| Forward | Teruskan |
| Draft | Konsep |
| Archive | Simpan ke Arsip |
| Validation error | "Mohon lengkapi: {kolom}" tepat di bawah kolom (merah + ikon) |
| 403 | "Maaf, Anda tidak memiliki akses ke halaman ini." + tombol Kembali |
| 500 | "Terjadi gangguan. Silakan coba lagi. Jika berulang, hubungi Admin IT." |
Konfirmasi: "Kirim surat ini kepada Bapak/Ibu {nama} untuk disetujui?" [Ya, Kirim] [Belum]

## 7. Bantuan
Tombol **"? Bantuan"** di tiap halaman utama (satu paragraf + 3 langkah), contoh isian pada kolom ("Contoh: Permohonan Data Nasabah"), dan `docs/USER-GUIDE.md` 1 halaman per peran (huruf besar, bisa dicetak).

## 8. Ditunda / Disembunyikan
2FA (opsional oleh Super Admin), pengaturan delegasi (diatur Admin), pencarian canggih (cukup kotak Cari + "Pencarian lebih rinci"), WhatsApp (fase 2). Satu tombol **"Unduh"** di laporan.

## 9. Arahan Teknis Filament
- Tema kustom (`php artisan make:filament-theme`) dengan token §5: sidebar terang dengan item aktif "pil" hijau, font 18px, tombol `min-height: 3rem`, kartu ringkasan berikon lingkaran. **Tulis CSS sendiri** — jangan menempelkan template admin pihak ketiga.
- Panel: warna primer dari token; sidebar terbuka di desktop; `NavigationGroup` bernama sesuai §2 (SURAT, ARSIP & LAPORAN, PENGELOLAAN, LANJUTAN).
- Beranda: widget sapaan (teks), `StatsOverview` kustom, widget tabel "Perlu Tindakan Saya".
- Resource: form satu halaman; `Section::make('Isian tambahan')->collapsed()`; tabel ≤ 5 kolom default (sisanya `toggleable(isToggledHiddenByDefault: true)`), `paginated([10, 25])`; aksi dengan `->requiresConfirmation()` + teks §6.
- Navigasi: `shouldRegisterNavigation()` berbasis permission; lencana angka pada Surat Masuk (belum dibaca), Surat Internal, dan Persetujuan.
- Aset (font, ikon, logo) lokal lewat Vite. Catat lisensi font/ikon di `docs/LICENSES.md`.

## 10. Kriteria Uji Kemudahan (Antigravity & UAT)
Pengguna usia 50+ tanpa pelatihan harus bisa, **tanpa bantuan**:
1. Dari Beranda, membuka surat baru untuk dirinya dalam ≤ 2 klik.
2. Meneruskan surat masuk ke penerima dalam ≤ 4 klik.
3. Menyetujui surat dalam ≤ 3 klik.
4. Membuat surat keluar sederhana dengan satu form.
5. Memahami semua pesan error tanpa istilah teknis.
Lolos bila ≥ 4 dari 5 tugas selesai tanpa bantuan pada UAT dengan minimal 3 pengguna senior.
