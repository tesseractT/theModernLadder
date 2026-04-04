import 'package:flutter/material.dart';
import 'package:flutter_app/app/pairly_theme.dart';
import 'package:flutter_app/core/api/api_client.dart';
import 'package:flutter_app/core/api/app_config.dart';
import 'package:flutter_app/features/auth/auth_screen.dart';
import 'package:flutter_app/features/discover/discover_screen.dart';
import 'package:flutter_app/features/session/session_controller.dart';

class PairlyBootstrap extends StatefulWidget {
  const PairlyBootstrap({super.key});

  @override
  State<PairlyBootstrap> createState() => _PairlyBootstrapState();
}

class _PairlyBootstrapState extends State<PairlyBootstrap> {
  late final ApiClient _apiClient;
  late final SessionController _sessionController;

  @override
  void initState() {
    super.initState();
    _apiClient = ApiClient(baseUrl: AppConfig.apiBaseUrl);
    _sessionController = SessionController(apiClient: _apiClient);
  }

  @override
  void dispose() {
    _sessionController.dispose();
    _apiClient.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _sessionController,
      builder: (context, _) {
        return MaterialApp(
          debugShowCheckedModeBanner: false,
          title: AppConfig.appName,
          theme: PairlyTheme.light(),
          home: AnimatedSwitcher(
            duration: const Duration(milliseconds: 280),
            switchInCurve: Curves.easeOutCubic,
            switchOutCurve: Curves.easeInCubic,
            child: _sessionController.isAuthenticated
                ? DiscoverScreen(
                    key: const ValueKey('discover'),
                    sessionController: _sessionController,
                  )
                : AuthScreen(
                    key: const ValueKey('auth'),
                    sessionController: _sessionController,
                  ),
          ),
        );
      },
    );
  }
}
