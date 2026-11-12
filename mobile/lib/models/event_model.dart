import 'package:flutter/material.dart';

class EventModel {
  const EventModel({
    required this.id,
    required this.eventName,
    required this.date,
    String? endDate,
    required this.startTime,
    required this.endTime,
    required this.status,
    this.eventType,
    this.description,
    this.roomId,
    this.roomName,
    this.picId,
    this.picName,
    String? userName,
    this.rejectionReason,
    this.lifecycleStatus,
    this.lifecycleStatusLabel,
    this.cancellationStatus,
  })  : endDate = endDate ?? date,
        userName = userName ?? picName;

  final int id;
  final String eventName;
  final String date;
  final String endDate;
  final String startTime;
  final String endTime;
  final String status;
  final String? eventType;
  final String? description;
  final int? roomId;
  final String? roomName;
  final int? picId;
  final String? picName;
  final String? userName;
  final String? rejectionReason;
  final String? lifecycleStatus;
  final String? lifecycleStatusLabel;
  final String? cancellationStatus;

  bool get isApproved => status.toLowerCase() == 'approved';
  bool get isPending => status.toLowerCase() == 'pending';
  bool get isRejected => status.toLowerCase() == 'rejected';
  bool get isCancelled => status.toLowerCase() == 'cancelled';

  /// Mendapatkan kategori agenda sesuai format baku Web RAPI
  String get category {
    final t = (eventType ?? '').toLowerCase();
    if (t.contains('rapat') || t.contains('koordinasi')) return 'Rapat / Koordinasi';
    if (t.contains('akademik') || t.contains('ujian') || t.contains('ukk')) return 'Akademik / Ujian';
    if (t.contains('upacara')) return 'Upacara';
    if (t.contains('kegiatan') || t.contains('sekolah')) return 'Kegiatan Sekolah';
    if (t.contains('lainnya')) return 'Lainnya';

    // Deteksi cerdas dari nama agenda jika tipe kosong
    final n = eventName.toLowerCase();
    if (n.contains('rapat') || n.contains('koordinasi') || n.contains('briefing') || n.contains('evaluasi')) {
      return 'Rapat / Koordinasi';
    }
    if (n.contains('ujian') || n.contains('ukk') || n.contains('pts') || n.contains('pas') || n.contains('akademik') || n.contains('belajar') || n.contains('praktik')) {
      return 'Akademik / Ujian';
    }
    if (n.contains('upacara') || n.contains('apel') || n.contains('peringatan')) {
      return 'Upacara';
    }
    if (n.contains('kegiatan') || n.contains('lomba') || n.contains('workshop') || n.contains('ekskul') || n.contains('senam')) {
      return 'Kegiatan Sekolah';
    }
    return 'Lainnya';
  }

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
    if (isCancelled) return 'Dibatalkan';
    return status;
  }

  /// Status pelaksanaan (lifecycle) yang dihitung dari jam/tanggal agenda
  String get computedLifecycleStatus {
    if (lifecycleStatus != null && lifecycleStatus!.isNotEmpty) {
      return lifecycleStatus!;
    }
    final s = status.toLowerCase();
    if (s == 'cancelled') return 'cancelled';
    if (cancellationStatus == 'pending') return 'cancellation_pending';
    if (s != 'approved') return s;

    final now = DateTime.now();
    try {
      final sDate = DateTime.tryParse(date);
      if (sDate == null) return 'upcoming';
      final eDate = DateTime.tryParse(endDate) ?? sDate;

      final sParts = startTime.split(':');
      final sHour = sParts.isNotEmpty ? int.tryParse(sParts[0]) ?? 0 : 0;
      final sMin = sParts.length > 1 ? int.tryParse(sParts[1]) ?? 0 : 0;
      final startDt = DateTime(sDate.year, sDate.month, sDate.day, sHour, sMin);

      final eParts = endTime.split(':');
      final eHour = eParts.isNotEmpty ? int.tryParse(eParts[0]) ?? 23 : 23;
      final eMin = eParts.length > 1 ? int.tryParse(eParts[1]) ?? 59 : 59;
      final endDt = DateTime(eDate.year, eDate.month, eDate.day, eHour, eMin);

      if (now.isBefore(startDt)) {
        return 'upcoming';
      } else if (now.isAfter(endDt) || now.isAtSameMomentAs(endDt)) {
        return 'completed';
      } else {
        return 'in_progress';
      }
    } catch (_) {
      return 'upcoming';
    }
  }

  /// Label teks status pelaksanaan untuk tampilan card
  String get lifecycleDisplay {
    if (lifecycleStatusLabel != null && lifecycleStatusLabel!.isNotEmpty) {
      return lifecycleStatusLabel!;
    }
    switch (computedLifecycleStatus) {
      case 'in_progress':
        return 'Sedang Terlaksana';
      case 'completed':
        return 'Sudah Terlaksana';
      case 'upcoming':
        return 'Belum Terlaksana';
      case 'pending':
        return 'Menunggu Persetujuan';
      case 'cancellation_pending':
        return 'Menunggu Pembatalan';
      case 'rejected':
        return 'Tidak Disetujui';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return computedLifecycleStatus;
    }
  }

  Color get lifecycleBgColor {
    switch (computedLifecycleStatus) {
      case 'in_progress':
        return const Color(0xFFECFDF5); // emerald-50
      case 'completed':
        return const Color(0xFFEFF6FF); // blue-50
      case 'upcoming':
      case 'pending':
        return const Color(0xFFFFFBEB); // amber-50
      case 'cancellation_pending':
        return const Color(0xFFFAF5FF); // purple-50
      case 'rejected':
        return const Color(0xFFFEF2F2); // red-50
      case 'cancelled':
        return const Color(0xFFFFF1F2); // rose-50
      default:
        return const Color(0xFFF8FAFC); // slate-50
    }
  }

  Color get lifecycleBorderColor {
    switch (computedLifecycleStatus) {
      case 'in_progress':
        return const Color(0xFFA7F3D0); // emerald-200
      case 'completed':
        return const Color(0xFFBFDBFE); // blue-200
      case 'upcoming':
      case 'pending':
        return const Color(0xFFFDE68A); // amber-200
      case 'cancellation_pending':
        return const Color(0xFFE9D5FF); // purple-200
      case 'rejected':
        return const Color(0xFFFECACA); // red-200
      case 'cancelled':
        return const Color(0xFFFECDD3); // rose-200
      default:
        return const Color(0xFFE2E8F0); // slate-200
    }
  }

  Color get lifecycleTextColor {
    switch (computedLifecycleStatus) {
      case 'in_progress':
        return const Color(0xFF065F46); // emerald-800
      case 'completed':
        return const Color(0xFF1E40AF); // blue-800
      case 'upcoming':
      case 'pending':
        return const Color(0xFF92400E); // amber-800
      case 'cancellation_pending':
        return const Color(0xFF6B21A8); // purple-800
      case 'rejected':
        return const Color(0xFF991B1B); // red-800
      case 'cancelled':
        return const Color(0xFF9F1239); // rose-800
      default:
        return const Color(0xFF475569); // slate-600
    }
  }

  Color get lifecycleDotColor {
    switch (computedLifecycleStatus) {
      case 'in_progress':
        return const Color(0xFF10B981); // emerald-500
      case 'completed':
        return const Color(0xFF3B82F6); // blue-500
      case 'upcoming':
      case 'pending':
        return const Color(0xFFF59E0B); // amber-500
      case 'cancellation_pending':
        return const Color(0xFFA855F7); // purple-500
      case 'rejected':
        return const Color(0xFFEF4444); // red-500
      case 'cancelled':
        return const Color(0xFFF43F5E); // rose-500
      default:
        return const Color(0xFF94A3B8); // slate-400
    }
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

    final user = json['user'] is Map<String, dynamic>
        ? json['user'] as Map<String, dynamic>
        : (json['user'] is Map
              ? Map<String, dynamic>.from(json['user'] as Map)
              : null);

    final resolvedPicName = pic?['fullname'] != null
        ? '${pic!['fullname']}'
        : (user?['fullname'] != null ? '${user!['fullname']}' : null);

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
      eventType: json['event_type'] != null
          ? '${json['event_type']}'
          : (json['category'] != null ? '${json['category']}' : null),
      description: json['description'] != null
          ? '${json['description']}'
          : (json['keperluan'] != null
              ? '${json['keperluan']}'
              : (json['notes'] != null ? '${json['notes']}' : null)),
      roomId: json['room_id'] != null
          ? int.tryParse('${json['room_id']}')
          : null,
      roomName: room?['room_name'] != null ? '${room!['room_name']}' : null,
      picId: json['pic_id'] != null ? int.tryParse('${json['pic_id']}') : null,
      picName: resolvedPicName,
      userName: resolvedPicName,
      rejectionReason: json['rejection_reason'] != null
          ? '${json['rejection_reason']}'
          : null,
      lifecycleStatus: json['lifecycle_status']?.toString(),
      lifecycleStatusLabel: json['lifecycle_status_label']?.toString(),
      cancellationStatus: json['cancellation_status']?.toString(),
    );
  }
}
