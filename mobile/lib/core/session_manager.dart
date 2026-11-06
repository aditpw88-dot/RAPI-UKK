import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';
import 'constants.dart';

class SessionManager {
  static const _tokenKey = 'auth_token';
  static const _userKey = 'auth_user';
  static const _apiUrlKey = 'api_base_url';
  static const _legacyGuestKey = 'is_guest_siswa';

  static const _secureStorage = FlutterSecureStorage();

  static Future<String> getBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_apiUrlKey) ?? AppConstants.defaultLaragonVhostUrl;
  }

  static Future<void> saveBaseUrl(String url) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _apiUrlKey,
      url.trim().replaceAll(RegExp(r'/+$'), ''),
    );
  }

  static Future<void> saveSession({
    required String token,
    required UserModel user,
    required String baseUrl,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await _secureStorage.write(key: _tokenKey, value: token);
    await _secureStorage.write(key: _userKey, value: jsonEncode(user.toJson()));
    await prefs.setString(
      _apiUrlKey,
      baseUrl.trim().replaceAll(RegExp(r'/+$'), ''),
    );
    await prefs.remove(_legacyGuestKey);
  }

  static Future<Map<String, dynamic>> restoreSession() async {
    final prefs = await SharedPreferences.getInstance();
    final baseUrl =
        prefs.getString(_apiUrlKey) ?? AppConstants.defaultLaragonVhostUrl;
    final isLegacyGuest = prefs.getBool(_legacyGuestKey) ?? false;

    if (isLegacyGuest) {
      await clearSession();
      return {'token': null, 'user': null, 'baseUrl': baseUrl};
    }

    final token = await _secureStorage.read(key: _tokenKey);
    final userRaw = await _secureStorage.read(key: _userKey);

    if (token != null && userRaw != null) {
      try {
        final decoded = jsonDecode(userRaw);
        if (decoded is Map<String, dynamic>) {
          final user = UserModel.fromJson(decoded);
          if (user.isMobileAllowed) {
            return {'token': token, 'user': user, 'baseUrl': baseUrl};
          }
        }
      } catch (_) {
        await clearSession();
      }

      await clearSession();
    }

    return {'token': null, 'user': null, 'baseUrl': baseUrl};
  }

  static Future<void> clearSession() async {
    final prefs = await SharedPreferences.getInstance();
    await _secureStorage.delete(key: _tokenKey);
    await _secureStorage.delete(key: _userKey);
    await prefs.remove(_legacyGuestKey);
  }
}
