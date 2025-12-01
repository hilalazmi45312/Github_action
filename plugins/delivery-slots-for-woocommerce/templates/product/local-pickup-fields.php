<?php
/**
 * This template displays the product local pickup field.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/product/local-pickup-fields.php
 *
 * To maintain compatibility, Delivery and Pickup Scheduler for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 3.5.0
 * @var array $fields Fields to be displayed.
 * @var int $product_id Product ID.
 * @var string $price Price.
 * @var array $available_dates Available dates.
 * @var string $min_date Minimum date.
 * @var string $max_date Maximum date.
 * @var string $first_time_slot First time slot.
 * @var array $available_times Available times.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<div class='dey-product-delivery-slots-fields-wrapper dey-product-scheduler-fields dey-product-local-pickup-slots-wrapper'>
	<div class='dey-product-delivery-slots-fields-data'
		data-product_id="<?php echo esc_attr( $product_id ); ?>"
		data-min_date="<?php echo esc_attr( $min_date ); ?>"
		data-max_date="<?php echo esc_attr( $max_date ); ?>"
		data-first_time_slot="<?php echo esc_attr( $first_time_slot ); ?>"
		data-available_times="<?php echo esc_attr( json_encode( $available_times ) ); ?>"
		data-available_dates="<?php echo esc_attr( json_encode( $available_dates ) ); ?>" >
		<?php
		foreach ( $fields as $key => $field ) :
			switch ( $field['type'] ) :
				case 'show_calender':
					if ( $field['required'] ) :
						$field['class'][] = 'validate-required';
						$required         = '&nbsp;<abbr class="required" title="' . __( 'required', 'delivery-slots-for-woocommerce' ) . '">*</abbr>';
					else :
						$required = '&nbsp;<span class="optional">(' . __( 'optional', 'delivery-slots-for-woocommerce' ) . ')</span>';
					endif;
					?>
					<p id="<?php echo esc_attr( $key ); ?>_field" class="form-row <?php echo esc_attr( implode( ' ', $field['class'] ) ); ?>">
						<label for="<?php echo esc_attr( $key ); ?>">
							<?php echo wp_kses_post( $field['label'] . $required ); ?>
							<span></span>
						</label>

						<input id='dey-product-date-picker-label' class='dey-product-date-picker-label' value=''/>
						<span id="<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( implode( ' ', $field['input_class'] ) ); ?>"></span>
					</p>
					<?php
					break;
				default:
					woocommerce_form_field( $key, $field );
					break;
			endswitch;
		endforeach;
		?>
		<input type='hidden' name='dey_product_pickup_date' class='dey-product-pickup-date' value=''/>
	</div>

	<?php if ( dey_show_product_pickup_total_payable( $product_id ) ) : ?>
		<p class='dey-product-total-payable'>
			<label><b><?php esc_html_e( 'Total Payable:', 'delivery-slots-for-woocommerce' ); ?></b></label>
			<span class='dey-product-pickup-total'><?php echo wp_kses_post( $price ); ?></span>
		</p>
	<?php endif; ?>
</div>
