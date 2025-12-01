<?php
/**
 * Scheduler rule meta-box panels.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class='dey-scheduler-rule-data-panels-wrapper'>
	<span class='dey-scheduler-rule-data-panel-header'>
		<?php
		woocommerce_wp_select(
			array(
				'id'      => 'dey_scheduler_type',
				'label'   => __( 'Mode', 'delivery-slots-for-woocommerce' ),
				'options' => dey_get_order_scheduler_options(),
				'default' => '3',
				'value'   => $dey_scheduler_rule->get_scheduler_type(),
				'class'   => 'dey_select2 dey-order-scheduler-type',
			)
		);
		?>
	</span>
	<ul class='dey-scheduler-rule-data-tabs dey-panels-data-tabs'>
		<?php foreach ( self::get_panels() as $key => $panel_tab ) : ?>
			<li class="dey-scheduler-rule-data-tab dey_<?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $panel_tab['class'] ) ? implode( ' ', (array) $panel_tab['class'] ) : '' ); ?>">
				<a href="#<?php echo esc_attr( $panel_tab['target'] ); ?>" class='dey-scheduler-rule-data-tab-link'>
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
	 * This hook is used to display the extra order delivery rule panels content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'dey_scheduler_rule_data_panels' );
	?>
	<div class='clear'></div>

	<?php wp_nonce_field( 'dey_save_data', 'dey_meta_nonce' ); ?>
</div>

<?php
