# 01 — Product Requirements Document (PRD)

## 1. Tujuan
Mengganti pencatatan surat manual/spreadsheet dengan sistem terpusat yang:
1. Mencatat seluruh surat masuk dan keluar (buku agenda digital).
2. Meneruskan surat masuk ke penerima yang tepat dengan catatan "sudah dibaca" (disposisi berjenjang ditunda ke fase 2).
3. Mengelola pembuatan surat keluar dengan persetujuan berjenjang, penomoran otomatis, tanda tangan + QR.
4. Memberi notifikasi dan pengingat agar tidak ada surat terlambat ditindaklanjuti.
5. Menyediakan arsip yang dapat dicari, serta jejak audit lengkap.

## 2. Ruang Lingkup
**Termasuk (v1):** surat masuk (dengan fitur **Teruskan ke penerima**), surat keluar, approval berjenjang, penomoran, PDF + QR + verifikasi publik, notifikasi (in-app + email), arsip & pencarian, laporan/buku agenda, manajemen pengguna/peran/unit, audit log, delegasi (Plt/cuti), **surat internal (nota dinas, surat edaran, surat tugas) lengkap dengan balasan dan edaran massal**, dan **dashboard bergrafik**.
**Tidak termasuk (v1):** **disposisi (DITUNDA ke fase 2 atas arahan mentor; arsip spesifikasi di `docs/future/DISPOSISI-FASE-2.md`)**, integrasi core banking, tanda tangan elektronik tersertifikasi PSrE, aplikasi mobile native, OCR otomatis, WhatsApp gateway (disiapkan sebagai fase 2).

## 3. Peran Pengguna (ASUMSI — konfirmasi struktur sebenarnya)
| Peran (role) | Deskripsi |
|---|---|
| `super_admin` | Pengelola sistem (IT). Atur pengguna, peran, master data, pengaturan. Tidak otomatis melihat surat rahasia. |
| `admin_persuratan` | Sekretariat. Registrasi surat masuk, pengelola penomoran, pengiriman surat keluar, arsip. |
| `direksi` | Direktur Utama / Direktur. Menerima surat, menyetujui/menandatangani surat keluar. |
| `kepala_unit` | Kepala Divisi/Bagian/Cabang. Menerima surat masuk yang diteruskan, mereview surat keluar unitnya. |
| `staf` | Pelaksana. Menerima surat masuk yang diteruskan, membuat draft surat keluar. |
| `auditor` | Hanya baca (laporan, audit log) sesuai tingkat kerahasiaan yang diizinkan. |
| (opsional) `dps` / `komisaris` | Penerima/pengirim surat khusus; dapat disamakan dengan `direksi` pada v1. |

## 4. Fitur Fungsional

### F1 Autentikasi & Pengguna
- Login email/username + password; kebijakan password; throttle login; logout otomatis idle.
- 2FA (TOTP) opsional per pengguna (aktifkan bila didukung versi Filament).
- CRUD pengguna oleh `super_admin`: nama, NIP, email, jabatan, unit, peran, status aktif, foto tanda tangan & paraf (upload PNG transparan).
- Delegasi: pejabat menunjuk pengganti untuk rentang tanggal; pengganti memperoleh hak approval yang sama selama periode itu (tercatat di audit).

### F2 Master Data
Unit kerja (hierarki), jabatan, klasifikasi arsip, jenis surat, format penomoran, template surat (kop + isi), pengaturan instansi (nama, alamat, logo, kop).

### F3 Surat Masuk
- Registrasi oleh `admin_persuratan`: no. agenda otomatis (reset tahunan), tanggal terima, tanggal surat, no. surat pengirim, pengirim (instansi/orang), perihal, ringkasan, jenis, klasifikasi, sifat (`biasa/penting/segera/sangat_segera`), kerahasiaan, jumlah lampiran, cara terima (`langsung/pos/kurir/email`), tujuan awal, scan dokumen (PDF/gambar, boleh >1 file).
- Cek duplikat (no. surat + pengirim + tanggal sama) → peringatan.
- Cetak tanda terima surat (lembar penerimaan).
- Status: `registered → forwarded → archived`.

### F4 Teruskan Surat Masuk (pengganti disposisi di v1)
Disposisi (instruksi berjenjang, batas waktu, laporan tindak lanjut) **ditunda ke fase 2**. Di v1 cukup fitur sederhana:
- Admin Persuratan menekan **"Teruskan"** pada surat masuk → memilih satu/lebih **penerima** (orang atau unit) + **catatan singkat opsional** (maks. 255 karakter) → **Kirim**.
- Penerima mendapat notifikasi, melihat surat di menu *Surat Masuk*, dan saat membukanya sistem mencatat **"sudah dibaca"** otomatis. Tidak ada status kerja, batas waktu, laporan, atau sub-penerusan.
- Surat dapat diteruskan lagi (menambah penerima) oleh Admin; setiap penerusan tercatat (siapa, kapan, ke siapa, sudah dibaca atau belum) dan tampil sebagai riwayat sederhana di halaman surat.
- Status surat masuk hanya: `registered → forwarded → archived`.

### F5 Surat Keluar
- Pembuat (staf/kepala unit) membuat draft: tujuan (nama/instansi/alamat), perihal, jenis, klasifikasi, sifat, kerahasiaan, lampiran, tembusan, isi surat (rich text berbasis template) ATAU unggah file final; opsional tautan "membalas surat masuk #".
- Pilih/otomatis alur persetujuan sesuai jenis surat + unit.
- Kirim untuk persetujuan → langkah berurutan: `review` (Kepala Unit) → `approve` (opsional) → `sign` (Direksi/penandatangan).
- Aksi tiap langkah: **setujui**, **minta revisi** (kembali ke pembuat, wajib catatan), **tolak** (wajib alasan).
- Revisi: versi isi disimpan (riwayat), alur mulai lagi dari langkah pertama atau langkah yang meminta revisi (configurable, default: dari awal).
- Setelah langkah `sign` disetujui: sistem memberi **nomor surat**, menyematkan gambar tanda tangan + **QR verifikasi**, membuat PDF final (terkunci, hash disimpan).
- Admin Persuratan menandai **dikirim** (tanggal, cara kirim, bukti pengiriman) → arsip.
- Status: `draft → in_approval → revision / rejected → signed → sent → archived` (+ `cancelled`).

### F6 Tanda Tangan + QR
- Gambar tanda tangan/paraf milik pejabat (diunggah, hanya dipakai saat pejabat itu menyetujui — bukan dapat ditempel oleh orang lain).
- QR berisi URL `https://{domain}/verifikasi/{token}`; token acak 32+ karakter, unik per surat.
- Halaman publik/intranet **tanpa login** menampilkan: nomor surat, tanggal, perihal, penandatangan, status ("Sah/Terverifikasi", "Dibatalkan"), dan hash dokumen. Tidak menampilkan isi surat.
- Verifikasi hash: unggah PDF untuk dicocokkan dengan hash tersimpan (nice-to-have).
- Catatan hukum: ini bukan TTE tersertifikasi; lihat `DECISIONS.md`.

### F7 Notifikasi
Saluran: in-app (lonceng Filament) + email (queue). Daftar peristiwa di `03-ALUR-BISNIS-DAN-RBAC.md`. Pengingat terjadwal untuk approval yang menggantung > N hari dan edaran yang belum dikonfirmasi.

### F8 Arsip & Pencarian
Satu kotak cari yang mencocokkan **tiga hal saja: perihal, pengirim (pembuat/penerima untuk surat internal), dan nomor surat**, ditambah filter (tahun, jenis, klasifikasi, status, unit, sifat). Mencakup surat masuk, keluar, dan internal; hormati tingkat kerahasiaan. Pencarian isi scan (OCR) TIDAK dibuat.

### F9 Dashboard & Laporan
- **Beranda bergrafik (infografis):** kartu ringkasan + grafik tren surat, komposisi status, surat per jenis, surat masuk sudah dibaca vs belum, beban per unit, dan ringkasan surat internal; ditampilkan sesuai peran (lihat `07-PANDUAN-UX-SEDERHANA.md` §3B).
- Laporan: Buku Agenda Surat Masuk, Buku Agenda Surat Keluar, Rekap Surat per Unit. Ditambah: Buku Agenda Surat Internal (per jenis) dan Rekap Edaran (progres baca). Ekspor PDF & Excel (maatwebsite/excel diperbolehkan).

### F10 Audit & Keamanan
Audit log semua aksi penting (siapa, kapan, apa, nilai lama→baru, IP). Halaman audit untuk `super_admin`/`auditor`.

### F11 Surat Internal (Nota Dinas, Surat Edaran, Surat Tugas)
Surat yang beredar **di dalam BPRS** (antar pegawai/unit). Memakai mesin yang sama dengan surat keluar (konsep, persetujuan berjenjang, nomor otomatis, PDF + tanda tangan + QR), tetapi **tanpa tahap kirim manual**: setelah ditandatangani, surat otomatis masuk ke kotak penerima.
- **Jenis:** *Nota Dinas* (pembuat → satu/beberapa penerima, mis. atasan atau unit lain), *Surat Edaran* (ketentuan/pengumuman untuk banyak pegawai), *Surat Tugas* (penugasan pegawai: nama petugas, tujuan, tanggal mulai–selesai, lokasi).
- **Penerima:** Kepada (utama), Tembusan, dan Petugas (khusus Surat Tugas). Dapat dipilih per orang, per unit, per peran, atau **semua pegawai** (Surat Edaran).
- **Alur:** konsep → persetujuan (alur lebih pendek, dapat diatur per jenis) → tanda tangan final → nomor terbit + PDF + QR → **otomatis terkirim** + notifikasi.
- **Balasan (Nota Dinas):** penerima menekan **"Balas"** → terbentuk Nota Dinas baru yang terhubung dalam satu rangkaian (thread); penerima balasan otomatis pengirim asli. Rangkaian tampil sebagai garis waktu. Surat Edaran & Surat Tugas tidak dibalas, tetapi dapat dikonfirmasi.
- **Edaran massal:** sistem memecah target menjadi catatan penerima per orang (via queue) agar tiap orang punya status baca. Opsi **"Wajib konfirmasi sudah membaca"**. Pembuat melihat progres ("120 dari 150 sudah membaca") + daftar yang belum, dan pengingat otomatis dikirim ke yang belum membaca.
- **Surat Tugas:** tampil di Beranda petugas sebagai "Tugas Dinas" sampai tanggal selesai.
- **Penerima:** menu *Surat Internal* dengan dua tab **"Untuk Saya"** dan **"Buatan Saya"**; tombol **"Sudah Saya Baca"** dan (bila wajib) **"Saya Sudah Membaca & Mengerti"**.
- **Nomor:** terpisah per jenis (ND, SE, ST), reset tahunan. **Kerahasiaan, arsip, pencarian, QR verifikasi, audit:** sama seperti surat lain.
- **Hak membuat:** Nota Dinas — semua peran kecuali auditor; Surat Edaran & Surat Tugas — Direksi, Kepala Unit, Admin Persuratan (diatur lewat permission).
- **Di luar lingkup:** balasan massal, rapat/undangan terjadwal dengan RSVP, tanda tangan tersertifikasi.

## 5. Kebutuhan Non-Fungsional
- **Keamanan:** ikuti Bagian 5 di `AGENTS.md`; semua halaman butuh login kecuali verifikasi QR; HTTPS di produksi.
- **Kinerja:** daftar 10.000+ surat tetap responsif (index DB, pagination server-side).
- **Ketersediaan:** pencadangan harian (DB + file), prosedur restore diuji.
- **Kegunaan:** responsif (nyaman di tablet/ponsel untuk Direksi), bahasa Indonesia penuh, tanggal format `dd MMM yyyy`.
- **Kompatibilitas:** Chrome/Edge/Firefox versi terbaru.
- **Kemudahan perawatan:** test otomatis pada alur kritis (penomoran, approval, penerusan surat, otorisasi).
- **Retensi:** data tidak dihapus permanen; ekspor arsip tersedia.

## 6. Kriteria Keberhasilan
- Surat masuk dapat dicatat, diteruskan ke penerima, dan diarsipkan tanpa kertas pendamping.
- Surat keluar dari draft → nomor + QR + PDF final dalam satu alur, dengan jejak audit.
- Tidak ada nomor surat ganda meski dibuat bersamaan (diuji).
- Surat berkerahasiaan tinggi tidak terlihat oleh pengguna tanpa hak (diuji).
