<?php
/**
 * Unit test cases for base class.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Tests\Inc\WP_CLI;

use WP_Facebook_Posts\Inc\WP_CLI\Base;
use WP_Facebook_Posts\Tests\Helpers;

/**
 * Class Base
 *
 * @coversDefaultClass \WP_Facebook_Posts\Inc\WP_CLI\Base
 */
class Test_Base extends \WP_UnitTestCase {

	/**
	 * @var \WP_Facebook_Posts\Inc\WP_CLI\Base
	 */
	protected $_instance = false;

	/**
	 * Setup method.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->_instance = new Base();
	}

	/**
	 * @covers ::_extract_args
	 */
	public function test_extract_args() {

		$assoc_args = [
			'log-file' => './wp-facebook-import.log',
			'logs'     => 'true',
			'dry-run'  => 'true',
		];

		Helpers::execute_private_method( $this->_instance, '_extract_args', [ $assoc_args ] );

		$this->assertEquals( $assoc_args['log-file'], $this->_instance->log_file );
		$this->assertEquals( true, $this->_instance->logs );
		$this->assertEquals( true, $this->_instance->dry_run );

	}

	/**
	 * @covers ::write_log
	 * @covers ::error
	 * @covers ::success
	 * @covers ::warning
	 */
	public function test_write_log() {

		$assoc_args = [
			'log-file' => './wp-facebook-import.log',
			'logs'     => 'true',
			'dry-run'  => 'true',
		];

		Helpers::execute_private_method( $this->_instance, '_extract_args', [ $assoc_args ] );

		/**
		 * Test 1: Default message.
		 */
		$output = Helpers::get_buffer_input(
			'\WP_Facebook_Posts\Tests\Helpers::execute_private_method',
			[
				$this->_instance,
				'write_log',
				[ 'This is Default message.' ]
			]
		);

		$this->assertContains( 'This is Default message.', $output );

		/**
		 * Test 2: Success message.
		 */
		$output = Helpers::get_buffer_input(
			'\WP_Facebook_Posts\Tests\Helpers::execute_private_method',
			[
				$this->_instance,
				'success',
				[ 'This is Success message.' ]
			]
		);

		$this->assertContains( 'This is Success message.', $output );

		/**
		 * Test 3: Warning message.
		 */
		$output = Helpers::get_buffer_input(
			'\WP_Facebook_Posts\Tests\Helpers::execute_private_method',
			[
				$this->_instance,
				'warning',
				[ 'This is Warning message.' ]
			]
		);

		$this->assertContains( 'Warning: This is Warning message.', $output );

		/**
		 * Test 4: Error message
		 */
		$output = Helpers::get_buffer_input(
			'\WP_Facebook_Posts\Tests\Helpers::execute_private_method',
			[
				$this->_instance,
				'error',
				[ 'This is Error message.' ]
			]
		);

		$this->assertContains( 'Error: This is Error message.', $output );

		/**
		 * Test 5: When invalid value pass for second argument.
		 */
		$output = Helpers::get_buffer_input(
			'\WP_Facebook_Posts\Tests\Helpers::execute_private_method',
			[
				$this->_instance,
				'write_log',
				[ 'This is second Default message.', 9 ]
			]
		);

		$this->assertContains( 'This is second Default message.', $output );

		$this->unlink( $assoc_args['log-file'] );

	}

}
