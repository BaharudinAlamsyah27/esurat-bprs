# Keputusan Proyek

## 2026-11-02 — Notifikasi FCM terbatas untuk disposisi APK

- Pengguna menyetujui penambahan Firebase Cloud Messaging (FCM) sebagai tambahan terbatas Fase 3.
- FCM hanya memberi tahu penerima disposisi di APK Android dan membuka disposisi terkait setelah aplikasi mengambil data melalui API.
- Isi pesan bersifat umum; perihal, isi surat, nama pengirim, dan nama pegawai tidak dimasukkan ke payload Firebase.
- Cakupan tidak termasuk pengingat, notifikasi untuk surat lain, atau alur disposisi baru.
- Kredensial Firebase disimpan di server, di luar folder publik dan Git. Fitur tetap nonaktif sampai Admin/IT memasang kredensial dan mengaktifkan `FCM_ENABLED`.
