# MCP Abilities - Advanced Ads

Advanced Ads management for WordPress via MCP.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/mcp-abilities-advads)](https://github.com/bjornfix/mcp-abilities-advads/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

**Tested up to:** 6.9
**Stable tag:** 1.0.3
**Requires PHP:** 7.4
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## What It Does

This add-on plugin exposes Advanced Ads management through MCP (Model Context Protocol). Your AI assistant can manage ads, placements, groups, settings, and run diagnostics.

**Part of the [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/) ecosystem.**

## Requirements

- WordPress 6.0+
- PHP 7.4+
- [Abilities API](https://github.com/WordPress/abilities-api) plugin
- [MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin
- [Advanced Ads](https://wordpress.org/plugins/advanced-ads/) plugin

## Installation

1. Install the required plugins (Abilities API, MCP Adapter, Advanced Ads)
2. Download the latest release from [Releases](https://github.com/bjornfix/mcp-abilities-advads/releases)
3. Upload via WordPress Admin > Plugins > Add New > Upload Plugin
4. Activate the plugin

## Abilities (17)

| Ability | Description |
|---------|-------------|
| `advads/list-ads` | List ads |
| `advads/get-ad` | Get ad details |
| `advads/create-ad` | Create ad |
| `advads/update-ad` | Update ad |
| `advads/delete-ad` | Delete ad |
| `advads/list-placements` | List placements |
| `advads/get-placement` | Get placement details |
| `advads/create-placement` | Create placement |
| `advads/update-placement` | Update placement |
| `advads/delete-placement` | Delete placement |
| `advads/list-groups` | List groups |
| `advads/create-group` | Create group |
| `advads/update-group` | Update group |
| `advads/delete-group` | Delete group |
| `advads/get-settings` | Get Advanced Ads settings |
| `advads/update-settings` | Update Advanced Ads settings |
| `advads/diagnose` | Run configuration diagnostics |

## Usage Examples

### List ads

```json
{
  "ability_name": "advads/list-ads",
  "parameters": {
    "per_page": 20,
    "page": 1
  }
}
```

### Create a placement

```json
{
  "ability_name": "advads/create-placement",
  "parameters": {
    "slug": "header-banner",
    "name": "Header Banner",
    "type": "header"
  }
}
```

### Run diagnostics

```json
{
  "ability_name": "advads/diagnose",
  "parameters": {}
}
```

## Changelog

### 1.0.3
- Fixed: Removed hard plugin header dependency on abilities-api to avoid slug-mismatch activation blocking

### 1.0.2
- Improve list query efficiency and normalize status input
- Use a direct placement slug lookup for faster create checks

### 1.0.1
- Fixed placements to use Advanced Ads 2.0+ custom post type storage
- Placements are now stored as `advanced_ads_plcmnt` posts, not options

### 1.0.0
- Initial release
- 9 abilities for ads, placements, groups, and settings management

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com) - We've been doing SEO and web development since 1993.

## Links

- [Plugin Page](https://devenia.com/plugins/mcp-expose-abilities/)
- [Core Plugin (MCP Expose Abilities)](https://github.com/bjornfix/mcp-expose-abilities)
- [All Add-on Plugins](https://devenia.com/plugins/mcp-expose-abilities/#add-ons)
