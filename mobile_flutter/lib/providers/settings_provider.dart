import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Pilihan ukuran huruf di tab Akun.
///
/// Nilai ini dikalikan dengan skala huruf sistem, jadi pengguna yang sudah
/// memperbesar huruf di pengaturan Android tetap mendapat ukuran yang lebih
/// besar lagi bila memilih "Sangat besar".
enum AppFontScale {
  normal('Normal', 1.0),
  large('Besar', 1.15),
  extraLarge('Sangat besar', 1.3);

  const AppFontScale(this.label, this.multiplier);

  final String label;
  final double multiplier;
}

/// Menyimpan pilihan ukuran huruf pengguna di perangkat (tanpa server).
class SettingsProvider extends ChangeNotifier {
  static const String _storageKey = 'font_scale';

  AppFontScale _fontScale = AppFontScale.normal;
  bool _isLoaded = false;

  AppFontScale get fontScale => _fontScale;
  bool get isLoaded => _isLoaded;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final stored = prefs.getString(_storageKey);

    if (stored != null) {
      _fontScale = AppFontScale.values.firstWhere(
        (value) => value.name == stored,
        orElse: () => AppFontScale.normal,
      );
    }

    _isLoaded = true;
    notifyListeners();
  }

  Future<void> setFontScale(AppFontScale value) async {
    if (_fontScale == value) {
      return;
    }

    _fontScale = value;
    notifyListeners();

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_storageKey, value.name);
  }
}
