# Changelog

All notable changes to this package are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

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
