import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/session_manager.dart';
import '../../models/room_model.dart';
import '../../models/user_model.dart';
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
            const SizedBox(height: 6),
            _buildInfoRow('Server API', widget.api.baseUrl),
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
      DashboardTab(
        api: widget.api,
        user: widget.user,
        onNavigateToTab: (index) => setState(() => _selectedIndex = index),
        onOpenCreateEvent: _openCreateEventSheet,
        onOpenCreateComplaint: _openCreateComplaintSheet,
      ),
      RoomsTab(api: widget.api, user: widget.user),
      EventsTab(
        key: _eventsTabKey,
        api: widget.api,
        user: widget.user,
        onOpenCreateEvent: _openCreateEventSheet,
        onOpenCreateComplaint: _openCreateComplaintSheet,
      ),
      ComplaintsTab(
        key: _complaintsTabKey,
        api: widget.api,
        user: widget.user,
        onOpenCreateComplaint: _openCreateComplaintSheet,
      ),
      PublicCalendarScreen(api: widget.api, showAppBar: false),
    ];

    final titles = [
      'Beranda',
      'Daftar Ruangan',
      'Agenda & Jadwal',
      'Pengaduan Sarpras',
      'Kalender Sekolah',
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(titles[_selectedIndex]),
        actions: [
          InkWell(
            onTap: _showProfileDialog,
            borderRadius: BorderRadius.circular(20),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: widget.user.roleBadgeColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      widget.user.roleDisplayName,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: widget.user.roleBadgeColor,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  CircleAvatar(
                    radius: 16,
                    backgroundColor: widget.user.roleBadgeColor,
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
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Navigasi',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      widget.user.displayName,
                      style: const TextStyle(color: Color(0xFF64748B)),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              ...[
                (Icons.dashboard_outlined, 'Dashboard'),
                (Icons.meeting_room_outlined, 'Daftar Ruangan'),
                (Icons.event_note_outlined, 'Peminjaman & Agenda'),
                (Icons.build_outlined, 'Pengaduan Fasilitas'),
                (Icons.calendar_month_outlined, 'Kalender Interaktif'),
              ].asMap().entries.map((entry) {
                final index = entry.key;
                final (icon, label) = entry.value;
                return ListTile(
                  leading: Icon(icon),
                  title: Text(label),
                  selected: _selectedIndex == index,
                  onTap: () {
                    setState(() => _selectedIndex = index);
                    Navigator.pop(context);
                  },
                );
              }),
            ],
          ),
        ),
      ),
      body: IndexedStack(index: _selectedIndex, children: pages),
    );
  }
}
