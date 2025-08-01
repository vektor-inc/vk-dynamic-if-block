<?php
/**
 * VK Dynamic If Block Migration Functions
 *
 * @package VK Dynamic If Block
 */

defined('ABSPATH') || exit;

/**
 * アクティベーション時・アップデート時の移行処理
 */
function vk_dynamic_if_block_migrate_old_blocks_on_activation() {
	global $wpdb;
	
	error_log( "VK Dynamic If Block: Starting migration function..." );
	
	// VK Dynamic If Blockを使用している投稿を取得
	$posts = $wpdb->get_results("
		SELECT ID, post_content, post_title 
		FROM {$wpdb->posts} 
		WHERE post_content LIKE '%vk-blocks/dynamic-if%'
		AND post_status IN ('publish', 'draft', 'private')
	");
	
	error_log( "VK Dynamic If Block: Found " . count( $posts ) . " posts with dynamic-if blocks" );
	
	$migrated_count = 0;
	
	foreach ( $posts as $post ) {
		$original_content = $post->post_content;
		$updated_content = $original_content;
		
		// ブロックの正規表現パターン - より柔軟なパターンに変更
		$pattern = '/<!-- wp:vk-blocks\/dynamic-if\s+(\{[^}]*\})\s+-->/';
		
		if ( preg_match_all( $pattern, $original_content, $matches, PREG_OFFSET_CAPTURE ) ) {
			error_log( "VK Dynamic If Block: Found " . count( $matches[0] ) . " blocks in post ID: " . $post->ID . " - " . $post->post_title );
			
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
					error_log( "VK Dynamic If Block: Skipping block - attributes_json is not string: " . var_export( $attributes_json, true ) );
					continue;
				}
				
				error_log( "VK Dynamic If Block: Processing attributes JSON: " . $attributes_json );
				
				$attributes = json_decode( $attributes_json, true );
				
				if ( $attributes ) {
					error_log( "VK Dynamic If Block: Successfully decoded attributes: " . json_encode( $attributes ) );
					
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
					$found_old_attributes = [];
					foreach ( $old_attributes as $attr ) {
						if ( isset( $attributes[ $attr ] ) && ! empty( $attributes[ $attr ] ) && $attributes[ $attr ] !== 'none' ) {
							$has_old_attributes = true;
							$found_old_attributes[] = $attr;
							error_log( "VK Dynamic If Block: Found old attribute: {$attr} = " . $attributes[ $attr ] );
						}
					}
					
					if ( $has_old_attributes ) {
						error_log( "VK Dynamic If Block: Migrating block in post ID: " . $post->ID . " with old attributes: " . implode( ', ', $found_old_attributes ) );
						
						// 移行処理を実行
						$migrated_conditions = vk_dynamic_if_block_migrate_old_attributes( $attributes );
						$attributes['conditions'] = $migrated_conditions;
						
						// 古い属性を削除
						foreach ( $old_attributes as $attr ) {
							unset( $attributes[ $attr ] );
						}
						
						// 新しい属性でJSONを生成
						$new_attributes_json = json_encode( $attributes );
						error_log( "VK Dynamic If Block: New attributes JSON: " . $new_attributes_json );
						
						// 投稿の内容を更新
						$updated_content = substr_replace(
							$updated_content,
							'<!-- wp:vk-blocks/dynamic-if ' . $new_attributes_json . ' -->',
							$full_match_offset,
							strlen( $full_match )
						);
						
						$migrated_count++;
						error_log( "VK Dynamic If Block: Successfully migrated block in post ID: " . $post->ID );
					} else {
						error_log( "VK Dynamic If Block: No old attributes found in block" );
					}
				} else {
					error_log( "VK Dynamic If Block: Failed to decode attributes JSON: " . json_last_error_msg() );
				}
			}
			
			// 内容が変更された場合のみ更新
			if ( $updated_content !== $original_content ) {
				$result = wp_update_post( array(
					'ID' => $post->ID,
					'post_content' => $updated_content
				) );
				
				if ( is_wp_error( $result ) ) {
					error_log( "VK Dynamic If Block: Failed to update post ID: " . $post->ID . " - " . $result->get_error_message() );
				} else {
					error_log( "VK Dynamic If Block: Successfully updated post ID: " . $post->ID );
				}
			} else {
				error_log( "VK Dynamic If Block: No changes made to post ID: " . $post->ID );
			}
		} else {
			error_log( "VK Dynamic If Block: No blocks found in post ID: " . $post->ID );
		}
	}
	
	// 移行完了をログに記録
	if ( $migrated_count > 0 ) {
		error_log( "VK Dynamic If Block: {$migrated_count} blocks migrated successfully." );
	} else {
		error_log( "VK Dynamic If Block: No blocks were migrated." );
	}
	
	// 移行完了フラグを設定（移行対象がなくても完了とする）
	update_option( 'vk_dynamic_if_block_migration_completed', true );
}

/**
 * Migrate old attributes to new conditions format
 *
 * @param array $attributes Old attributes array.
 * @return array Migrated conditions array.
 */
function vk_dynamic_if_block_migrate_old_attributes( $attributes ) {
	$conditions = array();

	// Page Type Condition
	if ( isset( $attributes['ifPageType'] ) && ! empty( $attributes['ifPageType'] ) && 'none' !== $attributes['ifPageType'] ) {
		$conditions[] = array(
			'id' => 'migrated_page_type_' . time(),
			'type' => 'pageType',
			'values' => array(
				'ifPageType' => $attributes['ifPageType']
			)
		);
	}

	// Post Type Condition
	if ( isset( $attributes['ifPostType'] ) && ! empty( $attributes['ifPostType'] ) && 'none' !== $attributes['ifPostType'] ) {
		$conditions[] = array(
			'id' => 'migrated_post_type_' . time(),
			'type' => 'postType',
			'values' => array(
				'ifPostType' => $attributes['ifPostType']
			)
		);
	}

	// Language Condition
	if ( isset( $attributes['ifLanguage'] ) && ! empty( $attributes['ifLanguage'] ) && 'none' !== $attributes['ifLanguage'] ) {
		$conditions[] = array(
			'id' => 'migrated_language_' . time(),
			'type' => 'language',
			'values' => array(
				'ifLanguage' => $attributes['ifLanguage']
			)
		);
	}

	// User Role Condition
	if ( isset( $attributes['userRole'] ) && ! empty( $attributes['userRole'] ) ) {
		$conditions[] = array(
			'id' => 'migrated_user_role_' . time(),
			'type' => 'userRole',
			'values' => array(
				'userRole' => $attributes['userRole']
			)
		);
	}

	// Post Author Condition
	if ( isset( $attributes['postAuthor'] ) && ! empty( $attributes['postAuthor'] ) && 0 !== $attributes['postAuthor'] ) {
		$conditions[] = array(
			'id' => 'migrated_post_author_' . time(),
			'type' => 'postAuthor',
			'values' => array(
				'postAuthor' => $attributes['postAuthor']
			)
		);
	}

	// Custom Field Condition
	if ( isset( $attributes['customFieldName'] ) && ! empty( $attributes['customFieldName'] ) ) {
		$custom_field_condition = array(
			'id' => 'migrated_custom_field_' . time(),
			'type' => 'customField',
			'values' => array(
				'customFieldName' => $attributes['customFieldName']
			)
		);

		// Custom Field Rule
		if ( isset( $attributes['customFieldRule'] ) && ! empty( $attributes['customFieldRule'] ) ) {
			$custom_field_condition['values']['customFieldRule'] = $attributes['customFieldRule'];
		}

		// Custom Field Value
		if ( isset( $attributes['customFieldValue'] ) && ! empty( $attributes['customFieldValue'] ) ) {
			$custom_field_condition['values']['customFieldValue'] = $attributes['customFieldValue'];
		}

		$conditions[] = $custom_field_condition;
	}

	// Period Display Condition
	if ( isset( $attributes['periodDisplaySetting'] ) && ! empty( $attributes['periodDisplaySetting'] ) && 'none' !== $attributes['periodDisplaySetting'] ) {
		$period_condition = array(
			'id' => 'migrated_period_' . time(),
			'type' => 'period',
			'values' => array(
				'periodDisplaySetting' => $attributes['periodDisplaySetting']
			)
		);

		// Period Specification Method
		if ( isset( $attributes['periodSpecificationMethod'] ) && ! empty( $attributes['periodSpecificationMethod'] ) ) {
			$period_condition['values']['periodSpecificationMethod'] = $attributes['periodSpecificationMethod'];
		}

		// Period Display Value
		if ( isset( $attributes['periodDisplayValue'] ) && ! empty( $attributes['periodDisplayValue'] ) ) {
			$period_condition['values']['periodDisplayValue'] = $attributes['periodDisplayValue'];
		}

		// Period Refer Custom Field
		if ( isset( $attributes['periodReferCustomField'] ) && ! empty( $attributes['periodReferCustomField'] ) ) {
			$period_condition['values']['periodReferCustomField'] = $attributes['periodReferCustomField'];
		}

		$conditions[] = $period_condition;
	}

	// Show Only Login User Condition
	if ( isset( $attributes['showOnlyLoginUser'] ) && ! empty( $attributes['showOnlyLoginUser'] ) ) {
		$conditions[] = array(
			'id' => 'migrated_login_user_' . time(),
			'type' => 'loginUser',
			'values' => array(
				'showOnlyLoginUser' => $attributes['showOnlyLoginUser']
			)
		);
	}

	return $conditions;
} 