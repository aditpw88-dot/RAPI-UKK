import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../core/api_client.dart';
import '../../core/url_opener.dart';
import '../../models/room_model.dart';
import '../../models/user_model.dart';
import '../../theme/app_theme.dart';

class RoomsTab extends StatefulWidget {
  const RoomsTab({required this.api, required this.user, super.key});

  final ApiClient api;
  final UserModel user;

  @override
  State<RoomsTab> createState() => _RoomsTabState();
}

class _RoomsTabState extends State<RoomsTab> {
  late Future<List<RoomModel>> _roomsFuture;
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  String _searchQuery = '';
  String _roomFilter = 'Semua Ruangan';
  static const int _pageSize = 8;
  int _currentPage = 0;

  @override
  void initState() {
    super.initState();
    _roomsFuture = _fetchRooms();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _refresh() {
    setState(() {
      _currentPage = 0;
      _roomsFuture = _fetchRooms();
    });
  }

  /// Urutan Ruangan Standar Sekolah:
  /// 1. Ruang Wijaya
  /// 2. DAK
  /// 3. Lab 1 - 21
  /// 4. Ruang 1 - seterusnya (termasuk Ruang Kelas 1 - 32)
  /// 5. Ruangan lainnya
  static int _compareRoomNames(String nameA, String nameB) {
    List<dynamic> getRank(String rawName) {
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

    final rankA = getRank(nameA);
    final rankB = getRank(nameB);

    if (rankA[0] != rankB[0]) {
      return (rankA[0] as int).compareTo(rankB[0] as int);
    }
    if (rankA[1] != rankB[1]) {
      return (rankA[1] as int).compareTo(rankB[1] as int);
    }
    return (rankA[2] as String).compareTo(rankB[2] as String);
  }

  Future<List<RoomModel>> _fetchRooms() async {
    final response = await widget.api.get('/rooms') as Map<String, dynamic>;
    final rawList = response['data'] as List<dynamic>? ?? [];
    return rawList
        .map((e) => RoomModel.fromJson(Map<String, dynamic>.from(e as Map)))
        .toList();
  }

  Future<void> _showUpdateRoomStatusDialog(RoomModel room) async {
    bool isActive = room.isActive;
    final noteController = TextEditingController(
      text: room.maintenanceNote ?? '',
    );
    bool isSaving = false;
    String? errorText;
    String? conflictWaUrl;
    String? conflictBorrowerName;

    await showDialog<void>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'MANAJEMEN RUANG PIC',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.6,
                  color: Color(0xFFB45309),
                ),
              ),
              const SizedBox(height: 2),
              Text(
                'Ubah Status ${room.roomName}',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                DropdownButtonFormField<bool>(
                  initialValue: isActive,
                  decoration: const InputDecoration(
                    labelText: 'Kondisi Ruangan',
                  ),
                  items: const [
                    DropdownMenuItem(
                      value: true,
                      child: Text('Aktif (Siap Digunakan)'),
                    ),
                    DropdownMenuItem(
                      value: false,
                      child: Text('Tidak Aktif (Dalam Perbaikan)'),
                    ),
                  ],
                  onChanged: (val) {
                    if (val == null) return;
                    setDialogState(() {
                      isActive = val;
                      errorText = null;
                    });
                  },
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: noteController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    labelText: isActive
                        ? 'Catatan Ruangan (Opsional)'
                        : 'Catatan Perbaikan / Alasan *',
                    hintText:
                        'Contoh: Sedang renovasi plafon dan pengecatan sampai tanggal 20.',
                    errorText: errorText,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Catatan ini akan muncul beserta link WhatsApp Anda saat ruangan dipilih pada form peminjaman.',
                  style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                ),
                if (conflictWaUrl != null) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFFECACA)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          errorText ?? 'Ruangan sedang digunakan untuk agenda.',
                          style: const TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF991B1B),
                          ),
                        ),
                        const SizedBox(height: 10),
                        ElevatedButton.icon(
                          onPressed: () async {
                            final opened = UrlOpener.open(conflictWaUrl!);
                            if (!opened) {
                              await Clipboard.setData(
                                ClipboardData(text: conflictWaUrl!),
                              );
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text(
                                      'Link WhatsApp telah disalin ke clipboard.',
                                    ),
                                  ),
                                );
                              }
                            }
                          },
                          icon: const Icon(Icons.chat, size: 16),
                          label: Text(
                            conflictBorrowerName != null
                                ? 'Hubungi $conflictBorrowerName via WhatsApp'
                                : 'Hubungi Peminjam via WhatsApp',
                          ),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF16A34A),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            textStyle: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: isSaving ? null : () => Navigator.pop(ctx),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: isSaving
                  ? null
                  : () async {
                      final note = noteController.text.trim();
                      if (!isActive && note.isEmpty) {
                        setDialogState(() {
                          errorText =
                              'Mohon isi catatan perbaikan saat menonaktifkan ruangan.';
                        });
                        return;
                      }

                      setDialogState(() {
                        isSaving = true;
                        errorText = null;
                        conflictWaUrl = null;
                        conflictBorrowerName = null;
                      });

                      try {
                        await widget.api.patch(
                          '/rooms/${room.id}/status',
                          {
                            'is_active': isActive,
                            'maintenance_note': isActive ? null : note,
                          },
                        );
                        if (!ctx.mounted) return;
                        Navigator.pop(ctx);
                        _refresh();
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(
                                'Status ${room.roomName} berhasil diperbarui.',
                              ),
                            ),
                          );
                        }
                      } on ApiException catch (e) {
                        String? waUrl;
                        String? borrower;
                        if (e.data is Map) {
                          waUrl = e.data['whatsapp_url']?.toString();
                          if (e.data['ongoing_conflict'] is Map) {
                            final conf = e.data['ongoing_conflict'] as Map;
                            waUrl ??= conf['whatsapp_url']?.toString();
                            borrower = conf['borrower_name']?.toString();
                          }
                        }
                        setDialogState(() {
                          isSaving = false;
                          errorText = waUrl != null ? null : e.message;
                          conflictWaUrl = waUrl;
                          conflictBorrowerName = borrower;
                        });
                      } catch (e) {
                        setDialogState(() {
                          isSaving = false;
                          errorText = 'Gagal menyimpan: $e';
                        });
                      }
                    },
              child: Text(isSaving ? 'Menyimpan...' : 'Simpan Status'),
            ),
          ],
        ),
      ),
    );
  }

  void _showRoomDetailSheet(RoomModel room) {
    final isMyManagedRoom = widget.user.isPic && room.picId == widget.user.id;
    final waUrl = room.picWhatsappUrl;

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: room.isActive
                            ? AppTheme.primaryColor.withValues(alpha: 0.12)
                            : const Color(0xFFF59E0B).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Icon(
                        room.isActive
                            ? Icons.meeting_room
                            : Icons.construction_rounded,
                        color: room.isActive
                            ? AppTheme.primaryColor
                            : const Color(0xFFB45309),
                        size: 28,
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            room.roomName,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF0F172A),
                            ),
                          ),
                          const SizedBox(height: 2),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: room.isActive
                                  ? const Color(0xFF10B981)
                                      .withValues(alpha: 0.12)
                                  : const Color(0xFFFEF3C7),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              room.isActive
                                  ? 'Status: Aktif (Siap Digunakan)'
                                  : 'Status: Tidak Aktif (Dalam Perbaikan)',
                              style: TextStyle(
                                fontSize: 11,
                                color: room.isActive
                                    ? const Color(0xFF10B981)
                                    : const Color(0xFFB45309),
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                const Divider(),
                const SizedBox(height: 12),
                _buildDetailRow(
                  icon: Icons.people_outline,
                  title: 'Kapasitas Ruangan',
                  value: '${room.capacity} Orang',
                ),
                const SizedBox(height: 12),
                _buildDetailRow(
                  icon: Icons.person_pin_outlined,
                  title: 'Penanggung Jawab (PIC)',
                  value: room.picName ?? 'Belum Ditentukan',
                ),
                if (room.picPhone != null && room.picPhone!.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  _buildDetailRow(
                    icon: Icons.phone_outlined,
                    title: 'Nomor WhatsApp PIC',
                    value: room.picPhone!,
                  ),
                ],
                const SizedBox(height: 12),
                _buildDetailRow(
                  icon: Icons.verified_user_outlined,
                  title: 'Status Anda',
                  value: isMyManagedRoom
                      ? 'Anda adalah PIC ruangan ini'
                      : 'Pengguna Terdaftar',
                ),
                if (!room.isActive) ...[
                  const SizedBox(height: 16),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFFBEB),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFFDE68A)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Ruangan sedang dalam perbaikan, mohon lihat catatan ini dan hubungi nomor di bawah ini:',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF92400E),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          (room.maintenanceNote != null &&
                                  room.maintenanceNote!.trim().isNotEmpty)
                              ? 'Catatan PIC: ${room.maintenanceNote}'
                              : 'Catatan PIC: Ruangan sedang dalam perbaikan / renovasi.',
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF1E293B),
                          ),
                        ),
                        if (waUrl != null) ...[
                          const SizedBox(height: 10),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF059669),
                                foregroundColor: Colors.white,
                              ),
                              onPressed: () async {
                                final opened = openExternalUrl(waUrl);
                                if (!opened) {
                                  await Clipboard.setData(
                                    ClipboardData(text: waUrl),
                                  );
                                  if (ctx.mounted) {
                                    ScaffoldMessenger.of(ctx).showSnackBar(
                                      const SnackBar(
                                        content: Text(
                                          'Link WhatsApp PIC telah disalin.',
                                        ),
                                      ),
                                    );
                                  }
                                }
                              },
                              icon: const Icon(Icons.chat_outlined, size: 18),
                              label: const Text('Hubungi PIC via WhatsApp'),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
                const SizedBox(height: 20),
                if (isMyManagedRoom) ...[
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pop(ctx);
                        _showUpdateRoomStatusDialog(room);
                      },
                      icon: const Icon(Icons.settings_outlined, size: 18),
                      label: const Text('Ubah Status Kondisi Ruangan'),
                    ),
                  ),
                  const SizedBox(height: 10),
                ],
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: () => Navigator.pop(ctx),
                    child: const Text('Tutup Detail'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow({
    required IconData icon,
    required String title,
    required String value,
  }) {
    return Row(
      children: [
        Icon(icon, size: 20, color: const Color(0xFF64748B)),
        const SizedBox(width: 12),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
            ),
            Text(
              value,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Color(0xFF1E293B),
              ),
            ),
          ],
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Cari nama ruangan (Lab, Kelas, dll)...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
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
                  _searchQuery = val.trim().toLowerCase();
                  _currentPage = 0;
                });
              },
            ),
          ),

          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
            child: Row(
              children: [
                'Semua Ruangan',
                if (widget.user.isPic) 'Ruangan Saya',
                'Laboratorium (Lab)',
                'Ruang Kelas',
                'Belum Ada PIC',
              ].map((filter) {
                final selected = _roomFilter == filter;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(filter),
                    selected: selected,
                    onSelected: (_) {
                      setState(() {
                        _roomFilter = filter;
                        _currentPage = 0;
                      });
                    },
                    selectedColor: AppTheme.primaryColor.withValues(alpha: 0.15),
                    checkmarkColor: AppTheme.primaryColor,
                  ),
                );
              }).toList(),
            ),
          ),

          // Room List
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => _refresh(),
              child: FutureBuilder<List<RoomModel>>(
                future: _roomsFuture,
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
                              Icons.error_outline,
                              size: 40,
                              color: Color(0xFFEF4444),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              'Gagal memuat daftar ruangan: ${snapshot.error}',
                            ),
                            const SizedBox(height: 12),
                            OutlinedButton(
                              onPressed: _refresh,
                              child: const Text('Coba Lagi'),
                            ),
                          ],
                        ),
                      ),
                    );
                  }

                  // 1. Urutkan daftar ruangan sesuai standar sekolah:
                  // Ruang Wijaya -> DAK -> Lab 1-21 -> Ruang 1-seterusnya -> Lainnya
                  final rooms = List<RoomModel>.from(snapshot.data!)
                    ..sort((a, b) => _compareRoomNames(a.roomName, b.roomName));

                  final filtered = rooms.where((r) {
                    if (_searchQuery.isNotEmpty &&
                        !r.roomName.toLowerCase().contains(_searchQuery) &&
                        !(r.picName ?? '').toLowerCase().contains(_searchQuery)) {
                      return false;
                    }
                    if (_roomFilter == 'Ruangan Saya' &&
                        (!widget.user.isPic || r.picId != widget.user.id)) {
                      return false;
                    }
                    if (_roomFilter == 'Laboratorium (Lab)' &&
                        !r.roomName.toLowerCase().contains('lab')) {
                      return false;
                    }
                    if (_roomFilter == 'Ruang Kelas' &&
                        !(r.roomName.toLowerCase().contains('kelas') ||
                            RegExp(r'ruang\s*\d+', caseSensitive: false)
                                .hasMatch(r.roomName))) {
                      return false;
                    }
                    if (_roomFilter == 'Belum Ada PIC' && r.picId != null) {
                      return false;
                    }
                    return true;
                  }).toList();

                  if (filtered.isEmpty) {
                    return Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              Icons.meeting_room,
                              size: 44,
                              color: Colors.grey.shade400,
                            ),
                            const SizedBox(height: 8),
                            Text(
                              _searchQuery.isEmpty &&
                                      _roomFilter == 'Semua Ruangan'
                                  ? 'Belum ada ruangan yang terdaftar.'
                                  : 'Tidak ditemukan ruangan yang sesuai pencarian dan filter.',
                              textAlign: TextAlign.center,
                              style: const TextStyle(color: Color(0xFF64748B)),
                            ),
                          ],
                        ),
                      ),
                    );
                  }

                  final totalPages = (filtered.length / _pageSize).ceil();
                  final safePage = _currentPage >= totalPages
                      ? (totalPages - 1)
                      : _currentPage;
                  final pagedRooms = filtered
                      .skip(safePage * _pageSize)
                      .take(_pageSize)
                      .toList();

                  return Column(
                    children: [
                      Expanded(
                        child: ListView.builder(
                          controller: _scrollController,
                          physics: const AlwaysScrollableScrollPhysics(
                            parent: ClampingScrollPhysics(),
                          ),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 8,
                          ),
                          itemCount: pagedRooms.length,
                          itemBuilder: (context, index) {
                            final room = pagedRooms[index];
                      final isMyManagedRoom =
                          widget.user.isPic && room.picId == widget.user.id;

                      return Card(
                        child: InkWell(
                          onTap: () => _showRoomDetailSheet(room),
                          borderRadius: BorderRadius.circular(16),
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Row(
                              children: [
                                Container(
                                  width: 46,
                                  height: 46,
                                  decoration: BoxDecoration(
                                    color: isMyManagedRoom
                                        ? const Color(
                                            0xFFD97706,
                                          ).withValues(alpha: 0.15)
                                        : AppTheme.primaryColor.withValues(
                                            alpha: 0.1,
                                          ),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Icon(
                                    Icons.meeting_room_outlined,
                                    color: isMyManagedRoom
                                        ? const Color(0xFFD97706)
                                        : AppTheme.primaryColor,
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Expanded(
                                            child: Text(
                                              room.roomName,
                                              style: const TextStyle(
                                                fontSize: 15,
                                                fontWeight: FontWeight.w700,
                                                color: Color(0xFF0F172A),
                                              ),
                                            ),
                                          ),
                                          if (!room.isActive)
                                            Container(
                                              margin: const EdgeInsets.only(
                                                right: 6,
                                              ),
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                    horizontal: 6,
                                                    vertical: 2,
                                                  ),
                                              decoration: BoxDecoration(
                                                color: const Color(0xFFFEF3C7),
                                                borderRadius:
                                                    BorderRadius.circular(6),
                                                border: Border.all(
                                                  color: const Color(
                                                    0xFFFDE68A,
                                                  ),
                                                ),
                                              ),
                                              child: const Text(
                                                'PERBAIKAN',
                                                style: TextStyle(
                                                  color: Color(0xFF92400E),
                                                  fontSize: 9,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                          if (isMyManagedRoom)
                                            Container(
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                    horizontal: 6,
                                                    vertical: 2,
                                                  ),
                                              decoration: BoxDecoration(
                                                color: const Color(0xFFD97706),
                                                borderRadius:
                                                    BorderRadius.circular(6),
                                              ),
                                              child: const Text(
                                                'PIC ANDA',
                                                style: TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 9,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                        ],
                                      ),
                                      const SizedBox(height: 4),
                                      Row(
                                        children: [
                                          const Icon(
                                            Icons.groups_outlined,
                                            size: 14,
                                            color: Color(0xFF64748B),
                                          ),
                                          const SizedBox(width: 4),
                                          Text(
                                            'Kapasitas ${room.capacity} orang',
                                            style: const TextStyle(
                                              fontSize: 12,
                                              color: Color(0xFF64748B),
                                            ),
                                          ),
                                          const SizedBox(width: 10),
                                          const Icon(
                                            Icons.person_pin_circle_outlined,
                                            size: 14,
                                            color: Color(0xFF64748B),
                                          ),
                                          const SizedBox(width: 4),
                                          Expanded(
                                            child: Text(
                                              room.picName ?? 'PIC -',
                                              style: const TextStyle(
                                                fontSize: 12,
                                                color: Color(0xFF64748B),
                                              ),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                const Icon(
                                  Icons.chevron_right,
                                  color: Color(0xFF64748B),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                          },
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
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Kontrol Pagination Ruangan (Bersih, Responsif & Cepat)
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
              'Menampilkan $startItem-$endItem dari $totalItems ruangan',
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
