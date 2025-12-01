<?php
/**
 * Pickup location general panels.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="dey-pickup-location-data">
	<div class="dey-pickup-location-data-panels-wrapper">
		<input type="hidden" id="dey_pickup_location_id" value="<?php echo esc_attr( $dey_pickup_location->get_id() ); ?>" />
		<ul class="dey-pickup-location-data-tabs dey-panels-data-tabs">
			<?php foreach ( self::get_settings_panels() as $key => $panel_tab ) : ?>
				<li class="dey-pickup-location-data-tab dey_<?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $panel_tab['class'] ) ? implode( ' ', (array) $panel_tab['class'] ) : '' ); ?>">
					<a href="#<?php echo esc_attr( $panel_tab['target'] ); ?>" class="dey-pickup-location-data-tab-link">
						<span class="dashicons <?php echo esc_attr( $panel_tab['icon_class'] ); ?>"><?php echo esc_html( $panel_tab['label'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php
		self::render_general_panels();
		/**
		 * This hook is used to display the extra pickup location panels content.
		 *
		 * @since 1.0
		 */
		do_action( 'dey_pickup_location_general_data_panels' );
		?>
		<div class="clear"></div>

		<?php wp_nonce_field( 'dey_save_data', 'dey_meta_nonce' ); ?>
	</div>
</div>
<?php
