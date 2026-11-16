import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/cooperation_agreement.dart';
import '../providers/letter_provider.dart';
import '../theme/app_theme.dart';
import '../utils/format.dart';
import '../widgets/common_widgets.dart';
import 'pdf_viewer_screen.dart';

/// Daftar perjanjian kerjasama / PKS (isi tab "Kerjasama" di menu Surat).
class CooperationAgreementView extends StatefulWidget {
  const CooperationAgreementView({super.key});

  @override
  State<CooperationAgreementView> createState() =>
      _CooperationAgreementViewState();
}

class _CooperationAgreementViewState extends State<CooperationAgreementView> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<LetterProvider>(context, listen: false)
          .fetchCooperationAgreements(refresh: true);
    });

    _scrollController.addListener(() {
      if (_scrollController.position.pixels >=
          _scrollController.position.maxScrollExtent - 200) {
        Provider.of<LetterProvider>(context, listen: false)
            .fetchCooperationAgreements(search: _searchController.text);
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
        .fetchCooperationAgreements(
            search: _searchController.text, refresh: true);
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
            hintText: 'Cari no. PKS, mitra, NPWP, perihal, PIC...',
            onSubmitted: (value) =>
                Provider.of<LetterProvider>(context, listen: false)
                    .fetchCooperationAgreements(search: value, refresh: true),
            onClear: () {
              _searchController.clear();
              _reload();
            },
          ),
        ),
        Expanded(
          child: Consumer<LetterProvider>(
            builder: (context, provider, _) {
              if (provider.isLoadingCooperation &&
                  provider.cooperationAgreements.isEmpty) {
                return const LoadingList();
              }

              if (provider.cooperationError != null &&
                  provider.cooperationAgreements.isEmpty) {
                return OfflineState(
                  message: provider.cooperationError,
                  onRetry: _reload,
                );
              }

              if (provider.cooperationAgreements.isEmpty) {
                return const EmptyState(
                  title: 'Belum ada perjanjian kerjasama',
                  message:
                      'Data PKS dengan mitra akan tampil di halaman ini.',
                  icon: Icons.handshake_outlined,
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
                  itemCount: provider.cooperationAgreements.length +
                      (provider.hasMoreCooperation ? 1 : 0),
                  separatorBuilder: (_, __) =>
                      const SizedBox(height: AppSpacing.gap),
                  itemBuilder: (context, index) {
                    if (index == provider.cooperationAgreements.length) {
                      return const Padding(
                        padding: EdgeInsets.all(AppSpacing.gap),
                        child: Center(child: CircularProgressIndicator()),
                      );
                    }

                    return _CooperationAgreementCard(
                      agreement: provider.cooperationAgreements[index],
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

class _CooperationAgreementCard extends StatelessWidget {
  const _CooperationAgreementCard({required this.agreement});

  final CooperationAgreement agreement;

  void _openDetail(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.page,
            0,
            AppSpacing.page,
            AppSpacing.gapLarge,
          ),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  agreement.subject,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: AppSpacing.gap),
                _DetailRow(label: 'Nomor PKS BSS', value: agreement.bssPksNumber),
                _DetailRow(
                    label: 'Nomor PKS Mitra',
                    value: agreement.partnerPksNumber ?? '—'),
                _DetailRow(
                    label: 'Nomor NPWP Mitra',
                    value: agreement.partnerNpwp ?? '—'),
                _DetailRow(
                    label: 'Tanggal',
                    value: formatTanggalAtauStrip(agreement.agreementDate)),
                _DetailRow(label: 'Nama Mitra', value: agreement.partnerName),
                _DetailRow(label: 'PIC', value: agreement.picName),
                if (agreement.note != null && agreement.note!.isNotEmpty)
                  _DetailRow(label: 'Catatan', value: agreement.note!),
                const SizedBox(height: AppSpacing.gap),
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () {
                          Navigator.pop(sheetContext);
                          _openFile(context, agreement.pksFileUrl, 'Berkas PKS');
                        },
                        icon: const Icon(Icons.description_outlined),
                        label: const Text('Berkas PKS'),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.gapSmall),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          Navigator.pop(sheetContext);
                          _openFile(context, agreement.partnerLegalityUrl,
                              'Legalitas Mitra');
                        },
                        icon: const Icon(Icons.badge_outlined),
                        label: const Text('Legalitas'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _openFile(BuildContext context, String? url, String title) {
    if (url == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('$title belum diunggah')),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PdfViewerScreen(url: url, title: title),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SectionCard(
      onTap: () => _openDetail(context),
      semanticLabel:
          '${agreement.subject}, mitra ${agreement.partnerName}, ${formatTanggalAtauStrip(agreement.agreementDate)}',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Flexible(child: StatusBadge(label: agreement.bssPksNumber)),
              const SizedBox(width: AppSpacing.gapSmall),
              Text(
                formatTanggalAtauStrip(agreement.agreementDate),
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.gap),
          Text(
            agreement.subject,
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
            'Mitra: ${agreement.partnerName}',
            style: Theme.of(context).textTheme.bodyMedium,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: AppSpacing.gap),
          const Divider(),
          const SizedBox(height: AppSpacing.gap),
          MetaRow(
            icon: Icons.person_outline,
            text: 'PIC: ${agreement.picName}',
          ),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: 2),
          Text(
            value,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}
