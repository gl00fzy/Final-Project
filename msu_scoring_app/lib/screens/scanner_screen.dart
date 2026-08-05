import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../config/theme.dart';
import '../models/exam_model.dart';
import '../services/api_service.dart';

class ScannerScreen extends StatefulWidget {
  final ExamModel exam;
  const ScannerScreen({super.key, required this.exam});

  @override
  State<ScannerScreen> createState() => _ScannerScreenState();
}

class _ScannerScreenState extends State<ScannerScreen> {
  CameraController? _cameraController;
  bool _isCameraInitialized = false;
  String _selectedExamSet = 'A';
  String _scanMode = 'student'; // 'student' or 'key'
  bool _isProcessing = false;
  Map<String, dynamic>? _lastResult;

  @override
  void initState() {
    super.initState();
    _initCamera();
  }

  Future<void> _initCamera() async {
    final cameras = await availableCameras();
    if (cameras.isNotEmpty) {
      // Choose back camera
      final backCamera = cameras.firstWhere(
        (c) => c.lensDirection == CameraLensDirection.back,
        orElse: () => cameras.first,
      );

      _cameraController = CameraController(
        backCamera,
        ResolutionPreset.high,
        enableAudio: false,
      );

      await _cameraController!.initialize();
      if (mounted) {
        setState(() {
          _isCameraInitialized = true;
        });
      }
    }
  }

  void _simulateCaptureScan() async {
    if (_isProcessing) return;
    setState(() => _isProcessing = true);

    // Simulate native camera scan processing
    await Future.delayed(const Duration(milliseconds: 600));

    final studentId =
        '6601${(1000000 + (DateTime.now().millisecondsSinceEpoch % 8999999)).toInt()}';
    final score = (widget.exam.questionCount * 0.85).roundToDouble();

    try {
      await ApiService.submitScore(
        examId: widget.exam.examId,
        studentId: studentId,
        score: score,
        examSet: _selectedExamSet,
      );

      setState(() {
        _lastResult = {'student_id': studentId, 'score': score};
        _isProcessing = false;
      });
    } catch (e) {
      setState(() => _isProcessing = false);
    }
  }

  @override
  void dispose() {
    _cameraController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          // 1. Live Native Camera Feed
          if (_isCameraInitialized && _cameraController != null)
            Positioned.fill(child: CameraPreview(_cameraController!))
          else
            const Center(
              child: CircularProgressIndicator(color: AppColors.gold),
            ),

          // 2. Viewfinder Overlay Reticle with Golden Corner Brackets
          Center(
            child: Container(
              width: MediaQuery.of(context).size.width * 0.82,
              height: MediaQuery.of(context).size.width * 0.82 * 1.414,
              decoration: BoxDecoration(
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.15),
                  width: 1.5,
                ),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Stack(
                children: [
                  // Corner brackets
                  _buildCornerBracket(
                    top: -2,
                    left: -2,
                    isTop: true,
                    isLeft: true,
                  ),
                  _buildCornerBracket(
                    top: -2,
                    right: -2,
                    isTop: true,
                    isLeft: false,
                  ),
                  _buildCornerBracket(
                    bottom: -2,
                    left: -2,
                    isTop: false,
                    isLeft: true,
                  ),
                  _buildCornerBracket(
                    bottom: -2,
                    right: -2,
                    isTop: false,
                    isLeft: false,
                  ),

                  // Helper Label
                  Align(
                    alignment: Alignment.bottomCenter,
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 16),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 8,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(100),
                          border: Border.all(
                            color: AppColors.gold.withValues(alpha: 0.4),
                          ),
                        ),
                        child: Text(
                          'เล็งกรอบให้อยู่ในหน้าจอ',
                          style: GoogleFonts.sarabun(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: AppColors.gold,
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // 3. Top HUD Bar (Back Button + Exam Set Picker)
          Positioned(
            top: 48,
            left: 16,
            right: 16,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                IconButton.filledTonal(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(
                    Icons.arrow_back_rounded,
                    color: Colors.white,
                  ),
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.black.withValues(alpha: 0.6),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.7),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.gold),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: _selectedExamSet,
                      dropdownColor: AppColors.navyCard,
                      style: GoogleFonts.sarabun(
                        fontWeight: FontWeight.bold,
                        color: AppColors.gold,
                      ),
                      items: ['A', 'B', 'C'].map((set) {
                        return DropdownMenuItem(
                          value: set,
                          child: Text('ชุดข้อสอบ $set'),
                        );
                      }).toList(),
                      onChanged: (val) {
                        if (val != null) setState(() => _selectedExamSet = val);
                      },
                    ),
                  ),
                ),
              ],
            ),
          ),

          // 4. Mode Toggle (Scan Student vs Scan Key)
          Positioned(
            top: 104,
            left: 0,
            right: 0,
            child: Center(
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.75),
                  borderRadius: BorderRadius.circular(100),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.2),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _buildModeBtn('สแกนนิสิต', 'student'),
                    _buildModeBtn('สแกนเฉลย', 'key'),
                  ],
                ),
              ),
            ),
          ),

          // 5. Capture Floating Button
          Positioned(
            bottom: 36,
            left: 0,
            right: 0,
            child: Center(
              child: GestureDetector(
                onTap: _simulateCaptureScan,
                child: Container(
                  width: 76,
                  height: 76,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: AppColors.gold,
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.gold.withValues(alpha: 0.5),
                        blurRadius: 20,
                        spreadRadius: 2,
                      ),
                    ],
                  ),
                  child: Center(
                    child: _isProcessing
                        ? const CircularProgressIndicator(
                            color: AppColors.navyBackground,
                          )
                        : const Icon(
                            Icons.camera_alt_rounded,
                            size: 36,
                            color: AppColors.navyBackground,
                          ),
                  ),
                ),
              ),
            ),
          ),

          // 6. Success Score Modal Overlay
          if (_lastResult != null)
            Positioned.fill(
              child: Container(
                color: Colors.black.withValues(alpha: 0.8),
                child: Center(
                  child: Container(
                    width: 320,
                    padding: const EdgeInsets.all(28),
                    decoration: BoxDecoration(
                      color: AppColors.navyCard,
                      borderRadius: BorderRadius.circular(28),
                      border: Border.all(color: AppColors.gold),
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Text('🎉', style: TextStyle(fontSize: 48)),
                        const SizedBox(height: 12),
                        Text(
                          'สแกนสำเร็จ!',
                          style: GoogleFonts.sarabun(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: AppColors.gold,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'รหัสนิสิต',
                          style: GoogleFonts.sarabun(
                            color: AppColors.textMuted,
                            fontSize: 13,
                          ),
                        ),
                        Text(
                          _lastResult!['student_id'],
                          style: GoogleFonts.outfit(
                            fontSize: 22,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'คะแนนที่ได้',
                          style: GoogleFonts.sarabun(
                            color: AppColors.textMuted,
                            fontSize: 13,
                          ),
                        ),
                        Text(
                          '${_lastResult!['score']}',
                          style: GoogleFonts.outfit(
                            fontSize: 48,
                            fontWeight: FontWeight.w900,
                            color: AppColors.gold,
                          ),
                        ),
                        const SizedBox(height: 24),
                        ElevatedButton(
                          onPressed: () => setState(() => _lastResult = null),
                          style: ElevatedButton.styleFrom(
                            minimumSize: const Size(double.infinity, 48),
                          ),
                          child: const Text('สแกนใบถัดไป'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildModeBtn(String text, String mode) {
    final isSelected = _scanMode == mode;
    return GestureDetector(
      onTap: () => setState(() => _scanMode = mode),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.gold : Colors.transparent,
          borderRadius: BorderRadius.circular(100),
        ),
        child: Text(
          text,
          style: GoogleFonts.sarabun(
            fontSize: 13,
            fontWeight: FontWeight.bold,
            color: isSelected ? AppColors.navyBackground : Colors.white70,
          ),
        ),
      ),
    );
  }

  Widget _buildCornerBracket({
    double? top,
    double? bottom,
    double? left,
    double? right,
    required bool isTop,
    required bool isLeft,
  }) {
    return Positioned(
      top: top,
      bottom: bottom,
      left: left,
      right: right,
      child: Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          border: Border(
            top: isTop
                ? const BorderSide(color: AppColors.gold, width: 4)
                : BorderSide.none,
            bottom: !isTop
                ? const BorderSide(color: AppColors.gold, width: 4)
                : BorderSide.none,
            left: isLeft
                ? const BorderSide(color: AppColors.gold, width: 4)
                : BorderSide.none,
            right: !isLeft
                ? const BorderSide(color: AppColors.gold, width: 4)
                : BorderSide.none,
          ),
        ),
      ),
    );
  }
}
