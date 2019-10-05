<?php
/**
 * To register all wp cli commands.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Inc;

use WP_Facebook_Posts\Inc\Traits\Singleton;

/**
 * Class WP_CLI
 */
class WP_CLI {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {

		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		\WP_CLI::add_command( 'wp-facebook-posts-import', '\WP_Facebook_Posts\Inc\WP_CLI\Facebook_Posts_Import' );

	}

}
