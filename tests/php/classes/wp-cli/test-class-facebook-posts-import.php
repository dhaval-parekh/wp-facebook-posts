<?php
/**
 * Unit test case for Facebook_Posts_Import
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Tests\Inc\WP_CLI;

use WP_Facebook_Posts\Inc\WP_CLI\Facebook_Posts_Import;
use WP_Facebook_Posts\Tests\Helpers;

/**
 * Class Test_Facebook_Posts_Import
 *
 * @coversDefaultClass \WP_Facebook_Posts\Inc\WP_CLI\Facebook_Posts_Import
 */
class Test_Facebook_Posts_Import extends \WP_UnitTestCase {

	/**
	 * @var \WP_Facebook_Posts\Inc\WP_CLI\Facebook_Posts_Import
	 */
	protected $_instance = false;

	/**
	 * Setup method.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->_instance = new Facebook_Posts_Import();
	}

	/**
	 * @covers ::import_from_json_file
	 */
	public function test_import_from_json_file_invalid_import_file() {

		$data_directory = dirname( dirname( dirname( __DIR__ ) ) );

		/**
		 * Test 1: Check with no import file.
		 */
		$output = Helpers::get_buffer_input( [ $this->_instance, 'import_from_json_file' ], [ [], [] ] );

		$this->assertEquals( "Error: Please provide JSON file.\n", $output );

		/**
		 * Test 2: Check with providing invalid file path.
		 */
		$output_2 = Helpers::get_buffer_input(
			[ $this->_instance, 'import_from_json_file' ],
			[
				[],
				[
					'import-file' => 'invalid/file/location.json',
				]
			]
		);

		$this->assertEquals( "Error: File does not exists in given path.\n", $output_2 );

		/**
		 * Test 3: Invalid file.
		 */
		$output = Helpers::get_buffer_input(
			[ $this->_instance, 'import_from_json_file' ],
			[
				[],
				[
					'import-file' => dirname( dirname( __DIR__ ) ) . '/../data/sample.png',
				]
			]
		);

		$this->assertEquals( "Error: Invalid file provided.\n", $output );

		/**
		 * Test 4: Valid file but not posts.
		 */
		$output = Helpers::get_buffer_input(
			[ $this->_instance, 'import_from_json_file' ],
			[
				[],
				[
					'import-file' => $data_directory . '/data/zero-facebook-posts.json',
				]
			]
		);

		$this->assertEquals( "Error: No post found in JSON file.\n", $output );

	}

	/**
	 * @covers ::import_from_json_file
	 * @covers ::_import_start
	 * @covers ::_import_end
	 * @covers ::_stop_the_insanity
	 */
	public function test_import_from_json_file() {

		$data_directory = dirname( dirname( dirname( __DIR__ ) ) );
		$import_file    = $data_directory . '/data/facebook-data.json';

		$input_data = file_get_contents( $import_file );
		$input_data = json_decode( $input_data, 1 );
		$input_data = ( ! empty( $input_data['data'] ) && is_array( $input_data['data'] ) ) ? $input_data['data'] : [];

		$all_ids = wp_list_pluck( $input_data, 'id' );

		/**
		 * Test 1: Dry Run.
		 */
		$output = Helpers::get_buffer_input(
			[ $this->_instance, 'import_from_json_file' ],
			[
				[],
				[
					'import-file' => $import_file,
				]
			]
		);

		foreach ( $all_ids as $id ) {
			$this->assertContains(
				sprintf( '%s: -- Will create new post', $id ),
				$output
			);
		}

		/**
		 * Test 2: Actual Run.
		 */
		$output = Helpers::get_buffer_input(
			[ $this->_instance, 'import_from_json_file' ],
			[
				[],
				[
					'import-file' => $import_file,
					'dry-run'     => 'false',
					'batch-size'  => 5,
				]
			]
		);

		// New Post will created for this posts.
		$expected_create_ids = [
			'309223206523329_523500428428938',
			'309223206523329_521686031943711',
			'309223206523329_521426575302990',
			'309223206523329_521424451969869',
		];

		// This post will updated,
		// Note: In JSON file we have two record for same ID with different content.
		$expected_update_ids = [
			'309223206523329_521426575302990',
		];

		// These will failed to create post, Since those don't have title, content or excerpt.
		$expected_fail_ids = [
			'309223206523329_522033981908916',
			'309223206523329_521455098633471',
			'309223206523329_521454625300185',
		];

		foreach ( $expected_create_ids as $id ) {
			$this->assertContains(
				sprintf( '%s: -- Created:', $id ),
				$output
			);
		}

		foreach ( $expected_update_ids as $id ) {
			$this->assertContains(
				sprintf( '%s: -- Updated:', $id ),
				$output
			);
		}

		foreach ( $expected_fail_ids as $id ) {
			$this->assertContains(
				sprintf( '%s: -- Failed', $id ),
				$output
			);
		}

		/**
		 * Test 3: Second time Dry run.
		 */
		$output = Helpers::get_buffer_input(
			[ $this->_instance, 'import_from_json_file' ],
			[
				[],
				[
					'import-file' => $import_file,
				]
			]
		);

		// All posts will update, except those which failed to create last time.
		foreach ( $expected_fail_ids as $id ) {
			$this->assertContains(
				sprintf( '%s: -- Will create new post.', $id ),
				$output
			);
		}

		$last_run_created_ids = array_diff( $all_ids, $expected_fail_ids );

		foreach ( $last_run_created_ids as $id ) {
			$this->assertContains(
				sprintf( '%s: -- Will update', $id ),
				$output
			);
		}

		/**
		 * Test 4: Second time actual run.
		 */
		$output = Helpers::get_buffer_input(
			[ $this->_instance, 'import_from_json_file' ],
			[
				[],
				[
					'import-file' => $import_file,
					'dry-run'     => 'false',
				]
			]
		);

		// All those last time created, Those will skip this time.
		// Except for one record for which we have two record.
		$expected_skip_ids = array_diff( $expected_create_ids, $expected_update_ids );

		foreach ( $expected_skip_ids as $id ) {
			$this->assertContains(
				sprintf( '%s: -- Skipped -- Post ID:', $id ),
				$output
			);
		}


		// One those were, failed last time will fail again.
		foreach ( $expected_fail_ids as $id ) {
			$this->assertContains(
				sprintf( '%s: -- Failed -- Message:', $id ),
				$output
			);
		}

	}

}
