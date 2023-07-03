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
	$page_type = isset( $attributes['ifPageType'] ) ? $attributes['ifPageType'] : 'none';
	$post_type = isset( $attributes['ifPostType'] ) ? $attributes['ifPostType'] : 'none';
	$post_type = isset( $attributes['customFieldName'] ) ? $attributes['customFieldName'] : '';
	$post_type = isset( $attributes['customFieldRule'] ) ? $attributes['customFieldRule'] : '';
	$post_type = isset( $attributes['customFieldValue'] ) ? $attributes['customFieldValue'] : '';
	$exclusion = isset( $attributes['exclusion'] ) ? $attributes['exclusion'] : false;

	$display           = '';

	// Page Type Condition Check //////////////////////////////////.

	$display_page_type = '';

	if (
		is_front_page() && 'is_front_page' === $page_type ||
		is_single() && 'is_single' === $page_type ||
		is_page() && 'is_page' === $page_type ||
		is_singular() && 'is_singular' === $page_type ||
		is_home() && ! is_front_page() && 'is_home' === $page_type ||
		is_post_type_archive() && 'is_post_type_archive' === $page_type ||
		is_category() && 'is_category' === $page_type ||
		is_tag() && 'is_tag' === $page_type ||
		is_tax() && 'is_tax' === $page_type ||
		is_year() && 'is_year' === $page_type ||
		is_month() && 'is_month' === $page_type ||
		is_date() && 'is_date' === $page_type ||
		is_author() && 'is_author' === $page_type ||
		is_search() && 'is_search' === $page_type ||
		is_404() && 'is_404' === $page_type ||
		is_archive() && 'is_archive' === $page_type ||
		'none' === $page_type
	) {
		$display_page_type = true;
	}

	// Post Type Condition Check //////////////////////////////////.

	$display_post_type = '';

	// vendorファイルの配信・読み込みミス時のフォールバック
	// Fallback for vendor files failed to deliver or load.
	if ( class_exists( 'VkHelpers' ) ) {
		$post_type_info = VkHelpers::get_post_type_info();
		$post_type_slug = $post_type_info['slug'];
	} else {
		$post_type_slug = get_post_type();
	}

	if ( 'none' === $post_type ) {
		$display_post_type = true;
	} elseif ( $post_type_slug === $post_type ) {
		$display_post_type = true;
	} else {
		$display_post_type = false;
	}

	// Merge Condition Check //////////////////////////////////.

	if ( $display_post_type && $display_page_type ) {
		$display = true;
	}

	/**
	 * Exclusion
	 *
	 * @since 0.3.0
	 */
	if ( $exclusion ) {
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
