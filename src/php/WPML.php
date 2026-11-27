<?php
/**
 * WPML compatibility for archive lookups.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent;

/**
 * Keeps archive identities independent of the current WPML language.
 */
final class WPML {
	/**
	 * Read the actual terms while leaving WPML's language selection in place.
	 *
	 * @template T
	 * @param callable(): T $lookup Archive lookup.
	 * @return T Lookup result.
	 */
	public static function with_original_terms( callable $lookup ) {
		if ( ! has_filter( 'wpml_language_is_active' ) ) {
			return $lookup();
		}

		$skip_term_query_translation = static function ( array $args ): array {
			$args['wpml_skip_filters'] = true;
			return $args;
		};
		$skip_term_id_translation    = static fn(): bool => true;
		add_filter( 'get_terms_args', $skip_term_query_translation, PHP_INT_MIN );
		add_filter( 'wpml_disable_term_adjust_id', $skip_term_id_translation, PHP_INT_MAX );
		try {
			return $lookup();
		} finally {
			remove_filter( 'wpml_disable_term_adjust_id', $skip_term_id_translation, PHP_INT_MAX );
			remove_filter( 'get_terms_args', $skip_term_query_translation, PHP_INT_MIN );
		}
	}
}
