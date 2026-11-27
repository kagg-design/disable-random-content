<?php
/**
 * Minimal Polylang language list for the archive request tests.
 *
 * @package DisableRandomContent
 */

/**
 * Return language slugs configured by the current test.
 *
 * @param array $args Polylang language list options.
 * @return string[] Language slugs.
 */
function pll_languages_list( array $args = [] ): array {
	return apply_filters( 'disable_random_content_test_polylang_languages', [], $args );
}
