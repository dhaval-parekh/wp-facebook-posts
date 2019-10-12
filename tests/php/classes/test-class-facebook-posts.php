<?php
/**
 * Unit test case for Facebook_Posts class.
 *
 * @package wp-facebook-posts
 */

namespace WP_Facebook_Posts\Tests\Inc;

use WP_Facebook_Posts\Inc\Facebook_Posts;
use WP_Facebook_Posts\Inc\Media;

/**
 * Class Test_Facebook_Posts
 *
 * @coversDefaultClass \WP_Facebook_Posts\Inc\Facebook_Posts
 */
class Test_Facebook_Posts extends \WP_UnitTestCase {

	/**
	 * @covers ::get_post_id_by_facebook_post_id
	 */
	public function test_get_post_id_by_facebook_post_id() {

		$facebook_post_id = '1234567890_0987654321';

		/**
		 * Test 1: Empty facebook ID.
		 */
		$this->assertEmpty( Facebook_Posts::get_post_id_by_facebook_post_id( '' ) );

		/**
		 * Test 2: Facebook ID. With no record in WordPress
		 */
		$this->assertEmpty( Facebook_Posts::get_post_id_by_facebook_post_id( $facebook_post_id ) );

		/**
		 * Test 3: Facebook ID. With record in WordPress
		 */
		// Mock post.
		$post_id = $this->factory->post->create( [
			'meta_input' => [
				'wp_facebook_post_id' => $facebook_post_id,
			]
		] );

		$this->assertEquals( $post_id, Facebook_Posts::get_post_id_by_facebook_post_id( $facebook_post_id ) );
	}

	/**
	 * @covers ::import_single_post
	 * @covers ::_prepare_post_data
	 */
	public function test_import_single_post() {

		$import_args = [
			'attachment-import' => true,
		];

		/**
		 * Test 1: Empty values.
		 */
		$output = Facebook_Posts::import_single_post( [], $import_args );
		$this->assertEmpty( $output['post_id'] );

		/**
		 * Test 2: Data without ID.
		 */
		$input  = [
			'message'     => 'Post Message what will come here',
			'picture'     => 'https://via.placeholder.com/10.jpeg',
			'link'        => 'http://trib.al/XYZ',
			'name'        => 'Post title',
			'caption'     => 'Caption',
			'description' => ' ',
		];
		$output = Facebook_Posts::import_single_post( $input, $import_args );
		$this->assertEmpty( $output['post_id'] );

		/**
		 * Test 3: Check with actual data.
		 *
		 * Should create new post, and save as draft.
		 */
		$input = [
			'id'           => '309223206523329_523500428428938',
			'from'         => [
				'name' => 'CipherSoul_Test',
				'id'   => 309223206523329,
			],
			'full_picture' => 'https://scontent.xx.fbcdn.net/v/t15.13418-10/70191280_1344117842428846_6738242141924884480_n.jpeg?_nc_cat=109&_nc_oc=AQmU0rHA2MagNl48jBzPojz2jq1xTGarcGNXO2HfsZdVSHoaRI8JgskbN8pHVzt8HPA&_nc_ht=scontent.xx&oh=a544d15ce38987bc2ad11deca78dbee9&oe=5E20C304',
			'picture'      => 'https://scontent.xx.fbcdn.net/v/t15.13418-10/s130x130/70191280_1344117842428846_6738242141924884480_n.jpeg?_nc_cat=109&_nc_oc=AQmU0rHA2MagNl48jBzPojz2jq1xTGarcGNXO2HfsZdVSHoaRI8JgskbN8pHVzt8HPA&_nc_ht=scontent.xx&oh=165e2f8b3ae14e15a2890ebe49606a4b&oe=5DF0073D',
			'icon'         => 'https://www.facebook.com/images/icons/video.gif',
			'actions'      => [
				[
					'name' => 'Like',
					'link' => 'https://www.facebook.com/309223206523329/posts/523500428428938/',
				],
				[
					'name' => 'Comment',
					'link' => 'https://www.facebook.com/309223206523329/posts/523500428428938/',
				],
				[
					'name' => 'Share',
					'link' => 'https://www.facebook.com/309223206523329/posts/523500428428938/'
				],
			],
			'privacy'      => [
				'allow'       => '',
				'deny'        => '',
				'description' => 'Public',
				'friends'     => '',
				'value'       => 'EVERYONE',
			],
			'status_type'  => 'added_video',
			'created_time' => '2019-10-05T09:19:28+0000',
			'updated_time' => '2019-10-05T09:19:28+0000',
			'message'      => 'This is dummy video description.',
		];

		$first_response = Facebook_Posts::import_single_post( $input, $import_args );

		$this->assertIsInt( $first_response['post_id'] );
		$this->assertFalse( $first_response['is_updated'] );
		$this->assertFalse( $first_response['is_skipped'] );

		// Assert Post's data.
		$post_id = $first_response['post_id'];
		$post    = get_post( $post_id );

		$expected_date          = gmdate( 'Y-m-d H:i:s', strtotime( $input['created_time'] ) );
		$expected_modified_date = gmdate( 'Y-m-d H:i:s', strtotime( $input['created_time'] ) );

		$this->assertEquals( '', $post->post_title );
		$this->assertEquals( $input['message'], $post->post_content );
		$this->assertEquals( 'draft', $post->post_status );
		$this->assertEquals( $expected_date, $post->post_date_gmt );
		$this->assertEquals( $expected_modified_date, $post->post_modified_gmt );

		/**
		 * Test 4: Try to update same post again. Should have skip this.
		 */
		$second_response = Facebook_Posts::import_single_post( $input, $import_args );

		$this->assertEquals( $post_id, $second_response['post_id'] );
		$this->assertFalse( $second_response['is_updated'] );
		$this->assertTrue( $second_response['is_skipped'] );

		/**
		 * Test 5: Update some content.
		 *
		 * Should add Post Title, and post status should be publish.
		 */
		$input['name']    = 'Lorem Impsum';
		$input['message'] .= ' (Updated)';

		$third_response = Facebook_Posts::import_single_post( $input, $import_args );

		$post = get_post( $post_id );

		$this->assertEquals( $post_id, $third_response['post_id'] );
		$this->assertTrue( $third_response['is_updated'] );
		$this->assertFalse( $third_response['is_skipped'] );

		$this->assertEquals( $input['name'], $post->post_title );
		$this->assertEquals( $input['message'], $post->post_content );
		$this->assertEquals( 'publish', $post->post_status );

		// Check if attachment is set or not.
		$expected_attachment_id = Media::find_attachment_by_import_url( $input['full_picture'] );

		$this->assertEquals( $expected_attachment_id, get_post_thumbnail_id( $post_id ) );

	}

	/**
	 * @covers ::generate_hash
	 */
	public function test_generate_hash() {

		/**
		 * Test 1: Empty value.
		 */
		$this->assertEmpty( Facebook_Posts::generate_hash( '' ) );

		/**
		 * Test 2: Check with string.
		 */
		$input    = 'some_random_string';
		$expected = md5( $input );
		$this->assertEquals( $expected, Facebook_Posts::generate_hash( $input ) );

		/**
		 * Test 3: Check with array.
		 */
		$input = [
			'key_1' => 'value_1',
			'key_2' => 'value_2',
			'key_3' => 'value_3',
		];
		ksort( $input );
		$expected = md5( wp_json_encode( $input ) );

		$this->assertEquals( $expected, Facebook_Posts::generate_hash( $input ) );
	}

}
