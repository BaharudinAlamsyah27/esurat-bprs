import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/user.dart';
import '../services/api_service.dart';
import '../services/push_notification_service.dart';

class AuthProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  final _storage = const FlutterSecureStorage();

  User? _user;
  bool _isLoading = false;
  bool _isRestoring = true;
  String? _error;

  User? get user => _user;
  bool get isLoading => _isLoading;
  bool get isRestoring => _isRestoring;
  String? get error => _error;
  bool get isAuthenticated => _user != null;

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.login(username, password);
      final token = response.data['token'];
      final userData = User.fromJson(response.data['user']);

      await _storage.write(key: 'auth_token', value: token);
      _user = userData;
      _isLoading = false;
      notifyListeners();
      if (userData.pushEnabled) {
        await PushNotificationService.instance.registerAfterLogin();
      }
      return true;
    } on DioException catch (e) {
      _isLoading = false;
      _error = _messageFrom(e);
      notifyListeners();
      return false;
    } catch (e) {
      _isLoading = false;
      _error = 'Gagal masuk. Coba lagi.';
      notifyListeners();
      return false;
    }
  }

  Future<String?> changePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      await _apiService.changePassword(
        currentPassword: currentPassword,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );

      return null;
    } on DioException catch (e) {
      final data = e.response?.data;
      if (data is Map) {
        final errors = data['errors'];
        if (errors is Map) {
          for (final field in [
            'current_password',
            'password',
            'password_confirmation',
          ]) {
            final messages = errors[field];
            if (messages is List && messages.isNotEmpty) {
              return messages.first.toString();
            }
          }
        }

        if (data['message'] is String &&
            data['message'] != 'The given data was invalid.') {
          return data['message'] as String;
        }
      }

      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout) {
        return 'Tidak dapat menghubungi server. Periksa jaringan kantor Anda.';
      }

      return 'Kata sandi belum berhasil diubah. Coba lagi.';
    } catch (_) {
      return 'Kata sandi belum berhasil diubah. Coba lagi.';
    }
  }

  /// Mengambil pesan dari server (bahasa Indonesia) atau memberi pesan
  /// yang mudah dipahami kalau jaringannya yang bermasalah.
  String _messageFrom(DioException e) {
    final data = e.response?.data;
    if (data is Map && data['message'] is String) {
      return data['message'] as String;
    }

    if (e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout) {
      return 'Tidak dapat menghubungi server. Periksa jaringan kantor Anda.';
    }

    return 'Gagal masuk. Coba lagi.';
  }

  Future<void> logout() async {
    await PushNotificationService.instance.unregisterBeforeLogout();
    try {
      await _apiService.logout();
    } catch (e) {
      // Abaikan error saat logout
    } finally {
      await _storage.delete(key: 'auth_token');
      _user = null;
      notifyListeners();
    }
  }

  Future<void> tryAutoLogin() async {
    String? token = await _storage.read(key: 'auth_token');
    if (token == null) {
      _isRestoring = false;
      notifyListeners();
      return;
    }

    try {
      final response = await _apiService.getMe();
      _user = User.fromJson(response.data);
    } catch (e) {
      await _storage.delete(key: 'auth_token');
      _user = null;
    }

    _isRestoring = false;
    notifyListeners();
    if (_user?.pushEnabled ?? false) {
      unawaited(PushNotificationService.instance.registerAfterLogin());
    }
  }
}
