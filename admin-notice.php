<?php
/**
 * Admin notice for VK Dynamic If Block important update
 */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible" style="padding:1.2rem;">
		<h3 style="margin-top:0;"><?php esc_html_e( 'VK Dynamic If Block から重要なお知らせ', 'vk-dynamic-if-block' ); ?></h2>
		<p>
			<?php echo esc_html__( 'VK Dynamic If Block Version 1.0 から、複数の条件を組み合わせられるように条件分岐の指定方法が変更されます。', 'vk-dynamic-if-block' ); ?><br>
			<?php echo esc_html__( 'しかしながら、プラグインをアップデートしただけではブロックエディタの構造上の都合で条件分岐が効かなくなります。', 'vk-dynamic-if-block' ); ?><br>
			<?php echo esc_html__( 'プラグインをアップデートした上で VK Dynamic If Block を配置している編集画面を一度開いて保存してください。', 'vk-dynamic-if-block' ); ?><br>
			<?php echo esc_html__( 'つきましては、新バージョンの一般リリースに先立ち、以下よりダウンロードできるようにしてあります。', 'vk-dynamic-if-block' ); ?><br>
			<?php echo esc_html__( '手動にて「プラグイン > 新規追加 > ファイルのアップロード」からアップロード・有効化した上で、VK Dynamic If Block を利用しているテンプレートファイルなどを開いて保存してください。', 'vk-dynamic-if-block' ); ?>
		</p>
		<a href="https://downloads.wordpress.org/plugin/vk-dynamic-if-block.1.0.0.zip" target="_blank" class="button button-primary"><?php echo esc_html__( 'Download', 'vk-dynamic-if-block' ); ?></a>
	</div>
	<?php
} );
