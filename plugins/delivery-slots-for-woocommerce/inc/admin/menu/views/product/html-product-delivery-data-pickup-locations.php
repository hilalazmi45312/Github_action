<?php
/**
 * Product pickup locations panel.
 *
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="dey_delivery_slots_data_pickup_locations" class="dey-delivery-slots-options-wrapper woocommerce_options_panel">
	<?php
	woocommerce_wp_select(
		array(
			'id'      => 'dey_pickup_location_selection_type',
			'label'   => __( 'Location Selection Type', 'delivery-slots-for-woocommerce' ),
			'options' => array(
				'1' => __( 'Global Level Location', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Product Level Location', 'delivery-slots-for-woocommerce' ),
			),
			'default' => '1',
			'value'   => dey_get_product_pickup_location_selection_type( $thepostid ),
		)
	);
	?>
	<div class="options_group dey-pickup-locations-wrapper">
		<input type="button" class="button dey-add-pickup-location" value="<?php esc_attr_e( 'Add Pickup Location', 'delivery-slots-for-woocommerce' ); ?>"/>

		<div class="dey-pickup-locations-inner-wrapper">
			<?php
			$pickup_locations = dey_get_product_pickup_locations( $thepostid );
			if ( dey_check_is_array( $pickup_locations ) ) :
				foreach ( $pickup_locations as $key => $pickup_location ) :
					include 'html-product-delivery-pickup-location.php';
				endforeach;
			endif;
			?>
		</div>
	</div>
</div>
<?php
