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

		public function _shortcode( $atts = array() ) {

			$atts = shortcode_atts( array(
				'active_filters'    => 'yes',
				'mobile_breakpoint' => 700,
				'mobile_label'      => 'Filters',
			), $atts );

			wp_enqueue_script( 'jet-themes' );

			wp_localize_script( 'jet-themes', 'jetThemesSettings', array(
				'slug'               => jet_themes_post_type()->slug(),
				'perPage'            => 6,
				'filtersRoute'       => jet_themes_filters_api()->get_filters_route(),
				'activeFiltersTitle' => 'Active Filters',
				'mobileBreakpoint'   => $atts['mobile_breakpoint'],
				'mobileLabel'        => $atts['mobile_label'],
			) );

			$result = array(
				'all_filters'    => '<div class="filters-wrap"></div>',
				'themes'         => '<div class="themes-wrap">Loading...</div>',
				'more'           => '<div class="more-wrap"></div>',
			);

			if ( 'yes' === $atts['active_filters'] ) {
				$result = array_merge( array( 'active_filters' => '<div class="active-filters"></div>' ), $result );
			}

			return implode( '', $result );

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
