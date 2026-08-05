import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/theme.dart';
import '../models/exam_model.dart';
import '../providers/exam_provider.dart';
import '../services/api_service.dart';

class KeyEditorScreen extends ConsumerStatefulWidget {
  final ExamModel exam;
  const KeyEditorScreen({super.key, required this.exam});

  @override
  ConsumerState<KeyEditorScreen> createState() => _KeyEditorScreenState();
}

class _KeyEditorScreenState extends ConsumerState<KeyEditorScreen> {
  String _selectedSet = 'A';
  late Map<String, Map<int, List<String>>>
  _setKeys; // {'A': {1: ['A'], 2: ['B']}}
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _initAnswerKey();
  }

  void _initAnswerKey() {
    _setKeys = {'A': {}, 'B': {}, 'C': {}};
    final existingKey = widget.exam.answerKey;

    for (var setKey in ['A', 'B', 'C']) {
      final Map<int, List<String>> currentMap = {};
      Map<String, dynamic> sourceMap = {};

      if (existingKey.containsKey('A')) {
        sourceMap = Map<String, dynamic>.from(existingKey[setKey] ?? {});
      } else if (setKey == 'A') {
        sourceMap = existingKey;
      }

      for (int q = 1; q <= widget.exam.questionCount; q++) {
        final val = sourceMap[q.toString()];
        if (val is String) {
          currentMap[q] = [val];
        } else if (val is Map) {
          final answers = List<String>.from(val['answers'] ?? []);
          currentMap[q] = answers;
        } else {
          currentMap[q] = [];
        }
      }
      _setKeys[setKey] = currentMap;
    }
  }

  void _toggleAnswer(int qNum, String option) {
    setState(() {
      final currentList = _setKeys[_selectedSet]![qNum] ?? [];
      if (currentList.contains(option)) {
        currentList.remove(option);
      } else {
        currentList.clear(); // Single choice default
        currentList.add(option);
      }
      _setKeys[_selectedSet]![qNum] = currentList;
    });
  }

  void _saveKey() async {
    setState(() => _isSaving = true);

    // Format output JSON structure: {'A': {'1': ['A'], ...}, 'B': {...}, 'C': {...}}
    final Map<String, dynamic> formattedData = {};
    _setKeys.forEach((set, questionsMap) {
      final Map<String, dynamic> qData = {};
      questionsMap.forEach((q, ansList) {
        if (ansList.isNotEmpty) {
          qData[q.toString()] = {
            'answers': ansList,
            'logic': 'OR',
            'points': 1.0,
            'penalty': 0.0,
          };
        }
      });
      formattedData[set] = qData;
    });

    try {
      final success = await ApiService.saveAnswerKey(
        widget.exam.examId,
        formattedData,
      );
      if (!mounted) return;
      setState(() => _isSaving = false);

      if (success) {
        ref.invalidate(examDetailProvider(widget.exam.examId));
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('บันทึกเฉลยเรียบร้อยแล้ว (Auto-Regrade เสร็จสิ้น)'),
            backgroundColor: AppColors.success,
          ),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString()), backgroundColor: AppColors.error),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final options = ['A', 'B', 'C', 'D', 'E'];
    final currentQuestionsMap = _setKeys[_selectedSet] ?? {};

    return Scaffold(
      backgroundColor: AppColors.navyBackground,
      appBar: AppBar(
        title: Text('แก้ไขเฉลย (${widget.exam.examTitle})'),
        actions: [
          IconButton(
            icon: _isSaving
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: AppColors.gold,
                    ),
                  )
                : const Icon(Icons.check_rounded, color: AppColors.gold),
            onPressed: _isSaving ? null : _saveKey,
            tooltip: 'บันทึกเฉลย',
          ),
        ],
      ),
      body: Column(
        children: [
          // Set Selector Tabs (A, B, C)
          Container(
            color: AppColors.navyCard,
            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: ['A', 'B', 'C'].map((set) {
                final isSelected = _selectedSet == set;
                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 6),
                  child: ChoiceChip(
                    label: Text(
                      'ชุดข้อสอบ $set',
                      style: GoogleFonts.sarabun(
                        fontWeight: FontWeight.bold,
                        color: isSelected
                            ? AppColors.navyBackground
                            : AppColors.textPrimary,
                      ),
                    ),
                    selected: isSelected,
                    selectedColor: AppColors.gold,
                    backgroundColor: AppColors.navySurface,
                    onSelected: (selected) {
                      if (selected) setState(() => _selectedSet = set);
                    },
                  ),
                );
              }).toList(),
            ),
          ),

          // Question Items List
          Expanded(
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: widget.exam.questionCount,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (ctx, idx) {
                final qNum = idx + 1;
                final selectedAns = currentQuestionsMap[qNum] ?? [];

                return Card(
                  color: AppColors.navyCard,
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                    child: Row(
                      children: [
                        // Question Number Badge
                        Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: AppColors.navySurface,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: AppColors.navyBorder),
                          ),
                          child: Center(
                            child: Text(
                              '$qNum',
                              style: GoogleFonts.outfit(
                                fontWeight: FontWeight.bold,
                                fontSize: 15,
                                color: AppColors.gold,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 16),

                        // Options A, B, C, D, E Pills
                        Expanded(
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                            children: options.map((opt) {
                              final isSelected = selectedAns.contains(opt);
                              return GestureDetector(
                                onTap: () => _toggleAnswer(qNum, opt),
                                child: AnimatedContainer(
                                  duration: const Duration(milliseconds: 150),
                                  width: 40,
                                  height: 40,
                                  decoration: BoxDecoration(
                                    color: isSelected
                                        ? AppColors.gold
                                        : AppColors.navySurface,
                                    shape: BoxShape.circle,
                                    border: Border.all(
                                      color: isSelected
                                          ? AppColors.gold
                                          : AppColors.navyBorder,
                                      width: isSelected ? 2 : 1,
                                    ),
                                    boxShadow: isSelected
                                        ? [
                                            BoxShadow(
                                              color: AppColors.gold.withValues(
                                                alpha: 0.3,
                                              ),
                                              blurRadius: 8,
                                            ),
                                          ]
                                        : null,
                                  ),
                                  child: Center(
                                    child: Text(
                                      opt,
                                      style: GoogleFonts.outfit(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 16,
                                        color: isSelected
                                            ? AppColors.navyBackground
                                            : AppColors.textPrimary,
                                      ),
                                    ),
                                  ),
                                ),
                              );
                            }).toList(),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: ElevatedButton(
            onPressed: _isSaving ? null : _saveKey,
            style: ElevatedButton.styleFrom(
              minimumSize: const Size(double.infinity, 50),
            ),
            child: _isSaving
                ? const CircularProgressIndicator(
                    color: AppColors.navyBackground,
                  )
                : const Text('บันทึกเฉลยทั้งหมด (Auto-Regrade)'),
          ),
        ),
      ),
    );
  }
}
