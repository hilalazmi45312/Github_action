<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VIJAGIF_HELPER' ) ) {
	class VIJAGIF_HELPER {
		protected static $instance = null;

		public static function get_instance( $new = false ) {
			if ( $new || null === self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		function getCart( $recalculate_total = false ) {
			$cart = array();
			if ( function_exists( 'WC' ) ) {
				if ( isset( WC()->cart ) && WC()->cart != null ) {
					if ( method_exists( WC()->cart, 'get_cart' ) ) {
						$cart = WC()->cart->get_cart();
					}
				}
			}

			return apply_filters( 'jagif_get_cart', $cart );
		}

		public function jagif_get_display_conditions() {
			return apply_filters( 'jagif_get_display_conditions', get_option( 'jagif_display_conditions' ) );
		}

		public function get_all_post_type() {
			$field_properties  = [];
			$get_all_post_type = get_posts( array(
					'post_type'      => 'woo_free_gift_rules',
					'post_status'    => 'publish',
					'orderby'        => 'menu_order',
					'order'          => 'DESC',
					'posts_per_page' => - 1
				)
			);
			if ( ! empty( $get_all_post_type ) && is_array( $get_all_post_type ) ) {
				foreach ( $get_all_post_type as $key => $fields_properties ) {
					$id                    = $fields_properties->ID;
					$data_rule_product     = get_post_meta( $id, 'jagif-woo_free_gift_rules', true );
					$data_rule_enable      = get_post_meta( $id, 'jagif-woo_free_gift_enable', true );
					$data_rule_description = get_post_meta( $id, 'jagif-woo_free_gift_description', true );
					$data_rule_override    = get_post_meta( $id, 'jagif-woo_free_gift_override', true );
					if ( empty( $data_rule_override ) ) {
						$data_rule_override = array( 'enable' => '', 'priority' => 0 );
					}
					if ( $data_rule_enable == false || $data_rule_enable == 'false' || empty( $data_rule_enable ) ) {
						continue;
					}
					if ( is_array( $data_rule_product ) && ! empty( $data_rule_product ) ) {
						$data_rule = $data_rule_product;
					} else {
						continue;
					}
					$field_properties[ $id ]                = $data_rule;
					$field_properties[ $id ]['title']       = get_the_title( $id );
					$field_properties[ $id ]['override']    = $data_rule_override;
					$field_properties[ $id ]['description'] = $data_rule_description;
				}
			}

			return $field_properties;
		}

		public function get_all_product() {
			$all_product_id = [];
			$args           = array(
				'type'   => array(
					'variable',
					'simple',
					'bopobb',
					'grouped',
					'external',
				),
				'status' => 'publish',
				'limit'  => - 1,
			);

			$products = wc_get_products( $args );
			foreach ( $products as $item ) {
				$all_product_id[] = $item->get_id();
			}

			return $all_product_id;
		}

		public function get_all_product_cat() {
			$arr_tax = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
				)
			);
			$items   = array();
			if ( count( $arr_tax ) ) {
				foreach ( $arr_tax as $tax_item ) {
					$items[] = $tax_item->term_id;
				}
			}

			return $items;
		}

		public function get_all_coupon() {
			$cp    = new WP_Query( array(
				'post_status'    => 'publish',
				'post_type'      => 'shop_coupon',
				'posts_per_page' => - 1,
			) );
			$items = array();
			if ( $cp->have_posts() ) :
				while ( $cp->have_posts() ) : $cp->the_post();
					$items[] = get_the_ID();
				endwhile;
				wp_reset_postdata();
			endif;

			return $items;
		}

		public static function get_cart_products() {
			global $woocommerce;
			$cart_items = $woocommerce->cart->get_cart();

			$added_products          = [];
			$added_products['count'] = count( $cart_items );
			if ( ! empty( $cart_items ) ) {
				foreach ( $cart_items as $cart_item ) {
					$added_products['ids'][]     = $cart_item['product_id'];
					$added_products['objects'][] = $cart_item['data'];
				}
			}

			return $added_products;
		}

		public static function price_currency_display( $price, $currency, $type = 'src' ) {
			if ( is_plugin_active( 'woocommerce-multi-currency/woocommerce-multi-currency.php' ) ) {
				$currencies_setting  = \WOOMULTI_CURRENCY_Data::get_ins();
				$selected_currencies = $currencies_setting->get_list_currencies();
				if ( $type == 'src' ) {
					return floatval( (float) $price / (float) $selected_currencies[ $currency ]['rate'] );
				} else {
					return floatval( (float) $price * (float) $selected_currencies[ $currency ]['rate'] );
				}
			}
			if ( is_plugin_active( 'woocommerce-currency-switcher/index.php' ) ) {
				global $WOOCS;
				$currencies = $WOOCS->get_currencies();
				if ( $type == 'src' ) {
					return floatval( (float) $price / (float) $currencies[$WOOCS->current_currency]['rate'] );
				} else {
					return floatval( (float) $price * (float) $currencies[$WOOCS->current_currency]['rate'] );
				}
			}

			return $price;
		}

		public static function jagif_get_single_conditions( $gift_rules ) {
			if ( empty( $gift_rules ) || ! is_array( $gift_rules ) ) {
				return $gift_rules;
			}
			$gifts_single = array();
			foreach ( $gift_rules as $gift_rule ) {
				if ( ! empty( $gift_rule ) && is_array( $gift_rule ) ) {
					if ( isset( $gift_rule['rule_id'] ) ) {
						if ( $gift_rule['rule_id'] == 'single' || $gift_rule['rule_id'] == '' ) {
							$gifts_single[] = $gift_rule;
						}
					}
				}
			}

			return $gifts_single;
		}
	}
}