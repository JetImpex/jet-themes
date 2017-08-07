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

		/**
		 * Process themes request.
		 *
		 * @param  array  $args Request arguments.
		 * @return array
		 */
		public function request( $args = array() ) {

			$author = jet_themes_settings()->get( 'jet-author-id' );
			$type   = jet_themes_settings()->get( 'jet-type' );
			$api    = jet_themes_api( array( 'author' => $author, 'type' => $type ) );

			return $api->request_themes( $args );
		}

		/**
		 * Insert themes requested from an API.
		 *
		 * @param  array  $args Request arguments.
		 * @return int
		 */
		public function insert_themes( $args = array() ) {

			$themes  = $this->request( $args );
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

			//var_dump( $data );

			$post_data = array(
				'post_name'  => isset( $data['templateId'] ) ? $data['templateId'] : false,
				'post_title' => isset( $data['templateFullTitle'] ) ? $data['templateFullTitle'] : false,
			);

			$meta_input = array(
				'jet_live_demo'  => '',
				'jet_theme_page' => '',
			);

			//$thumb_id = $this->import_thumb( $data );
			$thumb_id = false;

			if ( $thumb_id ) {
				$meta_input['_thumbnail_id'] = $thumb_id;
			}

			var_dump( $thumb_id );

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
