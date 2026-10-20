# Pertanyaan Terbuka & Asumsi

Status: [ ] menunggu · [x] terjawab

- [ ] Format nomor surat resmi BPRS Sragen (contoh default: `045/DIR/SKR/BPRS-SRG/X/2026`)?
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

## 2026-10-20 — Ukuran huruf pengguna

- `// ASSUMPTION:` Pada M0 pilihan ukuran huruf disimpan di browser pengguna (`localStorage`) karena kolom `users.ui_font_scale` termasuk perubahan profil pengguna M1. Saat M1, pindahkan preferensi ini ke akun agar mengikuti pengguna lintas perangkat.
