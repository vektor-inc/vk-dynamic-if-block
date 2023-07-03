<?php
/**
 * Class SampleTest
 *
 * @package vektor-inc/vk-dynamic-if-block
 */

/**
 * Sample test case.
 */

class VkDynamicIfBlockRenderTest extends WP_UnitTestCase {

	/**
	 * PHP Unit テストにあたって、ユーザーを登録します。
	 *
	 * @return array $test_users : 作成したユーザーidを配列で返します。
	 */
	public static function create_test_users() {

		$test_users = array();

		// テスト用ユーザーを発行.
		$userdata                = array(
			'user_login'   => 'vektor',
			'user_url'     => 'https://vektor-inc.co.jp',
			'user_pass'    => 'password',
			'display_name' => 'Vektor, Inc.',
		);
		$test_users['test_user'] = wp_insert_user( $userdata, $userdata['user_pass'] );

		return $test_users;
	}

	/**
	 * PHP Unit テストにあたって、各種投稿やカスタム投稿タイプ、カテゴリーを登録します。
	 *
	 * @return array $test_posts : 作成した投稿の記事idなどを配列で返します。
	 */
	public static function create_test_posts() {

		$test_posts = array();
		$test_users = self::create_test_users();

		/******************************************
		 * カテゴリーの登録 */

		// 親カテゴリー parent_category を登録.
		$catarr                           = array(
			'cat_name' => 'parent_category',
		);
		$test_posts['parent_category_id'] = wp_insert_category( $catarr );

		// 子カテゴリー child_category を登録.
		$catarr                          = array(
			'cat_name'        => 'child_category',
			'category_parent' => $test_posts['parent_category_id'],
		);
		$test_posts['child_category_id'] = wp_insert_category( $catarr );

		// 投稿を割り当てないカテゴリー no_post_category を登録.
		$catarr                            = array(
			'cat_name' => 'no_post_category',
		);
		$test_posts['no_post_category_id'] = wp_insert_category( $catarr );

		/******************************************
		 * タグの登録 */
		$args                      = array(
			'slug' => 'test_tag_name',
		);
		$term_info                 = wp_insert_term( 'test_tag_name', 'post_tag', $args );
		$test_posts['test_tag_id'] = $term_info['term_id'];

		/******************************************
		 * 投稿タイプ event を追加 */
		register_post_type(
			'event',
			array(
				'label'       => 'Event',
				'has_archive' => true,
				'public'      => true,
			)
		);

		/******************************************
		 * カスタム分類 event_cat を追加 */
		register_taxonomy(
			'event_cat',
			'event',
			array(
				'label'        => 'Event Category',
				'rewrite'      => array( 'slug' => 'event_cat' ),
				'hierarchical' => true,
			)
		);

		/******************************************
		 * カスタム分類 の登録 */
		$args                        = array(
			'slug' => 'event_category_name',
		);
		$term_info                   = wp_insert_term( 'event_category_name', 'event_cat', $args );
		$test_posts['event_term_id'] = $term_info['term_id'];

		/******************************************
		 * テスト用投稿の登録 */

		// 通常の投稿 Test Post を投稿.
		$post                  = array(
			'post_title'    => 'Test Post',
			'post_status'   => 'publish',
			'post_author'   => $test_users['test_user'],
			'post_content'  => 'content',
			'post_category' => array( $test_posts['parent_category_id'] ),
		);
		$test_posts['post_id'] = wp_insert_post( $post );
		// 投稿にカテゴリー指定.
		wp_set_object_terms( $test_posts['post_id'], 'child_category', 'category' );
		wp_set_object_terms( $test_posts['post_id'], 'test_tag_name', 'post_tag' );

		// 固定ページ Parent Page を投稿.
		$post                         = array(
			'post_title'   => 'Parent Page',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_author'  => $test_users['test_user'],
			'post_content' => 'content',
		);
		$test_posts['parent_page_id'] = wp_insert_post( $post );

		// 固定ページの子ページ Child Page を投稿.
		$post = array(
			'post_title'   => 'Child Page',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_author'  => $test_users['test_user'],
			'post_content' => 'content',
			'post_parent'  => $test_posts['parent_page_id'],

		);
		$test_posts['child_page_id'] = wp_insert_post( $post );

		// 投稿トップ用の固定ページ Post Top を投稿.
		$post                       = array(
			'post_title'   => 'Post Top',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_author'  => $test_users['test_user'],
			'post_content' => 'content',
		);
		$test_posts['home_page_id'] = wp_insert_post( $post );

		// フロントページ用の固定ページ Front Page を投稿.
		$post                        = array(
			'post_title'   => 'Front Page',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_author'  => $test_users['test_user'],
			'post_content' => 'content',
		);
		$test_posts['front_page_id'] = wp_insert_post( $post );

		// カスタム投稿タイプ event 用の Event Test Post を投稿.
		$post                        = array(
			'post_title'   => 'Event Test Post',
			'post_type'    => 'event',
			'post_status'  => 'publish',
			'post_author'  => $test_users['test_user'],
			'post_content' => 'content',
		);
		$test_posts['event_post_id'] = wp_insert_post( $post );

		// 作成した Event Test Post にイベントカテゴリーを指定.
		wp_set_object_terms( $test_posts['event_post_id'], 'event_category_name', 'event_cat' );

		return $test_posts;
	}

	/**
	 * Test render call back ブロックのテスト
	 */
	public function test_vk_dynamic_if_block_render() {

		print PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print 'vk_dynamic_if' . PHP_EOL;
		print '------------------------------------' . PHP_EOL;
		print PHP_EOL;

		// Create test posts.
		$test_posts = self::create_test_posts();

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

			array(
				'name'      => 'Front exclusion ( Front-page: page, Posts page: page )',
				'options'   => array(
					'page_on_front'  => $test_posts['front_page_id'],
					'show_on_front'  => 'page',
					'page_for_posts' => $test_posts['home_page_id'],
				),
				'go_to'     => home_url(),
				'attribute' => array(
					'ifPageType' => 'is_front_page',
					'ifPostType' => 'none',
					'exclusion'  => true,
				),
				'content'   => 'Front excluded',
				'expected'  => '',
			),
			array(
				'name'      => 'Front exclusion ( Front-page: posts )',
				'options'   => array(
					'show_on_front' => 'posts',
				),
				'go_to'     => home_url(),
				'attribute' => array(
					'ifPageType' => 'is_front_page',
					'ifPostType' => 'none',
					'exclusion'  => true,
				),
				'content'   => 'Front excluded',
				'expected'  => '',
			),

			/******************************************
			 * Home */
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
			 * Yearly Archive page */
			array(
				'name'      => 'Yearly Archive page',
				'go_to'     => get_year_link( gmdate( 'Y' ) ),
				'attribute' => array(
					'ifPageType' => 'is_year',
				),
				'content'   => 'Yearly Archive page',
				'expected'  => 'Yearly Archive page',
			),
			/******************************************
			* Monthly Archive page */
			array(
				'name'      => 'Monthly Archive page',
				'go_to'     => get_month_link( gmdate( 'Y' ), gmdate( 'm' ) ),
				'attribute' => array(
					'ifPageType' => 'is_month',
				),
				'content'   => 'Monthly Archive page',
				'expected'  => 'Monthly Archive page',
			),
			/******************************************
			* Daily Archive page */
			array(
				'name'      => 'Daily Archive page',
				'go_to'     => get_day_link( gmdate( 'Y' ), gmdate( 'm' ), gmdate( 'd' ) ),
				'attribute' => array(
					'ifPageType' => 'is_date',
				),
				'content'   => 'Daily Archive page',
				'expected'  => 'Daily Archive page',
			),
			/******************************************
			* Category archive page */
			array(
				'name'      => 'Category archive page',
				'go_to'     => get_category_link( $test_posts['parent_category_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_category',
				),
				'content'   => 'Category Archive page',
				'expected'  => 'Category Archive page',
			),
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
			* Tag archive page */
			array(
				'name'      => 'Tag archive page',
				'go_to'     => get_term_link( $test_posts['test_tag_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_tag',
				),
				'content'   => 'Tag Archive page',
				'expected'  => 'Tag Archive page',
			),
			/******************************************
			* Term archive page */
			array(
				'name'      => 'Term archive page',
				'go_to'     => get_term_link( $test_posts['event_term_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_tax',
				),
				'content'   => 'Term Archive page',
				'expected'  => 'Term Archive page',
			),
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
			array(
				'name'      => 'Term archive page',
				'go_to'     => get_term_link( $test_posts['event_term_id'] ),
				'attribute' => array(
					'ifPageType' => 'is_single',
					'ifPostType' => 'event',
					'exclusion'  => true,
				),
				'content'   => 'Term Archive page',
				'expected'  => 'Term Archive page',
			),
			// Author archive page.
			array(
				'name'      => 'Author archive page',
				'go_to'     => get_author_posts_url( 1 ),
				'attribute' => array(
					'ifPageType' => 'is_author',
				),
				'content'   => 'Author Archive page',
				'expected'  => 'Author Archive page',
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
			// single exclusion.
			array(
				'name'      => '! Post Type Event',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPostType' => 'event',
					'exclusion'  => true,
				),
				'content'   => 'Post Type Event',
				'expected'  => '',
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
			/******************************************
			 * カスタムフィールド
			 *
			 * @since 0.4.0 */

			// URLで home_url() . /non-exist-page/ にアクセスしてもサーバー側で Not Found にされてしまい WordPressの 404 にならないため
			// home_url() . '/?cat=999999' で指定している
			array(
				'name'      => 'Custom Field Exist',
				'go_to'     => home_url() . '/?cat=999999',
				'attribute' => array(
					'ifPageType'       => 'none',
					'ifPostType'       => 'none',
					'CustomFieldName'  => 'price',
					'customFieldRule'  => '',
					'customFieldValue' => '',
				),
				'content'   => '100',
				'expected'  => '100',
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
