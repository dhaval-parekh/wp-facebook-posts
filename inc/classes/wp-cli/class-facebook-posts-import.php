<?php
/**
 * WP CLI command for import posts from facebook.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Inc\WP_CLI;

use WP_Facebook_Posts\Inc\Facebook_Posts;

/**
 * To import facebook posts from various source.
 *
 * @package WP_Facebook_Posts\Inc\WP_CLI
 */
class Facebook_Posts_Import extends Base {

	/**
	 * To import facebook posts to WordPress from JSON file.
	 *
	 * ## OPTIONS
	 *
	 * [--import-file]
	 * : JSON file to import posts.
	 *
	 * [--dry-run]
	 * : Whether or not to do dry run.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 *
	 * [--logs]
	 * : Whether or not to show logs.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 *
	 * [--log-file=<file>]
	 * : Path to the log file.
	 *
	 * ## EXAMPLE
	 *      wp wp-facebook-posts-import import_from_json_file --file=facebook-posts.json
	 *
	 * @param array $args       Store all the positional arguments.
	 * @param array $assoc_args Store all the associative arguments.
	 *
	 * @throws \WP_CLI\ExitException
	 *
	 * @return void
	 */
	public function import_from_json_file( $args = [], $assoc_args = [] ) {

		$this->_extract_args( $assoc_args );

		$json_file = ( ! empty( [ $assoc_args['import-file'] ] ) ) ? $assoc_args['import-file'] : '';

		if ( empty( $json_file ) ) {
			$this->error( 'Please provide JSON file.' );
		} elseif ( ! file_exists( $json_file ) ) {
			$this->error( 'File does not exists in given path.' );
		} elseif ( 0 !== validate_file( $json_file ) ) {
			$this->error( 'Invalid file provided.' );
		}

		$json_content = file_get_contents( $json_file );
		$json_content = json_decode( $json_content, true );

		if ( empty( $json_content['data'] ) || ! is_array( $json_content['data'] ) ) {
			$this->error( 'No post found in JSON file.' );
		}

		$posts = $json_content['data'];

		$counter = [
			'created' => 0,
			'updated' => 0,
			'failed'  => 0,
		];

		$post_processed = 0;

		foreach ( $posts as $post_data ) {

			$facebook_post_id = ( ! empty( $post_data['id'] ) ) ? $post_data['id'] : '';

			if ( $this->dry_run ) {

				$existing_post_id = Facebook_Posts::get_post_by_facebook_post_id( $facebook_post_id );

				if ( ! empty( $existing_post_id ) ) {
					$this->write_log( sprintf( 'Facebook post "%s" will update "%s".', $facebook_post_id, $existing_post_id ) );
					$counter['updated']++;
				} else {
					$this->write_log( sprintf( 'Facebook post "%s" will create new post.', $facebook_post_id ) );
					$counter['created']++;
				}

			} else {
				$response = Facebook_Posts::import_single_post( $post_data );

				$post_id    = ( ! empty( $response['post_id'] ) ) ? $response['post_id'] : 0;
				$is_updated = ( ! empty( $response['is_updated'] ) ) ? $response['is_updated'] : false;

				if ( empty( $post_id ) ) {
					$this->warning( sprintf( 'Facebook post "%s", Failed to import.', $facebook_post_id ) );
					$counter['failed']++;
				} else {
					if ( $is_updated ) {
						$this->success( sprintf( 'Facebook post "%s", Updates post "%s".', $facebook_post_id, $post_id ) );
						$counter['updated']++;
					} else {
						$this->success( sprintf( 'Facebook post "%s", Created post "%s".', $facebook_post_id, $post_id ) );
						$counter['created']++;
					}
				}

				$post_processed++;

				if ( $post_processed % 10 === 0 ) {
					sleep( 1 );
				}

			}

		}

		if ( $this->dry_run ) {
			$this->success( sprintf( '"%d" posts will create.', $counter['created'] ) );
			$this->success( sprintf( '"%d" posts will update.', $counter['updated'] ) );
		} else {
			$this->success( sprintf( '"%d" posts are created.', $counter['created'] ) );
			$this->success( sprintf( '"%d" posts are updated.', $counter['updated'] ) );
			$this->warning( sprintf( '"%d" posts are failed to import.', $counter['failed'] ) );
		}

	}

}
