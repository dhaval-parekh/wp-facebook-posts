<?php
/**
 * Plugin manifest class.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Inc;

use WP_Facebook_Posts\Inc\Traits\Singleton;

/**
 * Class Plugin
 */
class Plugin {

	use Singleton;

	/**
	 * Construct method.
	 *
	 * @codeCoverageIgnore
	 */
	protected function __construct() {

		// Load all classes here.
		WP_CLI::get_instance();

	}

}
