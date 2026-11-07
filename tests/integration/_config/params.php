<?php
/**
 * Find local WordPress integration test parameters.
 *
 * @package DisableRandomContent
 */

$params_file = getenv( 'DRC_TEST_PARAMS' );

if ( ! $params_file && getenv( 'GITHUB_ACTIONS' ) ) {
	$params_file = __DIR__ . '/params.github-actions.php';
}

if ( ! $params_file ) {
	$params_file = __DIR__ . '/params.local.php';
}

if ( ! file_exists( $params_file ) ) {
	throw new RuntimeException( 'Copy params.example.php to params.local.php or set DRC_TEST_PARAMS.' );
}

return require $params_file;
