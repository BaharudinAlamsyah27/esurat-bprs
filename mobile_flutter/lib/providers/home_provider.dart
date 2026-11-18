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
    this.cooperationTotal = 0,
    this.countsArePartial = false,
  });

  final int incomingTotal;
  final int outgoingTotal;
  final int cooperationTotal;

  /// Jumlah disposisi untuk pengguna yang belum dibaca.
  final int unreadDispositions;
  final int dispositionsTotal;

  /// Maksimal 5 surat masuk terbaru.
  final List<IncomingLetter> recentIncoming;

  /// Dipertahankan untuk kompatibilitas pengujian widget.
  final bool countsArePartial;
}

/// Mengambil data ringkasan Beranda langsung dari endpoint `/api/dashboard/summary`.
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
        cooperationTotal: (payload['cooperation_total'] as num?)?.toInt() ?? 0,
        unreadDispositions:
            (payload['unread_dispositions'] as num?)?.toInt() ?? 0,
        dispositionsTotal: (payload['dispositions_total'] as num?)?.toInt() ?? 0,
        recentIncoming: recent,
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
