import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'config/theme.dart';
import 'screens/splash_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    const ProviderScope(
      child: MsuScoringApp(),
    ),
  );
}

class MsuScoringApp extends StatelessWidget {
  const MsuScoringApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'MSU Scoring',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.darkTheme,
      home: const SplashScreen(),
    );
  }
}
