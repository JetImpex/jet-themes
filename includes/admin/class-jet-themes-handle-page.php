<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Themes_Handle_Page' ) ) {

	/**
	 * Define Jet_Themes_Handle_Page class
	 */
	class Jet_Themes_Handle_Page {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Initialize page
		 *
		 * @return [type] [description]
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'register_handler_page' ) );
		}

		/**
		 * Register plugin options page
		 *
		 * @return void
		 */
		public function register_handler_page() {

			add_submenu_page(
				'edit.php?post_type=' . jet_themes_post_type()->slug(),
				esc_html__( 'Themes Handler', 'jet-themes' ),
				esc_html__( 'Themes Handler', 'jet-themes' ),
				'edit_theme_options',
				'jet-themes-handler',
				array( $this, 'render_page' ),
				'',
				64
			);

		}

		/**
		 * Render page callback
		 * @return [type] [description]
		 */
		public function render_page() {

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
 * Returns instance of Jet_Themes_Handle_Page
 *
 * @return object
 */
function jet_themes_handle_page() {
	return Jet_Themes_Handle_Page::get_instance();
}
