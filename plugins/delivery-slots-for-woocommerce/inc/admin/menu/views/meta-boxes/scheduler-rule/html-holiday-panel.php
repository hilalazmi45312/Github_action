<?php
/**
 * Scheduler rule panel - Holiday.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id='dey_scheduler_rule_data_holiday' class='dey-scheduler-rule-options-wrapper woocommerce_options_panel'>
	<?php
		woocommerce_wp_select(
			array(
				'id'      => 'dey_scheduler_rule_holidays_mode',
				'label'   => __( 'Holidays Based on', 'delivery-slots-for-woocommerce' ),
				'options' => array(
					'1' => __( 'Global Level', 'delivery-slots-for-woocommerce' ),
					'2' => __( 'Rule Level', 'delivery-slots-for-woocommerce' ),
				),
				'class'   => 'dey-scheduler-rule-holidays-mode',
				'default' => '1',
				'value'   => $dey_scheduler_rule->get_holidays_mode(),
			)
		);
		?>
	<div class='options_group dey-scheduler-rule-holidays-wrapper'>
		<a href='#dey_scheduler_rule_holiday_modal' class='button dey-add-new-holiday' rel='modal:open'><?php esc_html_e( 'Add New Holiday', 'delivery-slots-for-woocommerce' ); ?></a>
		<?php require_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/popup/html-scheduler-rule-holiday.php'; ?>
		<div class='dey-scheduler-rule-holidays-inner-wrapper'>
			<?php
			$holidays = array_filter( (array) $dey_scheduler_rule->get_holidays() );
			if ( dey_check_is_array( $holidays ) ) :
				foreach ( $holidays as $key => $holiday ) :
					include 'html-holiday.php';
				endforeach;
			endif;
			?>
		</div>
	</div>
</div>
<?php
