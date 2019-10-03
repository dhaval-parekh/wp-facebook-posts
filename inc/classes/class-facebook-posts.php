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
	 * @return int Post ID.
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
		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s;",
			'facebook_post_id',
			sanitize_text_field( $facebook_post_id )
		);

		$row = $wpdb->get_row( $query, ARRAY_A );

		$post_id = ( ! empty( $row['post_id'] ) && 0 < intval( $row['post_id'] ) ) ? intval( $row['post_id'] ) : 0;

		return $post_id;
	}

	/**
	 * Prepare facebook response data to import/update into WordPress.
	 *
	 * @param array $data Facebook response data.
	 *
	 * @return array Prepared data.
	 */
	protected static function prepare_post_data( $data ) {

		if ( empty( $data ) || ! is_array( $data ) ) {
			return [];
		}

		// If node don't have "id", Than don't import.
		if ( empty( $data['id'] ) ) {
			return [];
		}

		// Find and get if we have existing post for this.
		$post_data = [
			'post_title'   => ( ! empty( $data['name'] ) ) ? sanitize_text_field( $data['name'] ) : '',
			'post_content' => ( ! empty( $data['message'] ) ) ? wp_kses_post( $data['message'] ) : '',
			'post_status'  => 'draft',
		];

		// Published date.
		$create_timestamp = strtotime( $data['created_time'] );

		$post_data['post_date']     = get_date_from_gmt( $data['created_time'] );
		$post_data['post_date_gmt'] = date( 'Y-m-d H:i:s', strtotime( $data['created_time'] ) );

		// Last modified date.
		$post_data['post_modified']     = get_date_from_gmt( $data['updated_time'] );
		$post_data['post_modified_gmt'] = date( 'Y-m-d H:i:s', strtotime( $data['updated_time'] ) );

		// Post status.
		$post_data['post_status'] = ( current_time( 'timestamp', true ) > $create_timestamp ) ? 'publish' : 'future';

		// Check if same post is already imported.
		$existing_post_id = static::get_post_id_by_facebook_post_id( $data['id'] );

		if ( ! empty( $existing_post_id ) ) {
			$post_data['ID'] = $existing_post_id;
		}

		return $post_data;
	}

	/**
	 * Helper function to update/insert Facebook post to WordPress.
	 *
	 * @param array $data Facebook post data.
	 *
	 * @return array Response.
	 */
	public static function import_single_post( $data ) {

		$post_data = static::prepare_post_data( $data );

		if ( ! empty( $post_data['ID'] ) ) {
			$post_id = wp_update_post( $post_data );
		} else {
			$post_id = wp_insert_post( $post_data );
		}

		if ( empty( $post_id ) || is_wp_error( $post_id ) ) {
			return [
				'post_id'    => 0,
				'is_updated' => false,
			];
		}

		$post_meta_datas = [
			'facebook_post_id'      => sanitize_text_field( $data['id'] ),
			'_facebook_import_data' => wp_json_encode( $data ),
		];

		foreach ( $post_meta_datas as $meta_key => $meta_value ) {
			update_post_meta( $post_id, $meta_key, $meta_value );
		}

		return [
			'post_id'    => $post_id,
			'is_updated' => ( $post_data['ID'] ) ? true : false,
		];

	}

}