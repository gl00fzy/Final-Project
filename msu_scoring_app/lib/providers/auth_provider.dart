import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/user_model.dart';
import '../services/auth_service.dart';
import '../services/api_service.dart';

class AuthState {
  final bool isLoading;
  final bool isAuthenticated;
  final UserModel? user;
  final String? errorMessage;

  AuthState({
    this.isLoading = false,
    this.isAuthenticated = false,
    this.user,
    this.errorMessage,
  });

  AuthState copyWith({
    bool? isLoading,
    bool? isAuthenticated,
    UserModel? user,
    String? errorMessage,
  }) {
    return AuthState(
      isLoading: isLoading ?? this.isLoading,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      user: user ?? this.user,
      errorMessage: errorMessage,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier() : super(AuthState(isLoading: true)) {
    checkAuthStatus();
  }

  Future<void> checkAuthStatus() async {
    state = state.copyWith(isLoading: true);
    final isLoggedIn = await AuthService.isLoggedIn();
    if (isLoggedIn) {
      final user = await AuthService.getUserProfile();
      state = state.copyWith(
        isLoading: false,
        isAuthenticated: true,
        user: user,
      );
    } else {
      state = state.copyWith(
        isLoading: false,
        isAuthenticated: false,
        user: null,
      );
    }
  }

  Future<bool> login(String username, String password) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final res = await ApiService.login(username, password);
      if (res['status'] == 'success') {
        final user = UserModel.fromJson(res['user']);
        state = state.copyWith(
          isLoading: false,
          isAuthenticated: true,
          user: user,
        );
        return true;
      } else {
        state = state.copyWith(
          isLoading: false,
          errorMessage: res['message'] ?? 'เข้าสู่ระบบไม่สำเร็จ',
        );
        return false;
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์',
      );
      return false;
    }
  }

  Future<void> logout() async {
    await AuthService.logout();
    state = AuthState(isAuthenticated: false);
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier();
});
