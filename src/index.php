<?php
function vk_dynamic_if_block_assets() {
	wp_enqueue_style(
		'vk-dynamic-if-block-editor',
		plugins_url( 'build/editor.css', dirname( __FILE__ ) ),
		[],
		filemtime( plugin_dir_path( __DIR__ ) . 'build/editor.css' )
	);
}
add_action( 'enqueue_block_editor_assets', 'vk_dynamic_if_block_assets' );

function vk_dynamic_if_block_dynamic_render_callback( $attributes, $content ) {
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
	register_block_type_from_metadata(
		__DIR__,
		[
			'render_callback' => 'vk_dynamic_if_block_dynamic_render_callback',
		]
	);
}
add_action( 'init', 'vk_dynamic_if_block_register_dynamic' );
