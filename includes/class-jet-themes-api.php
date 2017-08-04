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

if ( ! class_exists( 'Jet_Themes_API' ) ) {

	/**
	 * Define Jet_Themes_API class
	 */
	class Jet_Themes_API {

		protected $auth = array();
		protected $api  = 'http://api.templatemonster.com/products/v1/products/en';

		/**
		 * Constructor for the class
		 */
		public function __construct( $auth = array() ) {
			$this->auth = array_merge( array( 'author' => false, 'type' => false ), $auth );
		}

		/**
		 * Process single reuest to API
		 *
		 * @param  array  $params [description]
		 * @return [type]         [description]
		 */
		public function request_themes( $params = array() ) {

			if ( ! $this->auth['author'] || ! $this->auth['type'] ) {
				return array();
			}

			if ( isset( $params['properties'] ) ) {
				$params['properties'] = array_merge(
					$params['properties'], array( 'author_user_id' => $this->auth['author'] )
				);
			} else {
				$params['properties']['author_user_id'] = $this->auth['author'];
			}

			$params['type'] = $this->auth['type'];

			$url      = add_query_arg( $params, $this->api );
			$response = wp_remote_get( $url, array(
				'timeout' => 60,
			) );

			$body    = wp_remote_retrieve_body( $response );
			$decoded = json_decode( $body, true );

			if ( ! $decoded ) {
				return array();
			} else {
				return $decoded;
			}

		}
	}

}

/**
 * Returns instance of Jet_Themes_API
 *
 * @return object
 */
function jet_themes_api( $auth = array() ) {
	return new Jet_Themes_API( $auth );
}
