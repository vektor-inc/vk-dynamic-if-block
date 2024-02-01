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
			'post_date'    => date( 'Y-m-d', strtotime( '-5 days', strtotime( date( 'Y-m-d' ) ) ) ),
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

			array(
				'name'      => 'Custom Field Exist',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPageType'       => 'none',
					'ifPostType'       => 'none',
					'CustomFieldName'  => 'price',
					'customFieldRule'  => 'valueExists',
					'customFieldValue' => '',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'price',
					'meta_value' => '100',
				),
				'content'   => 'Custom Field Exist',
				'expected'  => 'Custom Field Exist',
			),
			array(
				'name'      => 'Custom Field no value',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPageType'       => 'none',
					'ifPostType'       => 'none',
					'customFieldName'  => 'price',
					'customFieldRule'  => 'valueExists',
					'customFieldValue' => '',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'price',
					'meta_value' => '',
				),
				'content'   => 'Custom Field Exist',
				'expected'  => '',
			),
			array(
				'name'      => 'Custom Field value exist string 0',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPageType'       => 'none',
					'ifPostType'       => 'none',
					'customFieldName'  => 'price',
					'customFieldRule'  => 'valueExists',
					'customFieldValue' => '',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'price',
					'meta_value' => '0',
				),
				'content'   => '0',
				'expected'  => '0',
			),
			array(
				'name'      => 'Custom Field value exist number 0',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPageType'       => 'none',
					'ifPostType'       => 'none',
					'customFieldName'  => 'price',
					'customFieldRule'  => 'valueExists',
					'customFieldValue' => '',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'price',
					'meta_value' => 0,
				),
				'content'   => '0',
				'expected'  => '0',
			),
			array(
				'name'      => 'Custom Field value match',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPageType'       => 'none',
					'ifPostType'       => 'none',
					'customFieldName'  => 'price',
					'customFieldRule'  => 'valueEquals',
					'customFieldValue' => '100',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'price',
					'meta_value' => '100',
				),
				'content'   => 'Custom Field value match',
				'expected'  => 'Custom Field value match',
			),
			array(
				'name'      => 'Custom Field value not match',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPageType'       => 'none',
					'ifPostType'       => 'none',
					'customFieldName'  => 'price',
					'customFieldRule'  => 'valueEquals',
					'customFieldValue' => '100',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'price',
					'meta_value' => '',
				),
				'content'   => 'Custom Field value not match',
				'expected'  => '',
			),
			array(
				'name'      => 'Custom Field value not match',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'ifPageType'       => 'none',
					'ifPostType'       => 'none',
					'customFieldName'  => 'price',
					'customFieldRule'  => null,
					'customFieldValue' => '100',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'price',
					'meta_value' => '100',
				),
				'content'   => 'customFieldRule not set',
				'expected'  => 'customFieldRule not set',
			),
			/******************************************
			* User Role */
			array(
				'name'      => 'Page viewable by administrator and editor',
				'go_to'     => get_permalink( $test_posts['parent_page_id'] ),
				'attribute' => array(
					'userRole' => array(
						'administrator',
						'editor',
					),
				),
				'content'   => 'Page viewable by administrator and editor',
				'expected'  => '',
			),
			array(
				'name'      => 'No restrictions on viewers',
				'go_to'     => get_permalink( $test_posts['parent_page_id'] ),
				'attribute' => array(
					'userRole' => array(),
				),
				'content'   => 'No restrictions on viewers',
				'expected'  => 'No restrictions on viewers',
			),
			array(
				'name'       => 'Editor can view',
				'go_to'      => get_permalink( $test_posts['parent_page_id'] ),
				'attribute'  => array(
					'userRole' => array( 'editor' ),
				),
				'user_roles' => array( 'editor' ),
				'content'    => 'Editor can view',
				'expected'   => 'Editor can view',
			),
			array(
				'name'       => 'Editor can not view',
				'go_to'      => get_permalink( $test_posts['parent_page_id'] ),
				'attribute'  => array(
					'userRole' => array( 'administrator' ),
				),
				'user_roles' => array( 'editor' ),
				'content'    => 'Editor can not view',
				'expected'   => '',
			),
			/******************************************
			* Login User Only */
			array(
				'name'      => 'Only login user can view',
				'go_to'     => get_permalink( $test_posts['parent_page_id'] ),
				'attribute' => array(
					'showOnlyLoginUser' => true,
				),
				'is_login'  => true,
				'content'   => 'Only login user can view',
				'expected'  => 'Only login user can view',
			),
			array(
				'name'      => 'Only login user can view',
				'go_to'     => get_permalink( $test_posts['parent_page_id'] ),
				'attribute' => array(
					'showOnlyLoginUser' => true,
				),
				'is_login'  => false,
				'content'   => 'Only login user can view',
				'expected'  => '',
			),
			/******************************************
			 * Display Period */
			// not specified
			array(
				'name'      => 'Display Period not specified',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'none',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => '',
				),
				'content'   => 'Display Period not specified',
				'expected'  => 'Display Period not specified',
			),
			// deadline
			array(
				'name'      => 'Display Period [ deadline / direct / after today]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => date( 'Y-m-d H:i', strtotime( '+5 days' ) ),
				),
				'content'   => 'Display Period [ deadline / direct / after today]( true )',
				'expected'  => 'Display Period [ deadline / direct / after today]( true )',
			),
			array(
				'name'      => 'Display Period [ deadline / direct / before today]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => date( 'Y-m-d H:i', strtotime( '-5 days' ) ),
				),
				'content'   => 'Display Period [ deadline / direct / before today]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ deadline / direct / before now]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => date( 'Y-m-d H:i', strtotime( '-1 hours' ) ),
				),
				'content'   => 'Display Period [ deadline / direct / before now]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ deadline / direct / Y-m-d today]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => date( 'Y-m-d' ),
				),
				'content'   => 'Display Period [ deadline / direct / Y-m-d today]( true )',
				'expected'  => 'Display Period [ deadline / direct / Y-m-d today]( true )',
			),
			array(
				'name'      => 'Display Period [ deadline / referCustomField / after today]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'datetime',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'datetime',
					'meta_value' => date( 'Y-m-d H:i', strtotime( '+5 days' ) ),
				),
				'content'   => 'Display Period [ deadline / referCustomField / after today]( true )',
				'expected'  => 'Display Period [ deadline / referCustomField / after today]( true )',
			),
			array(
				'name'      => 'Display Period [ deadline / referCustomField / before today]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'datetime',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'datetime',
					'meta_value' => date( 'Y-m-d H:i', strtotime( '-5 days' ) ),
				),
				'content'   => 'Display Period [ deadline / referCustomField / before today]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ deadline / referCustomField(Y-m-d H:i:s) / before today]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'datetime',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'datetime',
					'meta_value' => date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
				),
				'content'   => 'Display Period [ deadline / referCustomField(Y-m-d H:i:s) / before today]( false )',
				'expected'  => '',
			),
			// startline /////////////////////////////////////////////////////////////////////.
			array(
				'name'      => 'Display Period [ startline / direct / after today]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'startline',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => date( 'Y-m-d H:i', strtotime( '+5 days' ) ),
				),
				'content'   => 'Display Period [ startline / direct / after today]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ startline / direct / before today]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'startline',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => date( 'Y-m-d H:i', strtotime( '-5 days' ) ),
				),
				'content'   => 'Display Period [ startline / direct / before today]( true )',
				'expected'  => 'Display Period [ startline / direct / before today]( true )',
			),
			array(
				'name'      => 'Display Period [ startline / direct / before now]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'startline',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => date( 'Y-m-d H:i', strtotime( '-1 hours' ) ),
				),
				'content'   => 'Display Period [ startline / direct / before now]( true )',
				'expected'  => 'Display Period [ startline / direct / before now]( true )',
			),
			array(
				'name'      => 'Display Period [ startline / direct / Y-m-d today]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'startline',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => date( 'Y-m-d' ),
				),
				'content'   => 'Display Period [ startline / direct / Y-m-d today]( true )',
				'expected'  => 'Display Period [ startline / direct / Y-m-d today]( true )',
			),
			array(
				'name'      => 'Display Period [ startline / referCustomField / after today]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'startline',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'datetime',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'datetime',
					'meta_value' => date( 'Y-m-d H:i', strtotime( '+5 days' ) ),
				),
				'content'   => 'Display Period [ startline / referCustomField / after today]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ startline / referCustomField / before today]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'startline',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'datetime',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'datetime',
					'meta_value' => date( 'Y-m-d H:i', strtotime( '-5 days' ) ),
				),
				'content'   => 'Display Period [ startline / referCustomField / before today]( true )',
				'expected'  => 'Display Period [ startline / referCustomField / before today]( true )',
			),
			array(
				'name'      => 'Display Period [ startline / referCustomField(Y-m-d H:i:s) / before today]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'startline',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'datetime',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'datetime',
					'meta_value' => date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
				),
				'content'   => 'Display Period [ startline / referCustomField(Y-m-d H:i:s) / before today]( true )',
				'expected'  => 'Display Period [ startline / referCustomField(Y-m-d H:i:s) / before today]( true )',
			),
			// daysSincePublic /////////////////////////////////////////////////////////////////////.
			array(
				'name'      => 'Display Period [ daysSincePublic / direct / 10 days later]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'daysSincePublic',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => '10',
				),
				'content'   => 'Display Period [ daysSincePublic / direct / 10 days later]( true )',
				'expected'  => 'Display Period [ daysSincePublic / direct / 10 days later]( true )',
			),
			array(
				'name'      => 'Display Period [ daysSincePublic / direct / 5 days later]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'daysSincePublic',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => '5',
				),
				'content'   => 'Display Period [ daysSincePublic / direct / 5 days later]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ daysSincePublic / direct / 3 days later]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'daysSincePublic',
					'periodSpecificationMethod' => 'direct',
					'periodDisplayValue'        => '3',
				),
				'content'   => 'Display Period [ daysSincePublic / direct / 3 days later]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ daysSincePublic / referCustomField / 10 days later]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'daysSincePublic',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'number',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'number',
					'meta_value' => '10',
				),
				'content'   => 'Display Period [ daysSincePublic / referCustomField / 10 days later]( true )',
				'expected'  => 'Display Period [ daysSincePublic / referCustomField / 10 days later]( true )',
			),
			array(
				'name'      => 'Display Period [ daysSincePublic / referCustomField / 5 days later]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'daysSincePublic',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'number',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'number',
					'meta_value' => '5',
				),
				'content'   => 'Display Period [ daysSincePublic / referCustomField / 5 days later]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ daysSincePublic / referCustomField / 3 days later]( false )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'daysSincePublic',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'number',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'number',
					'meta_value' => '3',
				),
				'content'   => 'Display Period [ daysSincePublic / referCustomField / 3 days later]( false )',
				'expected'  => '',
			),
			array(
				'name'      => 'Display Period [ deadline / referCustomField / empty ]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => '',
				),
				'content'   => 'Display Period [ deadline / referCustomField / empty ]( true )',
				'expected'  => 'Display Period [ deadline / referCustomField / empty ]( true )',
			),
			array(
				'name'      => 'Display Period [ deadline / referCustomField / not date]( true )',
				'go_to'     => get_permalink( $test_posts['event_post_id'] ),
				'attribute' => array(
					'periodDisplaySetting'      => 'deadline',
					'periodSpecificationMethod' => 'referCustomField',
					'periodReferCustomField'      => 'text',
				),
				'test_meta' => array(
					'post_id'    => $test_posts['event_post_id'],
					'meta_key'   => 'text',
					'meta_value' => 'text',
				),
				'content'   => 'Display Period [ deadline / referCustomField / not date]( true )',
				'expected'  => 'Display Period [ deadline / referCustomField / not date]( true )',
			)
		);

		foreach ( $tests as $test ) {

			if ( isset( $test['options'] ) ) {
				foreach ( $test['options'] as $option => $value ) {
					update_option( $option, $value );
				}
			}

			if ( isset( $test['test_meta'] ) && isset( $test['test_meta']['post_id'] ) ) {
				update_post_meta(
					$test['test_meta']['post_id'],
					$test['test_meta']['meta_key'],
					$test['test_meta']['meta_value']
				);
			}

			print PHP_EOL;
			$this->go_to( $test['go_to'] );
			if ( isset( $test['user_roles'] ) ) {
				$test['attribute']['test_user_roles'] = $test['user_roles'];
				$actual = vk_dynamic_if_block_render( $test['attribute'], $test['content'] );
			} elseif ( isset( $test['is_login'] )) {
				wp_set_current_user($test['is_login'] ? 1 : 0);
				$actual = vk_dynamic_if_block_render( $test['attribute'], $test['content'] );
				wp_set_current_user(0);
			} else {
				$actual = vk_dynamic_if_block_render( $test['attribute'], $test['content'] );
			}


			print 'Page : ' . esc_html( $test['name'] ) . PHP_EOL;
			print 'go_to : ' . esc_html( $test['go_to'] ) . PHP_EOL;
			if ( isset( $test['test_meta'] ) && isset( $test['test_meta']['post_id'] ) ) {
				print 'meta : ' . esc_html( get_post_meta( $test['test_meta']['post_id'], $test['test_meta']['meta_key'], true ) ) . PHP_EOL;
			}
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
