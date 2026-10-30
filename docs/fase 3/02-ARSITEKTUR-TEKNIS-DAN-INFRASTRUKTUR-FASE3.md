# 02 — Arsitektur Teknis, Jaringan & Panduan Build APK Fase 3

> Proyek: **E-Surat BPRS Sragen**  
> Dokumen: **Arsitektur Teknis, Topologi Infrastruktur On-Premise & SOP Build APK**

---

## 1. Topologi Jaringan On-Premise

Server E-Surat ditempatkan di rak server kantor BPRS Sragen. Karena data perbankan bersifat rahasia, server **tidak perlu dibuka ke internet publik sembarangan**.

```
                           [ JARINGAN INTERNET ]
                                     ▲
                                     │ (Outbound HTTPS Port 443)
                                     ▼
                       [ Google Firebase FCM Gateway ]
                                     ▲
                                     │ Push Notifikasi ke HP
                                     ▼
[ HP ANDROID / IPHONE ] <────────────┘
        │
        ├─► JALUR 1 (Di Dalam Kantor): Terhubung langsung via Wi-Fi Kantor (LAN IP: 192.168.x.x)
        │
        └─► JALUR 2 (Di Luar Kantor): Terhubung aman via VPN Kantor / IP Publik Reverse Proxy
                                     │
                                     ▼
                         [ FIREWALL / ROUTER KANTOR ]
                                     │
                                     ▼
                         [ SERVER E-SURAT ON-PREMISE ]
                         - OS: Ubuntu Linux (Produksi) / Laragon (Dev)
                         - Web Server: Nginx / Apache
                         - PHP 8.2 / 8.4 + Laravel 12 + MySQL 8
                         - Supervisor: php artisan queue:work (Kirim Push)
```

### Prinsip Aliran Data:
1. **Pemicu Notifikasi:** Server kantor hanya membutuhkan koneksi keluar (*outbound internet*) menuju Google FCM untuk memicu HP pegawai bergetar.
2. **Pembacaan Surat:** Saat pegawai menyentuh notifikasi di HP, aplikasi membuka alamat server kantor. Jika pegawai berada di kantor, otomatis tersambung via Wi-Fi kantor. Jika di luar kantor, cukup nyalakan VPN kantor.

---

## 2. Struktur Skema Database Utama

### Tabel `disposition_recipients`
Menyimpan tugas disposisi per masing-masing pegawai penerima:
* `id` (bigint, primary key)
* `disposition_id` (foreign key ke tabel `dispositions`, onDelete cascade)
* `user_id` (foreign key ke tabel `users`, penerima tugas)
* `status` (varchar: `new`, `pending`, `read`, `in_progress`, `done`)
* `read_at` (timestamp, jam saat pertama kali dibaca)
* `done_at` (timestamp, jam saat ditandai selesai)
* `done_note` (varchar 255, catatan tindak lanjut pegawai)
* `notified_at` (timestamp, jam notifikasi terkirim)
* `created_at`, `updated_at`

### Tabel `device_tokens`
Menyimpan token Firebase untuk pengiriman push notifikasi ke HP:
* `id` (bigint, primary key)
* `user_id` (foreign key ke `users`)
* `token` (varchar 512, unik per perangkat)
* `platform` (varchar: `android`, `ios`)
* `app_version` (varchar: versi rilis APK, misal `1.0.0`)
* `device_name` (varchar: merk/tipe HP)
* `session_id` (varchar: ID sesi login aktif)
* `last_used_at` (timestamp)

---

## 3. Spesifikasi Endpoint Manajemen Perangkat

Semua endpoint dilindungi middleware autentikasi sesi dan proteksi CSRF:

| Metode | Endpoint | Parameter Body | Fungsi |
|---|---|---|---|
| `POST` | `/device-tokens` | `token`, `platform`, `app_version`, `device_name` | Mendaftarkan token HP saat pegawai berhasil login. Jika token sudah pernah dipakai akun lain di HP itu, otomatis dialihkan ke akun saat ini. |
| `DELETE` | `/device-tokens` | `token` | Menghapus token dari server saat pegawai menekan tombol **Logout** atau mematikan toggle notifikasi di profil. |

---

## 4. Konfigurasi Pembungkus Mobile Android (`mobile/`)

Sistem menggunakan **Capacitor 6** yang terletak di direktori [`mobile/`](file:///c:/laragon/www/esurat/mobile/):

### Berkas `mobile/capacitor.config.json`
```json
{
  "appId": "id.bprssragen.esurat",
  "appName": "E-Surat BPRS Sragen",
  "webDir": "www",
  "bundledWebRuntime": false,
  "server": {
    "url": "http://192.168.10.199:8080",
    "cleartext": true,
    "androidScheme": "http"
  },
  "plugins": {
    "PushNotifications": {
      "presentationOptions": ["badge", "sound", "alert"]
    },
    "SplashScreen": {
      "launchShowDuration": 2000,
      "backgroundColor": "#ffffff"
    }
  }
}
```
*(Catatan: Nilai `server.url` disesuaikan dengan IP server kantor produksi saat rilis final).*

### Berkas Jembatan WebView (`resources/js/mobile-bridge.js`)
* Otomatis meminta izin notifikasi (*permission request*) secara ramah.
* Mendaftarkan token perangkat HP ke endpoint `/device-tokens`.
* Mengatur penanganan tombol fisik kembali (*hardware back button*) pada HP Android:
  - Jika di sub-halaman: kembali ke halaman sebelumnya.
  - Jika di halaman utama: meminta konfirmasi atau tekan 2x untuk keluar.
* Menangani klik notifikasi push (*deep linking*) langsung membuka halaman detail `/disposisi-saya/{id}`.

---

## 5. Prosedur Operasional Standar (SOP) Build File APK

Sebagai panduan langkah demi langkah bagi tim IT untuk menghasilkan file `app-release.apk`:

### Langkah 1: Persiapan Lingkungan di PC
Pastikan di PC IT telah terpasang:
1. **Node.js LTS** (v18 atau v20).
2. **Android Studio** (versi Ladybug / Flamingo atau yang terbaru) beserta Android SDK API 34.

### Langkah 2: Sinkronisasi Dependensi
Buka terminal di folder proyek:
```bash
cd mobile
npm install
```

### Langkah 3: Inisialisasi Proyek Android
Jalankan perintah penambahan platform Android (hanya dilakukan sekali):
```bash
npx cap add android
```
Kemudian lakukan sinkronisasi setiap ada perubahan konfigurasi:
```bash
npx cap sync android
```

### Langkah 4: Menghasilkan File APK di Android Studio
Buka Android Studio melalui terminal:
```bash
npx cap open android
```
Setelah Android Studio terbuka dan proses Gradle selesai:
1. Klik menu bar: **Build** $\rightarrow$ **Build Bundle(s) / APK(s)** $\rightarrow$ **Build APK(s)**.
2. Tunggu proses kompilasi sekitar 1–2 menit.
3. Di pojok kanan bawah akan muncul notifikasi sukses: *"APK(s) generated successfully"*.
4. Klik tautan **locate** untuk mengambil file APK hasil kompilasi.

### Langkah 5: Distribusi Internal ke Pegawai
Kirim file `.apk` tersebut ke grup WhatsApp internal kantor atau portal intranet BPRS Sragen. Pegawai cukup mengunduh dan menyentuh file tersebut di HP Android untuk langsung menginstalnya.
