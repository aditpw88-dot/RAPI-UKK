import 'api_client.dart';

/// Utilitas Kalender Nasional & Hari Libur Nasional Indonesia
/// Menyediakan data hari libur nasional secara offline instan + integrasi API dinamis.
class IndoHolidays {
  // Cache hari libur nasional format: YYYY-MM-DD -> Nama Hari Libur
  static final Map<String, String> _nationalHolidays = {
    // ── 2025 ───────────────────────────────────────────────────────────────
    '2025-01-01': 'Tahun Baru 2025 Masehi',
    '2025-01-27': 'Isra Mi\'raj Nabi Muhammad SAW',
    '2025-01-29': 'Tahun Baru Imlek 2576 Kongzili',
    '2025-03-29': 'Hari Suci Nyepi (Tahun Baru Saka 1947)',
    '2025-03-31': 'Hari Raya Idul Fitri 1446 H',
    '2025-04-01': 'Hari Raya Idul Fitri 1446 H (Hari Ke-2)',
    '2025-04-18': 'Wafat Yesus Kristus',
    '2025-04-20': 'Kebangkitan Yesus Kristus (Paskah)',
    '2025-05-01': 'Hari Buruh Internasional',
    '2025-05-12': 'Hari Raya Waisak 2569 BE',
    '2025-05-29': 'Kenaikan Yesus Kristus',
    '2025-06-01': 'Hari Lahir Pancasila',
    '2025-06-06': 'Hari Raya Idul Adha 1446 H',
    '2025-06-27': 'Tahun Baru Islam 1447 H',
    '2025-08-17': 'Hari Kemerdekaan Republik Indonesia Ke-80',
    '2025-09-05': 'Maulid Nabi Muhammad SAW',
    '2025-12-25': 'Hari Raya Natal',

    // ── 2026 (Tahun Aktif Sekolah RAPI) ───────────────────────────────────
    '2026-01-01': 'Tahun Baru 2026 Masehi',
    '2026-01-16': 'Isra Mi\'raj Nabi Muhammad SAW',
    '2026-02-17': 'Tahun Baru Imlek 2577 Kongzili',
    '2026-03-20': 'Hari Raya Idul Fitri 1447 H',
    '2026-03-21': 'Hari Raya Idul Fitri 1447 H (Hari Ke-2)',
    '2026-03-22': 'Cuti Bersama Idul Fitri 1447 H',
    '2026-03-23': 'Cuti Bersama Idul Fitri 1447 H',
    '2026-03-24': 'Hari Suci Nyepi (Tahun Baru Saka 1948)',
    '2026-04-03': 'Wafat Yesus Kristus (Jumat Agung)',
    '2026-04-05': 'Hari Paskah',
    '2026-05-01': 'Hari Buruh Internasional',
    '2026-05-14': 'Kenaikan Yesus Kristus',
    '2026-05-27': 'Hari Raya Idul Adha 1447 H',
    '2026-05-31': 'Hari Raya Waisak 2570 BE',
    '2026-06-01': 'Hari Lahir Pancasila',
    '2026-06-16': 'Tahun Baru Islam 1448 H (1 Muharram)',
    '2026-08-17': 'Hari Kemerdekaan Republik Indonesia Ke-81',
    '2026-08-25': 'Maulid Nabi Muhammad SAW',
    '2026-11-10': 'Hari Pahlawan Nasional',
    '2026-12-25': 'Hari Raya Natal',
    '2026-12-26': 'Cuti Bersama Hari Raya Natal',

    // ── 2027 ───────────────────────────────────────────────────────────────
    '2027-01-01': 'Tahun Baru 2027 Masehi',
    '2027-02-06': 'Tahun Baru Imlek 2578 Kongzili',
    '2027-03-09': 'Hari Raya Idul Fitri 1448 H',
    '2027-03-10': 'Hari Raya Idul Fitri 1448 H',
    '2027-05-01': 'Hari Buruh Internasional',
    '2027-06-01': 'Hari Lahir Pancasila',
    '2027-08-17': 'Hari Kemerdekaan RI Ke-82',
    '2027-12-25': 'Hari Raya Natal',
  };

  static String _formatKey(DateTime date) {
    final y = date.year.toString();
    final m = date.month < 10 ? '0${date.month}' : '${date.month}';
    final d = date.day < 10 ? '0${date.day}' : '${date.day}';
    return '$y-$m-$d';
  }

  /// Mengecek apakah suatu tanggal adalah Hari Libur Nasional (Tanggal Merah Resmi)
  static bool isNationalHoliday(DateTime date) {
    final key = _formatKey(date);
    if (_nationalHolidays.containsKey(key)) return true;

    // Hari libur tetap tahunan (Annual recurring)
    if (date.month == 1 && date.day == 1) return true; // Tahun Baru
    if (date.month == 5 && date.day == 1) return true; // Hari Buruh
    if (date.month == 6 && date.day == 1) return true; // Lahir Pancasila
    if (date.month == 8 && date.day == 17) return true; // HUT RI
    if (date.month == 12 && date.day == 25) return true; // Natal

    return false;
  }

  /// Mendapatkan nama Hari Libur Nasional jika ada
  static String? getHolidayName(DateTime date) {
    final key = _formatKey(date);
    if (_nationalHolidays.containsKey(key)) {
      return _nationalHolidays[key];
    }

    if (date.month == 1 && date.day == 1) return 'Tahun Baru Masehi';
    if (date.month == 5 && date.day == 1) return 'Hari Buruh Internasional';
    if (date.month == 6 && date.day == 1) return 'Hari Lahir Pancasila';
    if (date.month == 8 && date.day == 17) return 'Hari Kemerdekaan Republik Indonesia';
    if (date.month == 12 && date.day == 25) return 'Hari Raya Natal';

    return null;
  }

  /// Mengecek apakah suatu tanggal adalah hari libur (Sabtu, Minggu, atau Libur Nasional)
  static bool isHoliday(DateTime date) {
    // 6 = Sabtu, 7 = Minggu
    if (date.weekday == DateTime.saturday || date.weekday == DateTime.sunday) {
      return true;
    }
    return isNationalHoliday(date);
  }

  /// Daftar hari libur nasional pada bulan yang sedang dilihat
  static List<MapEntry<DateTime, String>> getHolidaysInMonth(DateTime month) {
    final result = <MapEntry<DateTime, String>>[];
    final daysInMonth = DateTime(month.year, month.month + 1, 0).day;

    for (int day = 1; day <= daysInMonth; day++) {
      final date = DateTime(month.year, month.month, day);
      final name = getHolidayName(date);
      if (name != null) {
        result.add(MapEntry(date, name));
      }
    }
    return result;
  }

  /// Sinkronisasi daftar hari libur dari backend secara asinkron tanpa memblok UI
  static Future<void> syncWithServer(ApiClient api, {DateTime? month}) async {
    try {
      final current = month ?? DateTime.now();
      final start = DateTime(current.year, current.month - 1, 1);
      final end = DateTime(current.year, current.month + 2, 0);

      final startStr = _formatKey(start);
      final endStr = _formatKey(end);

      final response = await api.get('/calendar/holidays?start=$startStr&end=$endStr');
      if (response is List) {
        for (final item in response) {
          if (item is Map) {
            final startVal = item['start']?.toString() ?? '';
            final titleVal = item['title']?.toString() ?? '';
            if (startVal.length >= 10 && titleVal.isNotEmpty) {
              final key = startVal.substring(0, 10);
              _nationalHolidays[key] = titleVal;
            }
          }
        }
      }
    } catch (_) {
      // Mengabaikan kegagalan jaringan secara tenang karena offline dataset sudah lengkap
    }
  }
}

