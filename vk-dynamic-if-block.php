<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Plugin URI: https://github.com/vektor-inc/vk-dynamic-if-block
 * Description: A dynamic block that shows its inner blocks based on specified conditions, such as whether the current page is the front page or a single post.
 * Author: Vektor,Inc.
 * Author URI: https://vektor-inc.co.jp
 * Version: 0.2.1
 * License: GPL-2.0-or-later
 * Text Domain: vk-dynamic-if-block
 *
 * @package VK Dynamic If Block
 */

defined( 'ABSPATH' ) || exit;

// Define Plugin  Root Path.
define( 'VKDIF_PLUGIN_ROOT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Composer Autoload
 */
$autoload_path = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
// Deploy failure countermeasure for Vendor directory.
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
load_plugin_textdomain( 'vk-dynamic-if-block', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

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
	// JSON翻訳ファイルを読み込む.
	// 注意 : wp_enqueue_script の後で読み込まないと翻訳が反映されない.
	// Caution : If you do not read the JSON translation file after wp_enqueue_script, the translation will not be reflected.
	$textdomain = 'vk-dynamic-if-block';
	wp_set_script_translations( $handle, $textdomain, VKDIF_PLUGIN_ROOT_PATH . 'languages/' );
}

add_action( 'enqueue_block_editor_assets', 'vk_dynamic_if_block_enqueue_scripts' );

require_once plugin_dir_path( __FILE__ ) . 'build/index.php';
