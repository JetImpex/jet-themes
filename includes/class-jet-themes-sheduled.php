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

if ( ! class_exists( 'Jet_Themes_Sheduled' ) ) {

	/**
	 * Define Jet_Themes_Sheduled class
	 */
	class Jet_Themes_Sheduled {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		protected $sheduled_action = 'jet_get_latest_themes';

		/**
		 * Constructor for the class
		 */
		public function init() {

			add_filter( 'cron_schedules', array( $this, 'testing_interval' ) );

			$timestamp = wp_next_scheduled( $this->sheduled_action );

			if ( false === $timestamp ) {
				wp_schedule_event( time(), 'minute', $this->sheduled_action );
			}

			add_action( $this->sheduled_action, array( $this, 'get_latest_themes' ) );

		}

		public function testing_interval( $schedules ) {

			$schedules['minute'] = array(
				'interval' => 60,
				'display'  => esc_html__( 'Every Minute' ),
			);

			return $schedules;
		}

		/**
		 * Insert latest themes
		 *
		 * @return [type] [description]
		 */
		public function get_latest_themes() {

			wp_set_current_user( 1 );

			$data = array(
				'sort'     => '-inserted_date',
				'state'    => 1,
				'per-page' => 3,
				'page'     => 1,
			);

			$results = jet_themes_manager()->insert_themes( $data );

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
 * Returns instance of Jet_Themes_Sheduled
 *
 * @return object
 */
function jet_themes_sheduled() {
	return Jet_Themes_Sheduled::get_instance();
}
