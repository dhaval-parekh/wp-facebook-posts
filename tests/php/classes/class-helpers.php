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
	 * @param array|string $callback  Callable function.
	 * @param array        $arguments Arguments for function.
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

	/**
	 * To call private/protected method of the class
	 *
	 * @param object|string $object Instance/Name of the class.
	 * @param string        $method Method of class
	 * @param array         $arguments Argument for that class
	 *
	 * @throws \ReflectionException
	 *
	 * @return mixed
	 */
	public static function execute_private_method( $object, $method, $arguments = [] ) {

		$reflection = new \ReflectionMethod( $object, $method );
		$reflection->setAccessible( true );

		$return = $reflection->invoke( $object, ...$arguments );

		return $return;
	}

}
