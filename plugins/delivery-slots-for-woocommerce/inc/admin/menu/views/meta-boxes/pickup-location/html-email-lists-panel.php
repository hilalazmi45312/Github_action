<?php
/**
 * Pickup location panel - Email lists.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
}
?>
<div id="dey_pickup_location_data_email_lists" class="dey-pickup-location-options-wrapper">

	<div class="options_group">
		<?php
		woocommerce_wp_textarea_input(
				array(
					'id'    => 'dey_email_lists',
					'label' => __( 'Email Lists', 'delivery-slots-for-woocommerce' ),
					'value' => $dey_pickup_location->get_email_lists(),
				)
		) ;
		?>
	</div>
</div>
<?php
