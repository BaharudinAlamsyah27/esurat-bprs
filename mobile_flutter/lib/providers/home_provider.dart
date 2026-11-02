import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../models/incoming_letter.dart';
import '../models/my_disposition.dart';
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
      final responses = await Future.wait<Response<dynamic>>([
        _apiService.getIncomingLetters(page: 1, perPage: 5),
        _apiService.getOutgoingLetters(page: 1, perPage: 1),
        _apiService.getMyDispositions(page: 1, perPage: 50),
      ]);

      final incomingData = responses[0].data as Map<String, dynamic>;
      final outgoingData = responses[1].data as Map<String, dynamic>;
      final dispositionData = responses[2].data as Map<String, dynamic>;

      final recent = (incomingData['data'] as List<dynamic>)
          .map((json) => IncomingLetter.fromJson(json as Map<String, dynamic>))
          .take(5)
          .toList();

      final dispositions = (dispositionData['data'] as List<dynamic>)
          .map((json) => MyDisposition.fromJson(json as Map<String, dynamic>))
          .toList();

      final unread = dispositions.where((item) => !item.isRead).length;
      final dispositionsTotal =
          _totalFrom(dispositionData, dispositions.length);

      _summary = HomeSummary(
        incomingTotal: _totalFrom(incomingData, recent.length),
        outgoingTotal: _totalFrom(outgoingData, 0),
        unreadDispositions: unread,
        dispositionsTotal: dispositionsTotal,
        recentIncoming: recent,
        countsArePartial: dispositionsTotal > dispositions.length,
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

  /// Membaca `meta.total` dari respons paginasi Laravel.
  int _totalFrom(Map<String, dynamic> payload, int fallback) {
    final meta = payload['meta'];

    if (meta is Map && meta['total'] is int) {
      return meta['total'] as int;
    }

    return fallback;
  }
}
