import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/user_model.dart';
import '../models/exam_model.dart';
import '../models/score_model.dart';
import 'auth_service.dart';

class ApiService {
  // Helper for Authorization Headers
  static Future<Map<String, String>> _getHeaders() async {
    final token = await AuthService.getToken();
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  // 1. Login with Username & Password
  static Future<Map<String, dynamic>> login(String username, String password) async {
    final url = Uri.parse('${ApiConfig.authEndpoint}?action=login');
    final response = await http.post(
      url,
      body: {
        'username': username,
        'password': password,
      },
    );

    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['status'] == 'success') {
      final token = data['token'];
      final user = UserModel.fromJson(data['user']);
      await AuthService.saveAuthData(token, user);
    }
    return data;
  }

  // 2. Login with Google ID Token
  static Future<Map<String, dynamic>> loginWithGoogle(String idToken) async {
    final url = Uri.parse('${ApiConfig.authEndpoint}?action=google');
    final response = await http.post(
      url,
      body: {
        'id_token': idToken,
      },
    );

    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['status'] == 'success') {
      final token = data['token'];
      final user = UserModel.fromJson(data['user']);
      await AuthService.saveAuthData(token, user);
    }
    return data;
  }

  // 3. Get Exams List
  static Future<List<ExamModel>> getExams() async {
    final url = Uri.parse('${ApiConfig.examsEndpoint}?action=list');
    final headers = await _getHeaders();
    final response = await http.get(url, headers: headers);

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['status'] == 'success') {
        final List list = data['data'] ?? [];
        return list.map((item) => ExamModel.fromJson(item)).toList();
      }
    }
    throw Exception('Failed to fetch exams');
  }

  // 4. Get Exam Detail
  static Future<ExamModel> getExamDetail(int examId) async {
    final url = Uri.parse('${ApiConfig.examsEndpoint}?action=detail&exam_id=$examId');
    final headers = await _getHeaders();
    final response = await http.get(url, headers: headers);

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['status'] == 'success') {
        return ExamModel.fromJson(data['data']);
      }
    }
    throw Exception('Failed to fetch exam detail');
  }

  // 5. Create Exam
  static Future<int> createExam(String title, String code, int questionCount) async {
    final url = Uri.parse('${ApiConfig.examsEndpoint}?action=create');
    final headers = await _getHeaders();
    final response = await http.post(
      url,
      headers: headers,
      body: {
        'exam_title': title,
        'exam_code': code,
        'question_count': questionCount.toString(),
      },
    );

    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['status'] == 'success') {
      return data['exam_id'];
    }
    throw Exception(data['message'] ?? 'Failed to create exam');
  }

  // 6. Save Answer Key
  static Future<bool> saveAnswerKey(int examId, Map<String, dynamic> keyData) async {
    final url = Uri.parse('${ApiConfig.examsEndpoint}?action=save_key');
    final headers = await _getHeaders();
    final response = await http.post(
      url,
      headers: headers,
      body: {
        'exam_id': examId.toString(),
        'answer_key': jsonEncode(keyData),
      },
    );

    final data = jsonDecode(response.body);
    return response.statusCode == 200 && data['status'] == 'success';
  }

  // 7. Submit Score
  static Future<Map<String, dynamic>> submitScore({
    required int examId,
    required String studentId,
    required double score,
    required String examSet,
    String? rawAnswersJson,
    String? base64Image,
  }) async {
    final url = Uri.parse('${ApiConfig.scoresEndpoint}?action=submit');
    final headers = await _getHeaders();
    final response = await http.post(
      url,
      headers: headers,
      body: {
        'exam_id': examId.toString(),
        'student_id': studentId,
        'score': score.toString(),
        'exam_set': examSet,
        'raw_answers': ?rawAnswersJson,
        'image': ?base64Image,
      },
    );

    return jsonDecode(response.body);
  }

  // 8. Get Scores List for Exam
  static Future<Map<String, dynamic>> getScoresList(int examId) async {
    final url = Uri.parse('${ApiConfig.scoresEndpoint}?action=list&exam_id=$examId');
    final headers = await _getHeaders();
    final response = await http.get(url, headers: headers);

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['status'] == 'success') {
        final List list = data['data'] ?? [];
        final scores = list.map((item) => ScoreModel.fromJson(item)).toList();
        return {
          'summary': data['summary'],
          'scores': scores,
        };
      }
    }
    throw Exception('Failed to fetch scores list');
  }

  // 9. Get Analytics Data
  static Future<Map<String, dynamic>> getAnalytics(int examId) async {
    final url = Uri.parse('${ApiConfig.analyticsEndpoint}?exam_id=$examId');
    final headers = await _getHeaders();
    final response = await http.get(url, headers: headers);

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['status'] == 'success') {
        return data['data'];
      }
    }
    throw Exception('Failed to fetch analytics');
  }
}
