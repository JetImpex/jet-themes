<?php
/**
 * Plugin Name: Jet Themes
 * Plugin URI:  http://www.cherryframework.com/plugins/
 * Description: Themes Manager. Dependencies: Wapu Core to process own options.
 * Version:     1.1.0
 * Author:      JetImpex
 * Author URI:  http://cherryframework.com/
 * Text Domain: jet-themes
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 *
 * @package Jet_Themes
 * @author  Cherry Team
 * @version 1.0.0
 * @license GPL-3.0+
 * @copyright  2002-2016, Cherry Team
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

// If class `Jet_Themes` doesn't exists yet.
if ( ! class_exists( 'Jet_Themes' ) ) {

	/**
	 * Sets up and initializes the plugin.
	 */
	class Jet_Themes {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    object
		 */
		private static $instance = null;

		/**
		 * A reference to an instance of cherry framework core class.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    object
		 */
		private $core = null;

		/**
		 * Holder for base plugin URL
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $plugin_url = null;

		/**
		 * Plugin version
		 *
		 * @var string
		 */
		private $version = '1.1.0';

		/**
		 * Core page trigger
		 *
		 * @var boolean
		 */
		private $is_core_page = false;

		/**
		 * Holder for base plugin path
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $plugin_path = null;

		/**
		 * Sets up needed actions/filters for the plugin to initialize.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function __construct() {

			// Load the installer core.
			add_action( 'after_setup_theme', require( dirname( __FILE__ ) . '/cherry-framework/setup.php' ), 0 );

			// Load the core functions/classes required by the rest of the plugin.
			add_action( 'after_setup_theme', array( $this, 'get_core' ), 1 );
			// Load the modules.
			add_action( 'after_setup_theme', array( 'Cherry_Core', 'load_all_modules' ), 2 );

			// Internationalize the text strings used.
			add_action( 'init', array( $this, 'lang' ), 0 );

			// Load the admin files.
			add_action( 'init', array( $this, 'init' ), 0 );

			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

			// Register activation and deactivation hook.
			register_activation_hook( __FILE__, array( $this, 'activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
		}

		/**
		 * Loads the core functions. These files are needed before loading anything else in the
		 * plugin because they have required functions for use.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return object
		 */
		public function get_core() {

			/**
			 * Fires before loads the plugin's core.
			 *
			 * @since 1.0.0
			 */
			do_action( 'jet_themes/core_before' );

			global $chery_core_version;

			if ( null !== $this->core ) {
				return $this->core;
			}

			if ( 0 < sizeof( $chery_core_version ) ) {
				$core_paths = array_values( $chery_core_version );
				require_once( $core_paths[0] );
			} else {
				die( 'Class Cherry_Core not found' );
			}

			$this->core = new Cherry_Core( array(
				'base_dir' => $this->plugin_path( 'cherry-framework' ),
				'base_url' => $this->plugin_url( 'cherry-framework' ),
				'modules'  => array(
					'cherry-js-core' => array(
						'autoload' => true,
					),
					'cherry-ui-elements' => array(
						'autoload' => false,
					),
					'cherry-handler' => array(
						'autoload' => false,
					),
					'cherry-interface-builder' => array(
						'autoload' => false,
					),
					'cherry-utility' => array(
						'autoload' => true,
						'args'     => array(
							'meta_key' => array(
								'term_thumb' => 'cherry_terms_thumbnails'
							),
						)
					),
					'cherry-widget-factory' => array(
						'autoload' => true,
					),
					'cherry-term-meta' => array(
						'autoload' => false,
					),
					'cherry-post-meta' => array(
						'autoload' => false,
					),
					'cherry-dynamic-css' => array(
						'autoload' => false,
					),
					'cherry5-insert-shortcode' => array(
						'autoload' => false,
					),
					'cherry5-assets-loader' => array(
						'autoload' => false,
					),
				),
			) );

			return $this->core;
		}

		/**
		 * Returns plugin version
		 *
		 * @return string
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * Manually init required modules.
		 *
		 * @return void
		 */
		public function init() {

			$this->load_files();

			$author = jet_themes_settings()->get( 'jet-author-id' );
			$type   = jet_themes_settings()->get( 'jet-type' );

			$themes = jet_themes_api( array( 'author' => $author, 'type' => $type ) )->request_themes( array(
				'sort'     => '-date',
				'state'    => 1,
				'per-page' => 10,
			) );

		}

		/**
		 * Load required files
		 *
		 * @return void
		 */
		public function load_files() {

			$files = array(
				'includes/class-jet-themes-settings.php' => array(
					'function' => 'jet_themes_settings',
					'is_admin' => false,
					'init'     => true,
				),
				'includes/class-jet-themes-post-type.php' => array(
					'function' => 'jet_themes_post_type',
					'is_admin' => false,
					'init'     => true,
				),
				'includes/class-jet-themes-api.php' => array(
					'function' => 'jet_themes_post_type',
					'is_admin' => false,
					'init'     => false,
				),
				'includes/admin/class-jet-themes-handle-page.php' => array(
					'function' => 'jet_themes_handle_page',
					'is_admin' => true,
					'init'     => true,
				),
			);

			foreach ( $files as $file => $data ) {

				if ( ! empty( $data['is_admin'] ) && ! is_admin() ) {
					continue;
				}

				require $this->plugin_path( $file );

				if ( true === $data['init'] ) {
					$function = $data['function'];
					$function()->init();
				}

			}
		}

		/**
		 * Returns path to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function plugin_path( $path = null ) {

			if ( ! $this->plugin_path ) {
				$this->plugin_path = trailingslashit( plugin_dir_path( __FILE__ ) );
			}

			return $this->plugin_path . $path;
		}
		/**
		 * Returns url to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function plugin_url( $path = null ) {

			if ( ! $this->plugin_url ) {
				$this->plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );
			}

			return $this->plugin_url . $path;
		}

		/**
		 * Loads the translation files.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function lang() {
			load_plugin_textdomain( 'jet-themes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Get the template path.
		 *
		 * @return string
		 */
		public function template_path() {
			return apply_filters( 'jet-themes/template-path', 'jet-themes/' );
		}

		/**
		 * Returns path to template file.
		 *
		 * @return string|bool
		 */
		public function get_template( $name = null ) {

			$template = locate_template( $this->template_path() . $name );

			if ( ! $template ) {
				$template = $this->plugin_path( 'templates/' . $name );
			}

			if ( file_exists( $template ) ) {
				return $template;
			} else {
				return false;
			}
		}

		/**
		 * Enqueue public-facing stylesheets.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function register_assets() {

			wp_register_script(
				'jet-themes',
				$this->plugin_url( 'assets/js/jet-themes.js' ),
				array( 'jquery' ),
				$this->get_version(),
				true
			);

			wp_register_script(
				'clipboard',
				$this->plugin_url( 'assets/js/vendor/clipboard.min.js' ),
				array( 'jquery' ),
				'1.6.1',
				true
			);

			$data = apply_filters( 'jet_themes/localize_data', array(
				'ajaxurl' => esc_url( admin_url( 'admin-ajax.php' ) ),
			) );

			wp_localize_script( 'jet-themes', 'jetThemesSettings', $data );

			wp_enqueue_style(
				'jet-themes', $this->plugin_url( 'assets/css/jet-themes.css' ), false, $this->get_version()
			);

			wp_enqueue_style(
				'nucleo-outline', $this->plugin_url( 'assets/css/nucleo-outline.css' ), false, $this->get_version()
			);

			$this->get_core()->init_module(
				'cherry5-assets-loader',
				array(
					'css' => array( 'jet-themes', 'nucleo-outline' ),
				)
			);
		}

		/**
		 * Do some stuff on plugin activation
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function activation() {
			flush_rewrite_rules();
		}

		/**
		 * Do some stuff on plugin activation
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function deactivation() {
			flush_rewrite_rules();
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @access public
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

if ( ! function_exists( 'jet_themes' ) ) {

	/**
	 * Returns instanse of the plugin class.
	 *
	 * @since  1.0.0
	 * @return object
	 */
	function jet_themes() {
		return Jet_Themes::get_instance();
	}
}

jet_themes();
