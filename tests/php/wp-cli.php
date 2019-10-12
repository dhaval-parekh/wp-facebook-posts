<?php
/**
 * Helper classes for WP-CLI
 */

if ( ! defined( 'WP_CLI' ) ) {
	define( 'WP_CLI', true );
}

class WP_CLI {

	public static function add_command( $command, $class ) {
		global $pmc_test_wp_cli_commands;

		$pmc_test_wp_cli_commands[ $command ] = $class;
	}

	public static function line( $message ) {
		echo "{$message}\n";
	}

	public static function error( $message ) {
		echo "Error: {$message}\n";
	}

	public static function success( $message ) {
		echo "Success: {$message}\n";
	}

	public static function warning( $message ) {
		echo "Warning: {$message}\n";
	}

}

class WP_CLI_Command {
}
