<?php
/**
 * PHPUnit bootstrap file
 *
 * @package wp-facebook-posts
 */

$tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $tests_dir ) {
	$tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

$tests_dir = rtrim( $tests_dir, '/' );

if ( ! file_exists( $tests_dir . '/includes/functions.php' ) ) {
	printf( 'Could not find %s/includes/functions.php, have you run bin/install-wp-tests.sh ?', $tests_dir );
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( dirname( __FILE__ ) ) ) . '/wp-facebook-posts.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $tests_dir . '/includes/bootstrap.php';

require_once 'wp-cli.php';
