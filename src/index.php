<?php
/**
 * Dynamic If Block
 * @package vektor-inc/vk-dynamic-if-block
 */

use VektorInc\VK_Helpers\VkHelpers;

function vk_dynamic_if_block_render($attributes, $content) {
    $defaults = ['groups' => [], 'conditions' => [], 'exclusion' => false];
    $attributes = array_merge($defaults, $attributes);

    if (!empty($attributes['groups'])) {
        return vk_dynamic_if_block_render_with_groups($attributes, $content);
    }

    if (empty($attributes['conditions'])) {
        $attributes['conditions'] = vk_dynamic_if_block_migrate_old_attributes($attributes);
    }

    if (empty($attributes['conditions'])) {
        return $content;
    }

    $display = true;
    foreach ($attributes['conditions'] as $index => $condition) {
        $result = vk_dynamic_if_block_evaluate_condition($condition);
        $operator = isset($condition['operator']) ? $condition['operator'] : 'and';
        
        if ($index === 0) {
            $display = $result;
        } else {
            $display = $operator === 'and' ? $display && $result : $display || $result;
        }
    }

    return ($attributes['exclusion'] ? !$display : $display) ? $content : '';
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
    $conditions = [];
    $migrations = [
        'ifPageType' => 'pageType',
        'ifPostType' => 'postType', 
        'ifLanguage' => 'language',
        'postAuthor' => 'postAuthor'
    ];

    foreach ($migrations as $old_key => $new_type) {
        if (isset($attributes[$old_key]) && $attributes[$old_key] !== 'none') {
            $conditions[] = [
                'id' => "migrated_{$new_type}_" . time(),
                'type' => $new_type,
                'values' => [$old_key => [$attributes[$old_key]]]
            ];
        }
    }

    // 特殊なケース
    if (isset($attributes['userRole']) && !empty($attributes['userRole'])) {
        $conditions[] = [
            'id' => 'migrated_user_role_' . time(),
            'type' => 'userRole',
            'values' => ['userRole' => $attributes['userRole']]
        ];
    }

    if (isset($attributes['customFieldName']) && !empty($attributes['customFieldName'])) {
        $values = ['customFieldName' => $attributes['customFieldName']];
        if (isset($attributes['customFieldRule'])) $values['customFieldRule'] = $attributes['customFieldRule'];
        if (isset($attributes['customFieldValue'])) $values['customFieldValue'] = $attributes['customFieldValue'];
        
        $conditions[] = [
            'id' => 'migrated_custom_field_' . time(),
            'type' => 'customField',
            'values' => $values
        ];
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
        $conditions[] = [
            'id' => 'migrated_login_user_' . time(),
            'type' => 'loginUser',
            'values' => ['showOnlyLoginUser' => $attributes['showOnlyLoginUser']]
        ];
    }

    return $conditions;
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

    $current_type = class_exists('VkHelpers') ? VkHelpers::get_post_type_info()['slug'] : get_post_type();
    
    foreach ($post_types as $post_type) {
        if ($post_type === 'none' || $current_type === $post_type) {
            return true;
        }
    }
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
    
    return $rule === 'valueExists' ? ($field_value || $field_value === '0') : $field_value === ($values['customFieldValue'] ?? '');
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
