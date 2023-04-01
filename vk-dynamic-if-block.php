<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Plugin URI: https://example.com/vk-dynamic-if-block
 * Description: A WordPress Gutenberg block plugin that allows users to conditionally display content based on a selected condition.
 * Author: Vektor,Inc.
 * Author URI: https://www.vektor-inc.co.jp
 * Version: 0.1.0
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vk-dynamic-if-block
 * Domain Path: /languages
 */

function vk_dynamic_if_block_register() {
	// Register the main plugin file for the build process.
	wp_register_script(
		'vk-dynamic-if-block',
		plugins_url( 'build/index.js', __FILE__ ),
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'build/index.js' )
	);

	// Register the editor stylesheet.
	wp_register_style(
		'vk-dynamic-if-block-editor',
		plugins_url( 'build/editor.css', __FILE__ ),
		array(),
		filemtime( plugin_dir_path( __FILE__ ) . 'build/editor.css' )
	);

	// Register the dynamic block.
	register_block_type(
		'vk-blocks/dynamic-if',
		array(
			'editor_script'   => 'vk-dynamic-if-block',
			'editor_style'    => 'vk-dynamic-if-block-editor',
			'render_callback' => 'vk_dynamic_if_block_render_callback',
			'attributes'      => array(
				'displayCondition' => array(
					'type'    => 'string',
					'default' => 'no-limit',
				),
			),
		)
	);
}
add_action( 'init', 'vk_dynamic_if_block_register' );

function vk_dynamic_if_block_render_callback( $attributes, $content ) {
	$condition = $attributes['displayCondition'];

	if ( ( $condition === 'is_front_page' && is_front_page() ) ||
		( $condition === 'is_single' && is_single() ) ||
		$condition === 'no-limit' ) {
		return $content;
	}

	return '';
}
