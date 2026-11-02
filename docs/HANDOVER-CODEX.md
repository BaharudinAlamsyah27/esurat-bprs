# HANDOVER — From Qoder Session to Codex (ChatGPT)

> Dokumen per 2026-11-02. Dipersiapkan untuk agent berikutnya (Codex) yang melanjutkan proyek E-Surat BPRS Sragen, khususnya aplikasi mobile Flutter.
> Sumber detail lain: `docs/PROGRESS.md`, `AGENTS.md` (aturan proyek), `docs/04-ROADMAP.md`.

## 1. Status Proyek

| Bagian | Status |
|---|---|
| Backend web (Laravel 12 + Filament, surat masuk/keluar, disposisi sederhana, laporan, dashboard, audit) | Selesai dan stabil |
| API `/api/*` untuk mobile | Selesai; `composer ci` **hijau — 67 test lulus** |
| APK Flutter v1 (tampilan lama, grid menu) | Terbangun, ada di `public/downloads/esurat-bprs-flutter.apk` (~34 MB) |
| Rombakan UI Flutter (bottom navigation, Material 3) | **Selesai, lulus `flutter analyze` + 11 widget test, terverifikasi di emulator dengan data nyata — TAPI belum di-commit dan APK untuk HP belum dibangun ulang** |
| Notifikasi push (Firebase FCM) | Pengguna mengonfirmasi pop-up sudah diterima di HP saat APK berada di latar belakang; konfigurasi lokal telah diselaraskan |

Repo: `C:\laragon\www\esurat` — proyek mobile di subfolder `mobile_flutter/` (satu repo dengan web; shared DB & API; jangan di-split tanpa alasan kuat).

## 2. Yang Sudah Dilakukan (ringkas)

### Backend (jaga agar tetap sederhana)
- Endpoint API aktif ada di `routes/api.php`: `POST /api/login`, `/api/logout`, `GET /api/me`, daftar/detail surat masuk dan keluar, daftar/detail disposisi penerima, penyelesaian disposisi, lampiran privat, serta endpoint token FCM yang baru ditambahkan (`POST/DELETE /api/device-tokens`).
- Auth: Bearer token Sanctum, ekspiry 12 jam; **harus lewat guard `api`** (guard `web` membuat file `storage/app/.gitignore` ikut ter-merge config dan `storage:link` rusak — pernah kejadian, sudah diperbaiki).
- Policy: `viewAny` cukup user aktif (level `admin`/`user` sama-sama melihat daftar surat) — ini sesuai keputusan desain, bukan bug.
- Unduh lampiran: **route streaming sendiri** (bukan `filesystem.download` milik Filament yang butuh sesi web); tercatat di audit trail sebagai "Unduh Lampiran (API Mobile)".
- Semua index menerima `page` dan `per_page` (1..50); respons paginasi punya `meta.total`.

### Mobile (Flutter, `mobile_flutter/`)
- Stack: Flutter 3.24.5 / Dart 3.5.4; paket: `provider`, `dio`, `flutter_secure_storage`, `shared_preferences`, `intl`, `firebase_core`, `firebase_messaging`.
- Struktur UI baru: `main.dart` (MaterialApp + komposisi text scale) → `screens/main_shell.dart` (NavigationBar 4 tab: Beranda, Disposisi, Surat [sub-tab Masuk/Keluar], Akun) → `screens/*_tab.dart` dan `screens/*_list_screen.dart` (versi embed, tanpa AppBar sendiri).
- `theme/app_theme.dart` — token warna/spacing tunggal (hijau `#0F5C46`, aksen `#1D9E75`).
- `widgets/common_widgets.dart` — kartu, badge status, skeleton, empty state, offline state, app bar brand.
- `services/api_messages.dart` — pesan offline standar; dipakai semua layar.
- `providers/` — `auth_provider`, `letter_provider` (state per daftar), `home_provider` (angka Beranda), `settings_provider` (ukuran huruf, tersimpan lokal).
- Font Plus Jakarta Sans dibundel lokal: salinan di `mobile_flutter/assets/fonts/` dari `resources/fonts/PlusJakartaSans/` (jangan hapus salah satu tanpa sinkron).
- Aturan yang ditaati: UI tetap sederhana. Pengecualian yang disetujui pengguna pada 2026-11-02 menambahkan FCM terbatas untuk disposisi, tanpa mengubah alur surat lain.

## 3. Build, Uji, dan Lingkungan Development

- **API base URL diset saat build**: `--dart-define=API_BASE_URL=http://<server>:8000/api`. IP HP lokal terakhir `192.168.10.197`; emulator memakai `10.0.2.2`. Kedua alamat menunjuk komputer yang sama dari perangkat berbeda.
- Jalankan API dev: `php artisan serve --host=0.0.0.0 --port=8000` (WAJIB `0.0.0.0`; MySQL via Laragon hidup).
- Emulator di laptop ini: AVD **`esurat_uji`** (Pixel 5, API 34 x86_64, `hw.keyboard=yes`). API dari emulator = `http://10.0.2.2:8000/api`.
- Build uji emulator (±21 MB, cepat dipasang):
  `flutter build apk --release --target-platform android-x64 --dart-define=API_BASE_URL=http://10.0.2.2:8000/api`
  lalu `adb install -r build\app\outputs\flutter-apk\app-release.apk` (adb di `$env:LOCALAPPDATA\Android\Sdk\platform-tools`).
  Jangan pakai APK debug (~103 MB) — transfernya macet di emulator software-GPU.
- Build untuk HP: `flutter build apk --release --dart-define=API_BASE_URL=http://IP-SERVER:8000/api` → salin hasil ke `public/downloads/esurat-bprs-flutter.apk`.
- Setiap install APK menghapus sesi login. Akun `mobile.uji` adalah akun uji internal; reset/nonaktifkan atau hapus sebelum lingkungan produksi. Jangan menyimpan kata sandi uji di dokumen.
- Kualitas: `flutter analyze` dan `flutter test` jalan di folder `mobile_flutter`. Test web tetap `composer ci`.
- Uji tampilan kecil: `adb shell wm size 1080x2400; adb shell wm density 480` (360×800 dp), `settings put system font_scale 1.3`, lalu `wm size reset; wm density reset`.

## 4. Langkah Selanjutnya (urutan saran)

1. Pastikan migrasi perbaikan FK penerima disposisi sudah tercatat `Ran`, lalu jalankan `composer ci`, `flutter analyze`, dan `flutter test` dari mesin pengembang.
2. Buat APK rilis baru dari sumber terbaru, uji instalasi/pembaruan di HP, lalu ganti APK lama yang dibagikan. Percobaan build Codex sebelumnya tidak memperbarui berkas APK.
3. Sebelum dipasang kantor, pindahkan FCM ke akun/proyek Google milik kantor, pasang kredensial server secara privat, gunakan HTTPS, dan jangan commit `.env`, `google-services.json`, atau kunci service account.
4. Untuk produksi, jangan menjalankan `php artisan serve`; gunakan web server kantor sesuai panduan deploy, dengan MySQL, backup terjadwal, `APP_DEBUG=false`, dan rahasia produksi tersimpan aman.
5. **Backend: data yang dibutuhkan Beranda** (saat ini dihitung client dengan asumsi):
   - endpoint/status "belum dibaca" atau "surat baru" untuk badge lonceng dan badge "Baru" (ask di `docs/OPEN_QUESTIONS.md` bila perlu persetujuan scope);
   - opsional: endpoint ringkas jumlah disposisi belum dibaca per user (saat ini dihitung dari maks 50 data).
6. Perbaikan UX hasil temuan di perangkat (§5 butir terakhir) bila muncul lagi saat tes HP.

## 5. Masalah / Utang Teknis yang Diketahui

1. **CI belum diulang setelah perubahan terakhir:** `composer ci`, `flutter analyze`, dan `flutter test` harus dijalankan sebelum serah terima.
2. **APK publik belum diperbarui:** `public/downloads/esurat-bprs-flutter.apk` adalah keluaran lama; build Codex terbaru tidak berhasil. Uji dan ganti dengan APK dari sumber terakhir sebelum dibagikan.
3. **Firebase lokal masih proyek uji pribadi:** pop-up sudah dikonfirmasi, tetapi sebelum kantor memakai sistem, mentor perlu memakai proyek Firebase/akun kantor dan menempatkan kunci server dengan aman. `.env` dan service-account berada di jalur privat/diabaikan Git; periksa `git status` agar tidak ada rahasia yang terlanjur di-stage.
4. **Server lokal bukan deployment kantor:** dua proses `php artisan serve`/alamat Laragon hanya untuk pengembangan. Produksi memerlukan HTTPS, konfigurasi server permanen, backup dan pemulihan yang diuji.
5. **Data Beranda mobile masih proksi:** badge "Baru" memakai aturan ≤7 hari (`lib/utils/format.dart`); jumlah disposisi belum dibaca dibatasi 50 data (`lib/providers/home_provider.dart`). Tidak menghalangi notifikasi push, tetapi perlu diketahui saat UAT.
6. **API status surat mobile sederhana:** `IncomingLetterStatus` baru memiliki nilai `registered`; label `baru` yang diperkirakan aplikasi belum disediakan API.
7. **Uji perangkat belum lengkap:** pengguna mengonfirmasi pop-up FCM; skenario logout, izin ditolak, uji jaringan kantor sebenarnya, dan pemulihan perangkat setelah instal ulang belum dicatat.
8. `composer ci` tidak mencakup test Flutter (`mobile_flutter/test/`); jalankan `flutter analyze` dan `flutter test` secara terpisah.

## 6. Catatan Git

Belum di-commit (per 2026-11-02): perubahan UI `mobile_flutter/`, integrasi FCM `mobile_flutter/` dan `app/`, tes API, serta dokumentasi terkait. Saran pesan commit:
`feat(mobile): tambah notifikasi push disposisi FCM`
Aturan lingkungan (dari AGENTS.md): agent boleh menulis kode/dokumen; **pengguna yang menjalankan `git add/commit`** (sandbox agent tidak punya akses Git ke folder ini). Jangan `git init` ulang atau mengubah konfigurasi Git.

## 7. Cara Mengerjakan Tugas (wajib dibaca sebelum kirim kode ke Codex)

- Ikuti `AGENTS.md`: scope v2 tertutup; jangan buat fitur di luar daftar (arsip fase 2 di `docs/future/`); kompatibel PHP 8.2; interface Indonesia via `lang/id`; logika di Services/Actions, Policy untuk akses; nomor agenda/penomoran pakai `lockForUpdate()`; tanpa hard delete surat.
- Pengecualian scope yang disetujui pengguna: FCM terbatas untuk notifikasi disposisi APK, dicatat di `docs/DECISIONS.md`.
- ambiguity → catat di `docs/OPEN_QUESTIONS.md` + tandai `// ASSUMPTION:` di kode, jangan menebak diam-diam.
- Update `docs/PROGRESS.md` tiap akhir sesi. Jangan menjalankan `migrate:fresh`/`db:wipe` tanpa izin eksplisit pengguna.
