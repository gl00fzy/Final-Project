import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/theme.dart';
import '../providers/auth_provider.dart';
import 'dashboard_screen.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _usernameController = TextEditingController(text: 'teacher_demo');
  final _passwordController = TextEditingController(text: 'password123');
  bool _obscurePassword = true;

  void _handleLogin() async {
    final username = _usernameController.text.trim();
    final password = _passwordController.text.trim();

    if (username.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    final success = await ref.read(authProvider.notifier).login(username, password);

    if (success && mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const DashboardScreen()),
      );
    } else if (mounted) {
      final errorMsg = ref.read(authProvider).errorMessage ?? 'เข้าสู่ระบบไม่สำเร็จ';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(errorMsg),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    return Scaffold(
      backgroundColor: AppColors.navyBackground,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Logo Icon
                Center(
                  child: Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [AppColors.gold, AppColors.goldDark],
                      ),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.gold.withOpacity(0.3),
                          blurRadius: 20,
                        )
                      ],
                    ),
                    child: const Center(
                      child: Text('🎯', style: TextStyle(fontSize: 34)),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  'MSU Scoring',
                  textAlign: TextAlign.center,
                  style: GoogleFonts.outfit(
                    fontSize: 32,
                    fontWeight: FontWeight.w900,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'ระบบตรวจข้อสอบปรนัยสำหรับอาจารย์',
                  textAlign: TextAlign.center,
                  style: GoogleFonts.sarabun(
                    fontSize: 14,
                    color: AppColors.textMuted,
                  ),
                ),
                const SizedBox(height: 40),

                // Username field
                Text(
                  'ชื่อผู้ใช้งาน',
                  style: GoogleFonts.sarabun(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: _usernameController,
                  decoration: const InputDecoration(
                    hintText: 'กรอกชื่อผู้ใช้งาน',
                    prefixIcon: Icon(Icons.person_outline, color: AppColors.gold),
                  ),
                ),
                const SizedBox(height: 20),

                // Password field
                Text(
                  'รหัสผ่าน',
                  style: GoogleFonts.sarabun(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: _passwordController,
                  obscureText: _obscurePassword,
                  decoration: InputDecoration(
                    hintText: 'กรอกรหัสผ่าน',
                    prefixIcon: const Icon(Icons.lock_outline, color: AppColors.gold),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword ? Icons.visibility_off : Icons.visibility,
                        color: AppColors.textMuted,
                      ),
                      onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                    ),
                  ),
                ),
                const SizedBox(height: 32),

                // Submit Button
                ElevatedButton(
                  onPressed: authState.isLoading ? null : _handleLogin,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: authState.isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.navyBackground),
                        )
                      : const Text('เข้าสู่ระบบ', style: TextStyle(fontSize: 16)),
                ),
                const SizedBox(height: 20),

                // Divider
                Row(
                  children: [
                    const Expanded(child: Divider(color: AppColors.navyBorder)),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Text(
                        'หรือ',
                        style: GoogleFonts.sarabun(color: AppColors.textMuted, fontSize: 13),
                      ),
                    ),
                    const Expanded(child: Divider(color: AppColors.navyBorder)),
                  ],
                ),
                const SizedBox(height: 20),

                // Google Sign In Button
                OutlinedButton.icon(
                  onPressed: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('กำลังเชื่อมต่อ Google OAuth (Web/Mobile)'),
                      ),
                    );
                  },
                  icon: const Text('🌐', style: TextStyle(fontSize: 18)),
                  label: Text(
                    'เข้าสู่ระบบด้วย Google (@msu.ac.th)',
                    style: GoogleFonts.sarabun(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    side: const BorderSide(color: AppColors.navyBorder),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
