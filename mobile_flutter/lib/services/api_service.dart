import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'api_server_settings.dart';

class ApiService {
  final Dio _dio = Dio();
  final _storage = const FlutterSecureStorage();

  static String get baseUrl => ApiServerSettings.serverUrl;

  ApiService() {
    _dio.options.baseUrl = baseUrl;
    _dio.options.connectTimeout = const Duration(seconds: 10);
    _dio.options.receiveTimeout = const Duration(seconds: 10);
    _dio.options.headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        // Alamat dapat diubah sebelum login; semua instance ApiService
        // memakai alamat terakhir yang tersimpan.
        options.baseUrl = baseUrl;
        String? token = await _storage.read(key: 'auth_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Tangani logout otomatis jika token tidak valid
          _storage.delete(key: 'auth_token');
        }
        return handler.next(error);
      },
    ));
  }

  Future<Response> login(String username, String password) async {
    return await _dio.post('/login', data: {
      'username': username,
      'password': password,
      'device_name': 'Mobile App',
    });
  }

  Future<Response> logout() async {
    return await _dio.post('/logout');
  }

  Future<Response> registerDeviceToken(String token) async {
    return _dio.post('/device-tokens', data: {
      'token': token,
      'platform': 'android',
      'app_version': '1.0.0',
      'device_name': 'E-Surat Android',
    });
  }

  Future<Response> revokeDeviceToken(String token) async {
    return _dio.delete('/device-tokens', data: {'token': token});
  }

  Future<Response> getMe() async {
    return await _dio.get('/me');
  }

  Future<Response> changePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    return _dio.post('/profile/password', data: {
      'current_password': currentPassword,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
  }

  Future<Response> getIncomingLetters(
      {String? search,
      int? classificationId,
      int page = 1,
      int? perPage}) async {
    Map<String, dynamic> queryParams = {'page': page};
    if (search != null && search.isNotEmpty) {
      queryParams['search'] = search;
    }
    if (classificationId != null) {
      queryParams['classification_id'] = classificationId;
    }
    if (perPage != null) {
      queryParams['per_page'] = perPage;
    }

    return await _dio.get('/incoming-letters', queryParameters: queryParams);
  }

  Future<Response> getIncomingLetterDetail(int id) async {
    return await _dio.get('/incoming-letters/$id');
  }

  Future<Response> getOutgoingLetters(
      {String? search, int page = 1, int? perPage}) async {
    Map<String, dynamic> queryParams = {'page': page};
    if (search != null && search.isNotEmpty) {
      queryParams['search'] = search;
    }
    if (perPage != null) {
      queryParams['per_page'] = perPage;
    }

    return await _dio.get('/outgoing-letters', queryParameters: queryParams);
  }

  Future<Response> getOutgoingLetterDetail(int id) async {
    return await _dio.get('/outgoing-letters/$id');
  }

  Future<Response> getCooperationAgreements(
      {String? search, int page = 1, int? perPage}) async {
    Map<String, dynamic> queryParams = {'page': page};
    if (search != null && search.isNotEmpty) {
      queryParams['search'] = search;
    }
    if (perPage != null) {
      queryParams['per_page'] = perPage;
    }

    return await _dio.get('/cooperation-agreements',
        queryParameters: queryParams);
  }

  Future<Response> getMyDispositions({int page = 1, int? perPage}) async {
    Map<String, dynamic> queryParams = {'page': page};
    if (perPage != null) {
      queryParams['per_page'] = perPage;
    }

    return await _dio.get('/dispositions', queryParameters: queryParams);
  }

  Future<Response> getDispositionDetail(int id) async {
    return await _dio.get('/dispositions/$id');
  }

  Future<Response> markDispositionAsDone(int id, String note) async {
    return await _dio.post('/dispositions/$id/done', data: {'note': note});
  }
}
