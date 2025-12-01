<?php
/**
 * Product delivery settings panels.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="dey-delivery-slots-data">
	<div class="dey-delivery-slots-data-panels-wrapper">
		<span class="dey-delivery-slot-type">
			<?php if ( dey_is_product_scheduler() ) : ?>
			<span class="dey-scheduler-type">
				<label for="dey_product_scheduler_type"><?php esc_html_e( 'Type', 'delivery-slots-for-woocommerce' ); ?></label> &mdash;
				<select id="dey_product_scheduler_type" name="dey_product_scheduler_type" class="dey-product-scheduler-type">
					<?php
					$option_value = get_post_meta( $thepostid, 'dey_product_scheduler_type', true );
					$option_value = $option_value ? $option_value : '1';
					foreach ( dey_product_scheduler_types() as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php echo selected( $option_value, $value, false ); ?>><?php echo esc_html( $label ); ?></option>
						<?php
					endforeach;
					?>
				</select>
			</span>
			<?php endif; ?>
			<label for="dey_delivery_slot_type"><?php esc_html_e( 'Enable', 'delivery-slots-for-woocommerce' ); ?></label> &mdash;
			<select id="dey_delivery_slot_type" name="dey_delivery_slot_type">
				<?php
				$option_value = get_post_meta( $thepostid, 'dey_delivery_slot_type', true );
				foreach ( dey_product_delivery_slot_types() as $value => $label ) :
					?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php echo selected( $option_value, $value, false ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>

		<ul class="dey-delivery-slots-data-tabs">
			<?php
			foreach ( self::get_delivery_slots_data_tabs() as $key => $panel_tab ) :
				?>
				<li class="dey-delivery-slots-data-tab dey_<?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $panel_tab['class'] ) ? implode( ' ', (array) $panel_tab['class'] ) : '' ); ?>">
					<a href="#<?php echo esc_attr( $panel_tab['target'] ); ?>" class="dey-delivery-slots-data-tab-link">
						<span class="dashicons <?php echo esc_attr( $panel_tab['icon_class'] ); ?>"><?php echo esc_html( $panel_tab['label'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php
		self::delivery_slots_data_panels();
		/**
		 * This hook is used to display the extra content for panels.
		 *
		 * @since 1.0.0
		 */
		do_action( 'dey_product_delivery_slots_data_panels' );
		?>
		<div class="clear"></div>

		<?php wp_nonce_field( 'dey_save_data', 'dey_meta_nonce' ); ?>
	</div>
</div>
<?php
