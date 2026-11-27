<?php
/**
 * Site settings and their administration screen.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent;

use WP_Post_Type;
use WP_Rewrite;
use WP_Taxonomy;
use WP_Term;

/**
 * Stores the two archive selections in a single site option.
 */
final class Settings {
	/** Settings page slug and option group. */
	private const PAGE = 'disable-random-content';

	/** Site option name. */
	public const OPTION = 'disable_random_content_settings';

	/** Public Blueprint endpoint used by the Playground demo. */
	private const DEMO_BLUEPRINT_URL = 'https://wordpress.org/plugins/wp-json/plugins/v1/plugin/disable-random-content/blueprint.json';

	/**
	 * Register the settings screen and plugin action link.
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'register_fields' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( DISABLE_RANDOM_CONTENT_FILE ), [ $this, 'add_settings_link' ] );

		if ( defined( 'DISABLE_RANDOM_CONTENT_DEMO' ) && true === DISABLE_RANDOM_CONTENT_DEMO ) {
			add_action( 'admin_notices', [ $this, 'render_demo_notice' ] );
		}

		add_action(
			'admin_enqueue_scripts',
			static function ( string $screen ): void {
				if ( 'settings_page_' . self::PAGE !== $screen ) {
					return;
				}

				wp_enqueue_style(
					self::PAGE,
					DISABLE_RANDOM_CONTENT_URL . '/assets/css/settings.css',
					[],
					DISABLE_RANDOM_CONTENT_VERSION
				);
			}
		);
	}

	/**
	 * Point Playground visitors to a demo with random redirects available.
	 *
	 * @noinspection HtmlUnknownTarget
	 * @noinspection HtmlUnknownAttribute
	 */
	public function render_demo_notice(): void {
		$message    = esc_html__( 'Try Disable Random Content on the demo homepage. See which random links work and which are blocked.', 'disable-random-content' );
		$url        = home_url( '/' );
		$label      = esc_html__( 'Open demo homepage', 'disable-random-content' );
		$attributes = '';

		if ( ! method_exists( WP_Rewrite::class, 'get_random_base' ) ) {
			$message    = esc_html__( 'Random redirects require WordPress 7.2. Open Site Settings in the Playground toolbar at the bottom. Under WordPress Version, choose 7.2 or a 7.2 release candidate, then click Create a fresh Playground.', 'disable-random-content' );
			$url        = add_query_arg(
				[
					'wp'            => 'beta',
					'url'           => rawurlencode( '/' ),
					'blueprint-url' => rawurlencode( self::DEMO_BLUEPRINT_URL ),
				],
				'https://playground.wordpress.net/'
			);
			$label      = esc_html__( 'Open WordPress 7.2 demo', 'disable-random-content' );
			$attributes = ' target="_blank" rel="noopener noreferrer"';
		}

		wp_admin_notice(
			sprintf(
				'%s <a class="button button-primary" href="%s"%s>%s</a>',
				$message,
				esc_url( $url ),
				$attributes,
				$label
			),
			[ 'type' => 'info' ]
		);
	}

	/**
	 * Read the selections for the current site.
	 *
	 * @return array{terms: int[], post_types: string[]} Site settings.
	 */
	public static function get(): array {
		$settings = (array) get_option( self::OPTION, [] );

		return [
			'terms'      => array_filter( (array) ( $settings['terms'] ?? [] ), 'is_int' ),
			'post_types' => array_filter( (array) ( $settings['post_types'] ?? [] ), 'is_string' ),
		];
	}

	/**
	 * Get public post types with random archive support.
	 *
	 * @return array<string, WP_Post_Type> Eligible post types.
	 */
	public static function post_types(): array {
		$types = get_post_types(
			[
				'publicly_queryable' => true,
				'randomable'         => true,
			],
			'objects'
		);
		return array_filter( $types, static fn( WP_Post_Type $type ): bool => (bool) $type->has_archive );
	}

	/**
	 * Check whether a taxonomy is publicly queryable and used by a randomable type.
	 *
	 * @param WP_Taxonomy $taxonomy Taxonomy to check.
	 *
	 * @return bool Whether its archives can be selected.
	 */
	public static function supports_taxonomy( WP_Taxonomy $taxonomy ): bool {
		return $taxonomy->publicly_queryable && array_intersect( $taxonomy->object_type, get_post_types( [ 'randomable' => true ] ) );
	}

	/**
	 * Add the page under the WordPress Settings menu.
	 */
	public function add_page(): void {
		$title = __( 'Disable Random Content', 'disable-random-content' );
		add_options_page( $title, $title, 'manage_options', self::PAGE, [ $this, 'render_page' ] );
	}

	/**
	 * Add a shortcut to the site settings from its plugins list.
	 *
	 * @param array $links Plugin action links.
	 *
	 * @return array Updated action links.
	 * @noinspection HtmlUnknownTarget
	 */
	public function add_settings_link( array $links ): array {
		if ( current_user_can( 'manage_options' ) ) {
			$links = [
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ),
					esc_html__( 'Settings', 'disable-random-content' )
				),
			] + $links;
		}

		return $links;
	}

	/**
	 * Register the settings section and multiple-selection fields.
	 */
	public function register_fields(): void {
		if ( ! method_exists( WP_Rewrite::class, 'get_random_base' ) ) {
			return;
		}

		register_setting( self::PAGE, self::OPTION, [ 'sanitize_callback' => [ $this, 'sanitize' ] ] );
		add_settings_section(
			'archives',
			__( 'Allowed archives', 'disable-random-content' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Hold Ctrl or Command while clicking to change individual selections.', 'disable-random-content' ) . '</p>';
			},
			self::PAGE
		);

		$fields = [
			'terms'      => [
				'label'       => __( 'Categories, tags, and other taxonomies', 'disable-random-content' ),
				'description' => __( 'Choose individual archives. Each subcategory needs its own selection to have a random link.', 'disable-random-content' ),
			],
			'post_types' => [
				'label'       => __( 'Post type archives', 'disable-random-content' ),
				'description' => __( 'Choose which post type archives may redirect to a random entry.', 'disable-random-content' ),
			],
		];

		foreach ( $fields as $key => $field ) {
			$field['key']       = $key;
			$field['label_for'] = self::PAGE . '-' . $key;
			add_settings_field( $key, $field['label'], [ $this, 'render_field' ], self::PAGE, 'archives', $field );
		}
	}

	/**
	 * Keep only existing, eligible selections submitted by WordPress.
	 *
	 * @param mixed $input Submitted settings.
	 *
	 * @return array{terms: int[], post_types: string[]} Valid settings.
	 */
	public function sanitize( $input ): array {
		$terms = [];

		foreach ( (array) ( $input['terms'] ?? [] ) as $value ) {
			if ( (int) $value < 1 || ( ! is_string( $value ) && ! is_int( $value ) ) || ! ctype_digit( (string) $value ) ) {
				continue;
			}

			// Validate the submitted ID itself, including when WPML translates get_term().
			$term     = WP_Term::get_instance( (int) $value );
			$taxonomy = $term instanceof WP_Term ? get_taxonomy( $term->taxonomy ) : false;

			if ( $taxonomy && self::supports_taxonomy( $taxonomy ) ) {
				$terms[ $term->term_id ] = $term->term_id;
			}
		}

		$post_types = array_intersect( array_keys( self::post_types() ), array_filter( (array) ( $input['post_types'] ?? [] ), 'is_string' ) );
		sort( $terms, SORT_NUMERIC );
		sort( $post_types, SORT_STRING );

		return [
			'terms'      => $terms,
			'post_types' => $post_types,
		];
	}

	/**
	 * Render the settings form below the notices printed by WordPress.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$random_redirects_available = method_exists( WP_Rewrite::class, 'get_random_base' );
		?>
		<div class="wrap drc-settings">
			<h1 class="drc-settings-header"><span class="drc-settings-icon" aria-hidden="true"></span><?php esc_html_e( 'Disable Random Content', 'disable-random-content' ); ?></h1>
			<div class="notice notice-info inline">
				<p>
					<?php if ( $random_redirects_available ) : ?>
						<strong><?php esc_html_e( 'All settings are optional.', 'disable-random-content' ); ?></strong>
						<?php esc_html_e( 'On activation, this plugin disables all built-in WordPress random content redirects. Use the settings below to allow redirects for individual archives.', 'disable-random-content' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Archive settings become available in WordPress 7.2. You can keep this plugin active before upgrading.', 'disable-random-content' ); ?>
					<?php endif; ?>
				</p>
			</div>
			<?php if ( $random_redirects_available ) : ?>
				<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
					<?php settings_fields( self::PAGE ); ?>
					<?php do_settings_sections( self::PAGE ); ?>
					<?php submit_button(); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a multiple-selection field, with groups for different taxonomies.
	 *
	 * @param array $field Settings field arguments.
	 */
	public function render_field( array $field ): void {
		$key      = $field['key'];
		$selected = self::get()[ $key ];
		$groups   = 'terms' === $key
			? WPML::with_original_terms( fn() => $this->term_choices() )
			: array_filter( [ '' => wp_list_pluck( self::post_types(), 'label' ) ] );
		$id       = $field['label_for'];

		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . '][]' ); ?>"
				aria-describedby="<?php echo esc_attr( $id . '-description' ); ?>" class="regular-text" multiple size="6" <?php disabled( ! $groups ); ?>>
			<?php foreach ( $groups as $label => $choices ) : ?>
				<?php if ( '' !== $label ) : ?>
					<optgroup label="<?php echo esc_attr( $label ); ?>">
				<?php endif; ?>
				<?php foreach ( $choices as $value => $name ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( in_array( $value, $selected, true ) ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
				<?php if ( '' !== $label ) : ?>
					</optgroup>
				<?php endif; ?>
			<?php endforeach; ?>
		</select>
		<p id="<?php echo esc_attr( $id . '-description' ); ?>" class="description">
			<?php echo esc_html( $groups ? $field['description'] : __( 'No eligible archives are available.', 'disable-random-content' ) ); ?>
		</p>
		<?php
	}

	/**
	 * List available terms under labels that include each taxonomy key.
	 *
	 * @return array<string, array<int, string>> Grouped term labels.
	 */
	private function term_choices(): array {
		$groups = [];

		foreach ( get_taxonomies( [ 'publicly_queryable' => true ], 'objects' ) as $taxonomy ) {
			if ( ! self::supports_taxonomy( $taxonomy ) ) {
				continue;
			}

			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy->name,
					'hide_empty' => false,
				]
			);
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}

			$group_label = sprintf( '%s (%s)', $taxonomy->label, $taxonomy->name );

			foreach ( $terms as $term ) {
				$label = get_term_parents_list(
					$term->term_id,
					$term->taxonomy,
					[
						'separator' => ' / ',
						'link'      => false,
					]
				);

				$groups[ $group_label ][ $term->term_id ] = is_string( $label ) ? substr( $label, 0, -3 ) : $term->name;
			}
		}

		return $groups;
	}
}
