<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Themes_Settings' ) ) {

	/**
	 * Define Jet_Themes_Settings class
	 */
	class Jet_Themes_Settings {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		protected $saved = null;

		/**
		 * Initialize class
		 *
		 * @return void
		 */
		public function init() {
			add_filter( 'wapu_core/general_setting', array( $this, 'register' ) );
			add_action( 'wp_loaded', array( $this, 'test_request' ), 999 );
		}

		/**
		 * Register settings callback
		 *
		 * @return array
		 */
		public function register( $settings = array() ) {

			$settings['tabs']['jet-themes'] = esc_html__( 'Themes Manager', 'jet-themes' );

			$settings['controls'] = array_merge( $settings['controls'], array(
				'jet-author-id' => array(
					'type'   => 'text',
					'id'     => 'jet-author-id',
					'name'   => 'jet-author-id',
					'value'  => 1519369,
					'label'  => esc_html__( 'Author ID', 'jet-themes' ),
					'parent' => 'jet-themes',
				),
				'jet-type' => array(
					'type'   => 'text',
					'id'     => 'jet-type',
					'name'   => 'jet-type',
					'value'  => 'wordpress',
					'label'  => esc_html__( 'Themes Type', 'jet-themes' ),
					'parent' => 'jet-themes',
				),
				'jet-property-taxonomies' => array(
					'parent'      => 'jet-themes',
					'type'        => 'repeater',
					'label'       => esc_html__( 'Filter Allowed Taxonomies', 'jet-themes' ),
					'add_label'   => esc_html__( 'Add New Tax', 'jet-themes' ),
					'title_field' => 'label',
					'fields'      => array(
						'slug' => array(
							'type'        => 'text',
							'id'          => 'slug',
							'name'        => 'slug',
							'placeholder' => esc_html__( 'Slug (only lowercase letters and "-" allowed)', 'jet-themes' ),
							'label'       => esc_html__( 'Slug', 'jet-themes' ),
						),
						'name' => array(
							'type'        => 'text',
							'id'          => 'name',
							'name'        => 'name',
							'placeholder' => esc_html__( 'Taxonomy Name', 'jet-themes' ),
							'label'       => esc_html__( 'Name', 'jet-themes' ),
						),
						'property' => array(
							'type'        => 'text',
							'id'          => 'property',
							'name'        => 'property',
							'placeholder' => esc_html__( 'Property name', 'jet-themes' ),
							'label'       => esc_html__( 'Related Property Name', 'jet-themes' ),
						),
						'enabled' => array(
							'type'    => 'select',
							'id'      => 'enabled',
							'name'    => 'enabled',
							'label'   => esc_html__( 'Filter enabled', 'jet-themes' ),
							'options' => array(
								'yes' => esc_html__( 'Yes', 'jet-themes' ),
								'no'  => esc_html__( 'No', 'jet-themes' ),
							),
						),
					),
					'jet-test-request' => array(
						'type'   => 'html',
						'id'     => 'jet-type',
						'name'   => 'jet-type',
						'html'   => '<a href="' . add_query_arg( array( 'jet-test-request' => true ), admin_url( '/' ) ) . '" target="_blank">Send</a>',
						'label'  => esc_html__( 'Test Request', 'jet-themes' ),
						'parent' => 'jet-themes',
					),
				),
			) );

			return $settings;

		}

		/**
		 * Send test request
		 *
		 * @return [type] [description]
		 */
		public function test_request() {

			if ( ! isset( $_GET['jet-test-request'] ) ) {
				return;
			}

			$data = array(
				'sort'     => '-inserted_date',
				'state'    => 1,
				'per-page' => 1,
				'page'     => 1,
			);

			$results = jet_themes_manager()->request( $data );

			var_dump( $results );
			die();
		}

		/**
		 * Get defaults
		 *
		 * @param  string  $name    [description]
		 * @param  boolean $default [description]
		 * @return [type]           [description]
		 */
		public function get( $name = null, $default = false ) {

			if ( null === $this->saved ) {
				$this->saved = get_option( 'wapu_core', array() );
			}

			return isset( $this->saved[ $name ] ) ? $this->saved[ $name ] : $default;
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
 * Returns instance of Jet_Themes_Settings
 *
 * @return object
 */
function jet_themes_settings() {
	return Jet_Themes_Settings::get_instance();
}
