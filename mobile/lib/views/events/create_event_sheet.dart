import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../core/date_utils.dart';
import '../../models/room_model.dart';
import '../../theme/app_theme.dart';
import '../../widgets/room_autocomplete_field.dart';

class CreateEventSheet extends StatefulWidget {
  const CreateEventSheet({
    required this.api,
    required this.rooms,
    required this.onEventCreated,
    super.key,
  });

  final ApiClient api;
  final List<RoomModel> rooms;
  final VoidCallback onEventCreated;

  @override
  State<CreateEventSheet> createState() => _CreateEventSheetState();
}

class _CreateEventSheetState extends State<CreateEventSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();

  int? _selectedRoomId;
  DateTime? _selectedDate;
  DateTime? _selectedEndDate;
  int _startSession = 1;
  int _endSession = 2;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    final activeRooms = widget.rooms.where((r) => r.isActive);
    if (activeRooms.isNotEmpty) {
      _selectedRoomId = activeRooms.first.id;
    }
    // Default date is tomorrow (backend requires date after today)
    _selectedDate = DateTime.now().add(const Duration(days: 1));
    _selectedEndDate = _selectedDate;
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final tomorrow = DateTime.now().add(const Duration(days: 1));
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate ?? tomorrow,
      firstDate: tomorrow,
      lastDate: tomorrow.add(const Duration(days: 365)),
      helpText: 'Pilih Tanggal Penggunaan Ruangan',
      confirmText: 'Pilih',
      cancelText: 'Batal',
    );

    if (picked != null) {
      setState(() {
        _selectedDate = picked;
        if (_selectedEndDate == null || _selectedEndDate!.isBefore(picked)) {
          _selectedEndDate = picked;
        }
      });
    }
  }

  Future<void> _pickEndDate() async {
    final startDate = _selectedDate;
    if (startDate == null) {
      _showToast('Pilih tanggal mulai terlebih dahulu.');
      return;
    }

    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedEndDate ?? startDate,
      firstDate: startDate,
      lastDate: startDate.add(const Duration(days: 365)),
      helpText: 'Pilih Tanggal Selesai Penggunaan Ruangan',
      confirmText: 'Pilih',
      cancelText: 'Batal',
    );

    if (picked != null) {
      setState(() => _selectedEndDate = picked);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedRoomId == null) {
      _showToast('Pilih ruangan terlebih dahulu.');
      return;
    }
    if (_selectedDate == null) {
      _showToast('Pilih tanggal mulai agenda.');
      return;
    }
    if (_selectedEndDate == null ||
        _selectedEndDate!.isBefore(_selectedDate!)) {
      _showToast('Tanggal selesai tidak boleh sebelum tanggal mulai.');
      return;
    }
    if (_endSession < _startSession) {
      _showToast('Sesi selesai harus lebih besar atau sama dengan sesi mulai.');
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final dateStr = IndoDateUtils.formatIsoDate(_selectedDate!);
      final endDateStr = IndoDateUtils.formatIsoDate(_selectedEndDate!);
      await widget.api.post('/events', {
        'event_name': _nameController.text.trim(),
        'room_id': _selectedRoomId,
        'date': dateStr,
        'end_date': endDateStr,
        'start_session': _startSession,
        'end_session': _endSession,
      });

      if (!mounted) return;
      Navigator.pop(context);
      widget.onEventCreated();
    } on ApiException catch (e) {
      _showToast(e.message);
    } catch (e) {
      _showToast('Gagal mengirim pengajuan agenda: $e');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _showToast(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: const Color(0xFFEF4444),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final startTime =
        AppConstants.schoolSessions[_startSession]?['start'] ?? '07:15';
    final endTime = AppConstants.schoolSessions[_endSession]?['end'] ?? '08:45';

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        padding: const EdgeInsets.fromLTRB(24, 20, 24, 28),
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
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
                const SizedBox(height: 16),
                const Text(
                  'Ajukan Peminjaman Ruangan',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Pengajuan akan diteruskan ke PIC Ruangan terkait untuk persetujuan.',
                  style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                ),
                const SizedBox(height: 20),

                // Event Name
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(
                    labelText: 'Nama Kegiatan / Agenda',
                    hintText: 'Contoh: Workshop Coding & Robotik',
                    prefixIcon: Icon(Icons.event_outlined),
                  ),
                  validator: (value) => value == null || value.trim().isEmpty
                      ? 'Nama agenda wajib diisi.'
                      : null,
                ),

                const SizedBox(height: 14),

                RoomAutocompleteField(
                  rooms: widget.rooms,
                  selectedRoomId: _selectedRoomId,
                  onChanged: (value) => setState(() => _selectedRoomId = value),
                  labelText: 'Pilih Ruangan',
                  optionDetails: (room) => 'Kapasitas: ${room.capacity}',
                ),

                const SizedBox(height: 14),

                // Date Range Pickers
                InkWell(
                  onTap: _pickDate,
                  borderRadius: BorderRadius.circular(12),
                  child: InputDecorator(
                    decoration: const InputDecoration(
                      labelText: 'Tanggal Mulai',
                      prefixIcon: Icon(Icons.calendar_today_outlined),
                      suffixIcon: Icon(Icons.arrow_drop_down),
                    ),
                    child: Text(
                      _selectedDate == null
                          ? 'Pilih Tanggal'
                          : IndoDateUtils.formatFull(_selectedDate!),
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ),

                const SizedBox(height: 14),
                InkWell(
                  onTap: _pickEndDate,
                  borderRadius: BorderRadius.circular(12),
                  child: InputDecorator(
                    decoration: const InputDecoration(
                      labelText: 'Tanggal Selesai',
                      prefixIcon: Icon(Icons.event_available_outlined),
                      suffixIcon: Icon(Icons.arrow_drop_down),
                    ),
                    child: Text(
                      _selectedEndDate == null
                          ? 'Pilih Tanggal Selesai'
                          : IndoDateUtils.formatFull(_selectedEndDate!),
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ),
                const SizedBox(height: 6),
                const Text(
                  'Sesi yang dipilih berlaku setiap hari selama rentang tanggal ini.',
                  style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                ),

                const SizedBox(height: 14),

                // Session Pickers
                Row(
                  children: [
                    Expanded(
                      child: DropdownButtonFormField<int>(
                        initialValue: _startSession,
                        decoration: const InputDecoration(
                          labelText: 'Sesi Mulai',
                          prefixIcon: Icon(Icons.schedule),
                        ),
                        items: List.generate(
                          13,
                          (idx) => DropdownMenuItem(
                            value: idx + 1,
                            child: Text(AppConstants.sessionLabel(idx + 1)),
                          ),
                        ),
                        onChanged: (val) {
                          if (val == null) return;
                          setState(() {
                            _startSession = val;
                            if (_endSession < val) _endSession = val;
                          });
                        },
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 14),

                Row(
                  children: [
                    Expanded(
                      child: DropdownButtonFormField<int>(
                        initialValue: _endSession,
                        decoration: const InputDecoration(
                          labelText: 'Sesi Selesai',
                          prefixIcon: Icon(Icons.timelapse),
                        ),
                        items: List.generate(
                          13,
                          (idx) => DropdownMenuItem(
                            value: idx + 1,
                            child: Text(AppConstants.sessionLabel(idx + 1)),
                          ),
                        ),
                        onChanged: (val) {
                          if (val == null) return;
                          setState(() {
                            _endSession = val;
                            if (_startSession > val) _startSession = val;
                          });
                        },
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 14),

                // Summary Time Preview Card
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: AppTheme.primaryColor.withValues(alpha: 0.2),
                    ),
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.info_outline,
                        color: AppTheme.primaryColor,
                        size: 18,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Waktu Penggunaan: $startTime - $endTime WIB '
                          '(Sesi $_startSession s/d $_endSession)',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: AppTheme.primaryColor,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 22),

                // Buttons
                FilledButton(
                  onPressed: _isSubmitting ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text('Kirim Pengajuan Agenda'),
                ),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: _isSubmitting
                      ? null
                      : () => Navigator.pop(context),
                  child: const Text('Batal'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
