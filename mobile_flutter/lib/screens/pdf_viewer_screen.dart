import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_pdfview/flutter_pdfview.dart';
import 'package:path_provider/path_provider.dart';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class PdfViewerScreen extends StatefulWidget {
  final String url;
  final String title;

  const PdfViewerScreen({super.key, required this.url, required this.title});

  @override
  State<PdfViewerScreen> createState() => _PdfViewerScreenState();
}

class _PdfViewerScreenState extends State<PdfViewerScreen> {
  String? localPath;
  bool isImage = false;
  bool isLoading = true;
  String? error;

  @override
  void initState() {
    super.initState();
    _downloadFile();
  }

  @override
  void dispose() {
    final path = localPath;
    if (path != null) {
      File(path).delete().ignore();
    }
    super.dispose();
  }

  Future<void> _downloadFile() async {
    try {
      const storage = FlutterSecureStorage();
      final token = await storage.read(key: 'auth_token');

      final dir = await getTemporaryDirectory();
      final file = File(
          '${dir.path}/temp_attachment_${DateTime.now().millisecondsSinceEpoch}');

      final dio = Dio();
      final response = await dio.download(
        widget.url,
        file.path,
        options: Options(
          headers: {
            'Authorization': 'Bearer $token',
            'Accept': 'application/pdf, image/jpeg, image/png',
          },
        ),
      );

      final contentType =
          (response.headers.value('content-type') ?? '').toLowerCase();

      if (mounted) {
        setState(() {
          localPath = file.path;
          isImage = contentType.startsWith('image/');
          isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          error = 'Gagal mengunduh berkas lampiran';
          isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (error != null) {
      return Center(child: Text(error!));
    }

    if (localPath != null) {
      if (isImage) {
        return InteractiveViewer(
          child: Center(
            child: Image.file(File(localPath!)),
          ),
        );
      }

      return PDFView(
        filePath: localPath,
        enableSwipe: true,
        swipeHorizontal: false,
        autoSpacing: false,
        pageFling: false,
        onError: (error) {
          setState(() {
            this.error = 'Terjadi kesalahan saat membuka berkas';
          });
        },
      );
    }

    return const Center(child: Text('Berkas tidak ditemukan'));
  }
}
