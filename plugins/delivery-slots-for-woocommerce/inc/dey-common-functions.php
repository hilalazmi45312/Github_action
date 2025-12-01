<?php

/**
 * Common functions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'dey-layout-functions.php';
require_once 'dey-post-functions.php';
require_once 'dey-default-functions.php';
require_once 'dey-delivery-functions.php';
require_once 'dey-store-api-functions.php';
require_once 'dey-updates-functions.php';

if ( ! function_exists( 'dey_check_is_array' ) ) {

	/**
	 * Check if the resource is array.
	 *
	 * @return bool
	 */
	function dey_check_is_array( $data ) {
		return is_array( $data ) && ! empty( $data );
	}
}

if ( ! function_exists( 'dey_price' ) ) {

	/**
	 * Display the price based on wc_price function.
	 *
	 *  @return string
	 */
	function dey_price( $price, $args = array(), $echo = false ) {

		if ( $echo ) {
			echo wp_kses_post( wc_price( $price, $args ) );
		}

		return wc_price( $price, $args );
	}
}

if ( ! function_exists( 'dey_filter_readable_products' ) ) {

	/**
	 * Filter the readable products.
	 *
	 * @return array
	 */
	function dey_filter_readable_products( $product_ids ) {

		if ( ! dey_check_is_array( $product_ids ) ) {
			return array();
		}

		if ( function_exists( 'wc_products_array_filter_readable' ) ) {
			return array_filter( array_map( 'wc_get_product', $product_ids ), 'wc_products_array_filter_readable' );
		} else {
			return array_filter( array_map( 'wc_get_product', $product_ids ), 'dey_products_array_filter_readable' );
		}
	}
}

if ( ! function_exists( 'dey_products_array_filter_readable' ) ) {

	/**
	 * Check the product is readable?.
	 *
	 * @return bool
	 */
	function dey_products_array_filter_readable( $product ) {
		return $product && is_a( $product, 'WC_Product' ) && current_user_can( 'read_product', $product->get_id() );
	}
}

if ( ! function_exists( 'dey_display_delivery_mode' ) ) {

	/**
	 * Display the delivery mode.
	 *
	 * @return string
	 */
	function dey_display_delivery_mode( $mode ) {
		$modes = dey_delivery_modes();
		if ( ! dey_check_is_array( $modes ) ) {
			return '';
		}

		if ( ! isset( $modes[ $mode ] ) ) {
			return '';
		}

		return $modes[ $mode ];
	}
}

if ( ! function_exists( 'dey_display_weekdays' ) ) {

	/**
	 * Display the Week days.
	 *
	 * @return string
	 */
	function dey_display_weekdays( $week_days ) {
		$formatted_week_days = array();
		if ( ! dey_check_is_array( $week_days ) ) {
			return '';
		}

		$week_day_labels = dey_weekdays();
		foreach ( $week_days as $day ) {
			if ( ! isset( $week_day_labels[ $day ] ) ) {
				continue;
			}

			$formatted_week_days[] = $week_day_labels[ $day ];
		}

		return implode( ', ', $formatted_week_days );
	}
}

if ( ! function_exists( 'dey_add_html_inline_style' ) ) {

	/**
	 * Add the custom CSS to HTML elements.
	 *
	 * @return Mixed
	 */
	function dey_add_html_inline_style( $content, $css, $full_content = false ) {
		if ( ! $css || ! $content ) {
			return $content;
		}

		// Return the content with style css when DOMDocument class not exists.
		if ( ! class_exists( 'DOMDocument' ) ) {
			return '<style type="text/css">' . $css . '</style>' . $content;
		}

		if ( class_exists( '\Pelago\Emogrifier\CssInliner' ) ) {
			// To create a instance with original HTML.
			$css_inliner_class = 'Pelago\Emogrifier\CssInliner';
			$domDocument       = $css_inliner_class::fromHtml( $content )->inlineCss( $css )->getDomDocument();
			// Removing the elements with display:none style declaration from the content.
			$html_pruner_class = 'Pelago\Emogrifier\HtmlProcessor\HtmlPruner';
			$html_pruner_class::fromDomDocument( $domDocument )->removeElementsWithDisplayNone();
			// Converts a few style attributes values to visual HTML attributes.
			$attribute_converter_class = 'Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter';
			$visual_html               = $attribute_converter_class::fromDomDocument( $domDocument )->convertCssToVisualAttributes();

			$content = ( $full_content ) ? $visual_html->render() : $visual_html->renderBodyContent();
		} elseif ( class_exists( '\Pelago\Emogrifier' ) ) {
			$emogrifier_class = 'Pelago\Emogrifier';
			$emogrifier       = new Emogrifier( $content, $css );
			$content          = ( $full_content ) ? $emogrifier->emogrify() : $emogrifier->emogrifyBodyContent();
		} elseif ( version_compare( WC_VERSION, '4.0', '<' ) ) {
			$emogrifier_class = 'Emogrifier';
			if ( ! class_exists( $emogrifier_class ) ) {
				include_once dirname( WC_PLUGIN_FILE ) . '/includes/libraries/class-emogrifier.php';
			}

			$emogrifier = new Emogrifier( $content, $css );
			$content    = ( $full_content ) ? $emogrifier->emogrify() : $emogrifier->emogrifyBodyContent();
		}

		return $content;
	}
}

if ( ! function_exists( 'dey_get_filter_rule_default_data' ) ) {

	/**
	 * Get the pickup location filter rule data.
	 *
	 * @return array
	 * */
	function dey_get_filter_rule_default_data( $rule = array() ) {
		$default_args = array(
			'rule_type'        => '1',
			'product_type'     => '1',
			'categories'       => array(),
			'products'         => array(),
			'product_types'    => array(),
			'tags'    => array(),
			'shipping_methods' => array(),
		);

		return wp_parse_args( $rule, $default_args );
	}
}

if ( ! function_exists( 'dey_customize_array_position' ) ) {

	/**
	 * Customize the array position.
	 *
	 * @since 2.3
	 *
	 * @return array
	 */
	function dey_customize_array_position( $array, $key, $new_value ) {
		$keys  = array_keys( $array );
		$index = array_search( $key, $keys );
		$pos   = false === $index ? count( $array ) : $index + 1;

		$new_value = is_array( $new_value ) ? $new_value : array( $new_value );

		return array_merge( array_slice( $array, 0, $pos ), $new_value, array_slice( $array, $pos ) );
	}
}

if ( ! function_exists( 'dey_is_product_delivery' ) ) {

	/**
	 * Is product delivery?
	 *
	 * @since 3.5.0
	 * @return bool
	 */
	function dey_is_product_delivery() {
		return ( 'yes' === get_option( 'dey_product_delivery_enable_product_delivery' ) );
	}
}

if ( ! function_exists( 'dey_is_product_local_pickup' ) ) {

	/**
	 * Is product local pickup?
	 *
	 * @since 3.5.0
	 * @return bool
	 */
	function dey_is_product_local_pickup() {
		return ( 'yes' === get_option( 'dey_product_local_pickup_enable_product_pickup' ) );
	}
}

if ( ! function_exists( 'dey_is_valid_product_scheduler' ) ) {

	/**
	 * Is valid product scheduler?
	 *
	 * @since 3.5.0
	 * @return bool
	 */
	function dey_is_valid_product_scheduler() {
		return dey_is_product_delivery() || dey_is_product_local_pickup();
	}
}

if ( ! function_exists( 'dey_is_product_scheduler' ) ) {

	/**
	 * Is product scheduler?
	 *
	 * @since 3.5.0
	 * @return bool
	 */
	function dey_is_product_scheduler() {
		return dey_is_product_delivery() && dey_is_product_local_pickup();
	}
}
if ( ! function_exists( 'dey_is_product_scheduler_enabled' ) ) {

	/**
	 * Is product scheduler enabled?
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @return array
	 */
	function dey_is_product_scheduler_enabled( $product_id ) {
		return ( '2' === (string) get_post_meta( $product_id, 'dey_delivery_slot_type', true ) );
	}
}

if ( ! function_exists( 'dey_is_user_selection_type' ) ) {

	/**
	 * Is product user selection type?
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	function dey_is_user_selection_type( $product_id ) {
		if ( ! dey_is_product_scheduler() ) {
			return false;
		}

		$product = wc_get_product( $product_id );
		if ( 'variation' === $product->get_type() ) {
			$product_id = $product->get_parent_id();
		}

		return ( '3' === get_post_meta( $product_id, 'dey_product_scheduler_type', true ) );
	}
}

if ( ! function_exists( 'dey_is_product_delivery_type' ) ) {

	/**
	 * Is product delivery type?
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	function dey_is_product_delivery_type( $product_id ) {
		if ( ! dey_is_product_delivery() ) {
			return false;
		}

		$product = wc_get_product( $product_id );
		if ( 'variation' === $product->get_type() ) {
			$product_id = $product->get_parent_id();
		}

		$product_scheduler_type = get_post_meta( $product_id, 'dey_product_scheduler_type', true );

		return dey_is_product_scheduler() ? ( '1' === $product_scheduler_type || empty( $product_scheduler_type ) ) : true;
	}
}

if ( ! function_exists( 'dey_is_product_pickup_type' ) ) {

	/**
	 * Is product pickup type?
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	function dey_is_product_pickup_type( $product_id ) {
		if ( ! dey_is_product_local_pickup() ) {
			return false;
		}

		$product = wc_get_product( $product_id );
		if ( 'variation' === $product->get_type() ) {
			$product_id = $product->get_parent_id();
		}

		return dey_is_product_scheduler() ? ( '2' === get_post_meta( $product_id, 'dey_product_scheduler_type', true ) ) : true;
	}
}

if ( ! function_exists( 'dey_get_product_pickup_location_selection_type' ) ) {

	/**
	 * Get the product pickup location selection type.
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @return int|string
	 */
	function dey_get_product_pickup_location_selection_type( $product_id ) {
		$pickup_location_selection_type = get_post_meta( $product_id, 'dey_pickup_location_selection_type', true );
		if ( ! $pickup_location_selection_type ) {
			return '1';
		}

		return $pickup_location_selection_type;
	}
}

if ( ! function_exists( 'dey_get_product_pickup_locations' ) ) {

	/**
	 * Get the product pickup locations.
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @return array
	 */
	function dey_get_product_pickup_locations( $product_id ) {
		return array_filter( (array) get_post_meta( $product_id, 'dey_pickup_locations', true ) );
	}
}

if ( ! function_exists( 'dey_get_product_pickup_location_admin_emails' ) ) {

	/**
	 * Get the product pickup location admin emails.
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @param int $location_id Location ID.
	 * @return array
	 */
	function dey_get_product_pickup_location_admin_emails( $product_id, $location_id ) {
		if ( ! $product_id || ! $location_id ) {
			return '';
		}

		// Product level pickup locations.
		$product_pickup_locations = dey_get_product_pickup_locations( $product_id );
		if ( ! isset( $product_pickup_locations[ $location_id ] ) || ! dey_check_is_array( $product_pickup_locations[ $location_id ] ) ) {
			return '';
		}

		if ( ! isset( $product_pickup_locations[ $location_id ]['email_lists'] ) ) {
			return '';
		}

		return $product_pickup_locations[ $location_id ]['email_lists'];
	}
}

if ( ! function_exists( 'dey_get_default_order_scheduler_type' ) ) {

	/**
	 * Get default order scheduler type.
	 *
	 * @since 3.8.0
	 * @return string
	 */
	function dey_get_default_order_scheduler_type() {
		$order_scheduler_type = '2' === get_option( 'dey_advanced_default_order_scheduler_type', '1' ) ? 'order-local-pickup' : 'order-delivery';

		/**
		* This hook is used to alter the default order scheduler type.
		*
		* @since 3.8.0
		*/
		return apply_filters( 'dey_default_order_scheduler_type', $order_scheduler_type );
	}
}
