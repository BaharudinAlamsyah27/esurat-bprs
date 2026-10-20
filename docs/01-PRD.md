# 01 — Product Requirements Document (PRD)

## 1. Tujuan
Mengganti pencatatan surat manual/spreadsheet dengan sistem terpusat yang:
1. Mencatat seluruh surat masuk dan keluar (buku agenda digital).
2. Mengalirkan surat masuk melalui disposisi berjenjang dengan pelacakan status.
3. Mengelola pembuatan surat keluar dengan persetujuan berjenjang, penomoran otomatis, tanda tangan + QR.
4. Memberi notifikasi dan pengingat agar tidak ada surat terlambat ditindaklanjuti.
5. Menyediakan arsip yang dapat dicari, serta jejak audit lengkap.

## 2. Ruang Lingkup
**Termasuk (v1):** surat masuk, disposisi, surat keluar, approval berjenjang, penomoran, PDF + QR + verifikasi publik, notifikasi (in-app + email), arsip & pencarian, laporan/buku agenda, manajemen pengguna/peran/unit, audit log, delegasi (Plt/cuti).
**Tidak termasuk (v1):** surat internal/nota dinas, integrasi core banking, tanda tangan elektronik tersertifikasi PSrE, aplikasi mobile native, OCR otomatis, WhatsApp gateway (disiapkan sebagai fase 2).

## 3. Peran Pengguna (ASUMSI — konfirmasi struktur sebenarnya)
| Peran (role) | Deskripsi |
|---|---|
| `super_admin` | Pengelola sistem (IT). Atur pengguna, peran, master data, pengaturan. Tidak otomatis melihat surat rahasia. |
| `admin_persuratan` | Sekretariat. Registrasi surat masuk, pengelola penomoran, pengiriman surat keluar, arsip. |
| `direksi` | Direktur Utama / Direktur. Menerima surat, membuat disposisi, menyetujui/menandatangani surat keluar. |
| `kepala_unit` | Kepala Divisi/Bagian/Cabang. Menerima & meneruskan disposisi, mereview surat keluar unitnya. |
| `staf` | Pelaksana. Menerima disposisi, membuat draft surat keluar, melaporkan tindak lanjut. |
| `auditor` | Hanya baca (laporan, audit log) sesuai tingkat kerahasiaan yang diizinkan. |
| (opsional) `dps` / `komisaris` | Penerima/pengirim surat khusus; dapat disamakan dengan `direksi` pada v1. |

## 4. Fitur Fungsional

### F1 Autentikasi & Pengguna
- Login email/username + password; kebijakan password; throttle login; logout otomatis idle.
- 2FA (TOTP) opsional per pengguna (aktifkan bila didukung versi Filament).
- CRUD pengguna oleh `super_admin`: nama, NIP, email, jabatan, unit, peran, status aktif, foto tanda tangan & paraf (upload PNG transparan).
- Delegasi: pejabat menunjuk pengganti untuk rentang tanggal; pengganti memperoleh hak approval/disposisi yang sama selama periode itu (tercatat di audit).

### F2 Master Data
Unit kerja (hierarki), jabatan, klasifikasi arsip, jenis surat, instruksi disposisi standar, format penomoran, template surat (kop + isi), pengaturan instansi (nama, alamat, logo, kop).

### F3 Surat Masuk
- Registrasi oleh `admin_persuratan`: no. agenda otomatis (reset tahunan), tanggal terima, tanggal surat, no. surat pengirim, pengirim (instansi/orang), perihal, ringkasan, jenis, klasifikasi, sifat (`biasa/penting/segera/sangat_segera`), kerahasiaan, jumlah lampiran, cara terima (`langsung/pos/kurir/email`), tujuan awal, scan dokumen (PDF/gambar, boleh >1 file).
- Cek duplikat (no. surat + pengirim + tanggal sama) → peringatan.
- Cetak lembar disposisi & tanda terima.
- Status: `registered → forwarded → in_disposition → in_progress → completed → archived`.

### F4 Disposisi
- Pembuat disposisi (Direksi/Kepala Unit) memilih satu/lebih penerima (pengguna atau unit), instruksi standar (checklist) + catatan, prioritas, batas waktu.
- **Berjenjang:** penerima dapat mendisposisikan lagi (sub-disposisi, `parent_id`) ke bawahannya.
- Penerima: tandai dibaca, ubah status (`pending → read → in_progress → done`), isi laporan tindak lanjut + lampiran.
- Pohon disposisi tampil sebagai timeline di halaman surat.
- Surat masuk berstatus `completed` otomatis saat semua penerima aktif `done` (atau ditutup manual oleh pembuat disposisi/admin dengan alasan).

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
Saluran: in-app (lonceng Filament) + email (queue). Daftar peristiwa di `03-ALUR-BISNIS-DAN-RBAC.md`. Pengingat terjadwal untuk disposisi mendekati/melewati batas waktu dan approval yang menggantung > N hari.

### F8 Arsip & Pencarian
Pencarian full-text sederhana (perihal, pengirim, nomor, ringkasan) + filter (tahun, jenis, klasifikasi, status, unit, sifat). Surat selesai dapat diarsipkan dengan lokasi arsip fisik (rak/box) bila ada.

### F9 Dashboard & Laporan
- Dashboard per peran: surat menunggu tindakan saya, disposisi jatuh tempo, ringkasan jumlah masuk/keluar bulan ini, grafik tren.
- Laporan: Buku Agenda Surat Masuk, Buku Agenda Surat Keluar, Rekap Disposisi, Kinerja Tindak Lanjut. Ekspor PDF & Excel (maatwebsite/excel diperbolehkan).

### F10 Audit & Keamanan
Audit log semua aksi penting (siapa, kapan, apa, nilai lama→baru, IP). Halaman audit untuk `super_admin`/`auditor`.

## 5. Kebutuhan Non-Fungsional
- **Keamanan:** ikuti Bagian 5 di `AGENTS.md`; semua halaman butuh login kecuali verifikasi QR; HTTPS di produksi.
- **Kinerja:** daftar 10.000+ surat tetap responsif (index DB, pagination server-side).
- **Ketersediaan:** pencadangan harian (DB + file), prosedur restore diuji.
- **Kegunaan:** responsif (nyaman di tablet/ponsel untuk Direksi), bahasa Indonesia penuh, tanggal format `dd MMM yyyy`.
- **Kompatibilitas:** Chrome/Edge/Firefox versi terbaru.
- **Kemudahan perawatan:** test otomatis pada alur kritis (penomoran, approval, disposisi, otorisasi).
- **Retensi:** data tidak dihapus permanen; ekspor arsip tersedia.

## 6. Kriteria Keberhasilan
- Surat masuk → disposisi → selesai dapat dilakukan penuh tanpa kertas pendamping.
- Surat keluar dari draft → nomor + QR + PDF final dalam satu alur, dengan jejak audit.
- Tidak ada nomor surat ganda meski dibuat bersamaan (diuji).
- Surat berkerahasiaan tinggi tidak terlihat oleh pengguna tanpa hak (diuji).
