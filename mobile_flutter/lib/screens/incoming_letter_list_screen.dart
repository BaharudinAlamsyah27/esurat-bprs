import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../models/incoming_letter.dart';
import '../providers/auth_provider.dart';
import '../providers/letter_provider.dart';
import '../services/api_service.dart';
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
                      onDispositionSaved: _reload,
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
  const _IncomingLetterCard({
    required this.letter,
    required this.onDispositionSaved,
  });

  final IncomingLetter letter;
  final Future<void> Function() onDispositionSaved;

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

  void _openDispositionModal(BuildContext context) {
    showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => _DispositionFormSheet(letter: letter),
    ).then((saved) {
      if (saved == true) {
        onDispositionSaved();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final baru = isSuratBaru(letter.receivedAt);
    final isAdmin = Provider.of<AuthProvider>(context).user?.isAdmin ?? false;

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
          if (isAdmin) ...[
            const SizedBox(height: AppSpacing.gapSmall),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => _openDispositionModal(context),
                icon: const Icon(Icons.assignment_add),
                label: Text(
                  letter.hasDisposition
                      ? 'Ubah Disposisi'
                      : 'Buat Disposisi',
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _DispositionFormSheet extends StatefulWidget {
  const _DispositionFormSheet({required this.letter});

  final IncomingLetter letter;

  @override
  State<_DispositionFormSheet> createState() => _DispositionFormSheetState();
}

class _DispositionFormSheetState extends State<_DispositionFormSheet> {
  final _noteController = TextEditingController();
  final _instructionOtherController = TextEditingController();
  bool _loadingOptions = true;
  bool _submitting = false;
  String? _error;

  List<Map<String, dynamic>> _natures = [];
  List<Map<String, dynamic>> _instructions = [];
  List<Map<String, dynamic>> _recipients = [];

  String? _selectedNature;
  final Set<int> _selectedRecipientIds = {};
  final Set<String> _selectedInstructions = {};

  @override
  void initState() {
    super.initState();
    final existing = widget.letter.disposition;
    if (existing != null) {
      _selectedNature = existing.nature;
      _selectedRecipientIds.addAll(existing.recipientUserIds);
      _selectedInstructions.addAll(existing.instructions);
      _instructionOtherController.text = existing.instructionOther ?? '';
      _noteController.text = existing.note ?? '';
    }
    _loadOptions();
  }

  @override
  void dispose() {
    _noteController.dispose();
    _instructionOtherController.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    try {
      final response = await ApiService().getDispositionOptions();
      final data = response.data['data'] as Map<String, dynamic>;
      setState(() {
        _natures = List<Map<String, dynamic>>.from(data['natures'] ?? []);
        _instructions =
            List<Map<String, dynamic>>.from(data['instructions'] ?? []);
        _recipients =
            List<Map<String, dynamic>>.from(data['recipients'] ?? []);
        if (_selectedNature == null && _natures.isNotEmpty) {
          _selectedNature = _natures.first['value'] as String?;
        }
        _loadingOptions = false;
      });
    } catch (_) {
      setState(() {
        _error = 'Gagal memuat pilihan disposisi.';
        _loadingOptions = false;
      });
    }
  }

  Future<void> _submit() async {
    if (_selectedNature == null ||
        _selectedRecipientIds.isEmpty ||
        _selectedInstructions.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pilih sifat surat, minimal 1 penerima, dan instruksi.'),
        ),
      );
      return;
    }

    if (_selectedInstructions.contains('lainnya') &&
        _instructionOtherController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Tuliskan instruksi lainnya terlebih dahulu.'),
        ),
      );
      return;
    }

    setState(() => _submitting = true);
    try {
      await ApiService().saveDispositionForLetter(widget.letter.id, {
        'nature': _selectedNature,
        'recipient_user_ids': _selectedRecipientIds.toList(),
        'instructions': _selectedInstructions.toList(),
        'instruction_other': _selectedInstructions.contains('lainnya')
            ? _instructionOtherController.text.trim()
            : null,
        'note': _noteController.text.trim(),
        'disposition_date': DateFormat('yyyy-MM-dd').format(DateTime.now()),
      });

      if (!mounted) return;
      Navigator.pop(context, true);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Disposisi berhasil disimpan.')),
      );
    } catch (_) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Gagal menyimpan disposisi.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: EdgeInsets.fromLTRB(
          AppSpacing.page,
          0,
          AppSpacing.page,
          MediaQuery.of(context).viewInsets.bottom + AppSpacing.gapLarge,
        ),
        child: _loadingOptions
            ? const SizedBox(
                height: 180,
                child: Center(child: CircularProgressIndicator()),
              )
            : _error != null
                ? Padding(
                    padding: const EdgeInsets.symmetric(vertical: 24),
                    child: Text(_error!),
                  )
                : SingleChildScrollView(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          'Disposisi: ${widget.letter.subject}',
                          style: const TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: AppSpacing.gap),
                        const Text('Sifat Surat',
                            style: TextStyle(fontWeight: FontWeight.w600)),
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 8,
                          children: _natures.map((item) {
                            final val = item['value'] as String;
                            return ChoiceChip(
                              label: Text(item['label'] as String),
                              selected: _selectedNature == val,
                              onSelected: (_) =>
                                  setState(() => _selectedNature = val),
                            );
                          }).toList(),
                        ),
                        const SizedBox(height: AppSpacing.gap),
                        const Text('Penerima Pegawai',
                            style: TextStyle(fontWeight: FontWeight.w600)),
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 8,
                          children: _recipients.map((user) {
                            final id = (user['id'] as num).toInt();
                            return FilterChip(
                              label: Text(user['name'] as String),
                              selected: _selectedRecipientIds.contains(id),
                              onSelected: (selected) => setState(() {
                                if (selected) {
                                  _selectedRecipientIds.add(id);
                                } else {
                                  _selectedRecipientIds.remove(id);
                                }
                              }),
                            );
                          }).toList(),
                        ),
                        const SizedBox(height: AppSpacing.gap),
                        const Text('Instruksi',
                            style: TextStyle(fontWeight: FontWeight.w600)),
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 8,
                          children: _instructions.map((item) {
                            final val = item['value'] as String;
                            return FilterChip(
                              label: Text(item['label'] as String),
                              selected: _selectedInstructions.contains(val),
                              onSelected: (selected) => setState(() {
                                if (selected) {
                                  _selectedInstructions.add(val);
                                } else {
                                  _selectedInstructions.remove(val);
                                }
                              }),
                            );
                          }).toList(),
                        ),
                        if (_selectedInstructions.contains('lainnya')) ...[
                          const SizedBox(height: AppSpacing.gap),
                          TextField(
                            controller: _instructionOtherController,
                            decoration: const InputDecoration(
                              labelText: 'Isi disposisi lainnya',
                              hintText: 'Tuliskan instruksi lainnya',
                              border: OutlineInputBorder(),
                            ),
                          ),
                        ],
                        const SizedBox(height: AppSpacing.gap),
                        TextField(
                          controller: _noteController,
                          maxLines: 3,
                          decoration: const InputDecoration(
                            labelText: 'Catatan Pimpinan (opsional)',
                            border: OutlineInputBorder(),
                          ),
                        ),
                        const SizedBox(height: AppSpacing.gap),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: _submitting ? null : _submit,
                            child: Text(
                                _submitting ? 'Menyimpan...' : 'Simpan Disposisi'),
                          ),
                        ),
                      ],
                    ),
                  ),
      ),
    );
  }
}
