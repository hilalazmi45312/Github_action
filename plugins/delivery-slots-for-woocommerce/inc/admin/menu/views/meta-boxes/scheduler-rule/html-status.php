<?php
/**
 * Scheduler rule - Status meta-box.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class='submitbox dey-scheduler-rule-status' id='submitpost'>
	<div id='minor-publishing'>
		<div id='misc-publishing-actions'>
			<?php if ( '0000-00-00 00:00:00' !== $dey_scheduler_rule->get_created_date() ) : ?>
				<div class='dey-status-field-row dey-created-row'>
					<label class='dey-status-label'><span class="dashicons dashicons-calendar"></span><?php esc_html_e( 'Created', 'delivery-slots-for-woocommerce' ); ?></label>
					<span class='dey-status-content'><?php echo esc_html( $dey_scheduler_rule->get_formatted_created_date() ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( '0000-00-00 00:00:00' !== $dey_scheduler_rule->get_modified_date() ) : ?>
				<div class='dey-status-field-row dey-modified-row'>
					<label class='dey-status-label'><span class="dashicons dashicons-clock"></span><?php esc_html_e( 'Modified', 'delivery-slots-for-woocommerce' ); ?></label>
					<span class='dey-status-content'><?php echo esc_html( $dey_scheduler_rule->get_formatted_modified_date() ); ?></span>
				</div>
			<?php endif; ?>
			<div class='dey-status-field-row dey-status-row'>
				<label class='dey-status-label'><span class="dashicons dashicons-post-status"></span><?php esc_html_e( 'Status', 'delivery-slots-for-woocommerce' ); ?></label>
				<span class='dey-status-content'>
					<select name='post_status' id='post_status'>
						<option value='dey_active' <?php selected( $dey_scheduler_rule->get_status(), 'dey_active' ); ?>><?php esc_html_e( 'Active', 'delivery-slots-for-woocommerce' ); ?></option>
						<option value='dey_inactive' <?php selected( $dey_scheduler_rule->get_status(), 'dey_inactive' ); ?>><?php esc_html_e( 'In-Active', 'delivery-slots-for-woocommerce' ); ?></option>
					</select>
				</span>
			</div>
		</div>
	</div>
	<div id='major-publishing-actions'>
		<div id='publishing-action'>
			<input type='submit' class='button button-primary tips' name='publish' value="<?php esc_attr_e( 'Save', 'delivery-slots-for-woocommerce' ); ?>" data-tip="<?php esc_html_e( 'Save/Update', 'delivery-slots-for-woocommerce' ); ?>" />
		</div>
		<div class='clear'></div>
		<?php wp_nonce_field( 'dey_save_data', 'dey_meta_nonce' ); ?>
	</div>
</div>
<?php
