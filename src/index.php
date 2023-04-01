<?php
function vk_dynamic_if_block_render( $attributes, $content ) {
	$display_condition = isset( $attributes['pageType'] ) ? $attributes['pageType'] : 'none';

	if (
		is_front_page() && 'is_front_page' === $display_condition ||
		is_single() && 'is_single' === $display_condition ||
		is_page() && 'is_page' === $display_condition ||
		is_singular() && 'is_singular' === $display_condition ||
		is_home() && ! is_front_page() && 'is_home' === $display_condition ||
		is_archive() && 'is_archive' === $display_condition ||
		is_search() && 'is_search' === $display_condition ||
		is_404() && 'is_404' === $display_condition ||
		'none' === $display_condition
	) {
		return $content;
	} else {
		return '';
	}

	return '';
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
