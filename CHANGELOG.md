# Changelog

## Unreleased

- Add weekly monitoring for seven official Duitku documentation/SDK sources, with a reviewed baseline, comparison artifacts, and one reusable GitHub tracking issue.
- Add CODEOWNERS, contribution/security/support/release policies, a Code of Conduct, and GitHub issue/PR templates.

## 3.0.0 — 2026-10-22

Laravel 12–13 support, payment verification hardening, and Laravel testing tools. This is a breaking upgrade from v2; follow the [migration guide](docs/guide/upgrading.md).

### Breaking changes

- Support Laravel 12 on PHP 8.2–8.5 and Laravel 13 on PHP 8.3–8.5. Drop Laravel 10–11 support.
- Use HMAC-SHA256 by default for payment API and POP signatures. Legacy signing requires explicit configuration; disbursement retains its separate SHA256 protocol.
- Verify callback merchant identity, signed amount, required fields, and provider transaction status before dispatching payment events. Pending transactions only dispatch the general callback event.
- Return ordered `BatchResult` objects from status batches and bulk bank inquiries, retaining each item's value or error.
- Validate payment requests and provider success responses. Apply configured expiry without mutating request DTOs; use integer token modes and nested customer IDs.
- Stop retrying invoice and payout execution requests automatically. Read operations only retry configured transient errors.
- Load the POP browser adapter from a published external asset. Publish `duitku-assets` when upgrading.

### Added

- `duitku:doctor` for offline configuration checks, with POP asset, disbursement, and strict modes.
- `Duitku::fake()` for synthetic payment API/POP responses, signed callback fixtures, and request assertions while retaining SDK validation and events.
- Callback metadata, `verifiedStatus`, and `verifyCallback()` for verification without event dispatch.
- Configurable batch concurrency, partial failure handling, and consistent HTTP settings across batch items.
- POP custom browser events and a tested example application listener with idempotent order updates.
- Weekly Dependabot updates for Composer, documentation dependencies, and GitHub Actions.

### Fixed

- Dependency resolution and CI across supported Laravel/PHP combinations without removing Larastan.
- Logging of request bodies, headers, and query strings containing customer data or signatures.
- POP reference encoding and repeated script loading; register Blade integration during provider boot.
- Malformed response handling, discontinued payment method selection, mixed item DTO serialization, and payment token field types.
- Documentation tooling vulnerabilities and Pages workflows; pin Actions to official commit SHAs.

### Validation

152 PHP tests with 409 assertions, three JavaScript tests, PHPStan, Pint, and documentation build checks pass. Seven PHP/Laravel combinations pass CI. A clean Laravel 13 consumer application verifies installation, auto-discovery, doctor, asset publishing, Blade, and the payment fake.

See the [sandbox verification record](docs/reviews/2026-10-22-sandbox.md) for provider observations and limits. Card lifecycle and SNAP modules remain outside this release.
