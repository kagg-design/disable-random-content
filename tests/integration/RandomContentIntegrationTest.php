<?php
/**
 * Random content integration tests.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent\Tests\Integration;

use WP_UnitTestCase;

/**
 * Exercise the WordPress paths gated by the plugin's filter.
 *
 * @group integration
 */
class RandomContentIntegrationTest extends WP_UnitTestCase {
	/**
	 * The plugin disables the core feature through its public filter.
	 */
	public function test_core_feature_is_disabled(): void {
		self::assertFalse( wp_is_random_content_redirect_enabled() );
	}

	/**
	 * A later callback can explicitly restore the core feature.
	 */
	public function test_later_filter_can_enable_random_content(): void {
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		$enable = static function (): bool {
			return true;
		};

		add_filter( 'wp_enable_random_content_redirect', $enable, 5 );

		try {
			self::assertTrue( wp_is_random_content_redirect_enabled() );
			self::assertNotEmpty( $wp_rewrite->random_rewrite_rules() );
		} finally {
			remove_filter( 'wp_enable_random_content_redirect', $enable, 5 );
		}

		self::assertFalse( wp_is_random_content_redirect_enabled() );
	}

	/**
	 * The disabled feature adds no pretty permalink routes.
	 */
	public function test_random_rewrite_rules_are_absent(): void {
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules( false );

		self::assertSame( [], $wp_rewrite->random_rewrite_rules() );

		foreach ( (array) get_option( 'rewrite_rules' ) as $destination ) {
			self::assertStringNotContainsString( 'random=1', $destination );
		}
	}

	/**
	 * A random query variable leaves the normal archive query in place.
	 */
	public function test_random_query_runs_as_an_ordinary_archive(): void {
		$post_ids = self::factory()->post->create_many( 2 );

		$this->go_to( '/?random=1' );

		global $wp_query;

		self::assertTrue( $wp_query->is_home() );
		self::assertTrue( $wp_query->is_random() );
		self::assertFalse( wp_is_random_content_query( $wp_query ) );
		self::assertContains( $post_ids[0], wp_list_pluck( $wp_query->posts, 'ID' ) );
		self::assertContains( $post_ids[1], wp_list_pluck( $wp_query->posts, 'ID' ) );
	}

	/**
	 * Core query modifiers do not apply even when called directly.
	 */
	public function test_random_query_modifiers_leave_normal_values_unchanged(): void {
		$this->go_to( '/?random=1' );

		global $wp_query;

		self::assertSame( 'post_date DESC', wp_random_content_posts_orderby( 'post_date DESC', $wp_query ) );
		self::assertSame( 'LIMIT 0, 10', wp_random_content_post_limits( 'LIMIT 0, 10', $wp_query ) );
	}

	/**
	 * Post changes at the random base do not flush rules while disabled.
	 */
	public function test_post_at_random_base_does_not_flush_rules(): void {
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$post = self::factory()->post->create_and_get(
			[
				'post_name'   => $wp_rewrite->get_random_base(),
				'post_status' => 'publish',
			]
		);

		$sentinel = [ 'sentinel/?$' => 'index.php?test=1' ];
		update_option( 'rewrite_rules', $sentinel );

		wp_random_content_flush_rewrite_rules_for_post( $post->ID, $post );

		self::assertSame( $sentinel, get_option( 'rewrite_rules' ) );
	}
}
