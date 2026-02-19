# ReportSorter

Allows users to drag and reorder reports within any standard Matomo report page. Order is saved per user.

> **Warning**
>
> This plugin is experimental and was coded using [Claude Code](https://claude.ai).
> It is provided without any warranty regarding quality, stability, or performance.
> This is a community project and is not officially supported by Matomo.

## Description

ReportSorter adds a floating **↕ Sort reports** button to every standard Matomo subcategory report page (e.g. Visitors → Locations, Acquisition → Referrers). Clicking it opens a drag-and-drop modal listing all reports on the page. Drag them into your preferred order, click **Save order**, and the page reloads with your custom layout — persisted per user, per page.

- Each user has their own independent ordering
- Works on any standard report subcategory page
- Reset to the default Matomo order at any time via the **Reset to default** button

## Requirements

- Matomo >= 5.0
- PHP >= 8.1

## Installation

### From Matomo Marketplace
1. Go to Administration → Marketplace
2. Search for "ReportSorter"
3. Click Install

### Manual Installation
1. Download the latest release from GitHub
2. Extract to your `matomo/plugins/` directory as `ReportSorter/`
3. Activate the plugin in Administration → Plugins

## License

GPL v3+. See [LICENSE](LICENSE) for details.
