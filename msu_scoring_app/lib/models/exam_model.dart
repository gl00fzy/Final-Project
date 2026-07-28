class ExamModel {
  final int examId;
  final int ownerId;
  final String? ownerName;
  final String examTitle;
  final String? examCode;
  final int questionCount;
  final Map<String, dynamic> answerKey;
  final int scannedCount;
  final String? createdAt;
  final bool isOwner;

  ExamModel({
    required this.examId,
    required this.ownerId,
    this.ownerName,
    required this.examTitle,
    this.examCode,
    required this.questionCount,
    required this.answerKey,
    required this.scannedCount,
    this.createdAt,
    this.isOwner = true,
  });

  factory ExamModel.fromJson(Map<String, dynamic> json) {
    Map<String, dynamic> parsedKey = {};
    if (json['answer_key'] != null) {
      if (json['answer_key'] is Map) {
        parsedKey = Map<String, dynamic>.from(json['answer_key']);
      } else if (json['answer_key'] is String) {
        // String json
        parsedKey = {};
      }
    }

    return ExamModel(
      examId: json['exam_id'] is int ? json['exam_id'] : int.parse(json['exam_id'].toString()),
      ownerId: json['owner_id'] is int ? json['owner_id'] : int.parse(json['owner_id'].toString()),
      ownerName: json['owner_name'],
      examTitle: json['exam_title'] ?? 'ไม่มีชื่อวิชา',
      examCode: json['exam_code'],
      questionCount: json['question_count'] is int ? json['question_count'] : int.parse((json['question_count'] ?? 50).toString()),
      answerKey: parsedKey,
      scannedCount: json['scanned_count'] is int ? json['scanned_count'] : int.parse((json['scanned_count'] ?? 0).toString()),
      createdAt: json['created_at'],
      isOwner: json['is_owner'] ?? true,
    );
  }
}
