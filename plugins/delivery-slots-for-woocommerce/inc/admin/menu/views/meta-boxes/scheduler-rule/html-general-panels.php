<?php
/**
 * Scheduler rule meta box - General panels.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class='dey-scheduler-rule-data-panels-wrapper'>
	<div class='dey-scheduler-rule-general-settings'>
		<h3><?php esc_html_e( 'General Settings', 'delivery-slots-for-woocommerce' ); ?></h3>
		<?php
		woocommerce_wp_select(
			array(
				'id'                => 'dey_order_delivery_shipping_methods[]',
				'label'             => __( 'Shipping Method', 'delivery-slots-for-woocommerce' ),
				'options'           => dey_get_shipping_methods(),
				'default'           => '',
				'value'             => dey_check_is_array( $dey_scheduler_rule->get_shipping_methods() ) ? $dey_scheduler_rule->get_shipping_methods() : '',
				'class'             => 'dey_select2',
				'custom_attributes' => array( 'multiple' => 'multiple' ),
				'placeholder'       => __( 'Select Shipping Methods', 'delivery-slots-for-woocommerce' ),
			)
		);
		?>
		<p class='form-field dey-date-field'>
			<label for='dey_scheduler_rule_end_date'><?php esc_html_e( 'Start Date', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_scheduler_rule_start_date',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
					'value'       => $dey_scheduler_rule->get_start_date(),
					'with_time'   => true,
				)
			);
			?>
		</p>
		<p class='form-field dey-date-field'>
			<label for='dey_scheduler_rule_end_date'><?php esc_html_e( 'End Date', 'delivery-slots-for-woocommerce' ); ?></label>
			<?php
			dey_get_datepicker_html(
				array(
					'id'          => 'dey_scheduler_rule_end_date',
					'placeholder' => DEY_Date_Time::get_wp_datetime_format(),
					'value'       => $dey_scheduler_rule->get_end_date(),
					'with_time'   => true,
				)
			);
			?>
		</p>
		<?php
		woocommerce_wp_text_input(
			array(
				'label'             => __( 'Priority', 'delivery-slots-for-woocommerce' ),
				'id'                => 'dey_scheduler_rule_priority',
				'type'              => 'number',
				'default'           => 10,
				'value'             => $dey_scheduler_rule->get_priority(),
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
			)
		);

		woocommerce_wp_textarea_input(
			array(
				'id'    => 'dey_scheduler_rule_description',
				'label' => __( 'Description', 'delivery-slots-for-woocommerce' ),
				'value' => $dey_scheduler_rule->get_description(),
			)
		);
		?>
	</div>
	<?php
	/**
	 * This hook is used to display the extra order delivery rule general data panels content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'dey_scheduler_rule_general_data_panels' );
	?>
	<div class='clear'></div>

	<?php wp_nonce_field( 'dey_save_data', 'dey_meta_nonce' ); ?>
</div>

<?php
