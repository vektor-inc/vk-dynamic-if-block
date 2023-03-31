<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Author: Vektor, Inc.
 * Description: A WordPress Gutenberg block plugin for conditionally displaying inner blocks based on selected conditions.
 * Version: 0.1.0
 */

function vk_dynamic_if_block_register() {
	$asset_file = include plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
	wp_register_script(
		'vk-dynamic-if-block-script',
		plugins_url( 'build/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version']
	);

	wp_register_style(
		'vk-dynamic-if-block-editor-style',
		plugins_url( 'build/editor.css', __FILE__ ),
		array(),
		filemtime( plugin_dir_path( __FILE__ ) . 'build/editor.css' )
	);

	register_block_type_from_metadata(
		__DIR__ . '/src',
		array(
			'editor_script' => 'vk-dynamic-if-block-script',
			'editor_style'  => 'vk-dynamic-if-block-editor-style',
		)
	);
}
add_action( 'init', 'vk_dynamic_if_block_register' );
