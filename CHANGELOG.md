# Changelog

## 1.0.0 — 2026-03-04

Initial release.

- Drag-and-drop modal to reorder reports on any standard Matomo subcategory page
- Per-user, per-page persistence stored in a dedicated database table
- Reset-to-default button to restore Matomo's original report order
- CSRF-protected Controller endpoints with token validation
- Input validation on all API and Controller parameters
- DoS safeguards: 100 KB payload cap, 200 widget IDs per page, 500 pages per user
