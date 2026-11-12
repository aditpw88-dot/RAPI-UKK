import 'package:flutter/material.dart';

/// Tema dan pewarnaan aplikasi mobile RAPI Sekolah
/// Sepenuhnya selaras dengan Brand & Desain Web Laravel (RAPI Kalender Sekolah).
class AppTheme {
  // ── Warna Identitas Header & Navigasi (Sesuai Web Laravel) ───────────────
  static const Color headerBgDark = Color(0xFF211C18); // Espresso gelap
  static const Color headerBgMid = Color(0xFF2D221A);  // Espresso sedang
  static const Color headerBgEnd = Color(0xFF3B2B1B);  // Espresso terang
  static const Color headerBorder = Color(0xFFB45309); // Border aksen amber/emas

  // ── Warna Utama & Aksen Brand RAPI ───────────────────────────────────────
  static const Color brandAmber = Color(0xFFEA580C);      // Orange-600 / Amber utama RAPI
  static const Color brandAmberHover = Color(0xFFC2410C); // Orange-700
  static const Color brandAmberLight = Color(0xFFFFF7ED); // Orange-50 (latar lembut)
  static const Color brandGold = Color(0xFFFEF3C7);       // Amber-100 (teks judul header)
  static const Color brandGoldSub = Color(0xFFFDE68A);    // Amber-200 (teks subjudul header)

  // ── Warna Dasar Aplikasi ──────────────────────────────────────────────────
  static const Color primaryColor = Color(0xFFEA580C);    // Warna tombol & aksi utama
  static const Color primaryLight = Color(0xFFF97316);    // Orange-500
  static const Color accentColor = Color(0xFFD97706);     // Amber-600
  static const Color scaffoldBg = Color(0xFFF8F9FA);     // Latar belakang bersih lembut
  static const Color cardColor = Colors.white;
  static const Color cardBorder = Color(0xFFE5E7EB);     // Gray-200
  static const Color textPrimary = Color(0xFF0F172A);    // Slate-900
  static const Color textSecondary = Color(0xFF64748B);  // Slate-500
  static const Color sidebarBg = Color(0xFF211C18);      // Header/sidebar gelap

  // ── Gradient Header Standar RAPI ─────────────────────────────────────────
  static const LinearGradient headerGradient = LinearGradient(
    colors: [headerBgDark, headerBgMid, headerBgEnd],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );

  static const List<String> fontFallbacks = [
    'Figtree',
    '-apple-system',
    'BlinkMacSystemFont',
    'Segoe UI',
    'Roboto',
    'Helvetica Neue',
    'Arial',
    'sans-serif',
  ];

  /// Helper TextStyle performa tinggi (bebas lag jaringan & jank pada HP)
  static TextStyle fontStyle({
    double fontSize = 13.5,
    FontWeight fontWeight = FontWeight.normal,
    Color color = textPrimary,
    double? letterSpacing,
    double? height,
  }) {
    return TextStyle(
      fontFamily: 'Figtree',
      fontFamilyFallback: fontFallbacks,
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      letterSpacing: letterSpacing,
      height: height,
    );
  }

  static final ThemeData lightTheme = _buildTheme();

  static ThemeData _buildTheme() {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: brandAmber,
        primary: primaryColor,
        secondary: accentColor,
        surface: scaffoldBg,
        brightness: Brightness.light,
      ),
      scaffoldBackgroundColor: scaffoldBg,
    );

    // Tipografi Figtree ringan dengan fallback sistem cepat tanpa jeda jaringan
    final textTheme = base.textTheme.copyWith(
      bodyLarge: fontStyle(fontSize: 15, color: textPrimary),
      bodyMedium: fontStyle(fontSize: 13.5, color: textPrimary),
      bodySmall: fontStyle(fontSize: 11.5, color: textSecondary),
      titleLarge: fontStyle(fontSize: 18, fontWeight: FontWeight.w700, color: textPrimary),
      titleMedium: fontStyle(fontSize: 15, fontWeight: FontWeight.w600, color: textPrimary),
      titleSmall: fontStyle(fontSize: 13, fontWeight: FontWeight.w600, color: textPrimary),
    );

    return base.copyWith(
      textTheme: textTheme,
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: textPrimary,
        elevation: 0,
        scrolledUnderElevation: 1,
        shadowColor: cardBorder,
        centerTitle: false,
        titleTextStyle: fontStyle(
          fontSize: 16.5,
          fontWeight: FontWeight.w700,
          color: textPrimary,
          letterSpacing: -0.2,
        ),
        surfaceTintColor: Colors.white,
      ),
      cardTheme: CardThemeData(
        color: cardColor,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: cardBorder, width: 1),
        ),
        margin: const EdgeInsets.symmetric(vertical: 4, horizontal: 0),
      ),
      dividerColor: cardBorder,
      dividerTheme: const DividerThemeData(color: cardBorder, space: 1),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white,
        elevation: 3,
        shadowColor: Colors.black12,
        surfaceTintColor: Colors.white,
        indicatorColor: brandAmberLight,
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return fontStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w700,
              color: brandAmber,
            );
          }
          return fontStyle(
            fontSize: 11.5,
            fontWeight: FontWeight.w500,
            color: textSecondary,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const IconThemeData(color: brandAmber, size: 22);
          }
          return const IconThemeData(color: textSecondary, size: 21);
        }),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 12,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFD1D5DB)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFD1D5DB)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: brandAmber, width: 1.8),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFDC2626)),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFDC2626), width: 1.8),
        ),
        labelStyle: fontStyle(
          fontSize: 13.5,
          color: textSecondary,
          fontWeight: FontWeight.w500,
        ),
        hintStyle: fontStyle(
          fontSize: 13,
          color: const Color(0xFF9CA3AF),
        ),
        errorStyle: fontStyle(fontSize: 11.5, color: const Color(0xFFDC2626)),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: Colors.white,
          disabledBackgroundColor: const Color(0xFFD1D5DB),
          disabledForegroundColor: const Color(0xFF9CA3AF),
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          textStyle: fontStyle(
            fontSize: 13.5,
            fontWeight: FontWeight.w700,
          ),
          elevation: 0,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: brandAmber,
          side: const BorderSide(color: Color(0xFFE5E7EB), width: 1.2),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          textStyle: fontStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: brandAmber,
          textStyle: fontStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: brandAmber,
        foregroundColor: Colors.white,
        elevation: 3,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(16)),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: Colors.white,
        labelStyle: fontStyle(
          fontSize: 11.5,
          fontWeight: FontWeight.w500,
        ),
        side: const BorderSide(color: cardBorder),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: textPrimary,
        contentTextStyle: fontStyle(
          fontSize: 13,
          color: Colors.white,
          fontWeight: FontWeight.w500,
        ),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        titleTextStyle: fontStyle(
          fontSize: 16,
          fontWeight: FontWeight.w700,
          color: textPrimary,
        ),
        contentTextStyle: fontStyle(
          fontSize: 13,
          color: textSecondary,
        ),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
      ),
    );
  }

  // ── Helper Warna Status Perbaikan / Pengaduan (Sesuai Web Laravel) ────────
  static Color statusColor(String? status) {
    final s = (status ?? '').toLowerCase();
    if (s.contains('sudah terlaksana') || s.contains('completed')) {
      return const Color(0xFF1D4ED8); // blue-700
    }
    if (s.contains('sudah diperbaiki') || s.contains('selesai') || s.contains('approved') || s.contains('disetujui')) {
      return const Color(0xFF16A34A); // green-600
    }
    if (s.contains('sedang diperbaiki') || s.contains('sedang terlaksana') || s.contains('in progress')) {
      return const Color(0xFF059669); // emerald-600
    }
    if (s.contains('menunggu spare part') || s.contains('spare part') || s.contains('pembatalan')) {
      return const Color(0xFF7C3AED); // purple-600
    }
    if (s.contains('menunggu') || s.contains('pending') || s.contains('belum terlaksana')) {
      return const Color(0xFFD97706); // amber-600
    }
    if (s.contains('belum diperbaiki') || s.contains('belum') || s.contains('reject') || s.contains('ditolak')) {
      return const Color(0xFFDC2626); // red-600
    }
    return const Color(0xFF64748B); // slate-500
  }

  static Color statusBg(String? status) {
    final s = (status ?? '').toLowerCase();
    if (s.contains('sudah terlaksana') || s.contains('completed')) {
      return const Color(0xFFEFF6FF); // blue-50
    }
    if (s.contains('sudah diperbaiki') || s.contains('selesai') || s.contains('approved') || s.contains('disetujui')) {
      return const Color(0xFFF0FDF4); // green-50
    }
    if (s.contains('sedang diperbaiki') || s.contains('sedang terlaksana') || s.contains('in progress')) {
      return const Color(0xFFECFDF5); // emerald-50
    }
    if (s.contains('menunggu spare part') || s.contains('spare part') || s.contains('pembatalan')) {
      return const Color(0xFFF5F3FF); // purple-50
    }
    if (s.contains('menunggu') || s.contains('pending') || s.contains('belum terlaksana')) {
      return const Color(0xFFFFFBEB); // amber-50
    }
    if (s.contains('belum diperbaiki') || s.contains('belum') || s.contains('reject') || s.contains('ditolak')) {
      return const Color(0xFFFEF2F2); // red-50
    }
    return const Color(0xFFF8FAFC); // slate-50
  }

  static Color statusBorder(String? status) {
    final s = (status ?? '').toLowerCase();
    if (s.contains('sudah terlaksana') || s.contains('completed')) {
      return const Color(0xFFBFDBFE); // blue-200
    }
    if (s.contains('sudah diperbaiki') || s.contains('selesai') || s.contains('approved') || s.contains('disetujui')) {
      return const Color(0xFFBBF7D0); // green-200
    }
    if (s.contains('sedang diperbaiki') || s.contains('sedang terlaksana')) {
      return const Color(0xFFA7F3D0); // emerald-200
    }
    if (s.contains('menunggu spare part') || s.contains('spare part') || s.contains('pembatalan')) {
      return const Color(0xFFDDD6FE); // purple-200
    }
    if (s.contains('menunggu') || s.contains('pending') || s.contains('belum terlaksana')) {
      return const Color(0xFFFDE68A); // amber-200
    }
    if (s.contains('belum diperbaiki') || s.contains('belum') || s.contains('reject') || s.contains('ditolak')) {
      return const Color(0xFFFECACA); // red-200
    }
    return const Color(0xFFE2E8F0); // slate-200
  }

  // ── Helper Warna Kategori Agenda (Sesuai config/event_types.php Web) ─────
  static Color categoryAccent(String? category) {
    final c = (category ?? '').toLowerCase();
    if (c.contains('rapat') || c.contains('koordinasi')) {
      return const Color(0xFF3B82F6); // blue-500
    }
    if (c.contains('akademik') || c.contains('ujian') || c.contains('ukk')) {
      return const Color(0xFF8B5CF6); // purple-500
    }
    if (c.contains('upacara')) {
      return const Color(0xFFEF4444); // red-500
    }
    if (c.contains('kegiatan') || c.contains('sekolah')) {
      return const Color(0xFF22C55E); // green-500
    }
    return const Color(0xFFF97316); // orange-500 (Lainnya)
  }

  static Color categoryBg(String? category) {
    final c = (category ?? '').toLowerCase();
    if (c.contains('rapat') || c.contains('koordinasi')) {
      return const Color(0xFFDBEAFE); // blue-100
    }
    if (c.contains('akademik') || c.contains('ujian') || c.contains('ukk')) {
      return const Color(0xFFEDE9FE); // purple-100
    }
    if (c.contains('upacara')) {
      return const Color(0xFFFEE2E2); // red-100
    }
    if (c.contains('kegiatan') || c.contains('sekolah')) {
      return const Color(0xFFDCFCE7); // green-100
    }
    return const Color(0xFFFFEDD5); // orange-100 (Lainnya)
  }

  static Color categoryText(String? category) {
    final c = (category ?? '').toLowerCase();
    if (c.contains('rapat') || c.contains('koordinasi')) {
      return const Color(0xFF1E40AF); // blue-800
    }
    if (c.contains('akademik') || c.contains('ujian') || c.contains('ukk')) {
      return const Color(0xFF5B21B6); // purple-800
    }
    if (c.contains('upacara')) {
      return const Color(0xFF991B1B); // red-800
    }
    if (c.contains('kegiatan') || c.contains('sekolah')) {
      return const Color(0xFF166534); // green-800
    }
    return const Color(0xFF9A3412); // orange-800 (Lainnya)
  }
}
