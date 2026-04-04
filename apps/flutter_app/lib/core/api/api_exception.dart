class ApiException implements Exception {
  const ApiException({
    required this.statusCode,
    required this.message,
    this.errors = const <String, List<String>>{},
  });

  final int statusCode;
  final String message;
  final Map<String, List<String>> errors;

  bool get isUnauthorized => statusCode == 401;
  bool get isValidationError => statusCode == 422;

  String? firstFieldError(String field) => errors[field]?.first;

  factory ApiException.fromPayload(int statusCode, dynamic payload) {
    final map = payload is Map
        ? Map<String, dynamic>.from(payload)
        : <String, dynamic>{};

    final rawErrors = map['errors'];
    final normalizedErrors = <String, List<String>>{};

    if (rawErrors is Map) {
      for (final entry in rawErrors.entries) {
        final value = entry.value;
        if (value is List) {
          normalizedErrors['${entry.key}'] = value
              .map((item) => '$item')
              .toList();
        } else if (value != null) {
          normalizedErrors['${entry.key}'] = ['$value'];
        }
      }
    }

    return ApiException(
      statusCode: statusCode,
      message: map['message']?.toString() ?? 'Request failed.',
      errors: normalizedErrors,
    );
  }

  @override
  String toString() => 'ApiException($statusCode, $message)';
}
