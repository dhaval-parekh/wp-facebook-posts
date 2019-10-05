<?php
/**
 * WP CLI command for import posts from facebook.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Inc\WP_CLI;

use WP_Facebook_Posts\Inc\Facebook_Posts;
use function WP_CLI\Utils\get_flag_value;

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
	 * @Todo: Provide option of post-type in which data will imported.
	 *
	 * ## EXAMPLE
	 *      wp wp-facebook-posts-import import_from_json_file --import-file=facebook-posts.json --logs=true
	 *
	 * @param array $args       Store all the positional arguments.
	 * @param array $assoc_args Store all the associative arguments.
	 *
	 * @throws \WP_CLI\ExitException WP CLI Exit Exception.
	 *
	 * @return void
	 */
	public function import_from_json_file( $args = [], $assoc_args = [] ) {

		$batch_size = 100;

		$this->_extract_args( $assoc_args );

		$json_file = filter_var( get_flag_value( $assoc_args, 'import-file' ), FILTER_SANITIZE_STRING );

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

		$this->success( 'WP-CLI command "wp wp-facebook-posts-import import_from_json_file"' );

		if ( $this->dry_run ) {
			$this->success( 'Dry Run -- ' . PHP_EOL );
		} else {
			$this->success( 'Actual Run -- ' . PHP_EOL );
		}

		$posts = $json_content['data'];

		$counter = [
			'created' => 0,
			'updated' => 0,
			'failed'  => 0,
		];

		$post_processed = 0;

		// Importing start.
		$this->_import_start();

		foreach ( $posts as $post_data ) {

			$facebook_post_id = ( ! empty( $post_data['id'] ) ) ? $post_data['id'] : '';

			if ( $this->dry_run ) {

				$existing_post_id = Facebook_Posts::get_post_id_by_facebook_post_id( $facebook_post_id );

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
			}

			$post_processed++;

			/**
			 * Halt script for some time.
			 */
			if ( 0 === ( $post_processed % $batch_size ) ) {
				sleep( 1 );
				$this->_stop_the_insanity();
			}
		}

		// Importing end.
		$this->_import_end();

		if ( $this->dry_run ) {
			$this->success( sprintf( '"%d" posts will create.', $counter['created'] ) );
			$this->success( sprintf( '"%d" posts will update.', $counter['updated'] ) );
		} else {
			$this->success( sprintf( '"%d" posts are created.', $counter['created'] ) );
			$this->success( sprintf( '"%d" posts are updated.', $counter['updated'] ) );
			$this->warning( sprintf( '"%d" posts are failed to import.', $counter['failed'] ) );
		}

	}

	/**
	 * To handle all process before importing.
	 *
	 * @return void
	 */
	protected function _import_start() {

		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		wp_suspend_cache_addition( true );

		wp_suspend_cache_invalidation( true );

		wp_defer_comment_counting( true );

		wp_defer_term_counting( true );

	}

	/**
	 * To handle all process after importing.
	 *
	 * @return void
	 */
	protected function _import_end() {

		wp_suspend_cache_addition( false );

		wp_suspend_cache_invalidation( false );

		wp_defer_comment_counting( false );

		wp_defer_term_counting( false );

	}


	/**
	 * Clear all of the caches for memory management.
	 *
	 * Reference: https://github.com/Automattic/vip-go-mu-plugins/blob/master/vip-helpers/vip-wp-cli.php#L8
	 *
	 * @return void
	 */
	protected function _stop_the_insanity() {

		/**
		 * Global variables.
		 *
		 * @var \WP_Object_Cache $wp_object_cache
		 * @var \wpdb            $wpdb
		 */
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array();

		if ( is_object( $wp_object_cache ) ) {
			$wp_object_cache->group_ops      = array();
			$wp_object_cache->stats          = array();
			$wp_object_cache->memcache_debug = array();
			$wp_object_cache->cache          = array();

			if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
				// Important.
				$wp_object_cache->__remoteset();
			}
		}
	}

}
