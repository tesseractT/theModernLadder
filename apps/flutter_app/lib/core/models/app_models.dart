class AppUser {
  const AppUser({
    required this.id,
    required this.email,
    required this.status,
    required this.profile,
    required this.preferences,
  });

  final String id;
  final String email;
  final String status;
  final UserProfile profile;
  final UserFoodPreferences preferences;

  factory AppUser.fromJson(Map<String, dynamic> json) {
    return AppUser(
      id: _string(json['id']),
      email: _string(json['email']),
      status: _string(json['status']),
      profile: UserProfile.fromJson(_map(json['profile'])),
      preferences: UserFoodPreferences.fromJson(_map(json['preferences'])),
    );
  }
}

class UserProfile {
  const UserProfile({
    required this.displayName,
    required this.bio,
    required this.locale,
    required this.timezone,
    required this.countryCode,
  });

  final String displayName;
  final String? bio;
  final String? locale;
  final String? timezone;
  final String? countryCode;

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      displayName: _string(json['display_name']),
      bio: _nullableString(json['bio']),
      locale: _nullableString(json['locale']),
      timezone: _nullableString(json['timezone']),
      countryCode: _nullableString(json['country_code']),
    );
  }
}

class UserFoodPreferences {
  const UserFoodPreferences({
    required this.dietaryPatterns,
    required this.preferredCuisines,
    required this.dislikedIngredients,
    required this.measurementSystem,
  });

  final List<String> dietaryPatterns;
  final List<String> preferredCuisines;
  final List<String> dislikedIngredients;
  final String measurementSystem;

  factory UserFoodPreferences.fromJson(Map<String, dynamic> json) {
    return UserFoodPreferences(
      dietaryPatterns: _stringList(json['dietary_patterns']),
      preferredCuisines: _stringList(json['preferred_cuisines']),
      dislikedIngredients: _stringList(json['disliked_ingredients']),
      measurementSystem: _string(
        json['measurement_system'],
        fallback: 'metric',
      ),
    );
  }
}

class IngredientLookup {
  const IngredientLookup({
    required this.id,
    required this.name,
    required this.slug,
    required this.description,
    required this.matchedAlias,
  });

  final String id;
  final String name;
  final String slug;
  final String? description;
  final String? matchedAlias;

  factory IngredientLookup.fromJson(Map<String, dynamic> json) {
    return IngredientLookup(
      id: _string(json['id']),
      name: _string(json['name']),
      slug: _string(json['slug']),
      description: _nullableString(json['description']),
      matchedAlias: _nullableString(json['matched_alias']),
    );
  }
}

class PantryItem {
  const PantryItem({
    required this.id,
    required this.ingredient,
    required this.enteredName,
    required this.status,
    this.quantity,
    this.unit,
    this.note,
    this.expiresOn,
  });

  final String id;
  final IngredientLookup ingredient;
  final String enteredName;
  final String status;
  final double? quantity;
  final String? unit;
  final String? note;
  final String? expiresOn;

  factory PantryItem.fromJson(Map<String, dynamic> json) {
    return PantryItem(
      id: _string(json['id']),
      ingredient: IngredientLookup.fromJson(_map(json['ingredient'])),
      enteredName: _string(json['entered_name']),
      status: _string(json['status']),
      quantity: _nullableDouble(json['quantity']),
      unit: _nullableString(json['unit']),
      note: _nullableString(json['note']),
      expiresOn: _nullableString(json['expires_on']),
    );
  }
}

class SuggestionCandidate {
  const SuggestionCandidate({
    required this.recipeTemplateId,
    required this.title,
    required this.summary,
    required this.suggestionType,
    required this.score,
    required this.requiredMatched,
    required this.requiredTotal,
    required this.missingRequiredCount,
    required this.substitutionCoveredCount,
    required this.matchedIngredientNames,
    required this.missingIngredientNames,
    required this.reasonCodes,
  });

  final String recipeTemplateId;
  final String title;
  final String summary;
  final String suggestionType;
  final int score;
  final int requiredMatched;
  final int requiredTotal;
  final int missingRequiredCount;
  final int substitutionCoveredCount;
  final List<String> matchedIngredientNames;
  final List<String> missingIngredientNames;
  final List<String> reasonCodes;

  bool get isReadyNow => missingRequiredCount == 0;

  factory SuggestionCandidate.fromJson(Map<String, dynamic> json) {
    final matchSummary = _map(json['match_summary']);

    return SuggestionCandidate(
      recipeTemplateId: _string(json['recipe_template_id']),
      title: _string(json['title']),
      summary: _string(json['summary']),
      suggestionType: _string(json['suggestion_type'], fallback: 'general'),
      score: _int(json['score']),
      requiredMatched: _int(matchSummary['required_matched']),
      requiredTotal: _int(matchSummary['required_total']),
      missingRequiredCount: _int(matchSummary['missing_required_count']),
      substitutionCoveredCount: _int(
        matchSummary['substitution_covered_missing_count'],
      ),
      matchedIngredientNames: _mapList(json['matched_ingredients'])
          .map((item) => _string(_map(item['ingredient'])['name']))
          .where((name) => name.isNotEmpty)
          .toList(),
      missingIngredientNames: _mapList(json['missing_ingredients'])
          .map((item) => _string(_map(item['ingredient'])['name']))
          .where((name) => name.isNotEmpty)
          .toList(),
      reasonCodes: _stringList(json['reason_codes']),
    );
  }
}

class RecipeTemplateDetail {
  const RecipeTemplateDetail({
    required this.template,
    required this.pantryFit,
    required this.requiredIngredients,
    required this.optionalIngredients,
    required this.steps,
    required this.substitutions,
  });

  final RecipeTemplateInfo template;
  final PantryFit pantryFit;
  final List<RecipeIngredientState> requiredIngredients;
  final List<RecipeIngredientState> optionalIngredients;
  final List<RecipeStepItem> steps;
  final List<RecipeSubstitutionGroup> substitutions;

  factory RecipeTemplateDetail.fromJson(Map<String, dynamic> json) {
    final ingredients = _map(json['ingredients']);

    return RecipeTemplateDetail(
      template: RecipeTemplateInfo.fromJson(_map(json['template'])),
      pantryFit: PantryFit.fromJson(_map(json['pantry_fit'])),
      requiredIngredients: _mapList(
        ingredients['required'],
      ).map(RecipeIngredientState.fromJson).toList(),
      optionalIngredients: _mapList(
        ingredients['optional'],
      ).map(RecipeIngredientState.fromJson).toList(),
      steps: _mapList(json['steps']).map(RecipeStepItem.fromJson).toList(),
      substitutions: _mapList(
        json['substitutions'],
      ).map(RecipeSubstitutionGroup.fromJson).toList(),
    );
  }
}

class RecipeTemplateInfo {
  const RecipeTemplateInfo({
    required this.id,
    required this.slug,
    required this.title,
    required this.recipeType,
    required this.difficulty,
    required this.summary,
    required this.dietaryPatterns,
    required this.servings,
    required this.prepMinutes,
    required this.cookMinutes,
    required this.totalMinutes,
  });

  final String id;
  final String slug;
  final String title;
  final String recipeType;
  final String difficulty;
  final String summary;
  final List<String> dietaryPatterns;
  final int? servings;
  final int? prepMinutes;
  final int? cookMinutes;
  final int? totalMinutes;

  factory RecipeTemplateInfo.fromJson(Map<String, dynamic> json) {
    return RecipeTemplateInfo(
      id: _string(json['id']),
      slug: _string(json['slug']),
      title: _string(json['title']),
      recipeType: _string(json['recipe_type'], fallback: 'general'),
      difficulty: _string(json['difficulty'], fallback: 'unknown'),
      summary: _string(json['summary']),
      dietaryPatterns: _stringList(json['dietary_patterns']),
      servings: _nullableInt(json['servings']),
      prepMinutes: _nullableInt(json['prep_minutes']),
      cookMinutes: _nullableInt(json['cook_minutes']),
      totalMinutes: _nullableInt(json['total_minutes']),
    );
  }
}

class PantryFit {
  const PantryFit({
    required this.requiredTotal,
    required this.requiredOwned,
    required this.requiredMissing,
    required this.optionalTotal,
    required this.optionalOwned,
    required this.optionalMissing,
    required this.substitutionCoveredRequiredMissing,
    required this.canMakeWithCurrentPantry,
    required this.canMakeAfterSubstitutions,
  });

  final int requiredTotal;
  final int requiredOwned;
  final int requiredMissing;
  final int optionalTotal;
  final int optionalOwned;
  final int optionalMissing;
  final int substitutionCoveredRequiredMissing;
  final bool canMakeWithCurrentPantry;
  final bool canMakeAfterSubstitutions;

  factory PantryFit.fromJson(Map<String, dynamic> json) {
    return PantryFit(
      requiredTotal: _int(json['required_total']),
      requiredOwned: _int(json['required_owned']),
      requiredMissing: _int(json['required_missing']),
      optionalTotal: _int(json['optional_total']),
      optionalOwned: _int(json['optional_owned']),
      optionalMissing: _int(json['optional_missing']),
      substitutionCoveredRequiredMissing: _int(
        json['substitution_covered_required_missing'],
      ),
      canMakeWithCurrentPantry: _bool(json['can_make_with_current_pantry']),
      canMakeAfterSubstitutions: _bool(json['can_make_after_substitutions']),
    );
  }
}

class RecipeIngredientState {
  const RecipeIngredientState({
    required this.position,
    required this.ingredient,
    required this.isRequired,
    required this.isOwned,
    required this.pantryItemId,
    required this.substitutions,
  });

  final int position;
  final IngredientLookup ingredient;
  final bool isRequired;
  final bool isOwned;
  final String? pantryItemId;
  final List<IngredientLookup> substitutions;

  factory RecipeIngredientState.fromJson(Map<String, dynamic> json) {
    return RecipeIngredientState(
      position: _int(json['position']),
      ingredient: IngredientLookup.fromJson(_map(json['ingredient'])),
      isRequired: _bool(json['is_required']),
      isOwned: _bool(json['is_owned']),
      pantryItemId: _nullableString(json['pantry_item_id']),
      substitutions: _mapList(json['substitutions'])
          .map((item) => IngredientLookup.fromJson(_map(item['ingredient'])))
          .toList(),
    );
  }
}

class RecipeStepItem {
  const RecipeStepItem({required this.position, required this.instruction});

  final int position;
  final String instruction;

  factory RecipeStepItem.fromJson(Map<String, dynamic> json) {
    return RecipeStepItem(
      position: _int(json['position']),
      instruction: _string(json['instruction']),
    );
  }
}

class RecipeSubstitutionGroup {
  const RecipeSubstitutionGroup({
    required this.forIngredient,
    required this.availableSubstitutes,
  });

  final IngredientLookup forIngredient;
  final List<IngredientLookup> availableSubstitutes;

  factory RecipeSubstitutionGroup.fromJson(Map<String, dynamic> json) {
    return RecipeSubstitutionGroup(
      forIngredient: IngredientLookup.fromJson(_map(json['for_ingredient'])),
      availableSubstitutes: _mapList(json['available_substitutes'])
          .map((item) => IngredientLookup.fromJson(_map(item['ingredient'])))
          .toList(),
    );
  }
}

class RecipeExplanation {
  const RecipeExplanation({
    required this.source,
    required this.headline,
    required this.whyItFits,
    required this.tasteProfile,
    required this.textureProfile,
    required this.substitutionGuidance,
    required this.quickTakeaways,
    required this.followUpOptions,
  });

  final String source;
  final String headline;
  final String whyItFits;
  final String tasteProfile;
  final String textureProfile;
  final List<String> substitutionGuidance;
  final List<String> quickTakeaways;
  final List<ExplanationOption> followUpOptions;

  factory RecipeExplanation.fromJson(Map<String, dynamic> json) {
    final explanation = _map(json['explanation']);

    return RecipeExplanation(
      source: _string(json['source']),
      headline: _string(explanation['headline']),
      whyItFits: _string(explanation['why_it_fits']),
      tasteProfile: _string(explanation['taste_profile']),
      textureProfile: _string(explanation['texture_profile']),
      substitutionGuidance: _stringList(explanation['substitution_guidance']),
      quickTakeaways: _stringList(explanation['quick_takeaways']),
      followUpOptions: _mapList(
        explanation['follow_up_options'],
      ).map(ExplanationOption.fromJson).toList(),
    );
  }
}

class ExplanationOption {
  const ExplanationOption({required this.key, required this.label});

  final String key;
  final String label;

  factory ExplanationOption.fromJson(Map<String, dynamic> json) {
    return ExplanationOption(
      key: _string(json['key']),
      label: _string(json['label']),
    );
  }
}

String _string(dynamic value, {String fallback = ''}) {
  if (value == null) {
    return fallback;
  }

  return value.toString();
}

String? _nullableString(dynamic value) {
  final normalized = _string(value);
  return normalized.isEmpty ? null : normalized;
}

bool _bool(dynamic value) {
  if (value is bool) {
    return value;
  }

  if (value is num) {
    return value != 0;
  }

  return '$value'.toLowerCase() == 'true';
}

int _int(dynamic value) {
  if (value is int) {
    return value;
  }

  if (value is num) {
    return value.toInt();
  }

  return int.tryParse('$value') ?? 0;
}

int? _nullableInt(dynamic value) {
  if (value == null) {
    return null;
  }

  if (value is int) {
    return value;
  }

  if (value is num) {
    return value.toInt();
  }

  return int.tryParse('$value');
}

double? _nullableDouble(dynamic value) {
  if (value == null) {
    return null;
  }

  if (value is double) {
    return value;
  }

  if (value is num) {
    return value.toDouble();
  }

  return double.tryParse('$value');
}

Map<String, dynamic> _map(dynamic value) {
  if (value is Map<String, dynamic>) {
    return value;
  }

  if (value is Map) {
    return Map<String, dynamic>.from(value);
  }

  return <String, dynamic>{};
}

List<Map<String, dynamic>> _mapList(dynamic value) {
  if (value is! List) {
    return const <Map<String, dynamic>>[];
  }

  return value.map((item) => _map(item)).toList();
}

List<String> _stringList(dynamic value) {
  if (value is! List) {
    return const <String>[];
  }

  return value
      .map((item) => _string(item))
      .where((item) => item.isNotEmpty)
      .toList();
}
