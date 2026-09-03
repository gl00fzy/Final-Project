class ApiConfig {
  // Base URL pointing to the PHP server backend
  // For local development on Android emulator use http://10.0.2.2/msuscore (or host IP)
  // For production or LAN testing, change to server URL e.g. https://score.msu.ac.th
  static const String baseUrl = 'https://score.msu.ac.th';
  
  // API Endpoints v2
  static const String authEndpoint = '$baseUrl/api/v2/auth.php';
  static const String examsEndpoint = '$baseUrl/api/v2/exams.php';
  static const String scoresEndpoint = '$baseUrl/api/v2/scores.php';
  static const String analyticsEndpoint = '$baseUrl/api/v2/analytics.php';
}
