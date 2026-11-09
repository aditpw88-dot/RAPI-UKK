import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../core/date_utils.dart';
import '../../models/event_model.dart';
import '../../models/user_model.dart';
import '../../theme/app_theme.dart';

class EventsTab extends StatefulWidget {
  const EventsTab({
    required this.api,
    required this.user,
    required this.onOpenCreateEvent,
    required this.onOpenCreateComplaint,
    super.key,
  });

  final ApiClient api;
  final UserModel user;
  final VoidCallback onOpenCreateEvent;
  final VoidCallback onOpenCreateComplaint;

  @override
  State<EventsTab> createState() => EventsTabState();
}

class EventsTabState extends State<EventsTab>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  int _refreshCounter = 0;
  String _searchQuery = '';
  DateTime? _filterDate;

  List<String> get _tabEndpoints {
    return [
      if (widget.user.isPic) ...[
        '/events/managed',
        '/events/managed',
        '/events/managed',
      ] else ...[
        '/events',
        if (widget.user.isGuru) '/events/mine',
      ],
    ];
  }

  List<String> get _tabLabels {
    return [
      if (widget.user.isPic) ...[
        'Disetujui',
        'Perlu Persetujuan',
        'Tidak Disetujui',
      ] else ...[
        'Disetujui',
        if (widget.user.isGuru) 'Agenda Saya',
      ],
    ];
  }

  List<String?> get _tabStatuses =>
      widget.user.isPic ? ['approved', 'pending', 'rejected'] : [null, null];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _tabLabels.length, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void refresh() {
    setState(() => _refreshCounter++);
  }

  Future<void> _approveEvent(EventModel event) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Setujui Pengajuan Agenda?'),
        content: Text(
          'Anda akan menyetujui "${event.eventName}" di ${event.roomName ?? 'Ruangan'} pada ${IndoDateUtils.formatDateRange(event.date, event.endDate)}.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
            ),
            child: const Text('Setujui'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      await widget.api.post('/events/${event.id}/approve');
      refresh();
      _showSuccessSnackBar('Agenda berhasil disetujui.');
    } on ApiException catch (e) {
      _showErrorSnackBar(e.message);
    }
  }

  Future<void> _rejectEvent(EventModel event) async {
    final reasonController = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (ctx) => Dialog(
        insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        child: SingleChildScrollView(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 520),
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text(
                    'Tolak Pengajuan Agenda',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Tolak agenda "${event.eventName}". Tuliskan alasan agar pengaju memahami keputusan.',
                    style: const TextStyle(
                      fontSize: 13,
                      color: Color(0xFF64748B),
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: reasonController,
                    minLines: 7,
                    maxLines: 10,
                    maxLength: 1000,
                    textCapitalization: TextCapitalization.sentences,
                    decoration: const InputDecoration(
                      labelText: 'Alasan Penolakan',
                      hintText: 'Tuliskan alasan penolakan di sini...',
                      alignLabelWithHint: true,
                      prefixIcon: Icon(Icons.edit_note_outlined),
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton(
                        onPressed: () => Navigator.pop(ctx),
                        child: const Text('Batal'),
                      ),
                      const SizedBox(width: 8),
                      FilledButton(
                        onPressed: () {
                          final text = reasonController.text.trim();
                          if (text.isNotEmpty) Navigator.pop(ctx, text);
                        },
                        style: FilledButton.styleFrom(
                          backgroundColor: const Color(0xFFEF4444),
                        ),
                        child: const Text('Tolak Agenda'),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );

    reasonController.dispose();

    if (reason == null || reason.isEmpty) return;

    try {
      await widget.api.post('/events/${event.id}/reject', {
        'rejection_reason': reason,
      });
      refresh();
      _showSuccessSnackBar('Agenda telah ditolak.');
    } on ApiException catch (e) {
      _showErrorSnackBar(e.message);
    }
  }

  void _showSuccessSnackBar(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: const Color(0xFF10B981)),
    );
  }

  void _showErrorSnackBar(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: const Color(0xFFEF4444)),
    );
  }

  Future<void> _pickFilterDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _filterDate ?? DateTime.now(),
      firstDate: DateTime(2025),
      lastDate: DateTime(2030),
      helpText: 'Filter Berdasarkan Tanggal',
    );
    if (picked != null) {
      setState(() => _filterDate = picked);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool showTabs = _tabLabels.length > 1;

    return Scaffold(
      floatingActionButton: widget.user.isGuru
          ? FloatingActionButton.extended(
              onPressed: widget.onOpenCreateEvent,
              icon: const Icon(Icons.add),
              label: const Text('Ajukan Agenda'),
            )
          : null,
      body: Column(
        children: [
          // Filter & Search bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 4),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    decoration: InputDecoration(
                      hintText: 'Cari agenda kegiatan...',
                      prefixIcon: const Icon(Icons.search, size: 20),
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 10,
                      ),
                      suffixIcon: _searchQuery.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 18),
                              onPressed: () =>
                                  setState(() => _searchQuery = ''),
                            )
                          : null,
                    ),
                    onChanged: (val) =>
                        setState(() => _searchQuery = val.trim().toLowerCase()),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filledTonal(
                  tooltip: _filterDate == null
                      ? 'Filter Tanggal'
                      : 'Hapus Filter Tanggal',
                  onPressed: _filterDate == null
                      ? _pickFilterDate
                      : () => setState(() => _filterDate = null),
                  icon: Icon(
                    _filterDate == null
                        ? Icons.calendar_today_outlined
                        : Icons.event_busy,
                    color: _filterDate == null
                        ? AppTheme.primaryColor
                        : const Color(0xFFEF4444),
                  ),
                ),
              ],
            ),
          ),

          if (_filterDate != null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.filter_alt,
                          size: 14,
                          color: AppTheme.primaryColor,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          'Tanggal: ${IndoDateUtils.formatShort(_filterDate!)}',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: AppTheme.primaryColor,
                          ),
                        ),
                        const SizedBox(width: 4),
                        InkWell(
                          onTap: () => setState(() => _filterDate = null),
                          child: const Icon(
                            Icons.close,
                            size: 14,
                            color: AppTheme.primaryColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

          if (showTabs)
            TabBar(
              controller: _tabController,
              tabs: _tabLabels.map((label) => Tab(text: label)).toList(),
            ),

          Expanded(
            child: showTabs
                ? TabBarView(
                    controller: _tabController,
                    children: List.generate(_tabEndpoints.length, (index) {
                      final endpoint = _tabEndpoints[index];
                      final isPendingTab = endpoint == '/events/pending';
                      final isPicPendingTab =
                          widget.user.isPic && _tabStatuses[index] == 'pending';
                      return _EventListView(
                        key: ValueKey(
                          '$endpoint-${_tabStatuses[index]}-$_refreshCounter',
                        ),
                        api: widget.api,
                        endpoint: endpoint,
                        statusFilter: _tabStatuses[index],
                        searchQuery: _searchQuery,
                        filterDate: _filterDate,
                        canManage: isPendingTab || isPicPendingTab,
                        canReportComplaint: widget.user.isGuru,
                        onApprove: _approveEvent,
                        onReject: _rejectEvent,
                        onRefresh: refresh,
                        onOpenCreateComplaint: widget.onOpenCreateComplaint,
                      );
                    }),
                  )
                : _EventListView(
                    key: ValueKey('/events-$_refreshCounter'),
                    api: widget.api,
                    endpoint: '/events',
                    statusFilter: null,
                    searchQuery: _searchQuery,
                    filterDate: _filterDate,
                    canManage: false,
                    canReportComplaint: widget.user.isGuru,
                    onApprove: _approveEvent,
                    onReject: _rejectEvent,
                    onRefresh: refresh,
                    onOpenCreateComplaint: widget.onOpenCreateComplaint,
                  ),
          ),
        ],
      ),
    );
  }
}

class _EventListView extends StatefulWidget {
  const _EventListView({
    required this.api,
    required this.endpoint,
    required this.statusFilter,
    required this.searchQuery,
    required this.filterDate,
    required this.canManage,
    required this.canReportComplaint,
    required this.onApprove,
    required this.onReject,
    required this.onRefresh,
    required this.onOpenCreateComplaint,
    super.key,
  });

  final ApiClient api;
  final String endpoint;
  final String? statusFilter;
  final String searchQuery;
  final DateTime? filterDate;
  final bool canManage;
  final bool canReportComplaint;
  final ValueChanged<EventModel> onApprove;
  final ValueChanged<EventModel> onReject;
  final VoidCallback onRefresh;
  final VoidCallback onOpenCreateComplaint;

  @override
  State<_EventListView> createState() => _EventListViewState();
}

class _EventListViewState extends State<_EventListView> {
  late Future<List<EventModel>> _eventsFuture;
  final ScrollController _scrollController = ScrollController();
  static const int _pageSize = 6;
  int _currentPage = 0;

  @override
  void initState() {
    super.initState();
    _eventsFuture = _loadEvents();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant _EventListView oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.searchQuery != widget.searchQuery ||
        oldWidget.filterDate != widget.filterDate ||
        oldWidget.endpoint != widget.endpoint ||
        oldWidget.statusFilter != widget.statusFilter) {
      setState(() => _currentPage = 0);
    }
  }

  /// Urutan Ruangan Standar Sekolah:
  /// 1. Ruang Wijaya
  /// 2. DAK
  /// 3. Lab 1 - 21
  /// 4. Ruang 1 - seterusnya (termasuk Ruang Kelas 1 - 32)
  /// 5. Ruangan lainnya
  static List<dynamic> _getRoomRank(String rawName) {
    final name = rawName.trim().replaceAll(RegExp(r'\s+'), ' ');
    final lower = name.toLowerCase();

    // 1. Ruang Wijaya
    if (lower.contains('wijaya')) {
      return [0, 0, lower];
    }

    // 2. DAK
    if (lower.contains('dak')) {
      return [0, 1, lower];
    }

    // 3. Lab 1 - 21
    final labMatch =
        RegExp(r'^lab\s*(\d+)', caseSensitive: false).firstMatch(name);
    if (labMatch != null) {
      return [1, int.tryParse(labMatch.group(1)!) ?? 0, lower];
    }

    // 4. Ruang 1 - seterusnya (termasuk Ruang Kelas 1 - 32 atau Ruang 1 - n)
    final roomMatch = RegExp(
      r'^(?:ruang\s+kelas|ruang|kelas)\s*(\d+)',
      caseSensitive: false,
    ).firstMatch(name);
    if (roomMatch != null) {
      return [2, int.tryParse(roomMatch.group(1)!) ?? 0, lower];
    }

    // 5. Ruangan lainnya
    return [3, 0, lower];
  }

  static int _compareEvents(EventModel a, EventModel b) {
    final rankA = _getRoomRank(a.roomName ?? '');
    final rankB = _getRoomRank(b.roomName ?? '');

    // 1. Urutkan berdasarkan prioritas ruangan sekolah
    if (rankA[0] != rankB[0]) {
      return (rankA[0] as int).compareTo(rankB[0] as int);
    }
    if (rankA[1] != rankB[1]) {
      return (rankA[1] as int).compareTo(rankB[1] as int);
    }
    final roomCompare = (rankA[2] as String).compareTo(rankB[2] as String);
    if (roomCompare != 0) {
      return roomCompare;
    }

    // 2. Jika ruangan sama, urutkan tanggal terbaru/terdekat dahulu
    final dateCompare = b.date.compareTo(a.date);
    if (dateCompare != 0) {
      return dateCompare;
    }

    // 3. Jika tanggal sama, urutkan jam mulai
    return a.startTime.compareTo(b.startTime);
  }

  Future<List<EventModel>> _loadEvents() async {
    final response =
        await widget.api.get(widget.endpoint) as Map<String, dynamic>;
    final rawList = response['data'] as List<dynamic>? ?? [];
    return rawList
        .map((e) => EventModel.fromJson(Map<String, dynamic>.from(e as Map)))
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<EventModel>>(
      future: _eventsFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.cloud_off,
                    size: 40,
                    color: Color(0xFFEF4444),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Gagal memuat agenda: ${snapshot.error}',
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton(
                    onPressed: widget.onRefresh,
                    child: const Text('Coba Lagi'),
                  ),
                ],
              ),
            ),
          );
        }

        // 1. Urutkan daftar agenda berdasarkan prioritas ruangan standar sekolah:
        // Ruang Wijaya -> DAK -> Lab 1-21 -> Ruang 1-seterusnya -> Lainnya
        final events = List<EventModel>.from(snapshot.data!)
          ..sort(_compareEvents);

        final filtered = events.where((e) {
          if (widget.statusFilter != null &&
              e.status.toLowerCase() != widget.statusFilter) {
            return false;
          }

          if (widget.searchQuery.isNotEmpty) {
            final matchName = e.eventName.toLowerCase().contains(
              widget.searchQuery,
            );
            final matchRoom = (e.roomName ?? '').toLowerCase().contains(
              widget.searchQuery,
            );
            if (!matchName && !matchRoom) return false;
          }

          if (widget.filterDate != null) {
            if (!e.occursOn(widget.filterDate!)) return false;
          }

          return true;
        }).toList();

        if (filtered.isEmpty) {
          return RefreshIndicator(
            onRefresh: () async => widget.onRefresh(),
            child: ListView(
              children: [
                SizedBox(
                  height: MediaQuery.of(context).size.height * 0.4,
                  child: Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.event_available,
                          size: 48,
                          color: Colors.grey.shade400,
                        ),
                        const SizedBox(height: 10),
                        const Text(
                          'Tidak ada agenda yang sesuai filter.',
                          style: TextStyle(color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          );
        }

        final totalPages = (filtered.length / _pageSize).ceil();
        final safePage = _currentPage >= totalPages
            ? (totalPages > 0 ? totalPages - 1 : 0)
            : _currentPage;
        final pagedEvents = filtered
            .skip(safePage * _pageSize)
            .take(_pageSize)
            .toList();

        return Column(
          children: [
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async => widget.onRefresh(),
                child: ListView.builder(
                  controller: _scrollController,
                  physics: const AlwaysScrollableScrollPhysics(
                    parent: ClampingScrollPhysics(),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 8,
                  ),
                  itemCount: pagedEvents.length,
                  itemBuilder: (context, index) {
                    final event = pagedEvents[index];
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: AppTheme.primaryColor.withValues(
                                alpha: 0.12,
                              ),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(
                              Icons.calendar_month,
                              color: AppTheme.primaryColor,
                              size: 24,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  event.eventName,
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                    color: Color(0xFF0F172A),
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Row(
                                  children: [
                                    const Icon(
                                      Icons.meeting_room,
                                      size: 14,
                                      color: Color(0xFF64748B),
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      event.roomName ?? 'Ruangan',
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600,
                                        color: AppTheme.primaryColor,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              color: AppConstants.statusColor(
                                event.status,
                              ).withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              event.statusDisplay,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: AppConstants.statusColor(event.status),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      const Divider(height: 1),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          const Icon(
                            Icons.access_time,
                            size: 14,
                            color: Color(0xFF64748B),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            '${IndoDateUtils.formatDateRange(event.date, event.endDate)}  •  ${IndoDateUtils.formatTimeRange(event.startTime, event.endTime)}',
                            style: const TextStyle(
                              fontSize: 12,
                              color: Color(0xFF475569),
                            ),
                          ),
                        ],
                      ),
                      if (event.rejectionReason != null &&
                          event.rejectionReason!.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: const Color(
                              0xFFEF4444,
                            ).withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: const Color(
                                0xFFEF4444,
                              ).withValues(alpha: 0.2),
                            ),
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Icon(
                                Icons.info_outline,
                                size: 16,
                                color: Color(0xFFEF4444),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  'Alasan Penolakan: ${event.rejectionReason}',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: Color(0xFFB91C1C),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                      if (widget.canReportComplaint) ...[
                        const SizedBox(height: 6),
                        Align(
                          alignment: Alignment.centerRight,
                          child: TextButton.icon(
                            onPressed: widget.onOpenCreateComplaint,
                            icon: const Icon(Icons.build_outlined, size: 16),
                            label: const Text('Laporkan Kerusakan'),
                          ),
                        ),
                      ],
                      if (widget.canManage) ...[
                        const SizedBox(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: [
                            OutlinedButton.icon(
                              onPressed: () => widget.onReject(event),
                              icon: const Icon(Icons.close, size: 16),
                              label: const Text('Tolak'),
                              style: OutlinedButton.styleFrom(
                                foregroundColor: const Color(0xFFEF4444),
                                side: const BorderSide(
                                  color: Color(0xFFEF4444),
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            FilledButton.icon(
                              onPressed: () => widget.onApprove(event),
                              icon: const Icon(Icons.check, size: 16),
                              label: const Text('Setujui'),
                              style: FilledButton.styleFrom(
                                backgroundColor: const Color(0xFF10B981),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ),
      _buildPaginationBar(
              currentPage: safePage,
              totalPages: totalPages,
              totalItems: filtered.length,
              onPageChanged: (newPage) {
                setState(() => _currentPage = newPage);
                if (_scrollController.hasClients) {
                  _scrollController.jumpTo(0);
                }
              },
            ),
          ],
        );
      },
    );
  }

  /// Kontrol Pagination Agenda & Jadwal (Bersih, Responsif & Cepat)
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

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(
          top: BorderSide(color: Color(0xFFE2E8F0)),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Text(
              'Menampilkan $startItem-$endItem dari $totalItems agenda',
              style: const TextStyle(
                fontSize: 11.5,
                color: Color(0xFF64748B),
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
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    color: currentPage > 0
                        ? Colors.white
                        : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: currentPage > 0
                          ? const Color(0xFFCBD5E1)
                          : const Color(0xFFE2E8F0),
                    ),
                  ),
                  alignment: Alignment.center,
                  child: Icon(
                    Icons.chevron_left,
                    size: 18,
                    color: currentPage > 0
                        ? const Color(0xFF0F172A)
                        : const Color(0xFF94A3B8),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 10),
                child: Text(
                  '${currentPage + 1} / $totalPages',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF0F172A),
                  ),
                ),
              ),
              InkWell(
                onTap: currentPage < totalPages - 1
                    ? () => onPageChanged(currentPage + 1)
                    : null,
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    color: currentPage < totalPages - 1
                        ? Colors.white
                        : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: currentPage < totalPages - 1
                          ? const Color(0xFFCBD5E1)
                          : const Color(0xFFE2E8F0),
                    ),
                  ),
                  alignment: Alignment.center,
                  child: Icon(
                    Icons.chevron_right,
                    size: 18,
                    color: currentPage < totalPages - 1
                        ? const Color(0xFF0F172A)
                        : const Color(0xFF94A3B8),
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
