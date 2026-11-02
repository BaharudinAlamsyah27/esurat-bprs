import 'package:flutter/material.dart';

import '../models/user.dart';
import '../providers/settings_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/common_widgets.dart';

/// Tab Akun: identitas pengguna, ukuran huruf, laporan, dan tombol keluar.
class AccountTab extends StatelessWidget {
  const AccountTab({
    super.key,
    required this.user,
    required this.fontScale,
    required this.onFontScaleChanged,
    required this.onOpenLaporan,
    required this.onLogout,
  });

  final User? user;
  final AppFontScale fontScale;
  final ValueChanged<AppFontScale> onFontScaleChanged;
  final VoidCallback onOpenLaporan;
  final VoidCallback onLogout;

  String get _levelLabel {
    return switch (user?.level) {
      'admin' => 'Administrator',
      'user' => 'Pengguna',
      _ => '-',
    };
  }

  String get _inisial {
    final nama = user?.name.trim() ?? '';

    return nama.isEmpty ? '?' : nama.substring(0, 1).toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Akun'),
        titleSpacing: AppSpacing.page,
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.page,
          AppSpacing.gapSmall,
          AppSpacing.page,
          AppSpacing.gapLarge,
        ),
        children: [
          SectionCard(
            child: Row(
              children: [
                Container(
                  width: 56,
                  height: 56,
                  alignment: Alignment.center,
                  decoration: const BoxDecoration(
                    color: AppColors.primarySoft,
                    shape: BoxShape.circle,
                  ),
                  child: Text(
                    _inisial,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                  ),
                ),
                const SizedBox(width: AppSpacing.page),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        user?.name ?? '-',
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '$_levelLabel · ${user?.username ?? '-'}',
                        style: Theme.of(context).textTheme.bodySmall,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if ((user?.positionTitle ?? '').isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Text(
                          user!.positionTitle!,
                          style: Theme.of(context).textTheme.bodySmall,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.gapLarge),
          Text('Ukuran huruf', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: AppSpacing.gapSmall),
          Text(
            'Pilih ukuran yang paling nyaman dibaca.',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: AppSpacing.gap),
          SectionCard(
            padding: const EdgeInsets.symmetric(vertical: AppSpacing.gapSmall),
            child: Column(
              children: [
                for (final pilihan in AppFontScale.values)
                  RadioListTile<AppFontScale>(
                    value: pilihan,
                    // ignore: deprecated_member_use
                    groupValue: fontScale,
                    // ignore: deprecated_member_use
                    onChanged: (value) {
                      if (value != null) {
                        onFontScaleChanged(value);
                      }
                    },
                    title: Text(
                      pilihan.label,
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                    secondary: const Icon(
                      Icons.format_size,
                      color: AppColors.primaryLight,
                    ),
                    activeColor: AppColors.primary,
                  ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.gapLarge),
          Text('Menu lain', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: AppSpacing.gap),
          SectionCard(
            onTap: onOpenLaporan,
            semanticLabel: 'Buka laporan',
            child: Row(
              children: [
                const Icon(
                  Icons.bar_chart_rounded,
                  color: AppColors.primaryLight,
                ),
                const SizedBox(width: AppSpacing.gap),
                Expanded(
                  child: Text(
                    'Laporan',
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                ),
                const Icon(Icons.chevron_right, color: AppColors.textSecondary),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.gapLarge),
          OutlinedButton(
            onPressed: onLogout,
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.danger,
              side: const BorderSide(color: AppColors.danger),
            ),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );
  }
}
