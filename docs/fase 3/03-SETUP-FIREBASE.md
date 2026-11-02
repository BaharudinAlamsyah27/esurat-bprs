# Setup Firebase Cloud Messaging (FCM) untuk APK Android

Dokumen ini menyiapkan notifikasi disposisi ke APK Flutter. FCM hanya mengirim pesan umum dan ID tujuan; perihal, isi surat, nama pengirim, serta nama pegawai tidak dikirim ke Firebase. Saat notifikasi ditekan, aplikasi mengambil rincian dari API setelah pengguna masuk.

Server Laravel memakai HTTP v1 yang sudah tersedia, jadi tidak memerlukan paket Composer atau server Firebase terpisah.

## Yang perlu disiapkan Admin/IT

Untuk pengujian lokal, proyek Firebase pribadi boleh digunakan sementara. Pop-up FCM di HP sudah dikonfirmasi pengguna pada 2026-11-02 setelah ID proyek dan path kredensial lokal diperbaiki. Sebelum serah terima/penggunaan kantor, pindahkan ke proyek dan akun Google milik kantor.

1. Buat proyek di [Firebase Console](https://console.firebase.google.com/) menggunakan akun Google milik kantor, lalu tambahkan aplikasi Android dengan ID `id.bprssragen.esurat_mobile`. Pastikan Firebase Cloud Messaging API HTTP v1 diaktifkan.
2. Unduh `google-services.json` dan letakkan di `mobile_flutter/android/app/google-services.json`. Berkas ini sudah dikecualikan dari Git.
3. Di setelan proyek Firebase, buat service account dan unduh kunci JSON. Berikan service account peran **Firebase Cloud Messaging API Admin** pada proyek tersebut. Simpan berkas kunci JSON di server pada `storage/app/private/firebase-service-account.json`. Jangan taruh di `public/`, folder Flutter, atau Git.
4. Atur `.env` server:

   ```dotenv
   FCM_ENABLED=true
   FIREBASE_PROJECT_ID=id-proyek-firebase
   FIREBASE_CREDENTIALS=storage/app/private/firebase-service-account.json
   ```

   Akun service harus memiliki izin mengirim pesan Firebase Cloud Messaging. Server kantor perlu akses internet keluar ke layanan Google melalui HTTPS (port 443).

## Persiapan APK

Sebelum build pertama, pasang **NDK (Side by side) 26.1.10909125** melalui Android Studio → Tools → SDK Manager → SDK Tools → Show Package Details. Aplikasi menetapkan NDK tersebut di `mobile_flutter/android/app/build.gradle`. Versi Android minimum aplikasi adalah **Android 6.0 (API 23)** karena plugin Firebase Messaging memerlukannya.

Di Laragon, dari folder `mobile_flutter`, jalankan:

```powershell
flutter pub get
flutter analyze
flutter test
flutter build apk --release --dart-define=API_BASE_URL=http://IP-SERVER-KANTOR:8000/api
```

Ganti `IP-SERVER-KANTOR` dengan alamat server yang dapat dijangkau ponsel. Untuk rilis kantor, HTTPS sangat disarankan; alamat HTTP hanya untuk pengujian di jaringan lokal tepercaya. Ponsel harus memiliki internet dan Google Play Services agar FCM dapat menerima pesan.

## Uji alur notifikasi

1. Pasang APK hasil build pada ponsel Android dan masuk dengan akun User yang aktif serta `push_enabled` aktif.
2. Izinkan notifikasi saat diminta. Pastikan ponsel mendaftarkan token ke server tanpa menampilkan token pada layar atau log.
3. Tutup aplikasi atau pindah ke aplikasi lain. Sebagai Admin, buat/simpan disposisi untuk User tersebut.
4. Pastikan notifikasi umum “Anda menerima disposisi baru” muncul. Pesan tidak boleh menampilkan perihal atau identitas surat.
5. Tekan notifikasi. Aplikasi meminta login bila sesi sudah keluar, lalu membuka disposisi yang dituju dan mengambil detailnya dari API.
6. Logout dari APK, lalu kirim disposisi uji baru. Perangkat yang logout tidak boleh lagi menerima notifikasi untuk sesi itu.
7. Uji aplikasi dalam keadaan terbuka: aplikasi menangani pesan melalui notifikasi dalam aplikasi. Untuk melihat pop-up sistem, minimalkan aplikasi dengan tombol Home; jangan paksa berhenti aplikasi saat pengujian. Pada Android 13 ke atas, izin notifikasi harus diberikan.

## Biaya dan batasan

Firebase Cloud Messaging tercantum sebagai layanan tanpa biaya pada [paket Firebase Spark](https://firebase.google.com/pricing). Pengiriman tetap bergantung pada internet ponsel dan akses keluar server. Server perlu dapat mengakses `fcm.googleapis.com` dan `oauth2.googleapis.com` melalui HTTPS. Layanan ini bukan jaminan pesan selalu sampai seketika; daftar disposisi di aplikasi tetap menjadi sumber utama. Dengan `QUEUE_CONNECTION=sync`, pengiriman dilakukan dalam permintaan yang sama dan dapat menambah waktu simpan jika jaringan Google lambat; klien FCM memakai batas waktu agar permintaan tidak menggantung tanpa batas.

## Referensi resmi

- [Menyiapkan Firebase untuk Flutter](https://firebase.google.com/docs/flutter/setup)
- [Menerima pesan FCM di Flutter](https://firebase.google.com/docs/cloud-messaging/flutter/receive-messages)
- [Mengirim FCM HTTP v1 dari server](https://firebase.google.com/docs/cloud-messaging/send/v1-api)

## Keputusan scope

Pada 2026-11-02 pengguna menyetujui FCM sebagai tambahan terbatas Fase 3 untuk pemberitahuan disposisi. Cakupan ini tidak mencakup pengingat, pelacakan berjenjang, notifikasi surat lain, atau pengiriman data isi surat ke Firebase.
