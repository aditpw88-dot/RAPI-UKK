import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Warna dan typography mengikuti komponen bersama pada web Laravel.
class AppTheme {
  static const Color primaryColor = Color(0xFF111827); // gray-900
  static const Color primaryLight = Color(0xFF374151); // gray-700
  static const Color accentColor = Color(0xFF374151); // gray-700
  static const Color scaffoldBg = Color(0xFFF3F4F6); // gray-100
  static const Color cardColor = Colors.white;
  static const Color cardBorder = Color(0xFFE5E7EB); // gray-200
  static const Color textPrimary = Color(0xFF0F172A); // slate-900
  static const Color textSecondary = Color(0xFF64748B); // slate-500
  static const Color sidebarBg = Color(0xFFF3F4F6); // gray-100

  static final ThemeData lightTheme = _buildTheme();

  static ThemeData _buildTheme() {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: accentColor,
        primary: primaryColor,
        secondary: accentColor,
        surface: scaffoldBg,
        brightness: Brightness.light,
      ),
      scaffoldBackgroundColor: scaffoldBg,
    );

    // Gunakan Figtree (seperti web) via Google Fonts
    final textTheme = GoogleFonts.figtreeTextTheme(
      base.textTheme,
    ).apply(bodyColor: textPrimary, displayColor: textPrimary).copyWith(
      bodyMedium: GoogleFonts.figtree(color: textPrimary),
      bodySmall: GoogleFonts.figtree(color: textSecondary),
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
        titleTextStyle: GoogleFonts.figtree(
          fontSize: 17,
          fontWeight: FontWeight.w700,
          color: textPrimary,
          letterSpacing: -0.3,
        ),
        surfaceTintColor: Colors.white,
      ),
      cardTheme: CardThemeData(
        color: cardColor,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12), // rounded-xl
          side: const BorderSide(color: cardBorder, width: 1),
        ),
        margin: const EdgeInsets.symmetric(vertical: 5, horizontal: 0),
      ),
      dividerColor: cardBorder,
      dividerTheme: const DividerThemeData(color: cardBorder, space: 1),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white,
        elevation: 2,
        shadowColor: cardBorder,
        surfaceTintColor: Colors.white,
        indicatorColor: cardBorder,
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return GoogleFonts.figtree(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: accentColor,
            );
          }
          return GoogleFonts.figtree(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: textSecondary,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const IconThemeData(color: accentColor);
          }
          return IconThemeData(color: textSecondary);
        }),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFD1D5DB)), // gray-300
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFD1D5DB)), // gray-300
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFF6B7280), width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFDC2626)), // red-600
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFDC2626), width: 2),
        ),
        labelStyle: GoogleFonts.figtree(
          fontSize: 14,
          color: textSecondary,
          fontWeight: FontWeight.w500,
        ),
        hintStyle: GoogleFonts.figtree(
          fontSize: 14,
          color: const Color(0xFF9CA3AF), // gray-400 placeholder
        ),
        errorStyle: GoogleFonts.figtree(fontSize: 12, color: Color(0xFFDC2626)),
      ),
      // Tombol utama mengikuti aksi bg-gray-900 pada web.
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: Colors.white,
          disabledBackgroundColor: const Color(0xFF9CA3AF),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          textStyle: GoogleFonts.figtree(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
          elevation: 0,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: primaryColor,
          side: const BorderSide(color: Color(0xFFD1D5DB), width: 1.5),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          textStyle: GoogleFonts.figtree(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: accentColor,
          textStyle: GoogleFonts.figtree(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: primaryColor,
        foregroundColor: Colors.white,
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(16)),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: const Color(0xFFF9FAFB), // gray-50
        labelStyle: GoogleFonts.figtree(
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
        side: const BorderSide(color: Color(0xFFE5E7EB)),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: primaryColor,
        contentTextStyle: GoogleFonts.figtree(
          fontSize: 14,
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
        titleTextStyle: GoogleFonts.figtree(
          fontSize: 16,
          fontWeight: FontWeight.w700,
          color: textPrimary,
        ),
        contentTextStyle: GoogleFonts.figtree(
          fontSize: 14,
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
      listTileTheme: ListTileThemeData(
        titleTextStyle: GoogleFonts.figtree(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: textPrimary,
        ),
        subtitleTextStyle: GoogleFonts.figtree(
          fontSize: 12,
          color: textSecondary,
        ),
      ),
    );
  }

  // ── Helper untuk status badge ──────────────────────────────────────────
  static Color statusColor(String? status) {
    final s = (status ?? '').toLowerCase();
    if (s.contains('sudah') ||
        s.contains('approved') ||
        s.contains('setujui') ||
        s.contains('disetujui')) {
      return const Color(0xFF16A34A); // green-600
    }
    if (s.contains('spare part') ||
        s.contains('menunggu') ||
        s.contains('pending')) {
      return const Color(0xFFD97706); // amber-600
    }
    if (s.contains('belum') || s.contains('reject') || s.contains('ditolak')) {
      return const Color(0xFFDC2626); // red-600
    }
    return const Color(0xFF6B7280); // gray-500
  }

  static Color statusBg(String? status) {
    final s = (status ?? '').toLowerCase();
    if (s.contains('sudah') ||
        s.contains('approved') ||
        s.contains('setujui') ||
        s.contains('disetujui')) {
      return const Color(0xFFF0FDF4); // green-50
    }
    if (s.contains('spare part') ||
        s.contains('menunggu') ||
        s.contains('pending')) {
      return const Color(0xFFFFFBEB); // amber-50
    }
    if (s.contains('belum') || s.contains('reject') || s.contains('ditolak')) {
      return const Color(0xFFFEF2F2); // red-50
    }
    return const Color(0xFFF9FAFB); // gray-50
  }
}
