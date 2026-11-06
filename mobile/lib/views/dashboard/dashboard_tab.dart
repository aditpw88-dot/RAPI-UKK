import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/date_utils.dart';
import '../../models/event_model.dart';
import '../../models/user_model.dart';
import '../../theme/app_theme.dart';

class DashboardTab extends StatefulWidget {
  const DashboardTab({
    required this.api,
    required this.user,
    required this.onNavigateToTab,
    required this.onOpenCreateEvent,
    required this.onOpenCreateComplaint,
    super.key,
  });

  final ApiClient api;
  final UserModel user;
  final void Function(int tabIndex) onNavigateToTab;
  final VoidCallback onOpenCreateEvent;
  final VoidCallback onOpenCreateComplaint;

  @override
  State<DashboardTab> createState() => _DashboardTabState();
}

class _DashboardTabState extends State<DashboardTab> {
  late Future<_DashboardData> _dataFuture;

  @override
  void initState() {
    super.initState();
    _dataFuture = _loadDashboard();
  }

  void _refresh() {
    setState(() {
      _dataFuture = _loadDashboard();
    });
  }

  Future<_DashboardData> _loadDashboard() async {
    final response =
        await widget.api.get('/dashboard/summary') as Map<String, dynamic>;
    final data = response['data'] as Map<String, dynamic>;
    final rawEvents = data['upcoming_events'] as List<dynamic>? ?? [];
    final upcomingEvents = rawEvents
        .map((e) => EventModel.fromJson(Map<String, dynamic>.from(e as Map)))
        .toList();

    return _DashboardData(
      totalApprovedEvents: data['total_approved_events'] as int? ?? 0,
      totalRooms: data['total_rooms'] as int? ?? 0,
      myEventsCount: data['my_events_count'] as int? ?? 0,
      pendingEventsCount: data['pending_events_count'] as int? ?? 0,
      complaintsCount: data['complaints_count'] as int? ?? 0,
      upcomingEvents: upcomingEvents,
    );
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async => _refresh(),
      child: FutureBuilder<_DashboardData>(
        future: _dataFuture,
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
                      size: 48,
                      color: Color(0xFFEF4444),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Gagal memuat ringkasan: ${snapshot.error}',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    OutlinedButton.icon(
                      onPressed: _refresh,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Coba Lagi'),
                    ),
                  ],
                ),
              ),
            );
          }

          final data = snapshot.data!;

          return ListView(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            children: [
              // Welcome Hero Card
              _buildHeroCard(),

              const SizedBox(height: 16),

              // Metric Stats Grid
              _buildStatsGrid(data),

              const SizedBox(height: 18),

              // Quick Actions
              _buildQuickActions(),

              const SizedBox(height: 20),

              // Upcoming Events Preview Section
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Agenda Terdekat',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                  TextButton(
                    onPressed: () =>
                        widget.onNavigateToTab(2), // go to events tab
                    child: const Text('Lihat Semua'),
                  ),
                ],
              ),
              const SizedBox(height: 6),

              if (data.upcomingEvents.isEmpty)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      children: [
                        Icon(
                          Icons.event_busy,
                          size: 40,
                          color: Colors.grey.shade400,
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Belum ada agenda sekolah terdekat yang disetujui.',
                          style: TextStyle(color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                )
              else
                ...data.upcomingEvents.map((event) => _buildEventItem(event)),
            ],
          );
        },
      ),
    );
  }

  Widget _buildHeroCard() {
    final today = DateTime.now();

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppTheme.cardColor,
        border: Border.all(color: AppTheme.cardBorder),
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: AppTheme.textPrimary.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: AppTheme.scaffoldBg,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      widget.user.roleIcon,
                      size: 14,
                      color: AppTheme.primaryColor,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      widget.user.roleDisplayName,
                      style: const TextStyle(
                        color: AppTheme.primaryColor,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              Text(
                IndoDateUtils.formatShort(today),
                style: const TextStyle(
                  color: AppTheme.textSecondary,
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Text(
            'Halo, ${widget.user.displayName}!',
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: AppTheme.textPrimary,
              letterSpacing: -0.4,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            widget.user.isGuru
                ? 'Kelola pengajuan penggunaan ruangan dan pantau perbaikan sarana sekolah.'
                : 'Tinjau persetujuan agenda dan perbarui status perbaikan sarana ruangan.',
            style: const TextStyle(
              fontSize: 13,
              color: AppTheme.textSecondary,
              height: 1.3,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsGrid(_DashboardData data) {
    return Row(
      children: [
        // Left Column Stat
        Expanded(
          child: Column(
            children: [
              _StatCard(
                title: 'Agenda Disetujui',
                value: '${data.totalApprovedEvents}',
                icon: Icons.event_available,
                iconColor: const Color(0xFF10B981),
                onTap: () => widget.onNavigateToTab(2),
              ),
              const SizedBox(height: 10),
              if (widget.user.isGuru)
                _StatCard(
                  title: 'Agenda Saya',
                  value: '${data.myEventsCount}',
                  icon: Icons.bookmark_added,
                  iconColor: AppTheme.primaryColor,
                  onTap: () => widget.onNavigateToTab(2),
                )
              else if (widget.user.isPic)
                _StatCard(
                  title: 'Perlu Persetujuan',
                  value: '${data.pendingEventsCount}',
                  icon: Icons.pending_actions,
                  iconColor: const Color(0xFFEF4444),
                  badgeAlert: data.pendingEventsCount > 0,
                  onTap: () => widget.onNavigateToTab(2),
                ),
            ],
          ),
        ),
        const SizedBox(width: 10),
        // Right Column Stat
        Expanded(
          child: Column(
            children: [
              _StatCard(
                title: 'Ruangan Aktif',
                value: '${data.totalRooms}',
                icon: Icons.meeting_room_outlined,
                iconColor: const Color(0xFF3B82F6),
                onTap: () => widget.onNavigateToTab(1),
              ),
              const SizedBox(height: 10),
              _StatCard(
                title: widget.user.isPic ? 'Pengaduan Masuk' : 'Pengaduan Saya',
                value: '${data.complaintsCount}',
                icon: Icons.build_circle_outlined,
                iconColor: const Color(0xFFD97706),
                onTap: () => widget.onNavigateToTab(3),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildQuickActions() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Aksi Cepat',
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w700,
            color: Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 10),
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            if (widget.user.isGuru) ...[
              ActionChip(
                avatar: const Icon(Icons.add, color: AppTheme.primaryColor),
                label: const Text('Ajukan Agenda'),
                backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                onPressed: widget.onOpenCreateEvent,
              ),
              ActionChip(
                avatar: const Icon(
                  Icons.add_a_photo_outlined,
                  color: Color(0xFFD97706),
                ),
                label: const Text('Lapor Kerusakan'),
                backgroundColor: const Color(0xFFD97706).withValues(alpha: 0.1),
                onPressed: widget.onOpenCreateComplaint,
              ),
            ],
            if (widget.user.isPic) ...[
              ActionChip(
                avatar: const Icon(
                  Icons.fact_check_outlined,
                  color: Color(0xFFD97706),
                ),
                label: const Text('Tinjau Pengajuan'),
                backgroundColor: const Color(0xFFD97706).withValues(alpha: 0.1),
                onPressed: () => widget.onNavigateToTab(2),
              ),
              ActionChip(
                avatar: const Icon(
                  Icons.build_outlined,
                  color: AppTheme.primaryColor,
                ),
                label: const Text('Update Status Sarpras'),
                backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                onPressed: () => widget.onNavigateToTab(3),
              ),
            ],
            ActionChip(
              avatar: const Icon(Icons.search, color: Color(0xFF3B82F6)),
              label: const Text('Cari Ruangan'),
              backgroundColor: const Color(0xFF3B82F6).withValues(alpha: 0.1),
              onPressed: () => widget.onNavigateToTab(1),
            ),
            ActionChip(
              avatar: const Icon(
                Icons.calendar_month,
                color: AppTheme.primaryColor,
              ),
              label: const Text('Lihat Kalender'),
              backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
              onPressed: () => widget.onNavigateToTab(4),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildEventItem(EventModel event) {
    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        leading: Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: AppTheme.primaryColor.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(Icons.event_note, color: AppTheme.primaryColor),
        ),
        title: Text(
          event.eventName,
          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
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
                    color: Color(0xFF64748B),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 2),
            Row(
              children: [
                const Icon(
                  Icons.access_time,
                  size: 14,
                  color: Color(0xFF64748B),
                ),
                const SizedBox(width: 4),
                Text(
                  '${IndoDateUtils.formatDateRange(event.date, event.endDate)}  •  ${IndoDateUtils.formatTimeRange(event.startTime, event.endTime)}',
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF64748B),
                  ),
                ),
              ],
            ),
          ],
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: const Color(0xFF10B981).withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Text(
            'Disetujui',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: Color(0xFF10B981),
            ),
          ),
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.iconColor,
    required this.onTap,
    this.badgeAlert = false,
  });

  final String title;
  final String value;
  final IconData icon;
  final Color iconColor;
  final VoidCallback onTap;
  final bool badgeAlert;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: iconColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(icon, color: iconColor, size: 20),
                  ),
                  if (badgeAlert)
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEF4444),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Text(
                        'PENTING',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF0F172A),
                ),
              ),
              const SizedBox(height: 2),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF64748B),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DashboardData {
  _DashboardData({
    required this.totalApprovedEvents,
    required this.totalRooms,
    required this.myEventsCount,
    required this.pendingEventsCount,
    required this.complaintsCount,
    required this.upcomingEvents,
  });

  final int totalApprovedEvents;
  final int totalRooms;
  final int myEventsCount;
  final int pendingEventsCount;
  final int complaintsCount;
  final List<EventModel> upcomingEvents;
}
