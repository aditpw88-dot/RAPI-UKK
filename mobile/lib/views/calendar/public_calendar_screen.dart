import 'dart:async';
import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../core/date_utils.dart';
import '../../core/indo_holidays.dart';
import '../../models/event_model.dart';
import '../../theme/app_theme.dart';

enum EventLifecycle {
  inProgress(0, 'Sedang Terlaksana'),
  upcoming(1, 'Belum Terlaksana'),
  completed(2, 'Sudah Terlaksana');

  final int sortOrder;
  final String label;
  const EventLifecycle(this.sortOrder, this.label);
}

class PublicCalendarScreen extends StatefulWidget {
  const PublicCalendarScreen({
    required this.api,
    this.onLogin,
    this.showAppBar = true,
    super.key,
  });

  final ApiClient api;
  final VoidCallback? onLogin;
  final bool showAppBar;

  @override
  State<PublicCalendarScreen> createState() => _PublicCalendarScreenState();
}

class _PublicCalendarScreenState extends State<PublicCalendarScreen> {
  static const _indoMonths = [
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
    'Desember'
  ];

  DateTime _visibleMonth = DateTime(DateTime.now().year, DateTime.now().month);
  DateTime _selectedDate = DateTime.now();

  List<EventModel> _events = [];

  // Cache O(1) untuk optimasi performa ultra-ringan di HP (Zero lag)
  final Map<String, List<EventModel>> _eventsByDateCache = {};
  final Set<String> _datesWithEventsCache = {};
  final Map<int, EventLifecycle> _lifecycleCache = {};
  final Map<int, String> _searchIndexCache = {};

  String? _loadError;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadEvents();
    IndoHolidays.syncWithServer(widget.api, month: _visibleMonth);
  }

  @override
  void dispose() {
    super.dispose();
  }

  String _dateKey(DateTime d) {
    final y = d.year.toString();
    final m = d.month < 10 ? '0${d.month}' : '${d.month}';
    final day = d.day < 10 ? '0${d.day}' : '${d.day}';
    return '$y-$m-$day';
  }

  EventLifecycle _computeLifecycle(EventModel event, DateTime now) {
    final startDate = DateTime.tryParse(event.date);
    if (startDate == null) return EventLifecycle.upcoming;

    final endDate = DateTime.tryParse(event.endDate) ?? startDate;

    int startHour = 0;
    int startMinute = 0;
    final startParts = event.startTime.split(':');
    if (startParts.isNotEmpty) {
      startHour = int.tryParse(startParts[0]) ?? 0;
      if (startParts.length > 1) {
        startMinute = int.tryParse(startParts[1]) ?? 0;
      }
    }

    int endHour = 23;
    int endMinute = 59;
    final endParts = event.endTime.split(':');
    if (endParts.isNotEmpty) {
      endHour = int.tryParse(endParts[0]) ?? 23;
      if (endParts.length > 1) {
        endMinute = int.tryParse(endParts[1]) ?? 59;
      }
    }

    final startDt = DateTime(
      startDate.year,
      startDate.month,
      startDate.day,
      startHour,
      startMinute,
    );
    final endDt = DateTime(
      endDate.year,
      endDate.month,
      endDate.day,
      endHour,
      endMinute,
    );

    if (now.isAfter(endDt) || now.isAtSameMomentAs(endDt)) {
      return EventLifecycle.completed; // 3. Sudah Terlaksana
    } else if (now.isBefore(startDt)) {
      return EventLifecycle.upcoming; // 2. Belum Terlaksana
    } else {
      return EventLifecycle.inProgress; // 1. Sedang Terlaksana
    }
  }

  void _rebuildEventCache(List<EventModel> events) {
    _eventsByDateCache.clear();
    _datesWithEventsCache.clear();
    _lifecycleCache.clear();
    _searchIndexCache.clear();

    final now = DateTime.now();

    // Saring agenda yang sudah selesai di jam-jam sebelumnya dari tampilan kalender
    final activeEvents = events.where((event) {
      final lc = _computeLifecycle(event, now);
      return lc != EventLifecycle.completed;
    }).toList();

    // 1. Pre-compute lifecycle & teks pencarian sekali saja
    for (final event in activeEvents) {
      _lifecycleCache[event.id] = _computeLifecycle(event, now);
      _searchIndexCache[event.id] =
          '${event.eventName} ${event.roomName ?? ''}'.toLowerCase();
    }

    // 2. Pre-sort agenda sekali saja saat data dimuat (O(N log N) sekali saja, bukan tiap frame!)
    // Urutan prioritas:
    // 1. Sedang Terlaksana
    // 2. Belum Terlaksana
    // Kemudian tanggal & waktu mulai
    activeEvents.sort((a, b) {
      final lcA = _lifecycleCache[a.id] ?? EventLifecycle.upcoming;
      final lcB = _lifecycleCache[b.id] ?? EventLifecycle.upcoming;
      if (lcA.sortOrder != lcB.sortOrder) {
        return lcA.sortOrder.compareTo(lcB.sortOrder);
      }
      final dateCmp = a.date.compareTo(b.date);
      if (dateCmp != 0) return dateCmp;
      return a.startTime.compareTo(b.startTime);
    });

    // 3. Masukkan ke dalam map tanggal dalam keadaan sudah terurut rapi
    for (final event in activeEvents) {
      final start = DateTime.tryParse(event.date);
      if (start == null) continue;
      final end = DateTime.tryParse(event.endDate) ?? start;

      var current = DateTime(start.year, start.month, start.day);
      final finish = DateTime(end.year, end.month, end.day);

      while (!current.isAfter(finish)) {
        final key = _dateKey(current);
        _datesWithEventsCache.add(key);
        (_eventsByDateCache[key] ??= []).add(event);
        current = current.add(const Duration(days: 1));
      }
    }
  }

  Future<void> _loadEvents() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });

    try {
      final response = await widget.api.get('/events');
      if (response is! Map || response['data'] is! List) {
        throw const FormatException('Format data agenda dari server tidak valid.');
      }

      final events = (response['data'] as List).map((item) {
        if (item is! Map) {
          throw const FormatException('Data agenda dari server tidak valid.');
        }
        return EventModel.fromJson(Map<String, dynamic>.from(item));
      }).toList();

      _rebuildEventCache(events);

      if (!mounted) return;
      setState(() {
        _events = events;
        _isLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loadError = error.toString();
        _isLoading = false;
      });
    }
  }

  List<EventModel> _eventsFor(DateTime date) {
    return _eventsByDateCache[_dateKey(date)] ?? const [];
  }

  void _changeMonth(int offset) {
    setState(() {
      final nextMonth = DateTime(
        _visibleMonth.year,
        _visibleMonth.month + offset,
      );
      final daysInNextMonth = DateTime(
        nextMonth.year,
        nextMonth.month + 1,
        0,
      ).day;
      _visibleMonth = nextMonth;
      _selectedDate = DateTime(
        nextMonth.year,
        nextMonth.month,
        _selectedDate.day > daysInNextMonth
            ? daysInNextMonth
            : _selectedDate.day,
      );
    });
    IndoHolidays.syncWithServer(widget.api, month: _visibleMonth);
  }

  void _setMonthYear(int year, int month) {
    setState(() {
      final daysInTargetMonth = DateTime(year, month + 1, 0).day;
      _visibleMonth = DateTime(year, month);
      _selectedDate = DateTime(
        year,
        month,
        _selectedDate.day > daysInTargetMonth
            ? daysInTargetMonth
            : _selectedDate.day,
      );
    });
    IndoHolidays.syncWithServer(widget.api, month: _visibleMonth);
  }

  void _goToToday() {
    final now = DateTime.now();
    setState(() {
      _visibleMonth = DateTime(now.year, now.month);
      _selectedDate = now;
    });
    IndoHolidays.syncWithServer(widget.api, month: _visibleMonth);
  }

  void _openMonthPicker() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        String query = '';
        return StatefulBuilder(
          builder: (context, setModalState) {
            final filteredMonths = <MapEntry<int, String>>[];
            for (int i = 0; i < _indoMonths.length; i++) {
              final mName = _indoMonths[i];
              if (query.isEmpty ||
                  mName.toLowerCase().contains(query.toLowerCase()) ||
                  '${i + 1}'.contains(query)) {
                filteredMonths.add(MapEntry(i + 1, mName));
              }
            }

            return Container(
              padding: EdgeInsets.fromLTRB(
                16,
                14,
                16,
                MediaQuery.of(context).viewInsets.bottom + 20,
              ),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: const Color(0xFFCBD5E1),
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.calendar_month, size: 20, color: Color(0xFFEA580C)),
                          SizedBox(width: 8),
                          Text(
                            'Pilih Bulan',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF0F172A),
                            ),
                          ),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppTheme.brandAmberLight,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          'Tahun ${_visibleMonth.year}',
                          style: TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.brandAmber,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  // Search Box Bulan
                  TextField(
                    autofocus: false,
                    decoration: InputDecoration(
                      hintText: 'Cari bulan (cth: Mei, Nov, 8)...',
                      hintStyle: const TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8)),
                      prefixIcon: const Icon(Icons.search, size: 18, color: Color(0xFF94A3B8)),
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: BorderSide(color: AppTheme.brandAmber, width: 1.5),
                      ),
                    ),
                    onChanged: (val) {
                      setModalState(() {
                        query = val.trim();
                      });
                    },
                  ),
                  const SizedBox(height: 14),
                  // Grid of 12 months
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: filteredMonths.map((entry) {
                      final monthNum = entry.key;
                      final monthTitle = entry.value;
                      final isSelected = _visibleMonth.month == monthNum;
                      final isThisMonth = DateTime.now().year == _visibleMonth.year &&
                          DateTime.now().month == monthNum;

                      return SizedBox(
                        width: (MediaQuery.of(context).size.width - 48) / 3,
                        child: InkWell(
                          onTap: () {
                            Navigator.pop(ctx);
                            _setMonthYear(_visibleMonth.year, monthNum);
                          },
                          borderRadius: BorderRadius.circular(10),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            decoration: BoxDecoration(
                              color: isSelected
                                  ? AppTheme.brandAmber
                                  : isThisMonth
                                      ? AppTheme.brandAmberLight
                                      : const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(
                                color: isSelected
                                    ? AppTheme.brandAmber
                                    : isThisMonth
                                        ? AppTheme.brandAmber.withValues(alpha: 0.4)
                                        : const Color(0xFFE2E8F0),
                              ),
                            ),
                            alignment: Alignment.center,
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  monthTitle,
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                                    color: isSelected
                                        ? Colors.white
                                        : isThisMonth
                                            ? AppTheme.brandAmber
                                            : const Color(0xFF1E293B),
                                  ),
                                ),
                                if (isThisMonth && !isSelected) ...[
                                  const SizedBox(height: 2),
                                  Text(
                                    'Bulan ini',
                                    style: TextStyle(
                                      fontSize: 9.5,
                                      fontWeight: FontWeight.w700,
                                      color: AppTheme.brandAmber,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  void _openYearPicker() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        String query = '';
        final currentYear = DateTime.now().year;
        // Rentang tahun awal yang luas (dari 10 tahun lalu hingga 35 tahun ke depan: cth 2016 s/d 2061)
        final defaultStartYear = currentYear - 10;
        final baseYears = List.generate(46, (index) => defaultStartYear + index);

        return StatefulBuilder(
          builder: (context, setModalState) {
            final trimmedQuery = query.trim();
            final parsedYear = int.tryParse(trimmedQuery);

            final Set<int> allYears = baseYears.toSet();
            allYears.add(_visibleMonth.year);
            allYears.add(currentYear);

            // Dinamis: Jika pengguna mengetik tahun apapun (cth: 2038, 2040, 2050, 2080, dll)
            // maka tahun tersebut otomatis masuk ke daftar dan tidak terbatas hanya s/d 2037
            if (parsedYear != null && parsedYear > 0 && parsedYear <= 9999) {
              allYears.add(parsedYear);
            }

            final sortedYears = allYears.toList()..sort();

            final filteredYears = sortedYears.where((y) {
              return trimmedQuery.isEmpty || '$y'.contains(trimmedQuery);
            }).toList();

            return Container(
              padding: EdgeInsets.fromLTRB(
                16,
                14,
                16,
                MediaQuery.of(context).viewInsets.bottom + 20,
              ),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: const Color(0xFFCBD5E1),
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.schedule, size: 20, color: Color(0xFFEA580C)),
                          SizedBox(width: 8),
                          Text(
                            'Pilih Tahun',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF0F172A),
                            ),
                          ),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppTheme.brandAmberLight,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          _indoMonths[_visibleMonth.month - 1],
                          style: TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.brandAmber,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  // Search Box Tahun (Dapat diketik bebas, misal: 2038, 2040, 2050, dll)
                  TextField(
                    autofocus: false,
                    keyboardType: TextInputType.number,
                    textInputAction: TextInputAction.done,
                    decoration: InputDecoration(
                      hintText: 'Ketik tahun (cth: 2026, 2038, 2045)...',
                      hintStyle: const TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8)),
                      prefixIcon: const Icon(Icons.search, size: 18, color: Color(0xFF94A3B8)),
                      suffixIcon: query.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 16),
                              onPressed: () {
                                setModalState(() {
                                  query = '';
                                });
                              },
                            )
                          : null,
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: BorderSide(color: AppTheme.brandAmber, width: 1.5),
                      ),
                    ),
                    onChanged: (val) {
                      setModalState(() {
                        query = val;
                      });
                    },
                    onSubmitted: (val) {
                      final y = int.tryParse(val.trim());
                      if (y != null && y > 0 && y <= 9999) {
                        Navigator.pop(ctx);
                        _setMonthYear(y, _visibleMonth.month);
                      }
                    },
                  ),
                  const SizedBox(height: 14),
                  ConstrainedBox(
                    constraints: const BoxConstraints(maxHeight: 280),
                    child: filteredYears.isEmpty
                        ? Padding(
                            padding: const EdgeInsets.symmetric(vertical: 20),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.search_off, size: 36, color: Color(0xFF94A3B8)),
                                const SizedBox(height: 8),
                                Text(
                                  'Tahun "$query" tidak ditemukan',
                                  style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                                  textAlign: TextAlign.center,
                                ),
                                if (parsedYear != null && parsedYear > 0) ...[
                                  const SizedBox(height: 12),
                                  ElevatedButton.icon(
                                    onPressed: () {
                                      Navigator.pop(ctx);
                                      _setMonthYear(parsedYear, _visibleMonth.month);
                                    },
                                    icon: const Icon(Icons.check, size: 16),
                                    label: Text('Gunakan Tahun $parsedYear'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppTheme.brandAmber,
                                      foregroundColor: Colors.white,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          )
                        : SingleChildScrollView(
                            child: Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: filteredYears.map((year) {
                                final isSelected = _visibleMonth.year == year;
                                final isCurrentYear = currentYear == year;

                                return SizedBox(
                                  width: (MediaQuery.of(context).size.width - 48) / 3,
                                  child: InkWell(
                                    onTap: () {
                                      Navigator.pop(ctx);
                                      _setMonthYear(year, _visibleMonth.month);
                                    },
                                    borderRadius: BorderRadius.circular(10),
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(vertical: 12),
                                      decoration: BoxDecoration(
                                        color: isSelected
                                            ? AppTheme.brandAmber
                                            : isCurrentYear
                                                ? AppTheme.brandAmberLight
                                                : const Color(0xFFF8FAFC),
                                        borderRadius: BorderRadius.circular(10),
                                        border: Border.all(
                                          color: isSelected
                                              ? AppTheme.brandAmber
                                              : isCurrentYear
                                                  ? AppTheme.brandAmber.withValues(alpha: 0.4)
                                                  : const Color(0xFFE2E8F0),
                                        ),
                                      ),
                                      alignment: Alignment.center,
                                      child: Column(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(
                                            '$year',
                                            style: TextStyle(
                                              fontSize: 14,
                                              fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                                              color: isSelected
                                                  ? Colors.white
                                                  : isCurrentYear
                                                      ? AppTheme.brandAmber
                                                      : const Color(0xFF1E293B),
                                            ),
                                          ),
                                          if (isCurrentYear && !isSelected) ...[
                                            const SizedBox(height: 2),
                                            Text(
                                              'Tahun ini',
                                              style: TextStyle(
                                                fontSize: 9.5,
                                                fontWeight: FontWeight.w700,
                                                color: AppTheme.brandAmber,
                                              ),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                  ),
                                );
                              }).toList(),
                            ),
                          ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  void _showDateAgendaModal(DateTime date) {
    final events = _eventsFor(date);
    showDialog<void>(
      context: context,
      builder: (ctx) => _DateAgendaDialog(
        date: date,
        events: events,
        onEventTap: _showEventDetailModal,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final selectedEvents = _eventsFor(_selectedDate);

    return Scaffold(
      backgroundColor: AppTheme.scaffoldBg,
      appBar: widget.showAppBar
          ? AppBar(
              flexibleSpace: Container(
                decoration: BoxDecoration(
                  gradient: AppTheme.headerGradient,
                  border: Border(
                    bottom: BorderSide(color: AppTheme.headerBorder, width: 2),
                  ),
                ),
              ),
              elevation: 2,
              titleSpacing: 14,
              title: Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(9),
                      border: Border.all(
                        color: AppTheme.brandGold.withValues(alpha: 0.35),
                        width: 1,
                      ),
                    ),
                    padding: const EdgeInsets.all(2.5),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(7),
                      child: Image.asset(
                        'assets/images/logo.png',
                        fit: BoxFit.contain,
                        errorBuilder: (_, __, ___) => Container(
                          decoration: BoxDecoration(
                            color: AppTheme.brandAmber,
                            borderRadius: BorderRadius.circular(7),
                          ),
                          alignment: Alignment.center,
                          child: const Icon(
                            Icons.school_rounded,
                            size: 18,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'Kalender Sekolah',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: AppTheme.brandGold,
                          letterSpacing: -0.3,
                        ),
                      ),
                      Text(
                        'Sistem Peminjaman & Agenda Ruang RAPI',
                        style: TextStyle(
                          fontSize: 10.5,
                          fontWeight: FontWeight.w500,
                          color: AppTheme.brandGoldSub,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              actions: [
                if (widget.onLogin != null)
                  Padding(
                    padding: const EdgeInsets.only(right: 12),
                    child: ElevatedButton.icon(
                      onPressed: widget.onLogin,
                      icon: const Icon(Icons.login, size: 14),
                      label: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text('Masuk'),
                          SizedBox(width: 4),
                          Icon(Icons.arrow_forward, size: 13),
                        ],
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppTheme.brandAmber,
                        foregroundColor: Colors.white,
                        elevation: 1,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 7,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                          side: const BorderSide(
                            color: Color(0xFFFBBF24),
                            width: 0.8,
                          ),
                        ),
                        textStyle: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
              ],
            )
          : null,
      body: RefreshIndicator(
        color: AppTheme.brandAmber,
        onRefresh: () async {
          await _loadEvents();
          await IndoHolidays.syncWithServer(widget.api, month: _visibleMonth);
        },
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(
            parent: ClampingScrollPhysics(),
          ),
          padding: const EdgeInsets.fromLTRB(14, 12, 14, 28),
          children: [
            // Kalender Card Interaktif & Anti-Lag (Pure Column/Row, 60 FPS)
            _buildCalendarCard(),
            const SizedBox(height: 14),

            // Card Tanggal Terpilih & Tombol Buka Agenda Modal
            _buildSelectedDateCard(selectedEvents),
            const SizedBox(height: 14),

            // Card Hari Libur Nasional Bulan Ini (Jika Ada)
            _buildMonthlyHolidaysCard(),
            const SizedBox(height: 14),

            // Card Legenda Kategori & Status Agenda (Sesuai Web)
            _buildLegendCard(),
          ],
        ),
      ),
    );
  }

  /// Card Tanggal Terpilih & Tombol Aksi Buka Modal
  Widget _buildSelectedDateCard(List<EventModel> events) {
    final isHoliday = IndoHolidays.isNationalHoliday(_selectedDate);
    final holidayName = IndoHolidays.getHolidayName(_selectedDate);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE4E4E7)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x06000000),
            blurRadius: 4,
            offset: Offset(0, 1),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: isHoliday
                      ? const Color(0xFFFEE2E2)
                      : AppTheme.brandAmberLight,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  isHoliday ? Icons.flag_outlined : Icons.calendar_month,
                  color: isHoliday ? const Color(0xFFDC2626) : AppTheme.brandAmber,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Agenda ${IndoDateUtils.formatFull(_selectedDate)}',
                      style: const TextStyle(
                        fontSize: 14.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                    const SizedBox(height: 3),
                    if (isHoliday && holidayName != null) ...[
                      Text(
                        '🚩 Libur Nasional: $holidayName',
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFFDC2626),
                        ),
                      ),
                      const SizedBox(height: 2),
                    ],
                    Text(
                      events.isEmpty
                          ? 'Tidak ada agenda kegiatan pada tanggal ini'
                          : '${events.length} agenda kegiatan tersimpan',
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF64748B),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: () => _showDateAgendaModal(_selectedDate),
              icon: const Icon(Icons.open_in_new, size: 16),
              label: Text(
                events.isEmpty
                    ? 'Lihat Agenda Tanggal Ini'
                    : 'Buka Agenda (${events.length} Agenda)',
              ),
              style: FilledButton.styleFrom(
                backgroundColor: AppTheme.brandAmber,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Card Hari Libur Nasional Pada Bulan Ini
  Widget _buildMonthlyHolidaysCard() {
    final holidays = IndoHolidays.getHolidaysInMonth(_visibleMonth);
    if (holidays.isEmpty) return const SizedBox.shrink();

    final monthName =
        '${_indoMonths[_visibleMonth.month - 1]} ${_visibleMonth.year}';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFFECACA)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text('🚩', style: TextStyle(fontSize: 16)),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Hari Libur Nasional $monthName',
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF991B1B),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          ...holidays.map((entry) {
            final date = entry.key;
            final name = entry.value;
            final dateStr = IndoDateUtils.formatShort(date);
            return InkWell(
              onTap: () {
                setState(() => _selectedDate = date);
                _showDateAgendaModal(date);
              },
              borderRadius: BorderRadius.circular(8),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 4),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEE2E2),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        dateStr,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFFDC2626),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        name,
                        style: const TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                    ),
                    const Icon(
                      Icons.chevron_right,
                      size: 16,
                      color: Color(0xFF94A3B8),
                    ),
                  ],
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  /// Card Legenda Kategori & Status Agenda Sesuai Web
  Widget _buildLegendCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE4E4E7)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Keterangan Kategori & Status Agenda',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: Color(0xFF0F172A),
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _buildLegendChip(
                'Rapat / Koordinasi',
                AppTheme.categoryAccent('Rapat / Koordinasi'),
              ),
              _buildLegendChip(
                'Akademik / Ujian',
                AppTheme.categoryAccent('Akademik / Ujian'),
              ),
              _buildLegendChip(
                'Kegiatan Sekolah',
                AppTheme.categoryAccent('Kegiatan Sekolah'),
              ),
              _buildLegendChip(
                'Upacara',
                AppTheme.categoryAccent('Upacara'),
              ),
              _buildLegendChip(
                'Lainnya',
                AppTheme.categoryAccent('Lainnya'),
              ),
              _buildLegendChip(
                'Sedang Terlaksana',
                const Color(0xFF10B981),
              ),
              _buildLegendChip(
                'Libur Nasional',
                const Color(0xFFDC2626),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildLegendChip(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 7,
            height: 7,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  /// Kalender Card Lebih Besar, Lega & Jelas (Zero-Grid, Ultra-Smooth 60FPS)
  Widget _buildCalendarCard() {
    final firstDay = DateTime(_visibleMonth.year, _visibleMonth.month, 1);
    final daysInMonth = DateTime(
      _visibleMonth.year,
      _visibleMonth.month + 1,
      0,
    ).day;
    final startingOffset = (firstDay.weekday - 1) % 7;
    final monthName =
        '${_indoMonths[_visibleMonth.month - 1]} ${_visibleMonth.year}';
    final numWeeks = ((startingOffset + daysInMonth) / 7).ceil();
    final todayKey = _dateKey(DateTime.now());
    final selectedKey = _dateKey(_selectedDate);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE4E4E7)),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      child: Column(
        children: [
          // Toolbar: [Hari Ini] di kiri, [Bulan ▾] & [Tahun ▾] di kanan
          Row(
            children: [
              // Tombol "Hari Ini"
              GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: _goToToday,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5.5),
                  decoration: BoxDecoration(
                    color: AppTheme.brandAmberLight,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: AppTheme.brandAmber.withValues(alpha: 0.35),
                      width: 1,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.today_rounded, size: 14, color: AppTheme.brandAmber),
                      const SizedBox(width: 5),
                      Text(
                        'Hari Ini',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: AppTheme.brandAmber,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const Spacer(),
              // Selector Bulan
              InkWell(
                onTap: _openMonthPicker,
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.calendar_month, size: 13, color: Color(0xFF64748B)),
                      const SizedBox(width: 4),
                      Text(
                        _indoMonths[_visibleMonth.month - 1],
                        style: const TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      const SizedBox(width: 2),
                      const Icon(Icons.keyboard_arrow_down, size: 15, color: Color(0xFF64748B)),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 6),
              // Selector Tahun
              InkWell(
                onTap: _openYearPicker,
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.schedule, size: 13, color: Color(0xFF64748B)),
                      const SizedBox(width: 4),
                      Text(
                        '${_visibleMonth.year}',
                        style: const TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      const SizedBox(width: 2),
                      const Icon(Icons.keyboard_arrow_down, size: 15, color: Color(0xFF64748B)),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 8),

          // Header Bulan & Navigasi Stepper (< Bulan Tahun >)
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              IconButton(
                visualDensity: VisualDensity.compact,
                tooltip: 'Bulan sebelumnya',
                onPressed: () => _changeMonth(-1),
                icon: Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(9),
                    border: Border.all(color: const Color(0xFFE4E4E7)),
                  ),
                  alignment: Alignment.center,
                  child: const Icon(
                    Icons.chevron_left,
                    size: 20,
                    color: Color(0xFF3F3F46),
                  ),
                ),
              ),
              Expanded(
                child: InkWell(
                  onTap: _openMonthPicker,
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 2),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          monthName,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF09090B),
                            letterSpacing: -0.2,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 1),
                        const Text(
                          'Pilih tanggal untuk melihat agenda',
                          style: TextStyle(
                            fontSize: 11,
                            color: Color(0xFFA1A1AA),
                            fontWeight: FontWeight.w500,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              IconButton(
                visualDensity: VisualDensity.compact,
                tooltip: 'Bulan berikutnya',
                onPressed: () => _changeMonth(1),
                icon: Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(9),
                    border: Border.all(color: const Color(0xFFE4E4E7)),
                  ),
                  alignment: Alignment.center,
                  child: const Icon(
                    Icons.chevron_right,
                    size: 20,
                    color: Color(0xFF3F3F46),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),

          // Label Hari (Sen - Min: Sabtu dan Minggu diblok merah tegas)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 4),
            child: Row(
              children: [
                Expanded(
                  child: Center(
                    child: Text(
                      'Sen',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFA1A1AA),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: Center(
                    child: Text(
                      'Sel',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFA1A1AA),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: Center(
                    child: Text(
                      'Rab',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFA1A1AA),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: Center(
                    child: Text(
                      'Kam',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFA1A1AA),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: Center(
                    child: Text(
                      'Jum',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFA1A1AA),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: Center(
                    child: Text(
                      'Sab',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFDC2626),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: Center(
                    child: Text(
                      'Min',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFDC2626),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 4),

          // Matriks Tanggal Lebih Luas & Jelas (Tinggi cell 46px, lingkaran 35px, angka 13.5)
          Column(
            children: List.generate(numWeeks, (weekIndex) {
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 1.5),
                child: Row(
                  children: List.generate(7, (colIndex) {
                    final cellIndex = weekIndex * 7 + colIndex;
                    final day = cellIndex - startingOffset + 1;
                    if (day < 1 || day > daysInMonth) {
                      return const Expanded(child: SizedBox(height: 46));
                    }

                    final isWeekend = colIndex == 5 || colIndex == 6; // Sab & Min
                    final date = DateTime(
                      _visibleMonth.year,
                      _visibleMonth.month,
                      day,
                    );
                    final isNatHoliday = IndoHolidays.isNationalHoliday(date);
                    final isHoliday = isWeekend || isNatHoliday;
                    final holidayName = IndoHolidays.getHolidayName(date);
                    final dateKey = _dateKey(date);
                    final isSelected = dateKey == selectedKey;
                    final isToday = dateKey == todayKey;
                    final hasEvents = _datesWithEventsCache.contains(dateKey);

                    Color cellBg;
                    BoxBorder? cellBorder;
                    Color textColor;

                    if (isSelected) {
                      cellBg = AppTheme.brandAmber;
                      cellBorder = null;
                      textColor = Colors.white;
                    } else if (isToday) {
                      cellBg = isHoliday
                          ? const Color(0xFFFEE2E2)
                          : AppTheme.brandAmberLight;
                      cellBorder = Border.all(
                        color: isHoliday
                            ? const Color(0xFFDC2626)
                            : AppTheme.brandAmber,
                        width: 1.5,
                      );
                      textColor = isHoliday
                          ? const Color(0xFFDC2626)
                          : AppTheme.brandAmber;
                    } else if (isHoliday) {
                      cellBg = const Color(0xFFFEF2F2); // Blok merah lembut libur
                      cellBorder = Border.all(
                        color: const Color(0xFFFECACA),
                        width: 0.8,
                      );
                      textColor = const Color(0xFFDC2626); // Angka merah libur
                    } else {
                      cellBg = Colors.transparent;
                      cellBorder = null;
                      textColor = const Color(0xFF1E293B);
                    }

                    final dayEvents = _eventsByDateCache[dateKey] ?? const [];
                    final hasDayEvents = dayEvents.isNotEmpty;

                    return Expanded(
                      child: GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: () {
                          final isAlreadySelected = _dateKey(_selectedDate) == dateKey;
                          setState(() {
                            _selectedDate = date;
                          });
                          // Jika tanggal yang sama diklik lagi, buka modal rincian agenda
                          if (isAlreadySelected) {
                            _showDateAgendaModal(date);
                          }
                        },
                        child: SizedBox(
                          height: 46,
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                width: 34,
                                height: 34,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  color: cellBg,
                                  shape: BoxShape.circle,
                                  border: cellBorder,
                                  boxShadow: isSelected
                                      ? [
                                          BoxShadow(
                                            color: AppTheme.brandAmber.withValues(alpha: 0.35),
                                            blurRadius: 4,
                                            offset: const Offset(0, 1),
                                          ),
                                        ]
                                      : null,
                                ),
                                child: Text(
                                  '$day',
                                  style: TextStyle(
                                    color: textColor,
                                    fontSize: 13.5,
                                    fontWeight: isSelected || isToday || isWeekend
                                        ? FontWeight.w700
                                        : FontWeight.w600,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 2),
                              // Maksimal 3 bulatan agenda (sesuai permintaan user)
                              SizedBox(
                                height: 5,
                                child: hasDayEvents
                                    ? Row(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        mainAxisSize: MainAxisSize.min,
                                        children: dayEvents.take(3).map((ev) {
                                          final dotColor = isSelected
                                              ? Colors.white
                                              : AppTheme.categoryAccent(ev.category);
                                          return Container(
                                            margin: const EdgeInsets.symmetric(horizontal: 1),
                                            width: 4,
                                            height: 4,
                                            decoration: BoxDecoration(
                                              color: dotColor,
                                              shape: BoxShape.circle,
                                            ),
                                          );
                                        }).toList(),
                                      )
                                    : const SizedBox.shrink(),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  }),
                ),
              );
            }),
          ),
          const Divider(height: 14, color: Color(0xFFF1F5F9)),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              DecoratedBox(
                decoration: BoxDecoration(
                  color: AppTheme.brandAmber,
                  shape: BoxShape.circle,
                ),
                child: const SizedBox(width: 8, height: 8),
              ),
              const SizedBox(width: 5),
              const Text(
                'Dipilih',
                style: TextStyle(
                  fontSize: 11,
                  color: Color(0xFF64748B),
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(width: 14),
              const DecoratedBox(
                decoration: BoxDecoration(
                  color: Color(0xFF10B981),
                  shape: BoxShape.circle,
                ),
                child: SizedBox(width: 6, height: 6),
              ),
              const SizedBox(width: 5),
              const Text(
                'Ada Agenda',
                style: TextStyle(
                  fontSize: 11,
                  color: Color(0xFF64748B),
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(width: 14),
              Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF2F2),
                  shape: BoxShape.circle,
                  border: Border.all(color: const Color(0xFFDC2626), width: 1),
                ),
              ),
              const SizedBox(width: 5),
              const Text(
                'Libur Nasional / Akhir Pekan',
                style: TextStyle(
                  fontSize: 11,
                  color: Color(0xFFDC2626),
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// MODAL BOTTOM SHEET DETAIL AGENDA LENGKAP
  void _showEventDetailModal(EventModel event) {
    final lifecycle = _lifecycleCache[event.id] ?? EventLifecycle.upcoming;
    final category = event.category ?? 'Lainnya';
    final catAccent = AppTheme.categoryAccent(category);
    final catBg = AppTheme.categoryBg(category);
    final catText = AppTheme.categoryText(category);
    final timeStr = IndoDateUtils.formatTimeRange(event.startTime, event.endTime);
    final dateStr = IndoDateUtils.formatDateRange(event.date, event.endDate);

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 38,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 4,
                    height: 24,
                    decoration: BoxDecoration(
                      color: catAccent,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      event.eventName,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: catBg,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      category,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: catText,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppTheme.statusBg(lifecycle.label),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: AppTheme.statusBorder(lifecycle.label)),
                    ),
                    child: Text(
                      lifecycle.label,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.statusColor(lifecycle.label),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              const Divider(height: 1),
              const SizedBox(height: 14),
              _buildDetailRow(Icons.calendar_month_outlined, 'Tanggal', dateStr),
              const SizedBox(height: 10),
              _buildDetailRow(Icons.access_time_outlined, 'Waktu', '$timeStr WIB'),
              const SizedBox(height: 10),
              _buildDetailRow(Icons.meeting_room_outlined, 'Ruangan', event.roomName ?? 'Ruang Sekolah'),
              if (event.userName != null && event.userName!.isNotEmpty) ...[
                const SizedBox(height: 10),
                _buildDetailRow(Icons.person_outline, 'Peminjam / PIC', event.userName!),
              ],
              if (event.description != null && event.description!.trim().isNotEmpty) ...[
                const SizedBox(height: 10),
                _buildDetailRow(Icons.notes_outlined, 'Keperluan', event.description!),
              ],
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('Tutup'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: AppTheme.brandAmber),
        const SizedBox(width: 10),
        SizedBox(
          width: 90,
          child: Text(
            label,
            style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              fontSize: 12.5,
              fontWeight: FontWeight.w600,
              color: Color(0xFF1E293B),
            ),
          ),
        ),
      ],
    );
  }
}

/// Modal Window Agenda Interaktif Sesuai Tampilan Web (Dialog)
/// Dilengkapi Pencarian Debounced, Filter Kategori, Filter Status, Kartu Agenda & Paginasi
class _DateAgendaDialog extends StatefulWidget {
  const _DateAgendaDialog({
    required this.date,
    required this.events,
    required this.onEventTap,
  });

  final DateTime date;
  final List<EventModel> events;
  final ValueChanged<EventModel> onEventTap;

  @override
  State<_DateAgendaDialog> createState() => _DateAgendaDialogState();
}

class _DateAgendaDialogState extends State<_DateAgendaDialog> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String _selectedCategory = 'Semua';
  String _selectedStatus = 'Semua Status';
  int _currentPage = 0;
  static const int _pageSize = 5;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  EventLifecycle _computeLifecycle(EventModel event, DateTime now) {
    final startDate = DateTime.tryParse(event.date);
    if (startDate == null) return EventLifecycle.upcoming;

    final endDate = DateTime.tryParse(event.endDate) ?? startDate;

    int startHour = 0;
    int startMinute = 0;
    final startParts = event.startTime.split(':');
    if (startParts.isNotEmpty) {
      startHour = int.tryParse(startParts[0]) ?? 0;
      if (startParts.length > 1) {
        startMinute = int.tryParse(startParts[1]) ?? 0;
      }
    }

    int endHour = 23;
    int endMinute = 59;
    final endParts = event.endTime.split(':');
    if (endParts.isNotEmpty) {
      endHour = int.tryParse(endParts[0]) ?? 23;
      if (endParts.length > 1) {
        endMinute = int.tryParse(endParts[1]) ?? 59;
      }
    }

    final startDt = DateTime(
      startDate.year,
      startDate.month,
      startDate.day,
      startHour,
      startMinute,
    );
    final endDt = DateTime(
      endDate.year,
      endDate.month,
      endDate.day,
      endHour,
      endMinute,
    );

    if (now.isAfter(endDt) || now.isAtSameMomentAs(endDt)) {
      return EventLifecycle.completed;
    } else if (now.isBefore(startDt)) {
      return EventLifecycle.upcoming;
    } else {
      return EventLifecycle.inProgress;
    }
  }

  @override
  Widget build(BuildContext context) {
    final isHoliday = IndoHolidays.isNationalHoliday(widget.date);
    final holidayName = IndoHolidays.getHolidayName(widget.date);
    final now = DateTime.now();

    final filtered = widget.events.where((e) {
      if (_selectedCategory != 'Semua') {
        if (e.category != _selectedCategory) return false;
      }
      final lifecycle = _computeLifecycle(e, now);
      if (_selectedStatus != 'Semua Status') {
        if (lifecycle.label != _selectedStatus) return false;
      }
      if (_searchQuery.isNotEmpty) {
        final q = _searchQuery.toLowerCase();
        final name = e.eventName.toLowerCase();
        final room = (e.roomName ?? '').toLowerCase();
        final cat = e.category.toLowerCase();
        final desc = (e.description ?? '').toLowerCase();
        final user = (e.userName ?? '').toLowerCase();
        if (!name.contains(q) &&
            !room.contains(q) &&
            !cat.contains(q) &&
            !desc.contains(q) &&
            !user.contains(q)) {
          return false;
        }
      }
      return true;
    }).toList();

    final totalPages = (filtered.length / _pageSize).ceil();
    final safePage = _currentPage >= totalPages
        ? (totalPages > 0 ? totalPages - 1 : 0)
        : _currentPage;
    final paged = filtered.skip(safePage * _pageSize).take(_pageSize).toList();

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 24),
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 580, maxHeight: 720),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header Dialog Sesuai Web: Agenda [Hari, Tanggal] & [X] agenda tersimpan + Tombol Tutup
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Agenda ${IndoDateUtils.formatFull(widget.date)}',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${widget.events.length} agenda tersimpan pada tanggal ini',
                          style: const TextStyle(
                            fontSize: 12,
                            color: Color(0xFF64748B),
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  OutlinedButton(
                    onPressed: () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      side: const BorderSide(color: Color(0xFFCBD5E1)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: const Text(
                      '✕ Tutup',
                      style: TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF475569),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              // Banner Hari Libur Nasional (Jika Ada)
              if (isHoliday && holidayName != null) ...[
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEE2E2),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: const Color(0xFFFCA5A5)),
                  ),
                  child: Row(
                    children: [
                      const Text('🚩', style: TextStyle(fontSize: 14)),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Libur Nasional: $holidayName',
                          style: const TextStyle(
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF991B1B),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
              ],

              // Bar Pencarian di dalam Dialog
              TextField(
                controller: _searchController,
                decoration: InputDecoration(
                  hintText: 'Cari nama agenda, ruangan, jenis agenda...',
                  hintStyle: const TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8)),
                  prefixIcon: const Icon(Icons.search, size: 19, color: Color(0xFF94A3B8)),
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  suffixIcon: _searchQuery.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 16),
                          onPressed: () {
                            _searchController.clear();
                            setState(() {
                              _searchQuery = '';
                              _currentPage = 0;
                            });
                          },
                        )
                      : null,
                ),
                onChanged: (val) {
                  setState(() {
                    _searchQuery = val.trim();
                    _currentPage = 0;
                  });
                },
              ),
              const SizedBox(height: 8),

              // Filter Chips Kategori & Status (Horizontal Scroll)
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    ...['Semua', ...AppConstants.eventCategories].map((cat) {
                      final isSel = _selectedCategory == cat;
                      return Padding(
                        padding: const EdgeInsets.only(right: 6),
                        child: FilterChip(
                          label: Text(cat),
                          selected: isSel,
                          onSelected: (_) {
                            setState(() {
                              _selectedCategory = cat;
                              _currentPage = 0;
                            });
                          },
                          selectedColor: AppTheme.brandAmberLight,
                          checkmarkColor: AppTheme.brandAmber,
                          labelStyle: TextStyle(
                            fontSize: 11,
                            fontWeight: isSel ? FontWeight.w700 : FontWeight.w500,
                            color: isSel ? AppTheme.brandAmber : const Color(0xFF475569),
                          ),
                          side: BorderSide(
                            color: isSel ? AppTheme.brandAmber : const Color(0xFFE2E8F0),
                          ),
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        ),
                      );
                    }),
                    ...['Semua Status', 'Sedang Terlaksana', 'Belum Terlaksana', 'Sudah Terlaksana'].map((st) {
                      final isSel = _selectedStatus == st;
                      return Padding(
                        padding: const EdgeInsets.only(right: 6),
                        child: FilterChip(
                          label: Text(st),
                          selected: isSel,
                          onSelected: (_) {
                            setState(() {
                              _selectedStatus = st;
                              _currentPage = 0;
                            });
                          },
                          selectedColor: const Color(0xFFEFF6FF),
                          checkmarkColor: const Color(0xFF2563EB),
                          labelStyle: TextStyle(
                            fontSize: 11,
                            fontWeight: isSel ? FontWeight.w700 : FontWeight.w500,
                            color: isSel ? const Color(0xFF2563EB) : const Color(0xFF475569),
                          ),
                          side: BorderSide(
                            color: isSel ? const Color(0xFF3B82F6) : const Color(0xFFE2E8F0),
                          ),
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        ),
                      );
                    }),
                  ],
                ),
              ),
              const SizedBox(height: 10),

              // Daftar Kartu Agenda (Scrollable) Sesuai Web
              Expanded(
                child: filtered.isEmpty
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                isHoliday ? Icons.beach_access_outlined : Icons.event_busy,
                                size: 36,
                                color: const Color(0xFF94A3B8),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                isHoliday
                                    ? 'Tidak ada kegiatan atau rapat sekolah pada tanggal merah ini.'
                                    : (widget.events.isEmpty
                                        ? 'Tidak ada agenda pada tanggal ini. Ruangan kelas dan lab tersedia bebas untuk peminjaman.'
                                        : 'Tidak ada agenda yang cocok dengan filter pencarian/kategori.'),
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  fontSize: 12.5,
                                  color: Color(0xFF64748B),
                                ),
                              ),
                            ],
                          ),
                        ),
                      )
                    : ListView.separated(
                        itemCount: paged.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final event = paged[index];
                          final lifecycle = _computeLifecycle(event, now);
                          final accent = AppTheme.categoryAccent(event.category);
                          return InkWell(
                            onTap: () => widget.onEventTap(event),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: const Color(0xFFE4E4E7)),
                              ),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: Container(
                                  decoration: BoxDecoration(
                                    border: Border(
                                      left: BorderSide(color: accent, width: 4),
                                    ),
                                  ),
                                  padding: const EdgeInsets.all(12),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Expanded(
                                            child: Text(
                                              event.eventName,
                                              style: const TextStyle(
                                                fontSize: 14,
                                                fontWeight: FontWeight.w700,
                                                color: Color(0xFF0F172A),
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          Wrap(
                                            spacing: 4,
                                            runSpacing: 4,
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFFF1F5F9),
                                                  borderRadius: BorderRadius.circular(4),
                                                ),
                                                child: Text(
                                                  event.category,
                                                  style: const TextStyle(
                                                    fontSize: 10,
                                                    fontWeight: FontWeight.w600,
                                                    color: Color(0xFF475569),
                                                  ),
                                                ),
                                              ),
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                                decoration: BoxDecoration(
                                                  color: AppTheme.statusBg(lifecycle.label),
                                                  borderRadius: BorderRadius.circular(4),
                                                  border: Border.all(color: AppTheme.statusBorder(lifecycle.label)),
                                                ),
                                                child: Text(
                                                  lifecycle.label,
                                                  style: TextStyle(
                                                    fontSize: 10,
                                                    fontWeight: FontWeight.w700,
                                                    color: AppTheme.statusColor(lifecycle.label),
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 6),
                                      Text(
                                        '${IndoDateUtils.formatTimeRange(event.startTime, event.endTime)} WIB · ${event.roomName ?? 'Ruangan'}',
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: Color(0xFF64748B),
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                      const SizedBox(height: 6),
                                      const Text(
                                        'Klik untuk melihat detail agenda →',
                                        style: TextStyle(
                                          fontSize: 11.5,
                                          fontWeight: FontWeight.w600,
                                          color: AppTheme.brandAmber,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          );
                        },
                      ),
              ),

              // Pagination Bar Sesuai Screenshot Web
              if (filtered.length > _pageSize) ...[
                const SizedBox(height: 8),
                const Divider(height: 1),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Menampilkan ${safePage * _pageSize + 1}-${(safePage + 1) * _pageSize > filtered.length ? filtered.length : (safePage + 1) * _pageSize} dari ${filtered.length} agenda',
                      style: const TextStyle(
                        fontSize: 11,
                        color: Color(0xFF64748B),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        OutlinedButton(
                          onPressed: safePage > 0
                              ? () => setState(() => _currentPage = safePage - 1)
                              : null,
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            minimumSize: const Size(46, 28),
                          ),
                          child: const Text('← Prev', style: TextStyle(fontSize: 10.5)),
                        ),
                        const SizedBox(width: 4),
                        ...List.generate(totalPages, (i) {
                          final isAct = i == safePage;
                          return Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 2),
                            child: InkWell(
                              onTap: () => setState(() => _currentPage = i),
                              borderRadius: BorderRadius.circular(4),
                              child: Container(
                                width: 26,
                                height: 26,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  color: isAct ? AppTheme.brandAmber : Colors.white,
                                  borderRadius: BorderRadius.circular(4),
                                  border: Border.all(
                                    color: isAct ? AppTheme.brandAmber : const Color(0xFFCBD5E1),
                                  ),
                                ),
                                child: Text(
                                  '${i + 1}',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: isAct ? FontWeight.w700 : FontWeight.w500,
                                    color: isAct ? Colors.white : const Color(0xFF1E293B),
                                  ),
                                ),
                              ),
                            ),
                          );
                        }),
                        const SizedBox(width: 4),
                        OutlinedButton(
                          onPressed: safePage < totalPages - 1
                              ? () => setState(() => _currentPage = safePage + 1)
                              : null,
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            minimumSize: const Size(46, 28),
                          ),
                          child: const Text('Next →', style: TextStyle(fontSize: 10.5)),
                        ),
                      ],
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
