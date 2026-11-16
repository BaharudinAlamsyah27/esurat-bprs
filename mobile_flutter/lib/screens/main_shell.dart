import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/incoming_letter.dart';
import '../providers/auth_provider.dart';
import '../providers/home_provider.dart';
import '../providers/letter_provider.dart';
import '../providers/settings_provider.dart';
import '../services/push_notification_service.dart';
import '../theme/app_theme.dart';
import 'account_tab.dart';
import 'disposition_list_screen.dart';
import 'home_tab.dart';
import 'letters_tab.dart';
import 'pdf_viewer_screen.dart';

/// Kerangka utama setelah masuk: 4 tab dengan bottom navigation.
///
/// Tab dimuat saat pertama kali dibuka (lazy) supaya aplikasi tidak memanggil
/// semua endpoint sekaligus ketika baru dibuka.
class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;
  int _lettersInitialIndex = 0;
  int _lettersKey = 0;
  final Set<int> _visited = {0};
  StreamSubscription<int>? _openedDispositionSubscription;
  StreamSubscription<void>? _foregroundMessageSubscription;

  @override
  void initState() {
    super.initState();
    final push = PushNotificationService.instance;
    _openedDispositionSubscription =
        push.openedDispositions.listen(_openDispositionFromPush);
    _foregroundMessageSubscription = push.foregroundMessages.listen((_) {
      if (!mounted) return;
      Provider.of<HomeProvider>(context, listen: false).load(silent: true);
      Provider.of<LetterProvider>(context, listen: false)
          .fetchDispositions(refresh: true);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Ada pembaruan disposisi.')),
      );
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<HomeProvider>(context, listen: false).load();
      final pendingId = push.takePendingDispositionId();
      if (pendingId != null) _openDispositionFromPush(pendingId);
    });
  }

  @override
  void dispose() {
    _openedDispositionSubscription?.cancel();
    _foregroundMessageSubscription?.cancel();
    super.dispose();
  }

  void _openDispositionFromPush(int id) {
    if (!mounted) return;
    setState(() {
      _visited.add(1);
      _index = 1;
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => DispositionDetailScreen(id: id),
        ),
      );
    });
  }

  void _openTab(int index) {
    setState(() {
      _visited.add(index);
      _index = index;
    });

    if (index == 0) {
      // Angka Beranda bisa berubah setelah disposisi diselesaikan.
      Provider.of<HomeProvider>(context, listen: false).load(silent: true);
    }

    if (index == 1) {
      Provider.of<LetterProvider>(context, listen: false)
          .fetchDispositions(refresh: true);
    }
  }

  void _openLetters(int tabIndex) {
    setState(() {
      _lettersInitialIndex = tabIndex;
      _lettersKey++;
      _visited.add(2);
      _index = 2;
    });
  }

  Future<void> _refreshHome() {
    return Provider.of<HomeProvider>(context, listen: false).load(silent: true);
  }

  void _openLetter(IncomingLetter letter) {
    if (letter.attachmentUrl == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Surat ini tidak memiliki lampiran berkas')),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PdfViewerScreen(
          url: letter.attachmentUrl!,
          title: 'Berkas Surat',
        ),
      ),
    );
  }

  void _showLaporanInfo() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content:
            Text('Laporan belum tersedia di aplikasi. Silakan buka versi web.'),
      ),
    );
  }

  void _confirmLogout() {
    final auth = Provider.of<AuthProvider>(context, listen: false);

    showDialog<void>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Keluar dari aplikasi?'),
        content: const Text(
          'Anda perlu masuk kembali untuk membuka surat dan disposisi.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () {
              Navigator.pop(dialogContext);
              auth.logout();
            },
            child: const Text('Ya, Keluar'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final settings = Provider.of<SettingsProvider>(context);
    final home = Provider.of<HomeProvider>(context);
    final unread = home.summary?.unreadDispositions ?? 0;

    Widget lazy(int index, Widget child) {
      return _visited.contains(index) ? child : const SizedBox.shrink();
    }

    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: [
          lazy(
            0,
            HomeTab(
              userName: auth.user?.name ?? '-',
              summary: home.summary,
              isLoading: home.isLoading,
              error: home.error,
              onRefresh: _refreshHome,
              onRetry: () => home.load(),
              onOpenDispositions: () => _openTab(1),
              onOpenSuratMasuk: () => _openLetters(0),
              onOpenSuratKeluar: () => _openLetters(1),
              onOpenLetter: _openLetter,
              unreadDispositions: unread,
              onNotificationTap: () => _openTab(1),
            ),
          ),
          lazy(
            1,
            Scaffold(
              appBar: AppBar(
                title: const Text('Disposisi Saya'),
                titleSpacing: AppSpacing.page,
              ),
              body: DispositionView(onOpen: _openDispositionFromPush),
            ),
          ),
          lazy(
            2,
            LettersTab(
              key: ValueKey<int>(_lettersKey),
              initialIndex: _lettersInitialIndex,
            ),
          ),
          lazy(
            3,
            AccountTab(
              user: auth.user,
              fontScale: settings.fontScale,
              onFontScaleChanged: settings.setFontScale,
              onOpenLaporan: _showLaporanInfo,
              onChangePassword: auth.changePassword,
              onLogout: _confirmLogout,
            ),
          ),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: _openTab,
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home_rounded),
            label: 'Beranda',
          ),
          NavigationDestination(
            icon: unread > 0
                ? Badge(
                    label: Text('$unread'),
                    backgroundColor: AppColors.danger,
                    child: const Icon(Icons.assignment_outlined),
                  )
                : const Icon(Icons.assignment_outlined),
            selectedIcon: unread > 0
                ? Badge(
                    label: Text('$unread'),
                    backgroundColor: AppColors.danger,
                    child: const Icon(Icons.assignment_rounded),
                  )
                : const Icon(Icons.assignment_rounded),
            label: 'Disposisi',
          ),
          const NavigationDestination(
            icon: Icon(Icons.mail_outline),
            selectedIcon: Icon(Icons.mail),
            label: 'Arsip',
          ),
          const NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Akun',
          ),
        ],
      ),
    );
  }
}
