<?php
/**
 * VK Dynamic If Block 一括移行スクリプト
 * 
 * 使用方法：
 * 1. このファイルをプラグインディレクトリに配置
 * 2. ブラウザで https://your-site.com/wp-content/plugins/vk-dynamic-if-block/migrate-old-blocks.php にアクセス
 * 3. 移行が完了したら、このファイルを削除
 */

// WordPressを読み込み
require_once('../../../wp-load.php');

// 移行処理ファイルを読み込み
require_once('inc/migration.php');

// 管理者権限チェック
if (!current_user_can('manage_options')) {
    wp_die('管理者権限が必要です。');
}

// 移行処理の実行
function migrate_vk_dynamic_if_blocks() {
    global $wpdb;
    
    // VK Dynamic If Blockを使用している投稿を取得
    $posts = $wpdb->get_results("
        SELECT ID, post_content, post_title, post_type
        FROM {$wpdb->posts} 
        WHERE post_content LIKE '%vk-blocks/dynamic-if%'
        AND post_status IN ('publish', 'draft', 'private')
    ");
    
    // ページテンプレートも取得
    $page_templates = $wpdb->get_results("
        SELECT ID, post_content, post_title, post_type
        FROM {$wpdb->posts} 
        WHERE post_content LIKE '%vk-blocks/dynamic-if%'
        AND post_type = 'page'
        AND post_status IN ('publish', 'draft', 'private')
    ");
    
    // テーマファイルも検索（wp_optionsテーブルのtheme_mods_*オプション）
    $theme_mods = $wpdb->get_results("
        SELECT option_name, option_value
        FROM {$wpdb->options}
        WHERE option_name LIKE 'theme_mods_%'
        AND option_value LIKE '%vk-blocks/dynamic-if%'
    ");
    
    // テーマファイル自体も検索
    $theme_files = [];
    $theme_dir = get_template_directory();
    $theme_files = search_blocks_in_theme_files($theme_dir);
    
    $migrated_count = 0;
    $error_count = 0;
    
    echo "<h2>VK Dynamic If Block 一括移行</h2>";
    echo "<p>対象投稿数: " . count($posts) . "</p>";
    echo "<p>対象ページ数: " . count($page_templates) . "</p>";
    echo "<p>対象テーマ設定数: " . count($theme_mods) . "</p>";
    echo "<p>対象テーマファイル数: " . count($theme_files) . "</p>";
    
    // 投稿の処理
    echo "<h3>投稿の処理</h3>";
    foreach ($posts as $post) {
        echo "<h4>投稿「{$post->post_title}」(ID: {$post->ID}, タイプ: {$post->post_type})</h4>";
        
        $original_content = $post->post_content;
        $updated_content = $original_content;
        
        // 内容の一部を表示（デバッグ用）
        $content_preview = substr($original_content, 0, 500);
        echo "<p><strong>内容プレビュー:</strong> " . htmlspecialchars($content_preview) . "...</p>";
        
        // 動的ifブロックの存在確認
        if (strpos($original_content, 'vk-blocks/dynamic-if') !== false) {
            echo "<p style='color: green;'>✓ 動的ifブロックの文字列を発見</p>";
        } else {
            echo "<p style='color: orange;'>⚠ 動的ifブロックの文字列が見つかりません</p>";
        }
        
        // ブロックの正規表現パターン
        $pattern = '/<!-- wp:vk-blocks\/dynamic-if\s+(\{[^}]*\})\s+-->/';
        
        if (preg_match_all($pattern, $original_content, $matches, PREG_OFFSET_CAPTURE)) {
            echo "<p>ブロック数: " . count($matches[0]) . "</p>";
            
            // 後ろから処理（オフセットが変わらないように）
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $full_match_data = $matches[0][$i];
                $attributes_json_data = $matches[1][$i];
                
                // PREG_OFFSET_CAPTUREフラグにより、配列の要素を取得
                if (is_array($full_match_data)) {
                    $full_match = $full_match_data[0];
                    $full_match_offset = $full_match_data[1];
                } else {
                    $full_match = $full_match_data;
                    $full_match_offset = 0;
                }
                
                if (is_array($attributes_json_data)) {
                    $attributes_json = $attributes_json_data[0];
                } else {
                    $attributes_json = $attributes_json_data;
                }
                
                echo "<p><strong>ブロック " . ($i + 1) . ":</strong></p>";
                echo "<p>完全一致: " . htmlspecialchars($full_match) . "</p>";
                echo "<p>属性JSON: " . htmlspecialchars($attributes_json) . "</p>";
                
                // 文字列であることを確認
                if (!is_string($attributes_json)) {
                    echo "<p style='color: red;'>✗ 属性JSONが文字列ではありません: " . var_export($attributes_json, true) . "</p>";
                    continue;
                }
                
                $attributes = json_decode($attributes_json, true);
                
                if ($attributes) {
                    echo "<p>デコードされた属性: " . htmlspecialchars(json_encode($attributes, JSON_PRETTY_PRINT)) . "</p>";
                    
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
                    foreach ($old_attributes as $attr) {
                        if (isset($attributes[$attr]) && !empty($attributes[$attr]) && $attributes[$attr] !== 'none') {
                            $has_old_attributes = true;
                            $found_old_attributes[] = $attr;
                            echo "<p style='color: blue;'>✓ 古い属性を発見: {$attr} = " . $attributes[$attr] . "</p>";
                        }
                    }
                    
                    if ($has_old_attributes) {
                        echo "<p style='color: green;'>✓ 移行対象の古い属性を発見: " . implode(', ', $found_old_attributes) . "</p>";
                        
                        // 移行処理を実行
                        $migrated_conditions = vk_dynamic_if_block_migrate_old_attributes($attributes);
                        $attributes['conditions'] = $migrated_conditions;
                        
                        // 古い属性を削除
                        foreach ($old_attributes as $attr) {
                            unset($attributes[$attr]);
                        }
                        
                        // 新しい属性でJSONを生成
                        $new_attributes_json = json_encode($attributes);
                        echo "<p>新しい属性JSON: " . htmlspecialchars($new_attributes_json) . "</p>";
                        
                        // 投稿の内容を更新
                        $updated_content = substr_replace(
                            $updated_content,
                            '<!-- wp:vk-blocks/dynamic-if ' . $new_attributes_json . ' -->',
                            $full_match_offset,
                            strlen($full_match)
                        );
                        
                        $migrated_count++;
                        echo "<p style='color: green;'>✓ ブロックを移行しました。</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠ 古い属性が見つかりませんでした。</p>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ 属性JSONのデコードに失敗: " . json_last_error_msg() . "</p>";
                }
            }
            
            // 内容が変更された場合のみ更新
            if ($updated_content !== $original_content) {
                $result = wp_update_post(array(
                    'ID' => $post->ID,
                    'post_content' => $updated_content
                ));
                
                if (is_wp_error($result)) {
                    $error_count++;
                    echo "<p style='color: red;'>✗ 投稿の更新に失敗しました: " . $result->get_error_message() . "</p>";
                } else {
                    echo "<p style='color: green;'>✓ 投稿を更新しました。</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠ 投稿の内容に変更がありませんでした。</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ 動的ifブロックが見つかりませんでした。</p>";
            
            // 正規表現パターンの問題をデバッグ
            echo "<p><strong>デバッグ情報:</strong></p>";
            echo "<p>正規表現パターン: " . htmlspecialchars($pattern) . "</p>";
            
            // 別のパターンでも試してみる
            $alt_pattern = '/<!-- wp:vk-blocks\/dynamic-if\s*(\{.*?\})\s*-->/s';
            if (preg_match_all($alt_pattern, $original_content, $alt_matches, PREG_OFFSET_CAPTURE)) {
                echo "<p style='color: blue;'>✓ 代替パターンで " . count($alt_matches[0]) . " 個のブロックを発見</p>";
                foreach ($alt_matches[0] as $i => $match) {
                    echo "<p>代替パターンブロック " . ($i + 1) . ": " . htmlspecialchars($match[0]) . "</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠ 代替パターンでもブロックが見つかりませんでした。</p>";
            }
        }
        
        echo "<hr>";
    }
    
    // ページテンプレートの処理
    echo "<h3>ページテンプレートの処理</h3>";
    foreach ($page_templates as $page) {
        echo "<h4>ページ「{$page->post_title}」(ID: {$page->ID})</h4>";
        
        $original_content = $page->post_content;
        $updated_content = $original_content;
        
        // ブロックの正規表現パターン
        $pattern = '/<!-- wp:vk-blocks\/dynamic-if\s+(\{[^}]*\})\s+-->/';
        
        if (preg_match_all($pattern, $original_content, $matches, PREG_OFFSET_CAPTURE)) {
            echo "<p>ブロック数: " . count($matches[0]) . "</p>";
            
            // 後ろから処理（オフセットが変わらないように）
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $full_match_data = $matches[0][$i];
                $attributes_json_data = $matches[1][$i];
                
                // PREG_OFFSET_CAPTUREフラグにより、配列の要素を取得
                if (is_array($full_match_data)) {
                    $full_match = $full_match_data[0];
                    $full_match_offset = $full_match_data[1];
                } else {
                    $full_match = $full_match_data;
                    $full_match_offset = 0;
                }
                
                if (is_array($attributes_json_data)) {
                    $attributes_json = $attributes_json_data[0];
                } else {
                    $attributes_json = $attributes_json_data;
                }
                
                echo "<p><strong>ブロック " . ($i + 1) . ":</strong></p>";
                echo "<p>完全一致: " . htmlspecialchars($full_match) . "</p>";
                echo "<p>属性JSON: " . htmlspecialchars($attributes_json) . "</p>";
                
                // 文字列であることを確認
                if (!is_string($attributes_json)) {
                    echo "<p style='color: red;'>✗ 属性JSONが文字列ではありません: " . var_export($attributes_json, true) . "</p>";
                    continue;
                }
                
                $attributes = json_decode($attributes_json, true);
                
                if ($attributes) {
                    echo "<p>デコードされた属性: " . htmlspecialchars(json_encode($attributes, JSON_PRETTY_PRINT)) . "</p>";
                    
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
                    foreach ($old_attributes as $attr) {
                        if (isset($attributes[$attr]) && !empty($attributes[$attr]) && $attributes[$attr] !== 'none') {
                            $has_old_attributes = true;
                            $found_old_attributes[] = $attr;
                            echo "<p style='color: blue;'>✓ 古い属性を発見: {$attr} = " . $attributes[$attr] . "</p>";
                        }
                    }
                    
                    if ($has_old_attributes) {
                        echo "<p style='color: green;'>✓ 移行対象の古い属性を発見: " . implode(', ', $found_old_attributes) . "</p>";
                        
                        // 移行処理を実行
                        $migrated_conditions = vk_dynamic_if_block_migrate_old_attributes($attributes);
                        $attributes['conditions'] = $migrated_conditions;
                        
                        // 古い属性を削除
                        foreach ($old_attributes as $attr) {
                            unset($attributes[$attr]);
                        }
                        
                        // 新しい属性でJSONを生成
                        $new_attributes_json = json_encode($attributes);
                        echo "<p>新しい属性JSON: " . htmlspecialchars($new_attributes_json) . "</p>";
                        
                        // 投稿の内容を更新
                        $updated_content = substr_replace(
                            $updated_content,
                            '<!-- wp:vk-blocks/dynamic-if ' . $new_attributes_json . ' -->',
                            $full_match_offset,
                            strlen($full_match)
                        );
                        
                        $migrated_count++;
                        echo "<p style='color: green;'>✓ ブロックを移行しました。</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠ 古い属性が見つかりませんでした。</p>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ 属性JSONのデコードに失敗: " . json_last_error_msg() . "</p>";
                }
            }
            
            // 内容が変更された場合のみ更新
            if ($updated_content !== $original_content) {
                $result = wp_update_post(array(
                    'ID' => $page->ID,
                    'post_content' => $updated_content
                ));
                
                if (is_wp_error($result)) {
                    $error_count++;
                    echo "<p style='color: red;'>✗ ページの更新に失敗しました: " . $result->get_error_message() . "</p>";
                } else {
                    echo "<p style='color: green;'>✓ ページを更新しました。</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠ ページの内容に変更がありませんでした。</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ 動的ifブロックが見つかりませんでした。</p>";
        }
        
        echo "<hr>";
    }
    
    // テーマファイルの処理
    echo "<h3>テーマファイルの処理</h3>";
    foreach ($theme_files as $theme_file) {
        echo "<h4>テーマファイル: " . basename($theme_file['path']) . "</h4>";
        
        $content = $theme_file['content'];
        
        // ブロックの正規表現パターン
        $pattern = '/<!-- wp:vk-blocks\/dynamic-if\s+(\{[^}]*\})\s+-->/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            echo "<p>ブロック数: " . count($matches[0]) . "</p>";
            
            // 後ろから処理（オフセットが変わらないように）
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $full_match_data = $matches[0][$i];
                $attributes_json_data = $matches[1][$i];
                
                // PREG_OFFSET_CAPTUREフラグにより、配列の要素を取得
                if (is_array($full_match_data)) {
                    $full_match = $full_match_data[0];
                    $full_match_offset = $full_match_data[1];
                } else {
                    $full_match = $full_match_data;
                    $full_match_offset = 0;
                }
                
                if (is_array($attributes_json_data)) {
                    $attributes_json = $attributes_json_data[0];
                } else {
                    $attributes_json = $attributes_json_data;
                }
                
                echo "<p><strong>ブロック " . ($i + 1) . ":</strong></p>";
                echo "<p>完全一致: " . htmlspecialchars($full_match) . "</p>";
                echo "<p>属性JSON: " . htmlspecialchars($attributes_json) . "</p>";
                
                // 文字列であることを確認
                if (!is_string($attributes_json)) {
                    echo "<p style='color: red;'>✗ 属性JSONが文字列ではありません: " . var_export($attributes_json, true) . "</p>";
                    continue;
                }
                
                $attributes = json_decode($attributes_json, true);
                
                if ($attributes) {
                    echo "<p>デコードされた属性: " . htmlspecialchars(json_encode($attributes, JSON_PRETTY_PRINT)) . "</p>";
                    
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
                    foreach ($old_attributes as $attr) {
                        if (isset($attributes[$attr]) && !empty($attributes[$attr]) && $attributes[$attr] !== 'none') {
                            $has_old_attributes = true;
                            $found_old_attributes[] = $attr;
                            echo "<p style='color: blue;'>✓ 古い属性を発見: {$attr} = " . $attributes[$attr] . "</p>";
                        }
                    }
                    
                    if ($has_old_attributes) {
                        echo "<p style='color: green;'>✓ 移行対象の古い属性を発見: " . implode(', ', $found_old_attributes) . "</p>";
                        
                        // 移行処理を実行
                        $migrated_conditions = vk_dynamic_if_block_migrate_old_attributes($attributes);
                        $attributes['conditions'] = $migrated_conditions;
                        
                        // 古い属性を削除
                        foreach ($old_attributes as $attr) {
                            unset($attributes[$attr]);
                        }
                        
                        // 新しい属性でJSONを生成
                        $new_attributes_json = json_encode($attributes);
                        echo "<p>新しい属性JSON: " . htmlspecialchars($new_attributes_json) . "</p>";
                        
                        // ファイルの内容を更新
                        $content = substr_replace(
                            $content,
                            '<!-- wp:vk-blocks/dynamic-if ' . $new_attributes_json . ' -->',
                            $full_match_offset,
                            strlen($full_match)
                        );
                        
                        $migrated_count++;
                        echo "<p style='color: green;'>✓ ブロックを移行しました。</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠ 古い属性が見つかりませんでした。</p>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ 属性JSONのデコードに失敗: " . json_last_error_msg() . "</p>";
                }
            }
            
            // 内容が変更された場合のみファイルを更新
            if ($content !== $theme_file['content']) {
                $result = file_put_contents($theme_file['path'], $content);
                
                if ($result === false) {
                    $error_count++;
                    echo "<p style='color: red;'>✗ テーマファイルの更新に失敗しました。</p>";
                } else {
                    echo "<p style='color: green;'>✓ テーマファイルを更新しました。</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠ テーマファイルの内容に変更がありませんでした。</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ 動的ifブロックが見つかりませんでした。</p>";
        }
        
        echo "<hr>";
    }
    
    // テーマ設定の処理
    echo "<h3>テーマ設定の処理</h3>";
    foreach ($theme_mods as $theme_mod) {
        echo "<h4>テーマ設定: {$theme_mod->option_name}</h4>";
        
        $theme_data = maybe_unserialize($theme_mod->option_value);
        
        if (is_array($theme_data)) {
            // テーマデータ内で動的ifブロックを検索
            $found_blocks = search_blocks_in_array($theme_data);
            
            if (!empty($found_blocks)) {
                echo "<p>テーマ設定内で動的ifブロックを発見: " . count($found_blocks) . "個</p>";
                
                foreach ($found_blocks as $block_info) {
                    echo "<p>ブロック位置: " . $block_info['path'] . "</p>";
                    echo "<p>ブロック内容: " . htmlspecialchars($block_info['content']) . "</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠ テーマ設定内に動的ifブロックが見つかりませんでした。</p>";
            }
        }
        
        echo "<hr>";
    }
    
    echo "<h3>移行完了</h3>";
    echo "<p>移行されたブロック数: {$migrated_count}</p>";
    echo "<p>エラー数: {$error_count}</p>";
    
    if ($error_count === 0) {
        echo "<p style='color: green;'>✓ すべての移行が正常に完了しました。</p>";
        echo "<p><strong>このファイルを削除してください。</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ 一部の移行でエラーが発生しました。</p>";
    }
}

// 配列内で動的ifブロックを検索する関数
function search_blocks_in_array($array, $path = '') {
    $found_blocks = [];
    
    foreach ($array as $key => $value) {
        $current_path = $path ? $path . '.' . $key : $key;
        
        if (is_array($value)) {
            $found_blocks = array_merge($found_blocks, search_blocks_in_array($value, $current_path));
        } elseif (is_string($value) && strpos($value, 'vk-blocks/dynamic-if') !== false) {
            $found_blocks[] = [
                'path' => $current_path,
                'content' => $value
            ];
        }
    }
    
    return $found_blocks;
}

// テーマファイル内で動的ifブロックを検索する関数
function search_blocks_in_theme_files($theme_dir) {
    $found_blocks = [];
    $files = array_diff(scandir($theme_dir), array('.', '..'));
    
    foreach ($files as $file) {
        if (is_dir($theme_dir . '/' . $file)) {
            $found_blocks = array_merge($found_blocks, search_blocks_in_theme_files($theme_dir . '/' . $file));
        } else {
            $content = file_get_contents($theme_dir . '/' . $file);
            if (strpos($content, 'vk-blocks/dynamic-if') !== false) {
                $found_blocks[] = [
                    'path' => $theme_dir . '/' . $file,
                    'content' => $content
                ];
            }
        }
    }
    
    return $found_blocks;
}

// 移行処理の実行
if (isset($_GET['migrate']) && $_GET['migrate'] === '1') {
    migrate_vk_dynamic_if_blocks();
} else {
    echo "<h2>VK Dynamic If Block 一括移行ツール</h2>";
    echo "<p>このツールは、VK Dynamic If Blockの古い形式のデータを新しい形式に移行します。</p>";
    echo "<p><strong>注意：</strong>移行前にデータベースのバックアップを取ることをお勧めします。</p>";
    echo "<p><a href='?migrate=1' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>移行を実行</a></p>";
}
?> 