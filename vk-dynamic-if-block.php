<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Plugin URI: https://github.com/vektor-inc/vk-dynamic-if-block
 * Description: A dynamic block displays its Inner Blocks based on specified conditions, such as whether the current page is the front page or a single post, the post type, or the value of a Custom Field.
 * Author: Vektor,Inc.
 * Author URI: https://vektor-inc.co.jp/en/
 * Version: 1.1.0
 * License: GPL-2.0-or-later
 * Text Domain: vk-dynamic-if-block
 *
 * @package VK Dynamic If Block
 */

defined('ABSPATH') || exit;

/**
 * Composer Autoload
 */
$autoload_path = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
// Deploy failure countermeasure for Vendor directory.
if (file_exists($autoload_path) ) {
    include_once $autoload_path;
}

// 移行処理ファイルを読み込み
require_once plugin_dir_path(__FILE__) . 'inc/migration/config.php';

/**
 * プラグインアップデート時の処理（移行フラグのリセットのみ）
 */
function vk_dynamic_if_block_check_version() {
	$current_version = get_option( 'vk_dynamic_if_block_version', '' );
	$plugin_version = '1.1.0';
	
	// 新規インストール判定（バージョン情報が存在しない場合）
	$is_new_installation = empty( $current_version );
	
	if ( $is_new_installation ) {
		// 新規インストール時はバージョン情報のみ保存
		update_option( 'vk_dynamic_if_block_version', $plugin_version );
		update_option( 'vk_dynamic_if_block_migration_completed', true );
		error_log( "VK Dynamic If Block: New installation - version set to {$plugin_version}" );
		return;
	}
	
	// アップデート時は移行フラグをリセット（管理画面で手動移行）
	if ( version_compare( $current_version, $plugin_version, '<' ) ) {
		delete_option( 'vk_dynamic_if_block_migration_completed' );
		error_log( "VK Dynamic If Block: Update detected - migration flag reset" );
	}
}
add_action( 'plugins_loaded', 'vk_dynamic_if_block_check_version' );

function vk_dynamic_if_block_enqueue_scripts()
{

    $script_dependencies = include plugin_dir_path(__FILE__) . '/build/index.asset.php';

    // WordPress 6.5 以下の対策
    if (! wp_script_is('react-jsx-runtime', 'registered') ) {
        wp_enqueue_script(
            'react-jsx-runtime',
            plugins_url('build/react-jsx-runtime.js', __FILE__),
            array( 'react' ),
            '18.3.1',
            true
        );
    }

    $handle = 'vk-dynamic-if-block';
    wp_enqueue_script(
        $handle,
        plugins_url('build/index.js', __FILE__),
        $script_dependencies['dependencies'],
        filemtime(plugin_dir_path(__FILE__) . 'build/index.js')
    );

    wp_enqueue_style(
        'vk-dynamic-if-block-editor',
        plugins_url('build/editor.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'build/editor.css')
    );
}

add_action('enqueue_block_editor_assets', 'vk_dynamic_if_block_enqueue_scripts');

require_once plugin_dir_path(__FILE__) . 'build/index.php';

if (! function_exists('vk_dynamic_if_block_set_script_translations') ) {
    /**
     * Set text domain for translations.
     */
    function vk_dynamic_if_block_set_script_translations()
    {
        if (function_exists('wp_set_script_translations') ) {
            wp_set_script_translations('vk-dynamic-if-block', 'vk-dynamic-if-block');
        }
    }
    add_action('enqueue_block_editor_assets', 'vk_dynamic_if_block_set_script_translations');
}

if (! function_exists('vk_blocks_set_wp_version') ) {
    /**
     * VK Blocks Set WP Version
     */
    function vk_blocks_set_wp_version()
    {
        global $wp_version;

        // RC版の場合ハイフンを削除.
        if (strpos($wp_version, '-') !== false ) {
            $_wp_version = strstr($wp_version, '-', true);
        } else {
            $_wp_version = $wp_version;
        }

        echo '<script>',
        'var wpVersion = "' . esc_attr($_wp_version) . '";',
        '</script>';
    }
    add_action('admin_head', 'vk_blocks_set_wp_version');
}
