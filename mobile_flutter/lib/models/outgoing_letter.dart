import 'incoming_letter.dart';

class OutgoingLetter {
  final int id;
  final String agendaNumber;
  final String letterNumber;
  final DateTime? letterDate;
  final String subject;
  final String recipientName;
  final Classification classification;
  final String? attachmentUrl;
  final DateTime createdAt;

  OutgoingLetter({
    required this.id,
    required this.agendaNumber,
    required this.letterNumber,
    this.letterDate,
    required this.subject,
    required this.recipientName,
    required this.classification,
    this.attachmentUrl,
    required this.createdAt,
  });

  factory OutgoingLetter.fromJson(Map<String, dynamic> json) {
    return OutgoingLetter(
      id: json['id'],
      agendaNumber: json['agenda_number'],
      letterNumber: json['letter_number'],
      letterDate: json['letter_date'] != null
          ? DateTime.parse(json['letter_date'])
          : null,
      subject: json['subject'],
      recipientName: json['recipient'],
      classification: Classification.fromJson(json['classification']),
      attachmentUrl: json['attachment_url'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}
