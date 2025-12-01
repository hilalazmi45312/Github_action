<?php
/**
 * Notifications Table.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<tr valign="top">
	<td class="dey-notifications-wrapper">
		<table class="dey-notifications-table widefat striped">
			<thead>
				<tr>
					<?php
					/**
					 * This hook is used to alter the notification columns.
					 * 
					 * @since 1.0
					 */
					$columns = apply_filters( 'dey_delivery_email_notifications_cloumn', array(
					'status'      => __( 'Status', 'delivery-slots-for-woocommerce' ),
						'name'        => __( 'Email', 'delivery-slots-for-woocommerce' ),
						'description' => __( 'Description', 'delivery-slots-for-woocommerce' ),
						'recipient'   => __( 'Recipient(s)', 'delivery-slots-for-woocommerce' ),
						'actions'     => __( 'Actions', 'delivery-slots-for-woocommerce' ),
							) ) ;

					foreach ( $columns as $key => $column ) :
						echo '<th class="dey-notification-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>' ;
					endforeach ;
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( DEY_Notification_Instances::get_notifications() as $key => $notification ) :

					if ( ! $notification->show_in_table() ) :
						continue ;
					endif ;
					?>
					<tr>
						<?php foreach ( $columns as $cloumn_key => $label ) : ?>
							<?php
							switch ( $cloumn_key ) :
								case 'status':
									?>
									<td data-title="<?php echo esc_attr( $label ) ; ?>">
										<input type="checkbox" name="<?php echo esc_attr( $notification->get_option_key( 'enabled' ) ) ; ?>" <?php checked( true, $notification->is_enabled() ) ; ?>/>
									</td>
									<?php
									break ;
								case 'name':
									?>
									<td data-title="<?php echo esc_attr( $label ) ; ?>">
										<a href="<?php echo esc_url( dey_get_settings_page_url( array( 'tab' => 'notifications', 'section' => $key ) ) ) ; ?>"><?php echo esc_html( $notification->get_title() ) ; ?></a>
									</td>
									<?php
									break ;
								case 'description':
									?>
									<td data-title="<?php echo esc_attr( $label ) ; ?>">
										<?php echo esc_html( $notification->get_description() ) ; ?>
									</td>
									<?php
									break ;
								case 'recipient':
									?>
									<td data-title="<?php echo esc_attr( $label ) ; ?>">
										<?php
										$admin_emails = explode( ',', $notification->get_admin_emails() ) ;
										$recipient    = ( 'customer' === $notification->get_type() ) ? __( 'Customer', 'delivery-slots-for-woocommerce' ) : implode( ' , ', $admin_emails ) ;
										echo esc_html( $recipient ) ;
										?>
									</td>
									<?php
									break ;
								case 'actions':
									?>
									<td data-title="<?php echo esc_attr( $label ) ; ?>">
										<a href="<?php echo esc_url( dey_get_settings_page_url( array( 'tab' => 'notifications', 'section' => $key ) ) ) ; ?>" class="button">
											<?php esc_html_e( 'Manage', 'delivery-slots-for-woocommerce' ) ; ?>
										</a>
									</td>
									<?php
									break ;
								default:
									/**
									 * This hook is used to display the notification custom column content.
									 * 
									 * @since 1.0
									 */
									do_action( 'dey_delivery_email_notifications_' . $key, $notification ) ;
									break ;
							endswitch ;
						endforeach ;
						?>
					</tr>
					<?php
				endforeach ;
				?>
			</tbody>
		</table>
	</td>
</tr>
<?php
