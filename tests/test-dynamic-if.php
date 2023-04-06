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
				'name'      => 'Front Page Home',
				'options'   => array(
					'show_on_front' => 'posts',
				),
				'go_to'     => home_url(),
				'attribute' => array(
					'ifPageType' => 'is_front_page',
					'ifPostType' => 'none',
				),
				'content'   => 'Front Page',
				'expected'  => 'Front Page',
			),
			array(
				'name'      => 'Front Page Home',
				'go_to'     => home_url(),
				'attribute' => array(
					'ifPageType' => 'is_home',
					'ifPostType' => 'none',
				),
				'content'   => 'Front Page',
				'expected'  => 'Front Page',
			),
			// array(
			// 'name'      => 'Home',
			// 'options'   => array(
			// 'page_on_front'  => $test_posts['front_page_id'],
			// 'show_on_front'  => 'page',
			// 'page_for_posts' => $test_posts['home_page_id'],
			// ),
			// 'go_to'     => home_url() . '/?page_id=' . $test_posts['home_page_id'],
			// 'attribute' => array(
			// 'ifPageType' => 'is_home',
			// 'ifPostType' => 'post',
			// ),
			// 'content'   => 'Home',
			// 'expected'  => 'Home',
			// ),

			array(
				'name'      => 'Post Type Archive pag',
				'go_to'     => get_post_type_archive_link( 'event' ),
				'attribute' => array(
					'ifPageType' => 'is_archive',
					'ifPostType' => 'event',
				),
				'content'   => 'Post Type Archive pag',
				'expected'  => 'Post Type Archive pag',
			),
			array(
				'name'      => 'Category archive page',
				'go_to'     => get_category_link( 1 ),
				'attribute' => array(
					'ifPageType' => 'is_archive',
					'ifPostType' => 'post',
				),
				'content'   => 'Category Archive page',
				'expected'  => 'Category Archive page',
			),
			// array(
			// 'name'      => 'Custom taxonomy archive page',
			// 'go_to'     => get_term_link( 'Sci-fi', 'genre' ),
			// 'attribute' => array(
			// 'ifPageType' => 'is_archive',
			// 'ifPostType' => 'book',
			// ),
			// 'content'   => 'Custom Taxonomy Archive page',
			// 'expected'  => 'Custom Taxonomy Archive page',
			// ),

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
			$this->go_to( $test['go_to'] );

			if ( isset( $test['options'] ) ) {
				foreach ( $test['options'] as $option => $value ) {
					update_option( $option, $value );
				}
			}

			print PHP_EOL;

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
