<?php
/**
 * Pickup location panel - General.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="dey_pickup_location_data_general" class="dey-pickup-location-options-wrapper">
	<div class="options_group">
		<?php
		woocommerce_wp_text_input(
			array(
				'id'    => 'dey_address1',
				'label' => __( 'Address1', 'delivery-slots-for-woocommerce' ),
				'value' => $dey_pickup_location->get_address1(),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'    => 'dey_address2',
				'label' => __( 'Address2', 'delivery-slots-for-woocommerce' ),
				'value' => $dey_pickup_location->get_address2(),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'    => 'dey_city',
				'label' => __( 'City', 'delivery-slots-for-woocommerce' ),
				'value' => $dey_pickup_location->get_city(),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'    => 'dey_country',
				'label' => __( 'Country', 'delivery-slots-for-woocommerce' ),
				'value' => $dey_pickup_location->get_country(),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'    => 'dey_pincode',
				'label' => __( 'Pincode', 'delivery-slots-for-woocommerce' ),
				'value' => $dey_pickup_location->get_pincode(),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'    => 'dey_phone_number',
				'label' => __( 'Phone Number', 'delivery-slots-for-woocommerce' ),
				'value' => $dey_pickup_location->get_phone_number(),
			)
		);
		?>
	</div>
</div>
<?php
