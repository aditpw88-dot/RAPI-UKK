class RoomModel {
  const RoomModel({
    required this.id,
    required this.roomName,
    required this.capacity,
    this.picId,
    this.picName,
    this.isActive = true,
  });

  final int id;
  final String roomName;
  final int capacity;
  final int? picId;
  final String? picName;
  final bool isActive;

  factory RoomModel.fromJson(Map<String, dynamic> json) {
    final pic = json['pic'] is Map<String, dynamic>
        ? json['pic'] as Map<String, dynamic>
        : (json['pic'] is Map ? Map<String, dynamic>.from(json['pic'] as Map) : null);

    final rawActive = json['is_active'];
    final bool active = rawActive == true ||
        rawActive == 1 ||
        rawActive == '1' ||
        '$rawActive'.toLowerCase() == 'aktif';

    return RoomModel(
      id: json['id'] is int
          ? json['id'] as int
          : int.tryParse('${json['id']}') ?? 0,
      roomName: '${json['room_name'] ?? 'Ruangan'}',
      capacity: json['capacity'] is int
          ? json['capacity'] as int
          : int.tryParse('${json['capacity']}') ?? 0,
      picId: json['pic_id'] != null ? int.tryParse('${json['pic_id']}') : null,
      picName: pic?['fullname'] != null ? '${pic!['fullname']}' : null,
      isActive: active,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'room_name': roomName,
      'capacity': capacity,
      'pic_id': picId,
      'is_active': isActive,
    };
  }
}
