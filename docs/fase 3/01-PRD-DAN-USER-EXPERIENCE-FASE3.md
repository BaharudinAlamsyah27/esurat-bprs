# 01 — PRD & Panduan User Experience (UX) Fase 3

> Proyek: **E-Surat BPRS Sragen**  
> Dokumen: **Product Requirement Document (PRD) & Standar UX Mobile Hybrid**

---

## 1. Persona Pengguna

1. **Admin / Sekretariat (Pengguna Laptop/PC Utama):**
   - Mengelola master data, membuat akun pegawai baru, mencatat surat masuk fisik yang di-scan, menyusun draf surat keluar, dan mencetak lembar disposisi kertas jika diperlukan.
   - Menggunakan layar monitor lebar dan keyboard fisik untuk input data cepat.
2. **Pimpinan / Direksi (Pengguna Laptop & HP):**
   - Meninjau surat masuk, memberikan arahan disposisi, menunjuk beberapa pegawai sekaligus, dan memantau status penyelesaian instruksi secara *real-time*.
3. **Pegawai Pelaksana / Staf Cabang (Pengguna HP Utama):**
   - Sebagian berusia senior (butuh huruf jelas dan tombol besar).
   - Menerima notifikasi di saku HP mereka saat ada arahan baru, membaca perihal surat dan lampiran PDF, serta melapor bahwa tugas telah selesai dilaksanakan.

---

## 2. Alur Hidup Disposisi Digital (End-to-End Lifecycle)

```
[ PIMPINAN / ADMIN ]
       │
       ├─► 1. Buka Surat Masuk -> Klik "Disposisi"
       ├─► 2. Pilih satu atau banyak pegawai penerima (Multi-Select)
       ├─► 3. Isi sifat surat, instruksi, dan catatan -> Klik "Simpan"
       │
       ▼
[ SISTEM BACKEND ]
       │
       ├─► 4. Simpan record Disposisi & Sinkronkan baris penerima di database
       ├─► 5. Buat notifikasi in-app (lonceng web)
       ├─► 6. Kirim Push Notification FCM ke token HP penerima
       │
       ▼
[ HP PEGAWAI (APK ANDROID / PWA IPHONE) ]
       │
       ├─► 7. HP bergetar/berbunyi di status bar: "E-Surat BPRS: Anda menerima disposisi baru"
       ├─► 8. Pegawai menyentuh notifikasi -> APK langsung membuka detail disposisi
       ├─► 9. Sistem otomatis mencatat status "DIBACA" beserta timestamp jam bacanya
       ├─► 10. Pegawai meninjau isi surat & membuka berkas scan PDF
       ├─► 11. Tugas selesai -> Pegawai klik tombol hijau "Tandai Selesai"
       ├─► 12. Pegawai mengisi catatan tindak lanjut singkat (maks 255 karakter) -> Klik "Simpan"
       │
       ▼
[ FEEDBACK KE PIMPINAN ]
       │
       └─► 13. Status berubah menjadi hijau (Selesai); Pimpinan menerima notifikasi balik
```

---

## 3. Cerita Pengguna & Kriteria Penerimaan (Acceptance Criteria)

### A. Alur Disposisi Multi-Penerima
* **US-01:** Pimpinan/Admin dapat memilih lebih dari satu pegawai penerima dalam satu lembar disposisi.
  * *Kriteria:* Pilihan berbentuk dropdown pencarian nama lengkap dan jabatan. Sistem mencegah pemilihan nama ganda. Jika ada pihak eksternal, dapat dicatat di kolom "Penerima Lainnya".
* **US-02:** Setiap penerima memiliki status tugas independen.
  * *Kriteria:* Jika Pimpinan menunjuk Pegawai A dan Pegawai B, ketika Pegawai A menyelesaikan tugasnya, status Pegawai B tetap berjalan mandiri sampai diselesaikan oleh Pegawai B sendiri.
* **US-03:** Pelacakan otomatis saat dibaca.
  * *Kriteria:* Pertama kali pegawai membuka halaman detail disposisi, sistem langsung mengubah status dari `Baru` menjadi `Dibaca` dan merekam waktu `read_at`.

### B. Penyelesaian Tugas oleh Pegawai
* **US-04:** Tombol "Tandai Selesai" dengan input catatan.
  * *Kriteria:* Tombol centang hijau membuka modal formulir sederhana. Pegawai wajib/dapat mengisi ringkasan laporan hasil pelaksanaan (misal: *"Dana telah ditransfer ke rekening nasabah"*).
  * *Kriteria:* Waktu penyelesaian `done_at` dan catatan `done_note` tersimpan permanen di database dan muncul pada rekap pimpinan.

### C. Pengalaman Pengguna di HP (Mobile UX)
* **US-05:** Tampilan Layar Penuh (Fullscreen Standalone).
  * *Kriteria:* Baik dibuka melalui APK Android maupun ikon PWA iPhone, aplikasi terbuka bersih tanpa bilah URL / address bar browser.
* **US-06:** Penyesuaian Elemen Ramah Pengguna Senior (Lansia).
  * *Kriteria:* Ukuran huruf minimal 16px–18px, kontras warna tajam sesuai pedoman BPRS Sragen, target sentuh tombol minimal 48px, dan tidak ada tabel yang terpotong secara horizontal di layar HP.
* **US-07:** Penanganan Sambungan Terputus (Offline Friendly).
  * *Kriteria:* Jika HP pegawai berada di luar jangkauan Wi-Fi kantor atau VPN belum aktif, layar HP menampilkan pesan informatif ramah:  
    *"Tidak dapat terhubung ke jaringan kantor. Pastikan Wi-Fi kantor atau VPN Anda sudah aktif, lalu tekan Coba Lagi."*

---

## 4. Matriks Hak Akses Halaman Mobile

| Halaman / Fitur | Role Admin | Role Pegawai Biasa (User) |
|---|---|---|
| **Dashboard** | Lengkap (Semua Kartu Ringkasan + Grafik) | Terbatas (Hanya surat & disposisi miliknya) |
| **Surat Masuk** | Lihat semua + Tambah + Edit + Disposisi + Hapus | Hanya lihat surat terkait |
| **Surat Keluar** | Lihat semua + Buat Surat + Edit + Hapus | Lihat & buat draf surat |
| **Disposisi Saya** | Melihat seluruh disposisi semua pegawai | **Hanya melihat disposisi miliknya sendiri** |
| **Tandai Selesai** | Bisa membantu menandai selesai bila perlu | Menandai selesai tugas miliknya |
| **Master Data** | Terbuka penuh (Instansi, Klasifikasi, Pengguna) | **Otomatis disembunyikan total** |
| **Perangkat Terdaftar**| Memantau & mencabut token HP pegawai | Tersembunyi |
| **Cetak PDF Lembar Disposisi** | Tersedia (1 klik cetak A4) | Tersedia untuk dibaca/diunduh |
