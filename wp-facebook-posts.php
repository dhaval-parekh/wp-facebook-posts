<?php
/**
 * Plugin Name: WP Facebook Posts
 * Plugin URI:  https://github.com/dhaval-parekh/wp-facebook-posts
 * Description: WordPress Plugin to import posts from facebook page.
 * Version:     1.0
 * Author:      Dhaval Parekh
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-facebook-posts
 *
 * @package wp-facebook-posts
 */

define( 'WP_FACEBOOK_POSTS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WP_FACEBOOK_POSTS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

$vendor_autoload = sprintf( '%s/vendor/autoload.php', WP_FACEBOOK_POSTS_PATH );

// Missing vendor autoload file or invalid file path.
if ( empty( $vendor_autoload ) || ! file_exists( $vendor_autoload ) || 0 !== validate_file( $vendor_autoload ) ) {
	return;
}

// We already making sure that file is exists and valid.
require_once( sprintf( '%s/autoloader.php', WP_FACEBOOK_POSTS_PATH ) ); // phpcs:ignore

// Initialize plugin.
\WP_Facebook_Posts\Inc\Plugin::get_instance();
