<?php
/**
 * Dynamic If Block
 *
 * @package vektor-inc/vk-dynamic-if-block
 */

use VektorInc\VK_Helpers\VkHelpers;

function vk_dynamic_if_block_render( $attributes, $content ) {
	$page_type = isset( $attributes['pageType'] ) ? $attributes['pageType'] : 'none';
	$post_type = isset( $attributes['selectedPostType'] ) ? $attributes['selectedPostType'] : 'none';

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
