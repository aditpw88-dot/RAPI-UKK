import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/date_utils.dart';
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

    return _DashboardData(
      totalApprovedEvents: data['total_approved_events'] as int? ?? 0,
      totalRooms: data['total_rooms'] as int? ?? 0,
      myEventsCount: data['my_events_count'] as int? ?? 0,
      pendingEventsCount: data['pending_events_count'] as int? ?? 0,
      complaintsCount: data['complaints_count'] as int? ?? 0,
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
            physics: const AlwaysScrollableScrollPhysics(
              parent: ClampingScrollPhysics(),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            children: [
              // 1. Welcome Hero Card
              RepaintBoundary(child: _buildHeroCard()),

              const SizedBox(height: 16),

              // 2. Metric Stats Grid (4 kartu metrik)
              RepaintBoundary(child: _buildStatsGrid(data)),

              const SizedBox(height: 18),

              // 3. Quick Actions (4 tombol penuh horizontal)
              RepaintBoundary(child: _buildQuickActions()),

              const SizedBox(height: 20),

              // 4. Panduan & SOP Operasional Ruangan (Ringan, Rapi & Bermanfaat)
              RepaintBoundary(child: _buildSopSection()),
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

  /// Tombol Aksi Cepat yang Memenuhi Ruang Horizontal Secara Penuh & Sempurna
  Widget _buildQuickActions() {
    Widget buildBtn({
      required IconData icon,
      required String label,
      required Color color,
      required Color bgColor,
      required Color borderColor,
      required VoidCallback onTap,
    }) {
      return Expanded(
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(10),
          child: Container(
            height: 42,
            padding: const EdgeInsets.symmetric(horizontal: 10),
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: borderColor),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, size: 17, color: color),
                const SizedBox(width: 7),
                Flexible(
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: color,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final btn1 = widget.user.isPic
        ? buildBtn(
            icon: Icons.fact_check_outlined,
            label: 'Tinjau Pengajuan',
            color: const Color(0xFFB45309),
            bgColor: const Color(0xFFFFFBEB),
            borderColor: const Color(0xFFFDE68A),
            onTap: () => widget.onNavigateToTab(2),
          )
        : buildBtn(
            icon: Icons.add_circle_outline,
            label: 'Ajukan Agenda',
            color: AppTheme.brandAmber,
            bgColor: AppTheme.brandAmberLight,
            borderColor: const Color(0xFFFED7AA),
            onTap: widget.onOpenCreateEvent,
          );

    final btn2 = widget.user.isPic
        ? buildBtn(
            icon: Icons.build_outlined,
            label: 'Update Status Sarpras',
            color: const Color(0xFF334155),
            bgColor: const Color(0xFFF1F5F9),
            borderColor: const Color(0xFFE2E8F0),
            onTap: () => widget.onNavigateToTab(3),
          )
        : buildBtn(
            icon: Icons.add_a_photo_outlined,
            label: 'Lapor Kerusakan',
            color: const Color(0xFFB45309),
            bgColor: const Color(0xFFFFFBEB),
            borderColor: const Color(0xFFFDE68A),
            onTap: widget.onOpenCreateComplaint,
          );

    final btn3 = buildBtn(
      icon: Icons.search_rounded,
      label: 'Cari Ruangan',
      color: const Color(0xFF2563EB),
      bgColor: const Color(0xFFEFF6FF),
      borderColor: const Color(0xFFDBEAFE),
      onTap: () => widget.onNavigateToTab(1),
    );

    final btn4 = buildBtn(
      icon: Icons.calendar_month_outlined,
      label: 'Lihat Kalender',
      color: const Color(0xFF0F172A),
      bgColor: const Color(0xFFF8FAFC),
      borderColor: const Color(0xFFE2E8F0),
      onTap: () => widget.onNavigateToTab(4),
    );

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
        LayoutBuilder(
          builder: (context, constraints) {
            // Pada layar lebar (desktop/web/tablet >= 640px): 1 baris 4 tombol penuh
            if (constraints.maxWidth >= 640) {
              return Row(
                children: [
                  btn1,
                  const SizedBox(width: 8),
                  btn2,
                  const SizedBox(width: 8),
                  btn3,
                  const SizedBox(width: 8),
                  btn4,
                ],
              );
            }

            // Pada layar ponsel (< 640px): 2 baris x 2 tombol penuh
            return Column(
              children: [
                Row(
                  children: [
                    btn1,
                    const SizedBox(width: 8),
                    btn2,
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    btn3,
                    const SizedBox(width: 8),
                    btn4,
                  ],
                ),
              ],
            );
          },
        ),
      ],
    );
  }

  /// Bagian SOP & Pengawasan Ruangan (Sangat Ringan, Cepat & Praktis untuk PIC)
  Widget _buildSopSection() {
    final isPic = widget.user.isPic;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE4E4E7)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: const Color(0xFF09090B).withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(
                        Icons.assignment_turned_in_outlined,
                        size: 18,
                        color: Color(0xFF09090B),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Flexible(
                      child: Text(
                        isPic
                            ? 'SOP & Pengawasan PIC Ruang'
                            : 'Panduan & Ketentuan Peminjaman',
                        style: const TextStyle(
                          fontSize: 13.5,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF09090B),
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                decoration: BoxDecoration(
                  color: const Color(0xFFECFDF5),
                  borderRadius: BorderRadius.circular(6),
                  border: Border.all(color: const Color(0xFFA7F3D0)),
                ),
                child: const Text(
                  'Standar Resmi',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF047857),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          _buildSopItem(
            icon: Icons.check_circle_outline,
            iconColor: const Color(0xFF10B981),
            bgColor: const Color(0xFFECFDF5),
            title: isPic
                ? 'Kesiapan Sarana & Fasilitas'
                : 'Pengajuan Sesuai Prosedur',
            description: isPic
                ? 'Pastikan AC, LCD proyektor, kelistrikan, dan kebersihan ruang siap sebelum kegiatan dimulai.'
                : 'Ajukan permohonan ruang minimal 1-2 hari sebelum pelaksanaan kegiatan sekolah.',
          ),
          const SizedBox(height: 10),

          _buildSopItem(
            icon: isPic ? Icons.fact_check_outlined : Icons.event_available_outlined,
            iconColor: const Color(0xFFD97706),
            bgColor: const Color(0xFFFFFBEB),
            title: isPic
                ? 'Verifikasi & Validasi Pengajuan'
                : 'Menunggu Persetujuan PIC',
            description: isPic
                ? 'Tinjau kesesuaian kapasitas ruang dan waktu kegiatan melalui menu Tinjau Pengajuan.'
                : 'Pantau status permohonan Anda. Penggunaan hanya sah jika status telah "Disetujui".',
          ),
          const SizedBox(height: 10),

          _buildSopItem(
            icon: Icons.lock_outline,
            iconColor: const Color(0xFF2563EB),
            bgColor: const Color(0xFFEFF6FF),
            title: 'Pengamanan & Ketertiban Selesai Pakai',
            description:
                'Pastikan semua perangkat elektronik telah dimatikan, sampah dibersihkan, dan pintu ruang terkunci rapat.',
          ),
          const SizedBox(height: 10),

          _buildSopItem(
            icon: Icons.build_outlined,
            iconColor: const Color(0xFF64748B),
            bgColor: const Color(0xFFF1F5F9),
            title: 'Pelaporan Kendala & Kerusakan',
            description:
                'Jika ditemukan kerusakan fasilitas atau sarana tidak berfungsi, laporkan segera melalui menu Update Status Sarpras.',
          ),
        ],
      ),
    );
  }

  Widget _buildSopItem({
    required IconData icon,
    required Color iconColor,
    required Color bgColor,
    required String title,
    required String description,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          margin: const EdgeInsets.only(top: 2),
          padding: const EdgeInsets.all(6),
          decoration: BoxDecoration(
            color: bgColor,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, size: 16, color: iconColor),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF18181B),
                ),
              ),
              const SizedBox(height: 2),
              Text(
                description,
                style: const TextStyle(
                  fontSize: 11.5,
                  color: Color(0xFF71717A),
                  height: 1.35,
                ),
              ),
            ],
          ),
        ),
      ],
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
  });

  final int totalApprovedEvents;
  final int totalRooms;
  final int myEventsCount;
  final int pendingEventsCount;
  final int complaintsCount;
}
