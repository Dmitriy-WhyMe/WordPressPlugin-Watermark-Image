# Contributing to Watermark Manager

Thanks for considering a contribution.

## Development Setup

1. Use WordPress 5.8+ and PHP 7.4+.
2. Ensure PHP GD extension is enabled.
3. Place plugin in `wp-content/plugins/watermark-manager`.
4. Activate plugin and open `Settings -> Watermark Manager`.

## How to Contribute

1. Fork the repository and create a branch.
2. Keep changes scoped to one issue/feature.
3. Add clear reproduction and validation steps in your PR.
4. If UI/behavior changes, include before/after screenshots.
5. Confirm no PHP warnings/notices are introduced.

## Pull Request Checklist

- [ ] Code is readable and follows WordPress coding style where practical
- [ ] Existing behavior is not broken for other post types
- [ ] Manual test done for featured image and content image replacement
- [ ] Manual test done for at least one bulk generation scenario
- [ ] README updated if user-facing behavior changed

## Reporting Bugs

Please include:

- WordPress version
- PHP version
- Active theme/plugins that might affect media handling
- Steps to reproduce
- Expected vs actual behavior
- Error logs (if available)

## Security

Do not open a public issue for sensitive vulnerabilities.
Contact the maintainer privately first and provide a minimal proof of concept.
