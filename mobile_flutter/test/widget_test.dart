import 'package:esurat_mobile/models/incoming_letter.dart';
import 'package:esurat_mobile/providers/home_provider.dart';
import 'package:esurat_mobile/providers/settings_provider.dart';
import 'package:esurat_mobile/screens/account_tab.dart';
import 'package:esurat_mobile/screens/home_tab.dart';
import 'package:esurat_mobile/models/user.dart';
import 'package:esurat_mobile/screens/login_screen.dart';
import 'package:esurat_mobile/services/api_server_settings.dart';
import 'package:esurat_mobile/providers/auth_provider.dart';
import 'package:esurat_mobile/theme/app_theme.dart';
import 'package:esurat_mobile/utils/format.dart';
import 'package:esurat_mobile/widgets/common_widgets.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

IncomingLetter _surat({
  int id = 1,
  String subject = 'Permohonan data nasabah',
  String sender = 'PT Mitra Sejahtera',
  DateTime? receivedAt,
}) {
  return IncomingLetter(
    id: id,
    agendaNumber: '001/2026',
    letterNumber: '12/BPRS/2026',
    receivedAt: receivedAt ?? DateTime(2026, 10, 27),
    senderName: sender,
    subject: subject,
    classification: Classification(id: 1, code: '100', name: 'Umum'),
    hasDisposition: false,
    createdAt: DateTime(2026, 10, 27),
  );
}

HomeSummary _ringkasan({
  int unread = 3,
  List<IncomingLetter>? recent,
}) {
  return HomeSummary(
    incomingTotal: 12,
    outgoingTotal: 8,
    unreadDispositions: unread,
    dispositionsTotal: unread,
    recentIncoming: recent ?? [_surat()],
    countsArePartial: false,
  );
}

/// Membungkus widget dengan ukuran layar 360x800 dan skala huruf tertentu.
Widget _bingkai(Widget child, {double textScale = 1.0}) {
  return MaterialApp(
    theme: buildAppTheme(),
    home: MediaQuery(
      data: MediaQueryData(
        size: const Size(360, 800),
        textScaler: TextScaler.linear(textScale),
      ),
      child: child,
    ),
  );
}

void main() {
  setUp(() async {
    SharedPreferences.setMockInitialValues(<String, Object>{});
    await ApiServerSettings.reset();
  });

  group('Tema', () {
    test('memakai hijau BPRS dan font Plus Jakarta Sans', () {
      final theme = buildAppTheme();

      expect(theme.colorScheme.primary, AppColors.primary);
      expect(theme.scaffoldBackgroundColor, AppColors.background);
      expect(theme.textTheme.bodyLarge?.fontSize, greaterThanOrEqualTo(16));
      expect(theme.textTheme.bodySmall?.fontSize, greaterThanOrEqualTo(14));
    });
  });

  group('Utilitas', () {
    test('sapaan mengikuti jam', () {
      expect(sapaanSekarang(DateTime(2026, 10, 30, 8)), 'Selamat pagi,');
      expect(sapaanSekarang(DateTime(2026, 10, 30, 13)), 'Selamat siang,');
      expect(sapaanSekarang(DateTime(2026, 10, 30, 17)), 'Selamat sore,');
      expect(sapaanSekarang(DateTime(2026, 10, 30, 21)), 'Selamat malam,');
    });

    test('tanggal memakai nama bulan Indonesia', () {
      expect(formatTanggal(DateTime(2026, 10, 27)), '27 Okt 2026');
      expect(formatTanggalAtauStrip(null), '-');
    });

    test('surat dianggap baru maksimal 7 hari', () {
      final sekarang = DateTime(2026, 10, 30);

      expect(isSuratBaru(DateTime(2026, 10, 29), sekarang: sekarang), isTrue);
      expect(isSuratBaru(DateTime(2026, 10, 1), sekarang: sekarang), isFalse);
    });
  });

  group('Pengaturan ukuran huruf', () {
    testWidgets('tersimpan di perangkat', (tester) async {
      final settings = SettingsProvider();
      await settings.load();
      expect(settings.fontScale, AppFontScale.normal);

      await settings.setFontScale(AppFontScale.extraLarge);

      final ulang = SettingsProvider();
      await ulang.load();
      expect(ulang.fontScale, AppFontScale.extraLarge);
      expect(AppFontScale.extraLarge.multiplier, greaterThan(1.0));
    });
  });

  group('Beranda', () {
    testWidgets('menampilkan sapaan, disposisi, dan surat terbaru',
        (tester) async {
      var dibuka = false;

      await tester.pumpWidget(
        _bingkai(
          HomeTab(
            userName: 'Bu Rina',
            summary: _ringkasan(),
            isLoading: false,
            error: null,
            onRefresh: () async {},
            onRetry: () {},
            onOpenDispositions: () => dibuka = true,
            onOpenSuratMasuk: () {},
            onOpenSuratKeluar: () {},
            onOpenLetter: (_) {},
            onNotificationTap: () {},
            unreadDispositions: 3,
            now: DateTime(2026, 10, 30, 9),
          ),
        ),
      );

      expect(find.text('E-Surat BPRS'), findsOneWidget);
      expect(find.text('Selamat pagi,'), findsOneWidget);
      expect(find.text('Bu Rina'), findsOneWidget);
      expect(find.text('3 belum dibaca'), findsOneWidget);
      expect(find.text('Surat masuk'), findsOneWidget);
      expect(find.text('Surat keluar'), findsOneWidget);
      expect(find.text('Surat terbaru'), findsOneWidget);
      expect(find.byTooltip('Buka disposisi'), findsOneWidget);
      expect(find.text('3'), findsOneWidget);
      expect(find.text('Permohonan data nasabah'), findsOneWidget);
      expect(
          find.text('Dari PT Mitra Sejahtera · 27 Okt 2026'), findsOneWidget);

      await tester.tap(find.text('Buka disposisi'));
      expect(dibuka, isTrue);
    });

    testWidgets('menampilkan "Belum ada surat." bila kosong', (tester) async {
      await tester.pumpWidget(
        _bingkai(
          HomeTab(
            userName: 'Bu Rina',
            summary: _ringkasan(unread: 0, recent: const []),
            isLoading: false,
            error: null,
            onRefresh: () async {},
            onRetry: () {},
            onOpenDispositions: () {},
            onOpenSuratMasuk: () {},
            onOpenSuratKeluar: () {},
            onOpenLetter: (_) {},
            onNotificationTap: () {},
          ),
        ),
      );

      expect(find.text('0 belum dibaca'), findsOneWidget);
      expect(find.text('Belum ada surat.'), findsOneWidget);
      expect(find.text('0'), findsNothing);
    });

    testWidgets('tanpa overflow pada skala huruf 1.3', (tester) async {
      await tester.pumpWidget(
        _bingkai(
          HomeTab(
            userName: 'Nama Pengguna Yang Cukup Panjang',
            summary: _ringkasan(
              recent: [
                _surat(
                    subject: 'Permohonan data nasabah dengan perihal panjang'),
              ],
            ),
            isLoading: false,
            error: null,
            onRefresh: () async {},
            onRetry: () {},
            onOpenDispositions: () {},
            onOpenSuratMasuk: () {},
            onOpenSuratKeluar: () {},
            onOpenLetter: (_) {},
            onNotificationTap: () {},
          ),
          textScale: 1.3,
        ),
      );

      expect(tester.takeException(), isNull);
    });
  });

  group('Keadaan jaringan', () {
    testWidgets('menampilkan pesan kantor dan tombol Coba Lagi',
        (tester) async {
      var dicoba = false;

      await tester.pumpWidget(
        _bingkai(
          OfflineState(onRetry: () => dicoba = true),
        ),
      );

      expect(
        find.text(
          'Tidak dapat terhubung ke jaringan kantor. Nyalakan Wi-Fi kantor atau VPN, lalu coba lagi.',
        ),
        findsOneWidget,
      );

      await tester.tap(find.text('Coba Lagi'));
      expect(dicoba, isTrue);
    });
  });

  group('Layar masuk', () {
    testWidgets('menampilkan kolom dan tombol, tanpa overflow', (tester) async {
      await tester.pumpWidget(
        _bingkai(
          ChangeNotifierProvider(
            create: (_) => AuthProvider(),
            child: const LoginScreen(),
          ),
          textScale: 1.3,
        ),
      );

      expect(find.text('E-Surat BPRS'), findsOneWidget);
      expect(find.text('Nama Pengguna'), findsOneWidget);
      expect(find.text('Kata Sandi'), findsOneWidget);
      expect(find.text('Masuk'), findsOneWidget);
      expect(find.text('Ubah alamat server'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });

    test('alamat server tersimpan dan otomatis memakai path API', () async {
      final berhasil = await ApiServerSettings.save(
        'http://10.226.242.115:8000',
      );

      expect(berhasil, isTrue);
      expect(ApiServerSettings.serverUrl, 'http://10.226.242.115:8000/api');

      await ApiServerSettings.load();
      expect(ApiServerSettings.serverUrl, 'http://10.226.242.115:8000/api');
    });

    test('menolak alamat yang bukan URL server http atau https', () async {
      final berhasil = await ApiServerSettings.save('10.226.242.115:8000');

      expect(berhasil, isFalse);
      expect(ApiServerSettings.serverUrl, ApiServerSettings.defaultServerUrl);
    });
  });

  group('Akun', () {
    final user = User(
      id: 4,
      name: 'Auditor Demo',
      username: 'auditor',
      level: 'user',
      positionTitle: 'Auditor Internal',
    );

    testWidgets('menampilkan level, pilihan huruf, dan tombol keluar',
        (tester) async {
      var keluar = false;
      AppFontScale? dipilih;

      await tester.pumpWidget(
        _bingkai(
          AccountTab(
            user: user,
            fontScale: AppFontScale.normal,
            onFontScaleChanged: (value) => dipilih = value,
            onOpenLaporan: () {},
            onChangePassword: ({
              required String currentPassword,
              required String password,
              required String passwordConfirmation,
            }) async => null,
            onLogout: () => keluar = true,
          ),
        ),
      );

      expect(find.text('Auditor Demo'), findsOneWidget);
      expect(find.text('Pegawai · auditor'), findsOneWidget);
      expect(find.text('Normal'), findsOneWidget);
      expect(find.text('Sangat besar'), findsOneWidget);

      await tester.tap(find.text('Sangat besar'));
      expect(dipilih, AppFontScale.extraLarge);

      final tombolKeluar = find.text('Keluar');
      await tester.scrollUntilVisible(
        tombolKeluar,
        200,
        scrollable: find
            .descendant(
              of: find.byType(AccountTab),
              matching: find.byType(Scrollable),
            )
            .first,
      );
      await tester.pumpAndSettle();
      await tester.tap(tombolKeluar);
      expect(keluar, isTrue);
    });
  });
}
