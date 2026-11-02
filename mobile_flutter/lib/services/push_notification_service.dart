import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

import 'api_service.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

/// Menghubungkan token FCM perangkat dengan akun yang sedang login.
/// Jika konfigurasi Firebase belum dipasang, aplikasi tetap dapat digunakan.
class PushNotificationService {
  PushNotificationService._();

  static final PushNotificationService instance = PushNotificationService._();

  final ApiService _apiService = ApiService();
  final StreamController<int> _openedDispositions =
      StreamController<int>.broadcast();
  final StreamController<void> _foregroundMessages =
      StreamController<void>.broadcast();

  Stream<int> get openedDispositions => _openedDispositions.stream;
  Stream<void> get foregroundMessages => _foregroundMessages.stream;

  bool _initialized = false;
  bool _available = false;
  bool _authenticated = false;
  int? _pendingDispositionId;
  String? _currentToken;

  Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;

    try {
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
      FirebaseMessaging.onMessage.listen((_) => _foregroundMessages.add(null));
      FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);
      FirebaseMessaging.instance.onTokenRefresh.listen(_registerToken);

      final initialMessage =
          await FirebaseMessaging.instance.getInitialMessage();
      if (initialMessage != null) _handleNotificationTap(initialMessage);

      _available = true;
    } catch (_) {
      // Tidak menghambat login/data utama jika file konfigurasi Firebase belum
      // tersedia atau layanan Firebase sedang tidak dapat dijangkau.
      _available = false;
    }
  }

  Future<void> registerAfterLogin() async {
    _authenticated = true;
    if (!_available) return;

    try {
      final permission = await FirebaseMessaging.instance.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );

      if (permission.authorizationStatus == AuthorizationStatus.denied) {
        return;
      }

      final token = await FirebaseMessaging.instance.getToken();
      if (token != null) await _registerToken(token);
    } catch (_) {
      // Push bersifat tambahan; kegagalan pendaftaran tidak membatalkan login.
    }
  }

  Future<void> unregisterBeforeLogout() async {
    final token = _currentToken;
    if (token != null) {
      try {
        await _apiService.revokeDeviceToken(token);
      } catch (_) {
        // Endpoint logout di server juga mencabut token yang terikat ke sesi API.
      }
    }

    _authenticated = false;
    _currentToken = null;
  }

  int? takePendingDispositionId() {
    final id = _pendingDispositionId;
    _pendingDispositionId = null;
    return id;
  }

  Future<void> _registerToken(String token) async {
    if (!_authenticated) return;

    try {
      await _apiService.registerDeviceToken(token);
      _currentToken = token;
    } catch (_) {
      // Token refresh berikutnya atau login berikutnya akan mencoba kembali.
    }
  }

  void _handleNotificationTap(RemoteMessage message) {
    final data = message.data;
    // Notifikasi penyelesaian ditujukan kepada pembuat disposisi, yang belum
    // tentu menjadi penerima. Buka aplikasi tanpa meminta endpoint penerima.
    if (data['type'] == 'disposition_completed') return;

    final path = data['path'];
    final match = path is String
        ? RegExp(r'^/disposisi-saya/(\d+)$').firstMatch(path)
        : null;
    final id = int.tryParse(match?.group(1) ?? data['id']?.toString() ?? '');

    if (id == null || id < 1) return;

    _pendingDispositionId = id;
    _openedDispositions.add(id);
  }
}
