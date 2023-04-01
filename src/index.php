<?php
function vk_dynamic_if_block_render( $attributes, $content ) {
	$display_condition = isset( $attributes['displayCondition'] ) ? $attributes['displayCondition'] : 'none';

	switch ( $display_condition ) {
		case 'is_front_page':
			if ( is_front_page() ) {
				return $content;
			}
			break;
		case 'is_single':
			if ( is_single() ) {
				return $content;
			}
			break;
		default:
			return $content;
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
