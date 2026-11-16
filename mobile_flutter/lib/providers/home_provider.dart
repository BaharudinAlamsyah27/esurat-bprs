import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../models/incoming_letter.dart';
import '../services/api_messages.dart';
import '../services/api_service.dart';

/// Angka ringkasan untuk tab Beranda.
class HomeSummary {
  const HomeSummary({
    required this.incomingTotal,
    required this.outgoingTotal,
    required this.unreadDispositions,
    required this.dispositionsTotal,
    required this.recentIncoming,
    required this.countsArePartial,
  });

  final int incomingTotal;
  final int outgoingTotal;

  /// Jumlah disposisi untuk pengguna yang belum dibaca.
  ///
  /// TODO(disposisi): API belum menyediakan endpoint hitung belum dibaca,
  /// jadi angka ini dihitung dari maksimal 50 disposisi terbaru.
  final int unreadDispositions;
  final int dispositionsTotal;

  /// Maksimal 5 surat masuk terbaru.
  final List<IncomingLetter> recentIncoming;

  /// Benar bila jumlah disposisi melebihi data yang bisa dihitung (> 50).
  final bool countsArePartial;
}

/// Mengambil data ringkasan Beranda dari endpoint API yang sudah ada.
///
/// Tidak ada endpoint baru yang dipanggil di sini: semua angka memakai
/// `/incoming-letters`, `/outgoing-letters`, dan `/dispositions` dengan
/// parameter `per_page` yang memang didukung controller.
class HomeProvider extends ChangeNotifier {
  HomeProvider({ApiService? apiService})
      : _apiService = apiService ?? ApiService();

  final ApiService _apiService;

  HomeSummary? _summary;
  bool _isLoading = false;
  String? _error;

  HomeSummary? get summary => _summary;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get hasError => _error != null;

  Future<void> load({bool silent = false}) async {
    if (_isLoading) {
      return;
    }

    _isLoading = true;
    _error = null;
    if (!silent) {
      notifyListeners();
    }

    try {
      final response = await _apiService.getDashboardSummary();
      final payload = response.data['data'] as Map<String, dynamic>;

      final recent = (payload['recent_incoming'] as List<dynamic>? ?? [])
          .map((json) => IncomingLetter.fromJson(json as Map<String, dynamic>))
          .take(5)
          .toList();

      _summary = HomeSummary(
        incomingTotal: (payload['incoming_total'] as num?)?.toInt() ?? 0,
        outgoingTotal: (payload['outgoing_total'] as num?)?.toInt() ?? 0,
        unreadDispositions:
            (payload['unread_dispositions'] as num?)?.toInt() ?? 0,
        dispositionsTotal: (payload['dispositions_total'] as num?)?.toInt() ?? 0,
        recentIncoming: recent,
        countsArePartial: false,
      );
      _isLoading = false;
      notifyListeners();
    } on DioException catch (error) {
      _isLoading = false;
      _error = isConnectionProblem(error)
          ? kOfflineMessage
          : kServerErrorMessage('ringkasan beranda');
      notifyListeners();
    } catch (_) {
      _isLoading = false;
      _error = kServerErrorMessage('ringkasan beranda');
      notifyListeners();
    }
  }
}
