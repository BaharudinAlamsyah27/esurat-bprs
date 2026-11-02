import 'package:flutter/material.dart';

import '../models/incoming_letter.dart';
import '../providers/home_provider.dart';
import '../theme/app_theme.dart';
import '../utils/format.dart';
import '../widgets/common_widgets.dart';

/// Tab Beranda: sapaan, kartu disposisi, ringkasan surat, dan surat terbaru.
///
/// Seluruh data diterima lewat properti (bukan langsung dari provider) supaya
/// tampilan ini mudah diuji tanpa jaringan.
class HomeTab extends StatelessWidget {
  const HomeTab({
    super.key,
    required this.userName,
    required this.summary,
    required this.isLoading,
    required this.error,
    required this.onRefresh,
    required this.onRetry,
    required this.onOpenDispositions,
    required this.onOpenSuratMasuk,
    required this.onOpenSuratKeluar,
    required this.onOpenLetter,
    required this.onNotificationTap,
    this.unreadDispositions = 0,
    this.now,
  });

  final String userName;
  final HomeSummary? summary;
  final bool isLoading;
  final String? error;
  final Future<void> Function() onRefresh;
  final VoidCallback onRetry;
  final VoidCallback onOpenDispositions;
  final VoidCallback onOpenSuratMasuk;
  final VoidCallback onOpenSuratKeluar;
  final ValueChanged<IncomingLetter> onOpenLetter;
  final VoidCallback onNotificationTap;
  final int unreadDispositions;

  /// Waktu acuan untuk sapaan dan lencana "Baru" (bisa diganti saat diuji).
  final DateTime? now;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: BrandAppBar(
        notificationLabel:
            unreadDispositions > 0 ? '$unreadDispositions' : null,
        onNotificationTap: onNotificationTap,
      ),
      body: _buildBody(context),
    );
  }

  Widget _buildBody(BuildContext context) {
    if (isLoading && summary == null) {
      return const LoadingList();
    }

    if (error != null && summary == null) {
      return OfflineState(message: error, onRetry: onRetry);
    }

    final data = summary;

    if (data == null) {
      return OfflineState(onRetry: onRetry);
    }

    return RefreshIndicator(
      onRefresh: onRefresh,
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.page,
          AppSpacing.gapSmall,
          AppSpacing.page,
          AppSpacing.gapLarge,
        ),
        children: [
          _Greeting(userName: userName, now: now ?? DateTime.now()),
          const SizedBox(height: AppSpacing.gapLarge),
          _DispositionCard(
            unread: data.unreadDispositions,
            onOpen: onOpenDispositions,
          ),
          const SizedBox(height: AppSpacing.gap),
          IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  child: _SummaryCard(
                    label: 'Surat masuk',
                    value: data.incomingTotal,
                    icon: Icons.inbox_rounded,
                    semanticLabel:
                        'Surat masuk, ${data.incomingTotal} surat. Buka daftar',
                    onTap: onOpenSuratMasuk,
                  ),
                ),
                const SizedBox(width: AppSpacing.gap),
                Expanded(
                  child: _SummaryCard(
                    label: 'Surat keluar',
                    value: data.outgoingTotal,
                    icon: Icons.send_rounded,
                    semanticLabel:
                        'Surat keluar, ${data.outgoingTotal} surat. Buka daftar',
                    onTap: onOpenSuratKeluar,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.gapLarge),
          Text('Surat terbaru', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: AppSpacing.gap),
          if (data.recentIncoming.isEmpty)
            const SectionCard(
              child: Text('Belum ada surat.'),
            )
          else
            ...data.recentIncoming.map(
              (letter) => Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.gap),
                child: _RecentLetterCard(
                  letter: letter,
                  now: now ?? DateTime.now(),
                  onTap: () => onOpenLetter(letter),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _Greeting extends StatelessWidget {
  const _Greeting({required this.userName, required this.now});

  final String userName;
  final DateTime now;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(sapaanSekarang(now), style: Theme.of(context).textTheme.bodyLarge),
        const SizedBox(height: 2),
        Text(
          userName,
          style: Theme.of(context).textTheme.headlineSmall,
        ),
      ],
    );
  }
}

/// Kartu hijau penuh: elemen paling menonjol di Beranda.
class _DispositionCard extends StatelessWidget {
  const _DispositionCard({required this.unread, required this.onOpen});

  final int unread;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.gapLarge),
      decoration: BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.circular(AppSpacing.radiusCard),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Disposisi untuk saya',
            style: TextStyle(fontSize: 16, color: Colors.white),
          ),
          const SizedBox(height: AppSpacing.gapSmall),
          Text(
            '$unread belum dibaca',
            style: const TextStyle(
              fontSize: 30,
              fontWeight: FontWeight.w700,
              color: Colors.white,
              height: 1.15,
            ),
          ),
          const SizedBox(height: AppSpacing.gapLarge),
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: onOpen,
              style: FilledButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: AppColors.primary,
                minimumSize: const Size.fromHeight(AppSpacing.minTouch),
              ),
              child: const Text('Buka disposisi'),
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.semanticLabel,
    required this.onTap,
  });

  final String label;
  final int value;
  final IconData icon;
  final String semanticLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SectionCard(
      onTap: onTap,
      semanticLabel: semanticLabel,
      padding: const EdgeInsets.all(AppSpacing.page),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 26, color: AppColors.primaryLight),
          const SizedBox(height: AppSpacing.gap),
          Text(label, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: 2),
          Text(
            '$value',
            style: const TextStyle(
              fontSize: 26,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
              height: 1.2,
            ),
          ),
        ],
      ),
    );
  }
}

class _RecentLetterCard extends StatelessWidget {
  const _RecentLetterCard({
    required this.letter,
    required this.now,
    required this.onTap,
  });

  final IncomingLetter letter;
  final DateTime now;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final baru = isSuratBaru(letter.receivedAt, sekarang: now);
    final tanggal = formatTanggalAtauStrip(letter.receivedAt);

    return SectionCard(
      onTap: onTap,
      semanticLabel:
          '${letter.subject}, dari ${letter.senderName}, $tanggal${baru ? ', baru' : ''}',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  letter.subject,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                    height: 1.3,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (baru) ...[
                const SizedBox(width: AppSpacing.gapSmall),
                const StatusBadge(label: 'Baru', tone: BadgeTone.primary),
              ],
            ],
          ),
          const SizedBox(height: 6),
          Text(
            'Dari ${letter.senderName} · $tanggal',
            style: Theme.of(context).textTheme.bodySmall,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
