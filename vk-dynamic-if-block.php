<?php
/**
 * Plugin Name: VK Dynamic If Block
 * Plugin URI: https://github.com/vektor-inc/vk-dynamic-if-block
 * Description: A dynamic block displays its Inner Blocks based on specified conditions, such as whether the current page is the front page or a single post, the post type, or the value of a Custom Field.
 * Author: Vektor,Inc.
 * Author URI: https://vektor-inc.co.jp/en/
 * Version: 1.1.0
 * Stable tag: 1.1.0
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
 * プラグインアクティベーション時の処理
 */
function vk_dynamic_if_block_activate() {
	// 移行処理を実行
	vk_dynamic_if_block_migrate_old_blocks_on_activation();
	
	// バージョン情報を保存
	update_option( 'vk_dynamic_if_block_version', '1.1.0' );
}
register_activation_hook( __FILE__, 'vk_dynamic_if_block_activate' );

/**
 * プラグインアップデート時の処理
 */
function vk_dynamic_if_block_check_version() {
	$current_version = get_option( 'vk_dynamic_if_block_version', '0.8.6' );
	$plugin_version = '1.1.0';
	
	// 移行完了フラグをチェック
	$migration_completed = get_option( 'vk_dynamic_if_block_migration_completed', false );
	
	// デバッグ用: 移行処理を強制実行
	error_log( "VK Dynamic If Block Debug - Current Version: {$current_version}, Plugin Version: {$plugin_version}, Migration Completed: " . ( $migration_completed ? 'true' : 'false' ) );
	
	// デバッグ用: 移行完了フラグをリセット（開発時のみ使用）
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_option( 'vk_dynamic_if_block_migration_completed' );
		delete_option( 'vk_dynamic_if_block_version' );
		$migration_completed = false;
		$current_version = '0.8.6';
		error_log( "VK Dynamic If Block: Debug mode - reset migration flags" );
	}
	
	// バージョンが異なる場合、かつ移行が未完了の場合のみ移行処理を実行
	// デバッグ用: 条件を一時的に緩和
	if ( ( version_compare( $current_version, $plugin_version, '<' ) || true ) && ! $migration_completed ) {
		error_log( "VK Dynamic If Block: Starting migration process..." );
		vk_dynamic_if_block_migrate_old_blocks_on_activation();
		update_option( 'vk_dynamic_if_block_version', $plugin_version );
		update_option( 'vk_dynamic_if_block_migration_completed', true );
		error_log( "VK Dynamic If Block: Migration process completed." );
	} else {
		error_log( "VK Dynamic If Block: Migration skipped - version: {$current_version}, migration completed: " . ( $migration_completed ? 'true' : 'false' ) );
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
