=== Disable Random Content ===
Contributors: kaggdesign, ernest35
Tags: random content, redirects, permalinks
Requires at least: 7.2
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable Random Content turns off WordPress 7.2 random content redirects. Activate it and you're done; no settings required.

== Description ==

Don't want WordPress to send visitors to a random post? Disable Random Content turns off the random content redirect feature introduced in WordPress 7.2 as soon as you activate it. All settings are optional.

=== What it does ===

* Stops redirects from WordPress's random URLs, such as `/random/` and random archive routes.
* Prevents requests using the `random` query parameter from selecting and redirecting to a random post. The underlying archive or search request continues normally.
* Lets you allow random redirects for individual category, tag, public taxonomy, or eligible post type archives.
* Leaves your published content available at its usual URLs.
* Handles permalink rules automatically on individual sites and Multisite networks.

It does not affect unrelated random-order queries in themes or other plugins.

== Installation ==

1. In WordPress, go to Plugins > Add New and search for "Disable Random Content".
2. Install and activate the plugin. On Multisite, activate it for one site or across the network.

No settings or manual permalink changes are required.

== Frequently Asked Questions ==

= What exactly does the plugin disable? =

It disables WordPress's random content redirects, including the default `/random/` routes and redirects triggered by the `random` query parameter. It uses the `wp_enable_random_content_redirect` filter.

= Does it disable random ordering in themes or other plugins? =

No. Unrelated queries that explicitly request random ordering are unaffected.

= What happens to `/random/` after activation? =

The special random redirect route is removed. The URL follows your site's normal routing rules; if nothing else handles it, it normally returns a 404 response.

= Does it change my posts, pages, or menus? =

No. It does not edit content or menus. Published content remains available at its usual URLs.

= Do I need to resave my permalinks? =

No. The plugin refreshes permalink rules when activated or deactivated on an individual site. Changing the allowed archives or the plugin's network activation clears the affected sites' saved rules, and WordPress rebuilds them on the next request.

= Does it support WordPress Multisite? =

Yes. You can activate it for an individual site or across the network.

= Can I allow random redirects for selected archives? =

Yes. Go to Settings > Disable Random Content and select the archives you want to allow. For example, selecting the Blog category allows `/random/category/blog/` and `/category/blog/?random`. Select a subcategory separately to enable its own random link. The site-wide `/random/` stays disabled.

= Can I restore all of WordPress's built-in random redirects? =

Deactivate the plugin to restore the core behavior. Other code on your site may still disable the feature.

= Does the plugin contact external services or store data? =

The plugin makes no external requests and adds no tracking. Archive selections are stored in a WordPress option for the current site. WordPress rebuilds the site's permalink rules after changes to the selections.

== Changelog ==

= 1.3.0 =
* Added an optional settings page under Settings > Disable Random Content to allow random redirects for selected archives.

= 1.2.0 =
* Tested with WordPress 7.2.

= 1.1.0 =
* Keep random content redirects disabled after deactivation and reactivation in the same request.

= 1.0.0 =
* Disable random content redirects and their rewrite rules.
* Refresh site and network rewrite rules when activation status changes.
