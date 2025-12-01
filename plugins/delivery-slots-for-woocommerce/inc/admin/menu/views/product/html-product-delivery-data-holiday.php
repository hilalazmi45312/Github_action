<?php
/**
 * Product delivery slot holiday panel.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<div id="dey_delivery_slots_data_holiday" class="dey-delivery-slots-options-wrapper woocommerce_options_panel">
	<div class="options_group dey-delivery-holidays-wrapper">
		<h4><?php esc_html_e( 'Holidays' , 'delivery-slots-for-woocommerce' ) ; ?></h4>
		<input type="button" class="dey-add-delivery-holiday" value="<?php esc_attr_e( 'Add Holiday' , 'delivery-slots-for-woocommerce' ) ; ?>"/>

		<div class="dey-delivery-holidays-inner-wrapper">
			<?php
			$holidays = array_filter( ( array ) get_post_meta( $thepostid , 'dey_delivery_holidays' , true ) ) ;
			if ( dey_check_is_array( $holidays ) ) :
				foreach ( $holidays as $key => $holiday ) :
					include 'html-product-delivery-holiday.php' ;
				endforeach ;
			endif ;
			?>
		</div>
	</div>
</div>
<?php
