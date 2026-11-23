<?php
/**
 * Disable Random Content plugin bootstrap.
 *
 * @package DisableRandomContent
 * @author            KAGG Design, Ernest Behinov
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name:       Disable Random Content
 * Plugin URI:        https://github.com/kagg-design/disable-random-content
 * Description:       Disables WordPress 7.2 random content redirects.
 * Version:           1.3.1
 * Requires at least: 7.1
 * Requires PHP:      7.4
 * Author:            KAGG Design, Ernest Behinov
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       disable-random-content
 */

use KAGG\DisableRandomContent\Main;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
const DISABLE_RANDOM_CONTENT_VERSION = '1.3.1';

/**
 * Main plugin file.
 */
const DISABLE_RANDOM_CONTENT_FILE = __FILE__;

/**
 * Base URL of the plugin directory.
 */
define( 'DISABLE_RANDOM_CONTENT_URL', untrailingslashit( plugin_dir_url( DISABLE_RANDOM_CONTENT_FILE ) ) );

require_once __DIR__ . '/vendor/autoload.php';

( new Main() )->init();
