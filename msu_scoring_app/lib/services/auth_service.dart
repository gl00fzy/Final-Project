import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';

class AuthService {
  static const String _keyToken = 'jwt_token';
  static const String _keyUser = 'user_profile';

  // Save Token & User profile
  static Future<void> saveAuthData(String token, UserModel user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyToken, token);
    await prefs.setString(_keyUser, jsonEncode(user.toJson()));
  }

  // Get Saved Token
  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyToken);
  }

  // Get Saved User Profile
  static Future<UserModel?> getUserProfile() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString(_keyUser);
    if (userJson != null) {
      return UserModel.fromJson(jsonDecode(userJson));
    }
    return null;
  }

  // Clear Session (Logout)
  static Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_keyToken);
    await prefs.remove(_keyUser);
  }

  // Check if logged in
  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }
}
