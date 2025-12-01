<?php
/**
 * This template displays the calender field.
 *
 * This template can be overridden by copying it to yourtheme/delivery-slots-for-woocommerce/order/delivery-fields.php
 *
 * To maintain compatibility, Delivery Slots for WooCommerce will update the template files and you have to copy the updated files to your theme
 *
 * @since 3.0.0
 * @var array $fields Order delivery fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class='dey-order-delivery-slots-fields-wrapper dey-order-scheduler-fields'>
	<?php
	foreach ( $fields as $key => $field ) :
		if ( $field['required'] ) :
			$field['class'][] = 'validate-required';
			$required         = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'delivery-slots-for-woocommerce' ) . '">*</abbr>';
		else :
			$required = '&nbsp;<span class="optional">(' . __( 'optional', 'delivery-slots-for-woocommerce' ) . ')</span>';
		endif;

		switch ( $field['type'] ) :
			case 'show_calender':
				?>
				<p id="<?php echo esc_attr( $key ); ?>_field" class="form-row <?php echo esc_attr( implode( ' ', $field['class'] ) ); ?>">
					<label for="<?php echo esc_attr( $key ); ?>">
						<?php echo wp_kses_post( $field['label'] . $required ); ?>
						<span></span>
					</label>

					<input id='dey-order-delivery-date-picker-field' class='dey-order-delivery-date-picker-field' value="<?php echo esc_attr( $field['default'] ); ?>"/>
					<span id="<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( implode( ' ', $field['input_class'] ) ); ?>"></span>
				</p>
				<?php
				if ( dey_can_display_order_calendar_color_info() ) :
					?>
					<div class='dey-calender-color-info'>
						<?php foreach ( dey_get_order_calender_colors_labels() as $name => $label ) : ?>
							<span class="dey-calender-dot-color dey-calender-<?php echo esc_attr( $name ); ?>-dot"><?php echo esc_html( $label ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php
				endif;
				break;

			case 'time_slots':
				?>
				<p id="<?php echo esc_attr( $key ); ?>_field" class="form-row <?php echo esc_attr( implode( ' ', $field['class'] ) ); ?>">
					<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_kses_post( $field['label'] . $required ); ?></label>
					<span class='woocommerce-input-wrapper'>
						<select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="select <?php echo esc_attr( implode( ' ', $field['input_class'] ) ); ?>" data-allow_clear='true'>
							<?php foreach ( $field['options'] as $option_key => $option_label ) : ?>
								<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $field['default'], true ); ?>><?php echo wp_kses_post( $option_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</span>
				</p>
				<?php
				break;

			default:
				woocommerce_form_field( $key, $field );
				if ( 'dey_delivery_date' === $key && dey_can_display_order_calendar_color_info() ) :
					?>
					<div class='dey-calender-color-info'>
						<?php foreach ( dey_get_order_calender_colors_labels() as $name => $label ) : ?>
							<span class="dey-calender-dot-color dey-calender-<?php echo esc_attr( $name ); ?>-dot"><?php echo esc_html( $label ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php
				endif;
				break;
		endswitch;
	endforeach;
	?>
	<input type='hidden' name='dey_delivery_date' class='dey-order-delivery-date' data-selected_date="<?php echo esc_attr( dey_get_selected_order_scheduler_data_from_session( 'order_delivery_date' ) ); ?>" value=""/>
</div>
