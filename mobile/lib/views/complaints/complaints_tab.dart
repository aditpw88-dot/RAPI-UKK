import 'package:flutter/material.dart';
import 'package:printing/printing.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../models/complaint_model.dart';
import '../../models/user_model.dart';
import '../../theme/app_theme.dart';

class ComplaintsTab extends StatefulWidget {
  const ComplaintsTab({
    required this.api,
    required this.user,
    required this.onOpenCreateComplaint,
    super.key,
  });

  final ApiClient api;
  final UserModel user;
  final VoidCallback onOpenCreateComplaint;

  @override
  State<ComplaintsTab> createState() => ComplaintsTabState();
}

class ComplaintsTabState extends State<ComplaintsTab> {
  late Future<List<ComplaintModel>> _complaintsFuture;
  String _selectedStatusFilter = 'Semua';
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();
  bool _isPrinting = false;

  @override
  void initState() {
    super.initState();
    _complaintsFuture = _loadComplaints();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void refresh() {
    setState(() {
      _complaintsFuture = _loadComplaints();
    });
  }

  Future<List<ComplaintModel>> _loadComplaints() async {
    final response =
        await widget.api.get('/complaints') as Map<String, dynamic>;
    final rawList = response['data'] as List<dynamic>? ?? [];
    return rawList
        .map(
          (e) => ComplaintModel.fromJson(Map<String, dynamic>.from(e as Map)),
        )
        .toList();
  }

  Future<void> _updateStatus(ComplaintModel complaint) async {
    final newStatus = await showModalBottomSheet<String>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 24),
                child: Text(
                  'Perbarui Status Perbaikan',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF0F172A),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              ...AppConstants.repairStatuses.map((status) {
                final isSelected = complaint.statusRepair == status;
                return ListTile(
                  leading: Icon(
                    isSelected
                        ? Icons.radio_button_checked
                        : Icons.radio_button_unchecked,
                    color: AppConstants.statusColor(status),
                  ),
                  title: Text(
                    status,
                    style: TextStyle(
                      fontWeight: isSelected
                          ? FontWeight.w700
                          : FontWeight.w500,
                    ),
                  ),
                  onTap: () => Navigator.pop(ctx, status),
                );
              }),
            ],
          ),
        ),
      ),
    );

    if (newStatus == null || newStatus == complaint.statusRepair) return;

    try {
      await widget.api.patch('/complaints/${complaint.id}/status', {
        'status_repair': newStatus,
      });
      refresh();
      _showToast('Status perbaikan berhasil diperbarui ke: $newStatus');
    } on ApiException catch (e) {
      _showErrorToast(e.message);
    }
  }

  Future<void> _printReport() async {
    final now = DateTime.now();
    final period = '${now.year}-${now.month.toString().padLeft(2, '0')}';
    setState(() => _isPrinting = true);

    try {
      final bytes = await widget.api.getBytes(
        '/pic/reports/complaints/pdf?period_type=monthly&period_value=$period',
      );
      await Printing.layoutPdf(onLayout: (_) async => bytes);
    } on ApiException catch (error) {
      _showErrorToast(error.message);
    } catch (error) {
      _showErrorToast('Gagal mencetak laporan kerusakan: $error');
    } finally {
      if (mounted) setState(() => _isPrinting = false);
    }
  }

  void _showImageDialog(String photoUrl) {
    final resolvedUrl = widget.api.resolveMediaUrl(photoUrl);

    showDialog<void>(
      context: context,
      builder: (ctx) => Dialog(
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Stack(
              children: [
                InteractiveViewer(
                  child: Image.network(
                    resolvedUrl,
                    fit: BoxFit.contain,
                    errorBuilder: (context, error, stackTrace) => const Padding(
                      padding: EdgeInsets.all(40),
                      child: Text('Foto tidak dapat dimuat.'),
                    ),
                  ),
                ),
                Positioned(
                  top: 8,
                  right: 8,
                  child: CircleAvatar(
                    backgroundColor: Colors.black54,
                    child: IconButton(
                      icon: const Icon(Icons.close, color: Colors.white),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _showToast(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: const Color(0xFF10B981)),
    );
  }

  void _showErrorToast(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: const Color(0xFFEF4444)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filterOptions = ['Semua', ...AppConstants.repairStatuses];

    return Scaffold(
      floatingActionButton: widget.user.isGuru
          ? FloatingActionButton.extended(
              onPressed: widget.onOpenCreateComplaint,
              icon: const Icon(Icons.add_a_photo_outlined),
              label: const Text('Buat Pengaduan'),
            )
          : null,
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 2),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Cari ruang, pelapor, atau kerusakan...',
                      prefixIcon: const Icon(Icons.search, size: 20),
                      isDense: true,
                      suffixIcon: _searchQuery.isEmpty
                          ? null
                          : IconButton(
                              icon: const Icon(Icons.clear, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                setState(() => _searchQuery = '');
                              },
                            ),
                    ),
                    onChanged: (value) => setState(
                      () => _searchQuery = value.trim().toLowerCase(),
                    ),
                  ),
                ),
                if (widget.user.isPic) ...[
                  const SizedBox(width: 8),
                  IconButton.filledTonal(
                    tooltip: 'Cetak laporan kerusakan bulan ini',
                    onPressed: _isPrinting ? null : _printReport,
                    icon: _isPrinting
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.print_outlined),
                  ),
                ],
              ],
            ),
          ),

          // Filter Chips Horizontal Scroll
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            child: Row(
              children: filterOptions.map((status) {
                final isSelected = _selectedStatusFilter == status;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(status),
                    selected: isSelected,
                    onSelected: (_) =>
                        setState(() => _selectedStatusFilter = status),
                    selectedColor: AppTheme.primaryColor.withValues(
                      alpha: 0.15,
                    ),
                    checkmarkColor: AppTheme.primaryColor,
                  ),
                );
              }).toList(),
            ),
          ),

          // Complaints List
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => refresh(),
              child: FutureBuilder<List<ComplaintModel>>(
                future: _complaintsFuture,
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
                              'Gagal memuat pengaduan: ${snapshot.error}',
                              textAlign: TextAlign.center,
                            ),
                            const SizedBox(height: 12),
                            OutlinedButton(
                              onPressed: refresh,
                              child: const Text('Coba Lagi'),
                            ),
                          ],
                        ),
                      ),
                    );
                  }

                  final complaints = snapshot.data!;
                  final filtered = complaints.where((c) {
                    if (_selectedStatusFilter != 'Semua' &&
                        c.statusRepair != _selectedStatusFilter) {
                      return false;
                    }
                    if (_searchQuery.isEmpty) return true;
                    return c.description.toLowerCase().contains(_searchQuery) ||
                        (c.roomName ?? '').toLowerCase().contains(_searchQuery) ||
                        (c.reporterName ?? '').toLowerCase().contains(_searchQuery) ||
                        (c.handlerName ?? '').toLowerCase().contains(_searchQuery);
                  }).toList();

                  if (filtered.isEmpty) {
                    return ListView(
                      children: [
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.4,
                          child: Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.build_circle_outlined,
                                  size: 48,
                                  color: Colors.grey.shade400,
                                ),
                                const SizedBox(height: 10),
                                Text(
                                  _selectedStatusFilter == 'Semua' &&
                                          _searchQuery.isEmpty
                                      ? 'Belum ada data pengaduan fasilitas.'
                                      : 'Tidak ada pengaduan yang sesuai pencarian dan filter.',
                                  style: const TextStyle(
                                    color: Color(0xFF64748B),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView.builder(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 8,
                    ),
                    itemCount: filtered.length,
                    itemBuilder: (context, index) {
                      final complaint = filtered[index];
                      final resolvedPhotoUrl = widget.api.resolveMediaUrl(
                        complaint.photoUrl,
                      );

                      return Card(
                        child: Padding(
                          padding: const EdgeInsets.all(14),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // Photo thumbnail
                                  if (resolvedPhotoUrl.isNotEmpty)
                                    GestureDetector(
                                      onTap: () =>
                                          _showImageDialog(complaint.photoUrl),
                                      child: ClipRRect(
                                        borderRadius: BorderRadius.circular(10),
                                        child: Image.network(
                                          resolvedPhotoUrl,
                                          width: 72,
                                          height: 72,
                                          fit: BoxFit.cover,
                                          errorBuilder:
                                              (context, error, stackTrace) =>
                                                  Container(
                                                    width: 72,
                                                    height: 72,
                                                    color: Colors.grey.shade200,
                                                    child: const Icon(
                                                      Icons.broken_image,
                                                      color: Colors.grey,
                                                    ),
                                                  ),
                                        ),
                                      ),
                                    ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          mainAxisAlignment:
                                              MainAxisAlignment.spaceBetween,
                                          children: [
                                            Expanded(
                                              child: Text(
                                                complaint.roomName ?? 'Ruangan',
                                                style: const TextStyle(
                                                  fontSize: 15,
                                                  fontWeight: FontWeight.w700,
                                                  color: Color(0xFF0F172A),
                                                ),
                                              ),
                                            ),
                                            Container(
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                    horizontal: 8,
                                                    vertical: 3,
                                                  ),
                                              decoration: BoxDecoration(
                                                color: AppConstants.statusColor(
                                                  complaint.statusRepair,
                                                ).withValues(alpha: 0.12),
                                                borderRadius:
                                                    BorderRadius.circular(8),
                                              ),
                                              child: Text(
                                                complaint.statusRepair,
                                                style: TextStyle(
                                                  fontSize: 10,
                                                  fontWeight: FontWeight.bold,
                                                  color:
                                                      AppConstants.statusColor(
                                                        complaint.statusRepair,
                                                      ),
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          complaint.description,
                                          style: const TextStyle(
                                            fontSize: 13,
                                            color: Color(0xFF334155),
                                            height: 1.3,
                                          ),
                                          maxLines: 3,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              const Divider(height: 1),
                              const SizedBox(height: 8),
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'Pelapor: ${complaint.reporterName ?? '-'}',
                                        style: const TextStyle(
                                          fontSize: 11,
                                          color: Color(0xFF64748B),
                                        ),
                                      ),
                                      if (complaint.handlerName != null)
                                        Text(
                                          'Penangan: ${complaint.handlerName}',
                                          style: const TextStyle(
                                            fontSize: 11,
                                            color: AppTheme.primaryColor,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                    ],
                                  ),
                                  if (widget.user.isPic)
                                    OutlinedButton.icon(
                                      onPressed: () => _updateStatus(complaint),
                                      icon: const Icon(
                                        Icons.edit_outlined,
                                        size: 14,
                                      ),
                                      label: const Text('Ubah Status'),
                                      style: OutlinedButton.styleFrom(
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 10,
                                          vertical: 6,
                                        ),
                                        textStyle: const TextStyle(
                                          fontSize: 12,
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
