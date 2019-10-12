<?php
/**
 * Helper function for media.
 *
 * @package wp-facebook-import
 */

namespace WP_Facebook_Posts\Inc;

use WP_Facebook_Posts\Inc\Traits\Singleton;

/**
 * Class Media
 */
class Media {

	use Singleton;

	/**
	 * To store list attachment ids created.
	 * It will hash as key and attachment id as value.
	 *
	 * @var array List of created attachment ids.
	 */
	static $downloaded_media = [];

	/**
	 * To get URL hash.
	 *
	 * @param string $url URL.
	 *
	 * @return string URL Hash.
	 */
	public static function get_url_hash( $url ) {
		return ( ! empty( $url ) ) ? md5( strtolower( trim( $url ) ) ) : '';
	}

	/**
	 * To find attachment ID from URL (from which it's imported in WordPress).
	 *
	 * @param string $url URL from which it's imported.
	 *
	 * @return int Attachment ID on Success Otherwise 0.
	 */
	public static function find_attachment_by_import_url( $url ) {

		if ( empty( $url ) ) {
			return 0;
		}

		// Create hash from URL.
		$url_hash = static::get_url_hash( $url );

		// First try to find attachment id  in list.
		// If yes then return that.
		if ( ! empty( static::$downloaded_media[ $url_hash ] ) && 0 < intval( static::$downloaded_media[ $url_hash ] ) ) {
			return static::$downloaded_media[ $url_hash ];
		}

		global $wpdb;

		/**
		 * Find if in past did we created attachment from same URL.
		 *
		 * Note: Here, We are using custom query.
		 * Since, WP_Query will join two tables which is not necessary.
		 */
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s;",
				'wp_facebook_import_url_hash',
				$url_hash
			),
			ARRAY_A
		);

		$attachment_id = ( ! empty( $row['post_id'] ) && 0 < intval( $row['post_id'] ) ) ? intval( $row['post_id'] ) : 0;

		if ( ! empty( $attachment_id ) ) {
			static::$downloaded_media[ $url_hash ] = $attachment_id;
		}

		return $attachment_id;
	}

	/**
	 * If fetching attachments is enabled then attempt to create a new attachment
	 *
	 * @param string $url URL to fetch attachment from.
	 *
	 * @return int|\WP_Error Post ID on success, WP_Error otherwise.
	 */
	public static function create_attachment_by_url( $url ) {

		if ( empty( $url ) ) {
			return 0;
		}

		/**
		 * Check if in past did we created attachment from same URL.
		 * If yes then use that and no need to import same media again.
		 */
		$attachment_id = static::find_attachment_by_import_url( $url );

		if ( ! empty( $attachment_id ) && 0 < intval( $attachment_id ) ) {
			return $attachment_id;
		}

		// Create hash from URL.
		$url_hash = static::get_url_hash( $url );

		// If the URL is absolute, but does not contain address, then upload it assuming base_site_url.
		if ( preg_match( '/^\/[\w\W]+$/m', $url ) ) {
			$base_url = home_url();
			$url      = rtrim( $base_url, '/' ) . $url;
		}

		$upload = static::fetch_remote_file( $url );

		if ( is_wp_error( $upload ) || empty( $upload['file'] ) || empty( $upload['url'] ) || empty( $upload['type'] ) ) {
			return $upload;
		}

		$attachment_data = [
			'post_title'     => basename( $upload['file'] ),
			'guid'           => $upload['url'],
			'post_mime_type' => $upload['type'],
		];

		$attachment_id = wp_insert_attachment( $attachment_data, $upload['file'] );

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

		/**
		 * Save URL hash in attachment meta data. Also in create media list.
		 *
		 * So in future we can check if we have create attachment with particulier URL or NOT.
		 */
		update_post_meta( $attachment_id, 'wp_facebook_import_url_hash', $url_hash );

		static::$downloaded_media[ $url_hash ] = $attachment_id;

		return $attachment_id;
	}

	/**
	 * Attempt to download a remote file attachment
	 *
	 * @param string $url URL of item to fetch.
	 *
	 * @return array|\WP_Error Local file location details on success, WP_Error otherwise
	 */
	public static function fetch_remote_file( $url ) {

		if ( empty( $url ) ) {
			return [];
		}

		/**
		 * Assume URL may have query string attached.
		 * Strip query string so we can get proper name for attachment.
		 */
		$url_without_query_string = $url;
		$query_string             = wp_parse_url( $url_without_query_string, PHP_URL_QUERY );
		$url_without_query_string = str_replace( $query_string, '', $url_without_query_string );
		$url_without_query_string = rtrim( $url_without_query_string, '?' );

		// Extract the file name and extension from the url.
		$file_name = basename( $url_without_query_string );
		$file_name = sanitize_file_name( $file_name );

		// Fetch the remote url and write it to the placeholder file.
		$remote_response = wp_safe_remote_get(
			$url,
			[
				'timeout' => 20,
			]
		);

		$headers = wp_remote_retrieve_headers( $remote_response );

		// Request failed.
		if ( empty( $headers ) ) {
			return new \WP_Error( 'import_file_error', __( 'Remote server did not respond', 'wp-facebook-posts' ) );
		}

		$remote_response_code = wp_remote_retrieve_response_code( $remote_response );

		// Make sure the fetch was successful.
		if ( 200 !== intval( $remote_response_code ) ) {
			/**
			 * Ignoring code coverage since, It require Dummy URL redirection.
			 * To test this case.
			 */
			// @codeCoverageIgnoreStart
			return new \WP_Error(
				'import_file_error',
				/* translators: %s Response code */
				sprintf( __( 'Remote server returned error response %1$d %2$s', 'wp-facebook-posts' ), esc_html( $remote_response_code ), get_status_header_desc( $remote_response_code ) )
			);
			// @codeCoverageIgnoreEnd
		}

		$body = wp_remote_retrieve_body( $remote_response );

		$upload = wp_upload_bits( $file_name, null, $body );

		if ( ! empty( $upload['error'] ) ) {
			return new \WP_Error( 'attachment_processing_error', $upload['error'] );
		}

		return ( ! empty( $upload ) && is_array( $upload ) ) ? $upload : [];
	}

}
