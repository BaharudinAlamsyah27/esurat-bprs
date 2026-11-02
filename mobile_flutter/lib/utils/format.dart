/// Pemformat tanggal sederhana berbahasa Indonesia.
///
/// Sengaja tidak memakai `DateFormat` dengan locale agar aplikasi tidak perlu
/// memuat data locale tambahan saat start.
const List<String> _namaBulan = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'Mei',
  'Jun',
  'Jul',
  'Agu',
  'Sep',
  'Okt',
  'Nov',
  'Des',
];

/// Contoh: 27 Okt 2026.
String formatTanggal(DateTime tanggal) {
  return '${tanggal.day} ${_namaBulan[tanggal.month - 1]} ${tanggal.year}';
}

/// Sama seperti [formatTanggal], tetapi mengembalikan tanda "-" bila kosong.
String formatTanggalAtauStrip(DateTime? tanggal) {
  return tanggal == null ? '-' : formatTanggal(tanggal);
}

/// Sapaan sesuai jam perangkat.
String sapaanSekarang(DateTime sekarang) {
  final jam = sekarang.hour;

  if (jam < 11) {
    return 'Selamat pagi,';
  }

  if (jam < 15) {
    return 'Selamat siang,';
  }

  if (jam < 19) {
    return 'Selamat sore,';
  }

  return 'Selamat malam,';
}

/// Surat dianggap "Baru" bila diterima dalam 7 hari terakhir.
///
/// TODO(status-surat): belum ada penanda "sudah dibaca" per surat di API,
/// jadi status Baru memakai tanggal terima sebagai perkiraan.
bool isSuratBaru(DateTime? diterimaPada, {DateTime? sekarang}) {
  if (diterimaPada == null) {
    return false;
  }

  final batas = (sekarang ?? DateTime.now()).subtract(const Duration(days: 7));

  return diterimaPada.isAfter(batas);
}
