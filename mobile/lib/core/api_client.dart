import 'dart:convert';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode, this.data});

  final String message;
  final int? statusCode;
  final dynamic data;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({required this.baseUrl, this.token});

  final String baseUrl;
  final String? token;

  String get sanitizedBaseUrl => baseUrl.trim().replaceAll(RegExp(r'/+$'), '');

  Uri _uri(String path) {
    final cleanPath = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$sanitizedBaseUrl$cleanPath');
  }

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    if (token != null && token!.isNotEmpty) 'Authorization': 'Bearer $token',
  };

  /// Fix media URLs pointing to localhost when accessed from emulator / devices
  String resolveMediaUrl(String? url) {
    if (url == null || url.trim().isEmpty) return '';
    final parsed = Uri.tryParse(url);
    if (parsed == null) return url;

    final apiUri = Uri.parse(sanitizedBaseUrl);
    // If the image URL is pointing to localhost / 127.0.0.1 but API host is different (e.g. 10.0.2.2 or ukk-aditya.test)
    if ((parsed.host == 'localhost' || parsed.host == '127.0.0.1') &&
        apiUri.host != 'localhost' &&
        apiUri.host != '127.0.0.1') {
      return parsed
          .replace(
            scheme: apiUri.scheme,
            host: apiUri.host,
            port: apiUri.hasPort ? apiUri.port : null,
          )
          .toString();
    }
    return url;
  }

  Future<dynamic> get(String path) async {
    try {
      final response = await http
          .get(_uri(path), headers: _headers)
          .timeout(const Duration(seconds: 15));
      return _decodeResponse(response);
    } on http.ClientException catch (e) {
      throw ApiException('Gagal terhubung ke server: ${e.message}');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException('Terjadi kesalahan jaringan: $e');
    }
  }

  Future<dynamic> post(String path, [Map<String, dynamic>? body]) async {
    try {
      final response = await http
          .post(
            _uri(path),
            headers: {..._headers, 'Content-Type': 'application/json'},
            body: jsonEncode(body ?? {}),
          )
          .timeout(const Duration(seconds: 15));
      return _decodeResponse(response);
    } on http.ClientException catch (e) {
      throw ApiException('Gagal terhubung ke server: ${e.message}');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException('Terjadi kesalahan jaringan: $e');
    }
  }

  Future<dynamic> patch(String path, Map<String, dynamic> body) async {
    try {
      final response = await http
          .patch(
            _uri(path),
            headers: {..._headers, 'Content-Type': 'application/json'},
            body: jsonEncode(body),
          )
          .timeout(const Duration(seconds: 15));
      return _decodeResponse(response);
    } on http.ClientException catch (e) {
      throw ApiException('Gagal terhubung ke server: ${e.message}');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException('Terjadi kesalahan jaringan: $e');
    }
  }

  Future<dynamic> delete(String path) async {
    try {
      final response = await http
          .delete(_uri(path), headers: _headers)
          .timeout(const Duration(seconds: 15));
      return _decodeResponse(response);
    } on http.ClientException catch (e) {
      throw ApiException('Gagal terhubung ke server: ${e.message}');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException('Terjadi kesalahan jaringan: $e');
    }
  }

  Future<Uint8List> getBytes(String path) async {
    try {
      final response = await http
          .get(_uri(path), headers: _headers)
          .timeout(const Duration(seconds: 30));
      if (response.statusCode < 200 || response.statusCode >= 300) {
        _decodeResponse(response);
      }
      return response.bodyBytes;
    } on http.ClientException catch (e) {
      throw ApiException('Gagal terhubung ke server: ${e.message}');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException('Terjadi kesalahan jaringan: $e');
    }
  }

  Future<dynamic> uploadComplaint({
    required int roomId,
    required String description,
    required XFile photo,
  }) async {
    try {
      final request = http.MultipartRequest('POST', _uri('/complaints'))
        ..headers.addAll(_headers)
        ..fields['room_id'] = '$roomId'
        ..fields['description'] = description
        ..files.add(await http.MultipartFile.fromPath('photo', photo.path));

      final streamedResponse = await request
          .send()
          .timeout(const Duration(seconds: 30));
      final response = await http.Response.fromStream(streamedResponse);
      return _decodeResponse(response);
    } on http.ClientException catch (e) {
      throw ApiException('Gagal mengunggah pengaduan: ${e.message}');
    } catch (e) {
      if (e is ApiException) rethrow;
      throw ApiException('Gagal memproses unggahan foto: $e');
    }
  }

  dynamic _decodeResponse(http.Response response) {
    dynamic body;
    if (response.body.isNotEmpty) {
      try {
        body = jsonDecode(response.body);
      } catch (_) {
        body = null;
      }
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      String? errorMessage;
      if (body is Map) {
        if (body['message'] != null) {
          errorMessage = '${body['message']}';
        }
        if (body['errors'] is Map) {
          final errorsMap = body['errors'] as Map;
          if (errorsMap.isNotEmpty) {
            final firstErr = errorsMap.values.first;
            if (firstErr is List && firstErr.isNotEmpty) {
              errorMessage = '${firstErr.first}';
            } else if (firstErr != null) {
              errorMessage = '$firstErr';
            }
          }
        }
      }

      errorMessage ??= 'Permintaan gagal dengan kode HTTP ${response.statusCode}';
      throw ApiException(errorMessage, statusCode: response.statusCode, data: body);
    }

    return body;
  }
}
