import 'dart:convert';
import 'package:flutter/foundation.dart';
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

  /// Menulis ke storage dengan aman. Pada web atau jika Web Crypto API tidak tersedia,
  /// gunakan SharedPreferences (localStorage) untuk menghindari error 'generateKey'.
  static Future<void> _writeStorage(String key, String value) async {
    final prefs = await SharedPreferences.getInstance();
    if (kIsWeb) {
      await prefs.setString(key, value);
      return;
    }
    try {
      await _secureStorage.write(key: key, value: value);
    } catch (_) {
      await prefs.setString(key, value);
    }
  }

  /// Membaca dari storage. Menggunakan SharedPreferences pada web.
  static Future<String?> _readStorage(String key) async {
    final prefs = await SharedPreferences.getInstance();
    if (kIsWeb) {
      return prefs.getString(key);
    }
    try {
      final val = await _secureStorage.read(key: key);
      return val ?? prefs.getString(key);
    } catch (_) {
      return prefs.getString(key);
    }
  }

  /// Menghapus dari storage.
  static Future<void> _deleteStorage(String key) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(key);
    if (!kIsWeb) {
      try {
        await _secureStorage.delete(key: key);
      } catch (_) {}
    }
  }

  static Future<String> getBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_apiUrlKey) ?? AppConstants.defaultAppUrl;
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
    await _writeStorage(_tokenKey, token);
    await _writeStorage(_userKey, jsonEncode(user.toJson()));
    await prefs.setString(
      _apiUrlKey,
      baseUrl.trim().replaceAll(RegExp(r'/+$'), ''),
    );
    await prefs.remove(_legacyGuestKey);
  }

  static Future<Map<String, dynamic>> restoreSession() async {
    final prefs = await SharedPreferences.getInstance();
    final baseUrl =
        prefs.getString(_apiUrlKey) ?? AppConstants.defaultAppUrl;
    final isLegacyGuest = prefs.getBool(_legacyGuestKey) ?? false;

    if (isLegacyGuest) {
      await clearSession();
      return {'token': null, 'user': null, 'baseUrl': baseUrl};
    }

    try {
      final token = await _readStorage(_tokenKey);
      final userRaw = await _readStorage(_userKey);

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
    } catch (_) {
      // Keystores/Storage error fallback
    }

    return {'token': null, 'user': null, 'baseUrl': baseUrl};
  }

  static Future<void> clearSession() async {
    final prefs = await SharedPreferences.getInstance();
    await _deleteStorage(_tokenKey);
    await _deleteStorage(_userKey);
    await prefs.remove(_legacyGuestKey);
  }
}
