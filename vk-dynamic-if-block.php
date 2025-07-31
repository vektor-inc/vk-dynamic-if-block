<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Plugin URI: https://github.com/vektor-inc/vk-dynamic-if-block
 * Description: A dynamic block displays its Inner Blocks based on specified conditions, such as whether the current page is the front page or a single post, the post type, or the value of a Custom Field.
 * Author: Vektor,Inc.
 * Author URI: https://vektor-inc.co.jp/en/
 * Version: 0.9.3
 * Stable tag: 0.9.3
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
// Deploy failure countermeasure for Vendor directory.
if ( file_exists( $autoload_path ) ) {
	require_once $autoload_path;
}

// Show important admin notice for version 1.0 update
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin-notice.php';
}

function vk_dynamic_if_block_enqueue_scripts() {

	$script_dependencies = include plugin_dir_path( __FILE__ ) . '/build/index.asset.php';

	// WordPress 6.5 以下の対策
	if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
		wp_enqueue_script(
			'react-jsx-runtime',
			plugins_url( 'build/react-jsx-runtime.js', __FILE__ ),
			array( 'react' ),
			'18.3.1',
			true
		);
	}

	$handle = 'vk-dynamic-if-block';
	wp_enqueue_script(
		$handle,
		plugins_url( 'build/index.js', __FILE__ ),
		$script_dependencies['dependencies'],
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

if ( ! function_exists( 'vk_dynamic_if_block_set_script_translations' ) ) {
	/**
	 * Set text domain for translations.
	 */
	function vk_dynamic_if_block_set_script_translations() {
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'vk-dynamic-if-block', 'vk-dynamic-if-block' );
		}
	}
	add_action( 'enqueue_block_editor_assets', 'vk_dynamic_if_block_set_script_translations' );
}

if ( ! function_exists( 'vk_blocks_set_wp_version' ) ) {
	/**
	 * VK Blocks Set WP Version
	 */
	function vk_blocks_set_wp_version() {
		global $wp_version;

		// RC版の場合ハイフンを削除.
		if ( strpos( $wp_version, '-' ) !== false ) {
			$_wp_version = strstr( $wp_version, '-', true );
		} else {
			$_wp_version = $wp_version;
		}

		echo '<script>',
			'var wpVersion = "' . esc_attr( $_wp_version ) . '";',
		'</script>';
	}
	add_action( 'admin_head', 'vk_blocks_set_wp_version' );
}
