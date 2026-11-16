class CooperationAgreement {
  final int id;
  final String bssPksNumber;
  final String? partnerPksNumber;
  final String? partnerNpwp;
  final DateTime? agreementDate;
  final String partnerName;
  final String subject;
  final String picName;
  final String? note;
  final String? pksFileUrl;
  final String? partnerLegalityUrl;

  CooperationAgreement({
    required this.id,
    required this.bssPksNumber,
    this.partnerPksNumber,
    this.partnerNpwp,
    this.agreementDate,
    required this.partnerName,
    required this.subject,
    required this.picName,
    this.note,
    this.pksFileUrl,
    this.partnerLegalityUrl,
  });

  factory CooperationAgreement.fromJson(Map<String, dynamic> json) {
    return CooperationAgreement(
      id: json['id'],
      bssPksNumber: json['bss_pks_number'] ?? '',
      partnerPksNumber: json['partner_pks_number'],
      partnerNpwp: json['partner_npwp'],
      agreementDate: json['agreement_date'] != null
          ? DateTime.tryParse(json['agreement_date'])
          : null,
      partnerName: json['partner_name'] ?? '',
      subject: json['subject'] ?? '',
      picName: json['pic_name'] ?? '',
      note: json['note'],
      pksFileUrl: json['pks_file_url'],
      partnerLegalityUrl: json['partner_legality_url'],
    );
  }
}
