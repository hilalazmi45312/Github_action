<?php
/**
 * Calender.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<div class = "wrap woocommerce dey-calender-wrap">
	<h2><?php esc_html_e( 'Calender', 'delivery-slots-for-woocommerce' ) ; ?></h2>

	<nav class="nav-tab-wrapper woo-nav-tab-wrapper dey-nav-tab-wrapper">
		<?php foreach ( $tabs as $name => $label ) : ?>
			<a href="<?php echo esc_url( dey_get_calender_page_url( array( 'tab' => $name ) ) ) ; ?>" class="nav-tab dey_tab_a <?php echo esc_attr( $name ) . '_a ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) ; ?>">
				<span><?php echo esc_html( $label ) ; ?></span>
			</a>
		<?php endforeach ; ?>
	</nav>

	<div class="dey-calender-inner-wrapper">
		<div class="dey-calender-color-details">
			<?php foreach ( $statuses as $name => $label ) : ?>
				<span class="dey-calender-dot-color dey-calender-<?php echo esc_attr( $name ) ; ?>-dot"><?php echo esc_html( $label ) ; ?></span>
			<?php endforeach ; ?>
		</div>

		<div class="dey_calender"></div>
		<input type="hidden" class="dey-delivery-calender-type" value="<?php echo esc_attr( $current_tab ) ; ?>"/>
	</div>

</div>
<?php
