import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'incoming_letter_list_screen.dart';
import 'outgoing_letter_list_screen.dart';

/// Tab "Surat": dua sub-tab Surat Masuk dan Surat Keluar.
class LettersTab extends StatelessWidget {
  const LettersTab({super.key, this.initialIndex = 0});

  /// 0 = Masuk, 1 = Keluar.
  final int initialIndex;

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      initialIndex: initialIndex,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Surat'),
          titleSpacing: AppSpacing.page,
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Masuk'),
              Tab(text: 'Keluar'),
            ],
          ),
        ),
        body: const TabBarView(
          children: [
            IncomingLetterView(),
            OutgoingLetterView(),
          ],
        ),
      ),
    );
  }
}
