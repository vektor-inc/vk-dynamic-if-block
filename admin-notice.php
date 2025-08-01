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
		<h3 style="margin-top:0;"><?php esc_html_e( 'Important Notice from VK Dynamic If Block', 'vk-dynamic-if-block' ); ?></h3>
		<p style="margin-bottom:1rem;">
			<?php echo esc_html__( 'In the next scheduled release, from VK Dynamic If Block Version 1.0, the way to specify conditional branching will change to allow combining multiple conditions.', 'vk-dynamic-if-block' ); ?><br>
			<?php echo esc_html__( 'However, due to the structure of the block editor, simply updating the plugin will cause conditional branching to stop working.', 'vk-dynamic-if-block' ); ?><br>
			<?php echo esc_html__( 'After updating the plugin to version 1.0 or later, please open and save the edit screen where VK Dynamic If Block is placed.', 'vk-dynamic-if-block' ); ?><br>
			<?php echo esc_html__( 'Ahead of the official release of the new version, it is available for download below.', 'vk-dynamic-if-block' ); ?><br>
			<?php echo esc_html__( 'Please manually upload and activate it from "Plugins > Add New > Upload Plugin", then open the template that uses the VK Dynamic If Block in the block editor and save it.', 'vk-dynamic-if-block' ); ?>
		</p>
		<a href="https://github.com/vektor-inc/vk-dynamic-if-block/releases" target="_blank" rel="noreferrer noopener" class="button button-primary"><?php echo esc_html__( 'Download', 'vk-dynamic-if-block' ); ?></a>
	</div>
	<?php
} );
