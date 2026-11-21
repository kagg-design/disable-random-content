<?php
/**
 * WordPress integration test parameters for GitHub Actions.
 *
 * @package DisableRandomContent
 */

return [
	'WP_URL'          => 'test.test',
	'WP_ROOT_PATH'    => getenv( 'WP_ROOT_PATH' ),
	'DB_HOST'         => getenv( 'DB_HOST' ),
	'DB_NAME'         => getenv( 'DB_NAME' ),
	'DB_USER'         => getenv( 'DB_USER' ),
	'DB_PASSWORD'     => getenv( 'DB_PASSWORD' ),
	'DB_TABLE_PREFIX' => 'wptests_',
];
