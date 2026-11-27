<?php
/**
 * Limit WordPress random redirects to the configured archives.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent;

use WP_MatchesMapRegex;
use WP_Rewrite;
use WP_Term;

/**
 * Filters rewrite generation and the parsed request before core selects a post.
 */
final class RandomArchives {

	/** Attach the filters and invalidation hooks. */
	public function init(): void {
		if ( ! method_exists( WP_Rewrite::class, 'get_random_base' ) ) {
			return;
		}

		add_filter( 'wp_enable_random_content_redirect', [ $this, 'has_exceptions' ], 0 );
		add_filter( 'random_rewrite_rules', [ $this, 'filter_rewrite_rules' ] );
		add_filter( 'request', [ $this, 'filter_request' ], PHP_INT_MAX );

		foreach ( [ 'add', 'update', 'delete' ] as $change ) {
			add_action( $change . '_option_' . Settings::OPTION, [ $this, 'invalidate_rewrite_rules' ] );
		}
		add_action( 'edited_term', [ $this, 'term_changed' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'term_changed' ], 10, 3 );
	}

	/** Detach the hooks before core regenerates rules on deactivation. */
	public function remove_hooks(): void {
		remove_filter( 'wp_enable_random_content_redirect', [ $this, 'has_exceptions' ], 0 );
		remove_filter( 'random_rewrite_rules', [ $this, 'filter_rewrite_rules' ] );
		remove_filter( 'request', [ $this, 'filter_request' ], PHP_INT_MAX );

		foreach ( [ 'add', 'update', 'delete' ] as $change ) {
			remove_action( $change . '_option_' . Settings::OPTION, [ $this, 'invalidate_rewrite_rules' ] );
		}
		remove_action( 'edited_term', [ $this, 'term_changed' ] );
		remove_action( 'delete_term', [ $this, 'term_changed' ] );
	}

	/**
	 * Keep the core feature enabled for selected archives when earlier filters allow it.
	 *
	 * @param bool $enabled Whether earlier filters allow random redirects.
	 * @return bool Whether selected archives may redirect.
	 */
	public function has_exceptions( bool $enabled = true ): bool {
		$settings = Settings::get();
		return $enabled && ( $settings['terms'] || $settings['post_types'] );
	}

	/**
	 * Drop the random flag from unapproved requests, preserving the underlying archive.
	 *
	 * @param array $query_vars Parsed WordPress request variables.
	 * @return array Request variables.
	 */
	public function filter_request( array $query_vars ): array {
		if ( ! isset( $query_vars['random'] ) ) {
			return $query_vars;
		}

		$settings = Settings::get();
		if ( ( $settings['terms'] || $settings['post_types'] ) && ! $this->allows( $query_vars, $settings ) ) {
			unset( $query_vars['random'] );
		}

		return $query_vars;
	}

	/**
	 * Keep selected post type rules and narrow taxonomy rules to the selected term paths.
	 *
	 * @param array<string, string> $rules Generated WordPress random rules.
	 * @return array<string, string> Rules for the selected archives.
	 */
	public function filter_rewrite_rules( array $rules ): array {
		// With empty settings, honor a site's explicit override of the core filter.
		$settings = Settings::get();

		if ( ! $rules || ( ! $settings['terms'] && ! $settings['post_types'] ) ) {
			return $rules;
		}

		return WPML::with_original_terms( fn() => $this->allowed_rewrite_rules( $rules, $settings ) );
	}

	/**
	 * Generate rules for the archives selected by their original IDs.
	 *
	 * @param array<string, string> $rules    Generated WordPress random rules.
	 * @param array                 $settings Site settings.
	 * @return array<string, string> Rules for the selected archives.
	 */
	private function allowed_rewrite_rules( array $rules, array $settings ): array {
		global $wp_rewrite;

		$allowed = [];
		foreach ( $rules as $regex => $destination ) {
			wp_parse_str( (string) wp_parse_url( $destination, PHP_URL_QUERY ), $query_vars );
			if ( isset( $query_vars['post_type'] ) && $this->allows( $query_vars, $settings ) ) {
				$allowed[ $regex ] = $destination;
			}
		}

		foreach ( $settings['terms'] as $term_id ) {
			$term     = get_term( $term_id );
			$taxonomy = $term instanceof WP_Term ? get_taxonomy( $term->taxonomy ) : false;
			if ( ! $taxonomy || empty( $taxonomy->rewrite['slug'] ) ) {
				continue;
			}

			$slug = $term->slug;
			if ( ! empty( $taxonomy->rewrite['hierarchical'] ) ) {
				$path = get_term_parents_list(
					$term_id,
					$term->taxonomy,
					[
						'format' => 'slug',
						'link'   => false,
					]
				);
				$slug = is_string( $path ) ? rtrim( $path, '/' ) : $slug;
			}

			$path = $wp_rewrite->root . $wp_rewrite->get_random_base() . '/' . $taxonomy->rewrite['slug'] . '/' . $slug;
			foreach ( $rules as $regex => $destination ) {
				if ( ! preg_match( "#^$regex#", $path, $matches ) ) {
					continue;
				}

				$destination = WP_MatchesMapRegex::apply( $destination, $matches );
				wp_parse_str( (string) wp_parse_url( $destination, PHP_URL_QUERY ), $query_vars );
				unset( $query_vars['random'] );
				if ( $this->term_id( $query_vars ) === $term_id ) {
					$allowed[ preg_quote( rawurldecode( $path ), '#' ) . '/?$' ] = $destination;
					break;
				}
			}
		}

		return $allowed;
	}

	/** Invalidate this site's saved rules after settings change. */
	public function invalidate_rewrite_rules(): void {
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Refresh term paths after an edit or deletion, including changes to parent terms.
	 *
	 * @param int    $term_id  Changed term ID.
	 * @param int    $tt_id    Changed term taxonomy ID.
	 * @param string $taxonomy Changed taxonomy.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function term_changed( int $term_id, int $tt_id, string $taxonomy ): void {
		$settings = Settings::get();
		if ( 'delete_term' === current_filter() && in_array( $term_id, $settings['terms'], true ) ) {
			$settings['terms'] = array_values( array_diff( $settings['terms'], [ $term_id ] ) );
			update_option( Settings::OPTION, $settings );
			return;
		}

		foreach ( $settings['terms'] as $allowed_id ) {
			if ( $term_id === $allowed_id || get_term( $allowed_id, $taxonomy ) instanceof WP_Term ) {
				$this->invalidate_rewrite_rules();
				return;
			}
		}
	}

	/**
	 * Check an individual archive using the same selection for both URL forms.
	 *
	 * @param array $query_vars Parsed request or rewrite variables.
	 * @param array $settings   Site settings.
	 * @return bool Whether random selection may run.
	 */
	private function allows( array $query_vars, array $settings ): bool {
		unset( $query_vars['random'], $query_vars['paged'] );

		// A configured language scopes the selected archive without identifying it.
		$language = $query_vars['lang'] ?? null;

		if (
			is_string( $language ) &&
			(
				(
					function_exists( 'pll_languages_list' ) &&
					in_array( $language, pll_languages_list( [ 'fields' => 'slug' ] ), true )
				) ||
				true === apply_filters( 'wpml_language_is_active', null, $language )
			)
		) {
			unset( $query_vars['lang'] );
		}

		if ( 1 === count( $query_vars ) && isset( $query_vars['post_type'] ) ) {
			$type = $query_vars['post_type'];
			return in_array( $type, $settings['post_types'], true ) && isset( Settings::post_types()[ $type ] );
		}

		return $settings['terms'] && in_array( $this->term_id( $query_vars ), $settings['terms'], true );
	}

	/**
	 * Resolve the term used by a single archive request.
	 *
	 * @param array $query_vars Archive variables.
	 * @return int Matching term ID or zero.
	 */
	private function term_id( array $query_vars ): int {
		$field = 'slug';
		if ( 2 === count( $query_vars ) && isset( $query_vars['taxonomy'], $query_vars['term'] ) ) {
			$taxonomy = $query_vars['taxonomy'];
			$value    = $query_vars['term'];
		} elseif ( 1 === count( $query_vars ) ) {
			$key   = key( $query_vars );
			$value = $query_vars[ $key ];
			if ( 'cat' === $key || 'tag_id' === $key ) {
				$taxonomy = 'cat' === $key ? 'category' : 'post_tag';
				$field    = 'id';
			} else {
				$taxonomies = get_taxonomies(
					[
						'publicly_queryable' => true,
						'query_var'          => $key,
					]
				);
				$taxonomy   = reset( $taxonomies );
			}
		} else {
			return 0;
		}

		if ( ! is_string( $taxonomy ) || ( ! is_string( $value ) && ! is_int( $value ) ) ) {
			return 0;
		}

		$object = get_taxonomy( $taxonomy );
		$value  = (string) $value;
		if ( '' === $value || ! $object || ! Settings::supports_taxonomy( $object ) || strpbrk( $value, ",+ \t\r\n" ) ) {
			return 0;
		}

		if ( 'id' === $field && ! ctype_digit( $value ) ) {
			return 0;
		}
		if ( 'slug' === $field && ! empty( $object->rewrite['hierarchical'] ) && isset( $query_vars[ $object->query_var ] ) ) {
			$value = wp_basename( $value );
		}

		$term = WPML::with_original_terms( static fn() => get_term_by( $field, $value, $taxonomy ) );
		return $term->term_id ?? 0;
	}
}
