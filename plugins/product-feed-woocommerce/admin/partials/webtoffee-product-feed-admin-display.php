<?php
/**
 * Admin display area
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro\Admin\Partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wf_admin_view_path = WT_PRODUCT_FEED_PRO_PLUGIN_PATH . 'admin/views/';
$wf_img_path = WT_PRODUCT_FEED_PRO_PLUGIN_URL . 'images/';
?>
<div class="wrap" id="<?php echo esc_html( WEBTOFFEE_PRODUCT_FEED_PRO_ID ); ?>">
	<h2 class="wp-heading-inline">
	<?php esc_html_e( 'Settings', 'product-feed-woocommerce' ); ?>
	</h2>

<?php
	// Get the active tab from the $_GET param.
	$default_tab = null;
	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $default_tab; // phpcs:ignore WordPress.Security.NonceVerification
?>
	<nav class="nav-tab-wrapper">        
	  <a href="?page=webtoffee_product_feed_main_pro_export" class="nav-tab 
	  <?php
		if ( null === $current_tab ) :
			?>
			nav-tab-active
			<?php
	 endif;
		?>
	 ">Create Feed</a>      
	  <a href="?page=webtoffee_product_feed_main_pro_export&tab=history" class="nav-tab 
	  <?php
		if ( 'history' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	 endif;
		?>
	 ">Manage Feeds</a>
	  <a href="?page=webtoffee_product_feed_main_pro_export&tab=cron" class="nav-tab 
	  <?php
		if ( 'cron' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	 endif;
		?>
	 ">Scheduled actions</a>
	  <a href="?page=webtoffee_product_feed_main_pro_export&tab=settings" class="nav-tab 
	  <?php
		if ( 'settings' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	 endif;
		?>
	 ">Settings</a>
	</nav>    

	<div class="wt-pfd-tab-container">
		<?php
		// Inside the settings form.
		$setting_views_a = array(
			'wt-advanced' => 'admin-settings-advanced.php',
		);

		// Outside the settings form.
		$setting_views_b = array();

		if ( isset( $_GET['debug'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$setting_views_b['wt-debug'] = 'admin-settings-debug.php';
		}
		?>
		<form method="post" class="wt_pf_settings_form_basic">
			<?php
			// Set nonce:.
			if ( function_exists( 'wp_nonce_field' ) ) {
				wp_nonce_field( WEBTOFFEE_PRODUCT_FEED_PRO_ID );
			}
			foreach ( $setting_views_a as $target_id => $value ) {
				$settings_view = $wf_admin_view_path . $value;
				if ( file_exists( $settings_view ) ) {
					include $settings_view;
				}
			}
			?>
			<?php
			// Settings form fields for module.
				/**
				 * After settings form.
				 *
				 * Enables adding extra arguments or setting defaults for a post
				 * collection request.
				 *
				 * @since 1.0.0
				 */
			do_action( 'wt_pf_plugin_settings_pro_form' );
			?>
					   
		</form>
		<?php
		foreach ( $setting_views_b as $target_id => $value ) {
			$settings_view = $wf_admin_view_path . $value;
			if ( file_exists( $settings_view ) ) {
				include $settings_view;
			}
		}
		?>
			
		<?php
				/**
				 * After settings form.
				 *
				 * Enables adding extra arguments or setting defaults for a post
				 * collection request.
				 *
				 * @since 1.0.0
				 */
				do_action( 'wt_pf_plugin_out_settings_pro_form' );
		?>
				 
	</div>
</div>
