<?php
/**
 * VK Dynamic If Block Migration System
 *
 * @package VK Dynamic If Block
 */

defined('ABSPATH') || exit;

/**
 * 移行が必要なページを検索
 */
function vk_dynamic_if_block_find_pages_with_old_blocks() {
	global $wpdb;
	
	$posts = $wpdb->get_results("
		SELECT ID, post_title, post_type
		FROM {$wpdb->posts} 
		WHERE post_content LIKE '%vk-blocks/dynamic-if%'
		AND post_status IN ('publish', 'draft', 'private')
		ORDER BY post_type, post_title
	");
	
	return $posts;
}

/**
 * 移行完了フラグを設定
 */
function vk_dynamic_if_block_set_migration_completed() {
	update_option( 'vk_dynamic_if_block_migration_completed', true );
	update_option( 'vk_dynamic_if_block_version', '1.1.0' );
}

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

/**
 * コンテンツを移行
 */
function vk_dynamic_if_block_migrate_content( $content ) {
	// ブロックの正規表現パターン
		$pattern = '/<!-- wp:vk-blocks\/dynamic-if\s+(\{[^}]*\})\s+-->/';
		
	if ( ! preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}
	
	$updated_content = $content;
			
			// 後ろから処理（オフセットが変わらないように）
			for ( $i = count( $matches[0] ) - 1; $i >= 0; $i-- ) {
				$full_match_data = $matches[0][ $i ];
				$attributes_json_data = $matches[1][ $i ];
				
				// PREG_OFFSET_CAPTUREフラグにより、配列の要素を取得
				if ( is_array( $full_match_data ) ) {
					$full_match = $full_match_data[0];
					$full_match_offset = $full_match_data[1];
				} else {
					$full_match = $full_match_data;
					$full_match_offset = 0;
				}
				
				if ( is_array( $attributes_json_data ) ) {
					$attributes_json = $attributes_json_data[0];
				} else {
					$attributes_json = $attributes_json_data;
				}
				
				// 文字列であることを確認
				if ( ! is_string( $attributes_json ) ) {
					continue;
				}
				
				$attributes = json_decode( $attributes_json, true );
				
				if ( $attributes ) {
					// 古い属性が存在するかチェック
					$old_attributes = [
						'customFieldName',
						'ifPageType',
						'ifPostType',
						'ifLanguage',
						'userRole',
						'postAuthor',
						'periodDisplaySetting',
						'showOnlyLoginUser'
					];
					
					$has_old_attributes = false;
					foreach ( $old_attributes as $attr ) {
						if ( isset( $attributes[ $attr ] ) && ! empty( $attributes[ $attr ] ) && $attributes[ $attr ] !== 'none' ) {
							$has_old_attributes = true;
					break;
						}
					}
					
					if ( $has_old_attributes ) {
						// 移行処理を実行
						$migrated_conditions = vk_dynamic_if_block_migrate_old_attributes( $attributes );
						$attributes['conditions'] = $migrated_conditions;
						
						// 古い属性を削除
						foreach ( $old_attributes as $attr ) {
							unset( $attributes[ $attr ] );
						}
						
						// 新しい属性でJSONを生成
						$new_attributes_json = json_encode( $attributes );
						
						// 投稿の内容を更新
						$updated_content = substr_replace(
							$updated_content,
							'<!-- wp:vk-blocks/dynamic-if ' . $new_attributes_json . ' -->',
							$full_match_offset,
							strlen( $full_match )
						);
			}
		}
	}
	
	return $updated_content;
}

/**
 * 管理画面に移行アラートを表示
 */
function vk_dynamic_if_block_admin_notice() {
	// 移行完了フラグをチェック
	$migration_completed = get_option( 'vk_dynamic_if_block_migration_completed', false );
	
	if ( $migration_completed ) {
		return;
	}
	
	// 移行が必要なページを検索
	$posts = vk_dynamic_if_block_find_pages_with_old_blocks();
	
	if ( empty( $posts ) ) {
		// 移行対象がない場合は完了フラグを設定
		vk_dynamic_if_block_set_migration_completed();
		return;
	}
	
	$post_count = count( $posts );
	$post_types = array();
	foreach ( $posts as $post ) {
		$post_types[ $post->post_type ] = $post->post_type;
	}
	
	?>
	<div class="notice notice-warning is-dismissible">
		<h3>VK Dynamic If Block 移行が必要です</h3>
		<p>
			<strong><?php echo $post_count; ?>件</strong>のページで古いブロック形式が検出されました。
			以下のページで一括移行を実行してください。
		</p>
		
		<div style="margin: 15px 0;">
			<h4>移行対象ページ:</h4>
			<ul style="margin-left: 20px;">
				<?php foreach ( $post_types as $post_type ): ?>
					<li><strong><?php echo get_post_type_object( $post_type )->labels->name; ?></strong></li>
				<?php endforeach; ?>
			</ul>
		</div>
		
		<p>
			<a href="<?php echo admin_url( 'tools.php?page=vk-dynamic-if-block-migration' ); ?>" class="button button-primary">
				移行対象ページを表示
			</a>
			<button type="button" class="button" onclick="vk_dynamic_if_block_dismiss_migration()">
				移行完了としてマーク
			</button>
		</p>
	</div>
	
	<script>
	function vk_dynamic_if_block_dismiss_migration() {
		if ( confirm( '移行を完了としてマークしますか？\n\n注意: 実際にページを保存していない場合、古いブロック形式のままになります。' ) ) {
			// AJAXで移行完了フラグを設定
			fetch( '<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'action=vk_dynamic_if_block_complete_migration'
			}).then( function() {
				location.reload();
			});
		}
	}
	</script>
	<?php
}
add_action( 'admin_notices', 'vk_dynamic_if_block_admin_notice' );

/**
 * AJAX: 移行完了フラグを設定
 */
function vk_dynamic_if_block_ajax_complete_migration() {
	check_ajax_referer( 'vk_dynamic_if_block_migration', 'nonce' );
	
	vk_dynamic_if_block_set_migration_completed();
	
	wp_die( 'Migration completed' );
}
add_action( 'wp_ajax_vk_dynamic_if_block_complete_migration', 'vk_dynamic_if_block_ajax_complete_migration' );

/**
 * 管理メニューに移行ページを追加
 */
function vk_dynamic_if_block_add_admin_menu() {
	// 移行完了フラグをチェック
	$migration_completed = get_option( 'vk_dynamic_if_block_migration_completed', false );
	
	if ( $migration_completed ) {
		return;
	}
	
	// 移行が必要なページを検索
	$posts = vk_dynamic_if_block_find_pages_with_old_blocks();
	
	if ( empty( $posts ) ) {
		return;
	}
	
	add_submenu_page(
		'tools.php', // 親メニュー（ツール）
		'VK Dynamic If Block 移行', // ページタイトル
		'VK Dynamic If Block 移行', // メニュータイトル
		'manage_options', // 必要な権限
		'vk-dynamic-if-block-migration', // メニュースラッグ
		'vk_dynamic_if_block_migration_page' // コールバック関数
	);
}
add_action( 'admin_menu', 'vk_dynamic_if_block_add_admin_menu' );

/**
 * 移行専用ページの表示
 */
function vk_dynamic_if_block_migration_page() {
	// 移行完了フラグをチェック
	$migration_completed = get_option( 'vk_dynamic_if_block_migration_completed', false );
	
	if ( $migration_completed ) {
		wp_die( '移行は既に完了しています。' );
	}
	
	$posts = vk_dynamic_if_block_find_pages_with_old_blocks();
	
	if ( empty( $posts ) ) {
		echo '<div class="wrap"><h1>VK Dynamic If Block 移行</h1><div class="notice notice-success"><p>移行対象のページはありません。</p></div></div>';
		return;
	}
	
	?>
	<div class="wrap">
		<h1>VK Dynamic If Block 移行</h1>
		<p>以下のページで一括移行を実行してください。</p>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'vk_migration_bulk_action', 'vk_migration_nonce' ); ?>
			
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="vk_bulk_action">
						<option value="-1">一括操作</option>
						<option value="vk_migrate_blocks">VK Dynamic If Block 移行</option>
					</select>
					<input type="submit" class="button action" value="適用">
				</div>
				<div class="alignright">
					<span class="displaying-num"><?php echo count( $posts ); ?>個の項目</span>
				</div>
				<br class="clear">
			</div>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="check-column">
							<input type="checkbox" id="cb-select-all-1">
						</th>
						<th>タイトル</th>
						<th>投稿タイプ</th>
						<th>ステータス</th>
						<th>操作</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts as $post ): ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="post_ids[]" value="<?php echo $post->ID; ?>">
							</th>
							<td>
								<strong>
									<a href="<?php echo get_edit_post_link( $post->ID ); ?>" target="_blank">
										<?php echo esc_html( $post->post_title ); ?>
									</a>
								</strong>
							</td>
							<td><?php echo get_post_type_object( $post->post_type )->labels->singular_name; ?></td>
							<td><?php echo get_post_status_object( get_post_status( $post->ID ) )->label; ?></td>
							<td>
								<a href="<?php echo get_edit_post_link( $post->ID ); ?>" class="button button-small" target="_blank">
									編集
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
			<div class="tablenav bottom">
				<div class="alignleft actions bulkactions">
					<select name="vk_bulk_action2">
						<option value="-1">一括操作</option>
						<option value="vk_migrate_blocks">VK Dynamic If Block 移行</option>
					</select>
					<input type="submit" class="button action" value="適用">
				</div>
				<div class="alignright">
					<span class="displaying-num"><?php echo count( $posts ); ?>個の項目</span>
				</div>
				<br class="clear">
			</div>
		</form>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		// 全選択チェックボックスの処理
		$('#cb-select-all-1').on('change', function() {
			$('input[name="post_ids[]"]').prop('checked', this.checked);
		});
		
		// 個別チェックボックスの処理
		$('input[name="post_ids[]"]').on('change', function() {
			var total = $('input[name="post_ids[]"]').length;
			var checked = $('input[name="post_ids[]"]:checked').length;
			$('#cb-select-all-1').prop('checked', total === checked);
		});
	});
	</script>
	<?php
}

/**
 * 移行対象ページ一覧での一括操作を処理
 */
function vk_dynamic_if_block_handle_migration_bulk_action() {
	if ( ! isset( $_POST['vk_migration_nonce'] ) || ! wp_verify_nonce( $_POST['vk_migration_nonce'], 'vk_migration_bulk_action' ) ) {
		return;
	}
	
	$bulk_action = $_POST['vk_bulk_action'] ?? $_POST['vk_bulk_action2'] ?? '';
	
	if ( $bulk_action !== 'vk_migrate_blocks' ) {
		return;
	}
	
	$post_ids = $_POST['post_ids'] ?? array();
	
	if ( empty( $post_ids ) ) {
		wp_die( '移行対象を選択してください。' );
	}
	
	$migrated_count = 0;
	$failed_count = 0;
	
	foreach ( $post_ids as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			$failed_count++;
			continue;
		}
		
		// ブロックが含まれているかチェック
		if ( strpos( $post->post_content, 'vk-blocks/dynamic-if' ) === false ) {
			continue;
		}
		
		// 移行処理を実行してから保存
		$updated_content = vk_dynamic_if_block_migrate_content( $post->post_content );
		
		$result = wp_update_post( array(
			'ID' => $post_id,
			'post_content' => $updated_content
		) );
		
		if ( is_wp_error( $result ) ) {
			$failed_count++;
		} else {
			$migrated_count++;
		}
	}
	
	// 移行が成功した場合は自動で移行完了フラグを設定
	if ( $migrated_count > 0 ) {
		vk_dynamic_if_block_set_migration_completed();
	}
	
	// 結果をセッションに保存
	$_SESSION['vk_migration_result'] = array(
		'migrated' => $migrated_count,
		'failed' => $failed_count
	);
	
	// 同じページにリダイレクト
	wp_redirect( add_query_arg( 'vk_migration', 'show_posts', admin_url( 'edit.php?post_type=page' ) ) );
	exit;
}
add_action( 'admin_init', 'vk_dynamic_if_block_handle_migration_bulk_action' );

/**
 * 移行結果を表示
 */
function vk_dynamic_if_block_show_migration_result() {
	if ( ! isset( $_SESSION['vk_migration_result'] ) ) {
		return;
	}
	
	$result = $_SESSION['vk_migration_result'];
	unset( $_SESSION['vk_migration_result'] );
	
	$message = '';
	if ( $result['migrated'] > 0 ) {
		$message .= "{$result['migrated']}件のページを移行しました。";
	}
	if ( $result['failed'] > 0 ) {
		$message .= "{$result['failed']}件の移行に失敗しました。";
	}
	
	if ( $message ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'vk_dynamic_if_block_show_migration_result' ); 