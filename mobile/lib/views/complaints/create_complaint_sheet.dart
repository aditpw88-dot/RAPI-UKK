import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../core/api_client.dart';
import '../../models/room_model.dart';
import '../../widgets/room_autocomplete_field.dart';

class CreateComplaintSheet extends StatefulWidget {
  const CreateComplaintSheet({
    required this.api,
    required this.rooms,
    required this.onComplaintCreated,
    super.key,
  });

  final ApiClient api;
  final List<RoomModel> rooms;
  final VoidCallback onComplaintCreated;

  @override
  State<CreateComplaintSheet> createState() => _CreateComplaintSheetState();
}

class _CreateComplaintSheetState extends State<CreateComplaintSheet> {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();

  int? _selectedRoomId;
  XFile? _selectedPhoto;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    if (widget.rooms.isNotEmpty) {
      _selectedRoomId = widget.rooms.first.id;
    }
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picker = ImagePicker();
      final photo = await picker.pickImage(
        source: source,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 82,
      );

      if (photo != null) {
        setState(() => _selectedPhoto = photo);
      }
    } catch (e) {
      _showToast('Gagal mengambil foto: $e');
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedRoomId == null) {
      _showToast('Pilih ruangan sarpras yang rusak.');
      return;
    }
    if (_selectedPhoto == null) {
      _showToast('Lampirkan foto bukti kerusakan sarana.');
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      await widget.api.uploadComplaint(
        roomId: _selectedRoomId!,
        description: _descriptionController.text.trim(),
        photo: _selectedPhoto!,
      );

      if (!mounted) return;
      Navigator.pop(context);
      widget.onComplaintCreated();
    } on ApiException catch (e) {
      _showToast(e.message);
    } catch (e) {
      _showToast('Gagal mengirim pengaduan: $e');
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
                  'Buat Pengaduan Fasilitas',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Laporkan kerusakan sarana/prasarana di ruangan agar ditindaklanjuti oleh PIC.',
                  style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                ),
                const SizedBox(height: 20),

                RoomAutocompleteField(
                  rooms: widget.rooms,
                  selectedRoomId: _selectedRoomId,
                  onChanged: (value) =>
                      setState(() => _selectedRoomId = value),
                  labelText: 'Pilih Ruangan',
                  optionDetails: (room) => 'PIC: ${room.picName ?? '-'}',
                ),

                const SizedBox(height: 14),

                // Description
                TextFormField(
                  controller: _descriptionController,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'Keterangan Kerusakan',
                    hintText: 'Contoh: AC tidak dingin dan kabel proyektor putus...',
                    alignLabelWithHint: true,
                  ),
                  validator: (val) =>
                      val == null || val.trim().isEmpty ? 'Keterangan kerusakan wajib diisi.' : null,
                ),

                const SizedBox(height: 16),

                // Photo preview or picker buttons
                if (_selectedPhoto != null) ...[
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Stack(
                      children: [
                        Image.file(
                          File(_selectedPhoto!.path),
                          height: 170,
                          width: double.infinity,
                          fit: BoxFit.cover,
                        ),
                        Positioned(
                          top: 8,
                          right: 8,
                          child: CircleAvatar(
                            backgroundColor: Colors.black54,
                            child: IconButton(
                              icon: const Icon(Icons.close, color: Colors.white, size: 18),
                              onPressed: () => setState(() => _selectedPhoto = null),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                ] else ...[
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _pickImage(ImageSource.camera),
                          icon: const Icon(Icons.camera_alt_outlined),
                          label: const Text('Ambil Kamera'),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _pickImage(ImageSource.gallery),
                          icon: const Icon(Icons.photo_library_outlined),
                          label: const Text('Galeri Foto'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    '* Wajib melampirkan 1 foto bukti kerusakan.',
                    style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                  ),
                ],

                const SizedBox(height: 22),

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
                      : const Text('Kirim Pengaduan'),
                ),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: _isSubmitting ? null : () => Navigator.pop(context),
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
