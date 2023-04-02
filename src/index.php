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

	$return = '';

	if (
		is_front_page() && 'is_front_page' === $page_type ||
		is_single() && 'is_single' === $page_type ||
		is_page() && 'is_page' === $page_type ||
		is_singular() && 'is_singular' === $page_type ||
		is_home() && ! is_front_page() && 'is_home' === $page_type ||
		is_archive() && 'is_archive' === $page_type ||
		is_search() && 'is_search' === $page_type ||
		is_404() && 'is_404' === $page_type ||
		'none' === $page_type
	) {
		$return         = $content;
		$post_type_info = VkHelpers::get_post_type_info();
		$post_type_slug = $post_type_info['slug'];

		if ( 'none' === $post_type ) {
			$return = $content;
		} elseif ( $post_type_slug === $post_type ) {
			$return = $content;
		} else {
			$return = '';
		}
	}

	return $return;
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
