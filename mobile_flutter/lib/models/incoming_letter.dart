class IncomingLetter {
  final int id;
  final String agendaNumber;
  final String letterNumber;
  final DateTime? letterDate;
  final DateTime? receivedAt;
  final String senderName;
  final String subject;
  final String? status;
  final Classification classification;
  final String? attachmentUrl;
  final bool hasDisposition;
  final DateTime createdAt;

  IncomingLetter({
    required this.id,
    required this.agendaNumber,
    required this.letterNumber,
    this.letterDate,
    this.receivedAt,
    required this.senderName,
    required this.subject,
    this.status,
    required this.classification,
    this.attachmentUrl,
    required this.hasDisposition,
    required this.createdAt,
  });

  factory IncomingLetter.fromJson(Map<String, dynamic> json) {
    return IncomingLetter(
      id: json['id'],
      agendaNumber: json['agenda_number'],
      letterNumber: json['letter_number'],
      letterDate: json['letter_date'] != null ? DateTime.parse(json['letter_date']) : null,
      receivedAt: json['received_at'] != null ? DateTime.parse(json['received_at']) : null,
      senderName: json['sender_name'],
      subject: json['subject'],
      status: json['status'],
      classification: Classification.fromJson(json['classification']),
      attachmentUrl: json['attachment_url'],
      hasDisposition: json['has_disposition'] ?? false,
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}

class Classification {
  final int? id;
  final String? code;
  final String? name;

  Classification({this.id, this.code, this.name});

  factory Classification.fromJson(Map<String, dynamic> json) {
    return Classification(
      id: json['id'],
      code: json['code'],
      name: json['name'],
    );
  }

  String get displayName => code != null && name != null ? '$code - $name' : 'Tanpa Klasifikasi';
}
