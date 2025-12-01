<?php
/**
 * Scheduler rule panel - Special day.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id='dey_scheduler_rule_data_special_day' class='dey-scheduler-rule-options-wrapper woocommerce_options_panel'>
	<?php
	woocommerce_wp_select(
		array(
			'id'      => 'dey_scheduler_rule_special_days_mode',
			'label'   => __( 'Specific Dates Based on', 'delivery-slots-for-woocommerce' ),
			'options' => array(
				'1' => __( 'Global Level', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Rule Level', 'delivery-slots-for-woocommerce' ),
			),
			'class'   => 'dey-scheduler-rule-special-days-mode',
			'default' => '1',
			'value'   => $dey_scheduler_rule->get_special_days_mode(),
		)
	);
	?>
	<div class='options_group dey-scheduler-rule-special-days-wrapper'>
		<a href='#dey_scheduler_rule_special_day_modal' class='button dey-add-new-special-day' rel='modal:open'><?php esc_html_e( 'Add New Specific Date', 'delivery-slots-for-woocommerce' ); ?></a>
		<?php require_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/popup/html-scheduler-rule-special-day.php'; ?>
		<div class='dey-scheduler-rule-special-days-inner-wrapper'>
			<?php
			$special_days = array_filter( (array) $dey_scheduler_rule->get_special_days() );
			if ( dey_check_is_array( $special_days ) ) :
				foreach ( $special_days as $key => $special_day ) :
					include 'html-special-day.php';
				endforeach;
			endif;
			?>
		</div>
	</div>
</div>
<?php
