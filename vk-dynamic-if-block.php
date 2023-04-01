<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Description: This plugin adds a dynamic if block that conditionally displays content in the inner blocks based on various conditions like if the page is the front page or a single post page.
 * Author: Vektor,Inc.
 * Author URI: https://www.vektor.co.jp/
 * Version: 0.1.0
 * License: GPL-2.0-or-later
 * Text Domain: vk-dynamic-if-block
 *
 * @package VK Dynamic If Block
 */

defined( 'ABSPATH' ) || exit;

define( 'VK_DYNAMIC_IF_BLOCK_VERSION', '0.1.0' );
define( 'VK_DYNAMIC_IF_BLOCK_DIR', plugin_dir_path( __FILE__ ) );

require_once VK_DYNAMIC_IF_BLOCK_DIR . 'src/index.php';

add_filter('render_block', 'vk_dynamic_if_block_content_filter', 10, 2);


function vk_dynamic_if_block_register_assets() {
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

	register_block_type(
		__DIR__ . '/src',
		array(
			'editor_script' => 'vk-dynamic-if-block-script',
			'editor_style'  => 'vk-dynamic-if-block-editor-style',
		)
	);
}
add_action( 'init', 'vk_dynamic_if_block_register_assets' );
