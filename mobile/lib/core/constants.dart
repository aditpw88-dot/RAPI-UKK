import 'package:flutter/material.dart';

class AppConstants {
  static const String appTitle = 'Agenda & Fasilitas Sekolah';

  // Default server URLs
  static const String defaultEmulatorUrl = 'http://10.0.2.2:8000/api';
  static const String defaultLocalhostUrl = 'http://127.0.0.1:8000/api';
  static const String defaultLaragonVhostUrl = 'http://ukk-aditya.test/api';
  static const String defaultLaragonPublicUrl = 'http://127.0.0.1/UKK-aditya/public/api';

  static const List<Map<String, String>> serverPresets = [
    {
      'label': 'Laragon Virtual Host (ukk-aditya.test)',
      'url': defaultLaragonVhostUrl,
      'desc': 'Rekomendasi untuk desktop / web yang terhubung ke Laragon.',
    },
    {
      'label': 'Laragon Public URL (127.0.0.1/UKK-aditya)',
      'url': defaultLaragonPublicUrl,
      'desc': 'Akses langsung ke folder public Apache Laragon.',
    },
    {
      'label': 'Artisan Serve Localhost (127.0.0.1:8000)',
      'url': defaultLocalhostUrl,
      'desc': 'Gunakan bila menjalankan "php artisan serve" di komputer.',
    },
    {
      'label': 'Android Emulator (10.0.2.2:8000)',
      'url': defaultEmulatorUrl,
      'desc': 'Akses host dari Android Emulator via artisan serve.',
    },
  ];

  // School sessions matching config/schedule.php
  static const Map<int, Map<String, String>> schoolSessions = {
    1: {'start': '07:15', 'end': '08:00'},
    2: {'start': '08:00', 'end': '08:45'},
    3: {'start': '08:45', 'end': '09:30'},
    4: {'start': '09:30', 'end': '10:15'},
    5: {'start': '10:15', 'end': '11:00'},
    6: {'start': '11:00', 'end': '11:45'},
    7: {'start': '11:45', 'end': '12:30'},
    8: {'start': '12:30', 'end': '13:15'},
    9: {'start': '13:15', 'end': '14:00'},
    10: {'start': '14:00', 'end': '14:45'},
    11: {'start': '14:45', 'end': '15:30'},
    12: {'start': '15:30', 'end': '16:15'},
    13: {'start': '16:15', 'end': '17:00'},
  };

  static String sessionLabel(int sessionNumber) {
    final session = schoolSessions[sessionNumber];
    if (session == null) return 'Sesi $sessionNumber';
    return 'Sesi $sessionNumber (${session['start']} - ${session['end']})';
  }

  // Repair Statuses
  static const List<String> repairStatuses = [
    'Belum Diperbaiki',
    'Menunggu Spare Part',
    'Sudah Diperbaiki',
  ];

  static Color statusColor(String? status) {
    final s = (status ?? '').toLowerCase();
    if (s.contains('sudah') || s.contains('approved') || s.contains('setujui')) {
      return const Color(0xFF10B981); // Emerald green
    }
    if (s.contains('spare part') || s.contains('menunggu') || s.contains('pending')) {
      return const Color(0xFFF59E0B); // Amber
    }
    if (s.contains('belum') || s.contains('reject') || s.contains('ditolak')) {
      return const Color(0xFFEF4444); // Red
    }
    return const Color(0xFF6B7280); // Gray
  }
}
