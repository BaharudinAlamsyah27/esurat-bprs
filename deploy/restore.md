# Panduan Memulihkan Cadangan

Pemulihan menimpa isi database dan berkas surat. Uji dahulu pada server staging dengan database kosong. Jangan memulihkan langsung ke server aktif tanpa persetujuan penanggung jawab dan salinan cadangan kondisi terkini.

## Uji pemulihan di staging

1. Siapkan database staging kosong dan akun MySQL yang boleh mengaksesnya.
2. Salin arsip cadangan ke server staging. Arsip berisi `database.sql.gz` dan `storage/app/private/`.
3. Ekstrak arsip ke direktori sementara, lalu impor dump ke database staging:

   ```bash
   mkdir -p /tmp/esurat-restore
   tar -xzf /var/backups/esurat/esurat-YYYYMMDD-HHMMSS.tar.gz -C /tmp/esurat-restore
   gunzip -c /tmp/esurat-restore/database.sql.gz | mysql --defaults-extra-file=/etc/esurat/mysql-client.cnf esurat_restore_test
   ```

4. Pulihkan berkas privat ke direktori staging aplikasi. Pastikan pemilik dan izin direktori sesuai pengguna PHP-FPM:

   ```bash
   tar -xzf /var/backups/esurat/esurat-YYYYMMDD-HHMMSS.tar.gz -C /var/www/esurat-staging
   chown -R www-data:www-data /var/www/esurat-staging/storage/app/private
   chmod -R o-rwx /var/www/esurat-staging/storage/app/private
   ```

5. Atur `.env` staging agar memakai `esurat_restore_test`, lalu jalankan:

   ```bash
   cd /var/www/esurat-staging
   php artisan config:clear
   php artisan about
   ```

6. Masuk sebagai pengguna uji. Pastikan data surat dan beberapa berkas dapat dibuka melalui aplikasi. Catat tanggal, nama arsip, durasi, dan hasil uji.

## Pemulihan server aktif

Hanya penanggung jawab server yang boleh menjadwalkan pemulihan produksi. Buat cadangan database dan berkas saat ini terlebih dahulu, aktifkan maintenance mode, lalu gunakan prosedur staging yang sudah berhasil dengan target database dan direktori produksi yang telah diperiksa. Setelah itu bersihkan cache, verifikasi login/surat/berkas, dan baru nonaktifkan maintenance mode. Jangan menjalankan `migrate:fresh` atau `db:wipe`.

Berkas cadangan harus disalin berkala ke media/server terpisah dengan akses terbatas. Backup pada disk server yang sama tidak melindungi dari kerusakan disk atau insiden server.
