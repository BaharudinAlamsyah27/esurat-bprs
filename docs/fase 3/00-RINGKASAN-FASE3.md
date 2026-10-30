# 00 — Ringkasan Fase 3: Ekosistem Hybrid Mobile (APK) & Web E-Surat

> Proyek: **E-Surat BPRS Sragen** (Bank Pembiayaan Rakyat Syariah)  
> Dokumen Acuan: **Fase 3 — Arsitektur Hybrid Terpadu (Web Desktop + Mobile APK Android & PWA)**  
> Tanggal Rilis: **27 Oktober 2026**

---

## 1. Pendahuluan & Latar Belakang

Pada operasional kantor BPRS Sragen, pengelolaan surat menyurat memiliki dua karakteristik pengguna yang berbeda:
1. **Staf Sekretariat & Administrator:** Memerlukan perangkat komputer/laptop dengan layar lebar untuk registrasi surat masuk, pengetikan draf surat keluar, pemindaian dokumen fisik resolusi tinggi, serta pencetakan lembar disposisi dan laporan A4 ke printer kantor.
2. **Pimpinan & Pegawai Pelaksana:** Memerlukan kecepatan respon dan mobilitas tinggi. Disposisi atau instruksi dinas harus sampai langsung ke saku mereka (smartphone) secara *real-time* tanpa harus membuka laptop atau menunggu lembar kertas fisik beredar.

Berdasarkan kesepakatan strategi pengembangan sistem untuk **Solo IT** (satu-satunya personel IT di kantor), diputuskan menggunakan pendekatan **Arsitektur Hybrid Terpadu**:
* **Backend & Web Core:** Tetap berbasis **Laravel 12 + Filament 3 + MySQL 8**, menjadi pusat kendali data tunggal (*single source of truth*).
* **Aplikasi Mobile (Android APK):** Dibungkus menggunakan **Capacitor** menjadi file installer `.apk` resmi kantor yang dapat diinstal di HP pegawai, dilengkapi push notification (Google FCM HTTP v1) saat layar HP mati, serta layar offline ramah pengguna.
* **Aplikasi Mobile (iOS / iPhone):** Dilengkapi konfigurasi **Progressive Web App (PWA)** dengan manifest dan meta tag mandiri sehingga dapat dibuka layar penuh (*standalone*) via Safari tanpa bilah alamat browser.

---

## 2. Matriks Keputusan Arsitektur

| Aspek | Pilihan Arsitektur | Alasan & Manfaat untuk Kantor |
|---|---|---|
| **Pondasi Sistem** | Laravel 12 + Filament 3 + MySQL 8 | Basis Fase 1 & 2 telah stabil, teruji, dan lulus 56 test otomatis (276 assertion). |
| **Metode Mobile Android** | Hybrid Container (Capacitor WebView) | Pegawai mendapatkan file `.apk` sungguhan. Beban pemeliharaan tetap ringan (1 basis kode). |
| **Pembaruan Sistem** | Hot-Update Otomatis via Server | Perubahan format, teks, atau penambahan fitur di server langsung terlihat di HP tanpa perlu instal ulang APK. |
| **Push Notification** | Google Firebase Cloud Messaging (FCM) HTTP v1 | Notifikasi tetap berbunyi/bergetar di HP meskipun aplikasi ditutup atau layar terkunci. |
| **Distribusi Aplikasi** | Sideload APK Internal (APK Kantor) | Bebas biaya langganan Google Play Console, aman, khusus internal bank. |
| **Akses Jaringan** | On-Premise (Wi-Fi Kantor / VPN) | Keamanan data perbankan terjaga; server kantor tidak perlu dibuka publik sembarangan. |
| **Dukungan iPhone** | Standalone PWA (Add to Home Screen) | Berfungsi fullscreen layaknya aplikasi tanpa biaya langganan Apple Developer ($99/thn). |

---

## 3. Matriks Pengalaman Pengguna (User Experience)

### A. Tampilan untuk Akun Admin
* **Di Laptop/PC (Browser):** Menampilkan antarmuka desktop penuh: sidebar kiri selalu terbuka, tabel data lebar dengan tombol aksi di kanan, filter tahun, pratinjau PDF, dan grafik dashboard 6 bulan.
* **Di HP Android (APK) / iPhone (PWA):** Hak akses Admin tetap 100% aktif. Seluruh menu (Data Master, Pengguna, Surat Masuk, Surat Keluar, Disposisi, Laporan) tersedia di balik tombol menu navigasi (ikon hamburger). Layout kartu tersusun rapi secara vertikal yang ramah jempol.

### B. Tampilan untuk Akun Pegawai Biasa (User)
* **Di Laptop/PC:** Menu terbatas khusus tugas kerja (Surat Masuk, Surat Keluar, Disposisi Saya, Laporan, Profil).
* **Di HP Android (APK):** Fokus utama pada menu **"Disposisi Saya"**. Begitu notifikasi disentuh di HP, aplikasi langsung terbuka ke detail lembar disposisi. Pegawai dapat membaca surat, mengunduh file lampiran, dan menekan tombol hijau **"Tandai Selesai"** dengan catatan tindak lanjut.

---

## 4. Keamanan & Kebijakan Data Perbankan

1. **Privasi Notifikasi Layar Kunci:**  
   Isi notifikasi push di layar HP **dilarang membocorkan data sensitif**.  
   *Judul:* `E-Surat BPRS`  
   *Isi:* `Anda menerima disposisi baru` (tanpa nomor surat, perihal, atau nama pimpinan). Perihal hanya terbaca setelah pegawai login di dalam aplikasi.
2. **Pencabutan Token Perangkat Saat Logout:**  
   Ketika pegawai menekan tombol "Keluar" (Logout) di HP, token perangkat HP tersebut otomatis dihapus dari database server untuk mencegah notifikasi masuk jika HP dipinjamkan.
3. **Penyimpanan Berkas Privat:**  
   Seluruh file scan surat dan lampiran tersimpan di disk privat server (`storage/app/private/`). Berkas tidak dapat diakses publik tanpa sesi login aktif.
4. **Isolasi Hak Akses (Row-Level Authorization):**  
   Pegawai biasa hanya dapat melihat disposisi yang ditugaskan secara eksplisit ke akun mereka (`user_id`). Data disposisi pegawai lain tidak akan pernah bocor di API maupun tampilan.

---

## 5. Ringkasan File & Lokasi Kode Terkait

- **Konfigurasi Mobile Android:** [`mobile/capacitor.config.json`](file:///c:/laragon/www/esurat/mobile/capacitor.config.json) & [`mobile/package.json`](file:///c:/laragon/www/esurat/mobile/package.json)
- **Layar Offline HP:** [`mobile/www/offline.html`](file:///c:/laragon/www/esurat/mobile/www/offline.html)
- **Jembatan WebView:** [`resources/js/mobile-bridge.js`](file:///c:/laragon/www/esurat/resources/js/mobile-bridge.js)
- **Layanan Push FCM:** [`app/Services/Push/FcmClient.php`](file:///c:/laragon/www/esurat/app/Services/Push/FcmClient.php) & [`app/Notifications/Channels/FcmChannel.php`](file:///c:/laragon/www/esurat/app/Notifications/Channels/FcmChannel.php)
- **Tindak Lanjut Disposisi:** [`app/Filament/Resources/MyDispositionResource.php`](file:///c:/laragon/www/esurat/app/Filament/Resources/MyDispositionResource.php) & [`app/Actions/CompleteDispositionRecipient.php`](file:///c:/laragon/www/esurat/app/Actions/CompleteDispositionRecipient.php)
- **Manifest PWA:** [`public/manifest.json`](file:///c:/laragon/www/esurat/public/manifest.json)
