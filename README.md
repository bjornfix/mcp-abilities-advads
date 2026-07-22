# MCP Abilities - Advanced Ads

MCP abilities for Advanced Ads. Manage ads, placements, groups, and settings programmatically.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/mcp-abilities-advads)](https://github.com/bjornfix/mcp-abilities-advads/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)

**Tested up to:** 7.0
**Stable tag:** 1.0.5
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## What It Does

MCP abilities for Advanced Ads. Manage ads, placements, groups, and settings programmatically.

This plugin is part of the Devenia MCP abilities ecosystem. It gives an MCP-capable agent a focused, authenticated way to work with Advanced Ads work inside WordPress through MCP.

**Example:** "Handle this WordPress maintenance task directly." - The agent can inspect the site, call the relevant ability, and return the result without making the human click through wp-admin for every step.

## The Real Workflow

In practice, the human should not have to memorize every ability name.

The normal pattern is:

1. install the base MCP stack
2. install only the add-ons the site actually needs
3. let the agent discover the available abilities
4. give the agent a clear task with boundaries
5. verify the result in WordPress

The human's job is mostly to describe the goal.
The agent's job is to figure out the mechanics.

## Why This Feels Different

Most WordPress automation still leaves the repetitive part to the human.

This plugin is different because the agent can act inside the site through a narrow, authenticated ability surface:

- inspect current site state before changing anything
- run the specific action needed for the task
- return structured results that are easy to verify
- keep the workflow inside WordPress instead of a separate checklist

That changes the experience from:

- `Here is what you should do in wp-admin`

to:

- `Tell the agent what needs doing, and let it carry out the work`

## Before vs After

### Before

- ask the AI what to do
- copy the answer into WordPress by hand
- click through wp-admin for the repetitive bits
- postpone maintenance because the task is tedious

### After

- tell the agent what needs doing
- let it inspect the relevant WordPress state
- let it run the targeted ability
- verify the result and move on

## Who It Is For

This is a good fit for:

- agencies managing WordPress sites with AI-assisted maintenance
- operators who want agents to do real WordPress work instead of producing instructions
- teams already using MCP Expose Abilities
- sites where this WordPress area is updated often enough to deserve automation

It is especially useful when the manual version is repetitive enough that important maintenance gets delayed.

## Documentation

Start with the main plugin page and base stack documentation:

- [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/)
- [Plugin Page](https://devenia.com/plugins/mcp-expose-abilities/#add-ons)
- [Getting Started](https://github.com/bjornfix/mcp-expose-abilities/wiki/Getting-Started)
- [Install Order and Dependencies](https://github.com/bjornfix/mcp-expose-abilities/wiki/Install-Order-and-Dependencies)

If you are using an AI agent, the simplest instruction is often just:

- `Read https://github.com/bjornfix/mcp-expose-abilities and figure out the stack before making changes.`

## Start Here

If you are new to the stack, use this order:

1. Install **Abilities API**.
2. Install **MCP Adapter**.
3. Install **MCP Expose Abilities**.
4. Install **MCP Abilities - Advanced Ads**.
5. Confirm the new abilities appear in discovery.
6. Give the agent a clear task that uses this add-on.

If you skip base-stack verification and start with add-ons immediately, troubleshooting gets harder than it needs to be.

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

### 1.0.5
- Fixed: Treat parameterless read abilities as no-input abilities so MCP calls without parameters validate correctly

### 1.0.4
- Fixed: Updated WordPress compatibility metadata for the Abilities API requirement and refreshed the public package checksum

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

## Contributing

PRs welcome. Keep changes focused on the plugin's WordPress ability surface and preserve authenticated, explicit workflows.

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com) - We've been doing SEO and web development since 1993.

## Links

- [Plugin Page](https://devenia.com/plugins/mcp-expose-abilities/#add-ons)
- [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/)
- [GitHub Releases](https://github.com/bjornfix/mcp-abilities-advads/releases)

## Star and Share

If this plugin saves you time or makes WordPress maintenance easier to verify, please:

- star the repo
- share it with people running WordPress sites
- point them to the main plugin page so they can see what the ecosystem can actually do

Why do it?

Because agent-friendly open WordPress tooling helps more of the boring but important work get done.
