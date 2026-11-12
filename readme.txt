=== Disable Random Content ===
Contributors: kaggdesign, ernest35
Tags: random content, redirects, permalinks
Requires at least: 7.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable WordPress random content redirects and their permalink routes.

== Description ==

WordPress 7.2 adds random content redirects for URLs such as `/random/` and for archive requests containing `?random=1`. This plugin turns off that feature through the `wp_enable_random_content_redirect` filter.

The plugin refreshes rewrite rules when activated or deactivated. In a multisite network, it clears each site's saved rules so WordPress can regenerate them with that site's own settings on the next request.

== Installation ==

1. Install the plugin in `/wp-content/plugins/disable-random-content/`.
2. Activate it for one site or across the network.

== Frequently Asked Questions ==

= Does this disable all random ordering in WordPress? =

No. It disables the random content redirect feature. Queries that explicitly use random ordering for other purposes are unaffected.

= What happens to existing `/random/` URLs? =

The plugin removes the special rewrite rules. Those URLs then follow the site's ordinary routing rules.

== Changelog ==

= 1.1.0 =
* Keep random content redirects disabled after deactivation and reactivation in the same request.

= 1.0.0 =
* Disable random content redirects and their rewrite rules.
* Refresh site and network rewrite rules when activation status changes.
