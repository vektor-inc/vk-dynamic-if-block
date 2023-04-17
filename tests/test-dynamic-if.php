<?php
/**
 * Class SampleTest
 *
 * @package vektor-inc/vk-dynamic-if-block
 */

/**
 * Sample test case.
 */

use VK_WP_Unit_Test_Tools\VkWpUnitTestHelpers;

class VkDynamicIfBlockRenderTest extends WP_UnitTestCase {

	public function test_vk_dynamic_if_block_render() {

		print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'vk_dynamic_if' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print PHP_EOL;

		// Create test posts.
		$test_posts = VkWpUnitTestHelpers::create_test_posts();

		$tests = array(
			/******************************************
			 * Front Page */
			array(
				'name'      => 'Front Page',
				'go_to'     => home_url(),
				'attribute' => array(
					'ifPageType' => 'is_front_page',
					'ifPostType' => 'none',
				),
				'content'   => 'Front Page',
				'expected'  => 'Front Page',
			),
			array(
				'name'      => 'Front Post Home',
				'options'   => array(
					'show_on_front' => 'posts',
				),
				'go_to'     => home_url(),
				'attribute' => array(
					'ifPageType' => 'is_front_page',
					'ifPostType' => 'none',
				),
				'content'   => 'Front Post Home',
				'expected'  => 'Front Post Home',
			),
			array(
				'name'      => 'Front Post If Home',
				'go_to'     => home_url(),
				'attribute' => array(
					'ifPageType' => 'is_home',
					'ifPostType' => 'none',
				),
				'content'   => 'Front Post If Home',
				'expected'  => 'Front Post If Home',
			),
			/******************************************
			 *Home */
			array(
				'name'      => 'Home',
				'options'   => array(
					'page_on_front'  => $test_posts['front_page_id'],
					'show_on_front'  => 'page',
					'page_for_posts' => $test_posts['home_page_id'],
				),
				'go_to'     => get_permalink( $test_posts['home_page_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_home',
				),
				'content'   => 'Home',
				'expected'  => 'Home',
			),
			/******************************************
			 * Archive Page */
			array(
				'name'      => 'Post Type Archive page',
				'go_to'     => get_post_type_archive_link( 'event' ),
				'attribute' => array(
					'ifPageType' => 'is_archive',
				),
				'content'   => 'Post Type Archive page',
				'expected'  => 'Post Type Archive page',
			),
			/******************************************
			* Category archive page */
			array(
				'name'      => 'Category archive page',
				'go_to'     => get_category_link( $test_posts['parent_category_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_archive',
					'ifPostType' => 'post',
				),
				'content'   => 'Category Archive page',
				'expected'  => 'Category Archive page',
			),
			/******************************************
			* Term archive page */
			array(
				'name'      => 'Term archive page',
				'go_to'     => get_term_link( $test_posts['event_term_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_archive',
				),
				'content'   => 'Term Archive page',
				'expected'  => 'Term Archive page',
			),
			/******************************************
			* Page */
			array(
				'name'      => 'Page',
				'go_to'     => get_permalink( $test_posts['parent_page_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_page',
				),
				'content'   => 'Page',
				'expected'  => 'Page',
			),
			/******************************************
			* Single */
			array(
				'name'      => 'Single',
				'go_to'     => get_permalink( $test_posts['post_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_single',
				),
				'content'   => 'Single',
				'expected'  => 'Single',
			),
			/******************************************
			* Post Type Event */
			// Post Type Archive page.
			array(
				'name'      => 'Post Type Archive page',
				'go_to'     => get_post_type_archive_link( 'event' ),
				'attribute' => array(
					'ifPageType' => 'is_post_type_archive',
				),
				'content'   => 'Post Type Archive page',
				'expected'  => 'Post Type Archive page',
			),
			array(
				'name'      => 'Post Type Archive page',
				'go_to'     => get_post_type_archive_link( 'event' ),
				'attribute' => array(
					'ifPostType' => 'event',
				),
				'content'   => 'Post Type Archive page',
				'expected'  => 'Post Type Archive page',
			),
			// Term archive page.
			array(
				'name'      => 'Term archive page',
				'go_to'     => get_term_link( $test_posts['event_term_id'] ),
				'attribute' => array(
					'ifPostType' => 'event',
				),
				'content'   => 'Term Archive page',
				'expected'  => 'Term Archive page',
			),
			// single.
			array(
				'name'      => 'Post Type Event',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPostType' => 'event',
				),
				'content'   => 'Post Type Event',
				'expected'  => 'Post Type Event',
			),
			/******************************************
			* 404 Page */
			// URLで home_url() . /non-exist-page/ にアクセスしてもサーバー側で Not Found にされてしまい WordPressの 404 にならないため
			// home_url() . '/?cat=999999' で指定している
			array(
				'name'      => '404 Page',
				'go_to'     => home_url() . '/?cat=999999',
				'attribute' => array(
					'ifPageType' => 'is_404',
					'ifPostType' => 'none',
				),
				'content'   => '404 Page',
				'expected'  => '404 Page',
			),
		);

		foreach ( $tests as $test ) {

			if ( isset( $test['options'] ) ) {
				foreach ( $test['options'] as $option => $value ) {
					update_option( $option, $value );
				}
			}

			print PHP_EOL;
			$this->go_to( $test['go_to'] );
			$actual = vk_dynamic_if_block_render( $test['attribute'], $test['content'] );
			print 'Page : ' . esc_html( $test['name'] ) . PHP_EOL;
			print 'go_to : ' . esc_html( $test['go_to'] ) . PHP_EOL;
			$this->assertSame( $test['expected'], $actual, $test['name'] );

			if ( isset( $test['options'] ) ) {
				foreach ( $test['options'] as $option => $value ) {
					delete_option( $option );
				}
			}
		}

		wp_delete_post( $test_posts['front_page_id'], true );
		wp_delete_post( $test_posts['home_page_id'], true );
	}

}
