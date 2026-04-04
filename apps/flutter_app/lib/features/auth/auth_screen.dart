import 'package:flutter/material.dart';
import 'package:flutter_app/app/pairly_theme.dart';
import 'package:flutter_app/core/api/api_exception.dart';
import 'package:flutter_app/features/session/session_controller.dart';

class AuthScreen extends StatefulWidget {
  const AuthScreen({super.key, required this.sessionController});

  final SessionController sessionController;

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordConfirmationController = TextEditingController();

  bool _isRegisterMode = false;
  bool _obscurePassword = true;
  String? _formError;
  Map<String, List<String>> _fieldErrors = const <String, List<String>>{};

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _passwordConfirmationController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    setState(() {
      _formError = null;
      _fieldErrors = const <String, List<String>>{};
    });

    try {
      if (_isRegisterMode) {
        await widget.sessionController.signUp(
          name: _nameController.text.trim(),
          email: _emailController.text.trim(),
          password: _passwordController.text,
          passwordConfirmation: _passwordConfirmationController.text,
        );
      } else {
        await widget.sessionController.signIn(
          email: _emailController.text.trim(),
          password: _passwordController.text,
        );
      }
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }

      setState(() {
        _formError = error.message;
        _fieldErrors = error.errors;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }

      setState(() {
        _formError = 'Unable to reach the backend right now.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: <Color>[
              PairlyColors.surface,
              PairlyColors.surfaceLow.withValues(alpha: 0.96),
              PairlyColors.surfaceCard,
            ],
          ),
        ),
        child: SafeArea(
          child: Stack(
            children: <Widget>[
              Positioned(
                top: -80,
                right: -40,
                child: _GlowOrb(
                  size: 220,
                  color: PairlyColors.primaryBright.withValues(alpha: 0.16),
                ),
              ),
              Positioned(
                bottom: 120,
                left: -60,
                child: _GlowOrb(
                  size: 180,
                  color: PairlyColors.secondary.withValues(alpha: 0.12),
                ),
              ),
              Align(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(24, 32, 24, 32),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 520),
                    child: Container(
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.8),
                        borderRadius: BorderRadius.circular(36),
                        border: Border.all(
                          color: PairlyColors.outlineSoft.withValues(
                            alpha: 0.35,
                          ),
                        ),
                        boxShadow: <BoxShadow>[
                          BoxShadow(
                            color: PairlyColors.primary.withValues(alpha: 0.08),
                            blurRadius: 48,
                            offset: const Offset(0, 24),
                          ),
                        ],
                      ),
                      padding: const EdgeInsets.fromLTRB(24, 24, 24, 28),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Row(
                            children: <Widget>[
                              Container(
                                width: 44,
                                height: 44,
                                decoration: BoxDecoration(
                                  color: PairlyColors.primary.withValues(
                                    alpha: 0.12,
                                  ),
                                  borderRadius: BorderRadius.circular(18),
                                ),
                                child: const Icon(
                                  Icons.restaurant_menu_rounded,
                                  color: PairlyColors.primary,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: <Widget>[
                                  Text(
                                    'Pairly',
                                    style: theme.textTheme.titleLarge,
                                  ),
                                  Text(
                                    'Pantry-led recipe discovery',
                                    style: theme.textTheme.bodySmall?.copyWith(
                                      color: PairlyColors.outline,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          const SizedBox(height: 28),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 14,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              color: PairlyColors.surfaceLow,
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              'FOOD DISCOVERY, NOT MEDICAL ADVICE',
                              style: theme.textTheme.labelSmall,
                            ),
                          ),
                          const SizedBox(height: 20),
                          Text(
                            _isRegisterMode
                                ? 'Start with what is already in your kitchen.'
                                : 'Sign in and turn pantry odds into polished ideas.',
                            style: theme.textTheme.headlineLarge?.copyWith(
                              fontSize: 34,
                              height: 1.05,
                            ),
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'The backend is already ready for auth, pantry, suggestions, recipe detail, and grounded explanations. This first client focuses on that exact flow.',
                            style: theme.textTheme.bodyLarge?.copyWith(
                              color: PairlyColors.inkSoft.withValues(
                                alpha: 0.86,
                              ),
                            ),
                          ),
                          const SizedBox(height: 28),
                          SegmentedButton<bool>(
                            showSelectedIcon: false,
                            segments: const <ButtonSegment<bool>>[
                              ButtonSegment<bool>(
                                value: false,
                                label: Text('Sign in'),
                              ),
                              ButtonSegment<bool>(
                                value: true,
                                label: Text('Create account'),
                              ),
                            ],
                            selected: <bool>{_isRegisterMode},
                            onSelectionChanged: (selection) {
                              setState(() {
                                _isRegisterMode = selection.first;
                                _formError = null;
                                _fieldErrors = const <String, List<String>>{};
                              });
                            },
                          ),
                          const SizedBox(height: 24),
                          if (_formError != null) ...<Widget>[
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: PairlyColors.error.withValues(
                                  alpha: 0.08,
                                ),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                _formError!,
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  color: PairlyColors.error,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            const SizedBox(height: 18),
                          ],
                          if (_isRegisterMode) ...<Widget>[
                            _LabeledField(
                              label: 'Display name',
                              child: TextField(
                                controller: _nameController,
                                textInputAction: TextInputAction.next,
                                decoration: InputDecoration(
                                  hintText: 'Casey Morgan',
                                  prefixIcon: const Icon(Icons.person_rounded),
                                  errorText: _fieldErrors['name']?.first,
                                ),
                              ),
                            ),
                            const SizedBox(height: 16),
                          ],
                          _LabeledField(
                            label: 'Email',
                            child: TextField(
                              controller: _emailController,
                              keyboardType: TextInputType.emailAddress,
                              textInputAction: TextInputAction.next,
                              decoration: InputDecoration(
                                hintText: 'you@example.com',
                                prefixIcon: const Icon(
                                  Icons.alternate_email_rounded,
                                ),
                                errorText: _fieldErrors['email']?.first,
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          _LabeledField(
                            label: 'Password',
                            child: TextField(
                              controller: _passwordController,
                              obscureText: _obscurePassword,
                              textInputAction: _isRegisterMode
                                  ? TextInputAction.next
                                  : TextInputAction.done,
                              decoration: InputDecoration(
                                hintText: 'Password123!',
                                prefixIcon: const Icon(
                                  Icons.lock_outline_rounded,
                                ),
                                errorText: _fieldErrors['password']?.first,
                                suffixIcon: IconButton(
                                  onPressed: () {
                                    setState(() {
                                      _obscurePassword = !_obscurePassword;
                                    });
                                  },
                                  icon: Icon(
                                    _obscurePassword
                                        ? Icons.visibility_off_rounded
                                        : Icons.visibility_rounded,
                                  ),
                                ),
                              ),
                            ),
                          ),
                          if (_isRegisterMode) ...<Widget>[
                            const SizedBox(height: 16),
                            _LabeledField(
                              label: 'Confirm password',
                              child: TextField(
                                controller: _passwordConfirmationController,
                                obscureText: _obscurePassword,
                                textInputAction: TextInputAction.done,
                                decoration: InputDecoration(
                                  hintText: 'Repeat your password',
                                  prefixIcon: const Icon(
                                    Icons.verified_user_outlined,
                                  ),
                                  errorText:
                                      _fieldErrors['password_confirmation']
                                          ?.first,
                                ),
                              ),
                            ),
                          ],
                          const SizedBox(height: 24),
                          FilledButton(
                            onPressed: widget.sessionController.isBusy
                                ? null
                                : _submit,
                            style: FilledButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 18),
                              backgroundColor: PairlyColors.primary,
                            ),
                            child: widget.sessionController.isBusy
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.4,
                                      color: Colors.white,
                                    ),
                                  )
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: <Widget>[
                                      Text(
                                        _isRegisterMode
                                            ? 'Create account'
                                            : 'Open my pantry',
                                      ),
                                      const SizedBox(width: 8),
                                      const Icon(Icons.arrow_forward_rounded),
                                    ],
                                  ),
                          ),
                          const SizedBox(height: 18),
                          Text(
                            'Recommended starting slice: auth, pantry-based discover, generated matches, recipe detail, then grounded explanation.',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: PairlyColors.outline,
                              height: 1.5,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _GlowOrb extends StatelessWidget {
  const _GlowOrb({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          boxShadow: <BoxShadow>[
            BoxShadow(color: color, blurRadius: 120, spreadRadius: 40),
          ],
        ),
      ),
    );
  }
}

class _LabeledField extends StatelessWidget {
  const _LabeledField({required this.label, required this.child});

  final String label;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Padding(
          padding: const EdgeInsets.only(left: 8, bottom: 8),
          child: Text(
            label.toUpperCase(),
            style: theme.textTheme.labelSmall?.copyWith(
              color: PairlyColors.outline,
            ),
          ),
        ),
        child,
      ],
    );
  }
}
