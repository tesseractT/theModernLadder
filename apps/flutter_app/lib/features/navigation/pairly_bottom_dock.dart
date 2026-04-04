import 'package:flutter/material.dart';
import 'package:flutter_app/app/pairly_theme.dart';

enum PairlyDockDestination { discover, match, chats, profile }

class PairlyBottomDock extends StatelessWidget {
  const PairlyBottomDock({
    super.key,
    required this.activeDestination,
    required this.onSelected,
  });

  final PairlyDockDestination activeDestination;
  final ValueChanged<PairlyDockDestination> onSelected;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.88),
          borderRadius: BorderRadius.circular(999),
          boxShadow: <BoxShadow>[
            BoxShadow(
              color: PairlyColors.ink.withValues(alpha: 0.08),
              blurRadius: 30,
              offset: const Offset(0, 12),
            ),
          ],
        ),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: <Widget>[
                _DockItem(
                  icon: Icons.explore_rounded,
                  label: 'Discover',
                  active: activeDestination == PairlyDockDestination.discover,
                  onTap: () => onSelected(PairlyDockDestination.discover),
                ),
                _DockItem(
                  icon: Icons.favorite_border_rounded,
                  label: 'Match',
                  active: activeDestination == PairlyDockDestination.match,
                  onTap: () => onSelected(PairlyDockDestination.match),
                ),
                _DockItem(
                  icon: Icons.chat_bubble_outline_rounded,
                  label: 'Chats',
                  active: activeDestination == PairlyDockDestination.chats,
                  onTap: () => onSelected(PairlyDockDestination.chats),
                ),
                _DockItem(
                  icon: Icons.person_outline_rounded,
                  label: 'Profile',
                  active: activeDestination == PairlyDockDestination.profile,
                  onTap: () => onSelected(PairlyDockDestination.profile),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DockItem extends StatelessWidget {
  const _DockItem({
    required this.icon,
    required this.label,
    required this.onTap,
    this.active = false,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool active;

  @override
  Widget build(BuildContext context) {
    final iconColor = active ? Colors.white : PairlyColors.outline;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(999),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                gradient: active
                    ? const LinearGradient(
                        colors: <Color>[
                          PairlyColors.primary,
                          PairlyColors.primaryBright,
                        ],
                      )
                    : null,
                color: active ? null : Colors.transparent,
                borderRadius: BorderRadius.circular(999),
              ),
              alignment: Alignment.center,
              child: Icon(icon, color: iconColor),
            ),
            const SizedBox(height: 4),
            Text(
              label.toUpperCase(),
              style: Theme.of(context).textTheme.labelSmall?.copyWith(
                color: active ? PairlyColors.primary : PairlyColors.outline,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
