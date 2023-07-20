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
 * @return string $return : Return HTML.
 */
function vk_dynamic_if_block_render( $attributes, $content ) {
	$attributes_default = array(
		'ifPageType'       => 'none',
		'ifPostType'       => 'none',
		'customFieldName'  => '',
		'customFieldRule'  => 'valueExists',
		'customFieldValue' => '',
		'moreThanValue' => '',
		'lessThanValue' => '',
		'exclusion'        => false,
	);
	$attributes         = array_merge( $attributes_default, $attributes );

	$display = false;

	// Page Type Condition Check //////////////////////////////////.

	$display_by_page_type = false;

	if (
		is_front_page() && 'is_front_page' === $attributes['ifPageType'] ||
		is_single() && 'is_single' === $attributes['ifPageType'] ||
		is_page() && 'is_page' === $attributes['ifPageType'] ||
		is_singular() && 'is_singular' === $attributes['ifPageType'] ||
		is_home() && ! is_front_page() && 'is_home' === $attributes['ifPageType'] ||
		is_post_type_archive() && 'is_post_type_archive' === $attributes['ifPageType'] ||
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

	if ( 'none' === $attributes['ifPostType'] ) {
		$display_by_post_type = true;
	} elseif ( $post_type_slug === $attributes['ifPostType'] ) {
		$display_by_post_type = true;
	} else {
		$display_by_post_type = false;
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
			} elseif ( 'setDisplayDeadline' === $attributes['customFieldRule'] ) {
				if ( $get_value > date("Y-m-d") ) {
					$display_by_custom_field = true;
				} else {
					$display_by_custom_field = false;
				}
			} elseif ( 'compareMoreLess' === $attributes['customFieldRule'] ) {
				if ( !empty($attributes['moreThanValue']) && empty($attributes['lessThanValue']) ){
					if ( $get_value > $attributes['moreThanValue'] ) {
						$display_by_custom_field = true;
					} else {
						$display_by_custom_field = false;
					}
				} elseif ( !empty($attributes['lessThanValue']) && empty($attributes['moreThanValue']) ){
					if ( $get_value < $attributes['lessThanValue'] ) {
						$display_by_custom_field = true;
					} else {
						$display_by_custom_field = false;
					}
				} elseif ( !empty($attributes['moreThanValue']) && !empty($attributes['lessThanValue']) ){
					if ( $get_value > $attributes['moreThanValue'] && $get_value < $attributes['lessThanValue'] ) {
						$display_by_custom_field = true;
					} else {
						$display_by_custom_field = false;
					}
				}
			}
		}
	}

	// Merge Condition Check //////////////////////////////////.

	if ( $display_by_post_type && $display_by_page_type && $display_by_custom_field ) {
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
		)
	);
}

add_action( 'enqueue_block_editor_assets', 'vk_dynamic_if_block_set_localize_script' );