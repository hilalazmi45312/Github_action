<?php
/**
 * Pickup location panels.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class='dey-order-pickup-scheduler-data-panels-wrapper'>
	<span class='dey-order-pickup-scheduler-panel-header'>
		<?php
		woocommerce_wp_select(
			array(
				'id'      => 'dey_pickup_mode',
				'label'   => __( 'Pickup date based on', 'delivery-slots-for-woocommerce' ),
				'options' => array(
					'1' => __( 'Global level', 'delivery-slots-for-woocommerce' ),
					'2' => __( 'Rule level', 'delivery-slots-for-woocommerce' ),
				),
				'default' => '1',
				'value'   => $dey_pickup_location->get_pickup_mode(),
			)
		);
		?>
	</span>
	<input type='hidden' id='dey_pickup_location_id' value="<?php echo esc_attr( $dey_pickup_location->get_id() ); ?>" />
	<ul class='dey-order-pickup-scheduler-data-tabs dey-panels-data-tabs'>
		<?php foreach ( self::get_order_pickup_scheduler_panels() as $key => $panel_tab ) : ?>
			<li class="dey-order-pickup-scheduler-data-tab dey_<?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $panel_tab['class'] ) ? implode( ' ', (array) $panel_tab['class'] ) : '' ); ?>">
				<a href="#<?php echo esc_attr( $panel_tab['target'] ); ?>" class='dey-order-pickup-scheduler-data-tab-link'>
					<?php if ( ! empty( $panel_tab['icon_url'] ) ) : ?>
						<img src="<?php echo esc_url( $panel_tab['icon_url'] ); ?>" alt="<?php echo esc_attr( $panel_tab['label'] ); ?>" width="20" height="20">
						<span><?php echo esc_html( $panel_tab['label'] ); ?></span>
					<?php else : ?>
						<span class="dashicons <?php echo esc_attr( $panel_tab['icon_class'] ); ?>"><?php echo esc_html( $panel_tab['label'] ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php
	self::render_panels();
	/**
	 * This hook is used to display the extra order scheduler panels content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'dey_order_pickup_location_data_panels' );
	?>
</div>
<?php
