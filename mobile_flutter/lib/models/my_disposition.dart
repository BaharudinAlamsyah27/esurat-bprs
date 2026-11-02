class MyDisposition {
  final int id;
  final int dispositionId;
  final DateTime? readAt;
  final DateTime? doneAt;
  final String? doneNote;
  final String note;
  final String natureLabel;
  final List<String> instructions;
  final DateTime? dispositionDate;
  final String senderName;
  final DispositionLetter incomingLetter;
  final DateTime createdAt;

  MyDisposition({
    required this.id,
    required this.dispositionId,
    this.readAt,
    this.doneAt,
    this.doneNote,
    required this.note,
    required this.natureLabel,
    required this.instructions,
    this.dispositionDate,
    required this.senderName,
    required this.incomingLetter,
    required this.createdAt,
  });

  bool get isRead => readAt != null;
  bool get isDone => doneAt != null;

  factory MyDisposition.fromJson(Map<String, dynamic> json) {
    final disposition = json['disposition'] as Map<String, dynamic>;
    final rawInstructions = disposition['instructions'];

    return MyDisposition(
      id: json['id'],
      dispositionId: json['disposition_id'],
      readAt: json['read_at'] != null ? DateTime.parse(json['read_at']) : null,
      doneAt: json['done_at'] != null ? DateTime.parse(json['done_at']) : null,
      doneNote: json['done_note'],
      note: disposition['note'] as String? ?? '',
      natureLabel: disposition['nature_label'] as String? ?? '—',
      instructions: rawInstructions is List
          ? rawInstructions.map((value) => value.toString()).toList()
          : const [],
      dispositionDate: disposition['disposition_date'] is String
          ? DateTime.tryParse(disposition['disposition_date'] as String)
          : null,
      senderName: disposition['sender'] as String? ?? '—',
      incomingLetter: DispositionLetter.fromJson(
        disposition['incoming_letter'] as Map<String, dynamic>,
      ),
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}

class DispositionLetter {
  final int id;
  final String letterNumber;
  final String subject;
  final String senderName;
  final String? attachmentUrl;

  DispositionLetter({
    required this.id,
    required this.letterNumber,
    required this.subject,
    required this.senderName,
    this.attachmentUrl,
  });

  factory DispositionLetter.fromJson(Map<String, dynamic> json) {
    return DispositionLetter(
      id: json['id'] as int? ?? 0,
      letterNumber: json['letter_number'] as String? ?? '—',
      subject: json['subject'] as String? ?? '—',
      senderName: json['sender_name'] as String? ?? '—',
      attachmentUrl: json['attachment_url'] as String?,
    );
  }
}
