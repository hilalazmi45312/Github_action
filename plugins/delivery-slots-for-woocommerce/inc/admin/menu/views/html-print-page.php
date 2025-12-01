<?php
/**
 * Print page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<table class="dey-print-details-table" border="1">
	<thead>
		<tr>
			<?php
			foreach ( $colum_names as $colum_name ) :
				?>
				<th><?php echo esc_html( $colum_name ) ; ?></th>
			<?php endforeach ; ?>
		</tr>
	</thead>
	<tbody>
		<?php
		if ( dey_check_is_array( $row_data ) ) :
			foreach ( $row_data as $column_data ) :
				?>
				<tr>
					<?php foreach ( $column_data as $column_value ) : ?>
						<td><?php echo wp_kses_post( $column_value ) ; ?></td>
					<?php endforeach ; ?>
				</tr>
				<?php
			endforeach ;
		endif ;
		?>
	</tbody>
</table>
<?php
