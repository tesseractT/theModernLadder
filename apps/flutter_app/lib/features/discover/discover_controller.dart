import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_app/core/api/api_client.dart';
import 'package:flutter_app/core/api/api_exception.dart';
import 'package:flutter_app/core/models/app_models.dart';

enum GoalOption {
  quickBite('snack', 'Quick bite', Icons.bolt_rounded),
  breakfast('breakfast', 'Breakfast', Icons.wb_sunny_outlined),
  drink('drink', 'Drink', Icons.local_bar_outlined),
  lightMeal('light_meal', 'Light meal', Icons.eco_outlined),
  dessert('dessert', 'Dessert', Icons.icecream_outlined);

  const GoalOption(this.apiValue, this.label, this.icon);

  final String apiValue;
  final String label;
  final IconData icon;
}

class DiscoverController extends ChangeNotifier {
  DiscoverController({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  final List<String> quickAddDefaults = const <String>[
    'eggs',
    'oats',
    'pineapple',
    'yogurt',
    'spinach',
    'lime',
  ];

  Timer? _searchDebounce;
  bool _initialized = false;

  bool isLoading = false;
  bool isSearching = false;
  bool isGenerating = false;
  String? loadError;
  String? suggestionsMessage;
  String searchQuery = '';
  GoalOption? selectedGoal;

  List<PantryItem> pantryItems = const <PantryItem>[];
  List<IngredientLookup> ingredientResults = const <IngredientLookup>[];
  List<SuggestionCandidate> suggestions = const <SuggestionCandidate>[];

  bool get hasPantry => pantryItems.isNotEmpty;
  bool get hasSuggestions => suggestions.isNotEmpty;

  Future<void> initialize() async {
    if (_initialized) {
      return;
    }

    _initialized = true;
    await refresh();
  }

  Future<void> refresh() async {
    isLoading = true;
    loadError = null;
    notifyListeners();

    try {
      final response = await _apiClient.get(
        '/me/pantry',
        query: const <String, String>{'per_page': '12'},
      );
      final json = Map<String, dynamic>.from(response as Map);
      pantryItems = _mapList(json['data']).map(PantryItem.fromJson).toList();

      if (pantryItems.isNotEmpty) {
        await generateSuggestions(silent: true);
      } else {
        suggestions = const <SuggestionCandidate>[];
        suggestionsMessage = null;
      }
    } on ApiException catch (error) {
      loadError = error.message;
    } catch (_) {
      loadError = 'Unable to load pantry data right now.';
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  void onSearchChanged(String value) {
    searchQuery = value;
    _searchDebounce?.cancel();

    if (value.trim().length < 2) {
      ingredientResults = const <IngredientLookup>[];
      isSearching = false;
      notifyListeners();
      return;
    }

    isSearching = true;
    notifyListeners();

    _searchDebounce = Timer(const Duration(milliseconds: 280), () async {
      try {
        final response = await _apiClient.get(
          '/ingredients/search',
          query: <String, String>{'q': value.trim(), 'limit': '6'},
        );
        final json = Map<String, dynamic>.from(response as Map);
        ingredientResults = _mapList(
          json['data'],
        ).map(IngredientLookup.fromJson).toList();
      } on ApiException catch (error) {
        loadError = error.message;
      } catch (_) {
        loadError = 'Unable to search ingredients right now.';
      } finally {
        isSearching = false;
        notifyListeners();
      }
    });
  }

  Future<void> addIngredient(IngredientLookup ingredient) async {
    try {
      await _apiClient.post(
        '/me/pantry',
        body: <String, dynamic>{'ingredient_id': ingredient.id},
      );
      searchQuery = '';
      ingredientResults = const <IngredientLookup>[];
      await refresh();
    } on ApiException catch (error) {
      if (error.statusCode == 422) {
        loadError = '${ingredient.name} is already in your pantry.';
      } else {
        loadError = error.message;
      }
      notifyListeners();
    }
  }

  Future<void> addQuickIngredient(String query) async {
    final response = await _apiClient.get(
      '/ingredients/search',
      query: <String, String>{'q': query, 'limit': '1'},
    );
    final json = Map<String, dynamic>.from(response as Map);
    final results = _mapList(
      json['data'],
    ).map(IngredientLookup.fromJson).toList();

    if (results.isEmpty) {
      loadError = 'No ingredient match found for $query.';
      notifyListeners();
      return;
    }

    await addIngredient(results.first);
  }

  Future<void> removePantryItem(PantryItem item) async {
    try {
      await _apiClient.delete('/me/pantry/${item.id}');
      await refresh();
    } on ApiException catch (error) {
      loadError = error.message;
      notifyListeners();
    }
  }

  Future<void> setGoal(GoalOption goal) async {
    selectedGoal = selectedGoal == goal ? null : goal;
    notifyListeners();

    if (hasPantry) {
      await generateSuggestions(silent: true);
    }
  }

  Future<void> generateSuggestions({bool silent = false}) async {
    if (!hasPantry) {
      suggestions = const <SuggestionCandidate>[];
      suggestionsMessage = 'Add pantry ingredients to get started.';
      notifyListeners();
      return;
    }

    isGenerating = !silent;
    loadError = null;
    notifyListeners();

    final body = <String, dynamic>{
      'limit': 5,
      'include_substitutions': true,
      if (selectedGoal != null) 'goal': selectedGoal!.apiValue,
    };

    try {
      final response = await _apiClient.post('/me/suggestions', body: body);
      final json = Map<String, dynamic>.from(response as Map);
      suggestions = _mapList(
        json['candidates'],
      ).map(SuggestionCandidate.fromJson).toList();
      suggestionsMessage = json['message']?.toString();
    } on ApiException catch (error) {
      loadError = error.message;
    } catch (_) {
      loadError = 'Unable to generate ideas right now.';
    } finally {
      isGenerating = false;
      notifyListeners();
    }
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    super.dispose();
  }
}

List<Map<String, dynamic>> _mapList(dynamic value) {
  if (value is! List) {
    return const <Map<String, dynamic>>[];
  }

  return value.map((item) => Map<String, dynamic>.from(item as Map)).toList();
}
