class EventModel {
  const EventModel({
    required this.id,
    required this.eventName,
    required this.date,
    String? endDate,
    required this.startTime,
    required this.endTime,
    required this.status,
    this.roomId,
    this.roomName,
    this.picId,
    this.picName,
    this.rejectionReason,
  }) : endDate = endDate ?? date;

  final int id;
  final String eventName;
  final String date;
  final String endDate;
  final String startTime;
  final String endTime;
  final String status;
  final int? roomId;
  final String? roomName;
  final int? picId;
  final String? picName;
  final String? rejectionReason;

  bool get isApproved => status.toLowerCase() == 'approved';
  bool get isPending => status.toLowerCase() == 'pending';
  bool get isRejected => status.toLowerCase() == 'rejected';

  bool occursOn(DateTime day) {
    final startDate = DateTime.tryParse(date);
    final finishDate = DateTime.tryParse(endDate) ?? startDate;
    if (startDate == null || finishDate == null) return false;

    final selectedDay = DateTime(day.year, day.month, day.day);
    return !selectedDay.isBefore(
          DateTime(startDate.year, startDate.month, startDate.day),
        ) &&
        !selectedDay.isAfter(
          DateTime(finishDate.year, finishDate.month, finishDate.day),
        );
  }

  String get statusDisplay {
    if (isApproved) return 'Disetujui';
    if (isPending) return 'Menunggu Persetujuan';
    if (isRejected) return 'Tidak Disetujui';
    return status;
  }

  factory EventModel.fromJson(Map<String, dynamic> json) {
    final room = json['room'] is Map<String, dynamic>
        ? json['room'] as Map<String, dynamic>
        : (json['room'] is Map
              ? Map<String, dynamic>.from(json['room'] as Map)
              : null);

    final pic = json['pic'] is Map<String, dynamic>
        ? json['pic'] as Map<String, dynamic>
        : (json['pic'] is Map
              ? Map<String, dynamic>.from(json['pic'] as Map)
              : null);

    return EventModel(
      id: json['id'] is int
          ? json['id'] as int
          : int.tryParse('${json['id']}') ?? 0,
      eventName: '${json['event_name'] ?? 'Agenda'}',
      date: '${json['date'] ?? ''}',
      endDate: '${json['end_date'] ?? json['date'] ?? ''}',
      startTime: '${json['start_time'] ?? ''}',
      endTime: '${json['end_time'] ?? ''}',
      status: '${json['status'] ?? 'pending'}',
      roomId: json['room_id'] != null
          ? int.tryParse('${json['room_id']}')
          : null,
      roomName: room?['room_name'] != null ? '${room!['room_name']}' : null,
      picId: json['pic_id'] != null ? int.tryParse('${json['pic_id']}') : null,
      picName: pic?['fullname'] != null ? '${pic!['fullname']}' : null,
      rejectionReason: json['rejection_reason'] != null
          ? '${json['rejection_reason']}'
          : null,
    );
  }
}
