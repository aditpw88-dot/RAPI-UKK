import 'package:flutter/material.dart';
import '../../core/api_client.dart';

class PasswordResetDialog extends StatefulWidget {
  const PasswordResetDialog({required this.api, super.key});

  final ApiClient api;

  @override
  State<PasswordResetDialog> createState() => _PasswordResetDialogState();
}

class _PasswordResetDialogState extends State<PasswordResetDialog> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _otpController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmationController = TextEditingController();

  int _step = 0;
  bool _isLoading = false;
  String? _errorMessage;
  String? _statusMessage;

  @override
  void dispose() {
    _emailController.dispose();
    _otpController.dispose();
    _passwordController.dispose();
    _confirmationController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _statusMessage = null;
    });

    try {
      if (_step == 0) {
        await widget.api.post('/password/forgot', {
          'email': _emailController.text.trim(),
        });
        setState(() => _step = 1);
      } else if (_step == 1) {
        await widget.api.post('/password/verify-otp', {
          'email': _emailController.text.trim(),
          'otp': _otpController.text.trim(),
        });
        setState(() => _step = 2);
      } else {
        await widget.api.post('/password/reset', {
          'email': _emailController.text.trim(),
          'otp': _otpController.text.trim(),
          'password': _passwordController.text,
          'password_confirmation': _confirmationController.text,
        });
        if (mounted) Navigator.of(context).pop(true);
      }
    } on ApiException catch (error) {
      setState(() => _errorMessage = error.message);
    } catch (error) {
      setState(() => _errorMessage = 'Gagal memproses permintaan: $error');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _resendOtp() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _statusMessage = null;
    });

    try {
      final response = await widget.api.post('/password/forgot', {
        'email': _emailController.text.trim(),
      }) as Map<String, dynamic>;
      setState(() => _statusMessage = '${response['message']}');
    } on ApiException catch (error) {
      setState(() => _errorMessage = error.message);
    } catch (error) {
      setState(() => _errorMessage = 'Gagal mengirim ulang kode OTP: $error');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final titles = ['Lupa Kata Sandi', 'Verifikasi Kode OTP', 'Buat Kata Sandi Baru'];
    final descriptions = [
      'Masukkan email akun Anda. Kode OTP akan dikirim ke email tersebut.',
      'Masukkan 6 digit kode yang dikirim ke ${_emailController.text.trim()}.',
      'Buat kata sandi baru dengan minimal 8 karakter.',
    ];

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 440),
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  titles[_step],
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  descriptions[_step],
                  style: const TextStyle(color: Color(0xFF64748B)),
                ),
                const SizedBox(height: 20),
                if (_step == 0)
                  TextFormField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'Email',
                      prefixIcon: Icon(Icons.email_outlined),
                    ),
                    validator: (value) {
                      final email = value?.trim() ?? '';
                      if (email.isEmpty || !email.contains('@')) {
                        return 'Masukkan alamat email yang valid.';
                      }
                      return null;
                    },
                  )
                else if (_step == 1)
                  TextFormField(
                    controller: _otpController,
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    decoration: const InputDecoration(
                      labelText: 'Kode OTP',
                      prefixIcon: Icon(Icons.verified_user_outlined),
                    ),
                    validator: (value) => value == null || value.trim().length != 6
                        ? 'Masukkan kode OTP 6 digit.'
                        : null,
                  )
                else ...[
                  TextFormField(
                    controller: _passwordController,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: 'Kata sandi baru',
                      prefixIcon: Icon(Icons.lock_outline),
                    ),
                    validator: (value) => value == null || value.length < 8
                        ? 'Kata sandi minimal 8 karakter.'
                        : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _confirmationController,
                    obscureText: true,
                    decoration: const InputDecoration(
                      labelText: 'Konfirmasi kata sandi',
                      prefixIcon: Icon(Icons.lock_reset_outlined),
                    ),
                    validator: (value) =>
                        value != _passwordController.text
                        ? 'Konfirmasi kata sandi tidak cocok.'
                        : null,
                  ),
                ],
                if (_errorMessage != null) ...[
                  const SizedBox(height: 12),
                  Text(
                    _errorMessage!,
                    style: const TextStyle(color: Color(0xFFB91C1C)),
                  ),
                ],
                if (_statusMessage != null) ...[
                  const SizedBox(height: 12),
                  Text(
                    _statusMessage!,
                    style: const TextStyle(color: Color(0xFF047857)),
                  ),
                ],
                const SizedBox(height: 20),
                FilledButton(
                  onPressed: _isLoading ? null : _submit,
                  child: _isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : Text([
                          'Kirim Kode OTP',
                          'Verifikasi OTP',
                          'Simpan Kata Sandi',
                        ][_step]),
                ),
                if (_step == 1)
                  TextButton(
                    onPressed: _isLoading ? null : _resendOtp,
                    child: const Text('Kirim ulang kode OTP'),
                  ),
                TextButton(
                  onPressed: _isLoading ? null : () => Navigator.pop(context),
                  child: const Text('Batal'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
