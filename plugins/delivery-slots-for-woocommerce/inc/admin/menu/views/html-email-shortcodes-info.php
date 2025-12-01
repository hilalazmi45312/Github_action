<?php
/**
 * Email Shortcodes info.
 *
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * This hook is used to display extra content before email shortcode content.
 *
 * @since 3.5.0
 */
do_action( 'dey_before_email_shortcode_contents_' . sanitize_title( $this->id ) );
?>
<tr>
	<th><label><?php esc_html_e( 'Supported Shortcodes', 'delivery-slots-for-woocommerce' ); ?></label></th>
	<td>
		<table class="form-table dey-email-shortcodes-info dey_<?php echo esc_attr( $this->id ); ?> widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'delivery-slots-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Description', 'delivery-slots-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( dey_check_is_array( $shortcodes_info ) ) :
					foreach ( $shortcodes_info as $key => $values ) :
						?>
						<tr>
							<td><?php echo esc_html( $key ); ?></td>
							<td><?php echo esc_html( $values['description'] ); ?></td>
						</tr>
						<?php
					endforeach;
				endif;
				?>
			</tbody>
		</table>
	</td>
</tr>
<?php
/**
 * This hook is used to display extra content after email shortcode content.
 *
 * @since 3.5.0
 */
do_action( 'dey_after_email_shortcodes_contents_' . sanitize_title( $this->id ) );

