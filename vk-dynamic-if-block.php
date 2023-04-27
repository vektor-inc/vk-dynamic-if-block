<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Plugin URI: https://vektor-inc.co.jp/en/plugins/vk-dynamic-if-block/
 * Description: A dynamic block that shows its inner blocks based on specified conditions, such as whether the current page is the front page or a single post.
 * Author: Vektor,Inc.
 * Author URI: https://vektor-inc.co.jp/en/
 * Version: 0.2.1
 * License: GPL-2.0-or-later
 * Text Domain: vk-dynamic-if-block
 *
 * @package VK Dynamic If Block
 */

defined( 'ABSPATH' ) || exit;

/**
 * Composer Autoload
 */
$autoload_path = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
// Deploy failure countermeasure for Vendor directory
if ( file_exists( $autoload_path ) ) {
	require_once $autoload_path;
}

function vk_dynamic_if_block_enqueue_scripts() {
	wp_enqueue_script(
		'vk-dynamic-if-block',
		plugins_url( 'build/index.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'wp-components' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'build/index.js' )
	);

	wp_enqueue_style(
		'vk-dynamic-if-block-editor',
		plugins_url( 'build/editor.css', __FILE__ ),
		array(),
		filemtime( plugin_dir_path( __FILE__ ) . 'build/editor.css' )
	);
}

add_action( 'enqueue_block_editor_assets', 'vk_dynamic_if_block_enqueue_scripts' );

require_once plugin_dir_path( __FILE__ ) . 'build/index.php';
