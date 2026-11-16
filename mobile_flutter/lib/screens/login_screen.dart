import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';
import '../services/api_server_settings.dart';
import '../theme/app_theme.dart';

/// Layar masuk. Tampilan disamakan dengan tema aplikasi (hijau BPRS,
/// latar abu netral, tombol besar).
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isEditingServer = false;

  Future<void> _editServerAddress() async {
    final controller = TextEditingController(
      text: ApiServerSettings.serverUrl.replaceFirst(RegExp(r'/api$'), ''),
    );
    final address = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Alamat server'),
        content: TextField(
          controller: controller,
          keyboardType: TextInputType.url,
          autocorrect: false,
          decoration: const InputDecoration(
            labelText: 'URL server',
            hintText: 'http://alamat-server:8000',
            helperText: 'Contoh: http://10.226.242.115:8000',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () {
              FocusManager.instance.primaryFocus?.unfocus();
              Navigator.pop(context);
            },
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () {
              FocusManager.instance.primaryFocus?.unfocus();
              Navigator.pop(context, controller.text.trim());
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
    controller.dispose();

    if (address == null || !mounted) return;
    if (!ApiServerSettings.isValid(address)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Masukkan alamat http:// atau https:// yang benar.'),
        ),
      );
      return;
    }

    setState(() => _isEditingServer = true);
    await ApiServerSettings.save(address);
    if (!mounted) return;
    setState(() => _isEditingServer = false);
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Alamat server berhasil disimpan.')),
    );
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _handleLogin() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final success = await authProvider.login(
      _usernameController.text,
      _passwordController.text,
    );

    if (success && mounted) {
      // AuthWrapper otomatis membuka MainShell setelah status berubah.
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(authProvider.error ?? 'Gagal masuk')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Container(
                    width: 72,
                    height: 72,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: const Text(
                      'B',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 34,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.gapLarge),
                const Text(
                  'E-Surat BPRS',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'Masuk ke akun Anda',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: 32),
                TextField(
                  controller: _usernameController,
                  textInputAction: TextInputAction.next,
                  autocorrect: false,
                  decoration: const InputDecoration(
                    labelText: 'Nama Pengguna',
                    prefixIcon: Icon(Icons.person_outline),
                  ),
                ),
                const SizedBox(height: AppSpacing.gap),
                TextField(
                  controller: _passwordController,
                  obscureText: _obscurePassword,
                  onSubmitted: (_) => _handleLogin(),
                  decoration: InputDecoration(
                    labelText: 'Kata Sandi',
                    prefixIcon: const Icon(Icons.lock_outline),
                    suffixIcon: IconButton(
                      tooltip: _obscurePassword
                          ? 'Tampilkan kata sandi'
                          : 'Sembunyikan kata sandi',
                      icon: Icon(
                        _obscurePassword
                            ? Icons.visibility_off_outlined
                            : Icons.visibility_outlined,
                      ),
                      onPressed: () =>
                          setState(() => _obscurePassword = !_obscurePassword),
                    ),
                  ),
                ),
                const SizedBox(height: 28),
                Consumer<AuthProvider>(
                  builder: (context, auth, _) {
                    return FilledButton(
                      onPressed: auth.isLoading ? null : _handleLogin,
                      child: auth.isLoading
                          ? const SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(
                                strokeWidth: 3,
                                color: Colors.white,
                              ),
                            )
                          : const Text('Masuk'),
                    );
                  },
                ),
                const SizedBox(height: AppSpacing.gapLarge),
                Text(
                  'Gunakan akun kantor BPRS Sragen.',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                const SizedBox(height: 12),
                TextButton.icon(
                  onPressed: _isEditingServer ? null : _editServerAddress,
                  icon: const Icon(Icons.dns_outlined),
                  label: Text(
                    _isEditingServer
                        ? 'Menyimpan alamat server…'
                        : 'Ubah alamat server',
                  ),
                ),
                Text(
                  ApiServerSettings.serverUrl.replaceFirst(RegExp(r'/api$'), ''),
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
