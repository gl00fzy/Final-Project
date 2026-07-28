class UserModel {
  final int userId;
  final String username;
  final String name;
  final String? email;
  final String role;

  UserModel({
    required this.userId,
    required this.username,
    required this.name,
    this.email,
    required this.role,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      userId: json['user_id'] is int ? json['user_id'] : int.parse(json['user_id'].toString()),
      username: json['username'] ?? '',
      name: json['name'] ?? '',
      email: json['email'],
      role: json['role'] ?? 'user',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'user_id': userId,
      'username': username,
      'name': name,
      'email': email,
      'role': role,
    };
  }
}
