import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/outgoing_letter.dart';
import '../providers/letter_provider.dart';
import '../theme/app_theme.dart';
import '../utils/format.dart';
import '../widgets/common_widgets.dart';
import 'pdf_viewer_screen.dart';

/// Daftar surat keluar (isi tab "Keluar" di menu Surat).
class OutgoingLetterView extends StatefulWidget {
  const OutgoingLetterView({super.key});

  @override
  State<OutgoingLetterView> createState() => _OutgoingLetterViewState();
}

class _OutgoingLetterViewState extends State<OutgoingLetterView> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<LetterProvider>(context, listen: false)
          .fetchOutgoingLetters(refresh: true);
    });

    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
          _scrollController.position.maxScrollExtent - 200) {
        Provider.of<LetterProvider>(context, listen: false)
            .fetchOutgoingLetters(search: _searchController.text);
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
        .fetchOutgoingLetters(search: _searchController.text, refresh: true);
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
            hintText: 'Cari nomor surat, penerima, perihal...',
            onSubmitted: (value) =>
                Provider.of<LetterProvider>(context, listen: false)
                    .fetchOutgoingLetters(search: value, refresh: true),
            onClear: () {
              _searchController.clear();
              _reload();
            },
          ),
        ),
        Expanded(
          child: Consumer<LetterProvider>(
            builder: (context, provider, _) {
              if (provider.isLoadingOutgoing &&
                  provider.outgoingLetters.isEmpty) {
                return const LoadingList();
              }

              if (provider.outgoingError != null &&
                  provider.outgoingLetters.isEmpty) {
                return OfflineState(
                  message: provider.outgoingError,
                  onRetry: _reload,
                );
              }

              if (provider.outgoingLetters.isEmpty) {
                return const EmptyState(
                  title: 'Belum ada surat keluar',
                  message:
                      'Surat yang dikirim keluar akan tampil di halaman ini.',
                  icon: Icons.send_outlined,
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
                  itemCount: provider.outgoingLetters.length +
                      (provider.hasMoreOutgoing ? 1 : 0),
                  separatorBuilder: (_, __) =>
                      const SizedBox(height: AppSpacing.gap),
                  itemBuilder: (context, index) {
                    if (index == provider.outgoingLetters.length) {
                      return const Padding(
                        padding: EdgeInsets.all(AppSpacing.gap),
                        child: Center(child: CircularProgressIndicator()),
                      );
                    }

                    return _OutgoingLetterCard(
                      letter: provider.outgoingLetters[index],
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

class _OutgoingLetterCard extends StatelessWidget {
  const _OutgoingLetterCard({required this.letter});

  final OutgoingLetter letter;

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
          title: 'Berkas Surat Keluar',
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SectionCard(
      onTap: () => _open(context),
      semanticLabel:
          '${letter.subject}, penerima ${letter.recipientName}, ${formatTanggalAtauStrip(letter.letterDate)}',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              StatusBadge(label: letter.agendaNumber),
              const Spacer(),
              Text(
                formatTanggalAtauStrip(letter.letterDate),
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
            'Penerima: ${letter.recipientName}',
            style: Theme.of(context).textTheme.bodyMedium,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: AppSpacing.gap),
          const Divider(),
          const SizedBox(height: AppSpacing.gap),
          MetaRow(
            icon: Icons.folder_open,
            text: letter.classification.displayName,
          ),
          const SizedBox(height: 4),
          Text(
            'No: ${letter.letterNumber}',
            style: Theme.of(context).textTheme.bodySmall,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
