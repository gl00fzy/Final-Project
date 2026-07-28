class ScoreModel {
  final int? scoreId;
  final int examId;
  final String studentId;
  final String? studentName;
  final String examSet;
  final double score;
  final String? imagePath;
  final dynamic rawAnswers;
  final String? scannedAt;
  final String? scannedByName;

  ScoreModel({
    this.scoreId,
    required this.examId,
    required this.studentId,
    this.studentName,
    required this.examSet,
    required this.score,
    this.imagePath,
    this.rawAnswers,
    this.scannedAt,
    this.scannedByName,
  });

  factory ScoreModel.fromJson(Map<String, dynamic> json) {
    return ScoreModel(
      scoreId: json['score_id'] != null ? (json['score_id'] is int ? json['score_id'] : int.parse(json['score_id'].toString())) : null,
      examId: json['exam_id'] is int ? json['exam_id'] : int.parse(json['exam_id'].toString()),
      studentId: json['student_id'] ?? '',
      studentName: json['student_name'],
      examSet: json['exam_set'] ?? 'A',
      score: (json['score'] as num).toDouble(),
      imagePath: json['image_path'],
      rawAnswers: json['raw_answers'],
      scannedAt: json['scanned_at'],
      scannedByName: json['scanned_by'],
    );
  }
}
