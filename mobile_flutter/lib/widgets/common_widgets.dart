import 'package:flutter/material.dart';

import '../services/api_messages.dart';
import '../theme/app_theme.dart';

/// Kartu putih dengan garis tipis dan sudut membulat.
class SectionCard extends StatelessWidget {
  const SectionCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(AppSpacing.page),
    this.onTap,
    this.semanticLabel,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final VoidCallback? onTap;
  final String? semanticLabel;

  @override
  Widget build(BuildContext context) {
    final borderRadius = BorderRadius.circular(AppSpacing.radiusCard);

    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: borderRadius,
        side: const BorderSide(color: AppColors.border),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        borderRadius: borderRadius,
        child: Semantics(
          button: onTap != null,
          label: onTap != null ? semanticLabel : null,
          child: Padding(padding: padding, child: child),
        ),
      ),
    );
  }
}

/// Jenis warna lencana.
enum BadgeTone { primary, danger, neutral }

/// Lencana kecil untuk status ("Baru", "Disposisi", jumlah belum dibaca).
class StatusBadge extends StatelessWidget {
  const StatusBadge({
    super.key,
    required this.label,
    this.tone = BadgeTone.neutral,
  });

  final String label;
  final BadgeTone tone;

  @override
  Widget build(BuildContext context) {
    final (background, foreground) = switch (tone) {
      BadgeTone.primary => (AppColors.primarySoft, AppColors.primary),
      BadgeTone.danger => (AppColors.dangerSoft, AppColors.danger),
      BadgeTone.neutral => (AppColors.background, AppColors.textSecondary),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: foreground,
        ),
      ),
    );
  }
}

/// Indikator memuat berbentuk kerangka kartu (skeleton).
class LoadingList extends StatefulWidget {
  const LoadingList({super.key, this.itemCount = 4});

  final int itemCount;

  @override
  State<LoadingList> createState() => _LoadingListState();
}

class _LoadingListState extends State<LoadingList>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 900),
  )..repeat(reverse: true);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: 'Sedang memuat data',
      child: ListView.separated(
        padding: const EdgeInsets.all(AppSpacing.page),
        physics: const NeverScrollableScrollPhysics(),
        itemCount: widget.itemCount,
        separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.gap),
        itemBuilder: (context, index) {
          return FadeTransition(
            opacity: Tween<double>(begin: 0.45, end: 0.9).animate(_controller),
            child: Container(
              height: 96,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppSpacing.radiusCard),
                border: Border.all(color: AppColors.border),
              ),
            ),
          );
        },
      ),
    );
  }
}

/// Tampilan saat daftar kosong (ramah, bukan galat).
class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.title,
    required this.message,
    this.icon = Icons.inbox_outlined,
  });

  final String title;
  final String message;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.page),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: const BoxDecoration(
                color: AppColors.primarySoft,
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 34, color: AppColors.primaryLight),
            ),
            const SizedBox(height: AppSpacing.gapLarge),
            Text(
              title,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: AppSpacing.gapSmall),
            Text(
              message,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          ],
        ),
      ),
    );
  }
}

/// Tampilan saat jaringan kantor tidak terjangkau.
class OfflineState extends StatelessWidget {
  const OfflineState({super.key, required this.onRetry, this.message});

  final VoidCallback onRetry;
  final String? message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.page),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: const BoxDecoration(
                color: AppColors.dangerSoft,
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.wifi_off_rounded,
                size: 34,
                color: AppColors.danger,
              ),
            ),
            const SizedBox(height: AppSpacing.gapLarge),
            Text(
              message ?? kOfflineMessage,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyLarge,
            ),
            const SizedBox(height: AppSpacing.gapLarge),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: onRetry,
                child: const Text('Coba Lagi'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// App bar beranda: logo BPRS kecil + nama aplikasi + lonceng notifikasi.
class BrandAppBar extends StatelessWidget implements PreferredSizeWidget {
  const BrandAppBar({
    super.key,
    this.title,
    this.notificationLabel,
    this.onNotificationTap,
  });

  /// Judul tab. Bila null, dipakai nama aplikasi + logo.
  final String? title;
  final String? notificationLabel;
  final VoidCallback? onNotificationTap;

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);

  @override
  Widget build(BuildContext context) {
    return AppBar(
      titleSpacing: AppSpacing.page,
      title: title != null
          ? Text(title!)
          : Row(
              children: [
                Container(
                  width: 32,
                  height: 32,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Text(
                    'B',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                const SizedBox(width: AppSpacing.gapSmall),
                const Flexible(
                  child: Text(
                    'E-Surat BPRS',
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
      actions: [
        if (onNotificationTap != null)
          Padding(
            padding: const EdgeInsets.only(right: AppSpacing.page),
            child: IconButton(
              tooltip: 'Buka disposisi',
              onPressed: onNotificationTap,
              icon: Badge(
                isLabelVisible: notificationLabel != null,
                label: Text(notificationLabel ?? ''),
                backgroundColor: AppColors.textSecondary,
                textColor: Colors.white,
                child: const Icon(Icons.notifications_none_rounded),
              ),
            ),
          ),
      ],
    );
  }
}

/// Baris kecil berisi ikon + teks (untuk meta surat: pengirim, tanggal).
class MetaRow extends StatelessWidget {
  const MetaRow({
    super.key,
    required this.icon,
    required this.text,
    this.trailing,
  });

  final IconData icon;
  final String text;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18, color: AppColors.textSecondary),
        const SizedBox(width: 6),
        Expanded(
          child: Text(
            text,
            style: Theme.of(context).textTheme.bodySmall,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ),
        if (trailing != null) ...[
          const SizedBox(width: AppSpacing.gapSmall),
          trailing!,
        ],
      ],
    );
  }
}

/// Kotak pencarian sederhana untuk daftar surat.
class SearchField extends StatelessWidget {
  const SearchField({
    super.key,
    required this.controller,
    required this.hintText,
    required this.onSubmitted,
    required this.onClear,
  });

  final TextEditingController controller;
  final String hintText;
  final ValueChanged<String> onSubmitted;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<TextEditingValue>(
      valueListenable: controller,
      builder: (context, value, _) {
        return TextField(
          controller: controller,
          textInputAction: TextInputAction.search,
          onSubmitted: onSubmitted,
          decoration: InputDecoration(
            hintText: hintText,
            prefixIcon: const Icon(Icons.search),
            suffixIcon: value.text.isEmpty
                ? null
                : IconButton(
                    tooltip: 'Hapus pencarian',
                    icon: const Icon(Icons.close),
                    onPressed: onClear,
                  ),
          ),
        );
      },
    );
  }
}
