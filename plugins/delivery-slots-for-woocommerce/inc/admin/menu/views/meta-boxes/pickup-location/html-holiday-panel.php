<?php
/**
 * Pickup location panel - Holiday.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id='dey_order_pickup_scheduler_data_holiday' class='dey-order-pickup-scheduler-options-wrapper woocommerce_options_panel'>
	<?php
	woocommerce_wp_select(
		array(
			'id'      => 'dey_order_pickup_holidays_mode',
			'label'   => __( 'Holidays Based on', 'delivery-slots-for-woocommerce' ),
			'options' => array(
				'1' => __( 'Global Level', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Rule Level', 'delivery-slots-for-woocommerce' ),
			),
			'class'   => 'dey-order-pickup-location-holidays-mode',
			'default' => '1',
			'value'   => $dey_pickup_location->get_holidays_mode(),
		)
	);
	?>
	<div class='options_group dey-order-pickup-scheduler-holidays-wrapper'>
		<a href='#dey_order_pickup_location_holiday_modal' class='button dey-add-new-holiday' rel='modal:open'><?php esc_html_e( 'Add New Holiday', 'delivery-slots-for-woocommerce' ); ?></a>
		<?php require_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/popup/html-order-pickup-location-holiday.php'; ?>
		<div class='dey-order-pickup-scheduler-holidays-inner-wrapper'>
			<?php
			$holidays = array_filter( (array) $dey_pickup_location->get_holidays() );
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
