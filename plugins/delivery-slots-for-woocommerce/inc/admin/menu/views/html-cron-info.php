<?php
/**
 * Cron Information.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<table class="form-table dey-cron-info widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Cron Name', 'delivery-slots-for-woocommerce' ) ; ?></th>
			<th><?php esc_html_e( 'Last Updated', 'delivery-slots-for-woocommerce' ) ; ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if ( dey_check_is_array( $cron_info ) ) {
			foreach ( $cron_info as $key => $values ) {
				?>
				<tr>
					<td><?php echo esc_html( $values[ 'cron' ] ) ; ?></td>
					<td><?php echo esc_html( $values[ 'last_updated_date' ] ) ; ?></td>
				</tr>
				<?php
			}
		}
		?>
	</tbody>
</table>
<?php

