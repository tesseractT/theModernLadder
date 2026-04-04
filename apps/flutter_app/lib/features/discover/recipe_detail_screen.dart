import 'package:flutter/material.dart';
import 'package:flutter_app/app/pairly_theme.dart';
import 'package:flutter_app/core/api/api_client.dart';
import 'package:flutter_app/core/api/api_exception.dart';
import 'package:flutter_app/core/models/app_models.dart';

class RecipeDetailScreen extends StatefulWidget {
  const RecipeDetailScreen({
    super.key,
    required this.apiClient,
    required this.suggestion,
  });

  final ApiClient apiClient;
  final SuggestionCandidate suggestion;

  @override
  State<RecipeDetailScreen> createState() => _RecipeDetailScreenState();
}

class _RecipeDetailScreenState extends State<RecipeDetailScreen> {
  RecipeTemplateDetail? _detail;
  RecipeExplanation? _explanation;
  bool _isLoadingDetail = true;
  bool _isLoadingExplanation = false;
  String? _detailError;
  String? _explanationError;

  @override
  void initState() {
    super.initState();
    Future<void>.microtask(_loadDetail);
  }

  Future<void> _loadDetail() async {
    setState(() {
      _isLoadingDetail = true;
      _detailError = null;
    });

    try {
      final response = await widget.apiClient.get(
        '/recipes/templates/${widget.suggestion.recipeTemplateId}',
      );

      setState(() {
        _detail = RecipeTemplateDetail.fromJson(
          Map<String, dynamic>.from(response as Map),
        );
      });
    } on ApiException catch (error) {
      setState(() {
        _detailError = error.message;
      });
    } catch (_) {
      setState(() {
        _detailError = 'Unable to load the recipe detail right now.';
      });
    } finally {
      setState(() {
        _isLoadingDetail = false;
      });
    }
  }

  Future<void> _loadExplanation() async {
    setState(() {
      _isLoadingExplanation = true;
      _explanationError = null;
    });

    try {
      final response = await widget.apiClient.post(
        '/recipes/templates/${widget.suggestion.recipeTemplateId}/explanation',
      );

      setState(() {
        _explanation = RecipeExplanation.fromJson(
          Map<String, dynamic>.from(response as Map),
        );
      });
    } on ApiException catch (error) {
      setState(() {
        _explanationError = error.message;
      });
    } catch (_) {
      setState(() {
        _explanationError =
            'Unable to generate a grounded explanation right now.';
      });
    } finally {
      setState(() {
        _isLoadingExplanation = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: PairlyColors.surface,
      body: SafeArea(
        child: _isLoadingDetail
            ? const Center(child: CircularProgressIndicator())
            : _detailError != null
            ? Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Text(
                    _detailError!,
                    textAlign: TextAlign.center,
                    style: theme.textTheme.titleMedium?.copyWith(
                      color: PairlyColors.error,
                    ),
                  ),
                ),
              )
            : ListView(
                padding: const EdgeInsets.fromLTRB(20, 18, 20, 32),
                children: <Widget>[
                  Row(
                    children: <Widget>[
                      IconButton.filledTonal(
                        onPressed: () => Navigator.of(context).pop(),
                        icon: const Icon(Icons.arrow_back_rounded),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          _detail!.template.title,
                          style: theme.textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  _HeroPanel(detail: _detail!, suggestion: widget.suggestion),
                  const SizedBox(height: 18),
                  _SectionCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Row(
                          children: <Widget>[
                            Container(
                              width: 46,
                              height: 46,
                              decoration: BoxDecoration(
                                color: PairlyColors.primary.withValues(
                                  alpha: 0.12,
                                ),
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: const Icon(
                                Icons.auto_awesome_rounded,
                                color: PairlyColors.primary,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: <Widget>[
                                  Text(
                                    'Grounded explanation',
                                    style: theme.textTheme.titleLarge,
                                  ),
                                  Text(
                                    'Built from the stored recipe template, pantry fit, and substitution data.',
                                    style: theme.textTheme.bodySmall,
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        if (_explanation == null)
                          FilledButton(
                            onPressed: _isLoadingExplanation
                                ? null
                                : _loadExplanation,
                            child: _isLoadingExplanation
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.4,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Text('Explain this match'),
                          )
                        else
                          _ExplanationView(explanation: _explanation!),
                        if (_explanationError != null) ...<Widget>[
                          const SizedBox(height: 12),
                          Text(
                            _explanationError!,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: PairlyColors.error,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  _SectionCard(
                    child: _IngredientSection(
                      title: 'Required ingredients',
                      items: _detail!.requiredIngredients,
                    ),
                  ),
                  const SizedBox(height: 18),
                  if (_detail!.optionalIngredients.isNotEmpty)
                    _SectionCard(
                      child: _IngredientSection(
                        title: 'Optional ingredients',
                        items: _detail!.optionalIngredients,
                      ),
                    ),
                  if (_detail!.optionalIngredients.isNotEmpty)
                    const SizedBox(height: 18),
                  if (_detail!.substitutions.isNotEmpty) ...<Widget>[
                    _SectionCard(
                      child: _SubstitutionSection(
                        substitutions: _detail!.substitutions,
                      ),
                    ),
                    const SizedBox(height: 18),
                  ],
                  _SectionCard(child: _StepsSection(steps: _detail!.steps)),
                ],
              ),
      ),
    );
  }
}

class _HeroPanel extends StatelessWidget {
  const _HeroPanel({required this.detail, required this.suggestion});

  final RecipeTemplateDetail detail;
  final SuggestionCandidate suggestion;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(34),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: <Color>[PairlyColors.primary, PairlyColors.primaryBright],
        ),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: PairlyColors.primary.withValues(alpha: 0.22),
            blurRadius: 30,
            offset: const Offset(0, 20),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: <Widget>[
              _HeroBadge(
                label: detail.template.recipeType.replaceAll('_', ' '),
              ),
              _HeroBadge(label: detail.template.difficulty),
              if (detail.template.totalMinutes != null)
                _HeroBadge(label: '${detail.template.totalMinutes} mins'),
            ],
          ),
          const SizedBox(height: 14),
          Text(
            detail.template.title,
            style: theme.textTheme.headlineMedium?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            detail.template.summary,
            style: theme.textTheme.bodyLarge?.copyWith(
              color: Colors.white.withValues(alpha: 0.92),
            ),
          ),
          const SizedBox(height: 20),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: <Widget>[
              _HeroMetric(
                title: 'Required fit',
                value:
                    '${detail.pantryFit.requiredOwned}/${detail.pantryFit.requiredTotal}',
              ),
              _HeroMetric(
                title: 'Missing',
                value: '${detail.pantryFit.requiredMissing}',
              ),
              _HeroMetric(title: 'Score', value: '${suggestion.score}'),
            ],
          ),
        ],
      ),
    );
  }
}

class _HeroBadge extends StatelessWidget {
  const _HeroBadge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(
          context,
        ).textTheme.labelMedium?.copyWith(color: Colors.white),
      ),
    );
  }
}

class _HeroMetric extends StatelessWidget {
  const _HeroMetric({required this.title, required this.value});

  final String title;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 110,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            title.toUpperCase(),
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
              color: Colors.white.withValues(alpha: 0.82),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: PairlyColors.surfaceCard,
        borderRadius: BorderRadius.circular(30),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: PairlyColors.ink.withValues(alpha: 0.05),
            blurRadius: 22,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      padding: const EdgeInsets.all(22),
      child: child,
    );
  }
}

class _ExplanationView extends StatelessWidget {
  const _ExplanationView({required this.explanation});

  final RecipeExplanation explanation;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          explanation.headline,
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 10),
        Text(explanation.whyItFits, style: theme.textTheme.bodyLarge),
        const SizedBox(height: 16),
        _InfoLine(title: 'Taste', value: explanation.tasteProfile),
        const SizedBox(height: 10),
        _InfoLine(title: 'Texture', value: explanation.textureProfile),
        if (explanation.substitutionGuidance.isNotEmpty) ...<Widget>[
          const SizedBox(height: 16),
          Text('Substitution guidance', style: theme.textTheme.titleMedium),
          const SizedBox(height: 8),
          ...explanation.substitutionGuidance.map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  const Padding(
                    padding: EdgeInsets.only(top: 5),
                    child: Icon(
                      Icons.check_circle_outline_rounded,
                      size: 16,
                      color: PairlyColors.primary,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(item, style: theme.textTheme.bodyMedium),
                  ),
                ],
              ),
            ),
          ),
        ],
        if (explanation.quickTakeaways.isNotEmpty) ...<Widget>[
          const SizedBox(height: 16),
          Text('Quick takeaways', style: theme.textTheme.titleMedium),
          const SizedBox(height: 8),
          ...explanation.quickTakeaways.map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Text('• $item', style: theme.textTheme.bodyMedium),
            ),
          ),
        ],
      ],
    );
  }
}

class _InfoLine extends StatelessWidget {
  const _InfoLine({required this.title, required this.value});

  final String title;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(title.toUpperCase(), style: theme.textTheme.labelSmall),
        const SizedBox(height: 4),
        Text(value, style: theme.textTheme.bodyMedium),
      ],
    );
  }
}

class _IngredientSection extends StatelessWidget {
  const _IngredientSection({required this.title, required this.items});

  final String title;
  final List<RecipeIngredientState> items;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(title, style: theme.textTheme.titleLarge),
        const SizedBox(height: 16),
        ...items.map((item) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 14),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: item.isOwned
                    ? PairlyColors.surfaceLow
                    : PairlyColors.surfaceHighest.withValues(alpha: 0.55),
                borderRadius: BorderRadius.circular(24),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Icon(
                    item.isOwned
                        ? Icons.check_circle_rounded
                        : Icons.radio_button_unchecked_rounded,
                    color: item.isOwned
                        ? PairlyColors.primary
                        : PairlyColors.outline,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          item.ingredient.name,
                          style: theme.textTheme.titleMedium,
                        ),
                        if ((item.ingredient.description ?? '').isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Text(
                              item.ingredient.description!,
                              style: theme.textTheme.bodySmall,
                            ),
                          ),
                        if (item.substitutions.isNotEmpty) ...<Widget>[
                          const SizedBox(height: 8),
                          Text(
                            'Available swap: ${item.substitutions.map((swap) => swap.name).join(', ')}',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: PairlyColors.primary,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
            ),
          );
        }),
      ],
    );
  }
}

class _SubstitutionSection extends StatelessWidget {
  const _SubstitutionSection({required this.substitutions});

  final List<RecipeSubstitutionGroup> substitutions;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text('Pantry substitutions', style: theme.textTheme.titleLarge),
        const SizedBox(height: 16),
        ...substitutions.map((group) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: PairlyColors.primaryBright.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(24),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    'For ${group.forIngredient.name}',
                    style: theme.textTheme.titleMedium?.copyWith(
                      color: PairlyColors.primary,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    group.availableSubstitutes
                        .map((item) => item.name)
                        .join(', '),
                    style: theme.textTheme.bodyMedium,
                  ),
                ],
              ),
            ),
          );
        }),
      ],
    );
  }
}

class _StepsSection extends StatelessWidget {
  const _StepsSection({required this.steps});

  final List<RecipeStepItem> steps;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text('Steps', style: theme.textTheme.titleLarge),
        const SizedBox(height: 16),
        ...steps.map((step) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Container(
                  width: 34,
                  height: 34,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: PairlyColors.surfaceLow,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Text(
                    '${step.position}',
                    style: theme.textTheme.titleSmall?.copyWith(
                      color: PairlyColors.primary,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      step.instruction,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: PairlyColors.ink,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          );
        }),
      ],
    );
  }
}
