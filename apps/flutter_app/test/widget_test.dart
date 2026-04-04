import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_app/app/pairly_app.dart';

void main() {
  testWidgets('boots into the Pairly auth experience', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(const PairlyBootstrap());

    expect(find.text('Pairly'), findsOneWidget);
    expect(find.text('Pantry-led recipe discovery'), findsOneWidget);
  });
}
