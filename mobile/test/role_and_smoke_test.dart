import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/core/constants.dart';
import 'package:mobile/core/date_utils.dart';
import 'package:mobile/models/event_model.dart';
import 'package:mobile/models/room_model.dart';
import 'package:mobile/models/user_model.dart';
import 'package:mobile/views/auth/login_screen.dart';

void main() {
  testWidgets('Login screen hides quick demo credentials', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: LoginScreen(
          currentBaseUrl: 'http://localhost/api',
          onLoginSuccess: (a, b, c) {},
          onBackToCalendar: () {},
        ),
      ),
    );

    expect(find.text('Uji Cepat Peran (1-Klik Isi):'), findsNothing);
    expect(find.text('Masuk Aplikasi'), findsOneWidget);
    expect(find.text('Lupa Kata Sandi?'), findsOneWidget);
    expect(find.text('Masuk sebagai Siswa (Mode Tamu)'), findsNothing);
    expect(find.text('Portal Mobile untuk Guru dan PIC Ruang'), findsOneWidget);
    expect(find.text('ukk-aditya.test/api'), findsNothing);
    expect(find.byTooltip('Pengaturan server API'), findsNothing);
    expect(find.byIcon(Icons.settings_outlined), findsNothing);
  });

  group('Role Authorization Tests', () {
    test('Guru is mobile allowed', () {
      final guru = UserModel.fromJson({
        'id': 1,
        'username': 'guru1',
        'fullname': 'Guru Bahasa',
        'email': 'guru@sekolah.sch.id',
        'role': 'Guru/Organisasi',
      });

      expect(guru.isGuru, isTrue);
      expect(guru.isAdmin, isFalse);
      expect(guru.isMobileAllowed, isTrue);
    });

    test('PIC is mobile allowed', () {
      final pic = UserModel.fromJson({
        'id': 2,
        'username': 'pic1',
        'fullname': 'PIC Lab',
        'email': 'pic@sekolah.sch.id',
        'role': 'PIC Ruang',
      });

      expect(pic.isPic, isTrue);
      expect(pic.isAdmin, isFalse);
      expect(pic.isMobileAllowed, isTrue);
    });

    test('Admin and Waka are one web-only role group', () {
      final admin = UserModel.fromJson({
        'id': 4,
        'username': 'admin1',
        'fullname': 'Administrator',
        'email': 'admin@sekolah.sch.id',
        'role': 'Admin',
      });

      final waka = UserModel.fromJson({
        'id': 5,
        'username': 'waka1',
        'fullname': 'Waka Sarpras',
        'email': 'waka@sekolah.sch.id',
        'role': 'Waka Sapras',
      });

      expect(admin.isAdmin, isTrue);
      expect(admin.isMobileAllowed, isFalse);
      expect(admin.roleDisplayName, 'Admin / Waka (Web Only)');

      expect(waka.isAdmin, isTrue);
      expect(waka.isMobileAllowed, isFalse);
      expect(waka.roleDisplayName, admin.roleDisplayName);
    });

    test('Siswa is not a supported mobile role', () {
      final siswa = UserModel.fromJson({
        'id': 6,
        'username': 'siswa1',
        'fullname': 'Siswa',
        'email': 'siswa@sekolah.sch.id',
        'role': 'Siswa',
      });

      expect(siswa.isMobileAllowed, isFalse);
    });
  });

  group('Timetable and Date Utils Tests', () {
    test('School session timetable label', () {
      expect(AppConstants.sessionLabel(1), 'Sesi 1 (07:15 - 08:00)');
      expect(AppConstants.sessionLabel(13), 'Sesi 13 (16:15 - 17:00)');
    });

    test('Indonesian date formatting', () {
      final dt = DateTime(2026, 11, 2);
      expect(IndoDateUtils.formatIsoDate(dt), '2026-11-02');
      expect(IndoDateUtils.formatShort(dt), '2 Nov 2026');
      expect(IndoDateUtils.formatFull(dt), contains('2 November 2026'));
      expect(
        IndoDateUtils.formatDateRange('2026-11-02', '2026-11-02'),
        '2 Nov 2026',
      );
      expect(
        IndoDateUtils.formatDateRange('2026-11-02', '2026-11-04'),
        '2 Nov 2026 - 4 Nov 2026',
      );
    });

    test('Event model keeps compatibility with single-day events', () {
      final event = EventModel.fromJson({
        'id': 1,
        'event_name': 'Agenda satu hari',
        'date': '2026-11-02',
        'start_time': '08:00:00',
        'end_time': '09:00:00',
        'status': 'approved',
      });

      expect(event.endDate, event.date);
      expect(event.occursOn(DateTime(2026, 11, 2)), isTrue);
      expect(event.occursOn(DateTime(2026, 11, 3)), isFalse);
    });

    test('Event model reads the end date for multi-day events', () {
      final event = EventModel.fromJson({
        'id': 2,
        'event_name': 'Agenda beberapa hari',
        'date': '2026-11-02',
        'end_date': '2026-11-04',
        'start_time': '08:00:00',
        'end_time': '09:00:00',
        'status': 'approved',
      });

      expect(event.endDate, '2026-11-04');
      expect(event.occursOn(DateTime(2026, 11, 2)), isTrue);
      expect(event.occursOn(DateTime(2026, 11, 3)), isTrue);
      expect(event.occursOn(DateTime(2026, 11, 4)), isTrue);
      expect(event.occursOn(DateTime(2026, 11, 5)), isFalse);
    });

    test('Room model parses maintenance note and PIC WhatsApp URL', () {
      final room = RoomModel.fromJson({
        'id': 10,
        'room_name': 'Lab 1',
        'capacity': 36,
        'pic_id': 2,
        'is_active': 'Tidak aktif',
        'maintenance_note': 'Sedang renovasi AC sampai Jumat.',
        'pic': {
          'id': 2,
          'fullname': 'Pak Budi',
          'phone_number': '083115722144',
        },
      });

      expect(room.isActive, isFalse);
      expect(room.maintenanceNote, 'Sedang renovasi AC sampai Jumat.');
      expect(room.picName, 'Pak Budi');
      expect(room.picPhone, '083115722144');
      expect(room.picWhatsappUrl, contains('https://wa.me/6283115722144'));
    });

    test('Event model parses and computes lifecycle status and display', () {
      final pastEvent = EventModel.fromJson({
        'id': 101,
        'event_name': 'Rapat Lama',
        'date': '2020-01-01',
        'start_time': '08:00:00',
        'end_time': '09:00:00',
        'status': 'approved',
      });
      expect(pastEvent.lifecycleDisplay, 'Sudah Terlaksana');

      final futureEvent = EventModel.fromJson({
        'id': 102,
        'event_name': 'Rapat Depan',
        'date': '2099-01-01',
        'start_time': '08:00:00',
        'end_time': '09:00:00',
        'status': 'approved',
      });
      expect(futureEvent.lifecycleDisplay, 'Belum Terlaksana');

      final pendingEvent = EventModel.fromJson({
        'id': 103,
        'event_name': 'Rapat Menunggu',
        'date': '2026-11-20',
        'start_time': '08:00:00',
        'end_time': '09:00:00',
        'status': 'pending',
      });
      expect(pendingEvent.lifecycleDisplay, 'Menunggu Persetujuan');

      final explicitEvent = EventModel.fromJson({
        'id': 104,
        'event_name': 'Rapat Spesifik',
        'date': '2026-11-20',
        'start_time': '08:00:00',
        'end_time': '09:00:00',
        'status': 'approved',
        'lifecycle_status': 'in_progress',
        'lifecycle_status_label': 'Sedang Terlaksana',
      });
      expect(explicitEvent.lifecycleDisplay, 'Sedang Terlaksana');
    });
  });
}
