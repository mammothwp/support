# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Releases are tagged vX.Y.Z and published on Packagist.

## [Unreleased]

## [1.1.1] - 2026-10-30

### Changed
- Documentation cleanup.

## [1.1.0] - 2026-10-30

### Changed
- `mammothwp/wp-kit` is now required as `^2.0` at default stability (commit-pinned `package` repository, VCS repository and `minimum-stability: dev` removed); `composer require mammothwp/support` works without `@dev`.

### Fixed
- Support screen fatal: `Site_Section::yes_no()` took a strict `bool` but `wp_using_ext_object_cache()` can return `null`; it now accepts any value and normalises it. Every report field is collected through `Field_Guard` (a failing field renders `unavailable`, logged via `error_log` when `WP_DEBUG`), and `Report_Builder` catches `\Throwable` per section so a section can never take down wp-admin.
- Menu icon: new 20x20 single-path silhouette (`fill="black"`, no width/height) so it renders crisply and takes the admin colour scheme; data URI built by `Menu_Icon` from `assets/mmwp-icon.svg`; filter `mmwp_support_menu_icon`.
- Tests: credential-shaped fixtures in `RedactorTest` are built at runtime; rule documented in `tests/README.md`.
- CI: workflow rules limited to merge requests, `develop` and `main`; shared dependency job; PHPStan memory limit 1G.

## [1.0.0] - 2026-10-20

### Added
- Single-instance bootstrap (`bootstrap.php`, Composer `files`): copies register version + path in a shared registry, the highest version boots on `plugins_loaded` (priority -50) and prepends its own class loader for `MammothWP\Support\`.
- `Support::register_plugin( __FILE__, [ 'email' => ... ] )` with plugin-header fallbacks, `mammothwp-support.json`, and email precedence filter > constant > array > JSON.
- Canonical Support screen (top-level, Tools, Settings, `parent:<slug>` or hidden), per-plugin menu shortcuts, "Support" action link, network admin screen when a registered plugin is network-active.
- White-label / branding resolution (Mammoth WP defaults, neutral defaults, single-vendor branding, site-owner constant and filter, local-only logo).
- Form with REST (fetch) submission and an admin-post.php fallback; one `wp_mail()` email, Reply-To, `[prefix][slug] subject (ref)`, rate limit, capped send log, mailto fallback.
- Deterministic site report (text + JSON) with a redaction pass; opt-in only (unticked checkbox, exact preview, optional debug.log tail).
- REST `mmwp/v1/support/*`, WP-CLI `wp mmwp support *` (`--dry-run`) and Abilities `mmwp/support-*` via `mammothwp/wp-kit`, with a version guard that degrades to the admin-post path.
- `TransportInterface` with `MailTransport` only; filter `mmwp_support_transport`.
- PHPUnit (Brain Monkey) suite, PHPCS (WPCS) and PHPStan level 6 in CI.
- Docs: README, `docs/CONFIGURATION.md`, `docs/WHITELABEL.md`, `docs/PRIVACY.md`, `docs/BRAND.md`, `HOOKS.md`.

### Notes
- `mammothwp/wp-kit` ^1.0 is not on Packagist yet and its repository has no `v1.*` tag, so `composer.json` pins commit `1bd2208` through a `package` repository declaring version `1.0.0`. Remove that entry once Packagist serves `mammothwp/wp-kit` 1.x. Composer ignores `repositories` of dependencies: a plugin that requires this package must add the same entry until then.
- `assets/mmwp-icon.svg` is a placeholder monochrome mark; replace it from the brand repository (do not hand-copy).
