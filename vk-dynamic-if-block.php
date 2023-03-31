<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Plugin URI: https://example.com
 * Description: A WordPress plugin that provides a dynamic if block for the block editor, allowing content to be displayed based on selected conditions.
 * Author: Vektor,Inc.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Tested up to: 6.2
 * License: GPL-2.0-or-later
 * Text Domain: vk-dynamic-if-block
 */

defined( 'ABSPATH' ) || exit;

function vk_dynamic_if_block_register() {
	require_once plugin_dir_path( __FILE__ ) . 'src/index.php';
}
add_action( 'init', 'vk_dynamic_if_block_register' );
