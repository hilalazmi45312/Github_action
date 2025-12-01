<?php

/*
 * Common functions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'efw-layout-functions.php';
require_once 'efw-post-functions.php';

if ( ! function_exists( 'efw_check_is_array' ) ) {

	/**
	 * Check if resource is array.
	 *
	 * @return bool
	 */
	function efw_check_is_array( $array ) {
		return ( is_array( $array ) && ! empty( $array ) );
	}
}

if ( ! function_exists( 'efw_page_screen_ids' ) ) {

	/**
	 * Get page screen IDs.
	 *
	 * @return array
	 */
	function efw_page_screen_ids() {

		$wc_screen_id = sanitize_title( esc_html__( 'WooCommerce', 'woocommerce' ) );
		/**
		 * Hook:efw_page_screen_ids.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'efw_page_screen_ids',
			array(
				'product',
				'product_cat',
				$wc_screen_id . '_page_efw_settings',
			)
		);
	}
}

if ( ! function_exists( 'efw_get_allowed_setting_tabs' ) ) {

	/**
	 * Get setting tabs.
	 *
	 * @return array
	 */
	function efw_get_allowed_setting_tabs() {
		/**
		 * Hook:efw_settings_tabs_array.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'efw_settings_tabs_array', array() );
	}
}

if ( ! function_exists( 'efw_get_settings_page_url' ) ) {

	/**
	 * Get Settings page URL.
	 *
	 * @return array
	 */
	function efw_get_settings_page_url( $args = array() ) {

		$url = add_query_arg( array( 'page' => 'efw_settings' ), admin_url( 'admin.php' ) );

		if ( efw_check_is_array( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}
}

if ( ! function_exists( 'efw_get_wc_categories' ) ) {

	/**
	 * Get WC Categories.
	 *
	 * @return array
	 */
	function efw_get_wc_categories() {
		static $categories;
		if ( isset( $categories ) ) {
			return $categories;
		}

		$categories    = array();
		$wc_categories = get_terms( 'product_cat' );

		if ( ! efw_check_is_array( $wc_categories ) ) {
			return $categories;
		}

		foreach ( $wc_categories as $category ) {
			$categories[ $category->term_id ] = $category->name;
		}

		return $categories;
	}
}

if ( ! function_exists( 'efw_get_wc_tags' ) ) {

	/**
	 * Get WC Tags.
	 *
	 * @return array
	 */
	function efw_get_wc_tags() {
		static $tags;
		if ( isset( $tags ) ) {
			return $tags;
		}

		$tags    = array();
		$wc_tags = get_terms( 'product_tag' );

		if ( ! efw_check_is_array( $wc_tags ) ) {
			return $tags;
		}

		foreach ( $wc_tags as $tag ) {
			$tags[ $tag->term_id ] = $tag->name;
		}

		return $tags;
	}
}

if ( ! function_exists( 'efw_get_wc_shipping_methods' ) ) {

	/**
	 * Get Active WC Shipping Methods.
	 *
	 * @return array
	 */
	function efw_get_wc_shipping_methods() {
		$shipping_methods    = array();
		$wc_shipping_methods = WC()->shipping()->get_shipping_methods();

		if ( ! efw_check_is_array( $wc_shipping_methods ) ) {
			return $shipping_methods;
		}

		foreach ( $wc_shipping_methods as $shipping_method ) {
			$shipping_methods[ $shipping_method->id ] = $shipping_method->method_title;
		}

		return $shipping_methods;
	}
}

if ( ! function_exists( 'efw_get_shipping_method_from_order' ) ) {

	/**
	 * Get Shipping method from order object
	 *
	 * @param array $shipping_methods Shipping methods.
	 * @since 4.9.0
	 */
	function efw_get_shipping_method_from_order( $shipping_methods ) {
		$selected_shipping_method_ids = array();
		if ( ! efw_check_is_array( $shipping_methods ) ) {
			return $selected_shipping_method_ids;
		}

		foreach ( $shipping_methods as $shipping_method ) {
			$selected_shipping_method_ids[] = $shipping_method->get_method_id();
		}

		return $selected_shipping_method_ids;
	}
}

if ( ! function_exists( 'efw_get_wc_shipping_zones' ) ) {

	/**
	 * Get Active WC Shipping Zone.
	 *
	 * @return array
	 */
	function efw_get_wc_shipping_zones() {
		$shipping_zones    = array();
		$wc_shipping_zones = WC_Shipping_Zones::get_zones();

		if ( ! efw_check_is_array( $wc_shipping_zones ) ) {
			return $shipping_zones;
		}

		foreach ( $wc_shipping_zones as $shipping_zone ) {
			foreach ($shipping_zone['shipping_methods'] as $shipping_method) {
				$shipping_zones[ $shipping_method->id . '_' . $shipping_method->instance_id ] = $shipping_zone['zone_name'] . ' - ' . $shipping_method->method_title;
			}
			// $shipping_zones[ $shipping_zone['zone_id'] ] = $shipping_zone['zone_name'];
		}

		return $shipping_zones;
	}
}

if ( ! function_exists( 'efw_get_wc_available_gateways' ) ) {

	/**
	 * Get WC Gateway Id.
	 *
	 * @return array
	 */
	function efw_get_wc_available_gateways( $active = false ) {
		$available_gateways = array();
		$wc_gateways        = WC()->payment_gateways->payment_gateways();

		if ( ! efw_check_is_array( $wc_gateways ) ) {
			return $available_gateways;
		}

		foreach ( $wc_gateways as $gateway ) {

			$enabled = $active ? ( 'yes' == $gateway->enabled ) : true;

			if ( $enabled ) {
				$available_gateways[ $gateway->id ] = $gateway->title;
			}
		}

		return $available_gateways;
	}
}

if ( ! function_exists( 'efw_get_wc_signup_fee' ) ) {

	/**
	 * Get WC Signup Fee
	 *
	 * @return string/float
	 */
	function efw_get_wc_signup_fee( $product, $variationid = 0 ) {
		$signup_fee = 0;
		if ( ( 'subscription' == $product->get_type() ) || ( 'variable-subscription' == $product->get_type() ) || ( 'subscription_variation' == $product->get_type() ) ) {
			$product_id = ( $variationid ) ? $variationid : $product->get_id();
			$signup_fee = WC_Subscriptions_Product::get_sign_up_fee( $product_id );
			return (float) $signup_fee;
		}

		return (float) $signup_fee;
	}
}

if ( ! function_exists( 'efw_get_wc_variable_price_string' ) ) {

	/**
	 * Get WC Subscription String for Variable Product
	 *
	 * @return string/float
	 */
	function efw_get_wc_variable_price_string( $product ) {
		$price_string = '';
		if ( ( 'variable-subscription' == $product->get_type() ) || ( 'subscription_variation' == $product->get_type() ) ) {
			$price        = ! empty( $product->get_sale_price() ) ? $product->get_sale_price() : $product->get_regular_price();
			$price_string = WC_Subscriptions_Product::get_price_string( $product, array( 'price' => wc_price( $price ) ) );
			return $price_string;
		}

		return $price_string;
	}
}

if ( ! function_exists( 'efw_get_shop_taxable_price' ) ) {

	function efw_get_shop_taxable_price( $product, $arg = array() ) {

		$product_price = ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) ? wc_get_price_including_tax( $product, $arg ) : wc_get_price_excluding_tax( $product, $arg );

		return $product_price;
	}
}

if ( ! function_exists( 'efw_get_wc_cart_subtotal' ) ) {

	/**
	 * Get WC cart Subtotal
	 *
	 * @return string/float
	 */
	function efw_get_wc_cart_subtotal( $cart_obj = null ) {
		if ( ! $cart_obj ) {
			$cart_obj = WC()->cart;
		}

		if ( method_exists( $cart_obj, 'get_cart_contents_total' ) ) {
			$subtotal = ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) ? $cart_obj->get_cart_contents_total() + $cart_obj->get_cart_contents_tax() : $cart_obj->get_cart_contents_total();
		} else {
			$subtotal = ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) ? $cart_obj->cart_contents_total + $cart_obj->cart_contents_tax : $cart_obj->cart_contents_total;
		}

		/**
		 * Hook:efw_cart_subtotal.
		 *
		 * @since 4.6.0
		 */
		$subtotal = apply_filters( 'efw_cart_subtotal', $subtotal, $cart_obj );

		return $subtotal;
	}
}

if ( ! function_exists( 'efw_get_wc_cart_total' ) ) {

	/**
	 * Get WC cart total.
	 *
	 * @return string/float
	 */
	function efw_get_wc_cart_total( $cart_obj = null ) {
		if ( ! $cart_obj ) {
			$cart_obj = WC()->cart;
		}

		if ( method_exists( $cart_obj, 'get_cart_contents_total' ) ) {
			$total = $cart_obj->get_cart_contents_total() + $cart_obj->get_cart_contents_tax() + $cart_obj->get_shipping_total() + $cart_obj->get_shipping_tax() + $cart_obj->get_fee_total() + $cart_obj->get_fee_tax();
		} else {
			$total = $cart_obj->cart_contents_total + $cart_obj->cart_contents_tax + $cart_obj->shipping_total + $cart_obj->shipping_tax + $cart_obj->fee_total + $cart_obj->fee_tax;
		}

		/**
		 * Hook:efw_cart_order_total.
		 *
		 * @since 4.6.0
		 */
		$total = apply_filters( 'efw_cart_order_total', $total, $cart_obj );

		return $total;
	}
}

if ( ! function_exists( 'efw_product_filter' ) ) {

	/**
	 * Check if Product is applicable for Product Fee.
	 *
	 * @return bool
	 */
	function efw_product_filter( $product_id ) {

		if ( '1' === get_option( 'efw_productfee_apply_for' ) ) {
			return true;
		} elseif ( '2' === get_option( 'efw_productfee_apply_for' ) ) {
			$include_product = get_option( 'efw_productfee_include_products' );
			$include_product = ! empty( $include_product ) ? $include_product : array();
			$include_product = efw_check_is_array( $include_product ) ? $include_product : explode( ',', $include_product );
			if ( in_array( $product_id, $include_product ) ) {
				return true;
			}
		} elseif ( '3' === get_option( 'efw_productfee_apply_for' ) ) {
			$exclude_product = get_option( 'efw_productfee_exclude_products' );
			$exclude_product = ! empty( $exclude_product ) ? $exclude_product : array();
			$exclude_product = efw_check_is_array( $exclude_product ) ? $exclude_product : explode( ',', $exclude_product );
			if ( ! in_array( $product_id, $exclude_product ) ) {
				return true;
			}
		} elseif ( '4' === get_option( 'efw_productfee_apply_for' ) ) {
			$include_categories = get_option( 'efw_productfee_include_category' );
			$include_categories = ! empty( $include_categories ) ? $include_categories : array();
			$include_categories = efw_check_is_array( $include_categories ) ? $include_categories : explode( ',', $include_categories );
			$product            = wc_get_product( $product_id );
			$productid          = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
			$product_categories = get_the_terms( $productid, 'product_cat' );
			if ( efw_check_is_array( $product_categories ) ) {
				foreach ( $product_categories as $terms ) {
					if ( in_array( $terms->term_id, $include_categories ) ) {
						return true;
					}
				}
			}

			$include_additional_product = get_option( 'efw_productfee_include_additional_products' );
			$include_additional_product = efw_check_is_array( $include_additional_product ) ? $include_additional_product : array();
			if ( in_array( $product_id, $include_additional_product ) ) {
				return true;
			}

		} elseif ( '5' === get_option( 'efw_productfee_apply_for' ) ) {
			$exclude_categories = get_option( 'efw_productfee_exclude_category' );
			$exclude_categories = ! empty( $exclude_categories ) ? $exclude_categories : array();
			$exclude_categories = efw_check_is_array( $exclude_categories ) ? $exclude_categories : explode( ',', $exclude_categories );
			$product            = wc_get_product( $product_id );
			$productid          = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
			$product_categories = get_the_terms( $productid, 'product_cat' );

			$exclude_additional_product = get_option( 'efw_productfee_exclude_additional_products' );
			$exclude_additional_product = efw_check_is_array( $exclude_additional_product ) ? $exclude_additional_product : array();
			if (  in_array( $product_id, $exclude_additional_product ) ) {
				return false;
			}

			if ( efw_check_is_array( $product_categories ) ) {
				foreach ( $product_categories as $terms ) {
					if ( ! in_array( $terms->term_id, $exclude_categories ) ) {
						return true;
					}
				}
			}
		} elseif ( '6' === get_option( 'efw_productfee_apply_for' ) ) {
			$include_tags = get_option( 'efw_productfee_include_tag' );
			$include_tags = ! empty( $include_tags ) ? $include_tags : array();
			$include_tags = efw_check_is_array( $include_tags ) ? $include_tags : explode( ',', $include_tags );
			$product      = wc_get_product( $product_id );
			$productid    = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
			$product_tags = get_the_terms( $productid, 'product_tag' );
			if ( efw_check_is_array( $product_tags ) ) {
				foreach ( $product_tags as $terms ) {
					if ( in_array( $terms->term_id, $include_tags ) ) {
						return true;
					}
				}
			}
		} else {
			$exclude_tag  = get_option( 'efw_productfee_exclude_tag' );
			$exclude_tag  = ! empty( $exclude_tag ) ? $exclude_tag : array();
			$exclude_tag  = efw_check_is_array( $exclude_tag ) ? $exclude_tag : explode( ',', $exclude_tag );
			$product      = wc_get_product( $product_id );
			$productid    = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
			$product_tags = get_the_terms( $productid, 'product_tag' );
			if ( efw_check_is_array( $product_tags ) ) {
				foreach ( $product_tags as $terms ) {
					if ( ! in_array( $terms->term_id, $exclude_tag ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'efw_global_fee_value' ) ) {

	/**
	 * Global Fee Value for Product.
	 *
	 * @param float $price Product price.
	 * @return float
	 */
	function efw_global_fee_value( $price ) {
		if ( '1' === get_option( 'efw_productfee_fee_type' ) ) {
			$fee_value = (float) get_option( 'efw_productfee_fixed_value' );
		} else {
			$percent_value = (float) get_option( 'efw_productfee_percent_value' );
			$fee_value     = ( $percent_value / 100 ) * (float) $price;
		}

		return (float) $fee_value;
	}
}

if ( ! function_exists( 'efw_get_fee_text' ) ) {

	/**
	 * Get Fee Text for Product.
	 *
	 * @param int $product_id Product Id.
	 * @return string
	 */
	function efw_get_fee_text( $product_id ) {
		$fee_text = '';
		if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
			$fee_text = get_option( 'efw_productfee_fee_text' );
		} elseif ( '1' === get_post_meta( $product_id, '_efw_fee_from', true ) ) {
			if ( '3' === get_post_meta( $product_id, '_efw_text_from', true ) ) {
				$product      = wc_get_product( $product_id );
				$productid    = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
				$category_lists = get_the_terms( $productid, 'product_cat' );
				if (efw_check_is_array($category_lists)) {
					foreach ($category_lists as $category_list) {
						if ( '1' == get_term_meta( $category_list->term_id, '_efw_text_from', true ) ) {
							$fee_text = get_option( 'efw_productfee_fee_text' );
						} else {
							$fee_text = get_term_meta( $category_list->term_id, '_efw_fee_text', true );
						}

						if ( !empty( $fee_text ) ) {
							return $fee_text;
						}
					}
				}
			} else if ( '2' === get_post_meta( $product_id, '_efw_text_from', true ) ) {
				$fee_text = get_post_meta( $product_id, '_efw_fee_text', true );
			} else {
				$fee_text = get_option( 'efw_productfee_fee_text' );
			}
		} elseif ( '2' === get_post_meta( $product_id, '_efw_fee_from', true ) ) {
			$product      = wc_get_product( $product_id );
			$productid    = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
			$category_lists = get_the_terms( $productid, 'product_cat' );
			if (efw_check_is_array($category_lists)) {
				foreach ($category_lists as $category_list) {
					if ( '1' == get_term_meta( $category_list->term_id, '_efw_text_from', true ) ) {
						$fee_text = get_option( 'efw_productfee_fee_text' );
					} else {
						$fee_text = get_term_meta( $category_list->term_id, '_efw_fee_text', true );
					}

					if ( ! empty( $fee_text ) ) {
						return $fee_text;
					}
				}
			}
		} else {
			$fee_text = get_option( 'efw_productfee_fee_text' );
		}

		return $fee_text;
	}
}

if ( ! function_exists( 'efw_get_rule_fee_text' ) ) {

	/**
	 * Get Rule Fee Text for Product.
	 *
	 * @param int   $product_id Product Id.
	 * @param float $price Product price.
	 * @return string
	 */
	function efw_get_rule_fee_text( $product_id, $price, $qty, $booking_data = array() ) {
		$fee_text = array();
		if ( 'category' == efw_get_fee_configured_level( $product_id )) {
			return $fee_text;
		}

		if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
			$args = array(
				'meta_key' => 'efw_settings_level',
				'meta_value' => 'global',
			);
		} else {
			$args = array(
				'post_parent' => $product_id,
			);
		}

		$rule_ids = efw_get_fee_rule_ids( $args );

		if ( ! efw_check_is_array( $rule_ids ) ) {
			return $fee_text;
		}

		foreach ( $rule_ids as $rule_id ) {
			$rule = efw_get_fee_rule( $rule_id );

			if ( ! efw_date_filter( $rule ) ) {
				continue;
			}

			if ( ! is_product() && ! is_shop() ) {
				if ( ! efw_quantity_filter( $rule, $qty )) {
					continue;
				}
			}

			if ( '1' === $rule->get_fee_type() ) {
				if ('yes' == get_option('efw_productfee_qty_restriction_enabled')) {
					$fee_value = $rule->get_fixed_fee();
				} else {
					$fee_value = isset($booking_data['_persons'][0]) ? $rule->get_fixed_fee() * $booking_data['_persons'][0] : $rule->get_fixed_fee();
				}
			} else {
				$percent_value = (float) $rule->get_percent_fee();
				$fee_value     = ( $percent_value / 100 ) * $price;
			}

			$fee_text[ $rule->get_id() ] = $fee_value;
		}

		return $fee_text;
	}
}

if ( ! function_exists( 'efw_get_fee_description' ) ) {

	/**
	 * Get Fee Description.
	 *
	 * @param int $product_id Product Id.
	 * @return string
	 */
	function efw_get_fee_description( $product_id ) {
		$fee_desc = '';
		if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
			$fee_desc = get_option( 'efw_productfee_description' );
		} elseif ( '1' === get_post_meta( $product_id, '_efw_fee_from', true ) ) {
			if ( '3' === get_post_meta( $product_id, '_efw_text_from', true ) ) {
				$product      = wc_get_product( $product_id );
				$productid    = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
				$category_lists = get_the_terms( $productid, 'product_cat' );
				if (efw_check_is_array($category_lists)) {
					foreach ($category_lists as $category_list) {
						if ( '1' == get_term_meta( $category_list->term_id, '_efw_text_from', true ) ) {
							$fee_desc = get_option( 'efw_productfee_description' );
						} else {
							$fee_desc = get_term_meta( $category_list->term_id, '_efw_fee_description', true );
						}

						if ( !empty( $fee_desc ) ) {
							return $fee_desc;
						}
					}
				}
			} else if ( '2' === get_post_meta( $product_id, '_efw_text_from', true ) ) {
				$fee_desc = get_post_meta( $product_id, '_efw_fee_description', true );
			} else {
				$fee_desc = get_option( 'efw_productfee_description' );
			}
		} elseif ( '2' === get_post_meta( $product_id, '_efw_fee_from', true ) ) {
			$product      = wc_get_product( $product_id );
			$productid    = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
			$category_lists = get_the_terms( $productid, 'product_cat' );
			if (efw_check_is_array($category_lists)) {
				foreach ($category_lists as $category_list) {
					if ( '1' == get_term_meta( $category_list->term_id, '_efw_text_from', true ) ) {
						$fee_desc = get_option( 'efw_productfee_description' );
					} else {
						$fee_desc = get_term_meta( $category_list->term_id, '_efw_fee_description', true );
					}

					if ( !empty( $fee_desc ) ) {
						return $fee_desc;
					}
				}
			}
		} else {
			$fee_desc = get_option( 'efw_productfee_description' );
		}

		return $fee_desc;
	}
}

if ( ! function_exists( 'efw_get_rule_fee_descriptions' ) ) {

	/**
	 * Get rule fee descriptions
	 *
	 * @param int $product_id Product Id.
	 * @return array
	 */
	function efw_get_rule_fee_descriptions( $product_id ) {
		$fee_descriptions = array();
		if ( 'category' == efw_get_fee_configured_level( $product_id )) {
			return $fee_descriptions;
		}

		if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
			$args = array(
				'meta_key' => 'efw_settings_level',
				'meta_value' => 'global',
			);
		} else {
			$args = array(
				'post_parent' => $product_id,
			);
		}

		$rule_ids = efw_get_fee_rule_ids( $args );

		foreach ( $rule_ids as $rule_id ) {
			$rule_object = efw_get_fee_rule( $rule_id );
			if ( ! is_object( $rule_object ) || ! $rule_object->get_fee_description() ) {
				continue;
			}

			$fee_descriptions[ $rule_object->get_id() ] = $rule_object->get_fee_description();
		}

		return $fee_descriptions;
	}
}

if ( ! function_exists( 'efw_date_filter' ) ) {

	/**
	 * Filter Rule for Specific Date Range.
	 *
	 * @param object $rule Rule object.
	 * @return bool
	 */
	function efw_date_filter( $rule ) {
		$current_date_obj = EFW_Date_Time::get_date_time_object( 'now' );

		$from_date = $rule->get_from_date();
		$to_date   = $rule->get_to_date();

		if ( empty( $from_date ) && empty( $to_date ) ) {
			return true;
		} elseif ( empty( $from_date ) && ! empty( $to_date ) ) {
			$to_date_object = EFW_Date_Time::get_date_time_object( $to_date );
			if ( $current_date_obj <= $to_date_object ) {
				return true;
			}
		} elseif ( ! empty( $from_date ) && empty( $to_date ) ) {
			$from_date_object = EFW_Date_Time::get_date_time_object( $from_date );
			if ( $current_date_obj >= $from_date_object ) {
				return true;
			}
		} elseif ( ! empty( $from_date ) && ! empty( $to_date ) ) {
			$from_date_object = EFW_Date_Time::get_date_time_object( $from_date );
			$to_date_object   = EFW_Date_Time::get_date_time_object( $to_date );
			if ( ( $current_date_obj >= $from_date_object ) && ( $current_date_obj <= $to_date_object ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'efw_quantity_filter' ) ) {

	/**
	 * Filter Rule for Specific QUantity Range.
	 *
	 * @param object $rule Rule object.
	 * @return bool
	 */
	function efw_quantity_filter( $rule, $qty ) {

		$min_qty = $rule->get_minimum_qty();
		$max_qty = $rule->get_maximum_qty();

		if ( empty( $min_qty ) && empty( $max_qty ) ) {
			return true;
		} elseif ( empty( $min_qty ) && ! empty( $max_qty ) ) {
			if ( $qty <= $max_qty ) {
				return true;
			}
		} elseif ( ! empty( $min_qty ) && empty( $max_qty ) ) {
			if ( $qty >= $min_qty ) {
				return true;
			}
		} elseif ( ! empty( $min_qty ) && ! empty( $max_qty ) ) {
			if ( ( $qty >= $min_qty ) && ( $qty <= $max_qty ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'efw_get_fee_configured_level' ) ) {

	/**
	 * Get Fee From which level.
	 *
	 * @param int   $product_id Product Id.
	 */
	function efw_get_fee_configured_level( $product_id ) {
		if ('1' == get_option('efw_productfee_fee_setup')) {
			return 'global';
		} else if ( '1' == get_post_meta($product_id, '_efw_fee_from', true)) {
			return 'product';
		} else if ( '2' == get_post_meta($product_id, '_efw_fee_from', true)) {
			return 'category';
		}
	}
}

if ( ! function_exists( 'efw_get_wp_user_roles' ) ) {

	/**
	 * Get WordPress User Roles
	 *
	 * @return array
	 */
	function efw_get_wp_user_roles() {
		global $wp_roles;
		$user_roles = array();

		if ( ! isset( $wp_roles->roles ) || ! efw_check_is_array( $wp_roles->roles ) ) {
			return $user_roles;
		}

		foreach ( $wp_roles->roles as $slug => $role ) {
			$user_roles[ $slug ] = $role['name'];
		}

		return $user_roles;
	}
}

if ( ! function_exists( 'efw_get_user_roles' ) ) {

	/**
	 * Get User Roles
	 *
	 * @param array $extra_options Extra Options.
	 * @return array
	 */
	function efw_get_user_roles( $extra_options = array() ) {
		$user_roles = efw_get_wp_user_roles();

		$user_roles['guest'] = esc_html__( 'Guest', 'extra-fees-for-woocommerce' );

		$user_roles = array_merge( $user_roles, $extra_options );

		return $user_roles;
	}
}

if ( ! function_exists( 'efw_get_custom_field_translate_string' ) ) {

	/**
	 * Get the custom field translated string.
	 *
	 * @param string $option_name Option name.
	 * @param string $value String to Translate.
	 * @param string $language Language to transalte.
	 * @return mixed
	 */
	function efw_get_custom_field_translate_string( $option_name, $value, $language = null ) {
		/**
		 * Hook:efw_custom_field_translate_string.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'efw_custom_field_translate_string', $value, $option_name, $language );
	}
}

if ( ! function_exists( 'efw_get_error_msg_for_product' ) ) {

	/**
	 * Check if Rule is Valid for Product.
	 *
	 * @param array $rule Rules.
	 * @return bool
	 */
	function efw_get_error_msg_for_product( $rule ) {
		$error = array();
		if ( empty( $rule['efw_fee_text'] ) ) {
			/* translators: %s : Rule Name */
			$error[] = sprintf( esc_html__( '%s : Fee Text Value field cannot be empty', 'extra-fees-for-woocommerce' ), $rule['efw_name'] );
		}
		if ( '1' === $rule['efw_fee_type'] ) {
			if ( empty( $rule['efw_fixed_fee'] ) ) {
				/* translators: %s : Rule Name */
				$error[] = sprintf( esc_html__( '%s : Fixed Fee Value field cannot be empty', 'extra-fees-for-woocommerce' ), $rule['efw_name'] );
			}
		}
		if ( '2' === $rule['efw_fee_type'] ) {
			if ( empty( $rule['efw_percent_fee'] ) ) {
				/* translators: %s : Rule Name */
				$error[] = sprintf( esc_html__( '%s : Percent Fee Value field cannot be empty', 'extra-fees-for-woocommerce' ), $rule['efw_name'] );
			}
		}

		if ( ! empty( $rule['efw_from_date'] ) && ! empty( $rule['efw_to_date'] ) ) {
			if ( $rule['efw_from_date'] > $rule['efw_to_date'] ) {
				/* translators: %s : Rule Name */
				$error[] = sprintf( esc_html__( '%s : From Date should not be greater than To Date', 'extra-fees-for-woocommerce' ), $rule['efw_name'] );
			}
		}

		return $error;
	}
}

if ( ! function_exists( 'efw_multiple_level_table_header' ) ) {

	/**
	 * Get Multiple level table header.
	 *
	 * @since 3.6
	 * @return array
	 */
	function efw_multiple_level_table_header() {
		return array(
			'1' => esc_html__( 'Min Cart Subtotal/Order Total', 'extra-fees-for-woocommerce' ),
			'2' => esc_html__( 'Max Cart Subtotal/Order Total' ),
			'3' => esc_html__( 'Fee Type', 'extra-fees-for-woocommerce' ),
			'4' => esc_html__( 'Fee Value', 'extra-fees-for-woocommerce' ),
		);
	}
}

if ( ! function_exists( 'efw_multiple_level_table_body' ) ) {

	/**
	 * Get Multiple Level table body.
	 *
	 * @since 3.6
	 * @return array
	 */
	function efw_multiple_level_table_body() {
		return array(
			'min_cart'  => '',
			'max_cart'  => '',
			'fee_type'  => '',
			'fee_value' => '<input type="number">',
			'remove'    => '<button class="efw-remove-multiple-fee">Remove</button>',
		);
	}
}

if ( ! function_exists( 'efw_get_fee_type_options' ) ) {

	/**
	 * Get Fee type options.
	 *
	 * @since 3.6
	 * @return array
	 */
	function efw_get_fee_type_options() {
		return array(
			'1' => esc_html__( 'Fixed Fee', 'extra-fees-for-woocommerce' ),
			'2' => esc_html__( 'Percentage of Cart Subtotal', 'extra-fees-for-woocommerce' ),
			'3' => esc_html__( 'Percentage of Order Total', 'extra-fees-for-woocommerce' ),
		);
	}
}

if ( ! function_exists( 'efw_order_fee_table_default_values' ) ) {

	/**
	 * Order fee table default values.
	 *
	 * @since 3.6
	 * @return array
	 */
	function efw_order_fee_table_default_values() {
		return array(
			0 => array(
				'min_cart_fee' => '',
				'max_cart_fee' => '',
				'fee_type'     => '1',
				'fee_value'    => '',
			),
		);
	}
}

if ( ! function_exists( 'efw_get_fee_tax_classes' ) ) {

	/**
	 * Get Tax class options.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	function efw_get_fee_tax_classes() {
		$tax_class = array(
			'not-required' => 'Not Required',
			'standard'     => 'Standard',
		);

		$tax_class_options = WC_Tax::get_tax_classes();
		foreach ( $tax_class_options as $key => $options ) {
			$tax_class[ sanitize_title( $options ) ] = $options;
		}

				/**
				 * This hook is used to alter the tax classes.
				 *
				 * @since 5.3.0
				 * @param array $tax_class Tax classes.
				 */
		return apply_filters( 'efw_tax_classes', $tax_class );
	}
}

if ( ! function_exists( 'efw_get_automatic_payment_ids' ) ) {

	/**
	 * Get automatic payment supports gateway ids.
	 *
	 * @return array
	 */
	function efw_get_automatic_payment_ids() {

		$available_gateways = array();
		$wc_gateways        = WC()->payment_gateways->payment_gateways();

		if ( ! efw_check_is_array( $wc_gateways ) ) {
			return $available_gateways;
		}

		foreach ( $wc_gateways as $gateway ) {

			if ( 'yes' == $gateway->enabled && $gateway->supports( 'subscriptions' ) ) {
				$available_gateways[] = $gateway->id;
			}
		}

		return $available_gateways;
	}
}

if ( ! function_exists( 'efw_get_product_fee_tax_class' ) ) {

	/**
	 * Get product fee tax class.
	 *
	 * @since 5.3.0
	 * @return string
	 */
	function efw_get_product_fee_tax_class() {
		return in_array( get_option( 'efw_productfee_tax_class' ), array_keys( efw_get_fee_tax_classes() ) ) ? get_option( 'efw_productfee_tax_class' ) : 'standard';
	}
}

if ( ! function_exists( 'efw_get_gateway_fee_tax_class' ) ) {

	/**
	 * Get gateway fee tax class.
	 *
	 * @since 5.3.0
	 * @param string $gateway_id Gateway ID.
	 * @return string
	 */
	function efw_get_gateway_fee_tax_class( $gateway_id ) {
		if ( ! $gateway_id ) {
			return 'standard';
		}

		return in_array( get_option( 'efw_tax_class_for_' . $gateway_id ), array_keys( efw_get_fee_tax_classes() ) ) ? get_option( 'efw_tax_class_for_' . $gateway_id ) : 'standard';
	}
}

if ( ! function_exists( 'efw_get_order_fee_tax_class' ) ) {

	/**
	 * Get order fee tax class.
	 *
	 * @since 5.3.0
	 * @return string
	 */
	function efw_get_order_fee_tax_class() {
		return in_array( get_option( 'efw_ordertotalfee_tax_class' ), array_keys( efw_get_fee_tax_classes() ) ) ? get_option( 'efw_ordertotalfee_tax_class' ) : 'standard';
	}
}

if ( ! function_exists( 'efw_get_shipping_fee_tax_class' ) ) {

	/**
	 * Get shipping fee tax class.
	 *
	 * @since 5.3.0
	 * @return string
	 */
	function efw_get_shipping_fee_tax_class( $shipping_method_id ) {
		if ( ! $shipping_method_id ) {
			return 'standard';
		}

		return in_array( str_replace( '_', '-', get_option( 'efw_shipping_tax_class_' . $shipping_method_id ) ), array_keys( efw_get_fee_tax_classes() ) ) ? str_replace( '_', '-', get_option( 'efw_shipping_tax_class_' . $shipping_method_id ) ) : 'standard';
	}
}

if ( ! function_exists( 'efw_get_wc_available_shippings' ) ) {

	/**
	 * Get WC Shipping Id.
	 *
	 * @return array
	 */
	function efw_get_wc_available_shippings( $active = false ) {
		$available_shippings = array() ;
		$wc_shipping         = WC()->shipping()->get_shipping_methods() ;

		if ( ! efw_check_is_array( $wc_shipping ) ) {
			return $available_shippings ;
		}

		foreach ( $wc_shipping as $shipping ) {

			$enabled = $active ? ( 'yes' == $shipping->enabled ) : true ;

			if ( $enabled ) {
				$available_shippings[ $shipping->id ] = $shipping->method_title ;
			}
		}

		return $available_shippings ;
	}

}

if ( ! function_exists( 'efw_is_block_checkout' ) ) {

	/**
	 * Check whether it is block checkout page.
	 * 
	 * @since 3.7.0
	 * @return boolean
	 */
	function efw_is_block_checkout() {
		global $post;
		$is_singular = true;

		if ( ! is_a($post, 'WP_Post')) {
			$is_singular = false;
		}

		if (isset($GLOBALS['wp']->query_vars['rest_route']) && false !== strpos($GLOBALS['wp']->query_vars['rest_route'], '/wc/store/v1/cart')) {
			return true;
		}

		return $is_singular && has_block('woocommerce/checkout', $post);
	}
}

if ( ! function_exists( 'efw_get_allowed_states' ) ) {

	/**
	 * Get WC Allowed Country/States.
	 *
	 * @return array
	 */
	function efw_get_allowed_states() {
		$allowed_states = array();
		foreach ( WC()->countries->get_allowed_country_states() as $country_code => $state_name ) {
			$country_name = WC()->countries->countries[ $country_code ];
			if (efw_check_is_array($state_name)) {
				foreach ($state_name as $state_code => $state_name) {
					$allowed_states[$country_code . ':' . $state_code] = $country_name . ' - ' . $state_name;
				}
			} else {
				$allowed_states[$country_code] = $country_name;
			}
		}

		return $allowed_states;
	}
}

if ( ! function_exists( 'efw_get_selected_state' ) ) {

	/**
	 * Get WC Selected States.
	 *
	 * @return array
	 */
	function efw_get_selected_state( $billing_country, $billing_state ) {
		$states = WC()->countries->get_states( $billing_country );
		$states = empty($states[$billing_state]) ? $billing_country : $billing_country . ':' . $billing_state;

		return $states;
	}
}

if ( ! function_exists( 'efw_get_weekdays_options' ) ) {

	/**
	 * Get the weekdays options.
	 *
	 * @return array
	 * */
	function efw_get_weekdays_options() {
		return array(
			'1' => esc_html__( 'Monday' , 'extra-fees-for-woocommerce' ),
			'2' => esc_html__( 'Tuesday' , 'extra-fees-for-woocommerce' ),
			'3' => esc_html__( 'Wednesday' , 'extra-fees-for-woocommerce' ),
			'4' => esc_html__( 'Thursday' , 'extra-fees-for-woocommerce' ),
			'5' => esc_html__( 'Friday' , 'extra-fees-for-woocommerce' ),
			'6' => esc_html__( 'Saturday' , 'extra-fees-for-woocommerce' ),
			'7' => esc_html__( 'Sunday' , 'extra-fees-for-woocommerce' ),
				) ;
	}

}
