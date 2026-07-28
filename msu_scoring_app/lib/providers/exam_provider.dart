import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/exam_model.dart';
import '../services/api_service.dart';

final examsListProvider = FutureProvider<List<ExamModel>>((ref) async {
  return await ApiService.getExams();
});

final examDetailProvider = FutureProvider.family<ExamModel, int>((ref, examId) async {
  return await ApiService.getExamDetail(examId);
});

final scoresListProvider = FutureProvider.family<Map<String, dynamic>, int>((ref, examId) async {
  return await ApiService.getScoresList(examId);
});

final analyticsProvider = FutureProvider.family<Map<String, dynamic>, int>((ref, examId) async {
  return await ApiService.getAnalytics(examId);
});
