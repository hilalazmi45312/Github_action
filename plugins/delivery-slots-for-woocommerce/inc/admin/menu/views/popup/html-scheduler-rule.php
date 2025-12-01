<?php
/**
 * Order delivery rule popup.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<div class='dey-hide dey-scheduler-rule-modal-wrapper' id='dey_scheduler_rule_modal'>
	<div class='dey-scheduler-rule-modal-header'><h3><b><?php esc_html_e( 'New Scheduler Rule', 'delivery-slots-for-woocommerce' ); ?></h3></b></div>
	<div class='options_group dey-scheduler-rule-modal-content'>
		<span class='dey-error'></span>
		<?php
		woocommerce_wp_text_input(
			array(
				'id'    => 'dey_scheduler_rule_title',
				'label' => __( 'Rule Name', 'delivery-slots-for-woocommerce' ) . "<span class='required'>*</span>",
			)
		);

		woocommerce_wp_select(
			array(
				'id'                => 'dey_order_delivery_shipping_methods',
				'label'             => __( 'Shipping Method', 'delivery-slots-for-woocommerce' ),
				'options'           => dey_get_shipping_methods(),
				'default'           => '',
				'class'             => 'dey_select2',
				'custom_attributes' => array( 'multiple' => 'multiple' ),
			)
		);

		woocommerce_wp_select(
			array(
				'id'      => 'dey_scheduler_type',
				'label'   => __( 'Applicable For', 'delivery-slots-for-woocommerce' ) . "<span class='required'>*</span>",
				'options' => dey_get_order_scheduler_options(),
				'value'   => '3', // Both delivery and local pickup.
				'class'   => 'dey_select2',
			)
		);
		?>
	</div>
	<div class='dey-scheduler-rule-modal-footer'>
		<input type='button' class='button-primary dey-create-scheduler-rule' value="<?php esc_html_e( 'Create', 'delivery-slots-for-woocommerce' ); ?>">
	</div>
</div>
<?php
