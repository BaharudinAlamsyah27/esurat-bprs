import 'package:flutter/material.dart';

/// Token warna, jarak, dan tema aplikasi mobile E-Surat BPRS Sragen.
///
/// Satu warna aksen (hijau BPRS) dipakai untuk seluruh ikon dan tombol.
/// Merah hanya untuk lencana/status bahaya. Warna dan ukuran huruf mengikuti
/// panduan UX yang sama dengan versi web.
class AppColors {
  const AppColors._();

  /// Hijau utama BPRS (tombol, kartu utama, ikon aktif).
  static const Color primary = Color(0xFF0F5C46);

  /// Varian terang untuk ikon di atas latar putih.
  static const Color primaryLight = Color(0xFF1D9E75);

  /// Latar halaman abu netral (bukan hijau muda).
  static const Color background = Color(0xFFF5F7F6);

  /// Latar kartu.
  static const Color surface = Color(0xFFFFFFFF);

  /// Garis tipis tepi kartu.
  static const Color border = Color(0xFFE3E8E5);

  /// Teks utama.
  static const Color textPrimary = Color(0xFF1F2933);

  /// Teks sekunder.
  static const Color textSecondary = Color(0xFF52606D);

  /// Hanya untuk lencana/status bahaya.
  static const Color danger = Color(0xFFB42318);

  /// Latar lembut untuk lencana bahaya.
  static const Color dangerSoft = Color(0xFFFEF3F2);

  /// Latar lembut untuk lencana hijau.
  static const Color primarySoft = Color(0xFFE8F3EE);
}

/// Ukuran jarak dan radius yang dipakai berulang.
class AppSpacing {
  const AppSpacing._();

  static const double page = 16;
  static const double gapSmall = 8;
  static const double gap = 12;
  static const double gapLarge = 20;
  static const double radiusCard = 16;
  static const double radiusSmall = 12;

  /// Area sentuh minimum (panduan aksesibilitas).
  static const double minTouch = 48;
}

/// Tema Material 3 aplikasi mobile.
///
/// Ukuran huruf dasar: judul 20-22sp, isi minimal 16sp, label kecil 14sp.
/// Skala huruf sistem TIDAK dikunci; aplikasi hanya menambahkan pilihan
/// ukuran huruf pengguna di tab Akun (lihat SettingsProvider).
ThemeData buildAppTheme() {
  final colorScheme = ColorScheme.fromSeed(
    seedColor: AppColors.primary,
    primary: AppColors.primary,
    onPrimary: Colors.white,
    surface: AppColors.surface,
    onSurface: AppColors.textPrimary,
    error: AppColors.danger,
  );

  const baseTextTheme = TextTheme(
    headlineSmall: TextStyle(
      fontSize: 22,
      fontWeight: FontWeight.w700,
      color: AppColors.textPrimary,
    ),
    titleLarge: TextStyle(
      fontSize: 20,
      fontWeight: FontWeight.w700,
      color: AppColors.textPrimary,
    ),
    titleMedium: TextStyle(
      fontSize: 17,
      fontWeight: FontWeight.w600,
      color: AppColors.textPrimary,
    ),
    bodyLarge: TextStyle(
      fontSize: 16,
      color: AppColors.textPrimary,
      height: 1.4,
    ),
    bodyMedium: TextStyle(
      fontSize: 16,
      color: AppColors.textSecondary,
      height: 1.4,
    ),
    bodySmall: TextStyle(
      fontSize: 14,
      color: AppColors.textSecondary,
      height: 1.35,
    ),
    labelLarge: TextStyle(
      fontSize: 16,
      fontWeight: FontWeight.w600,
      color: AppColors.textPrimary,
    ),
    labelMedium: TextStyle(
      fontSize: 14,
      fontWeight: FontWeight.w600,
      color: AppColors.textSecondary,
    ),
  );

  return ThemeData(
    useMaterial3: true,
    colorScheme: colorScheme,
    fontFamily: 'PlusJakartaSans',
    scaffoldBackgroundColor: AppColors.background,
    textTheme: baseTextTheme,
    splashFactory: InkRipple.splashFactory,
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.background,
      surfaceTintColor: Colors.transparent,
      foregroundColor: AppColors.textPrimary,
      elevation: 0,
      centerTitle: false,
      titleTextStyle: TextStyle(
        fontFamily: 'PlusJakartaSans',
        fontSize: 20,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
      ),
    ),
    cardTheme: CardTheme(
      color: AppColors.surface,
      elevation: 0,
      surfaceTintColor: Colors.transparent,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radiusCard),
        side: const BorderSide(color: AppColors.border),
      ),
    ),
    dividerTheme: const DividerThemeData(
      color: AppColors.border,
      thickness: 1,
      space: 1,
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        minimumSize: const Size.fromHeight(AppSpacing.minTouch),
        textStyle: const TextStyle(
          fontFamily: 'PlusJakartaSans',
          fontSize: 16,
          fontWeight: FontWeight.w700,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusSmall),
        ),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: AppColors.primary,
        minimumSize: const Size.fromHeight(AppSpacing.minTouch),
        side: const BorderSide(color: AppColors.border),
        textStyle: const TextStyle(
          fontFamily: 'PlusJakartaSans',
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusSmall),
        ),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: AppColors.primary,
        minimumSize: const Size(AppSpacing.minTouch, AppSpacing.minTouch),
        textStyle: const TextStyle(
          fontFamily: 'PlusJakartaSans',
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.surface,
      hintStyle: const TextStyle(fontSize: 16, color: AppColors.textSecondary),
      labelStyle: const TextStyle(fontSize: 16, color: AppColors.textSecondary),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radiusSmall),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radiusSmall),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(AppSpacing.radiusSmall),
        borderSide: const BorderSide(color: AppColors.primary, width: 2),
      ),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: AppColors.surface,
      surfaceTintColor: Colors.transparent,
      indicatorColor: AppColors.primarySoft,
      height: 76,
      elevation: 0,
      labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      labelTextStyle: WidgetStateProperty.resolveWith((states) {
        final selected = states.contains(WidgetState.selected);
        return TextStyle(
          fontFamily: 'PlusJakartaSans',
          fontSize: 14,
          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
          color: selected ? AppColors.primary : AppColors.textSecondary,
        );
      }),
      iconTheme: WidgetStateProperty.resolveWith((states) {
        final selected = states.contains(WidgetState.selected);
        return IconThemeData(
          size: 26,
          color: selected ? AppColors.primary : AppColors.textSecondary,
        );
      }),
    ),
    tabBarTheme: const TabBarTheme(
      labelColor: AppColors.primary,
      unselectedLabelColor: AppColors.textSecondary,
      indicatorColor: AppColors.primary,
      indicatorSize: TabBarIndicatorSize.tab,
      dividerColor: AppColors.border,
      labelStyle: TextStyle(
        fontFamily: 'PlusJakartaSans',
        fontSize: 16,
        fontWeight: FontWeight.w700,
      ),
      unselectedLabelStyle: TextStyle(
        fontFamily: 'PlusJakartaSans',
        fontSize: 16,
        fontWeight: FontWeight.w500,
      ),
    ),
    dialogTheme: const DialogTheme(
      backgroundColor: AppColors.surface,
      surfaceTintColor: Colors.transparent,
      titleTextStyle: TextStyle(
        fontFamily: 'PlusJakartaSans',
        fontSize: 20,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
      ),
      contentTextStyle: TextStyle(
        fontFamily: 'PlusJakartaSans',
        fontSize: 16,
        color: AppColors.textSecondary,
        height: 1.4,
      ),
    ),
  );
}
