<?php
/**
 * Integration tests for optional random archive exceptions.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent\Tests\Integration;

use KAGG\DisableRandomContent\Main;
use KAGG\DisableRandomContent\RandomArchives;
use KAGG\DisableRandomContent\Settings;
use RuntimeException;
use WP_UnitTestCase;

/**
 * Exercise settings and the corresponding WordPress request paths.
 *
 * @group integration
 */
class AllowedArchivesIntegrationTest extends WP_UnitTestCase {
	/**
	 * Original HTTP method, if set by the environment.
	 *
	 * @var string|null
	 */
	private ?string $request_method = null;

	/** Simulate a public GET request. */
	public function set_up(): void {
		parent::set_up();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Preserve the original value for tear_down().
		$this->request_method      = $_SERVER['REQUEST_METHOD'] ?? null;
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->set_permalink_structure( '/%postname%/' );
		create_initial_taxonomies();
	}

	/** Restore the test environment. */
	public function tear_down(): void {
		if ( null === $this->request_method ) {
			unset( $_SERVER['REQUEST_METHOD'] );
		} else {
			$_SERVER['REQUEST_METHOD'] = $this->request_method;
		}
		parent::tear_down();
	}

	/** An update removes stale rules once and preserves the site's archive selections. */
	public function test_rewrite_migration_preserves_selected_category(): void {
		global $wp_rewrite;

		$base     = $wp_rewrite->get_random_base();
		$category = self::factory()->category->create( [ 'slug' => 'blog' ] );
		$post     = self::factory()->post->create( [ 'post_category' => [ $category ] ] );
		$settings = [ 'terms' => [ $category ] ];
		update_option( Settings::OPTION, $settings );
		delete_option( 'disable_random_content_rewrite_rules_version' );
		update_option( 'rewrite_rules', [ $base . '/?$' => 'index.php?random=1' ] );

		$plugin = new Main();
		$plugin->maybe_migrate_rewrite_rules();

		self::assertFalse( get_option( 'rewrite_rules' ) );
		self::assertSame( $settings, get_option( Settings::OPTION ) );
		$this->go_to( home_url( "/$base/" ) );
		self::assertTrue( is_404() );
		self::assertSame( [ get_permalink( $post ), 302 ], $this->random_redirect( "/$base/category/blog/" ) );

		$rules = get_option( 'rewrite_rules' );
		self::assertNotEmpty( $rules );
		$plugin->maybe_migrate_rewrite_rules();
		self::assertSame( $rules, get_option( 'rewrite_rules' ) );
	}

	/** An earlier opt-out still blocks redirects when the site has selected archives. */
	public function test_selected_category_respects_earlier_global_opt_out(): void {
		$blog = self::factory()->category->create( [ 'slug' => 'blog' ] );
		$post = self::factory()->post->create( [ 'post_category' => [ $blog ] ] );
		update_option( Settings::OPTION, [ 'terms' => [ $blog ] ] );

		$url = '/category/blog/?random=1';
		add_filter( 'wp_enable_random_content_redirect', '__return_false', -1 );
		try {
			self::assertFalse( wp_is_random_content_redirect_enabled() );
			self::assertNull( $this->random_redirect( $url ) );
		} finally {
			remove_filter( 'wp_enable_random_content_redirect', '__return_false', -1 );
		}

		self::assertSame( [ get_permalink( $post ), 302 ], $this->random_redirect( $url ) );
	}

	/** Both URL forms select from the chosen category only. */
	public function test_only_selected_category_can_redirect(): void {
		global $wp_rewrite;
		$base    = '/' . $wp_rewrite->get_random_base();
		$blog    = self::factory()->category->create( [ 'slug' => 'blog' ] );
		$news    = self::factory()->category->create( [ 'slug' => 'news' ] );
		$blog_id = self::factory()->post->create( [ 'post_category' => [ $blog ] ] );
		self::factory()->post->create_many( 2, [ 'post_category' => [ $news ] ] );
		update_option( Settings::OPTION, [ 'terms' => [ $blog ] ] );

		foreach ( [ "$base/category/blog/", '/category/blog/?random=1', '/?cat=' . $blog . '&random=1' ] as $url ) {
			self::assertSame( [ get_permalink( $blog_id ), 302 ], $this->random_redirect( $url ), $url );
		}

		foreach ( [ "$base/", "$base/category/news/", '/?random=1', '/category/news/?random=1', '/?category_name=blog,news&random=1', '/?s=blog&cat=' . $blog . '&random=1' ] as $url ) {
			self::assertNull( $this->random_redirect( $url ), $url );
			self::assertFalse( wp_is_random_content_query( $GLOBALS['wp_query'] ), $url );
		}

		$this->go_to( home_url( '/category/news/?random=1' ) );
		self::assertTrue( is_category( $news ) );
		self::assertCount( 2, $GLOBALS['wp_query']->posts );

		update_option( Settings::OPTION, [] );
		self::assertFalse( get_option( 'rewrite_rules' ) );
		$this->go_to( home_url( "$base/category/blog/" ) );
		self::assertTrue( is_404() );
		self::assertFalse( wp_is_random_content_redirect_enabled() );
	}

	/** A configured Polylang language scopes the request without bypassing the archive selection. */
	public function test_polylang_language_preserves_only_selected_archive_redirects(): void {
		require_once __DIR__ . '/fixtures/polylang.php';

		$blog      = self::factory()->category->create( [ 'slug' => 'blog' ] );
		$news      = self::factory()->category->create( [ 'slug' => 'news' ] );
		$archives  = new RandomArchives();
		$languages = static fn() => [ 'en', 'fr' ];
		update_option( Settings::OPTION, [ 'terms' => [ $blog ] ] );

		add_filter( 'disable_random_content_test_polylang_languages', $languages );
		try {
			$selected = [
				'cat'    => $blog,
				'lang'   => 'en',
				'random' => '1',
			];
			self::assertSame( $selected, $archives->filter_request( $selected ) );
			self::assertSame(
				[
					'cat'  => $blog,
					'lang' => 'unknown',
				],
				$archives->filter_request(
					[
						'cat'    => $blog,
						'lang'   => 'unknown',
						'random' => '1',
					]
				)
			);
			self::assertSame(
				[
					'cat'  => $news,
					'lang' => 'fr',
				],
				$archives->filter_request(
					[
						'cat'    => $news,
						'lang'   => 'fr',
						'random' => '1',
					]
				)
			);
			self::assertSame(
				[ 'lang' => 'en' ],
				$archives->filter_request(
					[
						'lang'   => 'en',
						'random' => '1',
					]
				)
			);
		} finally {
			remove_filter( 'disable_random_content_test_polylang_languages', $languages );
		}
	}

	/** Term selection uses IDs and follows a parent slug change. */
	public function test_selected_child_route_follows_renamed_parent(): void {
		global $wp_rewrite;
		$base   = '/' . $wp_rewrite->get_random_base();
		$parent = self::factory()->category->create( [ 'slug' => 'blog' ] );
		$child  = self::factory()->category->create(
			[
				'slug'   => 'reviews',
				'parent' => $parent,
			]
		);
		$post   = self::factory()->post->create( [ 'post_category' => [ $child ] ] );
		update_option( Settings::OPTION, [ 'terms' => [ $child ] ] );
		self::assertSame( [ get_permalink( $post ), 302 ], $this->random_redirect( "$base/category/blog/reviews/" ) );
		$this->go_to( home_url( "$base/category/blog/" ) );
		self::assertTrue( is_404() );

		wp_update_term( $parent, 'category', [ 'slug' => 'journal' ] );
		self::assertFalse( get_option( 'rewrite_rules' ) );
		self::assertSame( [ get_permalink( $post ), 302 ], $this->random_redirect( "$base/category/journal/reviews/" ) );
		$this->go_to( home_url( "$base/category/blog/reviews/" ) );
		self::assertTrue( is_404() );

		wp_delete_term( $child, 'category' );
		self::assertSame( [], Settings::get()['terms'] );
		self::assertFalse( wp_is_random_content_redirect_enabled() );
	}

	/** Encoded tag slugs and plain tag IDs identify the same archive. */
	public function test_selected_tag_supports_both_url_forms(): void {
		global $wp_rewrite;
		$tag  = self::factory()->tag->create_and_get( [ 'name' => 'Обзоры' ] );
		$post = self::factory()->post->create();
		wp_set_post_tags( $post, [ $tag->name ] );
		update_option( Settings::OPTION, [ 'terms' => [ $tag->term_id ] ] );
		$base = '/' . $wp_rewrite->get_random_base();
		foreach ( [ "$base/tag/$tag->slug/", "/tag/$tag->slug/?random=1", '/?tag_id=' . $tag->term_id . '&random=1' ] as $url ) {
			self::assertSame( [ get_permalink( $post ), 302 ], $this->random_redirect( $url ), $url );
		}
	}

	/** Eligible types and custom taxonomies are available in both URL forms. */
	public function test_custom_archives_and_settings_validation(): void {
		global $wp_rewrite;
		$base = '/' . $wp_rewrite->get_random_base();
		register_post_type(
			'project',
			[
				'public'      => true,
				'has_archive' => 'projects',
			]
		);
		register_taxonomy(
			'project_topic',
			'project',
			[
				'public'    => true,
				'query_var' => false,
				'rewrite'   => [ 'slug' => 'topics' ],
			]
		);
		try {
			$topic   = self::factory()->term->create(
				[
					'taxonomy' => 'project_topic',
					'slug'     => 'research',
				]
			);
			$project = self::factory()->post->create( [ 'post_type' => 'project' ] );
			wp_set_object_terms( $project, [ $topic ], 'project_topic' );
			$settings = new Settings();
			$value    = $settings->sanitize(
				[
					'terms'      => [ (string) $topic, $topic, 'x', -1, [ 3 ] ],
					'post_types' => [ 'project', 'page', 'post', [ 'project' ] ],
				]
			);
			self::assertSame( [ $topic ], $value['terms'] );
			self::assertSame( [ 'project' ], $value['post_types'] );
			update_option( Settings::OPTION, $value );

			foreach ( [ "$base/projects/", '/projects/?random=1', "$base/topics/research/", '/topics/research/?random=1', '/?taxonomy=project_topic&term=research&random=1' ] as $url ) {
				self::assertSame( [ get_permalink( $project ), 302 ], $this->random_redirect( $url ), $url );
			}
		} finally {
			unregister_taxonomy( 'project_topic' );
			unregister_post_type( 'project' );
		}
	}

	/** The form shows the optional notice first and restores saved selections. */
	public function test_admin_renders_optional_settings(): void {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$tag = self::factory()->tag->create( [ 'name' => 'Tutorials' ] );
		update_option( Settings::OPTION, [ 'terms' => [ $tag ] ] );
		$settings = new Settings();
		$settings->register_fields();

		ob_start();
		try {
			$settings->render_page();
			$html = ob_get_contents();
		} finally {
			ob_end_clean();
			wp_set_current_user( 0 );
		}

		self::assertStringContainsString( 'All settings are optional.', $html );
		self::assertLessThan( strpos( $html, '<form' ), strpos( $html, 'All settings are optional.' ) );
		self::assertStringContainsString( Settings::OPTION . '[terms][]', $html );
		self::assertStringContainsString( Settings::OPTION . '[post_types][]', $html );
		self::assertMatchesRegularExpression( '/value="' . $tag . '"\s+selected=[^>]+>Tutorials<\/option>/', $html );
	}

	/**
	 * Capture the core redirect before it exits, including versions that redirect inside WP::main().
	 *
	 * @param string $url Site-relative request URL.
	 * @return array|null Redirect location and status, if any.
	 * @throws RuntimeException If processing fails before the redirect is captured.
	 */
	private function random_redirect( string $url ): ?array {
		$redirect = null;
		$capture  = static function ( $location, $status ) use ( &$redirect ): void {
			$redirect = [ $location, $status ];
			throw new RuntimeException( 'Redirect captured.' );
		};
		add_filter( 'wp_redirect', $capture, 10, 2 );
		try {
			$this->go_to( home_url( $url ) );
			wp_random_content_redirect();
		} catch ( RuntimeException $exception ) {
			if ( null === $redirect ) {
				throw $exception;
			}
		} finally {
			remove_filter( 'wp_redirect', $capture );
		}

		return $redirect;
	}
}
