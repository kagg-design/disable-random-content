<?php
/**
 * Plugin hooks and rewrite rule lifecycle.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent;

use WP_Rewrite;

/**
 * Disables random redirects and manages the plugin's rewrite rule lifecycle.
 */
final class Main {
	/** Keep only this many site IDs in memory at a time; all batches are processed. */
	private const SITE_BATCH_SIZE = 100;

	/** Per-site marker for completed rewrite rule migrations. */
	private const REWRITE_RULES_VERSION_OPTION = 'disable_random_content_rewrite_rules_version';

	/** Current rewrite rule migration version. */
	private const REWRITE_RULES_VERSION = 1;

	/**
	 * Random archive hooks.
	 *
	 * @var RandomArchives
	 */
	private RandomArchives $archives;

	/**
	 * Create the random archive hook handler.
	 */
	public function __construct() {
		$this->archives = new RandomArchives();
	}

	/**
	 * Register the plugin's WordPress hooks.
	 */
	public function init(): void {
		$this->archives->init();

		add_action( 'init', [ $this, 'maybe_migrate_rewrite_rules' ] );

		if ( is_admin() ) {
			( new Settings() )->init();
		}

		add_action( 'update_site_option_active_sitewide_plugins', [ $this, 'network_status_updated' ], 10, 4 );
		add_action( 'add_site_option_active_sitewide_plugins', [ $this, 'network_status_added' ], 10, 3 );

		register_activation_hook( DISABLE_RANDOM_CONTENT_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( DISABLE_RANDOM_CONTENT_FILE, [ $this, 'deactivate' ] );
	}

	/** Clear stale rules once on each site, including after an update of the active plugin. */
	public function maybe_migrate_rewrite_rules(): void {
		if ( ! method_exists( WP_Rewrite::class, 'get_random_base' ) ||
			(int) get_option( self::REWRITE_RULES_VERSION_OPTION, 0 ) >= self::REWRITE_RULES_VERSION ) {
			return;
		}

		// WordPress regenerates these rules in this site's context when it needs them.
		if ( delete_option( 'rewrite_rules' ) || ! get_option( 'rewrite_rules' ) ) {
			update_option( self::REWRITE_RULES_VERSION_OPTION, self::REWRITE_RULES_VERSION, true );
		}
	}

	/**
	 * Restore the disabling filter and refresh rules on ordinary activation.
	 *
	 * @param bool $network_wide Whether the plugin is activated for the network.
	 */
	public function activate( bool $network_wide ): void {
		// The plugin file is not loaded again when reactivated in the same request.
		$this->archives->init();

		if ( ! $network_wide ) {
			$this->refresh_site_rewrite_rules();
		}
	}

	/**
	 * Restore the current site's rules on ordinary deactivation.
	 *
	 * @param bool $network_wide Whether the plugin is deactivated for the network.
	 */
	public function deactivate( bool $network_wide ): void {
		$this->archives->remove_hooks();

		if ( ! $network_wide ) {
			$this->refresh_site_rewrite_rules();
		}
	}

	/**
	 * Refresh the current site's rules after its active plugins change.
	 */
	private function refresh_site_rewrite_rules(): void {
		if ( did_action( 'wp_loaded' ) ) {
			flush_rewrite_rules( false );
			return;
		}

		// Remote management tools can end the request before a deferred flush runs.
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Invalidate site rules after network activation or deactivation completes.
	 *
	 * @param string $option     Network option name.
	 * @param mixed  $new_value  New network-wide active plugin list.
	 * @param mixed  $old_value  Previous network-wide active plugin list.
	 * @param int    $network_id Network ID.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function network_status_updated( string $option, $new_value, $old_value, int $network_id ): void {
		$plugin     = plugin_basename( DISABLE_RANDOM_CONTENT_FILE );
		$was_active = is_array( $old_value ) && isset( $old_value[ $plugin ] );
		$is_active  = is_array( $new_value ) && isset( $new_value[ $plugin ] );

		if ( $was_active !== $is_active ) {
			$this->clear_network_rewrite_rules( $network_id );
		}
	}

	/**
	 * Handle the first creation of the network-wide active plugin list.
	 *
	 * @param string $option     Network option name.
	 * @param mixed  $value      Network-wide active plugin list.
	 * @param int    $network_id Network ID.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function network_status_added( string $option, $value, int $network_id ): void {
		if ( is_array( $value ) && isset( $value[ plugin_basename( DISABLE_RANDOM_CONTENT_FILE ) ] ) ) {
			$this->clear_network_rewrite_rules( $network_id );
		}
	}

	/**
	 * Clear saved rules for every site in the affected network.
	 *
	 * @param int $network_id Network ID.
	 */
	private function clear_network_rewrite_rules( int $network_id ): void {
		if ( $network_id < 1 || ! is_multisite() ) {
			return;
		}

		$offset = 0;

		do {
			$site_ids = get_sites(
				[
					'network_id' => $network_id,
					'fields'     => 'ids',
					'number'     => self::SITE_BATCH_SIZE,
					'offset'     => $offset,
					'orderby'    => 'id',
					'order'      => 'ASC',
				]
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );

				try {
					// Core also deletes rewrite_rules for a switched site in update_home_siteurl().
					// A flush here could save rules from the original site's WP_Rewrite object.
					// The next request to this site regenerates rules with its own settings.
					delete_option( 'rewrite_rules' );
				} finally {
					restore_current_blog();
				}
			}

			$site_count = count( $site_ids );
			$offset    += $site_count;
		} while ( self::SITE_BATCH_SIZE === $site_count );
	}
}
