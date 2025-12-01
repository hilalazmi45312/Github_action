<?php
/**
 * Scheduler rule panel - Time slots.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id='dey_scheduler_rule_data_time_slots' class='dey-scheduler-rule-options-wrapper woocommerce_options_panel'>
	<?php
		woocommerce_wp_select(
			array(
				'id'      => 'dey_scheduler_rule_time_slots_mode',
				'label'   => __( 'Time Slots Based on', 'delivery-slots-for-woocommerce' ),
				'options' => array(
					'1' => __( 'Global Level', 'delivery-slots-for-woocommerce' ),
					'2' => __( 'Rule Level', 'delivery-slots-for-woocommerce' ),
				),
				'class'   => 'dey-scheduler-rule-time-slots-mode',
				'default' => '1',
				'value'   => $dey_scheduler_rule->get_time_slots_mode(),
			)
		);
		?>
	<div class='options_group dey-scheduler-rule-time-slots-wrapper'>
		<a href='#dey_scheduler_rule_time_slot_modal' class='button dey-add-new-order-time-slot' rel='modal:open'><?php esc_html_e( 'Add New Time Slot', 'delivery-slots-for-woocommerce' ); ?></a>
		<?php require_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/popup/html-scheduler-rule-time-slot.php'; ?>
		<div class='dey-order-time-slots-inner-wrapper'>
			<?php
			$time_slots = array_filter( (array) $dey_scheduler_rule->get_time_slots() );
			if ( dey_check_is_array( $time_slots ) ) :
				foreach ( $time_slots as $key => $time_slot ) :
					include 'html-time-slot.php';
				endforeach;
			endif;
			?>
		</div>
	</div>
</div>
<?php
