<?php
/**
 * Dynamic If Block
 * @package vektor-inc/vk-dynamic-if-block
 */

use VektorInc\VK_Helpers\VkHelpers;

function vk_dynamic_if_block_render($attributes, $content) {    
    $defaults = ['groups' => [], 'conditions' => [], 'exclusion' => false];
    $attributes = array_merge($defaults, $attributes);

    // 新しいUIで設定されたconditionsが存在しない場合のみ、古い属性の移行処理を実行
    if (empty($attributes['conditions'])) {
        error_log("Dynamic If Block: No conditions found, checking for old attributes...");
        // 古い属性が設定されている場合は移行処理を実行
        $old_attributes = ['customFieldName', 'ifPageType', 'ifPostType', 'ifLanguage', 'userRole', 'postAuthor', 'periodDisplaySetting', 'showOnlyLoginUser'];
        $has_old_attributes = false;
        $found_old_attributes = [];
        
        foreach ($old_attributes as $attr) {
            if (isset($attributes[$attr]) && !empty($attributes[$attr]) && $attributes[$attr] !== 'none') {
                $has_old_attributes = true;
                $found_old_attributes[$attr] = $attributes[$attr];
                error_log("Dynamic If Block: Found old attribute '{$attr}': " . var_export($attributes[$attr], true));
            }
        }
        
        if ($has_old_attributes) {
            error_log("Dynamic If Block: Starting migration for old attributes: " . var_export($found_old_attributes, true));
            $migrated_conditions = vk_dynamic_if_block_migrate_old_attributes($attributes);
            error_log("Dynamic If Block: Migration completed. New conditions: " . var_export($migrated_conditions, true));
            $attributes['conditions'] = $migrated_conditions;
        } else {
            error_log("Dynamic If Block: No old attributes found, no migration needed");
        }
    } else {
        error_log("Dynamic If Block: Using existing conditions, skipping migration: " . var_export($attributes['conditions'], true));
    }

    // conditionsが設定されている場合はconditionsを優先
    if (!empty($attributes['conditions'])) {
        return vk_dynamic_if_block_render_with_conditions($attributes, $content);
    }

    // groupsが設定されている場合はgroupsを使用
    if (!empty($attributes['groups'])) {
        return vk_dynamic_if_block_render_with_groups($attributes, $content);
    }

    // 古い属性からの移行
    $attributes['conditions'] = vk_dynamic_if_block_migrate_old_attributes($attributes);

    if (empty($attributes['conditions'])) {
        return $content;
    }

    return vk_dynamic_if_block_render_with_conditions($attributes, $content);
}

function vk_dynamic_if_block_render_with_groups($attributes, $content) {
    $groups = $attributes['groups'];
    $groupOperator = $attributes['groupOperator'] ?? 'and';
    $exclusion = $attributes['exclusion'];

    if (empty($groups)) {
        return $content;
    }

    $display = true;
    foreach ($groups as $group_index => $group) {
        $conditions = $group['conditions'] ?? [];
        
        if (empty($conditions)) {
            $group_result = true;
        } else {
            $group_result = true;
            foreach ($conditions as $condition) {
                $group_result = $group_result && vk_dynamic_if_block_evaluate_condition($condition);
            }
        }

        if ($group_index === 0) {
            $display = $group_result;
        } else {
            $display = $groupOperator === 'and' ? $display && $group_result : $display || $group_result;
        }
    }

    return ($exclusion ? !$display : $display) ? $content : '';
}

function vk_dynamic_if_block_migrate_old_attributes($attributes) {
    error_log("Dynamic If Block: Starting migration process...");
    $conditions = [];
    $migrations = [
        'ifPageType' => 'pageType',
        'ifPostType' => 'postType', 
        'ifLanguage' => 'language',
        'postAuthor' => 'postAuthor'
    ];

    foreach ($migrations as $old_key => $new_type) {
        if (isset($attributes[$old_key]) && $attributes[$old_key] !== 'none') {
            // 複数の値が設定されている場合は配列として処理
            $values = is_array($attributes[$old_key]) ? $attributes[$old_key] : [$attributes[$old_key]];
            error_log("Dynamic If Block Migration: Converting '{$old_key}' to '{$new_type}' with values: " . var_export($values, true));
            $conditions[] = [
                'id' => "migrated_{$new_type}_" . time(),
                'type' => $new_type,
                'values' => [$old_key => $values]
            ];
            error_log("Dynamic If Block Migration: Created condition for '{$new_type}' with ID: migrated_{$new_type}_" . time());
        } else {
            error_log("Dynamic If Block Migration: Skipping '{$old_key}' - not set or 'none'");
        }
    }

    // 特殊なケース
    if (isset($attributes['userRole']) && !empty($attributes['userRole'])) {
        // 複数の値が設定されている場合は配列として処理
        $values = is_array($attributes['userRole']) ? $attributes['userRole'] : [$attributes['userRole']];
        error_log("Dynamic If Block Migration: Converting 'userRole' with values: " . var_export($values, true));
        $conditions[] = [
            'id' => 'migrated_user_role_' . time(),
            'type' => 'userRole',
            'values' => ['userRole' => $values]
        ];
        error_log("Dynamic If Block Migration: Created condition for 'userRole' with ID: migrated_user_role_" . time());
    } else {
        error_log("Dynamic If Block Migration: Skipping 'userRole' - not set or empty");
    }

    if (isset($attributes['customFieldName']) && !empty($attributes['customFieldName'])) {
        $values = ['customFieldName' => $attributes['customFieldName']];
        if (isset($attributes['customFieldRule'])) $values['customFieldRule'] = $attributes['customFieldRule'];
        if (isset($attributes['customFieldValue'])) $values['customFieldValue'] = $attributes['customFieldValue'];
        
        error_log("Dynamic If Block Migration: Converting 'customField' with values: " . var_export($values, true));
        
        $conditions[] = [
            'id' => 'migrated_custom_field_' . time(),
            'type' => 'customField',
            'values' => $values
        ];
        error_log("Dynamic If Block Migration: Created condition for 'customField' with ID: migrated_custom_field_" . time());
    } else {
        error_log("Dynamic If Block Migration: Skipping 'customField' - not set or empty");
    }

    if (isset($attributes['periodDisplaySetting']) && $attributes['periodDisplaySetting'] !== 'none') {
        $values = ['periodDisplaySetting' => $attributes['periodDisplaySetting']];
        $period_keys = ['periodSpecificationMethod', 'periodDisplayValue', 'periodReferCustomField'];
        foreach ($period_keys as $key) {
            if (isset($attributes[$key])) $values[$key] = $attributes[$key];
        }
        
        $conditions[] = [
            'id' => 'migrated_period_' . time(),
            'type' => 'period',
            'values' => $values
        ];
    }

    if (isset($attributes['showOnlyLoginUser']) && $attributes['showOnlyLoginUser']) {
        error_log("Dynamic If Block Migration: Converting 'showOnlyLoginUser' with value: " . var_export($attributes['showOnlyLoginUser'], true));
        $conditions[] = [
            'id' => 'migrated_login_user_' . time(),
            'type' => 'loginUser',
            'values' => ['showOnlyLoginUser' => $attributes['showOnlyLoginUser']]
        ];
        error_log("Dynamic If Block Migration: Created condition for 'loginUser' with ID: migrated_login_user_" . time());
    } else {
        error_log("Dynamic If Block Migration: Skipping 'showOnlyLoginUser' - not set or false");
    }

    error_log("Dynamic If Block Migration: Migration process completed. Total conditions created: " . count($conditions));
    return $conditions;
}

function vk_dynamic_if_block_render_with_conditions($attributes, $content) {
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
                $nested_result = vk_dynamic_if_block_evaluate_condition($nested_condition);
                $group_result = $group_result && $nested_result;
            }
            $result = $group_result;
        } else {
            $result = vk_dynamic_if_block_evaluate_condition($condition);
        }
        
        if ($index === 0) {
            $display = $result;
        } else {
            $display = $conditionOperator === 'and' ? $display && $result : $display || $result;
        }
    }

    $final_result = ($attributes['exclusion'] ? !$display : $display);
    
    return $final_result ? $content : '';
}

function vk_dynamic_if_block_evaluate_condition($condition) {
    $type = $condition['type'] ?? '';
    $values = $condition['values'] ?? [];

    $checkers = [
        'pageType' => 'vk_dynamic_if_block_check_page_type',
        'postType' => 'vk_dynamic_if_block_check_post_type',
        'language' => 'vk_dynamic_if_block_check_language',
        'userRole' => 'vk_dynamic_if_block_check_user_role',
        'postAuthor' => 'vk_dynamic_if_block_check_post_author',
        'customField' => 'vk_dynamic_if_block_check_custom_field',
        'period' => 'vk_dynamic_if_block_check_period',
        'loginUser' => 'vk_dynamic_if_block_check_login_user'
    ];

    return isset($checkers[$type]) ? $checkers[$type]($values) : true;
}

function vk_dynamic_if_block_check_page_type($values) {
    $page_types = (array)($values['ifPageType'] ?? []);
    if (empty($page_types)) return true;

    $page_checks = [
        'is_front_page' => is_front_page(),
        'is_single' => is_single(),
        'is_page' => is_page(),
        'is_singular' => is_singular(),
        'is_home' => is_home() && !is_front_page(),
        'is_post_type_archive' => is_post_type_archive(),
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

    foreach ($page_types as $page_type) {
        if ($page_type === 'none' || ($page_checks[$page_type] ?? false)) {
            return true;
        }
    }
    return false;
}

function vk_dynamic_if_block_check_post_type($values) {
    $post_types = (array)($values['ifPostType'] ?? []);
    if (empty($post_types)) return true;

    // デバッグ用
    error_log("Dynamic If Block Post Type Debug - Expected: " . var_export($post_types, true));

    // VkHelpersを使用してより確実に投稿タイプを取得
    if (class_exists('VkHelpers')) {
        $post_type_info = VkHelpers::get_post_type_info();
        $current_type = $post_type_info['slug'] ?? get_post_type();
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
    
    error_log("Dynamic If Block Post Type Debug - Current: " . var_export($current_type, true));
    
    foreach ($post_types as $post_type) {
        if ($post_type === 'none' || $current_type === $post_type) {
            error_log("Dynamic If Block Post Type Debug - Match found: {$post_type}");
            return true;
        }
    }
    
    error_log("Dynamic If Block Post Type Debug - No match found");
    return false;
}

function vk_dynamic_if_block_check_language($values) {
    $languages = (array)($values['ifLanguage'] ?? []);
    if (empty($languages)) return true;

    $current_locale = get_locale();
    foreach ($languages as $language) {
        if (empty($language) || $language === 'none' || $language === $current_locale) {
            return true;
        }
    }
    return false;
}

function vk_dynamic_if_block_check_user_role($values) {
    $user_roles = $values['userRole'] ?? [];
    if (empty($user_roles)) return true;

    $current_user = wp_get_current_user();
    return is_user_logged_in() && array_intersect($current_user->roles, $user_roles);
}

function vk_dynamic_if_block_check_post_author($values) {
    $authors = (array)($values['postAuthor'] ?? []);
    if (empty($authors)) return true;

    $author_id = (int)get_post_field('post_author', get_the_ID());
    foreach ($authors as $author) {
        $author = (int)$author;
        if ($author === 0 || is_author($author) || (is_singular() && $author_id === $author)) {
            return true;
        }
    }
    return false;
}

function vk_dynamic_if_block_check_custom_field($values) {
    $field_name = $values['customFieldName'] ?? '';
    if (empty($field_name) || !get_the_ID()) return true;

    $field_value = get_post_meta(get_the_ID(), $field_name, true);
    $rule = $values['customFieldRule'] ?? 'valueExists';
    
    if ($rule === 'valueExists') {
        // PHPの!empty()と同じ動作にする
        return !empty($field_value) || $field_value === '0' || $field_value === 0;
    } else {
        return $field_value === ($values['customFieldValue'] ?? '');
    }
}

function vk_dynamic_if_block_check_period($values) {
    $setting = $values['periodDisplaySetting'] ?? 'none';
    if ($setting === 'none') return true;

    $method = $values['periodSpecificationMethod'] ?? 'direct';
    $value = $values['periodDisplayValue'] ?? '';
    $refer_field = $values['periodReferCustomField'] ?? '';

    $checkers = [
        'deadline' => 'vk_dynamic_if_block_check_deadline',
        'startline' => 'vk_dynamic_if_block_check_startline',
        'daysSincePublic' => 'vk_dynamic_if_block_check_days_since_public'
    ];

    return isset($checkers[$setting]) ? $checkers[$setting]($method, $value, $refer_field) : true;
}

function vk_dynamic_if_block_check_deadline($method, $value, $refer_field) {
    if ($method === 'direct') {
        if ($value === date('Y-m-d', strtotime($value))) $value .= ' 23:59';
        if ($value !== date('Y-m-d H:i', strtotime($value))) $value = date('Y-m-d H:i', strtotime($value));
        return $value > current_time('Y-m-d H:i');
    }
    
    if ($method === 'referCustomField' && !empty($refer_field)) {
        $refer_value = get_post_meta(get_the_ID(), $refer_field, true);
        $formats = ['Y-m-d', 'Y-m-d H:i', 'Y-m-d H:i:s'];
        
        foreach ($formats as $format) {
            if (DateTime::createFromFormat($format, $refer_value)) {
                if ($format === 'Y-m-d') $refer_value .= ' 23:59:59';
                elseif ($format === 'Y-m-d H:i') $refer_value .= ':59';
                return $refer_value > current_time('Y-m-d H:i:s');
            }
        }
    }
    return true;
}

function vk_dynamic_if_block_check_startline($method, $value, $refer_field) {
    if ($method === 'direct') {
        if ($value === date('Y-m-d', strtotime($value))) $value .= ' 00:00';
        if ($value !== date('Y-m-d H:i', strtotime($value))) $value = date('Y-m-d H:i', strtotime($value));
        return $value <= current_time('Y-m-d H:i');
    }
    
    if ($method === 'referCustomField' && !empty($refer_field)) {
        $refer_value = get_post_meta(get_the_ID(), $refer_field, true);
        $formats = ['Y-m-d', 'Y-m-d H:i', 'Y-m-d H:i:s'];
        
        foreach ($formats as $format) {
            if (DateTime::createFromFormat($format, $refer_value)) {
                if ($format === 'Y-m-d') $refer_value .= ' 00:00:00';
                elseif ($format === 'Y-m-d H:i') $refer_value .= ':00';
                return $refer_value <= current_time('Y-m-d H:i:s');
            }
        }
    }
    return true;
}

function vk_dynamic_if_block_check_days_since_public($method, $value, $refer_field) {
    if ($method === 'direct') {
        $days = (int)$value;
        $publish_date = get_post_time('U', true, get_the_ID());
        return current_time('timestamp') < $publish_date + ($days * 86400);
    }
    
    if ($method === 'referCustomField' && !empty($refer_field)) {
        $refer_value = get_post_meta(get_the_ID(), $refer_field, true);
        if (is_numeric($refer_value)) {
            $days = (int)$refer_value;
            $publish_date = get_post_time('U', true, get_the_ID());
            return current_time('timestamp') < $publish_date + ($days * 86400);
        }
    }
    return true;
}

function vk_dynamic_if_block_check_login_user($values) {
    return !($values['showOnlyLoginUser'] ?? false) || is_user_logged_in();
}

function vk_dynamic_if_block_register_dynamic() {
    register_block_type(__DIR__ . '/block.json', ['render_callback' => 'vk_dynamic_if_block_render']);
}
add_action('init', 'vk_dynamic_if_block_register_dynamic');

function vk_dynamic_if_block_set_localize_script() {
    // 投稿タイプオプション
    $post_types = array_merge(['post', 'page'], get_post_types(['public' => true, 'show_ui' => true, '_builtin' => false], 'names'));
    $post_type_options = [['label' => __('No restriction', 'vk-dynamic-if-block'), 'value' => 'none']];
    foreach ($post_types as $type) {
        $obj = get_post_type_object($type);
        $post_type_options[] = ['label' => $obj->labels->singular_name, 'value' => $obj->name];
    }

    // 言語オプション
    $language_options = [
        ['label' => __('Unspecified', 'vk-dynamic-if-block'), 'value' => ''],
        ['label' => 'English (United States)', 'value' => 'en_US']
    ];
    
    $response = wp_remote_get('https://api.wordpress.org/translations/core/1.0/');
    if (!is_wp_error($response)) {
        $languages = json_decode(wp_remote_retrieve_body($response), true)['translations'] ?? [];
        foreach ($languages as $lang) {
            $language_options[] = ['label' => $lang['native_name'], 'value' => $lang['language']];
        }
    }

    // ユーザーオプション
    $users = get_users(['role__in' => apply_filters('vk_dynamic_if_block_author_role__in', ['contributor', 'author', 'editor', 'administrator'])]);
    $user_options = [['label' => __('Unspecified', 'vk-dynamic-if-block'), 'value' => 0]];
    
    foreach ($users as $user) {
        $has_posts = false;
        foreach (get_post_types(['public' => true], 'names') as $post_type) {
            if (count_user_posts($user->ID, $post_type, true) > 0) {
                $has_posts = true;
                break;
            }
        }
        if ($has_posts) {
            $user_options[] = ['label' => $user->display_name, 'value' => $user->ID];
        }
    }

    wp_localize_script('vk-dynamic-if-block', 'vk_dynamic_if_block_localize_data', [
        'postTypeSelectOptions' => $post_type_options,
        'languageSelectOptions' => $language_options,
        'userRoles' => wp_roles()->get_names(),
        'userSelectOptions' => $user_options,
        'currentSiteLanguage' => get_locale()
    ]);
}
add_action('enqueue_block_editor_assets', 'vk_dynamic_if_block_set_localize_script');
