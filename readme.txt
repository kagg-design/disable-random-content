=== Disable Random Content ===
Contributors: kaggdesign, ernest35
Tags: random content, random posts, redirects, random url, multisite
Requires at least: 7.1
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 1.3.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable Random Content turns off WordPress 7.2 random redirects. Keep random links for selected archives. Works with WPML and Polylang.

== Description ==

Disable Random Content turns off WordPress 7.2's built-in random redirects as soon as you activate it. You can keep random links for selected categories, tags, and other archives, including on sites that use **WPML** or **Polylang**. For example, on a WPML site you can allow a random link for your English Blog category while keeping random redirects disabled on its translated archives and at the site-wide `/random/` URL.

**WooCommerce and Multisite.** Keep a random link for a selected WooCommerce product category. On Multisite, each site has its own archive selection.

Choose the archives you want to allow under **Settings > Disable Random Content**. For example, selecting Blog enables its random URL, such as `/random/category/blog/`.

You can activate the plugin on WordPress 7.1 ahead of the upgrade, either for one site or across a Multisite network. It starts controlling random redirects when the site runs WordPress 7.2 or later.

Start the [Live Preview](https://wordpress.org/plugins/disable-random-content/?preview=1) and click **Open demo homepage** to try both links. The [setup guide](https://kagg.eu/how-to-disable-random-in-wordpress-7-2/) covers installation and Multisite.

If the demo opens on an earlier WordPress version, open **Site Settings** in the Playground toolbar, select WordPress 7.2 or later, and click **Apply to this Playground**.

=== What it does ===

* Stops redirects from WordPress's random URLs, such as `/random/` and random archive routes.
* Prevents requests using the `random` query parameter from selecting and redirecting to a random post. The underlying archive or search request continues normally.
* Lets you allow random redirects for individual category, tag, public taxonomy, or eligible post type archives.
* Leaves your published content available at its usual URLs.
* Handles permalink rules automatically on individual sites and Multisite networks.

It does not affect unrelated random-order queries in themes or other plugins.

== Installation ==

1. On a self-hosted WordPress site, go to Plugins > Add New Plugin and enter `slug:disable-random-content` in the keyword search field.
2. Install and activate the plugin. On Multisite, activate it for one site or across the network.

On WordPress.com, search for **Disable Random Content** by its full name in Plugins. Plugin installation is available on WordPress.com paid plans.

No settings or manual permalink changes are required.

With WP-CLI, run `wp plugin install disable-random-content --activate` for a single site or `wp plugin install disable-random-content --activate-network` for a Multisite network.

== Screenshots ==

1. Settings > Disable Random Content with random redirects allowed for the Blog category.
2. The demo homepage shows the three test links and how to switch the allowed category.
3. The site-wide /random/ URL returns a 404 on the demo site while the plugin is active.
4. The allowed Blog category's random link opens one of its published posts.

== Frequently Asked Questions ==

= How do I disable random redirects in WordPress 7.2? =

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

= Can I disable random redirects across a WordPress Multisite network? =

Yes. Activate the plugin across the network to disable the built-in random redirects on all sites. For a rollout to selected sites, activate it individually on each site. Each site can allow random links for its own archives.

= Can I keep a random link for just one category? =

Yes. In Settings > Disable Random Content, select just the category you want to allow. For example, selecting Blog enables `/random/category/blog/` and `/category/blog/?random`. The site-wide `/random/` stays disabled.

WordPress can pick posts from subcategories when choosing from a parent category. To enable a subcategory's own random link, select that subcategory separately. You can also allow links for specific tags and other eligible archives.

On a WooCommerce site, select only the Blog category to keep its random link and stop `/random/shop/` from choosing a product. Visitors can browse the store at its usual shop and product URLs.

To keep a random link for one product category, select it under **Product categories** and leave the **Products** post type archive unselected. The selected category can open a random product while the shop and other categories continue to open as usual.

For a custom post type, select its archive under **Post type archives**. For example, with the default random base, allowing an eligible Projects archive with the slug `projects` enables `/random/projects/` and `/projects/?random=1`. A post type appears in the list when it is publicly queryable, has an archive, and supports random selection in WordPress.

= Why isn't a selected archive redirecting? =

If a code snippet or another plugin disables random redirects across the site, turn off that snippet or setting. In Admin Optimizer, turn off **Disable Random Content Redirect** under **Content Controls**. In Admin and Site Enhancements (ASE), clear the random content redirect checkbox under **Disable Components > Disable Smaller Components**.

Try the selected archive's random URL again. If the redirect is still missing, go to Settings > Permalinks and click **Save Changes** to rebuild the rewrite rules.

= Why does /?random=1 still redirect with Jetpack or on WordPress.com? =

Jetpack 16.4 and the legacy WordPress.com handler can redirect `/?random=1` separately from WordPress core. Test both `/random/` and `/?random=1` on your site after activating Disable Random Content.

[Jetpack's update to use the WordPress core handler](https://github.com/Automattic/jetpack/pull/54665) also applies the `jetpack_random_redirect_enabled` filter to core redirects. If a custom snippet returns false for that filter, review it when upgrading Jetpack. It can switch off random links for your selected archives too.

= Can I restore all of WordPress's built-in random redirects? =

Deactivate the plugin to restore the core behavior. Other code on your site may still disable the feature.

= Does the plugin contact external services or store data? =

The plugin makes no external requests and adds no tracking. Archive selections are stored in a WordPress option for the current site. WordPress rebuilds the site's permalink rules after changes to the selections.

== Changelog ==

= 1.3.4 =
* Added WPML compatibility.
* Added Polylang compatibility.
* Added compatibility for global random redirect settings from other plugins, including Admin and Site Enhancements.
* Added a Live Preview walkthrough for testing the links before and after changing the allowed category.

= 1.3.3 =
* Fixed stale `/random/` routes after updating an active plugin. Version 1.3.3 clears saved rewrite rules once per site so WordPress can rebuild them using the site's existing archive selections.

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
