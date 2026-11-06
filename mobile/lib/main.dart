import 'package:flutter/material.dart';
import 'core/api_client.dart';
import 'core/constants.dart';
import 'core/session_manager.dart';
import 'models/user_model.dart';
import 'theme/app_theme.dart';
import 'views/auth/login_screen.dart';
import 'views/calendar/public_calendar_screen.dart';
import 'views/home/home_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const SchoolMobileApp());
}

class SchoolMobileApp extends StatefulWidget {
  const SchoolMobileApp({super.key});

  @override
  State<SchoolMobileApp> createState() => _SchoolMobileAppState();
}

class _SchoolMobileAppState extends State<SchoolMobileApp> {
  bool _isLoading = true;
  bool _showLogin = false;
  String? _token;
  UserModel? _user;
  String _baseUrl = AppConstants.defaultLaragonVhostUrl;

  @override
  void initState() {
    super.initState();
    _initApp();
  }

  Future<void> _initApp() async {
    final session = await SessionManager.restoreSession();
    if (!mounted) return;

    setState(() {
      _token = session['token'] as String?;
      _user = session['user'] as UserModel?;
      _baseUrl = session['baseUrl'] as String;
      _isLoading = false;
    });
  }

  void _onLoginSuccess(String token, UserModel user, String baseUrl) {
    setState(() {
      _token = token;
      _user = user;
      _baseUrl = baseUrl;
      _showLogin = false;
    });
  }

  void _onLogout() {
    setState(() {
      _token = null;
      _user = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: AppConstants.appTitle,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: _isLoading
          ? const Scaffold(
              body: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CircularProgressIndicator(color: AppTheme.primaryColor),
                    SizedBox(height: 16),
                    Text(
                      'Memuat Agenda Sekolah...',
                      style: TextStyle(
                        color: Color(0xFF64748B),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            )
          : (_user == null)
          ? _showLogin
                ? LoginScreen(
                    currentBaseUrl: _baseUrl,
                    onLoginSuccess: _onLoginSuccess,
                    onBackToCalendar: () => setState(() => _showLogin = false),
                  )
                : PublicCalendarScreen(
                    api: ApiClient(baseUrl: _baseUrl),
                    onLogin: () => setState(() => _showLogin = true),
                  )
          : HomeScreen(
              api: ApiClient(baseUrl: _baseUrl, token: _token),
              user: _user!,
              onLogout: _onLogout,
            ),
    );
  }
}
