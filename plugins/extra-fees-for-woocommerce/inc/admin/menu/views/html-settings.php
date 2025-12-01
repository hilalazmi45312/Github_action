<?php
/* Admin HTML Settings */

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<div class = "wrap <?php echo esc_attr( self::$plugin_slug ) ; ?>_wrapper_cover woocommerce">
	<form method = "post" enctype = "multipart/form-data">
		<div class = "<?php echo esc_attr( self::$plugin_slug ) ; ?>_wrapper">
			<nav class = "nav-tab-wrapper woo-nav-tab-wrapper <?php echo esc_attr( self::$plugin_slug ) ; ?>_tab_ul">
				<?php foreach ( $tabs as $name => $label ) { ?>
							<a href="<?php echo esc_url( efw_get_settings_page_url( array( 'tab' => $name ) ) ) ; ?>" class="nav-tab <?php echo esc_html( self::$plugin_slug ) ; ?>_tab_a <?php echo esc_attr( $name ) . '_a ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) ; ?>">
						<span><?php echo esc_html( $label ) ; ?></span>
					</a>
				<?php } ?>
			</nav>
			<div class="<?php echo esc_attr( self::$plugin_slug ) ; ?>_tab_content efw_<?php echo esc_attr( $current_tab ) ; ?>_tab_content_wrapper">
				<?php
				/**
								 * Trigger sections. 
								 *
								 * @since 1.0
								 */
				do_action( sanitize_key( self::$plugin_slug . '_sections_' . $current_tab ) ) ;
				?>
				<div class="<?php echo esc_attr( self::$plugin_slug ) ; ?>_tab_inner_content efw_<?php echo esc_attr( $current_tab ) ; ?>_tab_inner_content">
					<?php
										/**
										 * Trigger sections. 
										 *
										 * @since 1.0
										 */
					do_action( sanitize_key( self::$plugin_slug . '_before_tab_sections' ) ) ;

					/* Display Error or Warning Messages */
					self::show_messages() ;

					/**
										 * Trigger settings. 
										 *
										 * @since 1.0
										 */
					do_action( sanitize_key( self::$plugin_slug . '_settings_' . $current_tab ) ) ;

					/**
										 * Trigger settings buttons. 
										 *
										 * @since 1.0
										 */
					do_action( sanitize_key( self::$plugin_slug . '_settings_buttons_' . $current_tab ) ) ;

					/**
										 * Trigger after setting buttons. 
										 *
										 * @since 1.0
										 */
					do_action( sanitize_key( self::$plugin_slug . '_after_setting_buttons_' . $current_tab ) ) ;
					?>
				</div>
			</div>
		</div>
	</form>
	<?php
		/**
		 * Trigger current tab setting end. 
		 *
		 * @since 1.0
		 */
	do_action( sanitize_key( self::$plugin_slug . '_' . $current_tab . '_' . $current_section . '_setting_end' ) ) ;
		/**
		 * Trigger setting end. 
		 *
		 * @since 1.0
		 */
	do_action( sanitize_key( self::$plugin_slug . '_settings_end' ) ) ;
	?>
</div>
<?php
