<?php
/**
 * A template for information fields
 * @since 3.2.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo $open_td;

if( isset( $item['field_rows'] ) ) {

  $index = 0;

	$table_wrapper_classes = array(
		'pewc-information-fields',
	); ?>

	<table class="<?php echo join( ' ', $table_wrapper_classes ); ?>">

		<tbody>

		  <?php if( ! empty( $item['field_rows'] ) ) {
				foreach( $item['field_rows'] as $key=>$row_value ) {

					/*$image_url = ( ! empty( $row_value['image'] ) ) ? wp_get_attachment_url( $row_value['image'] ) : false;
					if( $image_url ) {
						$image = '<img src="' . esc_url( $image_url ) . '">';
					} else {
						$image = '';
					}*/

					// 3.26.4, added filter. WordPress sizes are thumbnail, medium, large, or can be an array of size width x height e.g. array( 50, 50 );
					$image_size = apply_filters( 'pewc_filter_information_row_image_size', 'full', $row_value, $item );
					// 3.26.3, new way of retrieving images, so that the image has the alt text if it exists
					$image = ( ! empty( $row_value['image'] ) ) ? wp_get_attachment_image( $row_value['image'], $image_size ) : '';
					$label = esc_html( $row_value['label'] );
					$data = wp_kses_post( $row_value['data'] );

					$row = sprintf(
						'<tr>
							<td class="pewc-information-image">%s</td>
							<td class="pewc-information-label">%s</td>
							<td class="pewc-information-data">%s</td>
						</tr>',
						$image,
						$label,
						$data
					);

					echo apply_filters( 'pewc_filter_information_row', $row, $row_value, $item );

				}
			} ?>

		</tbody>

	</table><!-- .pewc-radio-images-wrapper -->

<?php }

echo $close_td;
