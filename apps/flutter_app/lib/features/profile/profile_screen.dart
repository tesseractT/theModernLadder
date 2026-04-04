import 'package:flutter/material.dart';
import 'package:flutter_app/app/pairly_theme.dart';
import 'package:flutter_app/core/api/api_exception.dart';
import 'package:flutter_app/core/models/app_models.dart';
import 'package:flutter_app/features/navigation/pairly_bottom_dock.dart';
import 'package:flutter_app/features/session/session_controller.dart';

const List<String> _dietaryPatternOptions = <String>[
  'omnivore',
  'vegetarian',
  'vegan',
  'pescatarian',
  'halal',
  'kosher',
];

const List<String> _suggestedCuisineOptions = <String>[
  'Japanese',
  'Levantine',
  'Mediterranean',
  'West African',
  'Mexican',
  'Korean',
];

const List<String> _suggestedDislikes = <String>[
  'anchovy',
  'capers',
  'cilantro',
  'olives',
  'blue cheese',
];

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.sessionController});

  final SessionController sessionController;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _displayNameController = TextEditingController();
  final _bioController = TextEditingController();
  final _localeController = TextEditingController();
  final _timezoneController = TextEditingController();
  final _countryCodeController = TextEditingController();
  final _preferredCuisineInputController = TextEditingController();
  final _dislikedIngredientInputController = TextEditingController();

  AppUser? _seededUser;
  List<String> _preferredCuisines = <String>[];
  List<String> _dislikedIngredients = <String>[];
  Set<String> _dietaryPatterns = <String>{};
  String _measurementSystem = 'metric';

  bool _isRefreshing = false;
  bool _isSavingProfile = false;
  bool _isSavingPreferences = false;
  String? _profileNotice;
  String? _profileError;
  String? _preferencesNotice;
  String? _preferencesError;
  Map<String, List<String>> _profileFieldErrors =
      const <String, List<String>>{};
  Map<String, List<String>> _preferenceFieldErrors =
      const <String, List<String>>{};

  @override
  void initState() {
    super.initState();
    _seedFromUser(widget.sessionController.currentUser);
  }

  @override
  void dispose() {
    _displayNameController.dispose();
    _bioController.dispose();
    _localeController.dispose();
    _timezoneController.dispose();
    _countryCodeController.dispose();
    _preferredCuisineInputController.dispose();
    _dislikedIngredientInputController.dispose();
    super.dispose();
  }

  String get _normalizedDisplayName => _displayNameController.text.trim();

  String? get _normalizedBio => _blankToNull(_bioController.text);

  String get _normalizedLocale {
    final seededLocale = _seededUser?.profile.locale ?? 'en';
    final trimmed = _localeController.text.trim();
    return (trimmed.isEmpty ? seededLocale : trimmed).toLowerCase();
  }

  String? get _normalizedTimezone => _blankToNull(_timezoneController.text);

  String? get _normalizedCountryCode {
    final trimmed = _countryCodeController.text.trim();
    if (trimmed.isEmpty) {
      return null;
    }

    return trimmed.toUpperCase();
  }

  List<String> get _normalizedPreferredCuisines =>
      _normalizeStringList(_preferredCuisines);

  List<String> get _normalizedDislikedIngredients =>
      _normalizeStringList(_dislikedIngredients, lowercase: true);

  List<String> get _normalizedDietaryPatterns =>
      _normalizeStringList(_dietaryPatterns.toList(), lowercase: true);

  bool get _hasProfileChanges {
    final seededUser = _seededUser;
    if (seededUser == null) {
      return false;
    }

    final profile = seededUser.profile;

    return _normalizedDisplayName != profile.displayName ||
        _normalizedBio != profile.bio ||
        _normalizedLocale != (profile.locale ?? 'en').toLowerCase() ||
        _normalizedTimezone != profile.timezone ||
        _normalizedCountryCode != profile.countryCode;
  }

  bool get _hasPreferenceChanges {
    final seededUser = _seededUser;
    if (seededUser == null) {
      return false;
    }

    final preferences = seededUser.preferences;

    return !_sameList(
          _normalizedDietaryPatterns,
          _normalizeStringList(preferences.dietaryPatterns, lowercase: true),
        ) ||
        !_sameList(
          _normalizedPreferredCuisines,
          _normalizeStringList(preferences.preferredCuisines),
        ) ||
        !_sameList(
          _normalizedDislikedIngredients,
          _normalizeStringList(
            preferences.dislikedIngredients,
            lowercase: true,
          ),
        ) ||
        _measurementSystem != preferences.measurementSystem;
  }

  Future<void> _refreshFromServer() async {
    if (_isRefreshing) {
      return;
    }

    setState(() {
      _isRefreshing = true;
      _profileNotice = null;
      _preferencesNotice = null;
    });

    try {
      await widget.sessionController.refreshCurrentUser();
      if (!mounted) {
        return;
      }

      setState(() {
        _seedFromUser(widget.sessionController.currentUser);
      });
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }

      setState(() {
        _profileError = error.message;
        _preferencesError = error.message;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }

      setState(() {
        _profileError = 'Unable to refresh your account right now.';
        _preferencesError = 'Unable to refresh your account right now.';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isRefreshing = false;
        });
      }
    }
  }

  Future<void> _saveProfile() async {
    FocusScope.of(context).unfocus();

    if (_normalizedDisplayName.length < 2) {
      setState(() {
        _profileFieldErrors = const <String, List<String>>{
          'display_name': <String>['Display name needs at least 2 characters.'],
        };
        _profileError = 'Please tighten the profile details before saving.';
        _profileNotice = null;
      });
      return;
    }

    setState(() {
      _isSavingProfile = true;
      _profileError = null;
      _profileNotice = null;
      _profileFieldErrors = const <String, List<String>>{};
    });

    try {
      final user = await widget.sessionController.updateProfile(
        displayName: _normalizedDisplayName,
        bio: _normalizedBio,
        locale: _normalizedLocale,
        timezone: _normalizedTimezone,
        countryCode: _normalizedCountryCode,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _seedFromUser(user);
        _profileNotice = 'Profile updated successfully.';
      });

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Profile updated.')));
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }

      setState(() {
        _profileError = error.message;
        _profileFieldErrors = error.errors;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }

      setState(() {
        _profileError = 'Unable to save your profile right now.';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isSavingProfile = false;
        });
      }
    }
  }

  Future<void> _savePreferences() async {
    FocusScope.of(context).unfocus();

    setState(() {
      _isSavingPreferences = true;
      _preferencesError = null;
      _preferencesNotice = null;
      _preferenceFieldErrors = const <String, List<String>>{};
    });

    try {
      final user = await widget.sessionController.updatePreferences(
        dietaryPatterns: _normalizedDietaryPatterns,
        preferredCuisines: _normalizedPreferredCuisines,
        dislikedIngredients: _normalizedDislikedIngredients,
        measurementSystem: _measurementSystem,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _seedFromUser(user);
        _preferencesNotice = 'Kitchen preferences saved.';
      });

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Preferences updated.')));
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }

      setState(() {
        _preferencesError = error.message;
        _preferenceFieldErrors = error.errors;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }

      setState(() {
        _preferencesError = 'Unable to save your preferences right now.';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isSavingPreferences = false;
        });
      }
    }
  }

  Future<void> _signOut() async {
    await widget.sessionController.signOut();
    if (!mounted) {
      return;
    }

    Navigator.of(context).popUntil((route) => route.isFirst);
  }

  void _seedFromUser(AppUser? user) {
    if (user == null) {
      return;
    }

    _seededUser = user;
    _displayNameController.text = user.profile.displayName;
    _bioController.text = user.profile.bio ?? '';
    _localeController.text = user.profile.locale ?? 'en';
    _timezoneController.text = user.profile.timezone ?? '';
    _countryCodeController.text = user.profile.countryCode ?? '';
    _preferredCuisines = List<String>.from(user.preferences.preferredCuisines);
    _dislikedIngredients = List<String>.from(
      user.preferences.dislikedIngredients,
    );
    _dietaryPatterns = user.preferences.dietaryPatterns.toSet();
    _measurementSystem = user.preferences.measurementSystem;
    _profileFieldErrors = const <String, List<String>>{};
    _preferenceFieldErrors = const <String, List<String>>{};
  }

  void _markProfileDraftChanged() {
    setState(() {
      _profileNotice = null;
      _profileError = null;
      _profileFieldErrors = const <String, List<String>>{};
    });
  }

  void _markPreferenceDraftChanged() {
    setState(() {
      _preferencesNotice = null;
      _preferencesError = null;
      _preferenceFieldErrors = const <String, List<String>>{};
    });
  }

  void _toggleDietaryPattern(String pattern) {
    _markPreferenceDraftChanged();
    if (_dietaryPatterns.contains(pattern)) {
      _dietaryPatterns.remove(pattern);
    } else {
      _dietaryPatterns.add(pattern);
    }
  }

  void _addPreferredCuisine([String? explicitValue]) {
    final candidate = explicitValue ?? _preferredCuisineInputController.text;
    final normalized = _normalizeChipValue(candidate);

    if (normalized == null || _preferredCuisines.contains(normalized)) {
      _preferredCuisineInputController.clear();
      return;
    }

    _markPreferenceDraftChanged();
    _preferredCuisines = <String>[..._preferredCuisines, normalized];
    _preferredCuisineInputController.clear();
  }

  void _removePreferredCuisine(String value) {
    _markPreferenceDraftChanged();
    _preferredCuisines = _preferredCuisines
        .where((item) => item != value)
        .toList();
  }

  void _addDislikedIngredient([String? explicitValue]) {
    final candidate = explicitValue ?? _dislikedIngredientInputController.text;
    final normalized = _normalizeChipValue(candidate, lowercase: true);

    if (normalized == null || _dislikedIngredients.contains(normalized)) {
      _dislikedIngredientInputController.clear();
      return;
    }

    _markPreferenceDraftChanged();
    _dislikedIngredients = <String>[..._dislikedIngredients, normalized];
    _dislikedIngredientInputController.clear();
  }

  void _removeDislikedIngredient(String value) {
    _markPreferenceDraftChanged();
    _dislikedIngredients = _dislikedIngredients
        .where((item) => item != value)
        .toList();
  }

  List<String> _flattenErrorLines(Map<String, List<String>> errors) {
    return errors.values.expand((items) => items).toSet().toList();
  }

  @override
  Widget build(BuildContext context) {
    final currentUser = widget.sessionController.currentUser;
    final theme = Theme.of(context);

    if (currentUser == null) {
      return const SizedBox.shrink();
    }

    return AnimatedBuilder(
      animation: widget.sessionController,
      builder: (context, _) {
        final user = widget.sessionController.currentUser ?? currentUser;

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
                onRefresh: _refreshFromServer,
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(20, 18, 20, 140),
                  children: <Widget>[
                    _ProfileTopBar(
                      onBack: () => Navigator.maybePop(context),
                      onSignOut: _signOut,
                    ),
                    const SizedBox(height: 24),
                    _ProfileHeroCard(
                      user: user,
                      dietaryCount: _dietaryPatterns.length,
                      preferredCuisineCount: _preferredCuisines.length,
                      measurementSystem: _measurementSystem,
                    ),
                    const SizedBox(height: 26),
                    Text(
                      'Profile',
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: PairlyColors.outline,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _SectionPanel(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Text(
                            'Shape how Pairly knows you',
                            style: theme.textTheme.headlineMedium?.copyWith(
                              fontSize: 28,
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            'Keep identity light and kitchen-friendly: who you are, where you cook, and the language Pairly should speak back in.',
                            style: theme.textTheme.bodyLarge?.copyWith(
                              color: PairlyColors.inkSoft.withValues(
                                alpha: 0.84,
                              ),
                            ),
                          ),
                          const SizedBox(height: 22),
                          if (_profileNotice != null) ...<Widget>[
                            _FeedbackBanner(
                              message: _profileNotice!,
                              tint: PairlyColors.primaryBright,
                              textColor: PairlyColors.primary,
                            ),
                            const SizedBox(height: 14),
                          ],
                          if (_profileError != null) ...<Widget>[
                            _FeedbackBanner(
                              message: _profileError!,
                              tint: PairlyColors.error.withValues(alpha: 0.1),
                              textColor: PairlyColors.error,
                              details: _flattenErrorLines(_profileFieldErrors),
                            ),
                            const SizedBox(height: 14),
                          ],
                          _LabeledInput(
                            label: 'Display name',
                            child: TextField(
                              controller: _displayNameController,
                              textInputAction: TextInputAction.next,
                              onChanged: (_) => _markProfileDraftChanged(),
                              decoration: InputDecoration(
                                hintText: 'Casey Morgan',
                                prefixIcon: const Icon(Icons.person_rounded),
                                errorText:
                                    _profileFieldErrors['display_name']?.first,
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          _LabeledInput(
                            label: 'Bio',
                            child: TextField(
                              controller: _bioController,
                              minLines: 3,
                              maxLines: 4,
                              onChanged: (_) => _markProfileDraftChanged(),
                              decoration: InputDecoration(
                                hintText:
                                    'Ingredient-led home cook with a thing for bright breakfasts.',
                                alignLabelWithHint: true,
                                prefixIcon: const Padding(
                                  padding: EdgeInsets.only(bottom: 42),
                                  child: Icon(Icons.notes_rounded),
                                ),
                                errorText: _profileFieldErrors['bio']?.first,
                              ),
                            ),
                          ),
                          const SizedBox(height: 18),
                          _AdaptiveFields(
                            leading: _LabeledInput(
                              label: 'Locale',
                              child: TextField(
                                controller: _localeController,
                                textInputAction: TextInputAction.next,
                                onChanged: (_) => _markProfileDraftChanged(),
                                decoration: InputDecoration(
                                  hintText: 'en-gb',
                                  prefixIcon: const Icon(
                                    Icons.language_rounded,
                                  ),
                                  errorText:
                                      _profileFieldErrors['locale']?.first,
                                ),
                              ),
                            ),
                            trailing: _LabeledInput(
                              label: 'Country code',
                              child: TextField(
                                controller: _countryCodeController,
                                textCapitalization:
                                    TextCapitalization.characters,
                                textInputAction: TextInputAction.next,
                                onChanged: (_) => _markProfileDraftChanged(),
                                decoration: InputDecoration(
                                  hintText: 'GB',
                                  prefixIcon: const Icon(Icons.flag_outlined),
                                  errorText: _profileFieldErrors['country_code']
                                      ?.first,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 18),
                          _LabeledInput(
                            label: 'Timezone',
                            child: TextField(
                              controller: _timezoneController,
                              textInputAction: TextInputAction.done,
                              onChanged: (_) => _markProfileDraftChanged(),
                              decoration: InputDecoration(
                                hintText: 'Europe/London',
                                prefixIcon: const Icon(Icons.schedule_rounded),
                                errorText:
                                    _profileFieldErrors['timezone']?.first,
                              ),
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            'Examples: `en-gb`, `Europe/London`, `US`. Pairly keeps this lightweight so kitchen context stays useful without feeling clinical.',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: PairlyColors.outline,
                              height: 1.45,
                            ),
                          ),
                          const SizedBox(height: 22),
                          FilledButton(
                            onPressed: _isSavingProfile || !_hasProfileChanges
                                ? null
                                : _saveProfile,
                            child: _isSavingProfile
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.4,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Text('Save profile'),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Preferences',
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: PairlyColors.outline,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _SectionPanel(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Text(
                            'Tune your kitchen defaults',
                            style: theme.textTheme.headlineMedium?.copyWith(
                              fontSize: 28,
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(
                            'These preferences steer pantry suggestions, substitutions, and explanations. No diagnosis, no treatment framing, no medical-condition profiling.',
                            style: theme.textTheme.bodyLarge?.copyWith(
                              color: PairlyColors.inkSoft.withValues(
                                alpha: 0.84,
                              ),
                            ),
                          ),
                          const SizedBox(height: 22),
                          if (_preferencesNotice != null) ...<Widget>[
                            _FeedbackBanner(
                              message: _preferencesNotice!,
                              tint: PairlyColors.primaryBright,
                              textColor: PairlyColors.primary,
                            ),
                            const SizedBox(height: 14),
                          ],
                          if (_preferencesError != null) ...<Widget>[
                            _FeedbackBanner(
                              message: _preferencesError!,
                              tint: PairlyColors.error.withValues(alpha: 0.1),
                              textColor: PairlyColors.error,
                              details: _flattenErrorLines(
                                _preferenceFieldErrors,
                              ),
                            ),
                            const SizedBox(height: 14),
                          ],
                          Text(
                            'Measurement system',
                            style: theme.textTheme.titleMedium,
                          ),
                          const SizedBox(height: 12),
                          SegmentedButton<String>(
                            showSelectedIcon: false,
                            segments: const <ButtonSegment<String>>[
                              ButtonSegment<String>(
                                value: 'metric',
                                label: Text('Metric'),
                              ),
                              ButtonSegment<String>(
                                value: 'imperial',
                                label: Text('Imperial'),
                              ),
                            ],
                            selected: <String>{_measurementSystem},
                            onSelectionChanged: (selection) {
                              _markPreferenceDraftChanged();
                              _measurementSystem = selection.first;
                            },
                          ),
                          const SizedBox(height: 22),
                          Text(
                            'Dietary patterns',
                            style: theme.textTheme.titleMedium,
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 10,
                            runSpacing: 10,
                            children: _dietaryPatternOptions.map((pattern) {
                              final selected = _dietaryPatterns.contains(
                                pattern,
                              );

                              return FilterChip(
                                selected: selected,
                                label: Text(_presentLabel(pattern)),
                                onSelected: (_) => setState(
                                  () => _toggleDietaryPattern(pattern),
                                ),
                                selectedColor: PairlyColors.secondary
                                    .withValues(alpha: 0.18),
                                checkmarkColor: PairlyColors.secondaryDeep,
                                labelStyle: theme.textTheme.labelMedium
                                    ?.copyWith(
                                      color: selected
                                          ? PairlyColors.secondaryDeep
                                          : PairlyColors.ink,
                                    ),
                                side: BorderSide(
                                  color: selected
                                      ? PairlyColors.secondary.withValues(
                                          alpha: 0.48,
                                        )
                                      : Colors.transparent,
                                ),
                                backgroundColor: PairlyColors.surfaceLow,
                              );
                            }).toList(),
                          ),
                          const SizedBox(height: 24),
                          _TokenComposer(
                            title: 'Preferred cuisines',
                            hintText: 'Add a cuisine you lean toward',
                            helper:
                                'Use this to bias discovery toward flavor directions you already love.',
                            values: _preferredCuisines,
                            inputController: _preferredCuisineInputController,
                            suggestions: _suggestedCuisineOptions
                                .where(
                                  (item) => !_preferredCuisines.contains(item),
                                )
                                .toList(),
                            onChanged: () => _markPreferenceDraftChanged(),
                            onSubmitted: _addPreferredCuisine,
                            onRemove: (value) =>
                                setState(() => _removePreferredCuisine(value)),
                            onSuggestionTap: (value) =>
                                setState(() => _addPreferredCuisine(value)),
                          ),
                          const SizedBox(height: 22),
                          _TokenComposer(
                            title: 'Disliked ingredients',
                            hintText: 'Add an ingredient to avoid',
                            helper:
                                'This helps Pairly avoid recommending ingredients you would rather skip.',
                            values: _dislikedIngredients,
                            inputController: _dislikedIngredientInputController,
                            suggestions: _suggestedDislikes
                                .where(
                                  (item) =>
                                      !_dislikedIngredients.contains(item),
                                )
                                .toList(),
                            onChanged: () => _markPreferenceDraftChanged(),
                            onSubmitted: _addDislikedIngredient,
                            onRemove: (value) => setState(
                              () => _removeDislikedIngredient(value),
                            ),
                            onSuggestionTap: (value) =>
                                setState(() => _addDislikedIngredient(value)),
                          ),
                          const SizedBox(height: 22),
                          Container(
                            padding: const EdgeInsets.all(18),
                            decoration: BoxDecoration(
                              color: PairlyColors.surfaceLow,
                              borderRadius: BorderRadius.circular(24),
                            ),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: <Widget>[
                                Container(
                                  width: 42,
                                  height: 42,
                                  decoration: BoxDecoration(
                                    color: PairlyColors.primary.withValues(
                                      alpha: 0.12,
                                    ),
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  child: const Icon(
                                    Icons.verified_outlined,
                                    color: PairlyColors.primary,
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: <Widget>[
                                      Text(
                                        'Food-focused by design',
                                        style: theme.textTheme.titleMedium,
                                      ),
                                      const SizedBox(height: 6),
                                      Text(
                                        'This account area is intentionally narrow: pantry taste, cuisines, dietary patterns, and measurement defaults. That keeps the product on the food discovery side of the line.',
                                        style: theme.textTheme.bodyMedium,
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 22),
                          FilledButton(
                            onPressed:
                                _isSavingPreferences || !_hasPreferenceChanges
                                ? null
                                : _savePreferences,
                            child: _isSavingPreferences
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.4,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Text('Save preferences'),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),
                    TextButton.icon(
                      onPressed: _isRefreshing ? null : _refreshFromServer,
                      icon: _isRefreshing
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.refresh_rounded),
                      label: const Text('Refresh account data'),
                    ),
                  ],
                ),
              ),
            ),
          ),
          bottomNavigationBar: PairlyBottomDock(
            activeDestination: PairlyDockDestination.profile,
            onSelected: (destination) {
              switch (destination) {
                case PairlyDockDestination.discover:
                  Navigator.maybePop(context);
                case PairlyDockDestination.profile:
                  break;
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
            },
          ),
        );
      },
    );
  }
}

class _ProfileTopBar extends StatelessWidget {
  const _ProfileTopBar({required this.onBack, required this.onSignOut});

  final VoidCallback onBack;
  final Future<void> Function() onSignOut;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      children: <Widget>[
        IconButton.filledTonal(
          onPressed: onBack,
          style: IconButton.styleFrom(
            backgroundColor: PairlyColors.surfaceHigh,
            foregroundColor: PairlyColors.primary,
          ),
          icon: const Icon(Icons.arrow_back_rounded),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text('Pairly', style: theme.textTheme.titleLarge),
              Text(
                'Kitchen profile',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: PairlyColors.outline,
                ),
              ),
            ],
          ),
        ),
        IconButton(
          onPressed: onSignOut,
          tooltip: 'Sign out',
          color: PairlyColors.primary,
          icon: const Icon(Icons.logout_rounded),
        ),
      ],
    );
  }
}

class _ProfileHeroCard extends StatelessWidget {
  const _ProfileHeroCard({
    required this.user,
    required this.dietaryCount,
    required this.preferredCuisineCount,
    required this.measurementSystem,
  });

  final AppUser user;
  final int dietaryCount;
  final int preferredCuisineCount;
  final String measurementSystem;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final displayName = user.profile.displayName.trim().isEmpty
        ? 'Pairly Cook'
        : user.profile.displayName.trim();

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
            color: PairlyColors.primary.withValues(alpha: 0.2),
            blurRadius: 28,
            offset: const Offset(0, 18),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Container(
                width: 58,
                height: 58,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(22),
                ),
                child: Text(
                  displayName.characters.first.toUpperCase(),
                  style: theme.textTheme.headlineMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      displayName,
                      style: theme.textTheme.headlineMedium?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      user.email,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: Colors.white.withValues(alpha: 0.88),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            user.profile.bio?.trim().isNotEmpty == true
                ? user.profile.bio!
                : 'Build a calm kitchen profile that keeps discovery personal, food-led, and grounded in what you actually like cooking.',
            style: theme.textTheme.bodyLarge?.copyWith(
              color: Colors.white.withValues(alpha: 0.94),
            ),
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: <Widget>[
              _HeroFact(label: _presentLabel(user.status)),
              _HeroFact(label: '${_presentLabel(measurementSystem)} units'),
              _HeroFact(
                label:
                    '$dietaryCount dietary ${dietaryCount == 1 ? 'pick' : 'picks'}',
              ),
              _HeroFact(
                label:
                    '$preferredCuisineCount cuisine ${preferredCuisineCount == 1 ? 'signal' : 'signals'}',
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _HeroFact extends StatelessWidget {
  const _HeroFact({required this.label});

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

class _SectionPanel extends StatelessWidget {
  const _SectionPanel({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: PairlyColors.surfaceCard.withValues(alpha: 0.94),
        borderRadius: BorderRadius.circular(32),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: PairlyColors.primary.withValues(alpha: 0.06),
            blurRadius: 34,
            offset: const Offset(0, 20),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _FeedbackBanner extends StatelessWidget {
  const _FeedbackBanner({
    required this.message,
    required this.tint,
    required this.textColor,
    this.details = const <String>[],
  });

  final String message;
  final Color tint;
  final Color textColor;
  final List<String> details;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: tint,
        borderRadius: BorderRadius.circular(22),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            message,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: textColor,
              fontWeight: FontWeight.w700,
            ),
          ),
          if (details.isNotEmpty) ...<Widget>[
            const SizedBox(height: 8),
            ...details
                .take(3)
                .map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Text(
                      '• $item',
                      style: Theme.of(
                        context,
                      ).textTheme.bodySmall?.copyWith(color: textColor),
                    ),
                  ),
                ),
          ],
        ],
      ),
    );
  }
}

class _LabeledInput extends StatelessWidget {
  const _LabeledInput({required this.label, required this.child});

  final String label;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(label, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 10),
        child,
      ],
    );
  }
}

class _AdaptiveFields extends StatelessWidget {
  const _AdaptiveFields({required this.leading, required this.trailing});

  final Widget leading;
  final Widget trailing;

  @override
  Widget build(BuildContext context) {
    if (MediaQuery.sizeOf(context).width < 680) {
      return Column(
        children: <Widget>[leading, const SizedBox(height: 18), trailing],
      );
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Expanded(child: leading),
        const SizedBox(width: 16),
        Expanded(child: trailing),
      ],
    );
  }
}

class _TokenComposer extends StatelessWidget {
  const _TokenComposer({
    required this.title,
    required this.hintText,
    required this.helper,
    required this.values,
    required this.inputController,
    required this.suggestions,
    required this.onChanged,
    required this.onSubmitted,
    required this.onRemove,
    required this.onSuggestionTap,
  });

  final String title;
  final String hintText;
  final String helper;
  final List<String> values;
  final TextEditingController inputController;
  final List<String> suggestions;
  final VoidCallback onChanged;
  final ValueChanged<String> onSubmitted;
  final ValueChanged<String> onRemove;
  final ValueChanged<String> onSuggestionTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isNarrow = MediaQuery.sizeOf(context).width < 640;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(title, style: theme.textTheme.titleMedium),
        const SizedBox(height: 10),
        Text(
          helper,
          style: theme.textTheme.bodySmall?.copyWith(
            color: PairlyColors.outline,
          ),
        ),
        const SizedBox(height: 12),
        if (values.isNotEmpty) ...<Widget>[
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: values
                .map(
                  (value) => Chip(
                    label: Text(value),
                    backgroundColor: PairlyColors.surfaceLow,
                    onDeleted: () => onRemove(value),
                    deleteIconColor: PairlyColors.outline,
                    side: BorderSide.none,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                )
                .toList(),
          ),
          const SizedBox(height: 12),
        ],
        if (isNarrow)
          Column(
            children: <Widget>[
              TextField(
                controller: inputController,
                onChanged: (_) => onChanged(),
                onSubmitted: onSubmitted,
                decoration: InputDecoration(
                  hintText: hintText,
                  prefixIcon: const Icon(Icons.add_circle_outline_rounded),
                ),
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: FilledButton.tonal(
                  onPressed: () => onSubmitted(inputController.text),
                  style: FilledButton.styleFrom(
                    minimumSize: const Size.fromHeight(54),
                    backgroundColor: PairlyColors.surfaceLow,
                    foregroundColor: PairlyColors.primary,
                  ),
                  child: const Text('Add'),
                ),
              ),
            ],
          )
        else
          Row(
            children: <Widget>[
              Expanded(
                child: TextField(
                  controller: inputController,
                  onChanged: (_) => onChanged(),
                  onSubmitted: onSubmitted,
                  decoration: InputDecoration(
                    hintText: hintText,
                    prefixIcon: const Icon(Icons.add_circle_outline_rounded),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              FilledButton.tonal(
                onPressed: () => onSubmitted(inputController.text),
                style: FilledButton.styleFrom(
                  minimumSize: const Size(86, 58),
                  backgroundColor: PairlyColors.surfaceLow,
                  foregroundColor: PairlyColors.primary,
                ),
                child: const Text('Add'),
              ),
            ],
          ),
        if (suggestions.isNotEmpty) ...<Widget>[
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: suggestions
                .map(
                  (value) => ActionChip(
                    backgroundColor: PairlyColors.surfaceHigh,
                    label: Text(value),
                    onPressed: () => onSuggestionTap(value),
                  ),
                )
                .toList(),
          ),
        ],
      ],
    );
  }
}

String? _blankToNull(String value) {
  final trimmed = value.trim();
  return trimmed.isEmpty ? null : trimmed;
}

String? _normalizeChipValue(String raw, {bool lowercase = false}) {
  final trimmed = raw.trim();
  if (trimmed.isEmpty) {
    return null;
  }

  final normalized = lowercase ? trimmed.toLowerCase() : trimmed;
  return normalized;
}

List<String> _normalizeStringList(
  List<String> values, {
  bool lowercase = false,
}) {
  final seen = <String>{};
  final result = <String>[];

  for (final value in values) {
    final normalized = _normalizeChipValue(value, lowercase: lowercase);
    if (normalized == null || seen.contains(normalized)) {
      continue;
    }

    seen.add(normalized);
    result.add(normalized);
  }

  return result;
}

bool _sameList(List<String> left, List<String> right) {
  if (left.length != right.length) {
    return false;
  }

  for (var index = 0; index < left.length; index += 1) {
    if (left[index] != right[index]) {
      return false;
    }
  }

  return true;
}

String _presentLabel(String raw) {
  return raw
      .split('_')
      .where((segment) => segment.isNotEmpty)
      .map((segment) {
        return '${segment[0].toUpperCase()}${segment.substring(1)}';
      })
      .join(' ');
}
