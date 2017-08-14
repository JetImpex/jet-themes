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

if ( ! class_exists( 'Jet_Themes_Shortcode' ) ) {

	/**
	 * Define Jet_Themes_Shortcode class
	 */
	class Jet_Themes_Shortcode {

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
			add_shortcode( 'jet_themes', array( $this, '_shortcode' ) );
		}

		public function _shortcode() {

			wp_enqueue_script( 'jet-themes' );

			wp_localize_script( 'jet-themes', 'jetThemesSettings', array(
				'slug'    => jet_themes_post_type()->slug(),
				'perPage' => 1,
			) );

			return '<button class="theme-filter__item">Filter</button><div class="themes-wrap">Loading...</div><button class="theme-more">More</button>';

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
 * Returns instance of Jet_Themes_Shortcode
 *
 * @return object
 */
function jet_themes_shortcode() {
	return Jet_Themes_Shortcode::get_instance();
}
