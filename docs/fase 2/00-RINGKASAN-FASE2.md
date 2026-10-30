# 00 — Ringkasan Fase 2: Disposisi Digital + Aplikasi Android

> Dokumen induk Fase 2. Antigravity TIDAK memiliki riwayat obrolan sebelumnya; semua konteks penting ada di sini. **Jika dokumen di `docs/fase2/` bertentangan dengan `docs/01`–`docs/07` (Fase 1), `docs/fase2/` yang menang.**

## 1. Tujuan Fase 2
Mentor meminta disposisi dapat sampai **langsung ke HP pegawai sebagai notifikasi**, tanpa pegawai harus membuka laptop/browser setiap kali. Fase 2 mewujudkannya dengan:
1. **Disposisi digital sederhana:** penerima disposisi adalah akun pengguna; ada status dibaca/selesai.
2. **Notifikasi:** lonceng di dalam aplikasi + push notification ke HP Android (Firebase FCM).
3. **Aplikasi Android (APK) berupa "bingkai":** membuka web E-Surat yang sama layar penuh (bukan Chrome), menerima notifikasi, membuka PDF/unggah foto.
4. **Tampilan responsif HP** agar nyaman dipakai di layar kecil.

## 2. Latar Belakang Singkat
- **Proyek:** E-Surat BPRS Sragen — pencatatan surat masuk & keluar (Bank Pembiayaan Rakyat Syariah). Dijalankan **on-premise** (server kantor), bukan cloud. Pengguna banyak yang **berusia lanjut** → kemudahan pakai prioritas utama.
- **Fase 1 (v2) SELESAI** (laporan 27 Okt 2026): M0–M6 lulus `composer ci` (41 test / 211 assertion). Modul: Data Klasifikasi, Data Instansi (satu data), Data Pengguna, Surat Masuk, Surat Keluar, Lembar Disposisi cetak (PDF A4), Laporan (cetak/PDF/Excel), Dashboard (kartu + 2 grafik), Profil, Riwayat Aktivitas. Dua level: `admin` dan `user`.
- **Menunggu validasi manusia (Fase 1):** UAT ≥3 pegawai senior, uji beban 10.000 data, uji backup/restore, data instansi resmi, merge `feature/m6-dashboard` ke `develop`, deploy ke server produksi.
- **Tim:** Product Owner (pemula teknis, Windows + Laragon), Claude (PM/UI-UX/arsitek, hanya lewat obrolan), **Antigravity (programmer utama + QA)**, tim IT BPRS (server, jaringan, VPN), mentor.

## 3. Keputusan yang Sudah Diambil (jangan dibuka ulang tanpa alasan)
| # | Keputusan | Alasan |
|---|---|---|
| 1 | Stack tetap: PHP 8.2 (kode kompatibel 8.2), Laravel 12.x, Filament 3.3.x (yang terpasang), MySQL 8, Pest ^3, Pint, Larastan | Fase 1 sudah jalan dan lulus CI |
| 2 | Desain orisinal BPRS Sragen: latar netral abu terang, kartu putih, **satu warna aksen** (bawaan Hijau BPRS; pilihan tema Biru/Marun/Teal oleh Admin), huruf 18px, tombol ≥ 48px, tabel: data kiri + aksi (Lihat/Ubah) kanan, **Hapus hanya di halaman Ubah** | Profesional untuk bank, mudah untuk lansia |
| 3 | DILARANG menyalin tampilan/kode/logo/teks aplikasi pihak lain | Hak cipta |
| 4 | Jalur HP = **APK pembungkus (Capacitor) + notifikasi FCM** (Opsi 2). Bukan aplikasi native penuh | Kode web tetap satu; biaya sedang; notifikasi andal |
| 5 | **Android saja** (APK). iPhone di luar lingkup | Permintaan mentor menyebut APK |
| 6 | Server tetap on-premise. HP menjangkau server lewat **Wi-Fi kantor atau VPN** (VPN diurus tim IT BPRS, **tidak** dimasukkan ke dalam APK) | Keamanan bank |
| 7 | Server butuh **akses internet keluar** (HTTPS 443 ke Google) untuk mengirim FCM; server tidak perlu dibuka ke internet untuk notifikasi | Prinsip minimal |
| 8 | **Isi notifikasi minimal:** "Anda menerima disposisi baru" — tanpa perihal/pengirim/nama | Data bank |
| 9 | Logout = notifikasi berhenti untuk akun itu di HP tersebut | Aman bila HP dipinjam |
| 10 | Aplikasi dibuka dari ikonnya sendiri, tampil layar penuh di HP (bukan Chrome) | Pengalaman seperti aplikasi biasa |
| 11 | Distribusi APK internal (bukan Play Store publik) | Aplikasi internal bank |
| 12 | Tidak ada cPanel; tidak ada aplikasi superadmin terpisah. Admin mengelola pegawai di dalam aplikasi; IT BPRS mengelola server (Ubuntu) | Lihat dokumen 07 |
| 13 | Tanggapan Claude (PM) kepada Product Owner memakai bahasa sederhana; perintah siap salin | Pemilik proyek pemula |

## 4. Scope Fase 2
**DIKERJAKAN (IN)**
- Disposisi digital sederhana: penerima = akun; status `baru → dibaca → selesai` per penerima; menu "Disposisi Saya"; Admin melihat status tiap penerima.
- Notifikasi in-app (lonceng) + push FCM (Android) lewat antrean (queue); pendaftaran/pencabutan token perangkat; halaman Admin "Perangkat Terdaftar".
- Penyesuaian tampilan HP (responsif) untuk seluruh halaman utama.
- Aplikasi Android (`mobile/`, Capacitor): layar pembuka, WebView ke web E-Surat, push, tautan dalam (deep link), unggah foto/berkas, buka/bagikan PDF, halaman error offline.
- Penguatan keamanan & panduan jaringan/deploy; UAT dan rilis APK internal.

**DITUNDA / DI LUAR LINGKUP (jangan dibuat)**
Aplikasi iOS · aplikasi native penuh · notifikasi WhatsApp/Telegram/email · batas waktu & pengingat otomatis disposisi · disposisi berjenjang/sub-disposisi · persetujuan berjenjang · tanda tangan digital & QR · surat internal/nota dinas/edaran · delegasi · kerahasiaan per surat · OCR · enkripsi file · mode offline · integrasi core banking · VPN di dalam aplikasi · publikasi Play Store publik.

## 5. Aturan Kerja dengan Antigravity
- Satu milestone per sesi; urutan di `04-ROADMAP-FASE2.md`. Prompt siap pakai di `05-PROMPT-ANTIGRAVITY.md`.
- Antigravity boleh menjalankan terminal (composer/npm/artisan/test) dan `git add/commit` pada branch fitur. **Dilarang tanpa izin eksplisit:** `git push`, `git reset --hard`, force, hapus branch, `migrate:fresh`, `db:wipe`, mengirim data ke layanan luar selain Firebase.
- Langkah yang HARUS dilakukan manusia (Android Studio, konsol Firebase, memasang APK di HP, konfigurasi VPN/firewall) ditulis sebagai **daftar bernomor + perintah siap salin + keterangan di mana mengetiknya**. Lihat `06-` dan `07-`.
- Verifikasi tampilan dengan browser bawaan pada lebar 360 / 390 / 412 / 768 / 1280 px; lampirkan tangkapan layar.
- Akhir setiap sesi: `composer ci` hijau, perbarui `docs/PROGRESS.md`, bagian "Perintah/langkah untuk pemilik proyek" dan "Belum bisa diverifikasi".

## 6. Peta Dokumen Fase 2
| File | Isi |
|---|---|
| `00-RINGKASAN-FASE2.md` | Dokumen ini |
| `01-PRD-FASE2.md` | Kebutuhan, cerita pengguna, kriteria penerimaan |
| `02-ARSITEKTUR-DAN-API.md` | Arsitektur, tabel baru, endpoint, alur FCM, konfigurasi |
| `03-UX-MOBILE.md` | Tampilan HP, layar, teks, aturan kemudahan |
| `04-ROADMAP-FASE2.md` | Milestone S0, A1–A6 |
| `05-PROMPT-ANTIGRAVITY.md` | Prompt siap pakai |
| `06-SETUP-ANDROID-DAN-FIREBASE.md` | (manusia) Android Studio, Firebase, build & pasang APK |
| `07-JARINGAN-DAN-IT.md` | (IT BPRS) jaringan, VPN, firewall, HTTPS, deploy |
| `08-UAT-DAN-RILIS.md` | Rencana uji, matriks perangkat, rilis, rollback |
| `AGENTS-UPDATE.md` | Perubahan yang harus digabung ke `AGENTS.md` |

## 7. Glosarium Sederhana
- **APK:** berkas pemasang aplikasi Android. **WebView:** "jendela" di dalam aplikasi yang menampilkan halaman web.
- **FCM (Firebase Cloud Messaging):** layanan gratis Google yang membunyikan notifikasi di HP.
- **Token perangkat:** "alamat" sebuah HP untuk FCM; disimpan di server per pengguna.
- **Deep link:** menekan notifikasi langsung membuka halaman disposisi yang tepat.
- **Queue (antrean):** pengiriman notifikasi dikerjakan di latar belakang oleh `queue:work`.
- **VPN:** terowongan terenkripsi dari HP ke jaringan kantor (diurus IT BPRS).

## 8. Pertanyaan Terbuka Fase 2 (tambahkan ke `docs/OPEN_QUESTIONS.md`)
- [ ] Apakah pimpinan perlu **membuat disposisi langsung dari HP**? (Default: tidak; Admin/sekretariat yang membuat.)
- [ ] Apakah semua pegawai memakai Android? Berapa HP pribadi vs HP kantor?
- [ ] Alamat/domain produksi E-Surat dan sertifikat HTTPS (CA internal atau domain publik)?
- [ ] Apakah BPRS punya VPN? Apakah pegawai perlu membuka E-Surat dari luar kantor?
- [ ] Apakah firewall kantor mengizinkan keluar ke Google (FCM) dan Wi-Fi pegawai mengizinkan port FCM di HP?
- [ ] Kebijakan berbagi dokumen dari HP (tombol Bagikan PDF → WhatsApp dsb.): izinkan atau batasi?
- [ ] Perlu kunci aplikasi (sidik jari/PIN) dan/atau blokir tangkapan layar (FLAG_SECURE)?
- [ ] Nama paket aplikasi resmi (default `id.bprssragen.esurat`) dan logo 1024×1024 resmi?
