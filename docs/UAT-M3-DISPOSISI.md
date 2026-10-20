# UAT M3 — Skema Disposisi Berjenjang

Panduan ini menjelaskan cara pengguna memeriksa alur disposisi M3 di Laragon. Lakukan pada surat uji yang tidak rahasia dan tidak berisi data nasabah. Satu surat yang sudah selesai atau ditutup tidak dapat dipakai untuk mengulang seluruh alur, jadi siapkan beberapa surat uji.

## Gambaran alur

```text
Admin Persuratan mendaftarkan surat masuk
        ↓ meneruskan kepada
Direksi membuat disposisi pertama
        ↓ meneruskan kepada
Kepala Operasional membuat disposisi lanjutan
        ↓ meneruskan kepada
Staf Operasional membaca, mengerjakan, lalu menyelesaikan
        ↓
Surat selesai setelah semua penerima aktif menyelesaikan tugas
```

Setiap disposisi adalah arahan kepada satu atau beberapa pegawai/unit. Penerima mengerjakan tugasnya lewat **Tindak lanjuti disposisi**. Status bergerak satu langkah setiap kali disimpan:

```text
Menunggu dibaca → Sudah dibaca → Sedang dikerjakan → Selesai
```

Membuka detail surat yang ditugaskan otomatis mengubah **Menunggu dibaca** menjadi **Sudah dibaca**. Karena itu, setelah membuka surat, pilihan status berikutnya biasanya **Sedang dikerjakan**. Pilihan penerima hanya memuat tugas aktif yang bisa ditindaklanjuti oleh akun yang sedang login; satu pilihan saja wajar bila akun itu hanya memiliki satu tugas aktif.

## Persiapan

1. Pastikan Laragon berjalan dan buka `http://esurat.test`.
2. Gunakan akun demo yang sudah tersedia di komputer: **Admin Persuratan**, **Direksi**, **Kepala Operasional**, **Staf Operasional**, dan bila perlu **Auditor**. Pakai kata sandi lokal yang selama ini Anda gunakan; jangan kirim kata sandi ke chat.
3. Siapkan sekurangnya **4 surat masuk uji** non-rahasia dengan nomor/perihal berbeda. Contoh perihal: “UAT M3 Alur Utama”, “UAT M3 Dua Penerima”, “UAT M3 Penutupan Manual”, dan “UAT M3 Lampiran”. Jangan memakai surat operasional sebenarnya.
4. Admin Persuratan mendaftarkan tiap surat dan meneruskannya kepada **Direksi**. Pastikan surat berstatus sudah diteruskan sebelum Direksi membuat disposisi.
5. Catat nomor agenda setiap surat. Kerjakan satu per satu agar hasilnya mudah dicatat.

> Jika akun atau surat uji belum tersedia, berhenti di persiapan dan catat apa yang tidak tersedia. Jangan membuat akun baru atau mengubah data produksi hanya untuk UAT.

## Skenario 1 — Alur utama Direksi → Kepala Operasional → Staf

Gunakan surat **UAT M3 Alur Utama**.

### 1A. Direksi membuat disposisi pertama

1. Login sebagai **Direksi**, cari surat dengan nomor agenda, lalu buka detailnya.
2. Tekan **Buat disposisi**.
3. Kolom **Disposisi lanjutan dari** tidak perlu diisi untuk disposisi pertama. Kolom ini hanya relevan ketika penerima meneruskan arahan yang sudah diterimanya.
4. Pada **Penerima pengguna**, pilih **Kepala Operasional**. Untuk alur ini, biarkan **Penerima unit kerja** kosong agar tidak membuat tugas unit tambahan.
5. Pilih satu **Instruksi** yang tersedia. Isi catatan arahan singkat, misalnya “Mohon ditelaah dan ditindaklanjuti.” Pilih prioritas yang tersedia. Tenggat boleh dikosongkan untuk skenario dasar.
6. Simpan/kirim.

**Hasil yang diharapkan:** tidak muncul 403; pengirim melihat toast “Disposisi berhasil dikirim”; disposisi tampil pada riwayat surat dengan penerima Kepala Operasional; status surat berubah menjadi **Dalam disposisi**; akun Kepala Operasional menerima pemberitahuan di lonceng notifikasi dan dapat menemukan tugasnya.

### 1B. Kepala Operasional membaca dan meneruskan

1. Logout, login sebagai **Kepala Operasional**, kemudian buka tugas/surat UAT tadi dari daftar disposisi.
2. Periksa riwayat. Tugas Kepala Operasional berubah dari **Menunggu dibaca** menjadi **Sudah dibaca** setelah detail dibuka.
3. Pilih **Buat disposisi**. Pada **Disposisi lanjutan dari**, pilih arahan Direksi yang baru diterima. Jika hanya ada satu arahan aktif, sistem boleh memilihnya otomatis saat disimpan. Jika beberapa, pilih yang sesuai dengan surat dan arahan.
4. Pilih **Staf Operasional** sebagai penerima pengguna. Biarkan penerima unit kosong. Isi instruksi dan catatan singkat, lalu simpan.

**Hasil yang diharapkan:** daftar asal disposisi berisi arahan aktif yang diterima Kepala Operasional; tidak ada pilihan dari surat lain atau arahan yang sudah selesai; riwayat memperlihatkan arahan Staf Operasional berada di bawah arahan Kepala Operasional.

### 1C. Staf mengerjakan dan menyelesaikan

1. Logout, login sebagai **Staf Operasional**, lalu buka surat/tugas UAT.
2. Buka **Tindak lanjuti disposisi** dan pastikan label penerima menunjuk tugas untuk Staf Operasional. Keterangan pengirim harus terpisah dan tidak keliru dibaca sebagai penerima.
3. Karena membuka detail menandai tugas sudah dibaca, pilihan status yang diharapkan adalah **Sedang dikerjakan**. Pilih status itu, isi laporan singkat, lalu simpan.
4. Buka tindak lanjut lagi. Pilih **Selesai**, isi laporan hasil, misalnya “Telaah selesai, dokumen diteruskan kepada Kepala Operasional.”, lalu simpan.

**Hasil yang diharapkan:** status penerima bergerak berurutan; laporan tersimpan di riwayat; surat menjadi **Selesai** hanya setelah semua penerima aktif yang masih berjalan selesai. Status tidak boleh meloncat atau mundur.

## Skenario 2 — Memeriksa pilihan penerima dan status

Gunakan surat baru **UAT M3 Pilihan** dan teruskan lewat alur awal sampai tugas diterima akun uji.

1. Login sebagai penerima tugas dan buka **Tindak lanjuti disposisi**.
2. Buka pilihan **Penerima**. Pastikan ada tugas aktif yang ditujukan kepada akun atau unit yang sedang login. Jangan mengharapkan semua penerima surat muncul di daftar akun ini.
3. Pilih tugas tersebut lalu buka **Status**. Opsi harus tersedia dan hanya menunjukkan langkah berikutnya.
4. Simpan satu langkah, buka kembali, dan pastikan opsi berikutnya berubah. Ulangi sampai **Selesai**.

**Hasil yang diharapkan:** opsi status tidak kosong; perubahan penerima memperbarui opsi status; status yang tidak berurutan tidak dapat disimpan. Jika ada beberapa tugas aktif, masing-masing tampil sebagai pilihan yang dapat dibedakan dengan nama tujuan/status/pengirim.

## Skenario 3 — Lebih dari satu penerima

Gunakan surat baru **UAT M3 Dua Penerima**. Direksi membuat satu disposisi kepada **Kepala Operasional** dan satu penerima individu lain yang memang tersedia/aktif. Hindari memilih orang dan unit yang sama sekaligus untuk menghindari tugas ganda yang tidak disengaja.

1. Selesaikan tugas satu penerima sampai **Selesai**, biarkan penerima lainnya belum selesai.
2. Periksa status surat.
3. Selesaikan tugas penerima yang tersisa.

**Hasil yang diharapkan:** surat belum berstatus **Selesai** ketika masih ada penerima aktif yang belum selesai; sesudah semua penerima aktif selesai, surat menjadi **Selesai**.

## Skenario 4 — Validasi isian saat membuat disposisi

Gunakan surat baru yang masih dapat didisposisikan. Percobaan yang harus ditolak bisa dilakukan dengan mengosongkan isian yang diwajibkan atau memilih dari opsi yang tersedia. Jangan mengubah database langsung.

| Isian/percobaan | Hasil yang diharapkan |
|---|---|
| Tidak memilih penerima pengguna maupun unit | Tidak tersimpan; aplikasi meminta minimal satu penerima |
| Memilih penerima aktif yang diizinkan | Bisa dipilih dan disimpan |
| Mencari Auditor, Admin Persuratan, atau Super Admin sebagai penerima individu | Tidak tersedia sebagai penerima disposisi |
| Mengirim ke unit kerja aktif (skenario terpisah) | Anggota aktif unit tersebut dapat melihat dan menindaklanjuti tugas unit |
| Memilih instruksi aktif | Instruksi tampil di riwayat disposisi |
| Catatan arahan biasa | Tersimpan dan terbaca di riwayat |
| Catatan lebih dari batas 3000 karakter | Ditolak dengan pesan validasi, bukan error server |
| Prioritas valid dan tanggal tenggat kosong/valid | Dapat disimpan |
| Tanggal tenggat tidak valid | Ditolak dengan pesan validasi |
| Mengirim disposisi lanjutan tanpa memilih asal saat ada beberapa arahan aktif | Ditolak dengan pesan yang meminta pemilihan asal disposisi |
| Memilih asal disposisi dari surat lain atau yang tidak aktif | Ditolak; tidak terbentuk disposisi |

## Skenario 5 — Validasi tindak lanjut dan lampiran

Gunakan surat **UAT M3 Lampiran**.

| Percobaan | Hasil yang diharapkan |
|---|---|
| Menyelesaikan tugas tanpa laporan | Ditolak; status tetap belum selesai |
| Menyelesaikan tugas dengan laporan singkat | Tersimpan dan status menjadi **Selesai** |
| Laporan lebih dari 3000 karakter | Ditolak dengan pesan validasi |
| Unggah PDF/JPG/PNG yang ukurannya di bawah 10 MB | Diterima dan tampil pada riwayat untuk pengguna berhak |
| Unggah TXT atau berkas lebih dari 10 MB | Ditolak; status tidak berubah |
| Pengguna yang bukan penerima mencoba memperbarui tugas | Akses ditolak |

Untuk uji file, gunakan dokumen dummy tanpa data nasabah. Jangan mengunggah file surat nyata ke percakapan ini.

## Skenario 6 — Penutupan manual

Gunakan surat baru **UAT M3 Penutupan Manual** yang masih memiliki tindak lanjut berjalan. Akun yang menutup harus pembuat disposisi dan memiliki hak menutup.

1. Buka tindakan **Tutup tindak lanjut** dan coba simpan tanpa alasan.
2. Coba alasan yang terlalu pendek (kurang dari 5 karakter), bila form mengizinkan percobaan tersebut.
3. Isi alasan yang jelas, misalnya “UAT dibatalkan oleh penguji.”, kemudian simpan.

**Hasil yang diharapkan:** tanpa alasan/alasan terlalu pendek, penutupan ditolak; dengan alasan yang valid, surat menjadi **Selesai**, alasan tercatat, dan penerima yang belum selesai berstatus dibatalkan.

## Skenario 7 — Hak akses

Lakukan pemeriksaan memakai surat uji, bukan surat kerja.

- Staf yang tidak menerima tugas tidak dapat mengubah status tugas itu.
- Anggota unit yang dituju dapat menindaklanjuti tugas unit sesuai haknya.
- Auditor/Admin Persuratan/Super Admin tidak dapat dipilih sebagai penerima individu disposisi.
- Pengguna tanpa izin membuat disposisi tidak melihat/menjalankan tindakan tersebut.
- Surat yang sudah selesai atau diarsipkan tidak menerima disposisi/tindak lanjut baru.
- Setiap perubahan status dan penutupan terlihat pada riwayat.

## Catatan hasil UAT

Isi satu baris untuk setiap skenario yang dicoba. Jika gagal, kirim tahap, peran (tanpa kata sandi), nomor agenda uji, hasil yang diharapkan, hasil yang muncul, dan teks error. Sembunyikan alamat/email, isi surat, dan data pribadi dari tangkapan layar.

| Skenario | Peran | Lulus/Gagal | Hasil aktual / error |
|---|---|---|---|
| 1A Disposisi pertama | Direksi |  |  |
| 1B Disposisi lanjutan | Kepala Operasional |  |  |
| 1C Tindak lanjut | Staf Operasional |  |  |
| 2 Pilihan penerima/status | Penerima tugas |  |  |
| 3 Banyak penerima | Direksi dan penerima |  |  |
| 4 Validasi pembuatan | Direksi/penerus |  |  |
| 5 Laporan/lampiran | Penerima tugas |  |  |
| 6 Tutup manual | Pembuat disposisi |  |  |
| 7 Hak akses | Akun terkait |  |  |

## Jika tampilan atau hasil berbeda

1. Di Terminal Laragon, pastikan lokasi aktif adalah folder proyek `C:\laragon\www\esurat`.
2. Jalankan `php artisan optimize:clear`.
3. Restart Apache dari Laragon, lalu muat ulang `http://esurat.test` dengan **Ctrl+F5**.
4. Jika masih gagal, catat tahap dan pesan error. Jangan mengulang aksi selesai/tutup pada surat uji yang sama.

## Pemeriksaan otomatis

Di Terminal Laragon pada folder proyek jalankan:

```powershell
composer ci
```

Pemeriksaan dinyatakan lulus jika test Pest, Pint, dan PHPStan semuanya selesai tanpa error. Pemeriksaan otomatis melengkapi UAT manual; keduanya perlu dicatat.
