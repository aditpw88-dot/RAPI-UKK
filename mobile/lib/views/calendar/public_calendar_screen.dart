import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/date_utils.dart';
import '../../models/event_model.dart';

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
  static const _weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

  DateTime _visibleMonth = DateTime(DateTime.now().year, DateTime.now().month);
  DateTime _selectedDate = DateTime.now();
  List<EventModel> _events = [];
  String? _loadError;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadEvents();
  }

  Future<void> _loadEvents() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });

    try {
      final response = await widget.api.get('/events');
      if (response is! Map || response['data'] is! List) {
        throw const FormatException(
          'Format data agenda dari server tidak valid.',
        );
      }

      final events = (response['data'] as List).map((item) {
        if (item is! Map) {
          throw const FormatException('Data agenda dari server tidak valid.');
        }
        return EventModel.fromJson(Map<String, dynamic>.from(item));
      }).toList();

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

  bool _isSameDate(DateTime a, DateTime b) =>
      a.year == b.year && a.month == b.month && a.day == b.day;

  List<EventModel> _eventsFor(DateTime date) {
    return _events.where((event) => event.occursOn(date)).toList();
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
  }

  @override
  Widget build(BuildContext context) {
    final selectedEvents = _eventsFor(_selectedDate);

    return Scaffold(
      appBar: widget.showAppBar
          ? AppBar(
              title: const Text('Kalender Sekolah'),
              actions: [
                if (widget.onLogin != null)
                  TextButton.icon(
                    onPressed: widget.onLogin,
                    icon: const Icon(Icons.login, size: 18),
                    label: const Text('Login'),
                  ),
                const SizedBox(width: 8),
              ],
            )
          : null,
      body: RefreshIndicator(
        onRefresh: _loadEvents,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
          children: [
            Text(
              'KALENDER INTERAKTIF',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              'Jadwal Penggunaan Ruangan & Agenda Sekolah',
              textAlign: TextAlign.center,
              style: TextStyle(color: Color(0xFF64748B)),
            ),
            const SizedBox(height: 18),
            _buildCalendarCard(),
            const SizedBox(height: 20),
            Text(
              'Agenda ${IndoDateUtils.formatFull(_selectedDate)}',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            if (_isLoading)
              const Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (_loadError != null)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Text(
                        'Gagal memuat agenda: $_loadError',
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Color(0xFFB91C1C)),
                      ),
                      TextButton(
                        onPressed: _loadEvents,
                        child: const Text('Coba lagi'),
                      ),
                    ],
                  ),
                ),
              )
            else if (selectedEvents.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Text(
                    'Belum ada agenda yang disetujui pada tanggal ini.',
                    style: TextStyle(color: Color(0xFF64748B)),
                  ),
                ),
              )
            else
              ...selectedEvents.map(_buildEventCard),
            const SizedBox(height: 20),
            Text(
              'Semua agenda disetujui (${_events.length})',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            if (!_isLoading && _loadError == null && _events.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Text(
                    'Belum ada agenda yang disetujui.',
                    style: TextStyle(color: Color(0xFF64748B)),
                  ),
                ),
              )
            else if (!_isLoading && _loadError == null)
              ..._events.map(_buildAgendaListItem),
          ],
        ),
      ),
    );
  }

  Widget _buildCalendarCard() {
    final firstDay = DateTime(_visibleMonth.year, _visibleMonth.month, 1);
    final daysInMonth = DateTime(
      _visibleMonth.year,
      _visibleMonth.month + 1,
      0,
    ).day;
    final startingOffset = firstDay.weekday - 1;
    final dateParts = IndoDateUtils.formatFull(
      firstDay,
    ).split(', ').last.split(' ');
    final monthName = '${dateParts[1]} ${dateParts[2]}';

    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
        child: Column(
          children: [
            Row(
              children: [
                IconButton(
                  tooltip: 'Bulan sebelumnya',
                  onPressed: () => _changeMonth(-1),
                  icon: const Icon(Icons.chevron_left),
                ),
                Expanded(
                  child: Text(
                    monthName,
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                IconButton(
                  tooltip: 'Bulan berikutnya',
                  onPressed: () => _changeMonth(1),
                  icon: const Icon(Icons.chevron_right),
                ),
              ],
            ),
            Row(
              children: _weekdays
                  .map(
                    (day) => Expanded(
                      child: Center(
                        child: Text(
                          day,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF475569),
                          ),
                        ),
                      ),
                    ),
                  )
                  .toList(),
            ),
            const SizedBox(height: 8),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 7,
                mainAxisSpacing: 4,
                crossAxisSpacing: 2,
                childAspectRatio: 0.9,
              ),
              itemCount: 42,
              itemBuilder: (context, index) {
                final day = index - startingOffset + 1;
                if (day < 1 || day > daysInMonth) {
                  return const SizedBox.shrink();
                }

                final date = DateTime(
                  _visibleMonth.year,
                  _visibleMonth.month,
                  day,
                );
                final isSelected = _isSameDate(date, _selectedDate);
                final isToday = _isSameDate(date, DateTime.now());
                final hasEvents = _eventsFor(date).isNotEmpty;
                final colorScheme = Theme.of(context).colorScheme;

                return InkWell(
                  onTap: () => setState(() => _selectedDate = date),
                  borderRadius: BorderRadius.circular(10),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 34,
                        height: 34,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: isSelected ? colorScheme.primary : null,
                          shape: BoxShape.circle,
                          border: isToday && !isSelected
                              ? Border.all(color: colorScheme.primary)
                              : null,
                        ),
                        child: Text(
                          '$day',
                          style: TextStyle(
                            color: isSelected
                                ? Colors.white
                                : const Color(0xFF0F172A),
                            fontWeight: isSelected || isToday
                                ? FontWeight.w700
                                : FontWeight.w400,
                          ),
                        ),
                      ),
                      const SizedBox(height: 2),
                      Container(
                        width: 4,
                        height: 4,
                        decoration: BoxDecoration(
                          color: hasEvents
                              ? (isSelected
                                    ? colorScheme.primary
                                    : const Color(0xFF10B981))
                              : Colors.transparent,
                          shape: BoxShape.circle,
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEventCard(EventModel event) {
    return Card(
      child: ListTile(
        leading: const CircleAvatar(
          backgroundColor: Color(0xFFE2E8F0),
          child: Icon(Icons.event, color: Color(0xFF334155)),
        ),
        title: Text(
          event.eventName,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: Text(event.roomName ?? 'Ruangan tidak tersedia'),
        trailing: Text(
          IndoDateUtils.formatTimeRange(event.startTime, event.endTime),
          textAlign: TextAlign.right,
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }

  Widget _buildAgendaListItem(EventModel event) {
    final date = DateTime.tryParse(event.date);
    return Card(
      child: ListTile(
        onTap: date == null
            ? null
            : () {
                setState(() {
                  _selectedDate = date;
                  _visibleMonth = DateTime(date.year, date.month);
                });
              },
        leading: const CircleAvatar(
          backgroundColor: Color(0xFFE2E8F0),
          child: Icon(Icons.event, color: Color(0xFF334155)),
        ),
        title: Text(
          event.eventName,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: Text(
          '${event.roomName ?? 'Ruangan tidak tersedia'}'
          '${date == null ? '' : '\n${IndoDateUtils.formatDateRange(event.date, event.endDate)}'}',
        ),
        isThreeLine: true,
        trailing: Text(
          IndoDateUtils.formatTimeRange(event.startTime, event.endTime),
          textAlign: TextAlign.right,
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }
}
