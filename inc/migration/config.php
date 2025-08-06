<?php
/**
 * VK Dynamic If Block Migration System
 *
 * @package VK Dynamic If Block
 */

defined( 'ABSPATH' ) || exit;

/**
 * 移行が必要なページを検索
 *
 * @return array 移行対象の投稿一覧
 */
function vk_dynamic_if_block_find_pages_with_old_blocks() {
	global $wpdb;

	$posts = $wpdb->get_results(
		"
		SELECT ID, post_title, post_type
		FROM {$wpdb->posts} 
		WHERE post_content LIKE '%vk-blocks/dynamic-if%'
		AND post_status IN ('publish', 'draft', 'private')
		ORDER BY post_type, post_title
		"
	);

	return $posts;
}

/**
 * 移行完了フラグを設定
 *
 * @return void
 */
function vk_dynamic_if_block_set_migration_completed() {
	update_option( 'vk_dynamic_if_block_migration_completed', true );
	update_option( 'vk_dynamic_if_block_version', '1.1.0' );
}

/**
 * プラグインアップデート時の処理
 *
 * @return void
 */
function vk_dynamic_if_block_check_version() {
	$current_version = get_option( 'vk_dynamic_if_block_version', '' );
	$plugin_version = '1.1.0';

	// 新規インストール判定（バージョン情報が存在しない場合）
	$is_new_installation = empty( $current_version );

	if ( $is_new_installation ) {
		// 新規インストール時はバージョン情報を保存
		update_option( 'vk_dynamic_if_block_version', $plugin_version );
		
		// 移行が必要なページがあるかチェック
		$posts = vk_dynamic_if_block_find_pages_with_old_blocks();
		
		if ( empty( $posts ) ) {
			// 移行対象がない場合のみ完了フラグを設定
			update_option( 'vk_dynamic_if_block_migration_completed', true );
		} else {
			// 移行対象がある場合は完了フラグを設定しない（アラートを表示するため）
			delete_option( 'vk_dynamic_if_block_migration_completed' );
		}
		
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
 * 古い属性を新しい形式に移行（移行ツール用の簡易版）
 *
 * @param array $attributes ブロック属性
 * @return array 移行後の条件配列
 */
function vk_dynamic_if_block_migrate_old_attributes_simple( $attributes ) {
	$group_conditions = [];
	$migrations = [
		'ifPageType' => 'pageType',
		'ifPostType' => 'postType',
		'ifLanguage' => 'language',
		'postAuthor' => 'postAuthor'
	];

	foreach ( $migrations as $old_key => $new_type ) {
		if ( isset( $attributes[ $old_key ] ) && $attributes[ $old_key ] !== 'none' ) {
			// 昔の状態では単一値だったので、配列の場合は最初の値を使用
			$value = is_array( $attributes[ $old_key ] )
				? $attributes[ $old_key ][0]
				: $attributes[ $old_key ];
			$group_conditions[] = [
				'id' => "migrated_{$new_type}_" . time(),
				'type' => $new_type,
				'values' => [
					$old_key => $value
				]
			];
		}
	}

	// 特殊なケース - userRoleは配列のまま
	if ( isset( $attributes['userRole'] ) && ! empty( $attributes['userRole'] ) ) {
		// userRoleは複数選択可能なので配列として処理
		$values = is_array( $attributes['userRole'] )
			? $attributes['userRole']
			: [ $attributes['userRole'] ];
		$group_conditions[] = [
			'id' => 'migrated_user_role_' . time(),
			'type' => 'userRole',
			'values' => [ 'userRole' => $values ]
		];
	}

	if ( isset( $attributes['customFieldName'] )
		&& ! empty( $attributes['customFieldName'] )
	) {
		$values = [
			'customFieldName' => $attributes['customFieldName']
		];
		if ( isset( $attributes['customFieldRule'] ) ) {
			$values['customFieldRule'] = $attributes['customFieldRule'];
		}
		if ( isset( $attributes['customFieldValue'] ) ) {
			$values['customFieldValue'] = $attributes['customFieldValue'];
		}

		$group_conditions[] = [
			'id' => 'migrated_custom_field_' . time(),
			'type' => 'customField',
			'values' => $values
		];
	}

	if ( isset( $attributes['periodDisplaySetting'] )
		&& $attributes['periodDisplaySetting'] !== 'none'
	) {
		$values = [
			'periodDisplaySetting' => $attributes['periodDisplaySetting']
		];
		$period_keys = [
			'periodSpecificationMethod',
			'periodDisplayValue',
			'periodReferCustomField'
		];
		foreach ( $period_keys as $key ) {
			if ( isset( $attributes[ $key ] ) ) {
				$values[ $key ] = $attributes[ $key ];
			}
		}

		$group_conditions[] = [
			'id' => 'migrated_period_' . time(),
			'type' => 'period',
			'values' => $values
		];
	}

	if ( isset( $attributes['showOnlyLoginUser'] )
		&& $attributes['showOnlyLoginUser']
	) {
		$group_conditions[] = [
			'id' => 'migrated_login_user_' . time(),
			'type' => 'loginUser',
			'values' => [
				'showOnlyLoginUser' => $attributes['showOnlyLoginUser']
			]
		];
	}

	// JavaScriptが期待する形式（グループの配列）に変換
	if ( ! empty( $group_conditions ) ) {
		return [
			[
				'id' => 'migrated_group_' . time(),
				'name' => 'Migrated Conditions',
				'conditions' => $group_conditions,
				'operator' => 'and'
			]
		];
	}

	return [];
}

/**
 * コンテンツを移行
 *
 * @param string $content 投稿コンテンツ
 * @return string 移行後のコンテンツ
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
				$migrated_conditions = function_exists( 'vk_dynamic_if_block_migrate_old_attributes' )
					? vk_dynamic_if_block_migrate_old_attributes( $attributes )
					: vk_dynamic_if_block_migrate_old_attributes_simple( $attributes );
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
 *
 * @return void
 */
function vk_dynamic_if_block_admin_notice() {
	// 移行ページではアラートを表示しない
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'vk-dynamic-if-block-migration' ) {
		return;
	}
	
	// デバッグ用：強制的に移行アラートを表示する場合
	if ( isset( $_GET['force_migration_alert'] ) && current_user_can( 'manage_options' ) ) {
		delete_option( 'vk_dynamic_if_block_migration_completed' );
	}
	
	// 移行完了フラグをリセットする場合
	if ( isset( $_GET['reset_migration'] ) && current_user_can( 'manage_options' ) ) {
		delete_option( 'vk_dynamic_if_block_migration_completed' );
		echo '<div class="notice notice-success"><p>Migration flag has been reset. Please refresh the page.</p></div>';
		return;
	}
	
	// テスト用：強制的に移行アラートを表示
	if ( isset( $_GET['test_migration_alert'] ) && current_user_can( 'manage_options' ) ) {
		$migration_completed = false;
		$posts = array( (object) array( 'ID' => 1, 'post_title' => 'Test Page', 'post_type' => 'page' ) );
		$post_count = 1;
		$post_types = array( 'page' => 'page' );
		
		?>
		<div class="notice notice-warning is-dismissible">
			<h3><?php _e( 'VK Dynamic If Block Migration Required (TEST)', 'vk-dynamic-if-block' ); ?></h3>
			<p>
				<?php printf( __( '<strong>%d pages</strong> with old block format detected. Please perform bulk migration on the following pages.', 'vk-dynamic-if-block' ), $post_count ); ?>
			</p>

			<div style="margin: 15px 0;">
				<h4><?php _e( 'Migration Target Pages:', 'vk-dynamic-if-block' ); ?></h4>
				<ul style="margin-left: 20px;">
					<?php foreach ( $post_types as $post_type ) : ?>
						<li><strong><?php echo get_post_type_object( $post_type )->labels->name; ?></strong></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<p>
				<a href="<?php echo admin_url( 'tools.php?page=vk-dynamic-if-block-migration' ); ?>" class="button button-primary">
					<?php _e( 'Show Migration Target Pages', 'vk-dynamic-if-block' ); ?>
				</a>
				<button type="button" class="button" onclick="vk_dynamic_if_block_dismiss_migration()">
					<?php _e( 'Mark Migration as Complete', 'vk-dynamic-if-block' ); ?>
				</button>
			</p>
		</div>

		<script>
		function vk_dynamic_if_block_dismiss_migration() {
			if ( confirm( '<?php echo esc_js( __( 'Mark migration as complete?\n\nNote: If you haven\'t actually saved the pages, they will remain in the old block format.', 'vk-dynamic-if-block' ) ); ?>' ) ) {
				// AJAXで移行完了フラグを設定
				fetch( '<?php echo admin_url( 'admin-ajax.php' ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'action=vk_dynamic_if_block_complete_migration&nonce=<?php echo wp_create_nonce( 'vk_dynamic_if_block_migration' ); ?>'
				}).then( function() {
					location.reload();
				});
			}
		}
		</script>
		<?php
		return;
	}
	
	// 移行完了フラグをチェック
	$migration_completed = get_option( 'vk_dynamic_if_block_migration_completed', false );

	// デバッグ情報を出力
	if ( isset( $_GET['debug_migration'] ) && current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-info"><p><strong>Debug Info:</strong></p>';
		echo '<p>Migration completed: ' . ( $migration_completed ? 'true' : 'false' ) . '</p>';
		
		$posts = vk_dynamic_if_block_find_pages_with_old_blocks();
		echo '<p>Found pages with old blocks: ' . count( $posts ) . '</p>';
		
		if ( ! empty( $posts ) ) {
			echo '<p>Pages found:</p><ul>';
			foreach ( $posts as $post ) {
				echo '<li>' . esc_html( $post->post_title ) . ' (ID: ' . $post->ID . ')</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
	}

	// フックが動作しているかテスト
	if ( isset( $_GET['test_hook'] ) && current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-success"><p><strong>Hook Test:</strong> admin_notices hook is working!</p></div>';
		return;
	}

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
		<h3><?php _e( 'VK Dynamic If Block Migration Required', 'vk-dynamic-if-block' ); ?></h3>
		<p>
			<?php printf( __( '<strong>%d pages</strong> with old block format detected. Please perform bulk migration on the following pages.', 'vk-dynamic-if-block' ), $post_count ); ?>
		</p>

		<div style="margin: 15px 0;">
			<h4><?php _e( 'Migration Target Pages:', 'vk-dynamic-if-block' ); ?></h4>
			<ul style="margin-left: 20px;">
				<?php foreach ( $post_types as $post_type ) : ?>
					<li><strong><?php echo get_post_type_object( $post_type )->labels->name; ?></strong></li>
				<?php endforeach; ?>
			</ul>
		</div>

		<p>
			<a href="<?php echo admin_url( 'tools.php?page=vk-dynamic-if-block-migration' ); ?>" class="button button-primary">
				<?php _e( 'Show Migration Target Pages', 'vk-dynamic-if-block' ); ?>
			</a>
			<button type="button" class="button" onclick="vk_dynamic_if_block_dismiss_migration()">
				<?php _e( 'Mark Migration as Complete', 'vk-dynamic-if-block' ); ?>
			</button>
		</p>
	</div>

	<script>
	function vk_dynamic_if_block_dismiss_migration() {
		if ( confirm( '<?php echo esc_js( __( 'Mark migration as complete?\n\nNote: If you haven\'t actually saved the pages, they will remain in the old block format.', 'vk-dynamic-if-block' ) ); ?>' ) ) {
			// AJAXで移行完了フラグを設定
			fetch( '<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'action=vk_dynamic_if_block_complete_migration&nonce=<?php echo wp_create_nonce( 'vk_dynamic_if_block_migration' ); ?>'
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
 *
 * @return void
 */
function vk_dynamic_if_block_ajax_complete_migration() {
	check_ajax_referer( 'vk_dynamic_if_block_migration', 'nonce' );

	vk_dynamic_if_block_set_migration_completed();

	wp_die( 'Migration completed' );
}
add_action( 'wp_ajax_vk_dynamic_if_block_complete_migration', 'vk_dynamic_if_block_ajax_complete_migration' );

/**
 * 管理メニューに移行ページを追加
 *
 * @return void
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
		__( 'VK Dynamic If Block Migration', 'vk-dynamic-if-block' ), // ページタイトル
		__( 'VK Dynamic If Block Migration', 'vk-dynamic-if-block' ), // メニュータイトル
		'manage_options', // 必要な権限
		'vk-dynamic-if-block-migration', // メニュースラッグ
		'vk_dynamic_if_block_migration_page' // コールバック関数
	);
}
add_action( 'admin_menu', 'vk_dynamic_if_block_add_admin_menu' );

/**
 * 移行専用ページの表示
 *
 * @return void
 */
function vk_dynamic_if_block_migration_page() {
	// 移行完了フラグをチェック
	$migration_completed = get_option( 'vk_dynamic_if_block_migration_completed', false );

	if ( $migration_completed ) {
		wp_die( __( 'Migration is already completed.', 'vk-dynamic-if-block' ) );
	}

	$posts = vk_dynamic_if_block_find_pages_with_old_blocks();

	if ( empty( $posts ) ) {
		echo '<div class="wrap"><h1>' . __( 'VK Dynamic If Block Migration', 'vk-dynamic-if-block' ) . '</h1><div class="notice notice-success"><p>' . __( 'No pages require migration.', 'vk-dynamic-if-block' ) . '</p></div></div>';
		return;
	}

	?>
	<div class="wrap">
		<h1><?php _e( 'VK Dynamic If Block Migration', 'vk-dynamic-if-block' ); ?></h1>
		<p><?php _e( 'Please perform bulk migration on the following pages.', 'vk-dynamic-if-block' ); ?></p>

		<form method="post" action="">
			<?php wp_nonce_field( 'vk_migration_bulk_action', 'vk_migration_nonce' ); ?>

			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="vk_bulk_action">
						<option value="-1"><?php _e( 'Bulk Actions', 'vk-dynamic-if-block' ); ?></option>
						<option value="vk_migrate_blocks"><?php _e( 'VK Dynamic If Block Migration', 'vk-dynamic-if-block' ); ?></option>
					</select>
					<input type="submit" class="button action" value="<?php _e( 'Apply', 'vk-dynamic-if-block' ); ?>">
				</div>
				<div class="alignright">
					<span class="displaying-num"><?php printf( __( '%d items', 'vk-dynamic-if-block' ), count( $posts ) ); ?></span>
				</div>
				<br class="clear">
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="check-column">
							<input type="checkbox" id="cb-select-all-1">
						</th>
						<th><?php _e( 'Title', 'vk-dynamic-if-block' ); ?></th>
						<th><?php _e( 'Post Type', 'vk-dynamic-if-block' ); ?></th>
						<th><?php _e( 'Status', 'vk-dynamic-if-block' ); ?></th>
						<th><?php _e( 'Actions', 'vk-dynamic-if-block' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts as $post ) : ?>
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
									<?php _e( 'Edit', 'vk-dynamic-if-block' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="tablenav bottom">
				<div class="alignleft actions bulkactions">
					<select name="vk_bulk_action2">
						<option value="-1"><?php _e( 'Bulk Actions', 'vk-dynamic-if-block' ); ?></option>
						<option value="vk_migrate_blocks"><?php _e( 'VK Dynamic If Block Migration', 'vk-dynamic-if-block' ); ?></option>
					</select>
					<input type="submit" class="button action" value="<?php _e( 'Apply', 'vk-dynamic-if-block' ); ?>">
				</div>
				<div class="alignright">
					<span class="displaying-num"><?php printf( __( '%d items', 'vk-dynamic-if-block' ), count( $posts ) ); ?></span>
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
 *
 * @return void
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
		wp_die( __( 'Please select migration targets.', 'vk-dynamic-if-block' ) );
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

		$result = wp_update_post(
			array(
				'ID' => $post_id,
				'post_content' => $updated_content
			)
		);

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
 *
 * @return void
 */
function vk_dynamic_if_block_show_migration_result() {
	if ( ! isset( $_SESSION['vk_migration_result'] ) ) {
		return;
	}

	$result = $_SESSION['vk_migration_result'];
	unset( $_SESSION['vk_migration_result'] );

	$message = '';
	if ( $result['migrated'] > 0 ) {
		$message .= sprintf( __( '%d pages migrated.', 'vk-dynamic-if-block' ), $result['migrated'] );
	}
	if ( $result['failed'] > 0 ) {
		$message .= sprintf( __( '%d migrations failed.', 'vk-dynamic-if-block' ), $result['failed'] );
	}

	if ( $message ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'vk_dynamic_if_block_show_migration_result' ); 