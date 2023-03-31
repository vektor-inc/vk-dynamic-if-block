<?php

function vk_dynamic_if_block_render( $attributes, $content ) {
	$condition = isset( $attributes['condition'] ) ? $attributes['condition'] : 'is_front_page';

	if ( ( $condition === 'is_front_page' && is_front_page() ) || ( $condition === 'is_single' && is_single() ) ) {
		return $content;
	}

	return '';
}

register_block_type_from_metadata(
	__DIR__,
	array(
		'render_callback' => 'vk_dynamic_if_block_render',
	)
);
