<?php
/**
 * Class description
 *
 * @package   package_name
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Themes_Filters_API' ) ) {

	/**
	 * Define Jet_Themes_Filters_API class
	 */
	class Jet_Themes_Filters_API {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Constructor for the class
		 */
		public function init() {
			add_action( 'rest_api_init', array( $this, 'update_api_endpoint' ) );
			add_action( 'wp_footer', array( $this, 'print_templates' ) );
		}

		/**
		 * Update API endpoint
		 *
		 * @return void
		 */
		public function update_api_endpoint() {

			$fields = array(
				'featured_media_src',
				'live_demo_url',
				'theme_url',

			);

			foreach ( $fields as $field ) {

				register_rest_field( jet_themes_post_type()->slug(),
					$field,
					array(
						'get_callback'    => array( $this, $field ),
						'update_callback' => null,
						'schema'          => null,
					)
				);

			}

		}

		/**
		 * Returns featured media url
		 * @return [type] [description]
		 */
		public function featured_media_src( $object, $field_name, $request ) {
			return wp_get_attachment_image_url( $object['featured_media'], 'full' );
		}

		/**
		 * Returns LD URL
		 * @return [type] [description]
		 */
		public function live_demo_url( $object, $field_name, $request ) {
			return get_post_meta( $object['id'], 'jet_live_demo', true );
		}

		/**
		 * Returns theme URL
		 * @return [type] [description]
		 */
		public function theme_url( $object, $field_name, $request ) {
			return get_post_meta( $object['id'], 'jet_theme_page', true );
		}

		/**
		 * Print JS templates.
		 *
		 * @return void
		 */
		public function print_templates() {

			$templates = scandir( jet_themes()->plugin_path( 'templates/tmpl' ) );

			foreach ( $templates as $template ) {

				$parts = pathinfo( $template );

				if ( 'html' !== $parts['extension'] ) {
					continue;
				}

				$name = $parts['filename'];
				$path = locate_template( $template );

				if ( ! $path ) {
					$path = jet_themes()->plugin_path( 'templates/tmpl/' . $template );
				}

				if ( ! file_exists( $path ) ) {
					continue;
				}

				echo '<script type="text/html" id="tmpl-' . $name . '">';
				include $path;
				echo '</script>';

			}


		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}
	}

}

/**
 * Returns instance of Jet_Themes_Filters_API
 *
 * @return object
 */
function jet_themes_filters_api() {
	return Jet_Themes_Filters_API::get_instance();
}
