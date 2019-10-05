<?php
/**
 * Base class for all WP CLI commands.
 *
 * @package wp-facebook-import
 */

namespace WP_Facebook_Posts\Inc\WP_CLI;

use function WP_CLI\Utils\get_flag_value;

/**
 * Class Base
 */
class Base extends \WP_CLI_Command {

	/**
	 * Associative arguments.
	 *
	 * @var array
	 */
	protected $_assoc_args = [];

	/**
	 * Dry run command.
	 *
	 * @var bool
	 */
	public $dry_run = true;

	/**
	 * Log file.
	 *
	 * @var string Log file.
	 */
	public $log_file = '';

	/**
	 * Logs to show or hide.
	 *
	 * @var bool
	 */
	public $logs = false;

	/**
	 * Function to extract arguments.
	 *
	 * @param array $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	protected function _extract_args( $assoc_args ) {

		$assoc_args = ( ! empty( $assoc_args ) && is_array( $assoc_args ) ) ? $assoc_args : [];

		$this->_assoc_args = $assoc_args;
		$this->log_file    = filter_var( get_flag_value( $assoc_args, 'log-file' ), FILTER_SANITIZE_STRING );
		$this->logs        = filter_var( get_flag_value( $assoc_args, 'logs', true ), FILTER_VALIDATE_BOOLEAN );
		$this->dry_run     = filter_var( get_flag_value( $assoc_args, 'dry-run', true ), FILTER_VALIDATE_BOOLEAN );

	}

	/**
	 * Method to add a log entry and to output message on screen.
	 *
	 * @param string $message      Message to add to log and to output on screen.
	 * @param int    $message_type Message type - 0 for normal line, -1 for error, 1 for success, 2 for warning.
	 *
	 * @throws \WP_CLI\ExitException WP CLI Exit Exception.
	 *
	 * @return void
	 */
	protected function write_log( $message, $message_type = 0 ) {

		$message_type = intval( $message_type );

		if ( ! in_array( $message_type, [ -1, 0, 1, 2 ], true ) ) {
			$message_type = 0;
		}

		$message_prefix = '';

		// Message prefix for use in log file.
		switch ( $message_type ) {

			case -1:
				$message_prefix = 'Error: ';
				break;

			case 1:
				$message_prefix = 'Success: ';
				break;

			case 2:
				$message_prefix = 'Warning: ';
				break;

		}

		// Log message to log file if a log file.
		if ( ! empty( $this->log_file ) ) {
			file_put_contents( $this->log_file, $message_prefix . $message . "\n", FILE_APPEND );
		}

		if ( ! empty( $this->logs ) ) {

			switch ( $message_type ) {

				case -1:
					\WP_CLI::error( $message );
					break;

				case 1:
					\WP_CLI::success( $message );
					break;

				case 2:
					\WP_CLI::warning( $message );
					break;

				case 0:
				default:
					\WP_CLI::line( $message );
					break;

			}
		}

	}

	/**
	 * Method to log an error message and stop the script from running further
	 *
	 * @param string $message Message to add to log and to outout on screen.
	 *
	 * @throws \WP_CLI\ExitException WP CLI Exit Exception.
	 *
	 * @return void
	 */
	protected function error( $message ) {
		$this->write_log( $message, -1 );
	}

	/**
	 * Method to log a success message
	 *
	 * @param string $message Message to add to log and to outout on screen.
	 *
	 * @throws \WP_CLI\ExitException WP CLI Exit Exception.
	 *
	 * @return void
	 */
	protected function success( $message ) {
		$this->write_log( $message, 1 );
	}

	/**
	 * Method to log a warning message
	 *
	 * @param string $message Message to add to log and to outout on screen.
	 *
	 * @throws \WP_CLI\ExitException WP CLI Exit Exception.
	 *
	 * @return void
	 */
	protected function warning( $message ) {
		$this->write_log( $message, 2 );
	}


}
