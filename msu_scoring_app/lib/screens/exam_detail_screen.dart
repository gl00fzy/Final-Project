import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/theme.dart';
import '../models/exam_model.dart';
import '../providers/exam_provider.dart';
import '../services/api_service.dart';
import 'scanner_screen.dart';
import 'key_editor_screen.dart';
import 'analytics_screen.dart';

class ExamDetailScreen extends ConsumerStatefulWidget {
  final int examId;
  const ExamDetailScreen({super.key, required this.examId});

  @override
  ConsumerState<ExamDetailScreen> createState() => _ExamDetailScreenState();
}

class _ExamDetailScreenState extends ConsumerState<ExamDetailScreen> {

  void _showManualScoreDialog(ExamModel exam) {
    final studentIdController = TextEditingController();
    final scoreController = TextEditingController();
    String selectedSet = 'A';

    showDialog(
      context: context,
      builder: (dialogCtx) => AlertDialog(
        backgroundColor: AppColors.navyCard,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          'กรอกคะแนนด้วยตนเอง',
          style: GoogleFonts.sarabun(fontWeight: FontWeight.bold, color: AppColors.gold),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: studentIdController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'รหัสนิสิต (11 หลัก)',
                hintText: 'เช่น 66011234567',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: scoreController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'คะแนนที่ได้',
                hintText: 'เช่น 45',
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
              final studentId = studentIdController.text.trim();
              final score = double.tryParse(scoreController.text.trim()) ?? 0;

              if (studentId.isEmpty) return;

              try {
                await ApiService.submitScore(
                  examId: exam.examId,
                  studentId: studentId,
                  score: score,
                  examSet: selectedSet,
                );
                if (!mounted) return;
                Navigator.pop(dialogCtx);
                ref.invalidate(scoresListProvider(exam.examId));
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('บันทึกคะแนนเรียบร้อยแล้ว'), backgroundColor: AppColors.success),
                );
              } catch (e) {
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(e.toString()), backgroundColor: AppColors.error),
                );
              }
            },
            child: const Text('บันทึกคะแนน'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final examAsync = ref.watch(examDetailProvider(widget.examId));
    final scoresAsync = ref.watch(scoresListProvider(widget.examId));

    return Scaffold(
      backgroundColor: AppColors.navyBackground,
      appBar: AppBar(
        title: const Text('รายละเอียดชุดข้อสอบ'),
      ),
      body: examAsync.when(
        data: (exam) {
          return RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(examDetailProvider(widget.examId));
              ref.invalidate(scoresListProvider(widget.examId));
            },
            color: AppColors.gold,
            backgroundColor: AppColors.navyCard,
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Exam Header Card
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: AppColors.navyCard,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: AppColors.navyBorder),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          exam.examTitle,
                          style: GoogleFonts.sarabun(
                            fontSize: 22,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            if (exam.examCode != null && exam.examCode!.isNotEmpty) ...[
                              Text(
                                exam.examCode!,
                                style: const TextStyle(
                                  color: AppColors.gold,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Text('•', style: TextStyle(color: AppColors.textMuted)),
                              const SizedBox(width: 8),
                            ],
                            Text(
                              '${exam.questionCount} ข้อ',
                              style: const TextStyle(color: AppColors.textMuted),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Primary Scan Action Button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => ScannerScreen(exam: exam),
                          ),
                        );
                      },
                      icon: const Icon(Icons.camera_alt_rounded),
                      label: const Text('เริ่มสแกนด้วยกล้องสด'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.gold,
                        foregroundColor: AppColors.navyBackground,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Secondary Action Buttons Grid (Key Editor, Analytics, Manual Entry)
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => KeyEditorScreen(exam: exam),
                              ),
                            );
                          },
                          icon: const Icon(Icons.vpn_key_rounded, size: 18, color: AppColors.gold),
                          label: const Text('แก้ไขเฉลย', style: TextStyle(color: AppColors.textPrimary)),
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            side: const BorderSide(color: AppColors.navyBorder),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => AnalyticsScreen(exam: exam),
                              ),
                            );
                          },
                          icon: const Icon(Icons.bar_chart_rounded, size: 18, color: AppColors.gold),
                          label: const Text('สถิติ & สเปก', style: TextStyle(color: AppColors.textPrimary)),
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            side: const BorderSide(color: AppColors.navyBorder),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      IconButton.outlined(
                        onPressed: () => _showManualScoreDialog(exam),
                        icon: const Icon(Icons.edit_note_rounded, color: AppColors.gold),
                        style: IconButton.styleFrom(
                          side: const BorderSide(color: AppColors.navyBorder),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          padding: const EdgeInsets.all(12),
                        ),
                        tooltip: 'กรอกคะแนนด้วยตนเอง',
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),

                  // Scanned Student Scores Section
                  Text(
                    'ผลการสแกนนิสิต',
                    style: GoogleFonts.sarabun(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 12),

                  scoresAsync.when(
                    data: (scoreData) {
                      final List scores = scoreData['scores'] ?? [];
                      final Map summary = scoreData['summary'] ?? {};

                      if (scores.isEmpty) {
                        return Center(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 40),
                            child: Column(
                              children: [
                                const Text('📷', style: TextStyle(fontSize: 40)),
                                const SizedBox(height: 8),
                                Text(
                                  'ยังไม่มีผลการสแกน',
                                  style: GoogleFonts.sarabun(color: AppColors.textMuted),
                                ),
                              ],
                            ),
                          ),
                        );
                      }

                      return Column(
                        children: [
                          // Summary statistics bar
                          Container(
                            padding: const EdgeInsets.all(16),
                            margin: const EdgeInsets.only(bottom: 14),
                            decoration: BoxDecoration(
                              color: AppColors.navySurface,
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceAround,
                              children: [
                                _buildStatItem('รวมสแกน', '${summary['total_students']} ใบ'),
                                _buildStatItem('เฉลี่ย', '${summary['mean']} คะแนน'),
                                _buildStatItem('สูงสุด', '${summary['max']}'),
                                _buildStatItem('ต่ำสุด', '${summary['min']}'),
                              ],
                            ),
                          ),

                          // Scores List
                          ListView.separated(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: scores.length,
                            separatorBuilder: (_, _) => const SizedBox(height: 10),
                            itemBuilder: (ctx, idx) {
                              final sc = scores[idx];
                              return Card(
                                margin: EdgeInsets.zero,
                                child: ListTile(
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                                  title: Text(
                                    sc.studentId,
                                    style: GoogleFonts.outfit(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                      color: AppColors.textPrimary,
                                    ),
                                  ),
                                  subtitle: Text(
                                    'ชุด ${sc.examSet} • ${sc.scannedAt ?? ''}',
                                    style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                                  ),
                                  trailing: Text(
                                    '${sc.score}',
                                    style: GoogleFonts.outfit(
                                      fontSize: 22,
                                      fontWeight: FontWeight.w900,
                                      color: AppColors.gold,
                                    ),
                                  ),
                                ),
                              );
                            },
                          ),
                        ],
                      );
                    },
                    loading: () => const Center(child: CircularProgressIndicator(color: AppColors.gold)),
                    error: (err, stack) => const Text('Error loading scores', style: TextStyle(color: AppColors.error)),
                  ),
                ],
              ),
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator(color: AppColors.gold)),
        error: (err, stack) => const Center(child: Text('Error loading exam detail')),
      ),
    );
  }

  Widget _buildStatItem(String label, String value) {
    return Column(
      children: [
        Text(value, style: GoogleFonts.outfit(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.gold)),
        const SizedBox(height: 2),
        Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
      ],
    );
  }
}
