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
		 * Instance of the class Cherry_Interface_Builder.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		public $builder = null;

		/**
		 * Page slug
		 *
		 * @var string
		 */
		public $page = 'jet-themes-handler';

		/**
		 * Main themes handler action
		 *
		 * @var string
		 */
		public $action = 'get-themes';

		public $results = null;

		/**
		 * Initialize page
		 *
		 * @return [type] [description]
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'register_handler_page' ) );
			add_action( 'admin_init', array( $this, 'process_handler' ) );

			$this->builder = jet_themes()->get_core()->init_module( 'cherry-interface-builder', array() );

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
				$this->page,
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

			$this->builder->register_section(
				array(
					'handler' => array(
						'type'   => 'section',
						'scroll' => false,
						'title'  => esc_html__( 'Manually Get Themes', 'jet-themes' ),
					),
				)
			);

			$this->builder->register_form(
				array(
					'handler_form' => array(
						'type'   => 'form',
						'parent' => 'handler',
						'action' => add_query_arg(
							array( 'page' => $this->page, 'action' => $this->action ),
							esc_url( admin_url( 'admin.php' ) )
						),
					),
				)
			);

			$sections = array(
				'handler_content' => array(
					'type'   => 'settings',
					'parent' => 'handler_form',
				),
				'handler_footer' => array(
					'type'   => 'settings',
					'parent' => 'handler_form',
				),
			);

			if ( ! empty( $this->result ) ) {
				$sections = array_merge(
					array(
						'handler_results' => array(
							'type'   => 'settings',
							'parent' => 'handler_form',
						),
					),
					$sections
				);
			}

			$this->builder->register_settings( $sections );

			if ( ! empty( $this->result ) ) {
				$this->builder->register_html(
					array(
						'results_html' => array(
							'type'   => 'html',
							'parent' => 'handler_results',
							'class'  => 'handler-results',
							'html'   => $this->get_results(),
						),
					)
				);
			}

			$this->builder->register_control(
				array(
					'per_page' => array(
						'type'        => 'text',
						'id'          => 'per_page',
						'name'        => 'per_page',
						'value'       => '10',
						'title'       => esc_html__( 'Themes Per Page', 'jet-themes' ),
						'description' => esc_html__( 'Themes number processed per request (ex. 5, 10, 20)', 'jet-themes' ),
						'parent'      => 'handler_content',
					),
					'page' => array(
						'type'        => 'text',
						'id'          => 'page',
						'name'        => 'page',
						'value'       => '1',
						'title'       => esc_html__( 'Page Number', 'jet-themes' ),
						'description' => esc_html__( 'Page number in query (ex. 1, 2, 3)', 'jet-themes' ),
						'parent'      => 'handler_content',
					),
					'sort' => array(
						'type'        => 'text',
						'id'          => 'sort',
						'name'        => 'sort',
						'value'       => '-inserted_date',
						'title'       => esc_html__( 'Sort', 'jet-themes' ),
						'description' => esc_html__( 'Defines on which fields to sort (e.g. ?sort=price,-templateId) ("-" symbol is DESC sort)', 'jet-themes' ),
						'parent'      => 'handler_content',
					),
					'state' => array(
						'type'        => 'text',
						'id'          => 'state',
						'name'        => 'state',
						'value'       => '1',
						'title'       => esc_html__( 'Template State', 'jet-themes' ),
						'description' => esc_html__( 'HIDDEN_NEW => 5, HIDDEN => 0, HIDDEN_FOREVER => 3, HIDDEN_FOR_MARKETING => 6, HIDDEN_FOR_FINAL_CHECK => 7, HIDDEN_READY => 4, HIDDEN_DESIGN_REVIEW => 9, OUT_OF_STOCK => 8, FOR_SALE => 1, SOLD => 2', 'jet-themes' ),
						'parent'      => 'handler_content',
					),
				)
			);

			$this->builder->register_component(
				array(
					'submit_button' => array(
						'type'        => 'button',
						'id'          => 'handle-themes',
						'name'        => 'handle-themes',
						'button_type' => 'submit',
						'style'       => 'primary',
						'content'     => esc_html__( 'Process', 'jet-themes' ),
						'parent'      => 'handler_footer',
					),
				)
			);

			echo '<div class="themes-handle-page">';
				$this->builder->render();
			echo '</div>';

		}

		/**
		 * Rturn visual results
		 *
		 * @return [type] [description]
		 */
		public function get_results() {
			return $this->results;
		}

		/**
		 * Process themes handler
		 *
		 * @return void
		 */
		public function process_handler() {

			if ( ! isset( $_GET['page'] ) || $this->page !== $_GET['page'] ) {
				return;
			}

			if ( ! isset( $_GET['action'] ) || $this->action !== $_GET['action'] ) {
				return;
			}

			$data = array(
				'sort'     => isset( $_POST['sort'] ) ? esc_attr( $_POST['sort'] ) : '-inserted_date',
				'state'    => isset( $_POST['state'] ) ? absint( $_POST['state'] ) : 1,
				'per-page' => isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 30,
				'page'     => isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1,
			);

			$result = jet_themes_manager()->insert_themes( $data );

			die();

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
