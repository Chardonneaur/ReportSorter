# ReportSorter

**Drag and drop to reorder reports on any Matomo report page. Your custom layout is saved per user.**

> This plugin is a community contribution and is not officially supported by Matomo GmbH.
> It is provided under the GPL v3 license without any warranty of fitness for a particular purpose.

## Description

ReportSorter lets every Matomo user arrange the reports on any standard subcategory page in whatever order works best for them — without touching Matomo configuration or affecting other users.

A **↕ Sort reports** button appears on every standard report page (e.g. Visitors → Locations, Acquisition → Channels, Behaviour → Pages). Click it, drag the reports into your preferred order in the pop-up dialog, then click **Save order**. The page reloads with your custom layout. Each user has a completely independent ordering, so your preferences never interfere with a colleague's view.

### Features

- **Drag-and-drop interface** — native HTML5 drag, no third-party libraries required
- **Per-user persistence** — saved in the database, survives logout and browser restarts
- **Per-page granularity** — each subcategory page has its own independent order
- **One-click reset** — restore the default Matomo report order for any page at any time
- **Zero configuration** — works immediately after activation, no settings to configure
- **Lightweight** — one small JS file, one small CSS file, one database table

### How It Works

1. Navigate to any standard Matomo report page (e.g. Visitors → Locations).
2. Click the **↕ Sort reports** button that appears at the top of the report area.
3. In the dialog, drag reports up or down to your preferred order.
4. Click **Save order**. The page reloads with your custom layout applied.
5. To revert, open the dialog again and click **Reset to default**.

Custom ordering is stored per user and per subcategory page. It is applied at page render time by adjusting widget display order, so it is fully compatible with all standard Matomo report pages.

## Requirements

- Matomo >= 5.0
- PHP >= 8.1
- MySQL / MariaDB (InnoDB)

## Installation

### From the Matomo Marketplace

1. Go to **Administration → Marketplace**.
2. Search for **ReportSorter**.
3. Click **Install** and then **Activate**.

### Manual Installation

1. Download the latest release archive from the [GitHub repository](https://github.com/chardonneaur/ReportSorter/releases).
2. Extract it into your `matomo/plugins/` directory so that the path `matomo/plugins/ReportSorter/plugin.json` exists.
3. Go to **Administration → Plugins** and activate **ReportSorter**.

## FAQ

**Does this affect other users?**
No. Each user's report ordering is stored and applied independently. Activating or deactivating the plugin has no effect on other users' views beyond whether the Sort button is shown.

**Does it work on dashboard pages?**
No. ReportSorter targets standard subcategory report pages (the ones with `.reporting-page` in the DOM). Dashboards have their own separate widget management interface built into Matomo.

**What happens if I deactivate the plugin?**
Reports return to Matomo's default ordering immediately. Saved preferences are kept in the database. If you reactivate the plugin, all users' saved orderings are restored.

**What happens if I uninstall the plugin?**
The plugin's database table (`matomo_report_sorter_order`) is dropped and all saved orderings are permanently deleted.

**Is there a limit on how many pages I can customise?**
Yes — 500 subcategory pages per user, which is far more than the number of report pages that exist in a standard Matomo installation. This limit exists as a safeguard against runaway data growth.

**Does this work with custom plugins that add reports?**
Yes, as long as the custom plugin renders standard Matomo widgets (`.matomo-widget` elements with a unique ID) on a standard report subcategory page.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full version history.

## License

GPL v3+. See [LICENSE](LICENSE) for details.
