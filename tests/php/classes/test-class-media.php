<?php
/**
 * Unit test case for Media class.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Tests\Inc;

use WP_Facebook_Posts\Inc\Media;

/**
 * Class Test_Media
 *
 * @coversDefaultClass \WP_Facebook_Posts\Inc\Media
 */
class Test_Media extends \WP_UnitTestCase {

	/**
	 * To clean Upload directory.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->remove_added_uploads();
	}

	/**
	 * @covers ::get_url_hash
	 */
	public function test_get_url_hash() {

		$input = 'https://example.test/hello-world.html';

		$expected_output = md5( strtolower( trim( $input ) ) );

		$this->assertEquals( $expected_output, Media::get_url_hash( $input ) );

	}

	/**
	 * @covers ::find_attachment_by_import_url
	 */
	public function test_find_attachment_by_import_url() {

		/**
		 * Mock attachment data, And create attachment.
		 */
		$external_image_url = 'https://external.test/picture/sample.jpg';
		$url_hash           = md5( strtolower( trim( $external_image_url ) ) );
		$image_path         = dirname( dirname( __DIR__ ) ) . '/data/sample.png';
		$attachment_id      = $this->factory->attachment->create_upload_object( $image_path );

		/**
		 * Test 1: Check without meta, Response should be 0.
		 */
		$this->assertEquals( 0, Media::find_attachment_by_import_url( $external_image_url ) );

		// Mock attachment meta.
		update_post_meta( $attachment_id, 'wp_facebook_import_url_hash', $url_hash );

		/**
		 * Test 2: Attachment value should $attachment_id.
		 */
		$this->assertEquals( $attachment_id, Media::find_attachment_by_import_url( $external_image_url ) );

		/**
		 * Test 3: Get Attachment value from static variable, And not from DataBase query.
		 */
		$image_url = 'https://external.test/picture/sample-2.jpg';
		$url_hash  = md5( strtolower( trim( $image_url ) ) );

		// Mock the static variable.
		Media::$downloaded_media[ $url_hash ] = 9999;

		$this->assertEquals( 9999, Media::find_attachment_by_import_url( $image_url ) );

	}

	/**
	 * @covers ::find_attachment_by_import_url
	 */
	public function test_find_attachment_by_import_url_when_no_empty_value_passed() {

		$this->assertEquals( 0, Media::find_attachment_by_import_url( '' ) );

	}

	/**
	 * @covers ::create_attachment_by_url
	 */
	public function test_create_attachment_by_url() {

		/**
		 * Test 1: Empty URL.
		 */
		$this->assertEquals( 0, Media::create_attachment_by_url( '' ) );

		/**
		 * Test 2: Invalid Image.
		 */
		$image_url = 'https://via.placeholder.com/10/';
		$output    = Media::create_attachment_by_url( $image_url );

		$this->assertInstanceOf( 'WP_Error', $output );
		$this->assertEquals( 'attachment_processing_error', $output->get_error_code() );

		/**
		 * Test 3: Valid Image.
		 */
		$image_url = 'https://via.placeholder.com/10.jpeg';
		$output    = Media::create_attachment_by_url( $image_url );

		$this->assertNotEmpty( $output );
		$this->assertIsInt( $output );

		/**
		 * Test 4: Image with same URL again.
		 */
		$this->assertEquals( $output, Media::create_attachment_by_url( $image_url ) );

		/**
		 * Test 5: Absolute URL.
		 *
		 * In Absolute URL, Function will consider as it's from same site.
		 */
		$image_url = '/upload/sample.jpg';
		$output    = Media::create_attachment_by_url( $image_url );

		// Note: In actual case. This won't be error. And will return actual attachment ID,
		// Since, It's Unit Test cases, No response will come from server as an actual site.
		$this->assertInstanceOf( 'WP_Error', $output );
		$this->assertEquals( 'import_file_error', $output->get_error_code() );
	}

	/**
	 * @covers ::fetch_remote_file
	 */
	public function test_fetch_remote_file() {

		/**
		 * Test 1: Empty URL.
		 */
		$this->assertEmpty( Media::fetch_remote_file( '' ) );

		/**
		 * Test 2: Invalid Image URL.
		 */
		$output = Media::fetch_remote_file( 'https://external.test/picture/sample-4.jpg' );

		$this->assertInstanceOf( 'WP_Error', $output );
		$this->assertEquals( 'import_file_error', $output->get_error_code() );

		/**
		 * Test 3: Invalid Image.
		 */
		$image_url = 'https://via.placeholder.com/10/';
		$output    = Media::fetch_remote_file( $image_url );

		$this->assertInstanceOf( 'WP_Error', $output );
		$this->assertEquals( 'attachment_processing_error', $output->get_error_code() );

		/**
		 * Test 4: Valid Image.
		 */
		$image_url = 'https://via.placeholder.com/10.jpeg';
		$output    = Media::fetch_remote_file( $image_url );

		$upload_directory = wp_upload_dir();

		$this->assertEquals( 'image/jpeg', $output['type'] );
		$this->assertContains( $upload_directory['url'], $output['url'] );
		$this->assertContains( $upload_directory['path'], $output['file'] );

	}

}
