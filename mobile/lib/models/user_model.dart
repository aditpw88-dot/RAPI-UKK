import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class UserModel {
  const UserModel({
    required this.id,
    required this.username,
    required this.fullname,
    required this.email,
    required this.role,
    this.phone,
  });

  final int id;
  final String username;
  final String fullname;
  final String email;
  final String role;
  final String? phone;

  String get displayName => fullname.trim().isNotEmpty ? fullname : username;

  String get normalizedRole => role.toLowerCase();

  bool get isAdmin =>
      normalizedRole.contains('admin') || normalizedRole.contains('waka');

  bool get isGuru => normalizedRole.contains('guru');

  bool get isPic => normalizedRole.contains('pic');

  bool get isMobileAllowed => isGuru || isPic;

  String get roleDisplayName {
    if (isGuru) return 'Guru / Organisasi';
    if (isPic) return 'PIC Ruang';
    if (isAdmin) return 'Admin / Waka (Web Only)';
    return role.isNotEmpty ? role : 'Pengguna';
  }

  Color get roleBadgeColor => AppTheme.primaryColor;

  IconData get roleIcon {
    if (isGuru) return Icons.school_outlined;
    if (isPic) return Icons.admin_panel_settings_outlined;
    return Icons.badge_outlined;
  }

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] is int
          ? json['id'] as int
          : int.tryParse('${json['id']}') ?? 0,
      username: '${json['username'] ?? ''}',
      fullname: '${json['fullname'] ?? ''}',
      email: '${json['email'] ?? ''}',
      role: '${json['role'] ?? ''}',
      phone: json['phone_number'] != null ? '${json['phone_number']}' : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'username': username,
      'fullname': fullname,
      'email': email,
      'role': role,
      if (phone != null) 'phone_number': phone,
    };
  }
}
