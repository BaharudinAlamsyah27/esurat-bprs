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
    required this.onChangePassword,
    required this.onLogout,
  });

  final User? user;
  final AppFontScale fontScale;
  final ValueChanged<AppFontScale> onFontScaleChanged;
  final VoidCallback onOpenLaporan;
  final Future<String?> Function({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) onChangePassword;
  final VoidCallback onLogout;

  String get _levelLabel {
    return switch (user?.level) {
      'superadmin' => 'Superadmin',
      'admin' => 'Admin Persuratan',
      'user' => 'Pegawai',
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
          Text('Keamanan akun', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: AppSpacing.gap),
          SectionCard(
            onTap: () => _openChangePassword(context),
            semanticLabel: 'Ubah kata sandi',
            child: Row(
              children: [
                const Icon(Icons.lock_outline, color: AppColors.primaryLight),
                const SizedBox(width: AppSpacing.gap),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Ubah kata sandi',
                        style: Theme.of(context).textTheme.bodyLarge,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Ganti kata sandi akun Anda.',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right, color: AppColors.textSecondary),
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

  void _openChangePassword(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _ChangePasswordSheet(onSubmit: onChangePassword),
    );
  }
}

class _ChangePasswordSheet extends StatefulWidget {
  const _ChangePasswordSheet({required this.onSubmit});

  final Future<String?> Function({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) onSubmit;

  @override
  State<_ChangePasswordSheet> createState() => _ChangePasswordSheetState();
}

class _ChangePasswordSheetState extends State<_ChangePasswordSheet> {
  final _formKey = GlobalKey<FormState>();
  final _currentPassword = TextEditingController();
  final _password = TextEditingController();
  final _passwordConfirmation = TextEditingController();
  bool _isSaving = false;
  String? _error;

  @override
  void dispose() {
    _currentPassword.dispose();
    _password.dispose();
    _passwordConfirmation.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isSaving = true;
      _error = null;
    });

    final error = await widget.onSubmit(
      currentPassword: _currentPassword.text,
      password: _password.text,
      passwordConfirmation: _passwordConfirmation.text,
    );

    if (!mounted) return;
    if (error != null) {
      setState(() {
        _isSaving = false;
        _error = error;
      });
      return;
    }

    final messenger = ScaffoldMessenger.of(context);
    Navigator.of(context).pop();
    messenger.showSnackBar(
      const SnackBar(content: Text('Kata sandi berhasil diubah.')),
    );
  }

  String? _validateNewPassword(String? value) {
    if (value == null || value.isEmpty) return 'Masukkan kata sandi baru.';
    if (value.length < 10 ||
        !RegExp(r'[A-Z]').hasMatch(value) ||
        !RegExp(r'[a-z]').hasMatch(value) ||
        !RegExp(r'[0-9]').hasMatch(value)) {
      return 'Gunakan minimal 10 karakter, huruf besar, huruf kecil, dan angka.';
    }

    return null;
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.page,
          AppSpacing.gapLarge,
          AppSpacing.page,
          AppSpacing.gapLarge,
        ),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Ubah kata sandi',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: AppSpacing.gapSmall),
              Text(
                'Masukkan kata sandi saat ini, lalu buat kata sandi baru.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
              const SizedBox(height: AppSpacing.gapLarge),
              TextFormField(
                controller: _currentPassword,
                obscureText: true,
                autofillHints: const [AutofillHints.password],
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Kata sandi saat ini',
                ),
                validator: (value) => value == null || value.isEmpty
                    ? 'Masukkan kata sandi saat ini.'
                    : null,
              ),
              const SizedBox(height: AppSpacing.gap),
              TextFormField(
                controller: _password,
                obscureText: true,
                autofillHints: const [AutofillHints.newPassword],
                textInputAction: TextInputAction.next,
                decoration: const InputDecoration(
                  labelText: 'Kata sandi baru',
                  helperText: 'Minimal 10 karakter, huruf besar, kecil, dan angka.',
                ),
                validator: _validateNewPassword,
              ),
              const SizedBox(height: AppSpacing.gap),
              TextFormField(
                controller: _passwordConfirmation,
                obscureText: true,
                textInputAction: TextInputAction.done,
                decoration: const InputDecoration(
                  labelText: 'Ulangi kata sandi baru',
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Ulangi kata sandi baru.';
                  }
                  if (value != _password.text) {
                    return 'Ulangi kata sandi baru dengan benar.';
                  }

                  return null;
                },
                onFieldSubmitted: (_) => _submit(),
              ),
              if (_error != null) ...[
                const SizedBox(height: AppSpacing.gap),
                Text(
                  _error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ],
              const SizedBox(height: AppSpacing.gapLarge),
              FilledButton(
                onPressed: _isSaving ? null : _submit,
                child: _isSaving
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Simpan kata sandi'),
              ),
              TextButton(
                onPressed: _isSaving ? null : () => Navigator.of(context).pop(),
                child: const Text('Batal'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
