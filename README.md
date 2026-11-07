# Disable Random Content

This WordPress plugin disables the random content redirects introduced in WordPress 7.2. It removes their generated rewrite rules and prevents random selection for requests containing `random`.

The main plugin file is `plugin.php`. The hook implementation is in `src/Main.php`; WordPress loads it directly. Composer installs development tools only. On a multisite network, a change in network activation clears each site's saved rewrite rules. WordPress regenerates them in that site's own context on its next request.

## Integration tests

The tests use the WordPress PHPUnit test suite, following the setup used by Cyr-To-Lat. They require a **dedicated disposable database**: the WordPress test bootstrap installs tables and clears test data.

1. Run `composer install`.
2. Copy `tests/integration/_config/params.example.php` to `params.local.php` and set the WordPress root and dedicated database credentials. The local file is ignored by Git. Alternatively, point `DRC_TEST_PARAMS` to a private PHP config file.
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

`.github/workflows/ci.yml` installs WordPress 7.2 Beta 4 and runs all checks against a disposable MySQL database. `.distignore` keeps development files out of the WordPress.org package; the package contains `plugin.php`, `src/Main.php`, `readme.txt`, and `LICENSE`.

The WordPress.org icon and banners are in `.wordpress-org/`. The SVG icon has 128 px and 256 px PNG fallbacks. The editable banner source is `design/wordpress-org-banner.svg`; both banner PNGs are generated from it. The deploy workflow places `.wordpress-org/` in the WordPress.org SVN `assets` directory.

Publishing a non-prerelease GitHub release triggers `.github/workflows/deploy.yml`. Use an unprefixed release tag matching the `Version` header in `plugin.php` and `Stable tag` in `readme.txt` (for example, `1.0.0`). WordPress.org deployment requires the `disable-random-content` slug to be approved and `SVN_USERNAME` and `SVN_PASSWORD` to be configured as GitHub repository secrets. No release is created by the CI workflow.
