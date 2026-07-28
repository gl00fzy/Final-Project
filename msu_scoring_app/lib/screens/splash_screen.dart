import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/theme.dart';
import '../providers/auth_provider.dart';
import 'login_screen.dart';
import 'dashboard_screen.dart';

class SplashScreen extends ConsumerWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    ref.listen<AuthState>(authProvider, (previous, next) {
      if (!next.isLoading) {
        if (next.isAuthenticated) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const DashboardScreen()),
          );
        } else {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const LoginScreen()),
          );
        }
      }
    });

    return Scaffold(
      backgroundColor: AppColors.navyBackground,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 90,
              height: 90,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [AppColors.gold, AppColors.goldDark],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.gold.withOpacity(0.35),
                    blurRadius: 30,
                    spreadRadius: 2,
                  )
                ],
              ),
              child: const Center(
                child: Text('🎯', style: TextStyle(fontSize: 42)),
              ),
            ),
            const SizedBox(height: 24),
            RichText(
              text: TextSpan(
                style: GoogleFonts.outfit(
                  fontSize: 28,
                  fontWeight: FontWeight.w900,
                  color: AppColors.textPrimary,
                ),
                children: const [
                  TextSpan(text: 'MSU '),
                  TextSpan(
                    text: 'Scoring',
                    style: TextStyle(color: AppColors.gold),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'ระบบตรวจข้อสอบอัจฉริยะ',
              style: GoogleFonts.sarabun(
                fontSize: 14,
                color: AppColors.textMuted,
              ),
            ),
            const SizedBox(height: 48),
            const SizedBox(
              width: 24,
              height: 24,
              child: CircularProgressIndicator(
                strokeWidth: 2.5,
                color: AppColors.gold,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
