<?php
/**
 * Bootstrap for WordPress integration tests.
 *
 * @package DisableRandomContent
 */

$project_root    = dirname( __DIR__, 2 );
$vendor_autoload = $project_root . '/vendor/autoload.php';

if ( ! file_exists( $vendor_autoload ) ) {
	echo "Install development dependencies with composer install.\n";
	exit( 1 );
}

require_once $vendor_autoload;

$polyfills_autoload = $project_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

if ( file_exists( $polyfills_autoload ) ) {
	require_once $polyfills_autoload;
}

$composer_tests_dir = $project_root . '/vendor/wp-phpunit/wp-phpunit';
$tests_dir          = file_exists( $composer_tests_dir . '/includes/functions.php' )
	? $composer_tests_dir
	: ( getenv( 'WP_TESTS_DIR' ) ?: getenv( 'WP_PHPUNIT__DIR' ) );
$tests_dir          = rtrim( (string) $tests_dir, '/\\' );

if ( ! file_exists( $tests_dir . '/includes/functions.php' ) ) {
	echo "Set WP_TESTS_DIR to a WordPress PHPUnit test suite.\n";
	exit( 1 );
}

$tests_config = getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ?: __DIR__ . '/wp-tests-config.php';

if ( ! file_exists( $tests_config ) ) {
	echo "WordPress PHPUnit test configuration not found.\n";
	exit( 1 );
}

// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . $tests_config );
define( 'WP_TESTS_CONFIG_FILE_PATH', $tests_config );

require_once $tests_dir . '/includes/functions.php';

/**
 * Load the plugin with WordPress's must-use plugins.
 */
function disable_random_content_load_for_tests(): void {
	require dirname( __DIR__, 2 ) . '/plugin.php';
}

tests_add_filter( 'muplugins_loaded', 'disable_random_content_load_for_tests' );

require $tests_dir . '/includes/bootstrap.php';
