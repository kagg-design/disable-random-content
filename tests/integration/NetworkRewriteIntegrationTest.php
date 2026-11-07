<?php
/**
 * Multisite rewrite rule integration tests.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent\Tests\Integration;

use WP_UnitTestCase;

/**
 * Exercise network status transitions across separate site options tables.
 *
 * @group integration
 * @group ms-required
 */
class NetworkRewriteIntegrationTest extends WP_UnitTestCase {
	/**
	 * Network activation and deactivation invalidate every site's saved rules.
	 */
	public function test_network_transitions_clear_each_site_rules(): void {
		if ( ! is_multisite() ) {
			self::markTestSkipped( 'Requires a WordPress multisite test run.' );
		}

		$site_ids = [
			get_current_blog_id(),
			self::factory()->blog->create(),
			self::factory()->blog->create(),
		];
		$plugin   = plugin_basename( dirname( __DIR__, 2 ) . '/plugin.php' );

		update_site_option( 'active_sitewide_plugins', [] );
		$this->set_site_rules( $site_ids );
		update_site_option( 'active_sitewide_plugins', [ $plugin => time() ] );
		$this->assert_site_rules_cleared( $site_ids );

		$this->set_site_rules( $site_ids );
		update_site_option( 'active_sitewide_plugins', [] );
		$this->assert_site_rules_cleared( $site_ids );
	}

	/**
	 * Each site regenerates deleted rules using its own permalink structure.
	 */
	public function test_deleted_rules_regenerate_in_each_site_context(): void {
		if ( ! is_multisite() ) {
			self::markTestSkipped( 'Requires a WordPress multisite test run.' );
		}

		$site_ids = [
			self::factory()->blog->create(),
			self::factory()->blog->create(),
		];
		$plugin   = plugin_basename( dirname( __DIR__, 2 ) . '/plugin.php' );

		update_site_option( 'active_sitewide_plugins', [] );

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );

			try {
				update_option( 'permalink_structure', '/site-' . $site_id . '/%postname%/' );
				update_option( 'rewrite_rules', [ 'sentinel' => 'index.php' ] );
			} finally {
				restore_current_blog();
			}
		}

		update_site_option( 'active_sitewide_plugins', [ $plugin => time() ] );

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );

			try {
				self::assertFalse( get_option( 'rewrite_rules' ) );

				$rewrite = new \WP_Rewrite();
				$rules   = $rewrite->wp_rewrite_rules();

				self::assertIsArray( $rules );
				self::assertNotEmpty( $rules );
				self::assertSame( $rules, get_option( 'rewrite_rules' ) );
				self::assertNotEmpty( preg_grep( '#site-' . $site_id . '/#', array_keys( $rules ) ) );
			} finally {
				restore_current_blog();
			}
		}

		update_site_option( 'active_sitewide_plugins', [] );
	}

	/**
	 * The site query continues past the first batch of IDs.
	 */
	public function test_network_change_clears_sites_after_first_batch(): void {
		if ( ! is_multisite() ) {
			self::markTestSkipped( 'Requires a WordPress multisite test run.' );
		}

		$site_ids = self::factory()->blog->create_many( 101 );
		$last_id  = end( $site_ids );
		$plugin   = plugin_basename( dirname( __DIR__, 2 ) . '/plugin.php' );

		update_site_option( 'active_sitewide_plugins', [] );
		$this->set_site_rules( [ $last_id ] );
		update_site_option( 'active_sitewide_plugins', [ $plugin => time() ] );

		switch_to_blog( $last_id );
		self::assertFalse( get_option( 'rewrite_rules' ) );
		restore_current_blog();
		update_site_option( 'active_sitewide_plugins', [] );
	}

	/**
	 * The first creation of the network plugin option also clears saved rules.
	 */
	public function test_first_network_activation_clears_saved_rules(): void {
		if ( ! is_multisite() ) {
			self::markTestSkipped( 'Requires a WordPress multisite test run.' );
		}

		$site_id = self::factory()->blog->create();
		$plugin  = plugin_basename( dirname( __DIR__, 2 ) . '/plugin.php' );

		delete_site_option( 'active_sitewide_plugins' );
		$this->set_site_rules( [ $site_id ] );
		add_site_option( 'active_sitewide_plugins', [ $plugin => time() ] );

		$this->assert_site_rules_cleared( [ $site_id ] );
		update_site_option( 'active_sitewide_plugins', [] );
	}

	/**
	 * Store a detectable rule in each site.
	 *
	 * @param int[] $site_ids Site IDs.
	 */
	private function set_site_rules( array $site_ids ): void {
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			update_option( 'rewrite_rules', [ 'sentinel' => 'index.php' ] );
			restore_current_blog();
		}
	}

	/**
	 * Confirm that rules were cleared in each site's options table.
	 *
	 * @param int[] $site_ids Site IDs.
	 */
	private function assert_site_rules_cleared( array $site_ids ): void {
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			self::assertFalse( get_option( 'rewrite_rules' ) );
			restore_current_blog();
		}
	}
}
