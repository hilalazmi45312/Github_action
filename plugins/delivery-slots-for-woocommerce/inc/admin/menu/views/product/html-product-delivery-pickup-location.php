<?php
/**
 * Product pickup location.
 *
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit;

$name = 'dey_pickup_locations[' . $key . ']';
?>
<div class="dey-pickup-location-wrapper dey-delivery-slots-wrapper">
	<h3><?php echo esc_html( $pickup_location['name'] ); ?>
		<span class="dashicons dashicons-arrow-down dey-delivery-toggle" title="<?php esc_attr_e( 'Toggle', 'delivery-slots-for-woocommerce' ); ?>"></span>
		<span class="dashicons dashicons-trash dey-delete-pickup-location" title="<?php esc_attr_e( 'Remove', 'delivery-slots-for-woocommerce' ); ?>"></span>
	</h3>
	<div class="dey-pickup-location-content dey-delivery-slots-content-wrapper dey-hide">
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_pickup_location_name"><?php esc_html_e( 'Name', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" id="dey_pickup_location_name" name="<?php echo esc_attr( $name ); ?>[name]" value="<?php echo esc_attr( $pickup_location['name'] ); ?>" />
		</p>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_pickup_location_address1"><?php esc_html_e( 'Address1', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" id="dey_pickup_location_address1" name="<?php echo esc_attr( $name ); ?>[address1]" value="<?php echo esc_attr( $pickup_location['address1'] ); ?>" />
		</p>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_pickup_location_address2"><?php esc_html_e( 'Address2', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" id="dey_pickup_location_address2" name="<?php echo esc_attr( $name ); ?>[address2]" value="<?php echo esc_attr( $pickup_location['address2'] ); ?>" />
		</p>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_pickup_location_city"><?php esc_html_e( 'City', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" id="dey_pickup_location_city" name="<?php echo esc_attr( $name ); ?>[city]" value="<?php echo esc_attr( $pickup_location['city'] ); ?>" />
		</p>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_pickup_location_country"><?php esc_html_e( 'Country', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" id="dey_pickup_location_country" name="<?php echo esc_attr( $name ); ?>[country]" value="<?php echo esc_attr( $pickup_location['country'] ); ?>" />
		</p>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_pickup_location_pincode"><?php esc_html_e( 'Pincode', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" id="dey_pickup_location_pincode" name="<?php echo esc_attr( $name ); ?>[pincode]" value="<?php echo esc_attr( $pickup_location['pincode'] ); ?>" />
		</p>
		<p class="form-field dey-delivery-slots-field">
			<label for="dey_pickup_location_phone_number"><?php esc_html_e( 'Phone Number', 'delivery-slots-for-woocommerce' ); ?></label>
			<input type="text" id="dey_pickup_location_phone_number" name="<?php echo esc_attr( $name ); ?>[phone_number]" value="<?php echo esc_attr( $pickup_location['phone_number'] ); ?>" />
		</p>

		<div class='dey-pickup-location-admin-email-field'>
			<h4><?php esc_html_e( 'Location Admin Email Addresses', 'delivery-slots-for-woocommerce' ); ?></h4>
			<?php
			woocommerce_wp_textarea_input(
				array(
					'id'          => $name . '[email_lists]',
					'label'       => __( 'Email Lists', 'delivery-slots-for-woocommerce' ),
					'value'       => $pickup_location['email_lists'],
					'description' => __( 'You can enter multiple email addresses by using comma separator.', 'delivery-slots-for-woocommerce' ),
					'desc_tip'    => true,
				)
			);
			?>
		</div>
	</div>
</div>
<?php
