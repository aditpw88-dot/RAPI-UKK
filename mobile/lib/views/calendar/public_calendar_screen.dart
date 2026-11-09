import 'dart:async';
import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/date_utils.dart';
import '../../models/event_model.dart';

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
  static const _categories = ['Semua', 'Peminjaman', 'Akademik', 'Maintenance'];
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
  static const int _pageSize = 5; // Masing-masing menyimpan 5 data per halaman

  DateTime _visibleMonth = DateTime(DateTime.now().year, DateTime.now().month);
  DateTime _selectedDate = DateTime.now();
  String _selectedCategory = 'Semua';
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();
  final ValueNotifier<bool> _hasSearchText = ValueNotifier<bool>(false);
  Timer? _searchDebounceTimer;

  List<EventModel> _events = [];

  // Cache O(1) untuk optimasi performa ultra-ringan di HP (Zero lag)
  final Map<String, List<EventModel>> _eventsByDateCache = {};
  final Set<String> _datesWithEventsCache = {};
  final Map<int, EventLifecycle> _lifecycleCache = {};
  final Map<int, String> _searchIndexCache = {};

  String? _loadError;
  bool _isLoading = true;

  // Pagination state: masing-masing 5 data per halaman
  int _selectedDatePage = 0;
  int _allEventsPage = 0;

  @override
  void initState() {
    super.initState();
    _loadEvents();
  }

  @override
  void dispose() {
    _searchDebounceTimer?.cancel();
    _searchController.dispose();
    _hasSearchText.dispose();
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

    // 1. Pre-compute lifecycle & teks pencarian sekali saja
    for (final event in events) {
      _lifecycleCache[event.id] = _computeLifecycle(event, now);
      _searchIndexCache[event.id] =
          '${event.eventName} ${event.roomName ?? ''}'.toLowerCase();
    }

    // 2. Pre-sort agenda sekali saja saat data dimuat (O(N log N) sekali saja, bukan tiap frame!)
    // Urutan prioritas:
    // 1. Sedang Terlaksana
    // 2. Belum Terlaksana
    // 3. Sudah Terlaksana
    // Kemudian tanggal & waktu mulai
    events.sort((a, b) {
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
    for (final event in events) {
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
        _selectedDatePage = 0;
        _allEventsPage = 0;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loadError = error.toString();
        _isLoading = false;
      });
    }
  }

  bool _matchesFilter(EventModel event) {
    final text = _searchIndexCache[event.id] ?? '';

    // 1. Filter Kategori O(1)
    if (_selectedCategory != 'Semua') {
      if (_selectedCategory == 'Peminjaman') {
        final ok = text.contains('pinjam') ||
            text.contains('praktik') ||
            text.contains('lab') ||
            text.contains('ekskul') ||
            text.contains('belajar');
        if (!ok) return false;
      } else if (_selectedCategory == 'Akademik') {
        final ok = text.contains('rapat') ||
            text.contains('ujian') ||
            text.contains('ukk') ||
            text.contains('kurikulum') ||
            text.contains('akademik') ||
            text.contains('guru') ||
            text.contains('mapel');
        if (!ok) return false;
      } else if (_selectedCategory == 'Maintenance') {
        final ok = text.contains('maintenance') ||
            text.contains('servis') ||
            text.contains('perbaikan') ||
            text.contains('cek') ||
            text.contains('jaringan');
        if (!ok) return false;
      }
    }

    // 2. Filter Pencarian Text
    if (_searchQuery.isNotEmpty) {
      if (!text.contains(_searchQuery)) return false;
    }

    return true;
  }

  List<EventModel> _eventsFor(DateTime date) {
    final list = _eventsByDateCache[_dateKey(date)] ?? const [];
    if (_selectedCategory == 'Semua' && _searchQuery.isEmpty) {
      return list;
    }
    return list.where(_matchesFilter).toList();
  }

  List<EventModel> get _allFilteredEvents {
    if (_selectedCategory == 'Semua' && _searchQuery.isEmpty) {
      return _events;
    }
    return _events.where(_matchesFilter).toList();
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
      _selectedDatePage = 0;
    });
  }

  @override
  Widget build(BuildContext context) {
    // Data Bagian 1: Agenda Tanggal Terpilih (Data sudah pre-sorted!)
    final selectedEvents = _eventsFor(_selectedDate);
    final totalSelectedPages = (selectedEvents.length / _pageSize).ceil();
    final safeSelectedPage = totalSelectedPages == 0
        ? 0
        : (_selectedDatePage >= totalSelectedPages
            ? totalSelectedPages - 1
            : _selectedDatePage);
    final displayedSelectedEvents = selectedEvents
        .skip(safeSelectedPage * _pageSize)
        .take(_pageSize)
        .toList();

    // Data Bagian 2: Semua Agenda Sekolah (Data sudah pre-sorted!)
    final allEvents = _allFilteredEvents;
    final totalAllPages = (allEvents.length / _pageSize).ceil();
    final safeAllPage = totalAllPages == 0
        ? 0
        : (_allEventsPage >= totalAllPages
            ? totalAllPages - 1
            : _allEventsPage);
    final displayedAllEvents =
        allEvents.skip(safeAllPage * _pageSize).take(_pageSize).toList();

    return Scaffold(
      backgroundColor: const Color(0xFFFAFAFA),
      appBar: widget.showAppBar
          ? AppBar(
              backgroundColor: Colors.white,
              elevation: 0,
              scrolledUnderElevation: 1,
              titleSpacing: 16,
              title: Row(
                children: [
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: const Color(0xFF09090B),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    alignment: Alignment.center,
                    child: const Text(
                      'R',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                        fontSize: 15,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'RAPI-Sekolah',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF09090B),
                          letterSpacing: -0.2,
                        ),
                      ),
                      Text(
                        'Jadwal & Peminjaman Ruang',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF71717A),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              actions: [
                if (widget.onLogin != null)
                  Padding(
                    padding: const EdgeInsets.only(right: 14),
                    child: ElevatedButton.icon(
                      onPressed: widget.onLogin,
                      icon: const Icon(Icons.login, size: 15),
                      label: const Text('Login'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF09090B),
                        foregroundColor: Colors.white,
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 8,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(20),
                        ),
                        textStyle: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
              ],
            )
          : null,
      body: RefreshIndicator(
        color: const Color(0xFF09090B),
        onRefresh: _loadEvents,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(
            parent: ClampingScrollPhysics(),
          ),
          padding: const EdgeInsets.fromLTRB(14, 12, 14, 24),
          children: [
            // Bar Pencarian Agenda Bebas Lag (Debounced)
            RepaintBoundary(
              child: _buildSearchBar(),
            ),
            const SizedBox(height: 10),

            // Filter Kategori Horizontal Ringan
            RepaintBoundary(
              child: _buildCategoryFilter(),
            ),
            const SizedBox(height: 12),

            // Kalender Card Lebih Besar, Jelas & Anti-Lag (Pure Column/Row)
            RepaintBoundary(
              child: _buildCalendarCard(),
            ),
            const SizedBox(height: 16),

            // ==========================================
            // BAGIAN 1: AGENDA TANGGAL TERPILIH (PAGINATION 5 DATA)
            // ==========================================
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Agenda ${IndoDateUtils.formatFull(_selectedDate)}',
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF09090B),
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 1),
                      Text(
                        '${selectedEvents.length} agenda pada tanggal ini',
                        style: const TextStyle(
                          fontSize: 11,
                          color: Color(0xFF71717A),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF4F4F5),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(color: const Color(0xFFE4E4E7)),
                  ),
                  child: const Text(
                    'Terverifikasi',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF3F3F46),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),

            if (_isLoading)
              const Padding(
                padding: EdgeInsets.all(28),
                child: Center(
                  child: CircularProgressIndicator(
                    color: Color(0xFF09090B),
                    strokeWidth: 2.5,
                  ),
                ),
              )
            else if (_loadError != null)
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFFECACA)),
                ),
                child: Column(
                  children: [
                    Text(
                      'Gagal memuat agenda: $_loadError',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Color(0xFFB91C1C),
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 6),
                    TextButton(
                      onPressed: _loadEvents,
                      child: const Text(
                        'Coba lagi',
                        style: TextStyle(
                          color: Color(0xFF09090B),
                          fontWeight: FontWeight.w600,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                ),
              )
            else if (selectedEvents.isEmpty)
              Container(
                padding: const EdgeInsets.symmetric(
                  vertical: 20,
                  horizontal: 14,
                ),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE4E4E7)),
                ),
                child: Column(
                  children: [
                    const Icon(
                      Icons.calendar_today_outlined,
                      size: 24,
                      color: Color(0xFFA1A1AA),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      _searchQuery.isNotEmpty || _selectedCategory != 'Semua'
                          ? 'Tidak ada agenda yang cocok dengan filter'
                          : 'Tidak ada agenda pada tanggal ini',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF27272A),
                      ),
                    ),
                    const SizedBox(height: 2),
                    const Text(
                      'Ruangan kelas dan lab tersedia bebas untuk peminjaman.',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 11, color: Color(0xFF71717A)),
                    ),
                  ],
                ),
              )
            else ...[
              // List 5 Data Per Halaman
              ...displayedSelectedEvents.map(_buildSingleLineAgenda),

              // Pagination Kontrol Bagian 1 (5 Data)
              _buildPaginationBar(
                currentPage: safeSelectedPage,
                totalPages: totalSelectedPages,
                totalItems: selectedEvents.length,
                onPageChanged: (newPage) {
                  setState(() => _selectedDatePage = newPage);
                },
              ),
            ],

            const SizedBox(height: 18),

            // ==========================================
            // BAGIAN 2: SEMUA AGENDA SEKOLAH (PAGINATION 5 DATA)
            // ==========================================
            if (!_isLoading && _loadError == null) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      'Semua Agenda Sekolah (${allEvents.length})',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF09090B),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (_searchQuery.isNotEmpty || _selectedCategory != 'Semua')
                    Padding(
                      padding: const EdgeInsets.only(left: 6),
                      child: Text(
                        'Difilter: ${_selectedCategory != 'Semua' ? _selectedCategory : 'Cari'}',
                        style: const TextStyle(
                          fontSize: 10.5,
                          color: Color(0xFF71717A),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 8),

              if (allEvents.isEmpty)
                Container(
                  padding: const EdgeInsets.symmetric(
                    vertical: 16,
                    horizontal: 14,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE4E4E7)),
                  ),
                  child: const Center(
                    child: Text(
                      'Tidak ada agenda yang sesuai dengan pencarian/kategori.',
                      style: TextStyle(
                        fontSize: 11.5,
                        color: Color(0xFF71717A),
                      ),
                    ),
                  ),
                )
              else ...[
                // List 5 Data Per Halaman
                ...displayedAllEvents.map(_buildSingleLineAgenda),

                // Pagination Kontrol Bagian 2 (5 Data)
                _buildPaginationBar(
                  currentPage: safeAllPage,
                  totalPages: totalAllPages,
                  totalItems: allEvents.length,
                  onPageChanged: (newPage) {
                    setState(() => _allEventsPage = newPage);
                  },
                ),
              ],
            ],
          ],
        ),
      ),
    );
  }

  /// Bar Pencarian Agenda Super Lancar (Debounced & Tanpa Lag Mengetik)
  Widget _buildSearchBar() {
    return TextField(
      controller: _searchController,
      onChanged: (val) {
        final hasText = val.trim().isNotEmpty;
        if (_hasSearchText.value != hasText) {
          _hasSearchText.value = hasText;
        }

        // Debounce pencarian agar tidak merebuild layar pada setiap ketikan huruf
        _searchDebounceTimer?.cancel();
        _searchDebounceTimer = Timer(const Duration(milliseconds: 220), () {
          if (!mounted) return;
          final query = val.trim().toLowerCase();
          if (_searchQuery != query) {
            setState(() {
              _searchQuery = query;
              _selectedDatePage = 0;
              _allEventsPage = 0;
            });
          }
        });
      },
      style: const TextStyle(
        fontSize: 13.5,
        fontWeight: FontWeight.w600,
        color: Color(0xFF09090B),
      ),
      decoration: InputDecoration(
        filled: true,
        fillColor: Colors.white,
        hintText: 'Cari nama agenda atau ruangan...',
        hintStyle: const TextStyle(
          fontSize: 12.5,
          fontWeight: FontWeight.w400,
          color: Color(0xFF71717A),
        ),
        prefixIcon: const Icon(
          Icons.search_rounded,
          size: 20,
          color: Color(0xFF71717A),
        ),
        suffixIcon: ValueListenableBuilder<bool>(
          valueListenable: _hasSearchText,
          builder: (context, hasText, _) {
            if (!hasText) return const SizedBox.shrink();
            return IconButton(
              icon: const Icon(
                Icons.cancel,
                size: 18,
                color: Color(0xFF71717A),
              ),
              onPressed: () {
                _searchDebounceTimer?.cancel();
                _searchController.clear();
                _hasSearchText.value = false;
                if (_searchQuery.isNotEmpty) {
                  setState(() {
                    _searchQuery = '';
                    _selectedDatePage = 0;
                    _allEventsPage = 0;
                  });
                }
              },
            );
          },
        ),
        isDense: true,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 12,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFD4D4D8), width: 1.2),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFD4D4D8), width: 1.2),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF09090B), width: 1.5),
        ),
      ),
    );
  }

  Widget _buildCategoryFilter() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      physics: const BouncingScrollPhysics(),
      child: Row(
        children: _categories.map((cat) {
          final isSelected = _selectedCategory == cat;
          return Padding(
            padding: const EdgeInsets.only(right: 6),
            child: InkWell(
              onTap: () => setState(() {
                _selectedCategory = cat;
                _selectedDatePage = 0;
                _allEventsPage = 0;
              }),
              borderRadius: BorderRadius.circular(20),
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: isSelected ? const Color(0xFF09090B) : Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: isSelected
                        ? const Color(0xFF09090B)
                        : const Color(0xFFE4E4E7),
                  ),
                ),
                child: Text(
                  cat == 'Semua' ? 'Semua (${_events.length})' : cat,
                  style: TextStyle(
                    fontSize: 11.5,
                    fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                    color: isSelected ? Colors.white : const Color(0xFF52525B),
                  ),
                ),
              ),
            ),
          );
        }).toList(),
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
          // Header Bulan & Navigasi (Lebih Besar & Nyaman Diklik)
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
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      monthName,
                      style: const TextStyle(
                        fontSize: 15.5,
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
                    final dateKey = _dateKey(date);
                    final isSelected = dateKey == selectedKey;
                    final isToday = dateKey == todayKey;
                    final hasEvents = _datesWithEventsCache.contains(dateKey);

                    Color cellBg;
                    BoxBorder? cellBorder;
                    Color textColor;

                    if (isSelected) {
                      cellBg = const Color(0xFF09090B);
                      cellBorder = null;
                      textColor = Colors.white;
                    } else if (isToday) {
                      cellBg = isWeekend
                          ? const Color(0xFFFEE2E2)
                          : const Color(0xFFF4F4F5);
                      cellBorder = Border.all(
                        color: isWeekend
                            ? const Color(0xFFDC2626)
                            : const Color(0xFF09090B),
                        width: 1.4,
                      );
                      textColor = isWeekend
                          ? const Color(0xFFDC2626)
                          : const Color(0xFF09090B);
                    } else if (isWeekend) {
                      cellBg = const Color(0xFFFEF2F2); // Blok merah lembut libur
                      cellBorder = Border.all(
                        color: const Color(0xFFFECACA),
                        width: 0.8,
                      );
                      textColor = const Color(0xFFDC2626); // Angka merah
                    } else {
                      cellBg = Colors.transparent;
                      cellBorder = null;
                      textColor = const Color(0xFF18181B);
                    }

                    return Expanded(
                      child: InkWell(
                        onTap: () {
                          if (_selectedDate.year != date.year ||
                              _selectedDate.month != date.month ||
                              _selectedDate.day != date.day) {
                            setState(() {
                              _selectedDate = date;
                              _selectedDatePage = 0;
                            });
                          }
                        },
                        borderRadius: BorderRadius.circular(10),
                        child: SizedBox(
                          height: 46,
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                width: 35,
                                height: 35,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  color: cellBg,
                                  shape: BoxShape.circle,
                                  border: cellBorder,
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
                              Container(
                                width: 4.5,
                                height: 4.5,
                                decoration: BoxDecoration(
                                  color: hasEvents
                                      ? (isSelected
                                          ? const Color(0xFF09090B)
                                          : const Color(0xFF10B981))
                                      : Colors.transparent,
                                  shape: BoxShape.circle,
                                ),
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
          const Divider(height: 14, color: Color(0xFFF4F4F5)),
          const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              DecoratedBox(
                decoration: BoxDecoration(
                  color: Color(0xFF09090B),
                  shape: BoxShape.circle,
                ),
                child: SizedBox(width: 8, height: 8),
              ),
              SizedBox(width: 5),
              Text(
                'Dipilih',
                style: TextStyle(
                  fontSize: 11,
                  color: Color(0xFF71717A),
                  fontWeight: FontWeight.w500,
                ),
              ),
              SizedBox(width: 16),
              DecoratedBox(
                decoration: BoxDecoration(
                  color: Color(0xFF10B981),
                  shape: BoxShape.circle,
                ),
                child: SizedBox(width: 6, height: 6),
              ),
              SizedBox(width: 5),
              Text(
                'Ada Agenda',
                style: TextStyle(
                  fontSize: 11,
                  color: Color(0xFF71717A),
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// FORMAT 1 BARIS (SINGLE LINE) DENGAN STATUS PELAKSANAAN:
  /// Urutan Prioritas:
  /// 1. Sedang Terlaksana
  /// 2. Belum Terlaksana
  /// 3. Sudah Terlaksana
  Widget _buildSingleLineAgenda(EventModel event) {
    final lifecycle = _lifecycleCache[event.id] ?? EventLifecycle.upcoming;

    Color barColor;
    Color badgeBg;
    Color badgeBorder;
    Color badgeText;

    switch (lifecycle) {
      case EventLifecycle.inProgress: // Sedang Terlaksana
        barColor = const Color(0xFF10B981); // Emerald
        badgeBg = const Color(0xFFECFDF5);
        badgeBorder = const Color(0xFFA7F3D0);
        badgeText = const Color(0xFF047857);
        break;
      case EventLifecycle.upcoming: // Belum Terlaksana
        barColor = const Color(0xFF0284C7); // Sky Blue
        badgeBg = const Color(0xFFF0F9FF);
        badgeBorder = const Color(0xFFBAE6FD);
        badgeText = const Color(0xFF0369A1);
        break;
      case EventLifecycle.completed: // Sudah Terlaksana
        barColor = const Color(0xFF94A3B8); // Slate Gray
        badgeBg = const Color(0xFFF1F5F9);
        badgeBorder = const Color(0xFFCBD5E1);
        badgeText = const Color(0xFF475569);
        break;
    }

    final timeStr =
        IndoDateUtils.formatTimeRange(event.startTime, event.endTime);
    final roomStr = event.roomName ?? 'Ruang Sekolah';

    return Container(
      key: ValueKey(event.id),
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8.5),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFE4E4E7)),
      ),
      child: Row(
        children: [
          Container(
            width: 3.5,
            height: 18,
            decoration: BoxDecoration(
              color: barColor,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text.rich(
              TextSpan(
                children: [
                  TextSpan(
                    text: event.eventName,
                    style: const TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF09090B),
                    ),
                  ),
                  TextSpan(
                    text: ' • $roomStr • $timeStr',
                    style: const TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w500,
                      color: Color(0xFF71717A),
                    ),
                  ),
                ],
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          const SizedBox(width: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
            decoration: BoxDecoration(
              color: badgeBg,
              borderRadius: BorderRadius.circular(6),
              border: Border.all(color: badgeBorder),
            ),
            child: Text(
              lifecycle.label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                color: badgeText,
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// KONTROL PAGINATION 5 DATA (RINGAN & RAMAH JEMPOL)
  Widget _buildPaginationBar({
    required int currentPage,
    required int totalPages,
    required int totalItems,
    required ValueChanged<int> onPageChanged,
  }) {
    if (totalItems <= _pageSize) return const SizedBox.shrink();

    final startItem = currentPage * _pageSize + 1;
    final endItem = (currentPage + 1) * _pageSize > totalItems
        ? totalItems
        : (currentPage + 1) * _pageSize;

    return Padding(
      padding: const EdgeInsets.only(top: 4, bottom: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Text(
              'Menampilkan $startItem-$endItem dari $totalItems data',
              style: const TextStyle(
                fontSize: 10.5,
                color: Color(0xFF71717A),
                fontWeight: FontWeight.w500,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          const SizedBox(width: 8),
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              InkWell(
                onTap: currentPage > 0
                    ? () => onPageChanged(currentPage - 1)
                    : null,
                borderRadius: BorderRadius.circular(6),
                child: Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    color: currentPage > 0
                        ? Colors.white
                        : const Color(0xFFF4F4F5),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(
                      color: currentPage > 0
                          ? const Color(0xFFE4E4E7)
                          : const Color(0xFFF4F4F5),
                    ),
                  ),
                  alignment: Alignment.center,
                  child: Icon(
                    Icons.chevron_left,
                    size: 16,
                    color: currentPage > 0
                        ? const Color(0xFF09090B)
                        : const Color(0xFFA1A1AA),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                child: Text(
                  '${currentPage + 1} / $totalPages',
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF09090B),
                  ),
                ),
              ),
              InkWell(
                onTap: currentPage < totalPages - 1
                    ? () => onPageChanged(currentPage + 1)
                    : null,
                borderRadius: BorderRadius.circular(6),
                child: Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    color: currentPage < totalPages - 1
                        ? Colors.white
                        : const Color(0xFFF4F4F5),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(
                      color: currentPage < totalPages - 1
                          ? const Color(0xFFE4E4E7)
                          : const Color(0xFFF4F4F5),
                    ),
                  ),
                  alignment: Alignment.center,
                  child: Icon(
                    Icons.chevron_right,
                    size: 16,
                    color: currentPage < totalPages - 1
                        ? const Color(0xFF09090B)
                        : const Color(0xFFA1A1AA),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
