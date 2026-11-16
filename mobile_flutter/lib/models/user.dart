class User {
  final int id;
  final String name;
  final String username;
  final String level;
  final String? positionTitle;
  final bool pushEnabled;

  bool get isAdmin => level == 'admin' || level == 'superadmin';

  User({
    required this.id,
    required this.name,
    required this.username,
    required this.level,
    this.positionTitle,
    this.pushEnabled = true,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      username: json['username'],
      level: json['level'],
      positionTitle: json['position_title'],
      pushEnabled: json['push_enabled'] != false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'username': username,
      'level': level,
      'position_title': positionTitle,
      'push_enabled': pushEnabled,
    };
  }
}
