<?php
/**
 * Dynamic If Block
 *
 * @package vektor-inc/vk-dynamic-if-block
 */

// WordPress functions are available in this context
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
// phpcs:disable WordPress.WP.Functions

use VektorInc\VK_Helpers\VkHelpers;

/**
 * Block Render function
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 *
 * @return string Rendered content.
 */
function vk_dynamic_if_block_render( $attributes, $content ) {
	$attributes_default = array(
		'ifPageType'                => 'none',
		'ifPostType'                => 'none',
		'ifLanguage'                => 'none',
		'userRole'                  => array(),
		'postAuthor'                => 0,
		'customFieldName'           => '',
		'customFieldRule'           => 'valueExists',
		'customFieldValue'          => '',
		'exclusion'                 => false,
		'periodDisplaySetting'      => 'none',
		'periodSpecificationMethod' => 'direct',
		'periodDisplayValue'        => '',
		'periodReferCustomField'    => '',
		'showOnlyLoginUser'             => '',
		'conditions'                => array(), // 新しい形式
	);
	$attributes         = array_merge( $attributes_default, $attributes );

	// 古い属性のチェック
	$old_attributes = [
		'customFieldName',
		'ifPageType',
		'ifPostType',
		'ifLanguage',
		'userRole',
		'postAuthor',
		'periodDisplaySetting',
		'showOnlyLoginUser'
	];

	$has_old_attributes = false;
	foreach ($old_attributes as $attr) {
		// 属性が存在し、空でなく、'none'でもない場合のみ古い属性とみなす
		if (isset($attributes[ $attr ])) {
			$value = $attributes[ $attr ];
			if (is_array($value)) {
				// 配列の場合は空でないかチェック
				if (!empty($value)) {
					$has_old_attributes = true;
					break;
				}
			} elseif (is_string($value) || is_numeric($value)) {
				// 文字列や数値の場合は空でなく、'none'でもないかチェック
				if (!empty($value) && $value !== 'none' && $value !== 0) {
					$has_old_attributes = true;
					break;
				}
			}
		}
	}

	// 古い属性が存在する場合は古い構造で処理（新しいconditionsやgroupsを無視）
	if ($has_old_attributes) {
		return vk_dynamic_if_block_render_with_old_attributes(
			$attributes, 
			$content
		);
	}

	// conditionsが明示的に設定されている場合は新しい構造を優先
	if (! empty($attributes['conditions'])) {
		return vk_dynamic_if_block_render_with_conditions(
			$attributes, 
			$content
		);
	}

	// groupsが設定されている場合はgroupsを使用
	if (! empty($attributes['groups'])) {
		return vk_dynamic_if_block_render_with_groups(
			$attributes, 
			$content
		);
	}

	// 条件が設定されていない場合はコンテンツを表示
	return $content;
}

/**
 * Render the dynamic if block with groups.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 *
 * @return string Rendered content.
 */
function vk_dynamic_if_block_render_with_groups($attributes, $content)
{
	$groups = $attributes['groups'];
	$groupOperator = $attributes['groupOperator'] ?? 'and';
	$exclusion = $attributes['exclusion'];

	$display = true;
	foreach ($groups as $group_index => $group) {
		$conditions = $group['conditions'] ?? [];

		if (empty($conditions)) {
			$group_result = true;
		} else {
			$group_result = true;
			foreach ($conditions as $condition) {
				$group_result = $group_result 
				&& vk_dynamic_if_block_evaluate_condition($condition);
			}
		}

		if ($group_index === 0) {
			$display = $group_result;
		} else {
			$display = $groupOperator === 'and' 
				? $display && $group_result 
				: $display || $group_result;
		}
	}

	$final_result = ($exclusion ? !$display : $display);

	return $final_result ? $content : '';
}



/**
 * Render the dynamic if block with conditions.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 *
 * @return string Rendered content.
 */
function vk_dynamic_if_block_render_with_conditions($attributes, $content)
{
	if (empty($attributes['conditions'])) {
		return $content;
	}

	$conditionOperator = $attributes['conditionOperator'] ?? 'and';
	$display = true;
	foreach ($attributes['conditions'] as $index => $condition) {
		// ネストされた条件構造を処理
		if (isset($condition['conditions']) && is_array($condition['conditions'])) {
			$group_result = true;
			foreach ($condition['conditions'] as $nested_condition) {
				$nested_result = vk_dynamic_if_block_evaluate_condition(
					$nested_condition
				);
				$group_result = $group_result && $nested_result;
			}
			$result = $group_result;
		} else {
			$result = vk_dynamic_if_block_evaluate_condition(
				$condition
			);
		}

		if ($index === 0) {
			$display = $result;
		} else {
			$display = $conditionOperator === 'and' 
			? $display && $result 
			: $display || $result;
		}
	}

	$final_result = ($attributes['exclusion'] ? !$display : $display);

	return $final_result ? $content : '';
}

/**
 * Render the dynamic if block with old attributes structure.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content.
 *
 * @return string Rendered content.
 */
function vk_dynamic_if_block_render_with_old_attributes($attributes, $content)
{
	$display = true;

	// Page Type Check
	if (!empty($attributes['ifPageType']) && $attributes['ifPageType'] !== 'none') {
		$page_type = $attributes['ifPageType'];
		$page_checks = [
			'is_front_page' => is_front_page(),
			'is_single' => is_single(),
			'is_page' => is_page(),
			'is_singular' => is_singular(),
			'is_home' => is_home() && !is_front_page(),
			'is_post_type_archive' => is_post_type_archive() && !is_year() 
				&& !is_month() && !is_date(),
			'is_category' => is_category(),
			'is_tag' => is_tag(),
			'is_tax' => is_tax(),
			'is_year' => is_year(),
			'is_month' => is_month(),
			'is_date' => is_date(),
			'is_author' => is_author(),
			'is_search' => is_search(),
			'is_404' => is_404(),
			'is_archive' => is_archive()
		];

		if (is_string($page_type) && isset($page_checks[$page_type])) {
			$display = $display && $page_checks[$page_type];
		}
	}

	// Post Type Check
	if (!empty($attributes['ifPostType']) && $attributes['ifPostType'] !== 'none') {
		$post_type = $attributes['ifPostType'];
		$current_type = get_post_type();
		
		if (empty($current_type)) {
			if (is_post_type_archive()) {
				$current_type = get_query_var('post_type');
			} elseif (is_home() && !is_front_page()) {
				$current_type = 'post';
			} elseif (is_front_page()) {
				$current_type = 'page';
			}
		}

		$display = $display && ($current_type === $post_type);
	}

	// Language Check
	if (!empty($attributes['ifLanguage']) && $attributes['ifLanguage'] !== 'none') {
		$language = $attributes['ifLanguage'];
		$display = $display && ($language === get_locale());
	}

	// User Role Check
	if (!empty($attributes['userRole'])) {
		$user_roles = $attributes['userRole'];
		// userRoleが配列でない場合は配列に変換
		if (!is_array($user_roles)) {
			$user_roles = array($user_roles);
		}
		$current_user = wp_get_current_user();
		$display = $display && is_user_logged_in() && array_intersect($current_user->roles, $user_roles);
	}

	// Post Author Check
	if (!empty($attributes['postAuthor']) && $attributes['postAuthor'] !== 0) {
		$author = (int)$attributes['postAuthor'];
		$author_id = (int)get_post_field('post_author', get_the_ID());
		$display = $display && ($author === 0 || is_author($author) 
			|| (is_singular() && $author_id === $author));
	}

	// Custom Field Check
	if (!empty($attributes['customFieldName'])) {
		$field_name = $attributes['customFieldName'];
		$field_value = get_post_meta(get_the_ID(), $field_name, true);
		$rule = $attributes['customFieldRule'] ?? 'valueExists';
		$expected_value = $attributes['customFieldValue'] ?? '';

		switch ($rule) {
			case 'valueExists':
				$display = $display && ($field_value || $field_value === '0');
				break;
			case 'valueEquals':
				$display = $display && ($field_value === $expected_value);
				break;
		}
	}

	// Period Check
	if (!empty($attributes['periodDisplaySetting']) && $attributes['periodDisplaySetting'] !== 'none') {
		$setting = $attributes['periodDisplaySetting'];
		$method = $attributes['periodSpecificationMethod'] ?? 'direct';
		$value = $attributes['periodDisplayValue'] ?? '';
		$refer_field = $attributes['periodReferCustomField'] ?? '';

		$checkers = [
			'deadline' => 'vk_dynamic_if_block_check_deadline',
			'startline' => 'vk_dynamic_if_block_check_startline',
			'daysSincePublic' => 'vk_dynamic_if_block_check_days_since_public'
		];

		if (isset($checkers[$setting])) {
			$period_result = $checkers[$setting]($method, $value, $refer_field);
			$display = $display && $period_result;
		}
	}

	// Login User Check
	if (!empty($attributes['showOnlyLoginUser'])) {
		$display = $display && is_user_logged_in();
	}

	// Exclusion Check
	$final_result = ($attributes['exclusion'] ? !$display : $display);

	return $final_result ? $content : '';
}

/**
 * Evaluate a single condition.
 *
 * @param array $condition Condition to evaluate.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_evaluate_condition($condition)
{
	$type = $condition['type'] ?? '';
	$values = $condition['values'] ?? [];

	switch ($type) {
		case 'pageType':
			return vk_dynamic_if_block_check_page_type($values);
		case 'postType':
			return vk_dynamic_if_block_check_post_type($values);
		case 'language':
			return vk_dynamic_if_block_check_language($values);
		case 'userRole':
			return vk_dynamic_if_block_check_user_role($values);
		case 'postAuthor':
			return vk_dynamic_if_block_check_post_author($values);
		case 'customField':
			return vk_dynamic_if_block_check_custom_field($values);
		case 'period':
			return vk_dynamic_if_block_check_period($values);
		case 'loginUser':
			return vk_dynamic_if_block_check_login_user($values);
		default:
			return true;
	}
}

/**
 * Check page type condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_page_type($values)
{
	$page_type = $values['ifPageType'] ?? '';
	if (empty($page_type) || $page_type === 'none') {
		return true;
	}

	$page_checks = [
		'is_front_page' => is_front_page(),
		'is_single' => is_single(),
		'is_page' => is_page(),
		'is_singular' => is_singular(),
		'is_home' => is_home() && !is_front_page(),
		'is_post_type_archive' => is_post_type_archive() && !is_year() 
			&& !is_month() && !is_date(),
		'is_category' => is_category(),
		'is_tag' => is_tag(),
		'is_tax' => is_tax(),
		'is_year' => is_year(),
		'is_month' => is_month(),
		'is_date' => is_date(),
		'is_author' => is_author(),
		'is_search' => is_search(),
		'is_404' => is_404(),
		'is_archive' => is_archive()
	];

    // $page_typeが文字列でない場合はエラーを回避
    if (!is_string($page_type)) {
        return false;
    }

    $result = $page_checks[$page_type] ?? false;

    // is_pageの場合、階層条件もチェック
    if ($page_type === 'is_page' && $result) {
        $hierarchy_type = $values['pageHierarchyType'] ?? '';
        if (!empty($hierarchy_type) && $hierarchy_type !== 'none') {
            return vk_dynamic_if_block_check_page_hierarchy($values);
        }
    }

    return $result;
}

/**
 * Check post type condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_post_type($values)
{
	$post_type = $values['ifPostType'] ?? '';
	if (empty($post_type) || $post_type === 'none') {
		return true;
	}

	// VkHelpersを使用してより確実に投稿タイプを取得
	if (class_exists('VkHelpers')) {
		$post_type_info = VkHelpers::get_post_type_info();
		$current_type = $post_type_info['slug'] 
			?? get_post_type();
	} else {
		// VkHelpersが存在しない場合はWordPress標準関数を使用
		$current_type = get_post_type();
		if (empty($current_type)) {
			// アーカイブページの場合
			if (is_post_type_archive()) {
				$current_type = get_query_var('post_type');
			} elseif (is_home() && !is_front_page()) {
				$current_type = 'post';
			} elseif (is_front_page()) {
				$current_type = 'page';
			}
		}
	}

    // 投稿タイプが一致しない場合はfalse
    if ($current_type !== $post_type) {
        return false;
    }

    // 固定ページの場合、階層条件もチェック
    if ($post_type === 'page') {
        $hierarchy_type = $values['pageHierarchyType'] ?? '';
        if (!empty($hierarchy_type) && $hierarchy_type !== 'none') {
            return vk_dynamic_if_block_check_page_hierarchy($values);
        }
    }

    return true;
}

/**
 * Check language condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_language($values)
{
	$language = $values['ifLanguage'] ?? '';
	if (empty($language) || $language === 'none') {
		return true;
	}

	return $language === get_locale();
}

/**
 * Check user role condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_user_role($values)
{
	$user_roles = $values['userRole'] ?? [];
	// userRoleが配列でない場合は配列に変換
	if (!is_array($user_roles)) {
		$user_roles = array($user_roles);
	}
	if (empty($user_roles)) {
		return true;
	}

	$current_user = wp_get_current_user();
	return is_user_logged_in() && array_intersect($current_user->roles, $user_roles);
}

/**
 * Check post author condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_post_author($values)
{
	$author = $values['postAuthor'] ?? 0;
	if (empty($author)) {
		return true;
	}

	$author_id = (int)get_post_field('post_author', get_the_ID());
	$author = (int)$author;
	return $author === 0 || is_author($author) 
		|| (is_singular() && $author_id === $author);
}

/**
 * Check custom field condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_custom_field($values)
{
	$field_name = $values['customFieldName'] ?? '';
	if (empty($field_name) || !get_the_ID()) {
		return true;
	}

	$field_value = get_post_meta(get_the_ID(), $field_name, true);
	$rule = $values['customFieldRule'] ?? 'valueExists';
	$expected_value = $values['customFieldValue'] ?? '';

	switch ($rule) {
		case 'valueExists':
			return $field_value || $field_value === '0';
		case 'valueEquals':
			return $field_value === $expected_value;
		default:
			return true;
	}
}

/**
 * Check period condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_period($values)
{
	$setting = $values['periodDisplaySetting'] ?? 'none';
	if ($setting === 'none') {
		return true;
	}

	$method = $values['periodSpecificationMethod'] ?? 'direct';
	$value = $values['periodDisplayValue'] ?? '';
	$refer_field = $values['periodReferCustomField'] ?? '';

	$checkers = [
		'deadline' => 'vk_dynamic_if_block_check_deadline',
		'startline' => 'vk_dynamic_if_block_check_startline',
		'daysSincePublic' => 'vk_dynamic_if_block_check_days_since_public'
	];

	return isset($checkers[$setting]) 
		? $checkers[$setting]($method, $value, $refer_field) 
		: true;
}

/**
 * Check deadline condition.
 *
 * @param string $method      Specification method.
 * @param string $value       Deadline value.
 * @param string $refer_field Custom field name.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_deadline($method, $value, $refer_field)
{
	if ($method === 'direct') {
		if ($value === date('Y-m-d', strtotime($value))) {
			$value .= ' 23:59';
		}
		if ($value !== date('Y-m-d H:i', strtotime($value))) {
			$value = date('Y-m-d H:i', strtotime($value));
		}
		return $value > current_time('Y-m-d H:i');
	}

	if ($method === 'referCustomField' && !empty($refer_field)) {
		$refer_value = get_post_meta(get_the_ID(), $refer_field, true);
		$formats = ['Y-m-d', 'Y-m-d H:i', 'Y-m-d H:i:s'];

		foreach ($formats as $format) {
			if (DateTime::createFromFormat($format, $refer_value)) {
				if ($format === 'Y-m-d') {
					$refer_value .= ' 23:59:59';
				} elseif ($format === 'Y-m-d H:i') {
					$refer_value .= ':59';
				}
				return $refer_value > current_time('Y-m-d H:i:s');
			}
		}
	}
	return true;
}

/**
 * Check startline condition.
 *
 * @param string $method      Specification method.
 * @param string $value       Startline value.
 * @param string $refer_field Custom field name.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_startline($method, $value, $refer_field)
{
	if ($method === 'direct') {
		if ($value === date('Y-m-d', strtotime($value))) {
			$value .= ' 00:00';
		}
		if ($value !== date('Y-m-d H:i', strtotime($value))) {
			$value = date('Y-m-d H:i', strtotime($value));
		}
		return $value <= current_time('Y-m-d H:i');
	}

	if ($method === 'referCustomField' && !empty($refer_field)) {
		$refer_value = get_post_meta(get_the_ID(), $refer_field, true);
		$formats = ['Y-m-d', 'Y-m-d H:i', 'Y-m-d H:i:s'];

		foreach ($formats as $format) {
			if (DateTime::createFromFormat($format, $refer_value)) {
				if ($format === 'Y-m-d') {
					$refer_value .= ' 00:00:00';
				} elseif ($format === 'Y-m-d H:i') {
					$refer_value .= ':00';
				}
				return $refer_value <= current_time('Y-m-d H:i:s');
			}
		}
	}
	return true;
}

/**
 * Check days since public condition.
 *
 * @param string $method      Specification method.
 * @param string $value       Days value.
 * @param string $refer_field Custom field name.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_days_since_public($method, $value, $refer_field)
{
	if ($method === 'direct') {
		$days = (int)$value;
		$publish_date = get_post_time('U', true, get_the_ID());
		return current_time('timestamp') < $publish_date 
			+ ($days * 86400);
	}

	if ($method === 'referCustomField' && !empty($refer_field)) {
		$refer_value = get_post_meta(get_the_ID(), $refer_field, true);
		if (is_numeric($refer_value)) {
			$days = (int)$refer_value;
			$publish_date = get_post_time('U', true, get_the_ID());
			return current_time('timestamp') < $publish_date 
				+ ($days * 86400);
		}
	}
	return true;
}

/**
 * Check login user condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_login_user($values)
{
    return !($values['showOnlyLoginUser'] ?? false) 
        || is_user_logged_in();
}

/**
 * Check page hierarchy condition.
 *
 * @param array $values Condition values.
 *
 * @return bool Evaluation result.
 */
function vk_dynamic_if_block_check_page_hierarchy($values)
{
    $hierarchy_type = $values['pageHierarchyType'] ?? '';
    if (empty($hierarchy_type) || $hierarchy_type === 'none') {
        return true;
    }

    // 固定ページ以外では常にtrueを返す
    if (!is_page()) {
        return true;
    }

    $current_page_id = get_the_ID();
    if (!$current_page_id) {
        return true;
    }

    // ページ階層の条件
    switch ($hierarchy_type) {
    case 'has_parent':
        $parent_id = wp_get_post_parent_id($current_page_id);
        $result = $parent_id > 0;
        return $result;
            
    case 'has_children':
        $children = get_pages(
            [
            'parent' => $current_page_id,
            'number' => 1,
            'post_type' => 'page',
            'post_status' => 'publish'
                ]
        );
        $result = !empty($children);
        return $result;
            
    default:
        return true;
    }
}

/**
 * Register dynamic block.
 *
 * @return void
 */
function vk_dynamic_if_block_register_dynamic()
{
	register_block_type(
		__DIR__ . '/block.json', 
		['render_callback' => 'vk_dynamic_if_block_render']
	);
}
add_action('init', 'vk_dynamic_if_block_register_dynamic');

/**
 * Set localize script data.
 *
 * @return void
 */
function vk_dynamic_if_block_set_localize_script()
{
	// 投稿タイプオプション
	$post_types = array_merge(
		['post', 'page'],
		get_post_types(
			['public' => true, 'show_ui' => true, '_builtin' => false], 
			'names'
		)
	);
	$post_type_options = [
		['label' => __('No restriction', 'vk-dynamic-if-block'), 'value' => 'none']
	];
	foreach ($post_types as $type) {
		$obj = get_post_type_object($type);
		$post_type_options[] = [
			'label' => $obj->labels->singular_name, 
			'value' => $obj->name
		];
	}

	// 言語オプション
	$language_options = [
		['label' => __('Unspecified', 'vk-dynamic-if-block'), 'value' => ''],
		['label' => 'English (United States)', 'value' => 'en_US']
	];

	$response = wp_remote_get('https://api.wordpress.org/translations/core/1.0/');
	if (!is_wp_error($response)) {
		$response_body = wp_remote_retrieve_body($response);
		$languages = json_decode($response_body, true)['translations'] ?? [];
		foreach ($languages as $lang) {
			$language_options[] = [
				'label' => $lang['native_name'], 
				'value' => $lang['language']
			];
		}
	}

	// ユーザーオプション
	$users = get_users(
		[
			'role__in' => apply_filters(
				'vk_dynamic_if_block_author_role__in',
				['contributor', 'author', 'editor', 'administrator']
			)
		]
	);
	$user_options = [
		['label' => __('Unspecified', 'vk-dynamic-if-block'), 'value' => 0]
	];

	foreach ($users as $user) {
		$has_posts = false;
		foreach (get_post_types(['public' => true], 'names') 
			as $post_type) {
			if (count_user_posts($user->ID, $post_type, true) > 0) {
				$has_posts = true;
				break;
			}
		}
		if ($has_posts) {
			$user_options[] = [
				'label' => $user->display_name, 
				'value' => $user->ID
			];
		}
	}

	wp_localize_script(
		'vk-dynamic-if-block', 'vkDynamicIfBlockLocalizeData', [
			'postTypeSelectOptions' => $post_type_options,
			'languageSelectOptions' => $language_options,
			'userRoles' => wp_roles()->get_names(),
			'userSelectOptions' => $user_options,
			'currentSiteLanguage' => get_locale()
		]
	);
}
add_action('enqueue_block_editor_assets', 'vk_dynamic_if_block_set_localize_script');
