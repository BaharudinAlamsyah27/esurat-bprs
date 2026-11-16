import 'package:shared_preferences/shared_preferences.dart';

/// Alamat server sementara yang dapat diganti selama pengujian.
class ApiServerSettings {
  static const String defaultServerUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://192.168.10.197:8000/api',
  );
  static const String _preferenceKey = 'api_server_url';

  static String _serverUrl = _normalize(defaultServerUrl);

  static String get serverUrl => _serverUrl;

  static Future<void> load() async {
    final preferences = await SharedPreferences.getInstance();
    final saved = preferences.getString(_preferenceKey);
    if (saved != null && isValid(saved)) {
      _serverUrl = _normalize(saved);
    }
  }

  static Future<bool> save(String value) async {
    if (!isValid(value)) return false;

    _serverUrl = _normalize(value);
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(_preferenceKey, _serverUrl);
    return true;
  }

  static Future<void> reset() async {
    _serverUrl = _normalize(defaultServerUrl);
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_preferenceKey);
  }

  static bool isValid(String value) {
    final input = value.trim();
    final uri = Uri.tryParse(input);

    return uri != null &&
        (uri.scheme == 'http' || uri.scheme == 'https') &&
        uri.host.isNotEmpty &&
        uri.userInfo.isEmpty &&
        uri.query.isEmpty &&
        uri.fragment.isEmpty &&
        (uri.path.isEmpty || uri.path == '/' || uri.path == '/api');
  }

  static String _normalize(String value) {
    final uri = Uri.parse(value.trim());
    final path = uri.path.isEmpty || uri.path == '/' ? '/api' : uri.path;

    return uri.replace(path: path).toString().replaceFirst(RegExp(r'/$'), '');
  }
}
