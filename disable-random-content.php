<?php
/**
 * Plugin Name: Disable Random Content (test.test)
 * Description: Disables WordPress 7.2 random content redirects on test.test.
 * Version: 1.0.0
 */

function test_test_disable_random_content_redirect(): bool {
    return false;
}

add_filter( 'wp_enable_random_content_redirect', 'test_test_disable_random_content_redirect' );

function test_test_flush_random_rewrite_rules_on_activation( bool $network_wide ): void {
    if ( ! $network_wide ) {
        flush_rewrite_rules( false );
    }
}

register_activation_hook( __FILE__, 'test_test_flush_random_rewrite_rules_on_activation' );

function test_test_restore_random_rewrite_rules( bool $network_wide ): void {
    remove_filter( 'wp_enable_random_content_redirect', 'test_test_disable_random_content_redirect' );

    if ( ! $network_wide ) {
        flush_rewrite_rules();
    }
}

register_deactivation_hook( __FILE__, 'test_test_restore_random_rewrite_rules' );

/**
 * Clear each site's saved rules after the plugin's network status changes.
 * The rules are rebuilt on that site's next request with its own rewrite settings.
 */
function test_test_clear_network_random_rewrite_rules( int $network_id ): void {
    if ( ! is_multisite() || $network_id < 1 ) {
        return;
    }

    $batch_size = 100;
    $offset     = 0;

    do {
        $site_ids = get_sites(
            array(
                'network_id' => $network_id,
                'fields'     => 'ids',
                'number'     => $batch_size,
                'offset'     => $offset,
                'orderby'    => 'id',
                'order'      => 'ASC',
            )
        );

        foreach ( $site_ids as $site_id ) {
            switch_to_blog( (int) $site_id );

            try {
                // switch_to_blog() does not rebuild the global WP_Rewrite object.
                // Let this site regenerate its own rules on its next request.
                update_option( 'rewrite_rules', '' );
            } finally {
                restore_current_blog();
            }
        }

        $offset += count( $site_ids );
    } while ( $batch_size === count( $site_ids ) );
}

function test_test_network_random_plugin_status_changed( string $option, $new_value, $old_value, int $network_id ): void {
    $plugin = plugin_basename( __FILE__ );
    $was_active = is_array( $old_value ) && isset( $old_value[ $plugin ] );
    $is_active  = is_array( $new_value ) && isset( $new_value[ $plugin ] );

    if ( $was_active !== $is_active ) {
        test_test_clear_network_random_rewrite_rules( $network_id );
    }
}

add_action( 'update_site_option_active_sitewide_plugins', 'test_test_network_random_plugin_status_changed', 10, 4 );

function test_test_network_random_plugin_status_added( string $option, $value, int $network_id ): void {
    if ( is_array( $value ) && isset( $value[ plugin_basename( __FILE__ ) ] ) ) {
        test_test_clear_network_random_rewrite_rules( $network_id );
    }
}

add_action( 'add_site_option_active_sitewide_plugins', 'test_test_network_random_plugin_status_added', 10, 3 );
