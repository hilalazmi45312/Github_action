<?php
/**
 * Schedule listing settings
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style type="text/css">
.wt_productfeed_cron_settings_page{ padding:15px; }
.cron_list_tb td, .cron_list_tb th{ text-align:center; vertical-align:middle; }
.wt_productfeed_delete_cron{ cursor:pointer; }

.wt_productfeed_cron_current_time{float:right; width:auto; font-size:12px; font-weight:normal;}
.wt_productfeed_cron_current_time span{ display:inline-block; width:85px; }
.cron_list_tb td a{ cursor:pointer; }
</style>
<div class="wrap">
	<h2 class="wp-heading-inline">
	<?php esc_html_e( 'Product Feed', 'product-feed-woocommerce' ); ?>
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
<div class="wt_productfeed_cron_settings_page">
	<h2 class="wp-heading-inline">
	<?php
	esc_html_e(
		'Scheduled Feeds',
		'product-feed-woocommerce'
	);
	?>
	 
		<div class="wt_productfeed_cron_current_time"><b><?php esc_html_e( 'Current server time:', 'product-feed-woocommerce' ); ?></b> <span>--:--:-- --</span><br/>
		</div>
	</h2>
	<p>
		<?php
		esc_html_e(
			'Lists all the scheduled processes.',
			'product-feed-woocommerce'
		);
		?>
		<br />
		<?php
		esc_html_e(
			'Disable or delete unwanted scheduled actions to reduce server load and reduce the chances for failure of actively scheduled actions.',
			'product-feed-woocommerce'
		);
		?>
	</p>
	<?php
	Webtoffee_Product_Feed_Sync_Pro_Cron::list_cron();
	?>
</div>
</div>
