<?php

/**
 * Dynamic If Block
 *
 * @package vektor-inc/vk-dynamic-if-block
 */

use VektorInc\VK_Helpers\VkHelpers;

/**
 * Block Render function
 *
 * @param  array  $attributes : Block attributes.
 * @param  string $content    : Block inner content.
 * @return string $return : Return HTML.
 */

function vk_dynamic_if_block_render($attributes, $content)
{
	$attributes_default = array(
    'groups'     => array(),
    'conditions' => array(), // 後方互換性のため残す
    'exclusion'  => false,
    );
    $attributes         = array_merge($attributes_default, $attributes);

    // 新しいグループ構造の処理
    if (! empty($attributes['groups'])) {
        return vk_dynamic_if_block_render_with_groups($attributes, $content);
    }

    // 既存のブロックとの互換性のための移行処理
    if (empty($attributes['conditions'])) {
        $attributes['conditions'] = vk_dynamic_if_block_migrate_old_attributes($attributes);
    }

    // 新しい条件配列構造の処理
    if (empty($attributes['conditions'])) {
        // 条件が設定されていない場合は常に表示
        return $content;
    }

    $display = true;
    $previous_result = null;

    foreach ($attributes['conditions'] as $index => $condition) {
        $current_result = vk_dynamic_if_block_evaluate_condition($condition);

        if ($index === 0) {
            // 最初の条件
            $display = $current_result;
        } else {
            // 2番目以降の条件はオペレーターに基づいて結合
            $operator = isset($condition['operator']) ? $condition['operator'] : 'and';

            if ($operator === 'and') {
                $display = $display && $current_result;
            } else { // 'or'
                $display = $display || $current_result;
            }
        }

        $previous_result = $current_result;
    }

    /**
     * Exclusion
     *
     * @since 0.3.0
     */
    if ($attributes['exclusion']) {
        $display = ! $display;
    }

    if ($display) {
        return $content;
    } else {
        return '';
    }
}

/**
 * グループ構造を使用したブロックのレンダリング
 *
 * @param  array  $attributes ブロックの属性
 * @param  string $content    ブロックの内容
 * @return string レンダリング結果
 */
function vk_dynamic_if_block_render_with_groups($attributes, $content)
{
    $groups = $attributes['groups'];
    $groupOperator = isset($attributes['groupOperator']) ? $attributes['groupOperator'] : 'and';
    $exclusion = $attributes['exclusion'];

    if (empty($groups)) {
        // グループが設定されていない場合は常に表示
        return $content;
    }

    $display = true;
    $group_results = array();

    // 各グループを評価
    foreach ($groups as $group_index => $group) {
        $conditions = isset($group['conditions']) ? $group['conditions'] : array();
        $operator = isset($group['operator']) ? $group['operator'] : 'and';

        if (empty($conditions)) {
            // グループに条件がない場合は常にtrue
            $group_results[] = true;
            continue;
        }

        $group_result = true;
        $condition_results = array();

        // グループ内の各条件を評価
        foreach ($conditions as $condition_index => $condition) {
            $condition_result = vk_dynamic_if_block_evaluate_condition($condition);
            $condition_results[] = $condition_result;

            if ($condition_index === 0) {
                // 最初の条件
                $group_result = $condition_result;
            } else {
                // 2番目以降の条件は常にANDで結合
                $group_result = $group_result && $condition_result;
            }
        }

        $group_results[] = $group_result;
    }

    // グループ間はgroupOperatorに基づいて結合
    foreach ($group_results as $group_index => $group_result) {
        if ($group_index === 0) {
            // 最初のグループ
            $display = $group_result;
        } else {
            // 2番目以降のグループはgroupOperatorに基づいて結合
            if ($groupOperator === 'and') {
                $display = $display && $group_result;
            } else { // 'or'
                $display = $display || $group_result;
            }
        }
    }

    /**
     * Exclusion
     *
     * @since 0.3.0
     */
    if ($exclusion) {
        $display = ! $display;
    }

    if ($display) {
        return $content;
    } else {
        return '';
    }
}

/**
 * 古い属性形式から新しい条件配列形式への移行処理
 *
 * @param  array $attributes ブロックの属性
 * @return array 移行後の条件配列
 */
function vk_dynamic_if_block_migrate_old_attributes($attributes)
{
    $conditions = array();

    // ページタイプの移行
    if (isset($attributes['ifPageType']) && $attributes['ifPageType'] !== 'none') {
        $conditions[] = array(
        'id' => 'migrated_page_type_' . time(),
        'type' => 'pageType',
        'values' => array(
        'ifPageType' => array( $attributes['ifPageType'] ),
        ),
        );
    }

    // 投稿タイプの移行
    if (isset($attributes['ifPostType']) && $attributes['ifPostType'] !== 'none') {
        $conditions[] = array(
        'id' => 'migrated_post_type_' . time(),
        'type' => 'postType',
        'values' => array(
        'ifPostType' => array( $attributes['ifPostType'] ),
        ),
        );
    }

    // 言語の移行
    if (isset($attributes['ifLanguage']) && $attributes['ifLanguage'] !== 'none') {
        $conditions[] = array(
        'id' => 'migrated_language_' . time(),
        'type' => 'language',
        'values' => array(
        'ifLanguage' => array( $attributes['ifLanguage'] ),
        ),
        );
    }

    // ユーザー権限の移行
    if (isset($attributes['userRole']) && ! empty($attributes['userRole'])) {
        $conditions[] = array(
        'id' => 'migrated_user_role_' . time(),
        'type' => 'userRole',
        'values' => array(
        'userRole' => $attributes['userRole'],
        ),
        );
    }

    // 投稿者の移行
    if (isset($attributes['postAuthor']) && $attributes['postAuthor'] > 0) {
        $conditions[] = array(
        'id' => 'migrated_post_author_' . time(),
        'type' => 'postAuthor',
        'values' => array(
        'postAuthor' => array( $attributes['postAuthor'] ),
        ),
        );
    }

    // カスタムフィールドの移行
    if (isset($attributes['customFieldName']) && ! empty($attributes['customFieldName'])) {
        $custom_field_values = array(
        'customFieldName' => $attributes['customFieldName'],
        );

        if (isset($attributes['customFieldRule'])) {
            $custom_field_values['customFieldRule'] = $attributes['customFieldRule'];
        }

        if (isset($attributes['customFieldValue'])) {
            $custom_field_values['customFieldValue'] = $attributes['customFieldValue'];
        }

        $conditions[] = array(
        'id' => 'migrated_custom_field_' . time(),
        'type' => 'customField',
        'values' => $custom_field_values,
        );
    }

    // 表示期間の移行
    if (isset($attributes['periodDisplaySetting']) && $attributes['periodDisplaySetting'] !== 'none') {
        $period_values = array(
        'periodDisplaySetting' => $attributes['periodDisplaySetting'],
        );

        if (isset($attributes['periodSpecificationMethod'])) {
            $period_values['periodSpecificationMethod'] = $attributes['periodSpecificationMethod'];
        }

        if (isset($attributes['periodDisplayValue'])) {
            $period_values['periodDisplayValue'] = $attributes['periodDisplayValue'];
        }

        if (isset($attributes['periodReferCustomField'])) {
            $period_values['periodReferCustomField'] = $attributes['periodReferCustomField'];
        }

        $conditions[] = array(
        'id' => 'migrated_period_' . time(),
        'type' => 'period',
        'values' => $period_values,
        );
    }

    // ログインユーザーの移行
    if (isset($attributes['showOnlyLoginUser']) && $attributes['showOnlyLoginUser']) {
        $conditions[] = array(
        'id' => 'migrated_login_user_' . time(),
        'type' => 'loginUser',
        'values' => array(
        'showOnlyLoginUser' => $attributes['showOnlyLoginUser'],
        ),
        );
    }

    return $conditions;
}

/**
 * 個別の条件を評価する関数
 *
 * @param  array $condition 条件の配列
 * @return bool 条件を満たすかどうか
 */
function vk_dynamic_if_block_evaluate_condition($condition)
{
    $type = isset($condition['type']) ? $condition['type'] : '';
    $values = isset($condition['values']) ? $condition['values'] : array();

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
 * ページタイプの条件チェック
 */
function vk_dynamic_if_block_check_page_type($values)
{
    $ifPageType = isset($values['ifPageType']) ? $values['ifPageType'] : array();

    // 配列でない場合は配列に変換（後方互換性のため）
    if (! is_array($ifPageType)) {
        $ifPageType = array( $ifPageType );
    }

    // 空の場合は制限なし
    if (empty($ifPageType)) {
        return true;
    }

    // 複数のページタイプのいずれかに一致するかチェック
    foreach ($ifPageType as $page_type) {
        if ('none' === $page_type) {
            return true;
        }

        if (
            is_front_page() && 'is_front_page' === $page_type
            || is_single() && 'is_single' === $page_type
            || is_page() && 'is_page' === $page_type
            || is_singular() && 'is_singular' === $page_type
            || is_home() && ! is_front_page() && 'is_home' === $page_type
            || is_post_type_archive() && 'is_post_type_archive' === $page_type
            || is_category() && 'is_category' === $page_type
            || is_tag() && 'is_tag' === $page_type
            || is_tax() && 'is_tax' === $page_type
            || is_year() && 'is_year' === $page_type
            || is_month() && 'is_month' === $page_type
            || is_date() && 'is_date' === $page_type
            || is_author() && 'is_author' === $page_type
            || is_search() && 'is_search' === $page_type
            || is_404() && 'is_404' === $page_type
            || is_archive() && 'is_archive' === $page_type
        ) {
            return true;
        }
    }

    return false;
}

/**
 * 投稿タイプの条件チェック
 */
function vk_dynamic_if_block_check_post_type($values)
{
    $ifPostType = isset($values['ifPostType']) ? $values['ifPostType'] : array();

    // 配列でない場合は配列に変換（後方互換性のため）
    if (! is_array($ifPostType)) {
        $ifPostType = array( $ifPostType );
    }

    // 空の場合は制限なし
    if (empty($ifPostType)) {
        return true;
    }

	// vendorファイルの配信・読み込みミス時のフォールバック
    if (class_exists('VkHelpers')) {
		$post_type_info = VkHelpers::get_post_type_info();
		$post_type_slug = $post_type_info['slug'];
	} else {
		$post_type_slug = get_post_type();
	}

    // 複数の投稿タイプのいずれかに一致するかチェック
    foreach ($ifPostType as $post_type) {
        if ('none' === $post_type) {
            return true;
        }

        if ($post_type_slug === $post_type) {
            return true;
        }
    }

    return false;
}

/**
 * 言語の条件チェック
 */
function vk_dynamic_if_block_check_language($values)
{
    $ifLanguage = isset($values['ifLanguage']) ? $values['ifLanguage'] : array();

    // 配列でない場合は配列に変換（後方互換性のため）
    if (! is_array($ifLanguage)) {
        $ifLanguage = array( $ifLanguage );
    }

    // 空の場合は制限なし
    if (empty($ifLanguage)) {
        return true;
    }

    $current_locale = get_locale();

    // 複数の言語のいずれかに一致するかチェック
    foreach ($ifLanguage as $language) {
        if (empty($language) || 'none' === $language) {
            return true;
        }

        if ($language === $current_locale) {
            return true;
        }
    }

    return false;
}

/**
 * ユーザー権限の条件チェック
 */
function vk_dynamic_if_block_check_user_role($values)
{
    $userRole = isset($values['userRole']) ? $values['userRole'] : array();

    if (empty($userRole)) {
        return true;
    }

    $current_user = wp_get_current_user();
    $user_roles   = (array) $current_user->roles;

    if (is_user_logged_in() || $user_roles) {
        foreach ($user_roles as $role) {
            if (in_array($role, $userRole)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * 投稿者の条件チェック
 */
function vk_dynamic_if_block_check_post_author($values)
{
    $postAuthor = isset($values['postAuthor']) ? $values['postAuthor'] : array();

    // 配列でない場合は配列に変換（後方互換性のため）
    if (! is_array($postAuthor)) {
        $postAuthor = array( $postAuthor );
    }

    // 空の場合は制限なし
    if (empty($postAuthor)) {
        return true;
    }

    $author_id = intval(get_post_field('post_author', get_the_ID()));

    // 複数の投稿者のいずれかに一致するかチェック
    foreach ($postAuthor as $author) {
        $author = intval($author);

        if ($author === 0) {
            return true;
        }

        if (is_author($author)) {
            return true;
        } elseif (is_singular() && $author_id === $author) {
            return true;
        }
    }

    return false;
}

/**
 * カスタムフィールドの条件チェック
 */
function vk_dynamic_if_block_check_custom_field($values)
{
    $customFieldName = isset($values['customFieldName']) ? $values['customFieldName'] : '';
    $customFieldRule = isset($values['customFieldRule']) ? $values['customFieldRule'] : 'valueExists';
    $customFieldValue = isset($values['customFieldValue']) ? $values['customFieldValue'] : '';

    if (empty($customFieldName)) {
        return true;
    }

    if (get_the_ID()) {
        $get_value = get_post_meta(get_the_ID(), $customFieldName, true);

        if ('valueExists' === $customFieldRule || empty($customFieldRule)) {
            return $get_value || '0' === $get_value;
        } elseif ('valueEquals' === $customFieldRule) {
            return $get_value === $customFieldValue;
        }
    }

    return false;
}

/**
 * 表示期間の条件チェック
 */
function vk_dynamic_if_block_check_period($values)
{
    $periodDisplaySetting = isset($values['periodDisplaySetting']) ? $values['periodDisplaySetting'] : 'none';
    $periodSpecificationMethod = isset($values['periodSpecificationMethod']) ? $values['periodSpecificationMethod'] : 'direct';
    $periodDisplayValue = isset($values['periodDisplayValue']) ? $values['periodDisplayValue'] : '';
    $periodReferCustomField = isset($values['periodReferCustomField']) ? $values['periodReferCustomField'] : '';

    if ('none' === $periodDisplaySetting) {
        return true;
    }

    if ('deadline' === $periodDisplaySetting) {
        return vk_dynamic_if_block_check_deadline($periodSpecificationMethod, $periodDisplayValue, $periodReferCustomField);
    } elseif ('startline' === $periodDisplaySetting) {
        return vk_dynamic_if_block_check_startline($periodSpecificationMethod, $periodDisplayValue, $periodReferCustomField);
    } elseif ('daysSincePublic' === $periodDisplaySetting) {
        return vk_dynamic_if_block_check_days_since_public($periodSpecificationMethod, $periodDisplayValue, $periodReferCustomField);
    }

    return true;
}

/**
 * 期限の条件チェック
 */
function vk_dynamic_if_block_check_deadline($method, $value, $refer_field)
{
    if ('direct' === $method) {
        if ($value === date('Y-m-d', strtotime($value))) {
            $value .= ' 23:59';
        }
        if ($value !== date('Y-m-d H:i', strtotime($value))) {
            $value = date('Y-m-d H:i', strtotime($value));
        }
        return $value > current_time('Y-m-d H:i');
    } elseif ('referCustomField' === $method) {
        if (! empty($refer_field)) {
            $get_refer_value = get_post_meta(get_the_ID(), $refer_field, true);
            $check_date_ymd = DateTime::createFromFormat('Y-m-d', $get_refer_value);
            $check_date_ymd_hi = DateTime::createFromFormat('Y-m-d H:i', $get_refer_value);
            $check_date_ymd_his = DateTime::createFromFormat('Y-m-d H:i:s', $get_refer_value);

            if ($check_date_ymd || $check_date_ymd_hi || $check_date_ymd_his) {
                if ($check_date_ymd) {
                    $get_refer_value .= ' 23:59:59';
                }
                if ($check_date_ymd_hi) {
                    $get_refer_value .= ':59';
                }
                return $get_refer_value > current_time('Y-m-d H:i:s');
            }
        }
    }
    return true;
}

/**
 * 開始日の条件チェック
 */
function vk_dynamic_if_block_check_startline($method, $value, $refer_field)
{
    if ('direct' === $method) {
        if ($value === date('Y-m-d', strtotime($value))) {
            $value .= ' 00:00';
        }
        if ($value !== date('Y-m-d H:i', strtotime($value))) {
            $value = date('Y-m-d H:i', strtotime($value));
        }
        return $value <= current_time('Y-m-d H:i');
    } elseif ('referCustomField' === $method) {
        if (! empty($refer_field)) {
            $get_refer_value = get_post_meta(get_the_ID(), $refer_field, true);
            $check_date_ymd = DateTime::createFromFormat('Y-m-d', $get_refer_value);
            $check_date_ymd_hi = DateTime::createFromFormat('Y-m-d H:i', $get_refer_value);
            $check_date_ymd_his = DateTime::createFromFormat('Y-m-d H:i:s', $get_refer_value);

            if ($check_date_ymd || $check_date_ymd_hi || $check_date_ymd_his) {
                if ($check_date_ymd) {
                    $get_refer_value .= ' 00:00:00';
                }
                if ($check_date_ymd_hi) {
                    $get_refer_value .= ':00';
                }
                return $get_refer_value <= current_time('Y-m-d H:i:s');
            }
        }
    }
    return true;
}

/**
 * 公開日からの日数の条件チェック
 */
function vk_dynamic_if_block_check_days_since_public($method, $value, $refer_field)
{
    if ('direct' === $method) {
        $days_since_public = intval($value);
        $post_publish_date = get_post_time('U', true, get_the_ID());
        $current_time = current_time('timestamp');
        return $current_time < $post_publish_date + ( $days_since_public * 86400 );
    } elseif ('referCustomField' === $method) {
        if (! empty($refer_field)) {
            $get_refer_value = get_post_meta(get_the_ID(), $refer_field, true);
            if (is_numeric($get_refer_value)) {
                $days_since_public = intval($get_refer_value);
                $post_publish_date = get_post_time('U', true, get_the_ID());
                $current_time = current_time('timestamp');
                return $current_time < $post_publish_date + ( $days_since_public * 86400 );
            }
        }
    }
    return true;
}

/**
 * ログインユーザーの条件チェック
 */
function vk_dynamic_if_block_check_login_user($values)
{
    $showOnlyLoginUser = isset($values['showOnlyLoginUser']) ? $values['showOnlyLoginUser'] : false;

    if (! $showOnlyLoginUser) {
        return true;
    }

    return is_user_logged_in();
}

function vk_dynamic_if_block_register_dynamic()
{
	register_block_type(
		__DIR__ . '/block.json',
		array(
			'render_callback' => 'vk_dynamic_if_block_render',
		)
	);
}
add_action('init', 'vk_dynamic_if_block_register_dynamic');

// Get User Roles
function get_user_roles()
{
	return wp_roles()->get_names();
}

function vk_dynamic_if_block_set_localize_script()
{

	$post_type_select_options = array(
		array(
    'label' => __('No restriction', 'vk-dynamic-if-block'),
			'value' => 'none',
		),
	);

	// Default Post Type.
	$post_types_all = array(
		'post' => 'post',
		'page' => 'page',
	);
	$post_types_all = array_merge(
		$post_types_all,
		get_post_types(
			array(
				'public'   => true,
				'show_ui'  => true,
				'_builtin' => false,
			),
			'names',
			'and'
		)
	);
    foreach ($post_types_all as $post_type) {
        $post_type_object = get_post_type_object($post_type);

		$post_type_select_options[] = array(
			'label' => $post_type_object->labels->singular_name,
			'value' => $post_type_object->name,
		);
	}

	// Languages //////////////////////////////////.
	$language_select_options   = array(
		array(
    'label' => __('Unspecified', 'vk-dynamic-if-block'),
			'value' => '',
		),
		array(
			'label' => 'English (United States)',
			'value' => 'en_US',
		)
	);
	// WordPress.orgのAPIから利用可能な言語リストを取得
    $response = wp_remote_get('https://api.wordpress.org/translations/core/1.0/');
    if (! is_wp_error($response)) {
        $body      = wp_remote_retrieve_body($response);
        $languages = json_decode($body, true)['translations'];

		// 各言語に対してオプション配列を追加
        foreach ($languages as $language) {
			$language_select_options[] = array(
				'label' => $language['native_name'],
				'value' => $language['language'],
			);
		}
	}

	//ユーザー（ ID, Name ）
	$users = get_users();
	$user_select_options = array(
		array(
    'label' => __('Unspecified', 'vk-dynamic-if-block'),
			'value' => 0,
		),
	);
    foreach ($users as $user) {
		$user_select_options[] = array(
			'label' => $user->display_name,
			'value' => $user->ID,
		);
	}

	// The wp_localize_script() function is used to add custom JavaScript data to a script handle.
	wp_localize_script(
		'vk-dynamic-if-block', // Script handle.
		'vk_dynamic_if_block_localize_data', // JS object name.
		array(
			'postTypeSelectOptions' => $post_type_select_options,
			'languageSelectOptions' => $language_select_options,
			'userRoles'             => get_user_roles(),
			'userSelectOptions'     => $user_select_options,
		)
	);
}

add_action('enqueue_block_editor_assets', 'vk_dynamic_if_block_set_localize_script');
