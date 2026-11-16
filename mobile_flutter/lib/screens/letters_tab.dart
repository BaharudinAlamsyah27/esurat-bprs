import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'cooperation_agreement_list_screen.dart';
import 'incoming_letter_list_screen.dart';
import 'outgoing_letter_list_screen.dart';

/// Tab "Arsip": sub-tab Surat Masuk, Surat Keluar, dan Perjanjian Kerjasama.
class LettersTab extends StatelessWidget {
  const LettersTab({super.key, this.initialIndex = 0});

  /// 0 = Masuk, 1 = Keluar, 2 = Kerjasama.
  final int initialIndex;

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      initialIndex: initialIndex,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Arsip'),
          titleSpacing: AppSpacing.page,
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Masuk'),
              Tab(text: 'Keluar'),
              Tab(text: 'Kerjasama'),
            ],
          ),
        ),
        body: const TabBarView(
          children: [
            IncomingLetterView(),
            OutgoingLetterView(),
            CooperationAgreementView(),
          ],
        ),
      ),
    );
  }
}
