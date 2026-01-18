=== MCP Abilities - Advanced Ads ===
Contributors: devenia
Tags: mcp, advanced-ads, adsense, ads
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.2
Requires Plugins: mcp-expose-abilities
License: GPLv2 or later

MCP abilities for Advanced Ads (free version). Manage ads, placements, and settings programmatically.

== Description ==

This add-on provides 12 abilities for managing Advanced Ads through the MCP interface:

**Ads**
* advads/list-ads - List all ads
* advads/get-ad - Get ad details
* advads/create-ad - Create new ad (plain, content, adsense types)
* advads/update-ad - Update existing ad
* advads/delete-ad - Delete ad

**Placements**
* advads/list-placements - List all placements
* advads/create-placement - Create placement (post_content, header, footer, etc.)
* advads/update-placement - Update placement
* advads/delete-placement - Delete placement

**Groups**
* advads/list-groups - List ad groups

**Settings**
* advads/get-settings - Get AdSense and general settings
* advads/update-settings - Update settings

**Diagnostics**
* advads/diagnose - Check for configuration issues

== Installation ==

1. Ensure MCP Expose Abilities core plugin is installed and activated
2. Ensure Advanced Ads (free) is installed and activated
3. Upload this plugin to `/wp-content/plugins/`
4. Activate through the Plugins menu

== Changelog ==

= 1.0.2 =
* Improve list query efficiency and normalize status input
* Use a direct placement slug lookup for faster create checks

= 1.0.1 =
* Fixed placements to use Advanced Ads 2.0+ custom post type storage
* Placements are now stored as `advanced_ads_plcmnt` posts, not options

= 1.0.0 =
* Initial release
* 9 abilities for ads, placements, groups, and settings management
