class RoomModel {
  const RoomModel({
    required this.id,
    required this.roomName,
    required this.capacity,
    this.picId,
    this.picName,
    this.picPhone,
    this.maintenanceNote,
    this.isActive = true,
  });

  final int id;
  final String roomName;
  final int capacity;
  final int? picId;
  final String? picName;
  final String? picPhone;
  final String? maintenanceNote;
  final bool isActive;

  String? get picWhatsappUrl {
    if (picPhone == null || picPhone!.trim().isEmpty) return null;
    var clean = picPhone!.replaceAll(RegExp(r'[^0-9]'), '');
    if (clean.isEmpty) return null;
    if (clean.startsWith('0')) {
      clean = '62${clean.substring(1)}';
    }
    final msg = Uri.encodeComponent(
      'Halo Bapak/Ibu PIC $roomName, saya ingin menanyakan perihal ruangan $roomName yang sedang dalam perbaikan.',
    );
    return 'https://wa.me/$clean?text=$msg';
  }

  factory RoomModel.fromJson(Map<String, dynamic> json) {
    final pic = json['pic'] is Map<String, dynamic>
        ? json['pic'] as Map<String, dynamic>
        : (json['pic'] is Map ? Map<String, dynamic>.from(json['pic'] as Map) : null);

    final pics = json['pics'] is List ? json['pics'] as List : const [];
    String? resolvedName = pic?['fullname'] != null ? '${pic!['fullname']}' : null;
    String? resolvedPhone =
        pic?['phone_number'] != null ? '${pic!['phone_number']}' : null;

    if (pics.isNotEmpty) {
      final firstPic =
          pics.first is Map ? Map<String, dynamic>.from(pics.first as Map) : null;
      if (firstPic != null) {
        if (resolvedName == null || resolvedName.isEmpty) {
          resolvedName = firstPic['fullname']?.toString();
        }
        if (resolvedPhone == null || resolvedPhone.isEmpty) {
          resolvedPhone = firstPic['phone_number']?.toString();
        }
      }
    }

    final rawActive = json['is_active'];
    final bool active = rawActive == true ||
        rawActive == 1 ||
        rawActive == '1' ||
        '$rawActive'.toLowerCase() == 'aktif';

    final rawNote = json['maintenance_note']?.toString().trim();

    return RoomModel(
      id: json['id'] is int
          ? json['id'] as int
          : int.tryParse('${json['id']}') ?? 0,
      roomName: '${json['room_name'] ?? 'Ruangan'}',
      capacity: json['capacity'] is int
          ? json['capacity'] as int
          : int.tryParse('${json['capacity']}') ?? 0,
      picId: json['pic_id'] != null ? int.tryParse('${json['pic_id']}') : null,
      picName: resolvedName,
      picPhone: resolvedPhone,
      maintenanceNote: (rawNote != null && rawNote.isNotEmpty) ? rawNote : null,
      isActive: active,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'room_name': roomName,
      'capacity': capacity,
      'pic_id': picId,
      'maintenance_note': maintenanceNote,
      'is_active': isActive,
    };
  }
}
