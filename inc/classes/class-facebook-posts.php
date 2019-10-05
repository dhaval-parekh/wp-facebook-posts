<?php
/**
 * Class to import, publish post from/to Facebook.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Inc;

use WP_Facebook_Posts\Inc\Traits\Singleton;

/**
 * Class Facebook_Posts
 */
class Facebook_Posts {

	use Singleton;

	/**
	 * To get WordPress post id, associated with facebook post.
	 *
	 * @param string $facebook_post_id Facebook post ID.
	 *
	 * @return int Post ID on if post is exists otherwise 0.
	 */
	public static function get_post_id_by_facebook_post_id( $facebook_post_id ) {

		if ( empty( $facebook_post_id ) ) {
			return 0;
		}

		global $wpdb;

		/**
		 * Here, we are using custom query.
		 * Since, WP_Query will join two tables which is not necessary.
		 */
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s;",
				'facebook_post_id',
				sanitize_text_field( $facebook_post_id )
			),
			ARRAY_A
		);

		return ( ! empty( $row['post_id'] ) && 0 < intval( $row['post_id'] ) ) ? intval( $row['post_id'] ) : 0;
	}

	/**
	 * Prepare facebook response data to import/update into WordPress.
	 *
	 * @param array $data Facebook response data.
	 *
	 * @return array Prepared data.
	 */
	protected static function _prepare_post_data( $data ) {

		if ( empty( $data ) || ! is_array( $data ) ) {
			return [];
		}

		// If node don't have "id", Than don't import.
		if ( empty( $data['id'] ) ) {
			return [];
		}

		$create_timestamp = strtotime( $data['created_time'] );

		// Find and get if we have existing post for this.
		$post_data = [
			'post_title'        => ( ! empty( $data['name'] ) ) ? sanitize_text_field( $data['name'] ) : '',
			'post_content'      => ( ! empty( $data['message'] ) ) ? wp_kses_post( $data['message'] ) : '',

			// Post status.
			'post_status'       => ( current_time( 'timestamp', true ) > $create_timestamp ) ? 'publish' : 'future',

			// Published date.
			'post_date'         => get_date_from_gmt( $data['created_time'] ),
			'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', strtotime( $data['created_time'] ) ),

			// Last modified date.
			'post_modified'     => get_date_from_gmt( $data['updated_time'] ),
			'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( $data['updated_time'] ) ),

			// Meta data.
			'meta_input'        => [
				'facebook_post_id'      => sanitize_text_field( $data['id'] ),
				'_facebook_import_data' => wp_json_encode( $data ),
			],
		];

		// If there is not title or content. then make it draft.
		if ( empty( $post_data['post_title'] ) || empty( $post_data['post_content'] ) ) {
			$post_data['post_status'] = 'draft';
		}

		// Check if same post is already imported.
		$existing_post_id = static::get_post_id_by_facebook_post_id( $data['id'] );

		/**
		 * If we have existing post for this facebook post,
		 * Then pass update same post instead of creating new post for it.
		 */
		if ( ! empty( $existing_post_id ) ) {
			$post_data['ID'] = $existing_post_id;
		}

		return $post_data;
	}

	/**
	 * Helper function to update/insert Facebook post to WordPress.
	 *
	 * @param array $data        Facebook post data.
	 * @param array $import_args Import argument.
	 *                           attachment-import: Whether or not to import attachment or not. By default false.
	 *
	 * @return array Response.
	 */
	public static function import_single_post( $data, $import_args = [] ) {

		$default_args = [
			'attachment-import' => false,
		];

		$import_args = wp_parse_args( $import_args, $default_args );

		$post_data = static::_prepare_post_data( $data );

		$post_id = wp_insert_post( $post_data, true );

		if ( empty( $post_id ) || is_wp_error( $post_id ) ) {

			$message = '';

			if ( is_wp_error( $post_id ) ) {
				$message = $post_id->get_error_message();
			}

			return [
				'post_id'    => 0,
				'is_updated' => false,
				'message'    => $message,
			];
		}

		/**
		 * Set featured image.
		 */
		if ( true === $import_args['attachment-import'] ) {

			$attachment_url = ( ! empty( $data['full_picture'] ) ) ? $data['full_picture'] : '';

			// If "full_picture" is empty then only check for "picture".
			$attachment_url = ( empty( $attachment_url ) && ! empty( $data['picture'] ) ) ? $data['picture'] : $attachment_url;

			if ( ! empty( $attachment_url ) ) {

				$attachment_id = Media::create_attachment_by_url( $attachment_url );

				if ( ! empty( $attachment_id ) && 0 < intval( $attachment_id ) ) {
					set_post_thumbnail( $post_id, $attachment_id );
				}
			}
		}

		// @Todo: Import comments.

		return [
			'post_id'    => $post_id,
			'is_updated' => ( ! empty( $post_data['ID'] ) ) ? true : false,
		];

	}

}
