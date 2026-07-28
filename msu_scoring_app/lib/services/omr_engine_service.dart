import 'dart:math';
import 'package:flutter/foundation.dart';
import 'package:image/image.dart' as img;

/// Result of an OMR Bubble Scan
class OmrScanResult {
  final Map<int, List<String>> detectedAnswers; // e.g. {1: ["A"], 2: ["B"]}
  final String? studentId;
  final double score;
  final bool isSuccess;

  OmrScanResult({
    required this.detectedAnswers,
    this.studentId,
    required this.score,
    required this.isSuccess,
  });
}

class OmrEngineService {
  /// Evaluates detected student answers against the answer key
  static double calculateScore({
    required Map<int, List<String>> studentAnswers,
    required Map<String, dynamic> fullAnswerKey,
    required String examSet,
  }) {
    double totalScore = 0.0;

    Map<String, dynamic> key = {};
    if (fullAnswerKey.containsKey('A')) {
      key = Map<String, dynamic>.from(fullAnswerKey[examSet] ?? {});
    } else {
      key = fullAnswerKey;
    }

    studentAnswers.forEach((qNum, answers) {
      final keyItem = key[qNum.toString()];
      if (keyItem == null) return;

      if (keyItem is String) {
        if (answers.contains(keyItem)) {
          totalScore += 1.0;
        }
      } else if (keyItem is Map) {
        final Map item = keyItem;
        if (item['ignore'] == true) return;

        final List correctAns = List.from(item['answers'] ?? []);
        final String logic = (item['logic'] ?? 'OR').toString().toUpperCase();
        final double points = (item['points'] as num? ?? 1.0).toDouble();
        final double penalty = (item['penalty'] as num? ?? 0.0).toDouble();

        bool isCorrect = false;
        if (logic == 'AND') {
          final sortedStudent = List<String>.from(answers)..sort();
          final sortedCorrect = List<String>.from(correctAns)..sort();
          if (sortedStudent.join() == sortedCorrect.join() && sortedCorrect.isNotEmpty) {
            isCorrect = true;
          }
        } else {
          for (var a in answers) {
            if (correctAns.contains(a)) {
              isCorrect = true;
              break;
            }
          }
        }

        if (isCorrect) {
          totalScore += points;
        } else {
          totalScore -= penalty;
        }
      }
    });

    return max(0.0, totalScore);
  }

  /// Process raw camera image frame to detect dark bubbles
  static Future<OmrScanResult> processFrame({
    required img.Image frame,
    required int questionCount,
    required Map<String, dynamic> answerKey,
    required String examSet,
  }) async {
    return compute((params) {
      final img.Image rawImg = params['frame'] as img.Image;
      final int qCount = params['questionCount'] as int;
      final Map<String, dynamic> key = params['answerKey'] as Map<String, dynamic>;
      final String set = params['examSet'] as String;

      // 1. Grayscale image
      final img.Image gray = img.grayscale(rawImg);
      
      // 2. Sample bubble darkness
      final Map<int, List<String>> detectedAnswers = {};
      final options = ['A', 'B', 'C', 'D', 'E'];

      for (int q = 1; q <= qCount; q++) {
        List<String> chosenOptions = [];
        for (int optIdx = 0; optIdx < 4; optIdx++) {
          final optLetter = options[optIdx];
          // Sample pixel brightness from gray image
          int sampleX = (gray.width * (0.2 + (optIdx * 0.15))).toInt();
          int sampleY = (gray.height * (q / (qCount + 2))).toInt();
          if (sampleX < gray.width && sampleY < gray.height) {
            final pixel = gray.getPixel(sampleX, sampleY);
            if (pixel.r < 100) {
              chosenOptions.add(optLetter);
            }
          }
        }
        if (chosenOptions.isNotEmpty) {
          detectedAnswers[q] = chosenOptions;
        }
      }

      final score = calculateScore(
        studentAnswers: detectedAnswers,
        fullAnswerKey: key,
        examSet: set,
      );

      return OmrScanResult(
        detectedAnswers: detectedAnswers,
        score: score,
        isSuccess: true,
      );
    }, {
      'frame': frame,
      'questionCount': questionCount,
      'answerKey': answerKey,
      'examSet': examSet,
    });
  }
}
