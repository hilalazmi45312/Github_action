<?php
/**
 * Functions for layered images
 * @since 1.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if a field has layered images
 * @since 3.17.0
 */
function pewc_field_has_layers( $field ) {

	if( ! empty( $field['field_type'] ) && $field['field_type'] == 'image_swatch' && ! empty( $field['layered_images'] ) ) {
		return true;
	}
	return false;

}

/**
 * Get the URL of a swatch layer to use in the composite final image
 * @since 3.17.0
 */
function pewc_get_swatch_field_url( $field_value, $field ) {

	$field_options = ! empty( $field['field_options'] ) ?  $field['field_options'] : array();
	$field_image = false;
	
	foreach( $field_options as $option ) {
		if( ! empty( $option['value'] ) && $option['value'] == $field_value ) {
			// This is the attachment ID of the selected swatch
			$field_image = ! empty( $option['image'] ) ? $option['image'] : $option['image_alt']; // 3.25.5
			break;
		}
	}
	
	if( $field_image ) {
		// Return the URL of the selected swatch
		return wp_get_attachment_url( $field_image );
	}

	return false;

}

function pewc_create_composite_image( $cart_item_data, $groups ) {

	// 3.21.4, prevent fatal error if Imagick is not installed
	if ( ! class_exists( 'Imagick' ) ) {
		pewc_error_log( 'AOU: Imagick module does not exist. Please contact your hosting provider.' );
		return $cart_item_data;
	}

	$swatch_urls = array();
	$file_name = array();
	$swatch_main_image = ''; // 3.25.6

	// Iterate through each group and find fields that might add layers
	if( $groups ) {
		foreach( $groups as $group_id=>$group ) {

			if( ! empty( $group['items'] ) ) {
				// Now iterate through each field in the group
				foreach( $group['items'] as $field_id=>$field ) {

					$has_layers = pewc_field_has_layers( $field );
					if( ! $has_layers ) {
						// 3.25.6, if a Swatch field replaces the main image, allow users to use it
						if ( 'image_swatch' === $field['field_type'] && ! empty( $field['replace_main_image'] ) && ! apply_filters( 'pewc_disable_replace_base_image_with_selected_swatch', false ) ) {
							$field_value = ! empty( $_POST['pewc_group_' . $group_id . '_' . $field_id ][0] ) ? $_POST['pewc_group_' . $group_id . '_' . $field_id ][0] : false;
							$swatch_main_image = pewc_get_swatch_field_url( $field_value, $field );
						}
						continue;
					}
					
					$field_value = ! empty( $_POST['pewc_group_' . $group_id . '_' . $field_id ][0] ) ? $_POST['pewc_group_' . $group_id . '_' . $field_id ][0] : false;
					if( ! $field_value ) {
						continue;
					}
					
					$file_name[] = $field_id;
					$file_name[] = sanitize_key( $field_value );
					$swatch_urls[] = pewc_get_swatch_field_url( $field_value, $field );

				}
			}
		}
	}

	if( ! $swatch_urls ) {
		// If we don't have any layers, just return the data
		return $cart_item_data;
	}

	$upload_dir = trailingslashit( pewc_get_upload_dir() );
	$product_id = $_POST['pewc_product_id'];
	$product = wc_get_product( $product_id );
	$base_image_url = $base_image_url_orig = ! empty( $swatch_main_image ) ? $swatch_main_image : wp_get_attachment_url( $product->get_image_id() ); // 3.25.6
	$base_image_url = apply_filters( 'pewc_swatch_layer_base_image_url', $base_image_url ); // 3.21.4, allow users to change url to absolute path
	if ( basename( $base_image_url ) != basename( $base_image_url_orig ) ) {
		// incorrect use of filter?
		pewc_error_log('AOU: base_image_url filtered incorrectly. Original:'.$base_image_url_orig.', filtered:'.$base_image_url );
		return $cart_item_data;
	}

	//$base_image = new Imagick( $base_image );
	// 3.21.4, sometimes Imagick returns "Failed to read file" so let's try and catch the error
	try{
		$base_image = new Imagick( $base_image_url );
	} catch ( Exception $e ) {
		pewc_error_log('AOU: error retrieving base_image_url using Imagick: '.$e->getMessage().', '.$base_image_url.'. Trying a different method.');
		// try a different method
		try{
			$base_image_blob = file_get_contents( $base_image_url );
			$base_image = new Imagick();
			$base_image->readImageBlob( $base_image_blob );
		} catch ( Exception $e ) {
			pewc_error_log('AOU: second method also failed, return:'.$e->getMessage());
			return $cart_item_data;
		}
	}
	
	$base_image->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
	$base_image->setImageArtifact('compose:args', "1,0,-0.5,0.5");
	foreach( $swatch_urls as $swatch_url ) {
		//$swatch = new Imagick( $swatch_url );
		//$base_image->compositeImage($swatch, Imagick::COMPOSITE_MATHEMATICS, 0, 0);
		// 3.21.4, try/catch method for swatch images
		$swatch_url_orig = $swatch_url;
		$swatch_url = apply_filters( 'pewc_swatch_layer_swatch_url', $swatch_url ); // allow users to change url to absolute path
		if ( basename( $swatch_url_orig ) != basename( $swatch_url ) ) {
			// incorrect use of filter?
			pewc_error_log( 'AOU: swatch_url filtered incorrectly. Original:'.$swatch_url_orig.', filtered:'.$swatch_url );
			continue;
		}
		try {
			$swatch = new Imagick( $swatch_url );
		} catch ( Exception $e ) {
			pewc_error_log('AOU: error retrieving swatch using Imagick: '.$e->getMessage().', '.$swatch_url.'. Trying 2nd method.');
			// try a different method
			try {
				$swatch_blob = file_get_contents( $swatch_url );
				$swatch = new Imagick();
				$swatch->readImageBlob( $swatch_blob );
			} catch ( Exception $e ) {
				pewc_error_log('AOU: second method for retrieiving swatch also failed:'.$e->getMessage());
				$swatch = false;
			}
		}
		if ( $swatch ) {
			try {
				$base_image->compositeImage($swatch, apply_filters( 'pewc_swatch_layer_composite_constant', Imagick::COMPOSITE_MATHEMATICS ), 0, 0); // 3.21.4, use Imagick::COMPOSITE_DEFAULT if layered image appears black
			} catch ( Exception $e ) {
				pewc_error_log('AOU: error generating composite image for swatch: '.$e->getMessage().', '.$swatch_url);
			}
		}
	}

	$layer_dir = $upload_dir . trailingslashit( pewc_get_upload_subdirs() );
	$layer_url = pewc_get_upload_url() .trailingslashit( pewc_get_upload_subdirs() );

	// Make a directory for layered images if one does not already exist
	if( ! file_exists( $layer_dir . 'index.php' ) ) {
		wp_mkdir_p( $layer_dir );
		@file_put_contents( $layer_dir . 'index.php', '<?php' . PHP_EOL . '// That whereof we cannot speak, thereof we must remain silent.' );
	}

	$slug = $product->get_slug();
	$filename = $slug . '-' . join( '-', $file_name ) . '-' . time() . '.png';
	$filename = sanitize_file_name( $filename ); // 3.21.4, if $slug has a special character, it sometimes causes issues
	// Create a unique file name with product and swatch data
	$composite_file_url = $layer_dir . $filename;
	$base_image->writeImage( $composite_file_url );

	$cart_item_data['composite_image'] = $layer_url . $filename;

	return $cart_item_data;

}
add_filter( 'pewc_after_add_cart_item_data', 'pewc_create_composite_image', 10, 2 );