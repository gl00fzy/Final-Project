import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:msu_scoring_app/main.dart';

void main() {
  testWidgets('App smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: MsuScoringApp(),
      ),
    );
    expect(find.text('MSU Scoring'), findsOneWidget);
  });
}
