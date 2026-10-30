# 02 — Arsitektur, Data, API (Fase 2)

## 1. Gambaran
```
[HP Android: APK "bingkai" (Capacitor)] --HTTPS (Wi-Fi kantor/VPN)--> [Server E-Surat: Laravel+Filament+MySQL]
        ^                                                                   |
        |  push (FCM)                                                       | queue:work
        +------------------------- [Google FCM] <--HTTPS 443 (keluar)------+
```
- Web E-Surat tetap satu basis kode. APK hanya membuka URL produksi (WebView) + menerima push.
- Pengiriman push dilakukan server lewat **antrean database** (`queue:work` dijalankan Supervisor di produksi).
- Jalur notifikasi tidak mensyaratkan server terbuka ke internet; membuka surat mensyaratkan HP menjangkau server (Wi-Fi kantor/VPN).

## 2. Perubahan Database (migration BARU; jangan menyentuh tabel Fase 1 secara destruktif)
**users** (tambah): `position_title` string(100) null (jabatan, ditampilkan di pemilih & cetakan), `push_enabled` boolean default true.
**disposition_recipients**: id, `disposition_id` FK→dispositions (cascade), `user_id` FK→users, `status` string default `new` (enum `DispositionRecipientStatus`: `new`,`read`,`done`), `read_at` null, `done_at` null, `done_note` string(255) null, `notified_at` null, timestamps. **Unik**(disposition_id, user_id). Index (user_id, status).
**device_tokens**: id, `user_id` FK→users (cascade), `token` string(512) **unik**, `platform` string(20) default `android`, `app_version` string(20) null, `device_name` string(100) null, `session_id` string(100) null, `last_used_at` timestamp null, timestamps. Index user_id.
**notifications**: tabel standar Laravel (`php artisan notifications:table`) untuk lonceng.
**jobs / failed_jobs**: tabel standar (sudah ada bawaan Laravel; pastikan termigrasi).
`dispositions.forwarded_to` (JSON teks) **dipertahankan**: diisi otomatis dari nama penerima terpilih (+ jabatan) ditambah baris "tujuan lain" bila ada; dipakai untuk cetakan PDF dan data lama.

## 3. Kelas Utama (nama saran)
- `App\Enums\DispositionRecipientStatus`
- `App\Models\{DispositionRecipient, DeviceToken}`
- `App\Actions\{SaveDisposition (diperbarui: sync penerima), MarkDispositionRead, CompleteDispositionRecipient, RegisterDeviceToken, RevokeDeviceToken}`
- `App\Notifications\{DispositionAssigned, DispositionCompleted}` — saluran `database` + `App\Notifications\Channels\FcmChannel`
- `App\Services\Push\{FcmClient (HTTP v1), PushMessageBuilder}`; **kegagalan push tidak melempar ke alur bisnis** (ditangkap, dicatat, dicoba ulang oleh queue)
- `App\Listeners\RemoveDeviceTokensOnLogout` (event `Illuminate\Auth\Events\Logout`) → hapus token dengan `session_id` sesi itu
- Filament: `MyDispositionsResource` (menu "Disposisi Saya"), tabel penerima pada detail disposisi, halaman Admin `RegisteredDevicesPage`, `databaseNotifications()` pada panel (polling 30 dtk)
- Helper `isMobileApp(): bool` → `str_contains(request()->userAgent(), 'ESuratApp/')`
- JS jembatan `resources/js/mobile-bridge.js` (dimuat hanya jika `isMobileApp()`)

## 4. Endpoint (sesi cookie + CSRF, middleware `auth`, `throttle:30,1`)
| Metode | Path | Fungsi |
|---|---|---|
| POST | `/device-tokens` | body: `token`, `platform`, `app_version`, `device_name` → upsert milik pengguna login; simpan `session_id`. Jika token sudah ada di akun lain → **pindahkan** ke akun ini |
| DELETE | `/device-tokens` | body: `token` → hapus (dipakai sebelum logout atau saat toggle "Terima notifikasi di HP" dimatikan) |
Rute Filament: `/disposisi-saya` (daftar) dan `/disposisi-saya/{id}` (detail; membuka = tandai dibaca). Semua dilindungi Policy: pengguna hanya melihat disposisi yang ia terima; Admin melihat semua.

## 5. Alur Teknis Notifikasi
1. Admin menyimpan disposisi → `SaveDisposition` menyinkronkan penerima → untuk **penerima baru** dikirim `DispositionAssigned` (queued).
2. `database` channel menulis ke `notifications` (lonceng). `FcmChannel` mengambil semua `device_tokens` pengguna (jika `push_enabled` dan akun aktif) lalu memanggil FCM HTTP v1.
3. Payload: `notification: {title: "E-Surat BPRS", body: "Anda menerima disposisi baru"}`, `data: {type: "disposition", path: "/disposisi-saya/{id}"}`, Android: `channel_id: "disposisi"`, `priority: "high"`, `ttl: 86400s`. **Dilarang** memasukkan perihal/pengirim/nama.
4. Respons FCM `UNREGISTERED`/`INVALID_ARGUMENT` → hapus token. Galat sementara → biarkan queue mengulang (3×, backoff 10/60/300 dtk).
5. Menekan notifikasi → aplikasi menavigasi WebView ke `APP_URL + path` (path divalidasi: harus berawalan `/disposisi-saya/`; tolak lainnya agar tidak jadi open-redirect). Jika sesi habis → halaman login, lalu kembali ke tujuan (`intended`).
6. Penerima menyelesaikan → `DispositionCompleted` ke pembuat disposisi (+ lonceng & push).

## 6. Sisi Aplikasi Android (`mobile/`)
- **Capacitor (Android)**: `appId` default `id.bprssragen.esurat`, `appName` "E-Surat BPRS Sragen". Mode **URL jarak jauh** (`server.url = APP_URL`), `allowNavigation` hanya host E-Surat, `cleartext` hanya untuk build debug.
- Plugin: `@capacitor/push-notifications`, `@capacitor/splash-screen`, `@capacitor/app` (tombol kembali), serta plugin berkas/berbagi (filesystem + share atau pembuka berkas) bila diperlukan untuk PDF.
- User-Agent ditambah `ESuratApp/<versi>` (opsi `appendUserAgent` Capacitor).
- `www/offline.html` lokal untuk halaman gagal terhubung (teks di doc 03).
- Saluran notifikasi Android `disposisi` (importance tinggi). Izin `POST_NOTIFICATIONS` (Android 13+) diminta setelah penjelasan ramah.
- **Spike S0 wajib memverifikasi** bahwa jembatan plugin tersedia pada halaman jarak jauh (`window.Capacitor` pada domain E-Surat). Bila tidak berhasil/andal → **cadangan:** aplikasi Android native Kotlin minimal (WebView + FirebaseMessagingService + antarmuka JS `getFcmToken()`), dicatat di `DECISIONS.md`.
- Keamanan rilis: tanpa cleartext, `android:allowBackup="false"`, WebView debugging mati, penandatanganan dengan keystore (jangan di repo), opsi `FLAG_SECURE` dan kunci aplikasi sebagai konfigurasi (default sesuai keputusan IT).

## 7. Konfigurasi (`.env`; JANGAN commit nilai rahasia)
```
APP_URL=https://esurat.<domain-kantor>
QUEUE_CONNECTION=database
FCM_ENABLED=false            # true setelah kredensial Firebase terpasang
FIREBASE_PROJECT_ID=
FIREBASE_CREDENTIALS=storage/app/private/firebase-service-account.json   # di luar public/, gitignore
SESSION_LIFETIME=30
SESSION_SECURE_COOKIE=true   # produksi (HTTPS)
```
Bila `FCM_ENABLED=false` atau kredensial tak ada: `FcmChannel` tidak melakukan apa-apa (log info) sehingga web tetap berfungsi di pengembangan.

## 8. Keamanan Tambahan
- Pastikan route cetak/berkas tetap berautentikasi (Fase 1) dan bekerja di WebView (cookie sesi).
- Endpoint token: validasi panjang/format, throttle, hanya milik pengguna login.
- Log tidak memuat token penuh (potong/hash), tidak memuat isi surat.
- Audit: `device.registered`, `device.revoked`, `disposition.read`, `disposition.done` (tanpa catatan isi penuh; cukup ID).
