<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Themes_Manager' ) ) {

	/**
	 * Define Jet_Themes_Manager class
	 */
	class Jet_Themes_Manager {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		private $images_cdn = 'https://s.tmimgcdn.com/scr/';
		private $screen_id  = 334;
		private $type       = null;

		/**
		 * Process themes request.
		 *
		 * @param  array  $args Request arguments.
		 * @return array
		 */
		public function request( $args = array(), $type = false ) {

			$author = jet_themes_settings()->get( 'jet-author-id' );
			$type   = ( false !== $type ) ? $type : jet_themes_settings()->get( 'jet-type' );
			$api    = jet_themes_api( array( 'author' => $author, 'type' => $type ) );

			$this->type = $type;

			return $api->request_themes( $args );
		}

		/**
		 * Insert themes requested from an API.
		 *
		 * @param  array  $args Request arguments.
		 * @return int
		 */
		public function insert_themes( $args = array(), $type = false ) {

			$themes  = $this->request( $args, $type );
			$counter = 0;

			if ( ! $themes ) {
				return $counter;
			}

			foreach ( $themes as $theme ) {
				$id = $this->insert_theme( $theme );
				if ( $id ) {
					$counter++;
				}
			}

			return $counter;
		}

		/**
		 * Insert theme by passed data.
		 *
		 * @param  array  $data Theem data array.
		 * @return array
		 */
		public function insert_theme( $data = array() ) {

			if ( $this->is_theme_exists( $data['templateId'] ) ) {
				return false;
			}

			$post_data = array(
				'post_type'   => jet_themes_post_type()->slug(),
				'post_name'   => isset( $data['templateId'] ) ? $data['templateId'] : false,
				'post_date'   => date( 'Y-m-d H:i:s', strtotime( $data['inserted_date'] ) ),
				'post_status' => 'publish',
				'post_title'  => isset( $data['templateFullTitle'] ) ? $data['templateFullTitle'] : false,
			);

			$properties_data = $this->walk_properties( $data['properties'] );
			$terms_data      = $this->walk_terms( $data );

			$ld_format = jet_themes_settings()->get( 'jet-ld-format' );

			if ( $ld_format ) {
				$ld_link = sprintf( $ld_format, $data['templateId'] );
			} else {
				$ld_link = isset( $properties_data['live_demo'] ) ? $properties_data['live_demo'][0] : false;
			}

			$post_data['meta_input'] = array(
				'jet_theme_type' => ( $this->type ) ? $this->type : jet_themes_settings()->get( 'jet-type' ),
				'jet_live_demo'  => $ld_link,
				'jet_theme_page' => sprintf(
					'https://www.templatemonster.com/%s/%s.html',
					$data['templateType']['typeUrl'],
					$data['templateId']
				),
			);

			$thumb_id = $this->import_thumb( $data );

			if ( $thumb_id ) {
				$post_data['meta_input']['_thumbnail_id'] = $thumb_id;
			}

			$post_data['tax_input'] = $this->prepare_terms_input( array_merge( $properties_data, $terms_data ) );

			update_option( $data['templateId'], $post_data );

			$post_id = wp_insert_post( $post_data );

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				return false;
			}

			return $post_id;

		}

		/**
		 * Check if theme already exists
		 *
		 * @param  [type]  $theme_id [description]
		 * @return boolean           [description]
		 */
		public function is_theme_exists( $theme_id ) {

			global $wpdb;

			$id        = absint( $theme_id );
			$post_type = jet_themes_post_type()->slug();
			$query     = "
				SELECT post_name
				FROM {$wpdb->posts}
				WHERE post_name LIKE '%{$id}%' AND post_type = '{$post_type}'
			";

			$exists = $wpdb->get_var( $query );

			if ( $exists ) {
				return true;
			} else {
				return false;
			}

		}

		/**
		 * Repare terms input
		 *
		 * @param  [type] $terms_data [description]
		 * @return [type]             [description]
		 */
		public function prepare_terms_input( $terms_data ) {

			$taxes  = get_object_taxonomies( jet_themes_post_type()->slug(), ARRAY_A );
			$taxes  = array_keys( $taxes );
			$result = array();

			foreach ( $taxes as $tax ) {
				if ( isset( $terms_data[ $tax ] ) ) {
					$result[ $tax ] = implode( ', ', $terms_data[ $tax ] );
				}
			}

			return $result;

		}

		/**
		 * Prepare required terms
		 *
		 * @param  [type] $data [description]
		 * @return [type]       [description]
		 */
		public function walk_terms( $data ) {

			$result = array();

			foreach ( jet_themes_post_type()->terms_alias() as $key => $tax ) {

				if ( ! isset( $data[ $key ] ) ) {
					continue;
				}

				if ( is_array( $data[ $key ] ) ) {
					$result[ $tax ] = array( $data[ $key ]['categoryName'] );
				} else {
					$result[ $tax ] = array( $data[ $key ] );
				}

			}

			return $result;
		}

		/**
		 * Properties walker.
		 *
		 * @param  [type] $properties [description]
		 * @return [type]             [description]
		 */
		public function walk_properties( $properties ) {

			$map = array(
				'livepreviewurl' => 'live_demo',
			);
			$result = array();

			$map = array_merge( $map, jet_themes_post_type()->property_alias() );

			foreach ( $properties as $property ) {

				if( ! isset( $map[ $property['propertyUrlName'] ] ) ) {
					continue;
				}

				$key   = $map[ $property['propertyUrlName'] ];
				$value = $property['value'];

				if ( empty( $result[ $key ] ) ) {
					$result[ $key ] = array();
				}

				$result[ $key ][] = $value;

			}

			return $result;
		}

		/**
		 * Try to import thumbnail URL
		 * @param  [type] $data [description]
		 * @return [type]       [description]
		 */
		public function import_thumb( $data ) {

			$url = $this->get_thumb( $data );

			if ( ! $url ) {
				return false;
			}

			ini_set( 'max_execution_time', 300 );
			set_time_limit( 0 );

			$upload = $this->fetch_remote_file( $url );

			if ( is_wp_error( $upload ) ) {
				return $upload;
			}

			$info = wp_check_filetype( $upload['file'] );

			if ( ! $info ) {
				return new WP_Error( 'attachment_processing_error', 'Invalid file type' );
			}

			$post = array(
				'post_mime_type' => $info['type'],
				'guid'           => $upload['url'],
				'post_title'     => $data['templateId'] . '-thumbnail',
			);

			$post_id = wp_insert_attachment( $post, $upload['file'] );

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			ini_set( 'max_execution_time', 300 );
			set_time_limit( 0 );

			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, get_admin_url() ) . 'includes/image.php';
			}

			$data = wp_generate_attachment_metadata( $post_id, $upload['file'] );
			wp_update_attachment_metadata( $post_id, $data );

			return $post_id;

		}

		/**
		 * Attempt to download a remote file attachment
		 *
		 * @param  string $url  URL of item to fetch.
		 * @return array|WP_Error Local file location details on success, WP_Error otherwise.
		 */
		protected function fetch_remote_file( $url ) {

			// extract the file name and extension from the url
			$file_name = basename( $url );

			// get placeholder file in the upload dir with a unique, sanitized filename
			$upload = wp_upload_bits( $file_name, 0, '' );
			if ( $upload['error'] ) {
				return new WP_Error( 'upload_dir_error', $upload['error'] );
			}

			// fetch the remote url and write it to the placeholder file
			$response = wp_remote_get( $url, array(
				'timeout'  => 30,
				'stream'   => true,
				'filename' => $upload['file']
			) );

			// request failed
			if ( is_wp_error( $response ) ) {
				@unlink( $upload['file'] );
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );

			// make sure the fetch was successful
			if ( $code !== 200 ) {
				@unlink( $upload['file'] );
				return new WP_Error(
					'import_file_error',
					sprintf(
						'Remote server returned %1$d %2$s for %3$s',
						$code,
						get_status_header_desc( $code ),
						$url
					)
				);
			}

			$filesize = filesize( $upload['file'] );

			if ( 0 == $filesize ) {
				@unlink( $upload['file'] );
				return new WP_Error( 'import_file_error', 'Zero size file downloaded' );
			}

			return $upload;

		}

		/**
		 * Get template thumbnail URL
		 *
		 * @param  array  $data
		 * @return [type]       [description]
		 */
		public function get_thumb( $data = array() ) {

			if ( empty( $data['screenshots'] ) ) {
				return false;
			}

			$thumb_path = $this->get_thumbnail_dir( $data['templateId'] );

			foreach ( $data['screenshots'] as $screen ) {
				if ( $screen['scr_type_id'] === $this->screen_id ) {
					return trailingslashit( $thumb_path ) . $screen['filename'];
				}
			}

		}

		/**
		 * Returns url to dir with thumbnail on TM cdn
		 * @param  [type] $template_id [description]
		 * @return [type]              [description]
		 */
		public function get_thumbnail_dir( $template_id = null ) {
			$dir = floor( $template_id / 100 ) * 100;
			return trailingslashit( $this->images_cdn ) . $dir;
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
 * Returns instance of Jet_Themes_Manager
 *
 * @return object
 */
function jet_themes_manager() {
	return Jet_Themes_Manager::get_instance();
}
