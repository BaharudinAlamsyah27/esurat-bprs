import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/incoming_letter.dart';
import '../providers/letter_provider.dart';
import '../theme/app_theme.dart';
import '../utils/format.dart';
import '../widgets/common_widgets.dart';
import 'pdf_viewer_screen.dart';

/// Daftar surat masuk (isi tab "Masuk" di menu Surat).
class IncomingLetterView extends StatefulWidget {
  const IncomingLetterView({super.key});

  @override
  State<IncomingLetterView> createState() => _IncomingLetterViewState();
}

class _IncomingLetterViewState extends State<IncomingLetterView> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<LetterProvider>(context, listen: false)
          .fetchIncomingLetters(refresh: true);
    });

    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
          _scrollController.position.maxScrollExtent - 200) {
        Provider.of<LetterProvider>(context, listen: false)
            .fetchIncomingLetters(search: _searchController.text);
      }
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _reload() {
    return Provider.of<LetterProvider>(context, listen: false)
        .fetchIncomingLetters(search: _searchController.text, refresh: true);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.page,
            AppSpacing.gapSmall,
            AppSpacing.page,
            AppSpacing.gap,
          ),
          child: SearchField(
            controller: _searchController,
            hintText: 'Cari nomor surat, pengirim, perihal...',
            onSubmitted: (value) =>
                Provider.of<LetterProvider>(context, listen: false)
                    .fetchIncomingLetters(search: value, refresh: true),
            onClear: () {
              _searchController.clear();
              _reload();
            },
          ),
        ),
        Expanded(
          child: Consumer<LetterProvider>(
            builder: (context, provider, _) {
              if (provider.isLoadingIncoming &&
                  provider.incomingLetters.isEmpty) {
                return const LoadingList();
              }

              if (provider.incomingError != null &&
                  provider.incomingLetters.isEmpty) {
                return OfflineState(
                  message: provider.incomingError,
                  onRetry: _reload,
                );
              }

              if (provider.incomingLetters.isEmpty) {
                return const EmptyState(
                  title: 'Belum ada surat masuk',
                  message:
                      'Surat yang dicatat sekretariat akan tampil di halaman ini.',
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
                    0,
                    AppSpacing.page,
                    AppSpacing.gapLarge,
                  ),
                  itemCount: provider.incomingLetters.length +
                      (provider.hasMoreIncoming ? 1 : 0),
                  separatorBuilder: (_, __) =>
                      const SizedBox(height: AppSpacing.gap),
                  itemBuilder: (context, index) {
                    if (index == provider.incomingLetters.length) {
                      return const Padding(
                        padding: EdgeInsets.all(AppSpacing.gap),
                        child: Center(child: CircularProgressIndicator()),
                      );
                    }

                    return _IncomingLetterCard(
                      letter: provider.incomingLetters[index],
                    );
                  },
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _IncomingLetterCard extends StatelessWidget {
  const _IncomingLetterCard({required this.letter});

  final IncomingLetter letter;

  void _open(BuildContext context) {
    if (letter.attachmentUrl == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Surat ini tidak memiliki lampiran berkas')),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PdfViewerScreen(
          url: letter.attachmentUrl!,
          title: 'Berkas Surat',
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final baru = isSuratBaru(letter.receivedAt);

    return SectionCard(
      onTap: () => _open(context),
      semanticLabel:
          '${letter.subject}, dari ${letter.senderName}, ${formatTanggalAtauStrip(letter.receivedAt)}',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              StatusBadge(label: letter.agendaNumber),
              const SizedBox(width: AppSpacing.gapSmall),
              if (baru)
                const StatusBadge(label: 'Baru', tone: BadgeTone.primary),
              const Spacer(),
              Text(
                formatTanggalAtauStrip(letter.receivedAt),
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.gap),
          Text(
            letter.subject,
            style: const TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
              height: 1.3,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 4),
          Text(
            'Dari ${letter.senderName}',
            style: Theme.of(context).textTheme.bodyMedium,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: AppSpacing.gap),
          const Divider(),
          const SizedBox(height: AppSpacing.gap),
          MetaRow(
            icon: Icons.folder_open,
            text: letter.classification.displayName,
            trailing: letter.hasDisposition
                ? const StatusBadge(label: 'Disposisi')
                : null,
          ),
        ],
      ),
    );
  }
}
