<?php
/**
 * Shortcodes
 * 
 * @since 3.1.0
 * */
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * This hook is used to display extra content before shortcode content.
 * 
 * @since 3.1.0
 */
do_action('dey_before_shortcode_contents');
?>

<table class="form-table dey-parameter-syntax widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e('Syntax', 'delivery-slots-for-woocommerce'); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php esc_html_e('Syntax', 'delivery-slots-for-woocommerce'); ?></td>
			<td><?php esc_html_e('[shortcode parameter1 = "value" parameter2 = "value" ]'); ?></td>
		</tr>
	</tbody>
</table>

<table class="form-table dey-shortcodes-info widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e('Shortcode', 'delivery-slots-for-woocommerce'); ?></th>
			<th><?php esc_html_e('Supported Parameters', 'delivery-slots-for-woocommerce'); ?></th>
			<th><?php esc_html_e('Description', 'delivery-slots-for-woocommerce'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if (dey_check_is_array($shortcodes_info)) {
			foreach ($shortcodes_info as $key => $values) {
				?>
				<tr>
					<td><?php echo esc_html($key); ?></td>
					<td><?php echo esc_html($values['supported_parameters']); ?></td>
					<td><?php echo esc_html($values['usage']); ?></td>
				</tr>
				<?php
			}
		}
		?>
	</tbody>
</table>

<?php
/**
 * This hook is used to display extra content after shortcode content.
 * 
 * @since 3.1.0
 */
do_action('dey_after_shortcodes_contents');

