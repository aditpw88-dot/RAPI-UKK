class ComplaintModel {
  const ComplaintModel({
    required this.id,
    required this.description,
    required this.photoUrl,
    required this.statusRepair,
    this.roomId,
    this.roomName,
    this.reporterId,
    this.reporterName,
    this.handlerId,
    this.handlerName,
    this.reportedAt,
  });

  final int id;
  final String description;
  final String photoUrl;
  final String statusRepair;
  final int? roomId;
  final String? roomName;
  final int? reporterId;
  final String? reporterName;
  final int? handlerId;
  final String? handlerName;
  final String? reportedAt;

  bool get isRepaired => statusRepair.toLowerCase().contains('sudah');
  bool get isWaitingParts => statusRepair.toLowerCase().contains('spare part');
  bool get isNotRepaired => statusRepair.toLowerCase().contains('belum');

  factory ComplaintModel.fromJson(Map<String, dynamic> json) {
    final room = json['room'] is Map<String, dynamic>
        ? json['room'] as Map<String, dynamic>
        : (json['room'] is Map ? Map<String, dynamic>.from(json['room'] as Map) : null);

    final reporter = json['reporter'] is Map<String, dynamic>
        ? json['reporter'] as Map<String, dynamic>
        : (json['reporter'] is Map ? Map<String, dynamic>.from(json['reporter'] as Map) : null);

    final handler = json['handler'] is Map<String, dynamic>
        ? json['handler'] as Map<String, dynamic>
        : (json['handler'] is Map ? Map<String, dynamic>.from(json['handler'] as Map) : null);

    return ComplaintModel(
      id: json['id'] is int
          ? json['id'] as int
          : int.tryParse('${json['id']}') ?? 0,
      description: '${json['description'] ?? ''}',
      photoUrl: '${json['photo_url'] ?? ''}',
      statusRepair: '${json['status_repair'] ?? 'Belum Diperbaiki'}',
      roomId: json['room_id'] != null
          ? int.tryParse('${json['room_id']}')
          : (room?['id'] != null ? int.tryParse('${room!['id']}') : null),
      roomName: room?['room_name'] != null ? '${room!['room_name']}' : null,
      reporterId: reporter?['id'] != null ? int.tryParse('${reporter!['id']}') : null,
      reporterName: reporter?['fullname'] != null ? '${reporter!['fullname']}' : null,
      handlerId: handler?['id'] != null ? int.tryParse('${handler!['id']}') : null,
      handlerName: handler?['fullname'] != null ? '${handler!['fullname']}' : null,
      reportedAt: json['reported_at'] != null ? '${json['reported_at']}' : null,
    );
  }
}
