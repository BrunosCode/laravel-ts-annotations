# Changelog

All notable changes to `laravel-ts-annotations` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v0.3.0 - 2026-06-16

### Changed

- **BREAKING — namespace casing.** The PHP namespace is now `BrunosCode\LaravelTsAnnotations\…`
  (capital `C`), matching the GitHub handle and the sibling packages. Update your imports:
  `use Brunoscode\…` → `use BrunosCode\…`. On case-sensitive filesystems (Linux, CI, most
  production) the old casing no longer autoloads. The Packagist package name
  `brunoscode/laravel-ts-annotations` is unchanged.
- **BREAKING — dropped Laravel 10 and 11.** Both lines are affected by CVE-2026-48019 with no
  patched release, so Composer refuses to install them. Supported matrix is now Laravel 12
  (PHP 8.2+) and Laravel 13 (PHP 8.3+).
- Documentation rewritten and aligned with the package source and the sibling packages' style.

### Added

- `composer test` script.
- Code-style (Laravel Pint) CI workflow.
- `LICENSE.md` and this changelog.

### Fixed

- CI matrix no longer attempts the invalid PHP 8.2 + Laravel 13 combination.

## v0.2.3 and earlier

See the [GitHub releases](https://github.com/BrunosCode/LaravelTsAnnotations/releases) for the
history prior to v0.3.0.
