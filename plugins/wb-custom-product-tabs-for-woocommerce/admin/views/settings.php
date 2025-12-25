<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style type="text/css">
.wb_cptb_header{ width:calc( 100% + 42px ); min-height:60px; height:auto; background:#fff; margin-left:-22px; margin-top:-10px; border-bottom: 1px solid #dcdcde; margin-bottom:3rem; text-align: center; position:relative; }
.wb_cptb_header_logo{ position:absolute; top:0px; left:10px; width:100px; }
.wb_cptb_heading{ font-size: 23px; font-weight: 400; margin: 0; padding: 9px 0 4px; line-height: 1.3; }
.wb_cptb_menu_tab_nav{
	-ms-grid-columns: 1fr 1fr;
	display: -ms-inline-grid;
	display: inline-grid;
	grid-template-columns: 1fr 1fr;
	vertical-align: top; text-align: center; margin-top:2rem;
}
.wb_cptb_menu_tab_nav a{ color: inherit;
	display: block;
	margin: 0 1rem;
	padding: .5rem 1rem 1rem;
	text-decoration: none;
	transition: box-shadow .5s ease-in-out; }
.wb_cptb_menu_tab_nav a.active{ box-shadow: inset 0 -3px #007cba;
	font-weight: 600; }
.wb_cptb_content{ max-width:800px; margin:0 auto; }
</style>
<div class="wrap">
	<div class="wb_cptb_header">
			<img src="<?php echo esc_url( WB_TAB_ROOT_URL . '/admin/images/logo-blue.png' ); ?>" class="wb_cptb_header_logo">
			<div class="wb_cptb_heading"><?php esc_html_e( 'Product tabs', 'wb-custom-product-tabs-for-woocommerce' ); ?></div>

			<nav class="wb_cptb_menu_tab_nav">
				<a href="<?php echo esc_url( $page_url . '&wb_cptb_tab=general' ); ?>" class="<?php echo esc_attr( 'general' === $tab ? 'active' : '' ); ?>">
					<?php esc_html_e( 'Settings', 'wb-custom-product-tabs-for-woocommerce' ); ?>                      
				</a>
				<a href="<?php echo esc_url( $page_url . '&wb_cptb_tab=help' ); ?>" class="<?php echo esc_attr( 'help' === $tab ? 'active' : '' ); ?>">
					<?php esc_html_e( 'Help', 'wb-custom-product-tabs-for-woocommerce' ); ?>                       
				</a>
			</nav>

	</div>
	<?php
	if ( 'general' === $tab ) {
		include_once WB_TAB_ROOT_PATH . 'admin/views/-general-settings.php';
	} elseif ( 'help' === $tab ) {
		include_once WB_TAB_ROOT_PATH . 'admin/views/-help.php';
	}
	?>


	<div style="background-color: #6d4cb7; color: white; padding: 20px; text-align: center; border-radius: 8px; font-family: Arial, sans-serif; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-top:50px; font-size: 14px;">
		<h2 style="font-size: 24px; margin: 0; font-weight: bold; color: white;">Enjoying the plugin?</h2>
		
		<p style="font-size: 14px;">We've spent countless hours refining every feature to make it as smooth and useful as it is today.</p>
		<p style="font-size: 14px;">If our work has helped you, a <a href="https://wordpress.org/support/plugin/wb-custom-product-tabs-for-woocommerce/reviews/?rate=5#new-post" target="_blank" style="text-decoration:none; font-weight:bold; color:#09f309;">★★★★★</a> rating would mean a lot to us and inspire us to keep improving and adding more features.</p>
	  
		<p style="margin-top: 15px; font-size: 14px;">Thank you for your support!</p>
	</div>

</div>