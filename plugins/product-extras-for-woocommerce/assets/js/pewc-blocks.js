/**
 * Functions used in Cart and Checkout Blocks
 * @since 3.21.7
 */

function pewc_blocks_init_cart() {
	const { registerCheckoutFilters } = window.wc.blocksCheckout;

	registerCheckoutFilters( 'pewc-cart-item-filter', {
		showRemoveItemLink: function( defaultValue, extensions, args ) {
			// 3.22.1, remove Remove Item link in the cart if child product quantity is linked or one only
			if ( args?.context !== 'cart' || 'undefined' === typeof extensions.pewc_data || 'undefined' === typeof extensions.pewc_data.key || extensions.pewc_data.key.indexOf( 'pewc-quantities-' ) < 0 || extensions.pewc_data.key.indexOf( 'pewc-quantities-independent' ) > -1 ) {
				return defaultValue;
			}
			return false;
		},
		itemName: function( defaultValue, extensions, args ) {
			// Return early if not in cart, or if item does not have add-ons
			if ( args?.context !== 'cart' || 'undefined' === typeof extensions.pewc_data || 'undefined' === typeof extensions.pewc_data.edit_html ) {
				return defaultValue;
			}

			// add the [Edit options] text
			return defaultValue + extensions.pewc_data.edit_html;
		},
		cartItemClass: function( value, extensions, args ) {
			// Return early if not in the Cart or Checkout (summary) page
			// We must return the original value we received here.
			if ( ( args?.context !== 'cart' && args?.context !== 'summary' ) || 'undefined' === typeof extensions.pewc_data ) {
				return value;
			}

			// 3.25.7, created a separate function for the script so that we can add delay to it, to ensure uploaded images are displayed on Cart and Checkout pages using blocks
			setTimeout( pewc_blocks_add_uploaded_images, 200, extensions );

			return 'pewc-key-' + extensions.pewc_data.key;
		}
		/*cartItemPrice: function( defaultValue, extensions, args, validation ) {
			console.log(defaultValue);
			if ( ( args?.context !== 'cart' && args?.context !== 'summary' ) || 'undefined' === typeof extensions.pewc_data ) {
				return defaultValue;
			}

			return defaultValue + ' + extras';
		}*/
	} );

}

/**
 * @since 3.25.7
 */
function pewc_blocks_add_uploaded_images( extensions ) {

	jQuery(document).ready( function(){
		if ( undefined != extensions.pewc_data && jQuery(extensions.pewc_data.uploaded_files).length > 0 && jQuery( '.pewc-key-' + extensions.pewc_data.key + ' .wc-block-components-product-metadata' ).length > 0 ) {
			var ufiles = extensions.pewc_data.uploaded_files;
			for ( var i in ufiles ) {
				//var meta_key = '.pewc-key-' + extensions.pewc_data.key + ' .wc-block-components-product-details__' + i;
				var meta_key = '.pewc-key-' + extensions.pewc_data.key + ' .' + i;
				if ( jQuery( meta_key ).length > 0 && ! jQuery( meta_key ).hasClass( 'pewc-image-added' ) ) {
					// the upload field exists, add the images?
					for ( var index in ufiles[i] ) {
						var append_string = '<p>';
						if ( ufiles[i][index]['type'] == 'image' ) {
							append_string += '<img src="' + ufiles[i][index]['url'] + '">';
						} else {
							append_string += '<a href="' + ufiles[i][index]['url'] + '" target="_blank">' + ufiles[i][index]['name'] + '</a>';
						}
						if ( undefined != ufiles[i][index]['quantity'] ) {
							append_string += '<br><span class="pewc-upload-quantity">' + ufiles[i][index]['quantity'] + '</span>';
						}
						append_string += '</p>';
						jQuery( meta_key ).append( append_string );
					}
					jQuery( meta_key ).addClass( 'pewc-image-added' );
				}
			}
		}

		// 3.22.1, add arrow right to linked and one only child products
		if ( 'undefined' !== typeof extensions.pewc_data && 'undefined' !== typeof extensions.pewc_data.arrow_right ) {
			var class_selector = '.pewc-key-' + extensions.pewc_data.key.replace( ' ', '.' );
			var cart_item_image = jQuery( class_selector ).find( '.wc-block-cart-item__image' );
			if ( cart_item_image.find( '.pewc-arrow-right' ).length < 1 ) {
				cart_item_image.prepend( '<img src="' + extensions.pewc_data.arrow_right + '" class="pewc-arrow-right">' );
			}
		}
	});

}

if ( 'undefined' !== typeof wc && wc?.blocksCheckout ) {
	// this needs to be called as soon as WC blocks is ready?
	pewc_blocks_init_cart();
}
