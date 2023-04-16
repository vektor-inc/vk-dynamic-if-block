<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Plugin URI: https://github.com/vektor-inc/vk-dynamic-if-block
 * Description: A dynamic block that shows its inner blocks based on specified conditions, such as whether the current page is the front page or a single post.
 * Author: Vektor,Inc.
 * Author URI: https://vektor-inc.co.jp
 * Version: 0.1.2
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

// Update Checker.
if ( class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
	$my_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/vektor-inc/vk-dynamic-if-block',
		__FILE__,
		'vk-dynamic-if-block'
	);
	$my_update_checker->getVcsApi()->enableReleaseAssets();
}

/**
 * Load textdomain.
 */
function vk_dynamic_if_block_load_textdomain() {
	load_plugin_textdomain( 'vk-dynamic-if-block', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'vk_dynamic_if_block_load_textdomain' );

/**
 * Load translation file.
 */
function vk_dynamic_if_block_load_translation_file() {
	// JSON翻訳ファイルを読み込む.
	$handle     = 'vk-dynamic-if-block';
	$textdomain = 'vk-dynamic-if-block';
	wp_set_script_translations( $handle, $textdomain, plugin_dir_path( __FILE__ ) . 'languages' );
}
add_action( 'enqueue_block_editor_assets', 'vk_dynamic_if_block_load_translation_file' );

function vk_dynamic_if_block_enqueue_scripts() {

	$handle = 'vk-dynamic-if-block';
	wp_enqueue_script(
		$handle,
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
