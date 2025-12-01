<?php
/**
 * Product delivery special day panel.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<div id="dey_delivery_slots_data_special_day" class="dey-delivery-slots-options-wrapper woocommerce_options_panel">
	<div class="options_group dey-delivery-special-days-wrapper">
		<h4><?php esc_html_e( 'Special Days' , 'delivery-slots-for-woocommerce' ) ; ?></h4>
		<input type="button" class="dey-add-delivery-special-day" value="<?php esc_attr_e( 'Add Special Day' , 'delivery-slots-for-woocommerce' ) ; ?>"/>

		<div class="dey-delivery-special-days-inner-wrapper">
			<?php
			$special_days = array_filter( ( array ) get_post_meta( $thepostid , 'dey_delivery_special_days' , true ) ) ;
			if ( dey_check_is_array( $special_days ) ) :
				foreach ( $special_days as $key => $special_day ) :
					include 'html-product-delivery-special-day.php' ;
				endforeach ;
			endif ;
			?>
		</div>
	</div>
</div>
<?php
