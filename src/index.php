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

	$display = false;

	// 新しい形式（conditions配列）の処理
	if ( ! empty( $attributes['conditions'] ) && is_array( $attributes['conditions'] ) ) {
		$display = vk_dynamic_if_block_evaluate_conditions( $attributes['conditions'] );
	} else {
		// 古い形式の処理（後方互換性のため）
		$display = vk_dynamic_if_block_evaluate_old_attributes( $attributes );
	}

	// Exclusion処理
	if ( $attributes['exclusion'] ) {
		$display = ! $display;
	}

	if ( $display ) {
		return $content;
	} else {
		return '';
	}
}

/**
 * 新しいconditions配列形式を評価
 */
function vk_dynamic_if_block_evaluate_conditions( $conditions ) {
	$display = true;

	foreach ( $conditions as $condition ) {
		if ( ! isset( $condition['type'] ) ) {
			continue;
		}

		$condition_result = false;

		switch ( $condition['type'] ) {
			case 'pageType':
				$condition_result = vk_dynamic_if_block_evaluate_page_type( $condition );
				break;
			case 'postType':
				$condition_result = vk_dynamic_if_block_evaluate_post_type( $condition );
				break;
			case 'customField':
				$condition_result = vk_dynamic_if_block_evaluate_custom_field( $condition );
				break;
			case 'userRole':
				$condition_result = vk_dynamic_if_block_evaluate_user_role( $condition );
				break;
			case 'language':
				$condition_result = vk_dynamic_if_block_evaluate_language( $condition );
				break;
			case 'author':
				$condition_result = vk_dynamic_if_block_evaluate_author( $condition );
				break;
			case 'period':
				$condition_result = vk_dynamic_if_block_evaluate_period( $condition );
				break;
			case 'loginUser':
				$condition_result = vk_dynamic_if_block_evaluate_login_user( $condition );
				break;
		}

		// 条件がfalseの場合、全体もfalse
		if ( ! $condition_result ) {
			$display = false;
			break;
		}
	}

	return $display;
}

/**
 * ページタイプ条件を評価
 */
function vk_dynamic_if_block_evaluate_page_type( $condition ) {
	if ( ! isset( $condition['values']['ifPageType'] ) ) {
		return true;
	}

	$page_type = $condition['values']['ifPageType'];

	if ( 'none' === $page_type ) {
		return true;
	}

	switch ( $page_type ) {
		case 'is_front_page':
			return is_front_page();
		case 'is_single':
			return is_single();
		case 'is_page':
			return is_page();
		case 'is_singular':
			return is_singular();
		case 'is_home':
			return is_home() && ! is_front_page();
		case 'is_post_type_archive':
			return is_post_type_archive() && ! is_year() && ! is_month() && ! is_date();
		case 'is_category':
			return is_category();
		case 'is_tag':
			return is_tag();
		case 'is_tax':
			return is_tax();
		case 'is_year':
			return is_year();
		case 'is_month':
			return is_month();
		case 'is_date':
			return is_date();
		case 'is_author':
			return is_author();
		case 'is_search':
			return is_search();
		case 'is_404':
			return is_404();
		case 'is_archive':
			return is_archive();
		default:
			return false;
	}
}

/**
 * 投稿タイプ条件を評価
 */
function vk_dynamic_if_block_evaluate_post_type( $condition ) {
	if ( ! isset( $condition['values']['ifPostType'] ) ) {
		return true;
	}

	$post_type = $condition['values']['ifPostType'];

	if ( 'none' === $post_type ) {
		return true;
	}

	// vendorファイルの配信・読み込みミス時のフォールバック
	if ( class_exists( 'VkHelpers' ) ) {
		$post_type_info = VkHelpers::get_post_type_info();
		$current_post_type = $post_type_info['slug'];
	} else {
		$current_post_type = get_post_type();
	}

	return $current_post_type === $post_type;
}

/**
 * カスタムフィールド条件を評価
 */
function vk_dynamic_if_block_evaluate_custom_field( $condition ) {
	if ( ! isset( $condition['values']['customFieldName'] ) || empty( $condition['values']['customFieldName'] ) ) {
		return true;
	}

	$custom_field_name = $condition['values']['customFieldName'];
	$custom_field_rule = isset( $condition['values']['customFieldRule'] ) ? $condition['values']['customFieldRule'] : 'valueExists';
	$custom_field_value = isset( $condition['values']['customFieldValue'] ) ? $condition['values']['customFieldValue'] : '';

	if ( ! get_the_ID() ) {
		return false;
	}

	$get_value = get_post_meta( get_the_ID(), $custom_field_name, true );

	if ( 'valueExists' === $custom_field_rule || empty( $custom_field_rule ) ) {
		return $get_value || '0' === $get_value;
	} elseif ( 'valueEquals' === $custom_field_rule ) {
		return $get_value === $custom_field_value;
	}

	return false;
}

/**
 * ユーザーロール条件を評価
 */
function vk_dynamic_if_block_evaluate_user_role( $condition ) {
	if ( ! isset( $condition['values']['userRole'] ) || empty( $condition['values']['userRole'] ) ) {
		return true;
	}

	$user_roles = $condition['values']['userRole'];
	$current_user = wp_get_current_user();
	$user_roles_array = (array) $current_user->roles;

	if ( ! is_user_logged_in() && empty( $user_roles_array ) ) {
		return false;
	}

	foreach ( $user_roles_array as $role ) {
		if ( in_array( $role, $user_roles ) ) {
			return true;
		}
	}

	return false;
}

/**
 * 言語条件を評価
 */
function vk_dynamic_if_block_evaluate_language( $condition ) {
	if ( ! isset( $condition['values']['ifLanguage'] ) || empty( $condition['values']['ifLanguage'] ) || 'none' === $condition['values']['ifLanguage'] ) {
		return true;
	}

	return $condition['values']['ifLanguage'] === get_locale();
}

/**
 * 著者条件を評価
 */
function vk_dynamic_if_block_evaluate_author( $condition ) {
	if ( ! isset( $condition['values']['postAuthor'] ) || empty( $condition['values']['postAuthor'] ) ) {
		return true;
	}

	$author_id = intval( get_post_field( 'post_author', get_the_ID() ) );

	if ( 'is_author' === $condition['values']['ifPageType'] && is_author( $condition['values']['postAuthor'] ) ) {
		return true;
	} elseif ( 'is_author' !== $condition['values']['ifPageType'] && is_singular() && $author_id === $condition['values']['postAuthor'] ) {
		return true;
	}

	return false;
}

/**
 * 期間条件を評価
 */
function vk_dynamic_if_block_evaluate_period( $condition ) {
	// 期間条件の実装は複雑なため、現在は常にtrueを返す
	// 必要に応じて実装を追加
	return true;
}

/**
 * ログインユーザー条件を評価
 */
function vk_dynamic_if_block_evaluate_login_user( $condition ) {
	if ( ! isset( $condition['values']['showOnlyLoginUser'] ) || empty( $condition['values']['showOnlyLoginUser'] ) ) {
		return true;
	}

	return is_user_logged_in();
}

/**
 * 古い属性形式を評価（後方互換性）
 */
function vk_dynamic_if_block_evaluate_old_attributes( $attributes ) {
	$display = false;

	// Page Type Condition Check
	$display_by_page_type = false;

	if (
		is_front_page() && 'is_front_page' === $attributes['ifPageType'] ||
		is_single() && 'is_single' === $attributes['ifPageType'] ||
		is_page() && 'is_page' === $attributes['ifPageType'] ||
		is_singular() && 'is_singular' === $attributes['ifPageType'] ||
		is_home() && ! is_front_page() && 'is_home' === $attributes['ifPageType'] ||
		is_post_type_archive() && ! is_year() && ! is_month() && ! is_date() && 'is_post_type_archive' === $attributes['ifPageType'] ||
		is_category() && 'is_category' === $attributes['ifPageType'] ||
		is_tag() && 'is_tag' === $attributes['ifPageType'] ||
		is_tax() && 'is_tax' === $attributes['ifPageType'] ||
		is_year() && 'is_year' === $attributes['ifPageType'] ||
		is_month() && 'is_month' === $attributes['ifPageType'] ||
		is_date() && 'is_date' === $attributes['ifPageType'] ||
		is_author() && 'is_author' === $attributes['ifPageType'] ||
		is_search() && 'is_search' === $attributes['ifPageType'] ||
		is_404() && 'is_404' === $attributes['ifPageType'] ||
		is_archive() && 'is_archive' === $attributes['ifPageType'] ||
		'none' === $attributes['ifPageType']
	) {
		$display_by_page_type = true;
	}

	// Author Condition Check
	$display_by_author = false;
	$author_id         = intval( get_post_field( 'post_author', get_the_ID() ) );
	if ( empty( $attributes['postAuthor'] ) ) {
		$display_by_author = true;
	} elseif ( ! empty( $attributes['postAuthor'] ) && 'is_author' === $attributes['ifPageType'] && is_author( $attributes['postAuthor'] ) ) {
		$display_by_author = true;
	} elseif ( ! empty( $attributes['postAuthor'] ) && 'is_author' !== $attributes['ifPageType'] && is_singular() && $author_id === $attributes['postAuthor'] ) {
		$display_by_author = true;
	}

	// Post Type Condition Check
	$display_by_post_type = false;

	// vendorファイルの配信・読み込みミス時のフォールバック
	if ( class_exists( 'VkHelpers' ) ) {
		$post_type_info = VkHelpers::get_post_type_info();
		$post_type_slug = $post_type_info['slug'];
	} else {
		$post_type_slug = get_post_type();
	}

	if ( 'none' === $attributes['ifPostType'] ) {
		$display_by_post_type = true;
	} elseif ( $post_type_slug === $attributes['ifPostType'] ) {
		$display_by_post_type = true;
	} else {
		$display_by_post_type = false;
	}

	// Language Condition Check
	$display_by_language = false;
	if (  empty( $attributes['ifLanguage'] ) || 'none' === $attributes['ifLanguage'] || $attributes['ifLanguage'] === get_locale()  ){
		$display_by_language = true;
	}

	// User Role Condition Check
	$display_by_user_role = false;

	// PHPUnit用のユーザーロール情報がある場合はそれを設定.
	if ( ! empty( $attributes['test_user_roles'] ) ) {
		$user_roles = $attributes['test_user_roles'];
	} else {
		$current_user = wp_get_current_user();
		$user_roles   = (array) $current_user->roles;
	}

	if ( ! isset( $attributes['userRole'] ) || empty( $attributes['userRole'] ) ) {
		$display_by_user_role = true;
	} else {
		if ( is_user_logged_in() || $user_roles ) {

			// Check if any of the user's roles match the selected roles.
			foreach ( $user_roles as $role ) {
				if ( in_array( $role, $attributes['userRole'] ) ) {
					$display_by_user_role = true;
					break;
				}
			}
		} else {
			$display_by_user_role = false;
		}
	}

	// Custom Field Condition Check
	$display_by_custom_field = false;

	if ( ! $attributes['customFieldName'] ) {
		$display_by_custom_field = true;
	} elseif ( $attributes['customFieldName'] ) {

		if ( get_the_ID() ) {
			$get_value = get_post_meta( get_the_ID(), $attributes['customFieldName'], true );
			if ( 'valueExists' === $attributes['customFieldRule'] || empty( $attributes['customFieldRule'] ) ) {
				if ( $get_value || '0' === $get_value ) {
					$display_by_custom_field = true;
				} else {
					$display_by_custom_field = false;
				}
			} elseif ( 'valueEquals' === $attributes['customFieldRule'] ) {
				if ( $get_value === $attributes['customFieldValue'] ) {
					$display_by_custom_field = true;
				} else {
					$display_by_custom_field = false;
				}
			}
		}
	}

	// Display period Check
	$display_by_period = false;

	if ( 'none' === $attributes['periodDisplaySetting'] ) {
		$display_by_period = true;
	} elseif ( 'deadline' === $attributes['periodDisplaySetting'] ) {
		if ( 'direct' === $attributes['periodSpecificationMethod'] ) {

			// 時間指定がない場合(日付までで時間が入力されていない場合)に時間を自動指定.
			if ( $attributes['periodDisplayValue'] === date( 'Y-m-d', strtotime( $attributes['periodDisplayValue'] ) ) ) {
				$attributes['periodDisplayValue'] .= ' 23:59';
			}

			// 日付のフォーマットを Y-m-d H:i に指定.
			if ( $attributes['periodDisplayValue'] !== date( 'Y-m-d H:i', strtotime( $attributes['periodDisplayValue'] ) ) ) {
				$attributes['periodDisplayValue'] = date( 'Y-m-d H:i', strtotime( $attributes['periodDisplayValue'] ) );
			}

			if ( $attributes['periodDisplayValue'] > current_time( 'Y-m-d H:i' ) ) {
				$display_by_period = true;
			} else {
				$display_by_period = false;
			}
		} elseif ( 'referCustomField' === $attributes['periodSpecificationMethod'] ) {
			if ( ! empty( $attributes['periodReferCustomField'] ) ) {
				$get_refer_value = get_post_meta( get_the_ID(), $attributes['periodReferCustomField'], true );

				// Check if $get_refer_value matches the date format.
				$check_date_ymd     = DateTime::createFromFormat( 'Y-m-d', $get_refer_value );
				$check_date_ymd_hi  = DateTime::createFromFormat( 'Y-m-d H:i', $get_refer_value );
				$check_date_ymd_his = DateTime::createFromFormat( 'Y-m-d H:i:s', $get_refer_value );

				if ( $check_date_ymd || $check_date_ymd_hi || $check_date_ymd_his ) {

					if ( $check_date_ymd ) {
						// If it's only 'Y-m-d' format, append the time as 23:59.
						$get_refer_value .= ' 23:59:59';
					}

					if ( $check_date_ymd_hi ) {
						// If it's only 'Y-m-d H:s' format, append the time as 23:59:59.
						$get_refer_value .= ':59';
					}

					if ( $get_refer_value > current_time( 'Y-m-d H:i:s' ) ) {
						$display_by_period = true;
					} else {
						$display_by_period = false;
					}
				} else {
					// This means the value doesn't match either date formats
					$display_by_period = true;
				}
			} else {
				$display_by_period = true;
			}
		}
	} elseif ( 'startline' === $attributes['periodDisplaySetting'] ) {
		if ( 'direct' === $attributes['periodSpecificationMethod'] ) {

			// 時間指定がない場合に時間を自動指定.
			if ( $attributes['periodDisplayValue'] === date( 'Y-m-d', strtotime( $attributes['periodDisplayValue'] ) ) ) {
				$attributes['periodDisplayValue'] .= ' 00:00';
			}

			// 日付のフォーマットを Y-m-d H:i に指定.
			if ( $attributes['periodDisplayValue'] !== date( 'Y-m-d H:i', strtotime( $attributes['periodDisplayValue'] ) ) ) {
				$attributes['periodDisplayValue'] = date( 'Y-m-d H:i', strtotime( $attributes['periodDisplayValue'] ) );
			}

			if ( $attributes['periodDisplayValue'] <= current_time( 'Y-m-d H:i' ) ) {
				$display_by_period = true;
			} else {
				$display_by_period = false;
			}
		} elseif ( 'referCustomField' === $attributes['periodSpecificationMethod'] ) {
			if ( ! empty( $attributes['periodReferCustomField'] ) ) {
				$get_refer_value = get_post_meta( get_the_ID(), $attributes['periodReferCustomField'], true );

				// Check if $get_refer_value matches the date format.
				$check_date_ymd     = DateTime::createFromFormat( 'Y-m-d', $get_refer_value );
				$check_date_ymd_hi  = DateTime::createFromFormat( 'Y-m-d H:i', $get_refer_value );
				$check_date_ymd_his = DateTime::createFromFormat( 'Y-m-d H:i:s', $get_refer_value );

				if ( $check_date_ymd || $check_date_ymd_hi || $check_date_ymd_his ) {

					if ( $check_date_ymd ) {
						// If it's only 'Y-m-d' format, append the time as 00:00:00.
						$get_refer_value .= ' 00:00:00';
					}

					if ( $check_date_ymd_hi ) {
						// If it's only 'Y-m-d H:i' format, append the time as 00:00:00.
						$get_refer_value .= ':00';
					}

					if ( $get_refer_value <= current_time( 'Y-m-d H:i:s' ) ) {
						$display_by_period = true;
					} else {
						$display_by_period = false;
					}
				} else {
					// This means the value doesn't match either date formats.
					$display_by_period = true;
				}
			} else {
				$display_by_period = true;
			}
		}
	} elseif ( 'daysSincePublic' === $attributes['periodDisplaySetting'] ) {
		if ( 'direct' === $attributes['periodSpecificationMethod'] ) {
			$days_since_public = intval( $attributes['periodDisplayValue'] );
			$post_publish_date = get_post_time( 'U', true, get_the_ID() );
			$current_time      = current_time( 'timestamp' );

			if ( $current_time >= $post_publish_date + ( $days_since_public * 86400 ) ) {
				$display_by_period = false;
			} else {
				$display_by_period = true;
			}
		} elseif ( 'referCustomField' === $attributes['periodSpecificationMethod'] ) {
			if ( ! empty( $attributes['periodReferCustomField'] ) ) {
				$get_refer_value = get_post_meta( get_the_ID(), $attributes['periodReferCustomField'], true );

				// Check if $get_refer_value is numeric.
				if ( is_numeric( $get_refer_value ) ) {
					$days_since_public = intval( $get_refer_value );
					$post_publish_date = get_post_time( 'U', true, get_the_ID() );
					$current_time      = current_time( 'timestamp' );

					if ( $current_time >= $post_publish_date + ( $days_since_public * 86400 ) ) {
						$display_by_period = false;
					} else {
						$display_by_period = true;
					}
				} else {
					// This means the value is not numeric.
					$display_by_period = true;
				}
			} else {
				$display_by_period = true;
			}
		}
	}

	// Login user Check
	$display_by_login_user = true; // デフォルトで表示を許可

	// ユーザーがログインしているか、またはログインユーザーのみの表示が不要な場合に表示を許可
	if ( $attributes['showOnlyLoginUser'] && !is_user_logged_in() ) {
		$display_by_login_user = false;
	}

	// Merge Condition Check
	if ( $display_by_post_type && $display_by_author && $display_by_language && $display_by_page_type && $display_by_custom_field && $display_by_user_role && $display_by_period && $display_by_login_user ) {
		$display = true;
	}

	return $display;
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

	// Languages //////////////////////////////////.
	$language_select_options   = array(
		array(
			'label' => __( 'Unspecified', 'vk-dynamic-if-block' ),
			'value' => '',
		),
		array(
			'label' => 'English (United States)',
			'value' => 'en_US',
		)
	);
	// WordPress.orgのAPIから利用可能な言語リストを取得
	$response = wp_remote_get( 'https://api.wordpress.org/translations/core/1.0/' );
	if ( ! is_wp_error( $response ) ) {
		$body      = wp_remote_retrieve_body( $response );
		$languages = json_decode( $body, true )['translations'];

		// 各言語に対してオプション配列を追加
		foreach ( $languages as $language ) {
			$language_select_options[] = array(
				'label' => $language['native_name'],
				'value' => $language['language'],
			);
		}
	}

	$post_types = get_post_types( array( 'public' => true ), 'names' );

	$users = get_users( [
		'role__in' => apply_filters( 'vk_dynamic_if_block_author_role__in', array( 'contributor', 'author', 'editor', 'administrator' ) ),
	] );

	$user_select_options = array(
		array(
			'label' => __( 'Unspecified', 'vk-dynamic-if-block' ),
			'value' => 0,
		),
	);
	foreach ( $users as $user ) {
		$has_published = false;
		foreach ( $post_types as $post_type ) {
			if ( count_user_posts( $user->ID, $post_type, true ) > 0 ) {
				$has_published = true;
				break;
			}
		}
		if ( $has_published ) {
			$user_select_options[] = array(
				'label' => $user->display_name,
				'value' => $user->ID,
			);
		}
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

add_action( 'enqueue_block_editor_assets', 'vk_dynamic_if_block_set_localize_script' );
