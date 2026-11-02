import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/my_disposition.dart';
import '../providers/letter_provider.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../utils/format.dart';
import '../widgets/common_widgets.dart';
import 'pdf_viewer_screen.dart';

/// Daftar disposisi untuk pengguna (isi tab "Disposisi").
class DispositionView extends StatefulWidget {
  const DispositionView({super.key, this.onOpen});

  final ValueChanged<int>? onOpen;

  @override
  State<DispositionView> createState() => _DispositionViewState();
}

class _DispositionViewState extends State<DispositionView> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<LetterProvider>(context, listen: false)
          .fetchDispositions(refresh: true);
    });

    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
          _scrollController.position.maxScrollExtent - 200) {
        Provider.of<LetterProvider>(context, listen: false).fetchDispositions();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _reload() {
    return Provider.of<LetterProvider>(context, listen: false)
        .fetchDispositions(refresh: true);
  }

  void _showDoneDialog(MyDisposition disposition) {
    final noteController = TextEditingController();

    showDialog<void>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Tandai selesai'),
        content: TextField(
          controller: noteController,
          maxLines: 3,
          autofocus: true,
          decoration: const InputDecoration(
            hintText: 'Tulis catatan penyelesaian...',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () async {
              if (noteController.text.trim().isEmpty) {
                return;
              }

              // Simpan dulu objek navigasi & messenger agar tidak memakai
              // context setelah await (bisa error bila layar sudah ditutup).
              final navigator = Navigator.of(dialogContext);
              final messenger = ScaffoldMessenger.of(context);
              final provider =
                  Provider.of<LetterProvider>(context, listen: false);

              final success = await provider.markAsDone(
                  disposition.id, noteController.text);
              if (!success) {
                return;
              }

              navigator.pop();
              messenger.showSnackBar(
                const SnackBar(
                    content: Text('Disposisi berhasil diselesaikan')),
              );
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<LetterProvider>(
      builder: (context, provider, _) {
        if (provider.isLoadingDispositions && provider.dispositions.isEmpty) {
          return const LoadingList();
        }

        if (provider.dispositionsError != null &&
            provider.dispositions.isEmpty) {
          return OfflineState(
            message: provider.dispositionsError,
            onRetry: _reload,
          );
        }

        if (provider.dispositions.isEmpty) {
          return const EmptyState(
            title: 'Belum ada disposisi',
            message: 'Disposisi dari pimpinan akan tampil di halaman ini.',
            icon: Icons.assignment_outlined,
          );
        }

        return RefreshIndicator(
          onRefresh: _reload,
          color: AppColors.primary,
          child: ListView.separated(
            controller: _scrollController,
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.page,
              AppSpacing.gapSmall,
              AppSpacing.page,
              AppSpacing.gapLarge,
            ),
            itemCount: provider.dispositions.length +
                (provider.hasMoreDisposition ? 1 : 0),
            separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.gap),
            itemBuilder: (context, index) {
              if (index == provider.dispositions.length) {
                return const Padding(
                  padding: EdgeInsets.all(AppSpacing.gap),
                  child: Center(child: CircularProgressIndicator()),
                );
              }

              final disposition = provider.dispositions[index];

              return _DispositionCard(
                disposition: disposition,
                onDone: () => _showDoneDialog(disposition),
                onOpen: widget.onOpen == null
                    ? null
                    : () => widget.onOpen!(disposition.id),
              );
            },
          ),
        );
      },
    );
  }
}

class _DispositionCard extends StatelessWidget {
  const _DispositionCard({
    required this.disposition,
    required this.onDone,
    this.onOpen,
  });

  final MyDisposition disposition;
  final VoidCallback onDone;
  final VoidCallback? onOpen;

  void _openLetter(BuildContext context) {
    final url = disposition.incomingLetter.attachmentUrl;

    if (url == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Surat ini tidak memiliki lampiran berkas')),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PdfViewerScreen(url: url, title: 'Berkas Surat'),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final belumDibaca = !disposition.isRead;

    return SectionCard(
      padding: const EdgeInsets.all(AppSpacing.page),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              if (belumDibaca)
                const StatusBadge(label: 'Baru', tone: BadgeTone.danger),
              if (belumDibaca) const SizedBox(width: AppSpacing.gapSmall),
              Expanded(
                child: Text(
                  'Dari ${disposition.senderName}',
                  style: Theme.of(context).textTheme.bodySmall,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Text(
                formatTanggal(disposition.createdAt),
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.gap),
          Text(
            disposition.incomingLetter.subject,
            style: const TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
              height: 1.3,
            ),
          ),
          if (onOpen != null) ...[
            const SizedBox(height: AppSpacing.gapSmall),
            Align(
              alignment: Alignment.centerLeft,
              child: TextButton.icon(
                onPressed: onOpen,
                icon: const Icon(Icons.open_in_new),
                label: const Text('Buka rincian disposisi'),
              ),
            ),
          ],
          const SizedBox(height: AppSpacing.gap),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(AppSpacing.gap),
            decoration: BoxDecoration(
              color: AppColors.background,
              borderRadius: BorderRadius.circular(AppSpacing.radiusSmall),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Instruksi',
                    style: Theme.of(context).textTheme.labelMedium),
                const SizedBox(height: 4),
                Text(disposition.note,
                    style: Theme.of(context).textTheme.bodyLarge),
              ],
            ),
          ),
          if (disposition.isDone) ...[
            const SizedBox(height: AppSpacing.gap),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(AppSpacing.gap),
              decoration: BoxDecoration(
                color: AppColors.primarySoft,
                borderRadius: BorderRadius.circular(AppSpacing.radiusSmall),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Catatan penyelesaian',
                    style: Theme.of(context).textTheme.labelMedium?.copyWith(
                          color: AppColors.primary,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    disposition.doneNote ?? '-',
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: AppSpacing.page),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => _openLetter(context),
                  child: const Text('Lihat surat'),
                ),
              ),
              const SizedBox(width: AppSpacing.gap),
              Expanded(
                child: disposition.isDone
                    ? const SizedBox(
                        height: AppSpacing.minTouch,
                        child: StatusBadge(
                          label: 'Selesai',
                          tone: BadgeTone.primary,
                        ),
                      )
                    : FilledButton(
                        onPressed: onDone,
                        child: const Text('Selesai'),
                      ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

/// Rincian untuk tautan dari notifikasi; ID selalu dimuat ulang dari API
/// sehingga notifikasi lama tidak membuka data yang sudah tidak berhak dilihat.
class DispositionDetailScreen extends StatefulWidget {
  const DispositionDetailScreen({super.key, required this.id});

  final int id;

  @override
  State<DispositionDetailScreen> createState() =>
      _DispositionDetailScreenState();
}

class _DispositionDetailScreenState extends State<DispositionDetailScreen> {
  late Future<MyDisposition> _detail;

  @override
  void initState() {
    super.initState();
    _detail = _load();
  }

  Future<MyDisposition> _load() async {
    final response = await ApiService().getDispositionDetail(widget.id);
    return MyDisposition.fromJson(
      response.data['data'] as Map<String, dynamic>,
    );
  }

  Future<void> _markDone(MyDisposition disposition) async {
    final controller = TextEditingController();
    final note = await showDialog<String>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Tandai disposisi selesai'),
        content: TextField(
          controller: controller,
          autofocus: true,
          maxLines: 3,
          decoration: const InputDecoration(
            labelText: 'Catatan penyelesaian',
            hintText: 'Contoh: sudah ditindaklanjuti',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () {
              final value = controller.text.trim();
              if (value.isNotEmpty) Navigator.pop(dialogContext, value);
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );

    if (note == null || !mounted) return;
    try {
      await ApiService().markDispositionAsDone(disposition.id, note);
      if (!mounted) return;
      setState(() => _detail = _load());
      Provider.of<LetterProvider>(context, listen: false)
          .fetchDispositions(refresh: true);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Disposisi berhasil diselesaikan.')),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Belum berhasil menyimpan. Periksa jaringan lalu coba lagi.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Rincian disposisi')),
      body: FutureBuilder<MyDisposition>(
        future: _detail,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError || !snapshot.hasData) {
            return OfflineState(
              message: 'Rincian disposisi tidak dapat dimuat.',
              onRetry: () => setState(() => _detail = _load()),
            );
          }

          final item = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.all(AppSpacing.page),
            children: [
              SectionCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item.incomingLetter.subject,
                        style: Theme.of(context).textTheme.titleLarge),
                    const SizedBox(height: AppSpacing.gap),
                    _DetailLine(
                      label: 'Nomor surat',
                      value: item.incomingLetter.letterNumber,
                    ),
                    _DetailLine(
                      label: 'Pengirim surat',
                      value: item.incomingLetter.senderName,
                    ),
                    _DetailLine(
                      label: 'Diteruskan oleh',
                      value: item.senderName,
                    ),
                    _DetailLine(
                      label: 'Tanggal disposisi',
                      value: formatTanggal(item.dispositionDate ?? item.createdAt),
                    ),
                    _DetailLine(
                      label: 'Sifat',
                      value: item.natureLabel,
                    ),
                    _DetailLine(
                      label: 'Instruksi',
                      value: item.instructions.isEmpty
                          ? '—'
                          : item.instructions.join(', '),
                    ),
                    if (item.note.isNotEmpty)
                      _DetailLine(label: 'Catatan', value: item.note),
                    if (item.isDone)
                      _DetailLine(
                        label: 'Catatan selesai',
                        value: item.doneNote ?? '—',
                      ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.gap),
              if (item.incomingLetter.attachmentUrl != null)
                OutlinedButton.icon(
                  onPressed: () => Navigator.push<void>(
                    context,
                    MaterialPageRoute<void>(
                      builder: (_) => PdfViewerScreen(
                        url: item.incomingLetter.attachmentUrl!,
                        title: 'Berkas Surat',
                      ),
                    ),
                  ),
                  icon: const Icon(Icons.picture_as_pdf_outlined),
                  label: const Text('Buka lampiran surat'),
                ),
              if (!item.isDone) ...[
                const SizedBox(height: AppSpacing.gapSmall),
                FilledButton.icon(
                  onPressed: () => _markDone(item),
                  icon: const Icon(Icons.check_circle_outline),
                  label: const Text('Tandai selesai'),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _DetailLine extends StatelessWidget {
  const _DetailLine({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(top: AppSpacing.gap),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: Theme.of(context).textTheme.labelMedium),
            const SizedBox(height: 3),
            Text(value.isEmpty ? '—' : value,
                style: Theme.of(context).textTheme.bodyLarge),
          ],
        ),
      );
}
