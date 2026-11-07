<?php
/**
 * Disable Random Content plugin bootstrap.
 *
 * @package DisableRandomContent
 * @wordpress-plugin
 *
 * Plugin Name:       Disable Random Content
 * Plugin URI:        https://github.com/kagg-design/disable-random-content
 * Description:       Disables WordPress 7.2 random content redirects.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Author:            KAGG Design
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       disable-random-content
 */

use KAGG\DisableRandomContent\Main;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/src/Main.php';

( new Main( __FILE__ ) )->init();
