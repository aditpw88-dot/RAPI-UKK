import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../core/session_manager.dart';
import '../../models/user_model.dart';
import '../../theme/app_theme.dart';
import 'password_reset_dialog.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({
    required this.currentBaseUrl,
    required this.onLoginSuccess,
    required this.onBackToCalendar,
    super.key,
  });

  final String currentBaseUrl;
  final void Function(String token, UserModel user, String baseUrl)
  onLoginSuccess;
  final VoidCallback onBackToCalendar;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _loginController;
  late final TextEditingController _passwordController;
  late String _baseUrl;

  bool _obscurePassword = true;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _baseUrl = widget.currentBaseUrl;
    _loginController = TextEditingController();
    _passwordController = TextEditingController();
  }

  @override
  void dispose() {
    _loginController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _openServerConfigDialog() async {
    final urlController = TextEditingController(text: _baseUrl);
    final selected = await showDialog<String>(
      context: context,
      builder: (dialogCtx) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.dns_outlined, color: AppTheme.primaryColor),
            SizedBox(width: 8),
            Text('Pengaturan Server API'),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'Pilih preset koneksi atau masukkan URL API manual:',
                style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
              ),
              const SizedBox(height: 12),
              ...AppConstants.serverPresets.map(
                (preset) => InkWell(
                  onTap: () {
                    urlController.text = preset['url']!;
                  },
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      vertical: 6,
                      horizontal: 4,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          preset['label']!,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: AppTheme.primaryColor,
                          ),
                        ),
                        Text(
                          preset['url']!,
                          style: const TextStyle(
                            fontSize: 12,
                            color: Color(0xFF334155),
                            fontFamily: 'monospace',
                          ),
                        ),
                        Text(
                          preset['desc']!,
                          style: const TextStyle(
                            fontSize: 11,
                            color: Color(0xFF64748B),
                          ),
                        ),
                        const Divider(height: 16),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 6),
              TextField(
                controller: urlController,
                decoration: const InputDecoration(
                  labelText: 'URL API Aktif',
                  hintText: 'http://10.0.2.2:8000/api',
                  prefixIcon: Icon(Icons.link),
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogCtx),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () {
              final newUrl = urlController.text.trim();
              if (newUrl.isNotEmpty && newUrl.startsWith('http')) {
                Navigator.pop(dialogCtx, newUrl);
              }
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );

    urlController.dispose();

    if (selected != null && selected.isNotEmpty) {
      await SessionManager.saveBaseUrl(selected);
      setState(() => _baseUrl = selected);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Server API diubah ke: $selected'),
            backgroundColor: AppTheme.primaryColor,
          ),
        );
      }
    }
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final client = ApiClient(baseUrl: _baseUrl);
      final response =
          await client.post('/login', {
                'login': _loginController.text.trim(),
                'password': _passwordController.text,
                'device_name': 'Flutter Mobile App',
              })
              as Map<String, dynamic>;

      final token = response['token'] as String;
      final userMap = Map<String, dynamic>.from(response['user'] as Map);
      final user = UserModel.fromJson(userMap);

      // Check role authorization as requested by user
      if (user.isAdmin) {
        if (!mounted) return;
        _showAdminForbiddenDialog();
        return;
      }

      if (!user.isMobileAllowed) {
        if (!mounted) return;
        _showErrorSnackBar(
          'Role "${user.role}" tidak memiliki izin akses versi mobile. '
          'Aplikasi mobile hanya untuk Guru dan PIC Ruang.',
        );
        return;
      }

      await SessionManager.saveSession(
        token: token,
        user: user,
        baseUrl: _baseUrl,
      );

      widget.onLoginSuccess(token, user, _baseUrl);
    } on ApiException catch (e) {
      _showErrorSnackBar(e.message);
    } catch (e) {
      _showErrorSnackBar('Gagal terhubung ke $_baseUrl: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showAdminForbiddenDialog() {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        icon: const Icon(Icons.block, color: Color(0xFFEF4444), size: 48),
        title: const Text('Admin / Waka Menggunakan Web'),
        content: const Text(
          'Admin dan Waka merupakan satu kelompok akses dan menggunakan aplikasi web, bukan aplikasi mobile Flutter.\n\n'
          'Silakan gunakan browser untuk membuka dashboard Admin / Waka.',
          textAlign: TextAlign.center,
        ),
        actions: [
          Center(
            child: FilledButton(
              onPressed: () => Navigator.pop(ctx),
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFFEF4444),
              ),
              child: const Text('Mengerti'),
            ),
          ),
        ],
      ),
    );
  }

  void _showErrorSnackBar(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: const Color(0xFFEF4444),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  Future<void> _openPasswordResetDialog() async {
    final reset = await showDialog<bool>(
      context: context,
      builder: (context) =>
          PasswordResetDialog(api: ApiClient(baseUrl: _baseUrl)),
    );

    if (reset == true && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Kata sandi berhasil diubah. Silakan masuk kembali.'),
          backgroundColor: Color(0xFF10B981),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.scaffoldBg,
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: AppTheme.headerGradient,
            border: Border(
              bottom: BorderSide(color: AppTheme.headerBorder, width: 2),
            ),
          ),
        ),
        iconTheme: IconThemeData(color: AppTheme.brandGold),
        title: Text(
          'Masuk Aplikasi',
          style: TextStyle(
            color: AppTheme.brandGold,
            fontSize: 16.5,
            fontWeight: FontWeight.w800,
          ),
        ),
        leading: IconButton(
          onPressed: widget.onBackToCalendar,
          tooltip: 'Kembali ke kalender',
          icon: const Icon(Icons.arrow_back),
        ),
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Brand Icon & Header (Ikon roda gigi API disembunyikan, dapat diakses via long-press logo)
                    Center(
                      child: GestureDetector(
                        onLongPress: _openServerConfigDialog,
                        child: Container(
                          width: 82,
                          height: 82,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(22),
                            border: Border.all(
                              color: const Color(0xFFFED7AA),
                              width: 1.5,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: AppTheme.brandAmber.withValues(alpha: 0.22),
                                blurRadius: 20,
                                offset: const Offset(0, 8),
                              ),
                            ],
                          ),
                          padding: const EdgeInsets.all(9),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(15),
                            child: Image.asset(
                              'assets/images/logo.png',
                              fit: BoxFit.contain,
                              errorBuilder: (_, __, ___) => const Icon(
                                Icons.school_rounded,
                                size: 42,
                                color: AppTheme.brandAmber,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 18),
                    Text(
                      AppConstants.appTitle,
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.headlineSmall
                          ?.copyWith(
                            fontWeight: FontWeight.w800,
                            color: const Color(0xFF0F172A),
                            letterSpacing: -0.5,
                          ),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'Portal Mobile untuk Guru dan PIC Ruang',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 13,
                        color: Color(0xFF64748B),
                        fontWeight: FontWeight.w500,
                      ),
                    ),

                    const SizedBox(height: 28),

                    // Login fields
                    TextFormField(
                      controller: _loginController,
                      decoration: const InputDecoration(
                        labelText: 'Username atau Email',
                        hintText: 'Contoh: adit-guru',
                        prefixIcon: Icon(Icons.person_outline),
                      ),
                      validator: (value) =>
                          value == null || value.trim().isEmpty
                          ? 'Masukkan username atau email.'
                          : null,
                    ),

                    const SizedBox(height: 14),

                    TextFormField(
                      controller: _passwordController,
                      obscureText: _obscurePassword,
                      decoration: InputDecoration(
                        labelText: 'Kata Sandi',
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscurePassword
                                ? Icons.visibility_outlined
                                : Icons.visibility_off_outlined,
                          ),
                          onPressed: () => setState(
                            () => _obscurePassword = !_obscurePassword,
                          ),
                        ),
                      ),
                      validator: (value) => value == null || value.isEmpty
                          ? 'Masukkan kata sandi.'
                          : null,
                      onFieldSubmitted: (_) => _handleLogin(),
                    ),

                    const SizedBox(height: 20),

                    FilledButton(
                      onPressed: _isLoading ? null : _handleLogin,
                      child: _isLoading
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(
                                strokeWidth: 2.5,
                                color: Colors.white,
                              ),
                            )
                          : const Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.login, size: 20),
                                SizedBox(width: 8),
                                Text('Masuk Aplikasi'),
                              ],
                            ),
                    ),
                    Align(
                      alignment: Alignment.centerRight,
                      child: TextButton(
                        onPressed: _isLoading ? null : _openPasswordResetDialog,
                        child: const Text('Lupa Kata Sandi?'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
