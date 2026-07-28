import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/theme.dart';
import '../providers/auth_provider.dart';
import '../providers/exam_provider.dart';
import '../services/api_service.dart';
import 'exam_detail_screen.dart';
import 'login_screen.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  final _searchController = TextEditingController();
  String _searchQuery = '';

  void _showCreateExamDialog() {
    final titleController = TextEditingController();
    final codeController = TextEditingController();
    final countController = TextEditingController(text: '50');

    showDialog(
      context: context,
      builder: (dialogCtx) => AlertDialog(
        backgroundColor: AppColors.navyCard,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          'สร้างชุดข้อสอบใหม่',
          style: GoogleFonts.sarabun(fontWeight: FontWeight.bold, color: AppColors.gold),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: titleController,
              decoration: const InputDecoration(
                labelText: 'ชื่อวิชา / ชุดข้อสอบ',
                hintText: 'เช่น การโปรแกรมคอมพิวเตอร์ 1',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: codeController,
              decoration: const InputDecoration(
                labelText: 'รหัสวิชา (Optional)',
                hintText: 'เช่น 01012345',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: countController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'จำนวนข้อสอบ (1-200)',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogCtx),
            child: const Text('ยกเลิก', style: TextStyle(color: AppColors.textMuted)),
          ),
          ElevatedButton(
            onPressed: () async {
              final title = titleController.text.trim();
              final code = codeController.text.trim();
              final count = int.tryParse(countController.text) ?? 50;

              if (title.isEmpty) return;

              try {
                await ApiService.createExam(title, code, count);
                if (!mounted) return;
                Navigator.pop(dialogCtx);
                ref.invalidate(examsListProvider);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('สร้างชุดข้อสอบเรียบร้อยแล้ว'), backgroundColor: AppColors.success),
                );
              } catch (e) {
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(e.toString()), backgroundColor: AppColors.error),
                );
              }
            },
            child: const Text('สร้างชุดข้อสอบ'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final examsAsync = ref.watch(examsListProvider);

    return Scaffold(
      backgroundColor: AppColors.navyBackground,
      appBar: AppBar(
        title: RichText(
          text: TextSpan(
            style: GoogleFonts.outfit(fontSize: 20, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
            children: const [
              TextSpan(text: 'MSU '),
              TextSpan(text: 'Scoring', style: TextStyle(color: AppColors.gold)),
            ],
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded, color: AppColors.textMuted),
            tooltip: 'ออกจากระบบ',
            onPressed: () async {
              await ref.read(authProvider.notifier).logout();
              if (mounted) {
                Navigator.of(context).pushReplacement(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                );
              }
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(examsListProvider);
        },
        color: AppColors.gold,
        backgroundColor: AppColors.navyCard,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // User Profile Banner
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      AppColors.navyCard,
                      AppColors.navySurface.withOpacity(0.8),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: AppColors.navyBorder),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 26,
                      backgroundColor: AppColors.gold.withOpacity(0.2),
                      child: Text(
                        (authState.user?.name ?? 'U')[0].toUpperCase(),
                        style: GoogleFonts.outfit(
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                          color: AppColors.gold,
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            authState.user?.name ?? 'อาจารย์',
                            style: GoogleFonts.sarabun(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            authState.user?.email ?? authState.user?.username ?? 'มหาวิทยาลัยมหาสารคาม',
                            style: GoogleFonts.sarabun(
                              fontSize: 13,
                              color: AppColors.textMuted,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Title + Search bar
              Text(
                'รายการชุดข้อสอบ',
                style: GoogleFonts.sarabun(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _searchController,
                onChanged: (val) => setState(() => _searchQuery = val.toLowerCase()),
                decoration: const InputDecoration(
                  hintText: 'ค้นหาชื่อวิชา หรือรหัสวิชา...',
                  prefixIcon: Icon(Icons.search, color: AppColors.gold),
                ),
              ),
              const SizedBox(height: 20),

              // Exam List Async
              examsAsync.when(
                data: (exams) {
                  final filteredExams = exams.where((e) {
                    return e.examTitle.toLowerCase().contains(_searchQuery) ||
                        (e.examCode?.toLowerCase().contains(_searchQuery) ?? false);
                  }).toList();

                  if (filteredExams.isEmpty) {
                    return Center(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 40),
                        child: Column(
                          children: [
                            const Text('📂', style: TextStyle(fontSize: 48)),
                            const SizedBox(height: 12),
                            Text(
                              'ยังไม่มีชุดข้อสอบ',
                              style: GoogleFonts.sarabun(fontSize: 16, color: AppColors.textMuted),
                            ),
                          ],
                        ),
                      ),
                    );
                  }

                  return ListView.separated(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: filteredExams.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 14),
                    itemBuilder: (ctx, idx) {
                      final exam = filteredExams[idx];
                      return Card(
                        child: InkWell(
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => ExamDetailScreen(examId: exam.examId),
                              ),
                            );
                          },
                          borderRadius: BorderRadius.circular(16),
                          child: Padding(
                            padding: const EdgeInsets.all(18),
                            child: Row(
                              children: [
                                Container(
                                  width: 48,
                                  height: 48,
                                  decoration: BoxDecoration(
                                    color: AppColors.gold.withOpacity(0.12),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: const Center(
                                    child: Text('📝', style: TextStyle(fontSize: 22)),
                                  ),
                                ),
                                const SizedBox(width: 16),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        exam.examTitle,
                                        style: GoogleFonts.sarabun(
                                          fontSize: 16,
                                          fontWeight: FontWeight.bold,
                                          color: AppColors.textPrimary,
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Row(
                                        children: [
                                          if (exam.examCode != null && exam.examCode!.isNotEmpty) ...[
                                            Text(
                                              exam.examCode!,
                                              style: const TextStyle(
                                                color: AppColors.gold,
                                                fontSize: 12,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                            const SizedBox(width: 8),
                                            const Text('•', style: TextStyle(color: AppColors.textMuted)),
                                            const SizedBox(width: 8),
                                          ],
                                          Text(
                                            '${exam.questionCount} ข้อ',
                                            style: const TextStyle(
                                              color: AppColors.textMuted,
                                              fontSize: 12,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: AppColors.success.withOpacity(0.15),
                                        borderRadius: BorderRadius.circular(100),
                                      ),
                                      child: Text(
                                        'สแกนแล้ว ${exam.scannedCount} ใบ',
                                        style: const TextStyle(
                                          color: AppColors.success,
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: AppColors.textMuted),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  );
                },
                loading: () => const Center(
                  child: Padding(
                    padding: EdgeInsets.symmetric(vertical: 40),
                    child: CircularProgressIndicator(color: AppColors.gold),
                  ),
                ),
                error: (err, stack) => Center(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 40),
                    child: Text(
                      'เกิดข้อผิดพลาดในการโหลดข้อมูล',
                      style: GoogleFonts.sarabun(color: AppColors.error),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showCreateExamDialog,
        backgroundColor: AppColors.gold,
        foregroundColor: AppColors.navyBackground,
        icon: const Icon(Icons.add_rounded),
        label: Text(
          'สร้างชุดข้อสอบ',
          style: GoogleFonts.sarabun(fontWeight: FontWeight.bold),
        ),
      ),
    );
  }
}
