<?php
/**
 * Example parameters for a dedicated WordPress integration test database.
 *
 * @package DisableRandomContent
 */

return [
	'WP_URL'          => 'test.test',
	'WP_ROOT_PATH'    => 'C:/path/to/wordpress/',
	'DB_HOST'         => 'localhost',
	'DB_NAME'         => 'disable_random_content_tests',
	'DB_USER'         => 'root',
	'DB_PASSWORD'     => '',
	'DB_TABLE_PREFIX' => 'wptests_',
];
