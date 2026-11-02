import 'package:dio/dio.dart';

/// Pesan kesalahan jaringan yang dipakai bersama oleh seluruh layar.
const String kOfflineMessage =
    'Tidak dapat terhubung ke jaringan kantor. Nyalakan Wi-Fi kantor atau VPN, lalu coba lagi.';

/// Pesan umum ketika server membalas dengan galat.
String kServerErrorMessage(String konteks) =>
    'Gagal memuat $konteks. Coba lagi.';

/// Benar bila galat disebabkan jaringan putus / server tidak terjangkau.
///
/// Dipakai untuk membedakan "jaringan kantor mati" dari galat data biasa,
/// supaya pengguna mendapat pesan dan tombol "Coba Lagi" yang tepat.
bool isConnectionProblem(DioException error) {
  return error.type == DioExceptionType.connectionError ||
      error.type == DioExceptionType.connectionTimeout ||
      error.type == DioExceptionType.receiveTimeout ||
      error.type == DioExceptionType.sendTimeout;
}
