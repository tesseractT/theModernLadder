import 'package:flutter/material.dart';

abstract final class PairlyColors {
  static const Color surface = Color(0xFFF9F9FF);
  static const Color surfaceLow = Color(0xFFF1F3FF);
  static const Color surfaceHigh = Color(0xFFE1E8FD);
  static const Color surfaceHighest = Color(0xFFDCE2F7);
  static const Color surfaceCard = Color(0xFFFFFFFF);
  static const Color primary = Color(0xFF006B58);
  static const Color primaryBright = Color(0xFF20C7A6);
  static const Color primaryFixed = Color(0xFF68FAD7);
  static const Color ink = Color(0xFF141B2B);
  static const Color inkSoft = Color(0xFF3C4A45);
  static const Color outline = Color(0xFF6C7A75);
  static const Color outlineSoft = Color(0xFFBBCAC3);
  static const Color secondary = Color(0xFFFDBA53);
  static const Color secondaryDeep = Color(0xFF815500);
  static const Color accent = Color(0xFFA53263);
  static const Color error = Color(0xFFBA1A1A);
}

abstract final class PairlyTheme {
  static ThemeData light() {
    final scheme =
        ColorScheme.fromSeed(
          seedColor: PairlyColors.primary,
          brightness: Brightness.light,
          primary: PairlyColors.primary,
          secondary: PairlyColors.secondary,
          tertiary: PairlyColors.accent,
          surface: PairlyColors.surface,
          error: PairlyColors.error,
        ).copyWith(
          primary: PairlyColors.primary,
          onPrimary: Colors.white,
          primaryContainer: PairlyColors.primaryBright,
          onPrimaryContainer: const Color(0xFF004D3F),
          secondary: PairlyColors.secondaryDeep,
          onSecondary: Colors.white,
          secondaryContainer: PairlyColors.secondary,
          onSecondaryContainer: const Color(0xFF714B00),
          tertiary: PairlyColors.accent,
          onTertiary: Colors.white,
          tertiaryContainer: const Color(0xFFFF8EB6),
          onTertiaryContainer: const Color(0xFF821449),
          surface: PairlyColors.surface,
          onSurface: PairlyColors.ink,
          surfaceContainerLowest: PairlyColors.surfaceCard,
          surfaceContainerLow: PairlyColors.surfaceLow,
          surfaceContainer: const Color(0xFFE9EDFF),
          surfaceContainerHigh: PairlyColors.surfaceHigh,
          surfaceContainerHighest: PairlyColors.surfaceHighest,
          outline: PairlyColors.outline,
          outlineVariant: PairlyColors.outlineSoft,
          shadow: PairlyColors.primary.withValues(alpha: 0.12),
          scrim: PairlyColors.ink.withValues(alpha: 0.2),
        );

    final base = ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: PairlyColors.surface,
      brightness: Brightness.light,
      visualDensity: VisualDensity.adaptivePlatformDensity,
    );

    return base.copyWith(
      textTheme: base.textTheme.copyWith(
        displayLarge: base.textTheme.displayLarge?.copyWith(
          color: PairlyColors.ink,
          fontWeight: FontWeight.w800,
          letterSpacing: -1.6,
        ),
        displayMedium: base.textTheme.displayMedium?.copyWith(
          color: PairlyColors.ink,
          fontWeight: FontWeight.w800,
          letterSpacing: -1.2,
        ),
        headlineLarge: base.textTheme.headlineLarge?.copyWith(
          color: PairlyColors.ink,
          fontWeight: FontWeight.w800,
          letterSpacing: -0.8,
        ),
        headlineMedium: base.textTheme.headlineMedium?.copyWith(
          color: PairlyColors.ink,
          fontWeight: FontWeight.w700,
          letterSpacing: -0.5,
        ),
        titleLarge: base.textTheme.titleLarge?.copyWith(
          color: PairlyColors.ink,
          fontWeight: FontWeight.w700,
        ),
        titleMedium: base.textTheme.titleMedium?.copyWith(
          color: PairlyColors.ink,
          fontWeight: FontWeight.w700,
        ),
        bodyLarge: base.textTheme.bodyLarge?.copyWith(
          color: PairlyColors.ink,
          height: 1.45,
        ),
        bodyMedium: base.textTheme.bodyMedium?.copyWith(
          color: PairlyColors.inkSoft,
          height: 1.45,
        ),
        labelLarge: base.textTheme.labelLarge?.copyWith(
          color: Colors.white,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.2,
        ),
        labelMedium: base.textTheme.labelMedium?.copyWith(
          color: PairlyColors.inkSoft,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.3,
        ),
        labelSmall: base.textTheme.labelSmall?.copyWith(
          color: PairlyColors.outline,
          fontWeight: FontWeight.w700,
          letterSpacing: 1.0,
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: PairlyColors.surfaceLow,
        hintStyle: const TextStyle(color: PairlyColors.outline),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(999),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(999),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(999),
          borderSide: BorderSide(
            color: PairlyColors.primary.withValues(alpha: 0.18),
            width: 1,
          ),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 20,
          vertical: 18,
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: PairlyColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          minimumSize: const Size.fromHeight(58),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          textStyle: base.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w800,
            letterSpacing: -0.1,
          ),
        ),
      ),
      chipTheme: base.chipTheme.copyWith(
        side: BorderSide.none,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: PairlyColors.ink,
        contentTextStyle: base.textTheme.bodyMedium?.copyWith(
          color: Colors.white.withValues(alpha: 0.92),
        ),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
    );
  }
}
