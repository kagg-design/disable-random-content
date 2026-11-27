# Disable Random Content

This WordPress plugin disables the random content redirects introduced in WordPress 7.2. Activate the plugin to turn them off. All settings are optional.

[Install from WordPress.org](https://wordpress.org/plugins/disable-random-content/) or open [Live Preview](https://wordpress.org/plugins/disable-random-content/?preview=1) and select **Open demo homepage** to try the site-wide and Blog category links.

Composer autoloads the classes in `src/php/`. Run `composer install` before activating the plugin from a source checkout.

In **Settings → Disable Random Content**, select individual categories, tags, other public taxonomy terms, or eligible post type archives to allow. An allowed archive works both with a random URL such as `/random/category/blog/` and with `?random` on its regular archive URL. The site-wide `/random/` stays disabled. Selections belong to each site in Multisite.

The plugin clears a site's saved rewrite rules after its settings change. On a multisite network, an activation change clears the rules on each affected site. WordPress regenerates the rules in each site's own context on its next request.

An earlier callback that disables `wp_enable_random_content_redirect` also disables random redirects for selected archives. With empty settings, a later callback can explicitly re-enable the core feature. Refresh rewrite rules after changing such an override.

## Integration tests

The tests use the WordPress PHPUnit test suite, following the setup used by Cyr-To-Lat. They require a **dedicated disposable database**: the WordPress test bootstrap installs tables and clears test data.

1. Run `composer install`.
2. Copy `tests/php/integration/_config/params.example.php` to `params.local.php` and set the WordPress root and dedicated database credentials. The local file is ignored by Git. Alternatively, point `DRC_TEST_PARAMS` to a private PHP config file.
3. Run `composer test`. This checks PHPCS and runs the WordPress tests in single-site and Multisite modes.

The individual commands are:

```text
composer phpcs
composer integration
composer integration:multisite
```

`phpunit.integration.xml` runs the ordinary site tests, and `phpunit.multisite.xml` enables Multisite. The local PhpStorm project has run configurations for both suites.

## Coding standards

`phpcs.xml` checks the plugin and tests against WordPress Coding Standards and PHP 7.4 compatibility:

```text
composer phpcs
```

## CI and WordPress.org distribution

`.github/workflows/ci.yml` installs WordPress 7.2 Beta 4 and runs the checks against a disposable MySQL database. The deploy and Plugin Check workflows run `composer install --no-dev` before packaging. The WordPress.org package includes `plugin.php`, `src/`, `assets/`, `vendor/`, `readme.txt`, and `LICENSE`. `.distignore` excludes development files.

The WordPress.org icon and banners are in `.wordpress-org/`. The SVG icon has 128 px and 256 px PNG fallbacks. `design/kagg-compatibility-background.png` is the background layer extracted from the KAGG Compatibility `banner.psd`; `design/wordpress-org-banner.svg` contains the icon and text overlay. Regenerate the banners with ImageMagick:

```text
magick design/kagg-compatibility-background.png -background none design/wordpress-org-banner.svg -compose over -composite .wordpress-org/banner-1544x500.png
magick .wordpress-org/banner-1544x500.png -filter Lanczos -resize 772x250 .wordpress-org/banner-772x250.png
```

The deploy workflow places `.wordpress-org/` in the WordPress.org SVN `assets` directory.

Publishing a non-prerelease GitHub release triggers `.github/workflows/deploy.yml`. Use an unprefixed release tag matching the `Version` header in `plugin.php` and `Stable tag` in `readme.txt` (for example, `1.0.0`). WordPress.org deployment requires the `disable-random-content` slug to be approved and `SVN_USERNAME` and `SVN_PASSWORD` to be configured as GitHub repository secrets. No release is created by the CI workflow.
