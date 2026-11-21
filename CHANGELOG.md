# Changelog

All notable changes to this package are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-11-21

### Changed
- The licence check follows MageBooster License 1.1: without a valid serial number (missing, invalid, expired, other domain or other product) the module keeps working for 30 days, then the contract is no longer filled in or attached to order mails; checkout keeps working with the required checkbox and plain text. Development hosts (localhost, *.localhost, *.test, *.local, 127.0.0.1) always pass the domain check. The admin shows the state on every page; nothing is shown on the storefront and nothing is sent anywhere.
- The contract-hyva serial number is accepted by this module (no separate registration in Contract-Hyva).
- Requires magebooster/module-license ^1.1.

## [1.0.0] - 2026-11-20

First public release.

### Added
- `{{TAG}}` filling of Stores > Terms and Conditions texts at checkout (Luma Knockout checkout) and when the order is placed.
- Contract record per order and agreement (HTML, SHA-256 hash, customer IP), admin order tab, customer order page download, PDF via dompdf.
- PDF / HTML attachment on the order confirmation email (sync and async sending).
- "Create contracts now" for orders placed before the module was enabled (marked "created later").
- Seller settings with Store Information fallback, optional tax office address attribute.
- Two inactive sample Turkish texts (drafts, to be reviewed by a lawyer).
- Licence gating with the MageBooster serial (product code `contract`).
