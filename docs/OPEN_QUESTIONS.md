# Pertanyaan Terbuka & Asumsi

Status: [ ] menunggu · [x] terjawab

- [ ] Format nomor surat resmi BPRS Sragen (contoh default: `045/DIR/SKR/BPRS-SRG/X/2026`)?
- [ ] Format nomor agenda surat masuk resmi? Asumsi sementara: `001/2026`, urutan tahunan berdasarkan tahun pencatatan.
- [ ] Struktur organisasi sebenarnya (Direksi, divisi/bagian, kantor cabang/kas, DPS, Komisaris)?
- [ ] Perkiraan jumlah pengguna, jumlah kantor cabang, volume surat/bulan?
- [ ] Apakah ada daftar klasifikasi/kode arsip resmi?
- [ ] Apakah ada sistem/arsip existing yang datanya harus dimigrasi?
- [ ] Kop surat resmi (logo, alamat, warna) & contoh surat keluar yang berlaku?
- [ ] Apakah tanda tangan digital + QR cukup secara internal, atau perlu PSrE tersertifikasi?
- [ ] Apakah surat internal (nota dinas/memo) termasuk lingkup? (default: TIDAK, fase 2)
- [ ] SMTP internal tersedia untuk email notifikasi? Perlu WhatsApp gateway? (default: in-app + email)
- [ ] Kebijakan retensi arsip (berapa tahun)?
- [ ] Domain/URL internal & sertifikat HTTPS (CA internal)?
- [ ] Logo resmi BPRS Sragen (SVG/PNG) dan warna identitas resmi? (token warna di docs/07 §5 hanya nilai awal)
# Pertanyaan Terbuka

## Terjawab pada M1

- [x] Preferensi ukuran huruf disimpan pada `users.ui_font_scale`; perubahan pilihan dari widget Beranda dan halaman Profil tersimpan pada akun pengguna.
