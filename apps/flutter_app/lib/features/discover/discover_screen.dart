import 'package:flutter/material.dart';
import 'package:flutter_app/app/pairly_theme.dart';
import 'package:flutter_app/core/models/app_models.dart';
import 'package:flutter_app/features/discover/discover_controller.dart';
import 'package:flutter_app/features/discover/recipe_detail_screen.dart';
import 'package:flutter_app/features/navigation/pairly_bottom_dock.dart';
import 'package:flutter_app/features/profile/profile_screen.dart';
import 'package:flutter_app/features/session/session_controller.dart';

class DiscoverScreen extends StatefulWidget {
  const DiscoverScreen({super.key, required this.sessionController});

  final SessionController sessionController;

  @override
  State<DiscoverScreen> createState() => _DiscoverScreenState();
}

class _DiscoverScreenState extends State<DiscoverScreen> {
  late final DiscoverController _controller;
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _controller = DiscoverController(
      apiClient: widget.sessionController.apiClient,
    );
    Future<void>.microtask(_controller.initialize);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _controller.dispose();
    super.dispose();
  }

  Future<void> _showRecipe(SuggestionCandidate suggestion) async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RecipeDetailScreen(
          apiClient: widget.sessionController.apiClient,
          suggestion: suggestion,
        ),
      ),
    );
  }

  Future<void> _openProfile() async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) =>
            ProfileScreen(sessionController: widget.sessionController),
      ),
    );
  }

  void _handleDockSelection(PairlyDockDestination destination) {
    switch (destination) {
      case PairlyDockDestination.discover:
        break;
      case PairlyDockDestination.profile:
        _openProfile();
      case PairlyDockDestination.match:
      case PairlyDockDestination.chats:
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${destination.name[0].toUpperCase()}${destination.name.substring(1)} is coming in a later slice.',
            ),
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AnimatedBuilder(
      animation: Listenable.merge(<Listenable>[
        _controller,
        widget.sessionController,
      ]),
      builder: (context, _) {
        final currentUser = widget.sessionController.currentUser;

        if (_searchController.text != _controller.searchQuery) {
          _searchController.value = TextEditingValue(
            text: _controller.searchQuery,
            selection: TextSelection.collapsed(
              offset: _controller.searchQuery.length,
            ),
          );
        }

        return Scaffold(
          extendBody: true,
          body: Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: <Color>[
                  PairlyColors.surface,
                  PairlyColors.surfaceLow,
                  PairlyColors.surfaceCard,
                ],
              ),
            ),
            child: SafeArea(
              child: RefreshIndicator(
                color: PairlyColors.primary,
                onRefresh: _controller.refresh,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(20, 18, 20, 140),
                  children: <Widget>[
                    _TopBar(
                      displayName: currentUser?.profile.displayName ?? 'Chef',
                      onOpenProfile: _openProfile,
                      onLogout: widget.sessionController.signOut,
                    ),
                    const SizedBox(height: 28),
                    _HeroHeader(hasPantry: _controller.hasPantry),
                    const SizedBox(height: 24),
                    _ComposerPanel(
                      controller: _controller,
                      searchController: _searchController,
                    ),
                    if (_controller.loadError != null) ...<Widget>[
                      const SizedBox(height: 18),
                      _InlineNotice(message: _controller.loadError!),
                    ],
                    if (_controller.hasPantry) ...<Widget>[
                      const SizedBox(height: 26),
                      _SectionEyebrow(label: 'Current pantry'),
                      const SizedBox(height: 12),
                      Wrap(
                        spacing: 10,
                        runSpacing: 10,
                        children: _controller.pantryItems.map((item) {
                          return Chip(
                            avatar: const Icon(
                              Icons.eco_rounded,
                              size: 16,
                              color: PairlyColors.primary,
                            ),
                            label: Text(item.ingredient.name),
                            onDeleted: () async {
                              await _controller.removePantryItem(item);
                            },
                            deleteIconColor: PairlyColors.outline,
                            backgroundColor: PairlyColors.surfaceCard,
                            side: BorderSide.none,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(999),
                            ),
                          );
                        }).toList(),
                      ),
                    ],
                    const SizedBox(height: 28),
                    FilledButton(
                      onPressed:
                          !_controller.hasPantry || _controller.isGenerating
                          ? null
                          : () async {
                              await _controller.generateSuggestions();
                            },
                      style: FilledButton.styleFrom(
                        backgroundColor: _controller.hasPantry
                            ? PairlyColors.primary
                            : PairlyColors.surfaceHigh,
                        foregroundColor: _controller.hasPantry
                            ? Colors.white
                            : PairlyColors.outline,
                        disabledBackgroundColor: PairlyColors.surfaceHigh,
                        disabledForegroundColor: PairlyColors.outline,
                      ),
                      child: _controller.isGenerating
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2.4,
                                color: Colors.white,
                              ),
                            )
                          : const Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: <Widget>[
                                Text('Show me ideas'),
                                SizedBox(width: 8),
                                Icon(Icons.arrow_forward_rounded),
                              ],
                            ),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      'Food discovery and general nutrition education. Not diagnosis, treatment, or medical decision support.',
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: PairlyColors.outline,
                        height: 1.45,
                      ),
                    ),
                    if (_controller.hasPantry &&
                        _controller.suggestions.isEmpty &&
                        _controller.suggestionsMessage != null) ...<Widget>[
                      const SizedBox(height: 30),
                      _EmptySuggestionCallout(
                        message: _controller.suggestionsMessage!,
                      ),
                    ],
                    if (_controller.hasSuggestions) ...<Widget>[
                      const SizedBox(height: 36),
                      _SectionEyebrow(label: 'Top pantry match'),
                      const SizedBox(height: 12),
                      _FeaturedSuggestionCard(
                        suggestion: _controller.suggestions.first,
                        onOpen: () =>
                            _showRecipe(_controller.suggestions.first),
                      ),
                      const SizedBox(height: 18),
                      _SupportRow(
                        leading: _InsightCard(
                          title: 'Grounded explanation',
                          body:
                              'Open a match to pull the backend’s pantry-fit detail and grounded explanation layer.',
                          icon: Icons.auto_awesome_rounded,
                          cta: 'See why it fits',
                          onTap: () =>
                              _showRecipe(_controller.suggestions.first),
                        ),
                        trailing: _RecentPantryCard(
                          items: _controller.pantryItems,
                        ),
                      ),
                      if (_controller.suggestions.length > 1) ...<Widget>[
                        const SizedBox(height: 18),
                        _SectionEyebrow(label: 'Other matches'),
                        const SizedBox(height: 12),
                        ..._controller.suggestions
                            .skip(1)
                            .map(
                              (suggestion) => Padding(
                                padding: const EdgeInsets.only(bottom: 12),
                                child: _SuggestionTile(
                                  suggestion: suggestion,
                                  onOpen: () => _showRecipe(suggestion),
                                ),
                              ),
                            ),
                      ],
                    ],
                  ],
                ),
              ),
            ),
          ),
          bottomNavigationBar: PairlyBottomDock(
            activeDestination: PairlyDockDestination.discover,
            onSelected: _handleDockSelection,
          ),
        );
      },
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar({
    required this.displayName,
    required this.onOpenProfile,
    required this.onLogout,
  });

  final String displayName;
  final VoidCallback onOpenProfile;
  final Future<void> Function() onLogout;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      children: <Widget>[
        InkWell(
          onTap: onOpenProfile,
          borderRadius: BorderRadius.circular(16),
          child: Ink(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: PairlyColors.surfaceHighest,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Center(
              child: Text(
                displayName.characters.first.toUpperCase(),
                style: theme.textTheme.titleMedium?.copyWith(
                  color: PairlyColors.primary,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
        ),
        const SizedBox(width: 12),
        Text(
          'Pairly',
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
            letterSpacing: -0.6,
          ),
        ),
        const Spacer(),
        IconButton(
          onPressed: onLogout,
          tooltip: 'Sign out',
          icon: const Icon(Icons.logout_rounded),
          color: PairlyColors.primary,
        ),
      ],
    );
  }
}

class _HeroHeader extends StatelessWidget {
  const _HeroHeader({required this.hasPantry});

  final bool hasPantry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      children: <Widget>[
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            color: PairlyColors.surfaceLow,
            borderRadius: BorderRadius.circular(999),
          ),
          child: Text(
            hasPantry ? 'ARTISAN PAIRING ENGINE' : 'YOUR KITCHEN ASSISTANT',
            style: theme.textTheme.labelSmall,
          ),
        ),
        const SizedBox(height: 20),
        Text(
          hasPantry
              ? 'What is in your\nkitchen today?'
              : 'What do you have\nat home today?',
          textAlign: TextAlign.center,
          style: theme.textTheme.displayMedium?.copyWith(
            fontSize: hasPantry ? 40 : 38,
            height: 1.05,
          ),
        ),
        const SizedBox(height: 12),
        Text(
          hasPantry
              ? 'Use your saved pantry to surface grounded, high-fit recipe ideas in a few taps.'
              : 'Turn your ingredients into pairings, quick bites, drinks, and grounded culinary inspiration.',
          textAlign: TextAlign.center,
          style: theme.textTheme.bodyLarge?.copyWith(
            color: PairlyColors.inkSoft.withValues(alpha: 0.82),
          ),
        ),
      ],
    );
  }
}

class _ComposerPanel extends StatelessWidget {
  const _ComposerPanel({
    required this.controller,
    required this.searchController,
  });

  final DiscoverController controller;
  final TextEditingController searchController;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      decoration: BoxDecoration(
        color: PairlyColors.surfaceCard.withValues(alpha: 0.92),
        borderRadius: BorderRadius.circular(32),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: PairlyColors.primary.withValues(alpha: 0.08),
            blurRadius: 44,
            offset: const Offset(0, 22),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(18, 20, 18, 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            controller.hasPantry ? 'Selected ingredients' : 'Add ingredients',
            style: theme.textTheme.labelSmall?.copyWith(
              color: PairlyColors.outline,
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: searchController,
            onChanged: controller.onSearchChanged,
            decoration: InputDecoration(
              hintText: controller.hasPantry
                  ? 'Add more ingredients...'
                  : 'Add ingredients...',
              prefixIcon: const Icon(Icons.add_circle_outline_rounded),
              suffixIcon: controller.isSearching
                  ? const Padding(
                      padding: EdgeInsets.all(14),
                      child: SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                    )
                  : null,
            ),
          ),
          if (controller.ingredientResults.isNotEmpty) ...<Widget>[
            const SizedBox(height: 12),
            ...controller.ingredientResults.map(
              (ingredient) => ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),
                tileColor: PairlyColors.surfaceLow,
                leading: const Icon(
                  Icons.eco_outlined,
                  color: PairlyColors.primary,
                ),
                title: Text(
                  ingredient.name,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                subtitle: ingredient.matchedAlias != null
                    ? Text('Matched as ${ingredient.matchedAlias}')
                    : ingredient.description == null
                    ? null
                    : Text(ingredient.description!),
                trailing: const Icon(Icons.arrow_forward_rounded),
                onTap: () async {
                  await controller.addIngredient(ingredient);
                },
              ),
            ),
          ],
          const SizedBox(height: 16),
          Center(
            child: Text(
              'POPULAR CHOICES',
              style: theme.textTheme.labelSmall?.copyWith(
                color: PairlyColors.outline,
              ),
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            alignment: WrapAlignment.center,
            children: controller.quickAddDefaults.map((query) {
              return ActionChip(
                backgroundColor: PairlyColors.surfaceLow,
                label: Text(query),
                onPressed: () async {
                  await controller.addQuickIngredient(query);
                },
              );
            }).toList(),
          ),
          const SizedBox(height: 20),
          Center(
            child: Text(
              'WHAT IS THE VIBE?',
              style: theme.textTheme.labelSmall?.copyWith(
                color: PairlyColors.outline,
              ),
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: GoalOption.values.map((goal) {
              final isSelected = controller.selectedGoal == goal;
              return InkWell(
                onTap: () async => controller.setGoal(goal),
                borderRadius: BorderRadius.circular(24),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  width: 144,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 14,
                  ),
                  decoration: BoxDecoration(
                    color: isSelected
                        ? PairlyColors.secondary.withValues(alpha: 0.16)
                        : PairlyColors.surfaceLow,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(
                      color: isSelected
                          ? PairlyColors.secondary.withValues(alpha: 0.5)
                          : Colors.transparent,
                      width: 1.2,
                    ),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: <Widget>[
                      Icon(
                        goal.icon,
                        color: isSelected
                            ? PairlyColors.secondaryDeep
                            : PairlyColors.inkSoft,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        goal.label,
                        style: theme.textTheme.labelMedium?.copyWith(
                          color: isSelected
                              ? PairlyColors.secondaryDeep
                              : PairlyColors.ink,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}

class _SectionEyebrow extends StatelessWidget {
  const _SectionEyebrow({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Text(
      label.toUpperCase(),
      style: Theme.of(
        context,
      ).textTheme.labelSmall?.copyWith(color: PairlyColors.outline),
    );
  }
}

class _EmptySuggestionCallout extends StatelessWidget {
  const _EmptySuggestionCallout({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: PairlyColors.surfaceLow,
        borderRadius: BorderRadius.circular(28),
      ),
      child: Row(
        children: <Widget>[
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: PairlyColors.primary.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(
              Icons.lightbulb_outline_rounded,
              color: PairlyColors.primary,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Text(
              message,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: PairlyColors.ink,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FeaturedSuggestionCard extends StatelessWidget {
  const _FeaturedSuggestionCard({
    required this.suggestion,
    required this.onOpen,
  });

  final SuggestionCandidate suggestion;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return InkWell(
      onTap: onOpen,
      borderRadius: BorderRadius.circular(34),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(34),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: <Color>[PairlyColors.primary, PairlyColors.primaryBright],
          ),
          boxShadow: <BoxShadow>[
            BoxShadow(
              color: PairlyColors.primary.withValues(alpha: 0.24),
              blurRadius: 34,
              offset: const Offset(0, 20),
            ),
          ],
        ),
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              suggestion.isReadyNow
                  ? 'Ready with your pantry'
                  : 'Best next match',
              style: theme.textTheme.labelSmall?.copyWith(
                color: PairlyColors.primaryFixed,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              suggestion.title,
              style: theme.textTheme.headlineMedium?.copyWith(
                color: Colors.white,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              suggestion.summary,
              style: theme.textTheme.bodyLarge?.copyWith(
                color: Colors.white.withValues(alpha: 0.9),
              ),
            ),
            const SizedBox(height: 18),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: <Widget>[
                _MetricPill(
                  label:
                      '${suggestion.requiredMatched}/${suggestion.requiredTotal} required matched',
                ),
                _MetricPill(label: 'Score ${suggestion.score}'),
                _MetricPill(
                  label: suggestion.suggestionType.replaceAll('_', ' '),
                ),
              ],
            ),
            const SizedBox(height: 18),
            Text(
              suggestion.matchedIngredientNames.take(3).join(' • '),
              style: theme.textTheme.bodyMedium?.copyWith(
                color: Colors.white.withValues(alpha: 0.86),
              ),
            ),
            const SizedBox(height: 18),
            Align(
              alignment: Alignment.centerLeft,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: const <Widget>[
                    Text(
                      'Open recipe',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    SizedBox(width: 8),
                    Icon(
                      Icons.arrow_forward_rounded,
                      color: Colors.white,
                      size: 18,
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricPill extends StatelessWidget {
  const _MetricPill({required this.label});

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

class _SupportRow extends StatelessWidget {
  const _SupportRow({required this.leading, required this.trailing});

  final Widget leading;
  final Widget trailing;

  @override
  Widget build(BuildContext context) {
    final isNarrow = MediaQuery.sizeOf(context).width < 700;

    if (isNarrow) {
      return Column(
        children: <Widget>[leading, const SizedBox(height: 12), trailing],
      );
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Expanded(child: leading),
        const SizedBox(width: 12),
        Expanded(child: trailing),
      ],
    );
  }
}

class _InsightCard extends StatelessWidget {
  const _InsightCard({
    required this.title,
    required this.body,
    required this.icon,
    required this.cta,
    required this.onTap,
  });

  final String title;
  final String body;
  final IconData icon;
  final String cta;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: PairlyColors.primaryBright.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(30),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
              color: PairlyColors.primaryBright.withValues(alpha: 0.26),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Icon(icon, color: PairlyColors.primary),
          ),
          const SizedBox(height: 18),
          Text(title, style: theme.textTheme.titleLarge),
          const SizedBox(height: 10),
          Text(body, style: theme.textTheme.bodyMedium),
          const SizedBox(height: 18),
          TextButton.icon(
            onPressed: onTap,
            iconAlignment: IconAlignment.end,
            icon: const Icon(Icons.east_rounded, size: 18),
            label: Text(cta),
          ),
        ],
      ),
    );
  }
}

class _RecentPantryCard extends StatelessWidget {
  const _RecentPantryCard({required this.items});

  final List<PantryItem> items;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final visible = items.take(3).toList();

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: PairlyColors.surfaceCard,
        borderRadius: BorderRadius.circular(30),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: PairlyColors.ink.withValues(alpha: 0.04),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text('Recent pantry', style: theme.textTheme.titleLarge),
          const SizedBox(height: 18),
          ...visible.asMap().entries.map((entry) {
            final index = entry.key + 1;
            final item = entry.value;

            return Padding(
              padding: const EdgeInsets.only(bottom: 14),
              child: Row(
                children: <Widget>[
                  Container(
                    width: 40,
                    height: 40,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: PairlyColors.surfaceLow,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Text(
                      '$index',
                      style: theme.textTheme.titleSmall?.copyWith(
                        color: PairlyColors.primary,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
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
                        Text(
                          item.note ?? 'Saved to your pantry',
                          style: theme.textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }
}

class _SuggestionTile extends StatelessWidget {
  const _SuggestionTile({required this.suggestion, required this.onOpen});

  final SuggestionCandidate suggestion;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return InkWell(
      onTap: onOpen,
      borderRadius: BorderRadius.circular(28),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: PairlyColors.surfaceLow,
          borderRadius: BorderRadius.circular(28),
        ),
        child: Row(
          children: <Widget>[
            Container(
              width: 54,
              height: 54,
              decoration: BoxDecoration(
                color: PairlyColors.surfaceCard,
                borderRadius: BorderRadius.circular(18),
              ),
              child: Icon(
                suggestion.isReadyNow
                    ? Icons.check_circle_outline_rounded
                    : Icons.auto_fix_high_rounded,
                color: suggestion.isReadyNow
                    ? PairlyColors.primary
                    : PairlyColors.secondaryDeep,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(suggestion.title, style: theme.textTheme.titleMedium),
                  const SizedBox(height: 4),
                  Text(
                    suggestion.summary,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.bodyMedium,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    suggestion.missingRequiredCount == 0
                        ? 'Ready now'
                        : '${suggestion.missingRequiredCount} required gap'
                              '${suggestion.missingRequiredCount == 1 ? '' : 's'}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: PairlyColors.primary,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            const Icon(
              Icons.arrow_forward_ios_rounded,
              size: 18,
              color: PairlyColors.outline,
            ),
          ],
        ),
      ),
    );
  }
}

class _InlineNotice extends StatelessWidget {
  const _InlineNotice({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: PairlyColors.error.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(22),
      ),
      child: Row(
        children: <Widget>[
          const Icon(Icons.info_outline_rounded, color: PairlyColors.error),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: PairlyColors.error,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
