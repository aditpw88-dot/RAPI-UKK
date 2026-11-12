import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/session_manager.dart';
import '../../models/room_model.dart';
import '../../models/user_model.dart';
import '../../theme/app_theme.dart';
import '../complaints/complaints_tab.dart';
import '../complaints/create_complaint_sheet.dart';
import '../calendar/public_calendar_screen.dart';
import '../dashboard/dashboard_tab.dart';
import '../events/create_event_sheet.dart';
import '../events/events_tab.dart';
import '../rooms/rooms_tab.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({
    required this.api,
    required this.user,
    required this.onLogout,
    super.key,
  });

  final ApiClient api;
  final UserModel user;
  final VoidCallback onLogout;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _selectedIndex = 0;
  final Set<int> _loadedTabs = {0};

  void _onNavigateToTab(int index) {
    if (_selectedIndex != index) {
      setState(() {
        _selectedIndex = index;
        _loadedTabs.add(index);
      });
    }
  }

  final GlobalKey<EventsTabState> _eventsTabKey = GlobalKey<EventsTabState>();
  final GlobalKey<ComplaintsTabState> _complaintsTabKey =
      GlobalKey<ComplaintsTabState>();

  Future<List<RoomModel>> _loadActiveRooms() async {
    final res = await widget.api.get('/rooms') as Map<String, dynamic>;
    final raw = res['data'] as List<dynamic>? ?? [];
    return raw
        .map((e) => RoomModel.fromJson(Map<String, dynamic>.from(e as Map)))
        .toList();
  }

  Future<void> _openCreateEventSheet() async {
    try {
      final rooms = await _loadActiveRooms();
      if (!mounted) return;
      if (rooms.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Belum ada ruangan aktif.')),
        );
        return;
      }

      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        builder: (ctx) => CreateEventSheet(
          api: widget.api,
          rooms: rooms,
          onEventCreated: () {
            _eventsTabKey.currentState?.refresh();
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Pengajuan agenda berhasil dikirim.'),
                backgroundColor: Color(0xFF10B981),
              ),
            );
          },
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Gagal memuat form agenda: $e')));
    }
  }

  Future<void> _openCreateComplaintSheet() async {
    try {
      final rooms = await _loadActiveRooms();
      if (!mounted) return;
      if (rooms.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Belum ada ruangan aktif.')),
        );
        return;
      }

      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        builder: (ctx) => CreateComplaintSheet(
          api: widget.api,
          rooms: rooms,
          onComplaintCreated: () {
            _complaintsTabKey.currentState?.refresh();
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Pengaduan sarpras berhasil dikirim.'),
                backgroundColor: Color(0xFF10B981),
              ),
            );
          },
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal memuat form pengaduan: $e')),
      );
    }
  }

  Future<void> _showProfileDialog() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: [
            CircleAvatar(
              backgroundColor: widget.user.roleBadgeColor.withValues(
                alpha: 0.15,
              ),
              child: Icon(
                widget.user.roleIcon,
                color: widget.user.roleBadgeColor,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.user.displayName,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    widget.user.roleDisplayName,
                    style: TextStyle(
                      fontSize: 12,
                      color: widget.user.roleBadgeColor,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Divider(),
            const SizedBox(height: 8),
            _buildInfoRow('Username', widget.user.username),
            const SizedBox(height: 6),
            _buildInfoRow('Email', widget.user.email),
            const SizedBox(height: 12),
            const Text(
              'Aplikasi mobile ini dikhususkan bagi Guru dan PIC Ruang.',
              style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Tutup'),
          ),
          FilledButton.icon(
            onPressed: () => Navigator.pop(ctx, true),
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFEF4444),
            ),
            icon: const Icon(Icons.logout, size: 16),
            label: const Text('Keluar'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        await widget.api.post('/logout');
      } catch (_) {}
      await SessionManager.clearSession();
      widget.onLogout();
    }
  }

  Widget _buildInfoRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 80,
          child: Text(
            label,
            style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: Color(0xFF1E293B),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      _loadedTabs.contains(0)
          ? DashboardTab(
              api: widget.api,
              user: widget.user,
              onNavigateToTab: _onNavigateToTab,
              onOpenCreateEvent: _openCreateEventSheet,
              onOpenCreateComplaint: _openCreateComplaintSheet,
            )
          : const SizedBox.shrink(),
      _loadedTabs.contains(1)
          ? RoomsTab(api: widget.api, user: widget.user)
          : const SizedBox.shrink(),
      _loadedTabs.contains(2)
          ? EventsTab(
              key: _eventsTabKey,
              api: widget.api,
              user: widget.user,
              onOpenCreateEvent: _openCreateEventSheet,
              onOpenCreateComplaint: _openCreateComplaintSheet,
            )
          : const SizedBox.shrink(),
      _loadedTabs.contains(3)
          ? ComplaintsTab(
              key: _complaintsTabKey,
              api: widget.api,
              user: widget.user,
              onOpenCreateComplaint: _openCreateComplaintSheet,
            )
          : const SizedBox.shrink(),
      _loadedTabs.contains(4)
          ? PublicCalendarScreen(api: widget.api, showAppBar: false)
          : const SizedBox.shrink(),
    ];

    final titles = [
      'Beranda',
      'Daftar Ruangan',
      'Agenda & Jadwal',
      'Pengaduan Kerusakan',
      'Kalender Sekolah',
    ];

    return Scaffold(
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: AppTheme.headerGradient,
            border: Border(
              bottom: BorderSide(color: AppTheme.headerBorder, width: 2),
            ),
          ),
        ),
        iconTheme: IconThemeData(color: AppTheme.brandGold),
        title: Text(
          titles[_selectedIndex],
          style: TextStyle(
            color: AppTheme.brandGold,
            fontSize: 16.5,
            fontWeight: FontWeight.w800,
          ),
        ),
        actions: [
          InkWell(
            onTap: _showProfileDialog,
            borderRadius: BorderRadius.circular(20),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: AppTheme.brandAmber.withValues(alpha: 0.25),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: AppTheme.brandAmber.withValues(alpha: 0.5),
                        width: 1,
                      ),
                    ),
                    child: Text(
                      widget.user.roleDisplayName,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.brandGold,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  CircleAvatar(
                    radius: 16,
                    backgroundColor: AppTheme.brandAmber,
                    child: Text(
                      widget.user.displayName.isNotEmpty
                          ? widget.user.displayName[0].toUpperCase()
                          : 'U',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      drawer: Drawer(
        child: SafeArea(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 18),
                decoration: BoxDecoration(
                  gradient: AppTheme.headerGradient,
                  border: Border(
                    bottom: BorderSide(color: AppTheme.headerBorder, width: 2),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
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
                                  color: Colors.white,
                                  size: 18,
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'RAPI Sekolah',
                              style: TextStyle(
                                color: AppTheme.brandGold,
                                fontWeight: FontWeight.w800,
                                fontSize: 15,
                              ),
                            ),
                            Text(
                              'Agenda & Fasilitas Ruang',
                              style: TextStyle(
                                color: AppTheme.brandGoldSub,
                                fontSize: 11,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    Text(
                      widget.user.displayName,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      widget.user.email,
                      style: const TextStyle(
                        color: Color(0xFFE7D2BA),
                        fontSize: 11.5,
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  children: [
                    ...[
                      (Icons.dashboard_outlined, Icons.dashboard_rounded, 'Dashboard'),
                      (Icons.meeting_room_outlined, Icons.meeting_room_rounded, 'Daftar Ruangan'),
                      (Icons.event_note_outlined, Icons.event_note_rounded, 'Peminjaman & Agenda'),
                      (Icons.build_circle_outlined, Icons.build_circle_rounded, 'Pengaduan Kerusakan'),
                      (Icons.calendar_month_outlined, Icons.calendar_month_rounded, 'Kalender Sekolah'),
                    ].asMap().entries.map((entry) {
                      final index = entry.key;
                      final (icon, activeIcon, label) = entry.value;
                      final isSelected = _selectedIndex == index;
                      return ListTile(
                        leading: Icon(
                          isSelected ? activeIcon : icon,
                          color: isSelected ? AppTheme.brandAmber : AppTheme.textSecondary,
                        ),
                        title: Text(
                          label,
                          style: TextStyle(
                            color: isSelected ? AppTheme.brandAmber : AppTheme.textPrimary,
                            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                            fontSize: 13.5,
                          ),
                        ),
                        selected: isSelected,
                        selectedTileColor: AppTheme.brandAmberLight,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 18),
                        onTap: () {
                          _onNavigateToTab(index);
                          Navigator.pop(context);
                        },
                      );
                    }),
                  ],
                ),
              ),
              const Divider(height: 1),
              Padding(
                padding: const EdgeInsets.all(12),
                child: OutlinedButton.icon(
                  onPressed: _showProfileDialog,
                  icon: const Icon(Icons.manage_accounts_outlined, size: 18),
                  label: const Text('Akun & Info'),
                ),
              ),
            ],
          ),
        ),
      ),
      body: IndexedStack(index: _selectedIndex, children: pages),
    );
  }
}
