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
		'ifPageType'                => array(),
		'ifPostType'                => array(),
		'userRole'                  => array(),
		'customFieldName'           => '',
		'customFieldRule'           => 'valueExists',
		'customFieldValue'          => '',
		'exclusion'                 => false,
		'periodDisplaySetting'      => 'none',
		'periodSpecificationMethod' => 'direct',
		'periodDisplayValue'        => '',
		'periodReferCustomField'    => '',
	);
	$attributes         = array_merge( $attributes_default, $attributes );

	$display = false;

	// Page Type Condition Check //////////////////////////////////.

	$display_by_page_type = false;

	$current_page_type = '';

	if ( is_front_page() ) {
		$current_page_type = 'is_front_page';
	} elseif ( is_single() ) {
		$current_page_type = 'is_single';
	} elseif ( is_page() ) {
		$current_page_type = 'is_page';
	} elseif ( is_singular() ) {
		$current_page_type = 'is_singular';
	} elseif ( is_home() && ! is_front_page() ) {
		$current_page_type = 'is_home';
	} elseif ( is_post_type_archive() ) {
		$current_page_type = 'is_post_type_archive';
	} elseif ( is_category() ) {
		$current_page_type = 'is_category';
	} elseif ( is_tag() ) {
		$current_page_type = 'is_tag';
	} elseif ( is_tax() ) {
		$current_page_type = 'is_tax';
	} elseif ( is_year() ) {
		$current_page_type = 'is_year';
	} elseif ( is_month() ) {
		$current_page_type = 'is_month';
	} elseif ( is_date() ) {
		$current_page_type = 'is_date';
	} elseif ( is_author() ) {
		$current_page_type = 'is_author';
	} elseif ( is_archive() ) {
		$current_page_type = 'is_archive';
	} elseif ( is_search() ) {
		$current_page_type = 'is_search';
	} elseif ( is_404() ) {
		$current_page_type = 'is_404';
	}

	if (!is_array($attributes['ifPageType'])) {
		$attributes['ifPageType'] = array($attributes['ifPageType']);
	}

	if ( ! isset( $attributes['ifPageType'] ) || empty( $attributes['ifPageType'] ) ) {
		$display_by_page_type = true;
		// var_dump($attributes);
	} else {
		if ( in_array( $current_page_type, $attributes['ifPageType'] ) ) {
			$display_by_page_type = true;
		} else {
			$display_by_page_type = false;
		}
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

	if (!is_array($attributes['ifPostType'])) {
		$attributes['ifPostType'] = array($attributes['ifPostType']);
	}
	if (!isset($attributes['ifPostType']) || empty($attributes['ifPostType'])) {
		$display_by_post_type = true;
	} else {
		if ( in_array('none', $attributes['ifPostType']) ) {
			$display_by_post_type = true;
		} elseif ( in_array($post_type_slug, $attributes['ifPostType']) ) {
			$display_by_post_type = true;
		} else {
			$display_by_post_type = false;
		}
	}

	// User Role Condition Check //////////////////////////////////.

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

	// Custom Field Condition Check //////////////////////////////////.

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

	// Display period Check //////////////////////////////////.

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

	$post_type_select_options = array();

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
