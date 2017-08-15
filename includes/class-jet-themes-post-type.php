<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Themes_Post_Type' ) ) {

	/**
	 * Define Jet_Themes_Post_Type class
	 */
	class Jet_Themes_Post_Type {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		private $terms_alias    = array();
		private $property_alias = array();

		/**
		 * Constructor for the class
		 */
		public function init() {

			$this->register();
			$this->init_meta();

		//	add_action( 'wp', array( $this, 'single_page_redirect' ) );

		}

		/**
		 * Redirect single theme page on purchase page
		 *
		 * @return void
		 */
		public function single_page_redirect() {

			var_dump( get_the_id() );

		}

		/**
		 * Register Post Type
		 *
		 * @return void
		 */
		public function register() {

			$labels = array(
				'name'               => esc_html__( 'Themes', 'your-plugin-textdomain' ),
				'singular_name'      => esc_html__( 'Theme', 'your-plugin-textdomain' ),
				'menu_name'          => esc_html__( 'Themes', 'your-plugin-textdomain' ),
				'name_admin_bar'     => esc_html__( 'Theme', 'your-plugin-textdomain' ),
				'add_new'            => esc_html__( 'Add New', 'your-plugin-textdomain' ),
				'add_new_item'       => esc_html__( 'Add New Theme', 'your-plugin-textdomain' ),
				'new_item'           => esc_html__( 'New Theme', 'your-plugin-textdomain' ),
				'edit_item'          => esc_html__( 'Edit Theme', 'your-plugin-textdomain' ),
				'view_item'          => esc_html__( 'View Theme', 'your-plugin-textdomain' ),
				'all_items'          => esc_html__( 'All Themes', 'your-plugin-textdomain' ),
				'search_items'       => esc_html__( 'Search Themes', 'your-plugin-textdomain' ),
				'parent_item_colon'  => esc_html__( 'Parent Themes:', 'your-plugin-textdomain' ),
				'not_found'          => esc_html__( 'No Themes found.', 'your-plugin-textdomain' ),
				'not_found_in_trash' => esc_html__( 'No Themes found in Trash.', 'your-plugin-textdomain' )
			);

			$args = array(
				'labels'                => $labels,
				'public'                => true,
				'publicly_queryable'    => true,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'query_var'             => true,
				'rewrite'               => array( 'slug' => $this->slug() ),
				'capability_type'       => 'post',
				'has_archive'           => true,
				'hierarchical'          => false,
				'show_in_rest'          => true,
				'rest_base'             => $this->slug(),
				'rest_controller_class' => 'WP_REST_Posts_Controller',
				'menu_icon'             => 'dashicons-list-view',
				'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' )
			);

			register_post_type( $this->slug(), $args );

			$this->register_taxonomies();

		}

		/**
		 * Returns property alias
		 *
		 * @return void
		 */
		public function terms_alias() {
			return $this->terms_alias;
		}

		/**
		 * Returns property alias
		 *
		 * @return void
		 */
		public function property_alias() {
			return $this->property_alias;
		}

		/**
		 * Post type slug
		 *
		 * @return string
		 */
		public function slug() {
			return 'theme';
		}

		/**
		 * Register related taxonomies
		 *
		 * @return void
		 */
		public function register_taxonomies() {

			$taxes           = $this->required_taxonomies();
			$prop_taxonomies = $this->get_prop_taxonomies();

			foreach ( $taxes as $tax => $data ) {
				$this->terms_alias[ $data['key'] ] = $tax;
				register_taxonomy( $tax, $this->slug(), array(
					'label'        => $data['name'],
					'show_in_rest' => true,
				) );
			}

			foreach ( $prop_taxonomies as $tax => $data ) {
				$this->property_alias[ $data['property'] ] = $tax;
				register_taxonomy( $tax, $this->slug(), array(
					'label'        => $data['name'],
					'show_in_rest' => true,
				) );
			}

		}

		/**
		 * Returns prop taxonomies list
		 *
		 * @return
		 */
		public function get_prop_taxonomies() {

			$taxes   = jet_themes_settings()->get( 'jet-property-taxonomies' );
			$default = $this->default_prop_taxonomies();

			if ( ! $taxes ) {
				return $default;
			}

			$result = array();

			foreach ( $taxes as $tax ) {

				if ( empty( $tax['slug'] ) || empty( $tax['name'] ) || empty( $tax['property'] ) ) {
					continue;
				}

				$result[ $tax['slug'] ] = array(
					'name'     => $tax['name'],
					'property' => $tax['property'],
					'enabled'  => isset( $tax['enabled'] ) ? $tax['enabled'] : 'yes',
				);
			}

			return array_merge( $default, $result );
		}

		/**
		 * Return required taxonomies list
		 *
		 * @return [type] [description]
		 */
		public function required_taxonomies() {

			return apply_filters( 'jet_themes/post_type/required_taxonomies', array(
				'template-category' => array(
					'name' => esc_html__( 'Catergory', 'jet-elements' ),
					'key'  => 'templateCategory',
				),
			) );

		}

		/**
		 * Properties taxonomies
		 *
		 * @return string
		 */
		public function default_prop_taxonomies() {
			return apply_filters( 'jet_themes/post_type/topic_taxonomies', array(
				'topic' => array(
					'name'     => esc_html__( 'Topic', 'jet-elements' ),
					'property' => 'topic',
					'enabled'  => 'yes',
				),
				'features' => array(
					'name'     => esc_html__( 'Features', 'jet-elements' ),
					'property' => 'features',
					'enabled'  => 'yes',
				),
				'engine' => array(
					'name'     => esc_html__( 'Engine', 'jet-elements' ),
					'property' => 'wordpress-engine',
					'enabled'  => 'yes',
				),
				'styles' => array(
					'name'     => esc_html__( 'Style', 'jet-elements' ),
					'property' => 'styles',
					'enabled'  => 'yes',
				),
			) );
		}

		/**
		 * Initialize themes meta data
		 *
		 * @return [type] [description]
		 */
		public function init_meta() {

			jet_themes()->get_core()->init_module( 'cherry-post-meta', array(
				'id'            => 'themes-data',
				'title'         => esc_html__( 'Themes Data', 'jet-themes' ),
				'page'          => array( $this->slug() ),
				'context'       => 'normal',
				'priority'      => 'low',
				'callback_args' => false,
				'fields'        => array(
					'jet_live_demo' => array(
						'type'  => 'text',
						'title' => esc_html__( 'Live Demo URL', 'jet-themes' ),
					),
					'jet_theme_page' => array(
						'type'  => 'text',
						'title' => esc_html__( 'Theme Page URL', 'jet-themes' ),
					),
				),
			) );

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
 * Returns instance of Jet_Themes_Post_Type
 *
 * @return object
 */
function jet_themes_post_type() {
	return Jet_Themes_Post_Type::get_instance();
}
