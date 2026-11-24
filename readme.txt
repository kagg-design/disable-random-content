=== Disable Random Content ===
Contributors: kaggdesign, ernest35
Tags: random content, random post, redirects, permalinks
Requires at least: 7.1
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 1.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable WordPress 7.2 random post redirects, including /random/, on activation. Keep random links for selected archives.

== Description ==

Activate Disable Random Content to turn off WordPress 7.2's `/random/` URL and random redirects from archives. To keep random links for specific categories, tags, or other eligible archives, select them under Settings > Disable Random Content. For example, allow `/random/category/blog/` and keep the site-wide `/random/` disabled.

You can activate the plugin on WordPress 7.1 ahead of the upgrade, either for one site or across a Multisite network. It starts controlling random redirects when the site runs WordPress 7.2 or later.

Try both links in the [WordPress Playground demo linked in the setup guide](https://kagg.eu/how-to-disable-random-in-wordpress-7-2/).

=== What it does ===

* Stops redirects from WordPress's random URLs, such as `/random/` and random archive routes.
* Prevents requests using the `random` query parameter from selecting and redirecting to a random post. The underlying archive or search request continues normally.
* Lets you allow random redirects for individual category, tag, public taxonomy, or eligible post type archives.
* Leaves your published content available at its usual URLs.
* Handles permalink rules automatically on individual sites and Multisite networks.

It does not affect unrelated random-order queries in themes or other plugins.

== Installation ==

1. In WordPress, go to Plugins > Add New Plugin and enter `slug:disable-random-content` in the keyword search field.
2. Install and activate the plugin. On Multisite, activate it for one site or across the network.

No settings or manual permalink changes are required.

With WP-CLI, run `wp plugin install disable-random-content --activate` for a single site or `wp plugin install disable-random-content --activate-network` for a Multisite network.

== Screenshots ==

1. Settings > Disable Random Content with random redirects allowed for the Blog category.
2. The demo homepage shows a blocked site-wide route, a random Blog post link, and a normal Updates archive link.
3. The site-wide /random/ URL returns a 404 on the demo site while the plugin is active.

== Frequently Asked Questions ==

= How do I disable the `/random/` URL in WordPress 7.2? =

Activate Disable Random Content. It turns off the default `/random/` URL, random redirects on archive URLs, and redirects triggered by the `random` query parameter. It uses the `wp_enable_random_content_redirect` filter and supports a custom Random base set in Settings > Permalinks, such as `/shuffle/`.

= Does it disable random ordering in themes or other plugins? =

No. Unrelated queries that explicitly request random ordering are unaffected.

= What happens to `/random/` after activation? =

WordPress serves a published page or post that has `/random/` as its permalink. If no other route handles the address, WordPress usually returns a 404.

= Does it change my posts, pages, or menus? =

No. It does not edit content or menus. Published content remains available at its usual URLs.

= Do I need to resave my permalinks? =

No. The plugin refreshes permalink rules when activated or deactivated on an individual site. Changing the allowed archives or the plugin's network activation clears the affected sites' saved rules, and WordPress rebuilds them on the next request.

If a page cache or CDN keeps serving an earlier response after a change, clear the cached response for the affected URL and test again.

= Does it support WordPress Multisite? =

Yes. You can activate it for an individual site or across the network.

= Can I allow random redirects for selected archives? =

Yes. Go to Settings > Disable Random Content and select the archives you want to allow. For example, selecting the Blog category allows `/random/category/blog/` and `/category/blog/?random`. Select a subcategory separately to enable its own random link. The site-wide `/random/` stays disabled.

On a WooCommerce site, select only the Blog category to keep its random link and stop `/random/shop/` from choosing a product. Visitors can browse the store at its usual shop and product URLs.

For a custom post type, select its archive under **Post type archives**. For example, with the default random base, allowing an eligible Projects archive with the slug `projects` enables `/random/projects/` and `/projects/?random=1`. A post type appears in the list when it is publicly queryable, has an archive, and supports random selection in WordPress.

= Can I restore all of WordPress's built-in random redirects? =

Deactivate the plugin to restore the core behavior. Other code on your site may still disable the feature.

= Does the plugin contact external services or store data? =

The plugin makes no external requests and adds no tracking. Archive selections are stored in a WordPress option for the current site. WordPress rebuilds the site's permalink rules after changes to the selections.

== Changelog ==

= 1.3.2 =
* Fixed stale rewrite rules when a remote tool such as MainWP activates or deactivates the plugin before WordPress finishes loading.
* Preserved saved archive selections when a site temporarily returns to WordPress 7.1.
* Added a link to the working demo and instructions for switching versions when Playground starts on WordPress 7.1.

= 1.3.1 =
* Added an interactive WordPress Playground demo for the WordPress.org Live Preview button.

= 1.3.0 =
* Added an optional settings page under Settings > Disable Random Content to allow random redirects for selected archives.

= 1.2.0 =
* Tested with WordPress 7.2.

= 1.1.0 =
* Keep random content redirects disabled after deactivation and reactivation in the same request.

= 1.0.0 =
* Disable random content redirects and their rewrite rules.
* Refresh site and network rewrite rules when activation status changes.
