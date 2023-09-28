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
 * @param array  $attributes : Block attributes.
 * @param string $content : Block inner content.
 * @return string $return : Return HTML.
 */
function vk_dynamic_if_block_render( $attributes, $content ) {
	$attributes_default = array(
		'conditions' => array(
			'properties' => array(
				'conditionPageType' => array(
					'enable' => false,
					'properties' => array(
						'ifPageType' => 'none'
					)
				),
				'conditionPostType' => array(
					'enable' => false,
					'properties' => array(
						'ifPostType' => 'none'
					)
				),
				'conditionUserRole' => array(
					'enable' => false,
					'properties' => array(
						'userRole' => array()
					)
				),
				'conditionCustomField' => array(
					'enable' => false,
					'properties' => array(
						'customFieldName' => '',
						'customFieldRule' => 'valueExists',
						'customFieldValue' => ''
					)
				),
				'conditionPeriodDisplay' => array(
					'enable' => false,
					'properties' => array(
						'periodDisplaySetting' => 'none',
						'periodSpecificationMethod' => 'direct',
						'periodDisplayValue' => '',
						'periodReferCustomField' => ''
					)
				),
			),
		),
		'exclusion' => false
	);

	$attributes = array_merge($attributes_default, $attributes);

	// var_dump($attributes);


	$display = false;

	// Page Type Condition Check //////////////////////////////////.

	$display_by_page_type = false;

	$ifPageType = $attributes['conditions']['conditionPageType']['properties']['ifPageType'];

	if (
		is_front_page() && 'is_front_page' === $ifPageType ||
		is_single() && 'is_single' === $ifPageType ||
		is_page() && 'is_page' === $ifPageType ||
		is_singular() && 'is_singular' === $ifPageType ||
		is_home() && ! is_front_page() && 'is_home' === $ifPageType ||
		is_post_type_archive() && 'is_post_type_archive' === $ifPageType ||
		is_category() && 'is_category' === $ifPageType ||
		is_tag() && 'is_tag' === $ifPageType ||
		is_tax() && 'is_tax' === $ifPageType ||
		is_year() && 'is_year' === $ifPageType ||
		is_month() && 'is_month' === $ifPageType ||
		is_date() && 'is_date' === $ifPageType ||
		is_author() && 'is_author' === $ifPageType ||
		is_search() && 'is_search' === $ifPageType ||
		is_404() && 'is_404' === $ifPageType ||
		is_archive() && 'is_archive' === $ifPageType ||
		'none' === $ifPageType
	) {
		$display_by_page_type = true;
	}

	// Post Type Condition Check //////////////////////////////////.

	$display_by_post_type = false;

	// vendorファイルの配信・読み込みミス時のフォールバック
	// Fallback for vendor files failed to deliver or load.
	if ( class_exists( 'VkHelpers' ) ) {
		$post_type_info = VkHelpers::get_post_type_info();
		$post_type_slug = $post_type_info['slug'];
	} else {
		$post_type_slug = get_post_type();
	}

	$ifPostType = $attributes['conditions']['conditionPostType']['properties']['ifPostType'];

	if ( 'none' === $ifPostType ) {
		$display_by_post_type = true;
	} elseif ( $post_type_slug === $ifPostType ) {
		$display_by_post_type = true;
	} else {
		$display_by_post_type = false;
	}

	// User Role Condition Check //////////////////////////////////.

	$display_by_user_role = false;

	$userRole = $attributes['conditions']['conditionUserRole']['properties']['userRole'];

	// PHPUnit用のユーザーロール情報がある場合はそれを設定.
	if ( ! empty( $attributes['test_user_roles'] ) ) {
		$user_roles = $attributes['test_user_roles'];
	} else {
		$current_user = wp_get_current_user();
		$user_roles   = (array) $current_user->roles;
	}

	if ( ! isset( $userRole ) || empty( $userRole ) ) {
		$display_by_user_role = true;
	} else {
		if ( is_user_logged_in() || $user_roles ) {
			// Check if any of the user's roles match the selected roles.
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $userRole ) ) {
					$display_by_user_role = true;
					break;
				}
			}
		} else {
			$display_by_user_role = false;
		}
	}

	// Custom Field Condition Check //////////////////////////////////.

	$display_by_custom_field = false;

	$conditionCustomField = $attributes['conditions']['conditionCustomField']['properties'];

	if ( ! $conditionCustomField['customFieldName'] ) {
		$display_by_custom_field = true;
	} elseif ( $conditionCustomField['customFieldName'] ) {
		if ( get_the_ID() ) {
			$get_value = get_post_meta( get_the_ID(), $conditionCustomField['customFieldName'], true );
			if ( 'valueExists' === $conditionCustomField['customFieldRule'] || empty( $conditionCustomField['customFieldRule'] ) ) {
				if ( $get_value || '0' === $get_value ) {
					$display_by_custom_field = true;
				} else {
					$display_by_custom_field = false;
				}
			} elseif ( 'valueEquals' === $conditionCustomField['customFieldRule'] ) {
				if ( $get_value === $conditionCustomField['customFieldValue'] ) {
					$display_by_custom_field = true;
				} else {
					$display_by_custom_field = false;
				}
			}
		}
	}

	// Display period Check //////////////////////////////////.

	$display_by_period = false;

	$conditionPeriodDisplay = $attributes['conditions']['conditionPeriodDisplay']['properties'];

	$periodDisplaySetting = $conditionPeriodDisplay['periodDisplaySetting'];
	$periodSpecificationMethod = $conditionPeriodDisplay['periodSpecificationMethod'];
	$periodDisplayValue = $conditionPeriodDisplay['periodDisplayValue'];
	$periodReferCustomField = $conditionPeriodDisplay['periodReferCustomField'];

	if ( 'none' === $periodDisplaySetting ) {
		$display_by_period = true;
	} elseif ( 'deadline' === $periodDisplaySetting ) {
		if ( 'direct' === $periodSpecificationMethod ) {

			// Adjust time if no specific time is set
			if ($periodDisplayValue === date('Y-m-d', strtotime($periodDisplayValue))) {
				$periodDisplayValue .= ' 23:59';
			}

			// Ensure the date format is consistent
			if ($periodDisplayValue !== date('Y-m-d H:i', strtotime($periodDisplayValue))) {
				$periodDisplayValue = date('Y-m-d H:i', strtotime($periodDisplayValue));
			}

			if ($periodDisplayValue > current_time('Y-m-d H:i')) {
				$display_by_period = true;
			} else {
				$display_by_period = false;
			}
		} elseif ('referCustomField' === $periodSpecificationMethod) {
			$get_refer_value = get_post_meta(get_the_ID(), $periodReferCustomField, true);
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
				if ($get_refer_value > current_time('Y-m-d H:i:s')) {
					$display_by_period = true;
				} else {
					$display_by_period = false;
				}
			} else {
				$display_by_period = true;
			}
		}
	} elseif ('startline' === $periodDisplaySetting) {
		if ('direct' === $periodSpecificationMethod) {
			if ($periodDisplayValue === date('Y-m-d', strtotime($periodDisplayValue))) {
				$periodDisplayValue .= ' 00:00';
			}
			if ($periodDisplayValue !== date('Y-m-d H:i', strtotime($periodDisplayValue))) {
				$periodDisplayValue = date('Y-m-d H:i', strtotime($periodDisplayValue));
			}
			if ($periodDisplayValue <= current_time('Y-m-d H:i')) {
				$display_by_period = true;
			} else {
				$display_by_period = false;
			}
		} elseif ('referCustomField' === $periodSpecificationMethod) {
			$get_refer_value = get_post_meta(get_the_ID(), $periodReferCustomField, true);
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
				if ($get_refer_value <= current_time('Y-m-d H:i:s')) {
					$display_by_period = true;
				} else {
					$display_by_period = false;
				}
			} else {
				$display_by_period = true;
			}
		}
	} elseif ('daysSincePublic' === $periodDisplaySetting) {
		if ('direct' === $periodSpecificationMethod) {
			$days_since_public = intval($periodDisplayValue);
			$post_publish_date = get_post_time('U', true, get_the_ID());
			$current_time = current_time('timestamp');
			if ($current_time >= $post_publish_date + ($days_since_public * 86400)) {
				$display_by_period = false;
			} else {
				$display_by_period = true;
			}
		} elseif ('referCustomField' === $periodSpecificationMethod) {
			$get_refer_value = get_post_meta(get_the_ID(), $periodReferCustomField, true);
			if (is_numeric($get_refer_value)) {
				$days_since_public = intval($get_refer_value);
				$post_publish_date = get_post_time('U', true, get_the_ID());
				$current_time = current_time('timestamp');
				if ($current_time >= $post_publish_date + ($days_since_public * 86400)) {
					$display_by_period = false;
				} else {
					$display_by_period = true;
				}
			} else {
				$display_by_period = true;
			}
		}
	}

	// Merge Condition Check //////////////////////////////////.

	if ( $display_by_post_type && $display_by_page_type && $display_by_custom_field && $display_by_user_role && $display_by_period ) {
		$display = true;
	}

	/**
	 * Exclusion
	 *
	 * @since 0.3.0
	 */
	if ( $attributes['exclusion'] ) {
		$display = ! $display;
	}

	if ( $display ) {
		return $content;
	} else {
		return '';
	}

}

function vk_dynamic_if_block_register_dynamic() {
	register_block_type(
		__DIR__ . '/block.json',
		array(
			'render_callback' => 'vk_dynamic_if_block_render',
		)
	);
}
add_action( 'init', 'vk_dynamic_if_block_register_dynamic' );

// Get User Roles
function get_user_roles() {
	return wp_roles()->get_names();
}

function vk_dynamic_if_block_set_localize_script() {

	$post_type_select_options = array(
		array(
			'label' => __( 'No restriction', 'vk-dynamic-if-block' ),
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
	foreach ( $post_types_all as $post_type ) {

		$post_type_object = get_post_type_object( $post_type );

		$post_type_select_options[] = array(
			'label' => $post_type_object->labels->singular_name,
			'value' => $post_type_object->name,
		);
	}

	// The wp_localize_script() function is used to add custom JavaScript data to a script handle.
	wp_localize_script(
		'vk-dynamic-if-block', // Script handle.
		'vk_dynamic_if_block_localize_data', // JS object name.
		array(
			'postTypeSelectOptions' => $post_type_select_options,
			'userRoles'             => get_user_roles(),
		)
	);
}

add_action( 'enqueue_block_editor_assets', 'vk_dynamic_if_block_set_localize_script' );
