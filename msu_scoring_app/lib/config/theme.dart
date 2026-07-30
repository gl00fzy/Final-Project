import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  static const Color navyBackground = Color(0xFF0A0E1A);
  static const Color navyCard = Color(0xFF141C2E);
  static const Color navySurface = Color(0xFF1E293B);
  static const Color navyBorder = Color(0x26F5C842);
  
  static const Color gold = Color(0xFFF5C842);
  static const Color goldLight = Color(0xFFFFE082);
  static const Color goldDark = Color(0xFFC9A227);
  
  static const Color textPrimary = Color(0xFFF0F4FF);
  static const Color textMuted = Color(0xFF8A95B0);
  
  static const Color success = Color(0xFF10B981);
  static const Color error = Color(0xFFEF4444);
  static const Color warning = Color(0xFFF59E0B);
  static const Color info = Color(0xFF3B82F6);
}

class AppTheme {
  static ThemeData get darkTheme {
    return ThemeData.dark().copyWith(
      scaffoldBackgroundColor: AppColors.navyBackground,
      colorScheme: const ColorScheme.dark(
        primary: AppColors.gold,
        secondary: AppColors.goldLight,
        surface: AppColors.navyCard,
        error: AppColors.error,
      ),
      cardTheme: CardThemeData(
        color: AppColors.navyCard,
        elevation: 4,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.navyBorder),
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.navyBackground.withValues(alpha: 0.8),
        elevation: 0,
        centerTitle: true,
        titleTextStyle: GoogleFonts.sarabun(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: AppColors.textPrimary,
        ),
      ),
      textTheme: GoogleFonts.sarabunTextTheme(
        ThemeData.dark().textTheme,
      ).apply(
        bodyColor: AppColors.textPrimary,
        displayColor: AppColors.textPrimary,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.navySurface,
        hintStyle: TextStyle(color: AppColors.textMuted.withValues(alpha: 0.6)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.navyBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.navyBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.gold, width: 1.5),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.gold,
          foregroundColor: AppColors.navyBackground,
          elevation: 2,
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: GoogleFonts.sarabun(
            fontSize: 15,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}
