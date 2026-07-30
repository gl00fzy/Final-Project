import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/theme.dart';
import '../models/exam_model.dart';
import '../providers/exam_provider.dart';

class AnalyticsScreen extends ConsumerWidget {
  final ExamModel exam;
  const AnalyticsScreen({super.key, required this.exam});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final analyticsAsync = ref.watch(analyticsProvider(exam.examId));

    return Scaffold(
      backgroundColor: AppColors.navyBackground,
      appBar: AppBar(
        title: Text('สถิติ & Item Analysis (${exam.examTitle})'),
      ),
      body: analyticsAsync.when(
        data: (data) {
          final summary = data['summary'] ?? {};
          final List itemAnalysis = data['item_analysis'] ?? [];

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(analyticsProvider(exam.examId)),
            color: AppColors.gold,
            backgroundColor: AppColors.navyCard,
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Overview Title
                  Text(
                    'สรุปผลภาพรวม',
                    style: GoogleFonts.sarabun(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Summary Grid
                  GridView.count(
                    crossAxisCount: 2,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisSpacing: 12,
                    mainAxisSpacing: 12,
                    childAspectRatio: 2.2,
                    children: [
                      _buildMetricCard('จำนวนนิสิต', '${summary['total'] ?? 0} คน', Icons.people_outline, AppColors.info),
                      _buildMetricCard('คะแนนเฉลี่ย', '${summary['avg'] ?? 0}', Icons.analytics_outlined, AppColors.gold),
                      _buildMetricCard('สูงสุด / ต่ำสุด', '${summary['max']} / ${summary['min']}', Icons.leaderboard_outlined, AppColors.success),
                      _buildMetricCard('ส่วนเบี่ยงเบน (SD)', '${summary['std_dev'] ?? 0}', Icons.show_chart_rounded, AppColors.warning),
                    ],
                  ),
                  const SizedBox(height: 28),

                  // Item Analysis Section
                  Text(
                    'การวิเคราะห์รายข้อ (Item Analysis)',
                    style: GoogleFonts.sarabun(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 12),

                  if (itemAnalysis.isEmpty)
                    Center(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 30),
                        child: Text('ยังไม่มีข้อมูลสำหรับวิเคราะห์รายข้อ', style: GoogleFonts.sarabun(color: AppColors.textMuted)),
                      ),
                    )
                  else
                    Card(
                      color: AppColors.navyCard,
                      margin: EdgeInsets.zero,
                      child: SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: DataTable(
                          columnSpacing: 24,
                          headingRowHeight: 44,
                          columns: const [
                            DataColumn(label: Text('ข้อที่', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.gold))),
                            DataColumn(label: Text('ตอบถูก (คน)', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary))),
                            DataColumn(label: Text('ค่าความยาก (p)', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary))),
                            DataColumn(label: Text('ประเมินความยาก', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary))),
                          ],
                          rows: itemAnalysis.map<DataRow>((item) {
                            final pVal = (item['difficulty_p'] as num).toDouble();
                            final eval = item['evaluation']?.toString() ?? '-';

                            Color evalColor = AppColors.success;
                            if (eval == 'ยากมาก') evalColor = AppColors.error;
                            if (eval == 'ง่ายมาก') evalColor = AppColors.info;

                            return DataRow(
                              cells: [
                                DataCell(Text('${item['question']}', style: GoogleFonts.outfit(fontWeight: FontWeight.bold))),
                                DataCell(Text('${item['correct_count']} / ${item['total_students']}')),
                                DataCell(Text('$pVal', style: GoogleFonts.outfit(fontWeight: FontWeight.bold, color: AppColors.gold))),
                                DataCell(
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: evalColor.withValues(alpha: 0.15),
                                      borderRadius: BorderRadius.circular(100),
                                    ),
                                    child: Text(
                                      eval,
                                      style: TextStyle(color: evalColor, fontSize: 12, fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                ),
                              ],
                            );
                          }).toList(),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator(color: AppColors.gold)),
        error: (err, stack) => const Center(child: Text('Error loading analytics')),
      ),
    );
  }

  Widget _buildMetricCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.navyCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.navyBorder),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(value, style: GoogleFonts.outfit(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textPrimary)),
                const SizedBox(height: 2),
                Text(title, style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
