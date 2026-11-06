class IndoDateUtils {
  static const List<String> _days = [
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
    'Minggu',
  ];

  static const List<String> _months = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ];

  static const List<String> _shortMonths = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des',
  ];

  static String formatFull(DateTime dt) {
    final dayName = _days[dt.weekday - 1];
    final monthName = _months[dt.month - 1];
    return '$dayName, ${dt.day} $monthName ${dt.year}';
  }

  static String formatShort(DateTime dt) {
    final monthName = _shortMonths[dt.month - 1];
    return '${dt.day} $monthName ${dt.year}';
  }

  static String formatDateRange(String? startDate, String? endDate) {
    final start = DateTime.tryParse(startDate ?? '');
    if (start == null) return '-';

    final end = DateTime.tryParse(endDate ?? '') ?? start;
    final startLabel = formatShort(start);
    return start.year == end.year &&
            start.month == end.month &&
            start.day == end.day
        ? startLabel
        : '$startLabel - ${formatShort(end)}';
  }

  static String formatIsoDate(DateTime dt) {
    final y = dt.year.toString().padLeft(4, '0');
    final m = dt.month.toString().padLeft(2, '0');
    final d = dt.day.toString().padLeft(2, '0');
    return '$y-$m-$d';
  }

  static String formatString(String? dateString) {
    if (dateString == null || dateString.trim().isEmpty) return '-';
    try {
      final parsed = DateTime.parse(dateString);
      return formatFull(parsed);
    } catch (_) {
      return dateString;
    }
  }

  static String formatStringShort(String? dateString) {
    if (dateString == null || dateString.trim().isEmpty) return '-';
    try {
      final parsed = DateTime.parse(dateString);
      return formatShort(parsed);
    } catch (_) {
      return dateString;
    }
  }

  static String formatTimeRange(String? start, String? end) {
    if (start == null && end == null) return '-';
    final s = _trimSeconds(start);
    final e = _trimSeconds(end);
    return '$s - $e WIB';
  }

  static String _trimSeconds(String? time) {
    if (time == null || time.isEmpty) return '--:--';
    final parts = time.split(':');
    if (parts.length >= 2) {
      return '${parts[0].padLeft(2, '0')}:${parts[1].padLeft(2, '0')}';
    }
    return time;
  }
}
