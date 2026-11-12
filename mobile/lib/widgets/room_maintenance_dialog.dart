import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../core/url_opener.dart';
import '../models/room_model.dart';

Future<void> showRoomMaintenanceDialog(
  BuildContext context,
  RoomModel room,
) {
  final note = (room.maintenanceNote != null &&
          room.maintenanceNote!.trim().isNotEmpty)
      ? room.maintenanceNote!.trim()
      : 'Ruangan sedang dalam proses perbaikan / renovasi dan sementara tidak dapat digunakan.';
  final picName =
      (room.picName != null && room.picName!.trim().isNotEmpty)
          ? room.picName!.trim()
          : 'PIC Ruangan';
  final picPhone = room.picPhone?.trim();
  final waUrl = room.picWhatsappUrl;

  return showDialog<void>(
    context: context,
    builder: (ctx) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      titlePadding: const EdgeInsets.fromLTRB(20, 20, 20, 12),
      contentPadding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
      actionsPadding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
      title: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFFFEF3C7),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.warning_amber_rounded,
              color: Color(0xFFB45309),
              size: 24,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  room.roomName.toUpperCase(),
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.6,
                    color: Color(0xFFB45309),
                  ),
                ),
                const SizedBox(height: 2),
                const Text(
                  'Ruangan Sedang Dalam Perbaikan',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF0F172A),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Divider(),
            const SizedBox(height: 8),
            const Text(
              'Ruangan sedang dalam perbaikan, mohon lihat catatan ini dan hubungi nomor di bawah ini:',
              style: TextStyle(
                fontSize: 13,
                height: 1.4,
                fontWeight: FontWeight.w500,
                color: Color(0xFF334155),
              ),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'CATATAN DARI PIC TENTANG RUANGAN',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                      color: Color(0xFF92400E),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    note,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF1E293B),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'PENANGGUNG JAWAB (PIC) RUANGAN',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                      color: Color(0xFF64748B),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    picName,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    picPhone != null && picPhone.isNotEmpty
                        ? 'Nomor WhatsApp: $picPhone'
                        : 'Nomor WhatsApp belum tersedia',
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF475569),
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
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        onPressed: () async {
                          final opened = openExternalUrl(waUrl);
                          if (!opened) {
                            await Clipboard.setData(ClipboardData(text: waUrl));
                            if (ctx.mounted) {
                              ScaffoldMessenger.of(ctx).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    'Link WhatsApp PIC ($picPhone) telah disalin.',
                                  ),
                                ),
                              );
                            }
                          }
                        },
                        icon: const Icon(Icons.chat_outlined, size: 18),
                        label: const Text(
                          'Hubungi PIC via WhatsApp',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
      actions: [
        SizedBox(
          width: double.infinity,
          child: FilledButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Mengerti & Tutup'),
          ),
        ),
      ],
    ),
  );
}

