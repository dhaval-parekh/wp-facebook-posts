<?php
/**
 * Helper function for Unit test cases.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Tests;

/**
 * Class Helpers
 */
class Helpers {

	/**
	 * To get printed content from callable functions.
	 *
	 * @param array|string $callback Callable function.
	 * @param array $arguments Arguments for function.
	 *
	 * @return string Buffer value.
	 */
	public static function get_buffer_input( $callback, $arguments = [] ) {

		if ( ! is_callable( $callback ) ) {
			return '';
		}

		ob_start();
		call_user_func( $callback, ...$arguments );
		$content = ob_get_clean();

		return $content;
	}

}
