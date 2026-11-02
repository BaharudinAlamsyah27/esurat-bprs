import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../models/incoming_letter.dart';
import '../models/my_disposition.dart';
import '../models/outgoing_letter.dart';
import '../services/api_messages.dart';
import '../services/api_service.dart';

/// Data dan status muat untuk satu daftar (surat masuk/keluar/disposisi).
///
/// Setiap daftar punya status sendiri supaya tab Surat Masuk dan Surat Keluar
/// bisa memuat bersamaan tanpa saling menunggu.
class LetterProvider with ChangeNotifier {
  LetterProvider({ApiService? apiService})
      : _apiService = apiService ?? ApiService();

  final ApiService _apiService;

  final List<IncomingLetter> _incomingLetters = [];
  final List<OutgoingLetter> _outgoingLetters = [];
  final List<MyDisposition> _dispositions = [];

  bool _isLoadingIncoming = false;
  bool _isLoadingOutgoing = false;
  bool _isLoadingDispositions = false;

  String? _incomingError;
  String? _outgoingError;
  String? _dispositionsError;

  int _incomingPage = 1;
  int _outgoingPage = 1;
  int _dispositionPage = 1;

  bool _hasMoreIncoming = true;
  bool _hasMoreOutgoing = true;
  bool _hasMoreDisposition = true;

  List<IncomingLetter> get incomingLetters =>
      List.unmodifiable(_incomingLetters);
  List<OutgoingLetter> get outgoingLetters =>
      List.unmodifiable(_outgoingLetters);
  List<MyDisposition> get dispositions => List.unmodifiable(_dispositions);

  bool get isLoadingIncoming => _isLoadingIncoming;
  bool get isLoadingOutgoing => _isLoadingOutgoing;
  bool get isLoadingDispositions => _isLoadingDispositions;

  String? get incomingError => _incomingError;
  String? get outgoingError => _outgoingError;
  String? get dispositionsError => _dispositionsError;

  bool get hasMoreIncoming => _hasMoreIncoming;
  bool get hasMoreOutgoing => _hasMoreOutgoing;
  bool get hasMoreDisposition => _hasMoreDisposition;

  // ---------------------------------------------------------------- surat masuk

  Future<void> fetchIncomingLetters(
      {String? search, bool refresh = false}) async {
    if (refresh) {
      _incomingPage = 1;
      _incomingLetters.clear();
      _hasMoreIncoming = true;
    }

    if (!_hasMoreIncoming || _isLoadingIncoming) {
      return;
    }

    _isLoadingIncoming = true;
    _incomingError = null;
    notifyListeners();

    try {
      final response = await _apiService.getIncomingLetters(
        search: search,
        page: _incomingPage,
      );

      final data = response.data['data'] as List<dynamic>;
      final fetched = data
          .map((json) => IncomingLetter.fromJson(json as Map<String, dynamic>))
          .toList();

      if (fetched.isEmpty) {
        _hasMoreIncoming = false;
      } else {
        _incomingLetters.addAll(fetched);
        _incomingPage++;
      }
    } on DioException catch (error) {
      _incomingError = isConnectionProblem(error)
          ? kOfflineMessage
          : kServerErrorMessage('surat masuk');
    } catch (_) {
      _incomingError = kServerErrorMessage('surat masuk');
    }

    _isLoadingIncoming = false;
    notifyListeners();
  }

  // --------------------------------------------------------------- surat keluar

  Future<void> fetchOutgoingLetters(
      {String? search, bool refresh = false}) async {
    if (refresh) {
      _outgoingPage = 1;
      _outgoingLetters.clear();
      _hasMoreOutgoing = true;
    }

    if (!_hasMoreOutgoing || _isLoadingOutgoing) {
      return;
    }

    _isLoadingOutgoing = true;
    _outgoingError = null;
    notifyListeners();

    try {
      final response = await _apiService.getOutgoingLetters(
        search: search,
        page: _outgoingPage,
      );

      final data = response.data['data'] as List<dynamic>;
      final fetched = data
          .map((json) => OutgoingLetter.fromJson(json as Map<String, dynamic>))
          .toList();

      if (fetched.isEmpty) {
        _hasMoreOutgoing = false;
      } else {
        _outgoingLetters.addAll(fetched);
        _outgoingPage++;
      }
    } on DioException catch (error) {
      _outgoingError = isConnectionProblem(error)
          ? kOfflineMessage
          : kServerErrorMessage('surat keluar');
    } catch (_) {
      _outgoingError = kServerErrorMessage('surat keluar');
    }

    _isLoadingOutgoing = false;
    notifyListeners();
  }

  // ----------------------------------------------------------------- disposisi

  Future<void> fetchDispositions({bool refresh = false}) async {
    if (refresh) {
      _dispositionPage = 1;
      _dispositions.clear();
      _hasMoreDisposition = true;
    }

    if (!_hasMoreDisposition || _isLoadingDispositions) {
      return;
    }

    _isLoadingDispositions = true;
    _dispositionsError = null;
    notifyListeners();

    try {
      final response = await _apiService.getMyDispositions(
        page: _dispositionPage,
      );

      final data = response.data['data'] as List<dynamic>;
      final fetched = data
          .map((json) => MyDisposition.fromJson(json as Map<String, dynamic>))
          .toList();

      if (fetched.isEmpty) {
        _hasMoreDisposition = false;
      } else {
        _dispositions.addAll(fetched);
        _dispositionPage++;
      }
    } on DioException catch (error) {
      _dispositionsError = isConnectionProblem(error)
          ? kOfflineMessage
          : kServerErrorMessage('disposisi');
    } catch (_) {
      _dispositionsError = kServerErrorMessage('disposisi');
    }

    _isLoadingDispositions = false;
    notifyListeners();
  }

  Future<bool> markAsDone(int id, String note) async {
    try {
      await _apiService.markDispositionAsDone(id, note);
      await fetchDispositions(refresh: true);
      return true;
    } catch (_) {
      return false;
    }
  }
}
