<?php

function vk_dynamic_if_block_register() {
	wp_register_script(
		'vk-dynamic-if-block-editor',
		plugins_url( 'build/index.js', dirname( __FILE__, 2 ) ),
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components' ),
		VK_DYNAMIC_IF_BLOCK_VERSION,
		true
	);

	wp_register_style(
		'vk-dynamic-if-block-editor',
		plugins_url( 'build/editor.css', dirname( __FILE__, 2 ) ),
		array(),
		VK_DYNAMIC_IF_BLOCK_VERSION
	);

	register_block_type_from_metadata(
		dirname( __FILE__, 2 ),
		array(
			'editor_script' => 'vk-dynamic-if-block-editor',
			'editor_style'  => 'vk-dynamic-if-block-editor',
		)
	);
}
add_action( 'init', 'vk_dynamic_if_block_register' );

function vk_dynamic_if_block_content_filter( $block_content, $block ) {
	if ( 'vk-blocks/dynamic-if' !== $block['blockName'] ) {
		return $block_content;
	}

	$display_condition = isset( $block['attrs']['displayCondition'] ) ? $block['attrs']['displayCondition'] : 'none';

	switch ( $display_condition ) {
		case 'is_front_page':
			if ( is_front_page() ) {
				return $block_content;
			}
			break;
		case 'is_single':
			if ( is_single() ) {
				return $block_content;
			}
			break;
		default:
			return $block_content;
	}

	return '';
}
