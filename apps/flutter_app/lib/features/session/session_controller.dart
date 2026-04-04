import 'package:flutter/foundation.dart';
import 'package:flutter_app/core/api/api_client.dart';
import 'package:flutter_app/core/api/app_config.dart';
import 'package:flutter_app/core/models/app_models.dart';

class SessionController extends ChangeNotifier {
  SessionController({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  AppUser? _currentUser;
  String? _token;
  bool _isBusy = false;

  AppUser? get currentUser => _currentUser;
  bool get isBusy => _isBusy;
  bool get isAuthenticated => _token != null && _currentUser != null;
  ApiClient get apiClient => _apiClient;

  Future<void> refreshCurrentUser() async {
    if (_token == null) {
      return;
    }

    final response = await _apiClient.get('/me');
    _currentUser = _parseUserEnvelope(response);
    notifyListeners();
  }

  Future<AppUser> updateProfile({
    required String displayName,
    String? bio,
    required String locale,
    String? timezone,
    String? countryCode,
  }) async {
    final response = await _apiClient.patch(
      '/me/profile',
      body: <String, dynamic>{
        'display_name': displayName,
        'bio': bio,
        'locale': locale,
        'timezone': timezone,
        'country_code': countryCode,
      },
    );

    final user = _parseUserEnvelope(response);
    _currentUser = user;
    notifyListeners();
    return user;
  }

  Future<AppUser> updatePreferences({
    required List<String> dietaryPatterns,
    required List<String> preferredCuisines,
    required List<String> dislikedIngredients,
    required String measurementSystem,
  }) async {
    final response = await _apiClient.patch(
      '/me/preferences',
      body: <String, dynamic>{
        'dietary_patterns': dietaryPatterns,
        'preferred_cuisines': preferredCuisines,
        'disliked_ingredients': dislikedIngredients,
        'measurement_system': measurementSystem,
      },
    );

    final user = _parseUserEnvelope(response);
    _currentUser = user;
    notifyListeners();
    return user;
  }

  Future<void> signIn({required String email, required String password}) async {
    await _authenticate(
      path: '/auth/login',
      payload: <String, dynamic>{
        'email': email,
        'password': password,
        'device_name': AppConfig.defaultDeviceName,
      },
    );
  }

  Future<void> signUp({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _authenticate(
      path: '/auth/register',
      payload: <String, dynamic>{
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'device_name': AppConfig.defaultDeviceName,
      },
    );
  }

  Future<void> signOut({bool revokeRemote = true}) async {
    if (revokeRemote && _token != null) {
      try {
        await _apiClient.post('/auth/logout');
      } catch (_) {
        // Keep logout resilient even if the network or token is stale.
      }
    }

    _token = null;
    _currentUser = null;
    _apiClient.authToken = null;
    notifyListeners();
  }

  Future<void> _authenticate({
    required String path,
    required Map<String, dynamic> payload,
  }) async {
    _isBusy = true;
    notifyListeners();

    try {
      final response = await _apiClient.post(path, body: payload);
      final json = Map<String, dynamic>.from(response as Map);
      _token = json['token']?.toString();
      _currentUser = _parseUserEnvelope(response);
      _apiClient.authToken = _token;
    } finally {
      _isBusy = false;
      notifyListeners();
    }
  }

  AppUser _parseUserEnvelope(dynamic response) {
    final json = Map<String, dynamic>.from(response as Map);

    return AppUser.fromJson(Map<String, dynamic>.from(json['user'] as Map));
  }
}
