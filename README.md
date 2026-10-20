# E-Surat BPRS Sragen

Aplikasi internal untuk pencatatan surat masuk dan surat keluar BPRS Sragen.

## Menyiapkan lingkungan lokal

1. Pasang PHP 8.2+, Composer, Node.js, dan Laragon.
2. Salin `.env.example` menjadi `.env`, lalu sesuaikan koneksi MySQL. Nilai awal cocok untuk database `esurat` di Laragon.
3. Jalankan `composer install` dan `npm install`.
4. Jalankan `php artisan key:generate`.
5. Jalankan `php artisan migrate --seed` setelah migration dan seeder milestone berikutnya tersedia.
6. Jalankan `npm run build` untuk aset atau `npm run dev` saat mengembangkan tampilan.
7. Buka `http://esurat.test` melalui virtual host Laragon atau jalankan `php artisan serve`.

## Perintah pengembangan

- `composer ci` menjalankan pemeriksaan format, analisis statis, dan test.
- `composer lint` merapikan format PHP.
- `php artisan queue:work` menjalankan antrean notifikasi dan dokumen.

Panel aplikasi memakai locale Indonesia dan zona waktu `Asia/Jakarta`.
