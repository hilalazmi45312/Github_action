<?php
/**
 * Class to handle feature Coupons By Product Attribute
 *
 * @author      StoreApps
 * @category    Admin
 * @package     wocommerce-smart-coupons/includes
 * @version     1.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Coupons_By_Product_Attribute' ) ) {

	/**
	 * Class WC_SC_Coupons_By_Product_Attribute
	 */
	class WC_SC_Coupons_By_Product_Attribute {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {

			add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'usage_restriction' ), 10, 2 );
			add_action( 'woocommerce_coupon_options_save', array( $this, 'process_meta' ), 10, 2 );
			add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'coupon_validate' ), 11, 4 );
			add_filter( 'is_sc_valid_apply_credit', array( $this, 'validate_applicable_store_credit_against_line_item' ), 10, 4 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'handle_non_product_type_coupons' ), 11, 3 );
			add_filter( 'wc_smart_coupons_export_headers', array( $this, 'export_headers' ) );
			add_filter( 'smart_coupons_parser_postmeta_defaults', array( $this, 'postmeta_defaults' ) );
			add_filter( 'is_protected_meta', array( $this, 'make_action_meta_protected' ), 10, 3 );
			add_filter( 'sc_generate_coupon_meta', array( $this, 'generate_coupon_attribute_meta' ), 10, 2 );
			add_action( 'wc_sc_new_coupon_generated', array( $this, 'copy_coupon_attributes_meta' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_coupon_edit_script' ) );
			add_action( 'wp_ajax_wc_sc_search_attribute_terms', array( $this, 'search_attribute_terms_ajax' ) );
		}

		/**
		 * Get single instance of this class
		 *
		 * @return this class Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return result of function call
		 */
		public function __call( $function_name = '', $arguments = array() ) {

			global $woocommerce_smart_coupon;

			if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
			} else {
				return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
			}

		}

		/**
		 * Enqueue custom script for the WooCommerce coupon edit screen in admin.
		 *
		 * @return void
		 */
		public function load_custom_coupon_edit_script() {
			$screen = get_current_screen();

			if ( 'shop_coupon' === $screen->id ) {

				wp_enqueue_script(
					'wc-sc-coupon-add-restriction',
					untrailingslashit( plugins_url( '/', WC_SC_PLUGIN_FILE ) ) . '/assets/js/sc-add-restriction.js',
					array( 'jquery' ),
					WC()->version,
					true
				);
			}
		}

		/**
		 * Display field for coupon by product attribute
		 *
		 * @param integer   $coupon_id The coupon id.
		 * @param WC_Coupon $coupon    The coupon object.
		 */
		public function usage_restriction( $coupon_id = 0, $coupon = null ) {
			$coupon_types  = wc_get_coupon_types();
			$product_types = wc_get_product_coupon_types();
			$nonce         = wp_create_nonce( 'wc_sc_search_attribute_terms' );

			// Process saved attribute IDs.
			$get_attribute_ids = function ( $key ) use ( $coupon_id ) {
				$ids = get_post_meta( $coupon_id, $key, true );
				if ( empty( $ids ) ) {
					return array();
				}
				return is_array( $ids ) ? $ids : explode( '|', $ids );
			};

			$saved_product_attribute_ids         = $get_attribute_ids( 'wc_sc_product_attribute_ids' );
			$saved_exclude_product_attribute_ids = $get_attribute_ids( 'wc_sc_exclude_product_attribute_ids' );

			$non_product_coupon_types = array();
			foreach ( $coupon_types as $type => $label ) {
				if ( ! in_array( $type, $product_types, true ) ) {
					$non_product_coupon_types[] = $label;
				}
			}

			$non_product_coupon_types_label = ! empty( $non_product_coupon_types )
				? '"' . implode( ', ', $non_product_coupon_types ) . '"'
				: '';

			// Render saved attributes.
			$render_saved_attributes = function ( $ids ) {
				foreach ( $ids as $term_id ) {
					$term = get_term( $term_id );
					if ( $term && ! is_wp_error( $term ) && taxonomy_is_product_attribute( $term->taxonomy ) ) {
						$label = wc_attribute_label( $term->taxonomy ) . ' : ' . $term->name;
						printf(
							'<option value="%d" selected="selected">%s</option>',
							absint( $term_id ),
							esc_html( $label )
						);
					}
				}
			};
			?>
			<div class="options_group smart-coupons-field">
				<p class="form-field">
					<label for="wc_sc_product_attribute_ids"><?php esc_html_e( 'Product attributes', 'woocommerce-smart-coupons' ); ?></label>
					<select id="wc_sc_product_attribute_ids"
							name="wc_sc_product_attribute_ids[]"
							style="width: 50%;"
							class="wc-sc-ajax-attribute-select"
							multiple="multiple"
							data-placeholder="<?php esc_attr_e( 'Select product attributes...', 'woocommerce-smart-coupons' ); ?>">
						<?php $render_saved_attributes( $saved_product_attribute_ids ); ?>
					</select>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo wc_help_tip(
						sprintf(
							// translators: %s: List of non-product coupon types.
							esc_html__(
								'Product attributes that the coupon will be applied to, or that need to be in the cart in order for the %s to be applied.',
								'woocommerce-smart-coupons'
							),
							$non_product_coupon_types_label
						)
					);
					?>
				</p>
				<p class="form-field">
					<label for="wc_sc_exclude_product_attribute_ids"><?php esc_html_e( 'Exclude attributes', 'woocommerce-smart-coupons' ); ?></label>
					<select id="wc_sc_exclude_product_attribute_ids"
							name="wc_sc_exclude_product_attribute_ids[]"
							style="width: 50%;"
							class="wc-sc-ajax-exclude-attribute-select"
							multiple="multiple"
							data-placeholder="<?php esc_attr_e( 'No product attributes', 'woocommerce-smart-coupons' ); ?>">
						<?php $render_saved_attributes( $saved_exclude_product_attribute_ids ); ?>
					</select>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo wc_help_tip(
						sprintf(
							// translators: %s: List of non-product coupon types.
							esc_html__(
								'Product attributes that the coupon will not be applied to, or that cannot be in the cart in order for the %s to be applied.',
								'woocommerce-smart-coupons'
							),
							$non_product_coupon_types_label
						)
					);
					?>
				</p>
			</div>
			<?php
			// Inject JavaScript using wp_add_inline_script.
			wp_register_script( 'wc-smart-admin-user-restriction-coupons', false, array(), $this->get_smart_coupons_version(), true ); // Register fallback script for inline usage.
			wp_enqueue_script( 'wc-smart-admin-user-restriction-coupons' );

			wp_localize_script(
				'wc-smart-admin-user-restriction-coupons',
				'wcSmartCouponsUserRestrictionData',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => $nonce,
					'strings'  => array(
						'placeholder' => esc_html__( 'Select product attributes...', 'woocommerce-smart-coupons' ),
					),
				)
			);

			$inline_js = <<<JS
				(function($) {
					function initSmartCouponAttributeSelect(selector) {
						$(selector).select2({
							placeholder: function() {
								return $(this).data('placeholder') || wcSmartCouponsUserRestrictionData.strings.placeholder;
							},
							minimumInputLength: 2,
							ajax: {
								url: wcSmartCouponsUserRestrictionData.ajax_url,
								dataType: 'json',
								delay: 250,
								data: function(params) {
									return {
										action: 'wc_sc_search_attribute_terms',
										q: params.term,
										security: wcSmartCouponsUserRestrictionData.nonce,
									};
								},
								processResults: function(data) {
									return {
										results: data
									};
								},
								cache: true
							},
							width: 'resolve'
						});
					}

					initSmartCouponAttributeSelect('.wc-sc-ajax-attribute-select');
					initSmartCouponAttributeSelect('.wc-sc-ajax-exclude-attribute-select');
				})(jQuery);
JS;

			wp_add_inline_script( 'wc-smart-admin-user-restriction-coupons', $inline_js );
		}

		/**
		 * Save coupon by product attribute data in meta
		 *
		 * @param  Integer   $post_id The coupon post ID.
		 * @param  WC_Coupon $coupon    The coupon object.
		 */
		public function process_meta( $post_id = 0, $coupon = null ) {
			if ( empty( $post_id ) ) {
				return;
			}

			$coupon = new WC_Coupon( $coupon );

			$is_callable_coupon_update_meta = $this->is_callable( $coupon, 'update_meta_data' );

            $product_attribute_ids = ( isset( $_POST['wc_sc_product_attribute_ids'] ) ) ? wc_clean( wp_unslash( $_POST['wc_sc_product_attribute_ids'] ) ) : array(); // phpcs:ignore
			$product_attribute_ids = implode( '|', $product_attribute_ids ); // Store attribute ids as delimited data instead of serialized data.
			if ( true === $is_callable_coupon_update_meta ) {
				$coupon->update_meta_data( 'wc_sc_product_attribute_ids', $product_attribute_ids );
			} else {
				update_post_meta( $post_id, 'wc_sc_product_attribute_ids', $product_attribute_ids );
			}

            $exclude_product_attribute_ids = ( isset( $_POST['wc_sc_exclude_product_attribute_ids'] ) ) ? wc_clean( wp_unslash( $_POST['wc_sc_exclude_product_attribute_ids'] ) ) : array(); // phpcs:ignore
			$exclude_product_attribute_ids = implode( '|', $exclude_product_attribute_ids ); // Store attribute ids as delimited data instead of serialized data.
			if ( true === $is_callable_coupon_update_meta ) {
				$coupon->update_meta_data( 'wc_sc_exclude_product_attribute_ids', $exclude_product_attribute_ids );
			} else {
				update_post_meta( $post_id, 'wc_sc_exclude_product_attribute_ids', $exclude_product_attribute_ids );
			}

			if ( $this->is_callable( $coupon, 'save' ) ) {
				$coupon->save();
			}
		}

		/**
		 * Function to validate applicable StoreCredit against on line item.
		 *
		 * @param bool            $valid Coupon validity.
		 * @param WC_Product|null $product Product object.
		 * @param WC_Coupon|null  $coupon Coupon object.
		 * @param array|null      $cart_item Cart Item.
		 * @return bool           $valid
		 */
		public function validate_applicable_store_credit_against_line_item( $valid = false, $product = null, $coupon = null, $cart_item = null ) {
			$valid = $this->validate( $valid, $product, $coupon, $cart_item );
			return $valid;
		}

		/**
		 * Function to validate coupons against product attributes to determine eligibility for application in the cart.
		 *
		 * @param bool            $valid Coupon validity.
		 * @param WC_Product|null $product Product object.
		 * @param WC_Coupon|null  $coupon Coupon object.
		 * @param array|null      $cart_item Cart Item.
		 * @return bool           $valid
		 */
		public function coupon_validate( $valid = false, $product = null, $coupon = null, $cart_item = null ) {
			$backtrace = wp_list_pluck( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), 'function' ); // phpcs:ignore
			// If coupon is already invalid, no need for further checks.
			// Ignore this check if the discount type is a non-product-type discount.
			if ( true !== $valid && ! in_array( 'handle_non_product_type_coupons', $backtrace, true ) ) {
				return $valid;
			}
			$valid = $this->validate( $valid, $product, $coupon, $cart_item );
			return $valid;
		}

		/**
		 * Function to validate coupons for against product attributes
		 *
		 * @param bool            $valid Coupon validity.
		 * @param WC_Product|null $product Product object.
		 * @param WC_Coupon|null  $coupon Coupon object.
		 * @param array|null      $values Values.
		 * @return bool           $valid
		 */
		public function validate( $valid = false, $product = null, $coupon = null, $values = null ) {

			if ( empty( $product ) || empty( $coupon ) ) {
				return $valid;
			}

			if ( $this->is_wc_gte_30() ) {
				$coupon_id = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
			} else {
				$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
			}

			if ( ! empty( $coupon_id ) ) {
				$coupon = ( ! empty( $coupon_id ) ) ? new WC_Coupon( $coupon_id ) : null;

				if ( $this->is_callable( $coupon, 'get_meta' ) ) {
					$product_attribute_ids         = $coupon->get_meta( 'wc_sc_product_attribute_ids' );
					$exclude_product_attribute_ids = $coupon->get_meta( 'wc_sc_exclude_product_attribute_ids' );
				} else {
					$product_attribute_ids         = get_post_meta( $coupon_id, 'wc_sc_product_attribute_ids', true );
					$exclude_product_attribute_ids = get_post_meta( $coupon_id, 'wc_sc_exclude_product_attribute_ids', true );
				}

				if ( ! empty( $product_attribute_ids ) || ! empty( $exclude_product_attribute_ids ) ) {
					$current_product_attribute_ids = $this->get_product_attributes( $product );
					if ( ! empty( $product_attribute_ids ) ) {
						$product_attribute_ids = explode( '|', $product_attribute_ids );
					}
					$product_attribute_found = true;
					if ( ! empty( $product_attribute_ids ) && is_array( $product_attribute_ids ) ) {
						$common_attribute_ids = array_intersect( $product_attribute_ids, $current_product_attribute_ids );
						if ( count( $common_attribute_ids ) > 0 ) {
							$product_attribute_found = true;
						} else {
							$product_attribute_found = false;
						}
					}

					if ( ! empty( $exclude_product_attribute_ids ) ) {
						$exclude_product_attribute_ids = explode( '|', $exclude_product_attribute_ids );
					}

					$exclude_attribute_found = false;
					if ( ! empty( $exclude_product_attribute_ids ) && is_array( $exclude_product_attribute_ids ) ) {
						$common_exclude_attribute_ids = array_intersect( $exclude_product_attribute_ids, $current_product_attribute_ids );
						if ( count( $common_exclude_attribute_ids ) > 0 ) {
							$exclude_attribute_found = true;
						} else {
							$exclude_attribute_found = false;
						}
					}

					if ( false === $product_attribute_found && false === $exclude_attribute_found ) {
						$product_parent_id = is_callable( array( $product, 'get_parent_id' ) ) ? $product->get_parent_id() : 0;
						if ( ! empty( $product_parent_id ) ) {
							$parent_product = ( function_exists( 'wc_get_product' ) ) ? wc_get_product( $product_parent_id ) : null;
							if ( ! empty( $parent_product ) ) {
								$parent_product_attribute_ids = $this->get_product_attributes( $parent_product );
								if ( apply_filters( 'wc_sc_check_parent_attributes', true, $product ) && ! empty( $product_attribute_ids ) && is_array( $product_attribute_ids ) ) {
									$parent_product_attribute_id = array_intersect( $product_attribute_ids, $parent_product_attribute_ids );
									if ( count( $parent_product_attribute_id ) > 0 ) {
										$product_attribute_found = true;
									} else {
										$product_attribute_found = false;
									}
								}
								if ( apply_filters( 'wc_sc_check_parent_attributes', true, $product ) && ! empty( $exclude_product_attribute_ids ) && is_array( $exclude_product_attribute_ids ) ) {
									$exclude_parent_product_attribute_id = array_intersect( $exclude_product_attribute_ids, $parent_product_attribute_ids );
									if ( count( $exclude_parent_product_attribute_id ) > 0 ) {
										$exclude_attribute_found = true;
									} else {
										$exclude_attribute_found = false;
									}
								}
							}
						}
					}

					$valid = ( $product_attribute_found && ! $exclude_attribute_found ) ? true : false;
				}
			}

			return $valid;
		}

		/**
		 * Function to get product attributes of a given product.
		 *
		 * @param  WC_Product $product Product.
		 * @return array  $product_attributes_ids IDs of product attributes
		 */
		public function get_product_attributes( $product = null ) {

			$product_attributes_ids = array();

			if ( ! is_a( $product, 'WC_Product' ) ) {
				// Check if product id has been passed.
				if ( is_numeric( $product ) ) {
					$product = wc_get_product( $product );
				}
			}

			if ( ! is_a( $product, 'WC_Product' ) ) {
				return $product_attribute_ids;
			}

			$product_attributes = $product->get_attributes();
			if ( ! empty( $product_attributes ) ) {
				if ( true === $product->is_type( 'variation' ) ) {
					foreach ( $product_attributes as $variation_taxonomy => $variation_slug ) {
						$variation_attribute = get_term_by( 'slug', $variation_slug, $variation_taxonomy );
						if ( is_object( $variation_attribute ) ) {
							$product_attributes_ids[] = $variation_attribute->term_id;
						}
					}
				} else {
					$product_id = ( is_object( $product ) && is_callable( array( $product, 'get_id' ) ) ) ? $product->get_id() : 0;
					if ( ! empty( $product_id ) ) {
						$is_variable = $product->is_type( 'variable' );
						foreach ( $product_attributes as $attribute ) {
							if ( true === $is_variable && isset( $attribute['is_variation'] ) && ! empty( $attribute['is_variation'] ) ) {
								continue;
							}
							if ( isset( $attribute['is_taxonomy'] ) && ! empty( $attribute['is_taxonomy'] ) ) {
								$attribute_taxonomy_name = $attribute['name'];
								$product_term_ids        = wc_get_product_terms( $product_id, $attribute_taxonomy_name, array( 'fields' => 'ids' ) );
								if ( ! empty( $product_term_ids ) && is_array( $product_term_ids ) ) {
									foreach ( $product_term_ids as $product_term_id ) {
										$product_attributes_ids[] = $product_term_id;
									}
								}
							}
						}
					}
				}
			}

			return $product_attributes_ids;
		}

		/**
		 * Function to validate non product type coupons against product attribute restriction
		 * We need to remove coupon if it does not pass attribute validation even for single cart item in case of non product type coupons e.g fixed_cart, smart_coupon since these coupon type require all products in the cart to be valid
		 *
		 * @param boolean      $valid Coupon validity.
		 * @param WC_Coupon    $coupon Coupon object.
		 * @param WC_Discounts $discounts Discounts object.
		 * @throws Exception Validation exception.
		 * @return boolean  $valid Coupon validity
		 */
		public function handle_non_product_type_coupons( $valid = true, $coupon = null, $discounts = null ) {

			// If coupon is already invalid, no need for further checks.
			if ( true !== $valid ) {
				return $valid;
			}

			if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
				return $valid;
			}

			if ( $this->is_wc_gte_30() ) {
				$coupon_id     = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
				$discount_type = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_discount_type' ) ) ) ? $coupon->get_discount_type() : '';
			} else {
				$coupon_id     = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
				$discount_type = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
			}

			if ( ! empty( $coupon_id ) ) {
				if ( $this->is_callable( $coupon, 'get_meta' ) ) {
					$product_attribute_ids         = $coupon->get_meta( 'wc_sc_product_attribute_ids' );
					$exclude_product_attribute_ids = $coupon->get_meta( 'wc_sc_exclude_product_attribute_ids' );
				} else {
					$product_attribute_ids         = get_post_meta( $coupon_id, 'wc_sc_product_attribute_ids', true );
					$exclude_product_attribute_ids = get_post_meta( $coupon_id, 'wc_sc_exclude_product_attribute_ids', true );
				}
				// If product attributes are not set in coupon, stop further processing and return from here.
				if ( empty( $product_attribute_ids ) && empty( $exclude_product_attribute_ids ) ) {
					return $valid;
				}
			} else {
				return $valid;
			}

			$product_coupon_types = wc_get_product_coupon_types();

			// Proceed if it is non product type coupon.
			if ( ! in_array( $discount_type, $product_coupon_types, true ) ) {
				if ( class_exists( 'WC_Discounts' ) && isset( WC()->cart ) ) {
					$wc_cart           = WC()->cart;
					$wc_discounts      = new WC_Discounts( $wc_cart );
					$items_to_validate = array();
					if ( is_callable( array( $wc_discounts, 'get_items_to_validate' ) ) ) {
						$items_to_validate = $wc_discounts->get_items_to_validate();
					} elseif ( is_callable( array( $wc_discounts, 'get_items' ) ) ) {
						$items_to_validate = $wc_discounts->get_items();
					} elseif ( isset( $wc_discounts->items ) && is_array( $wc_discounts->items ) ) {
						$items_to_validate = $wc_discounts->items;
					}
					if ( ! empty( $items_to_validate ) && is_array( $items_to_validate ) ) {
						$valid_products   = array();
						$invalid_products = array();
						foreach ( $items_to_validate as $item ) {
							$cart_item    = clone $item; // Clone the item so changes to wc_discounts item do not affect the originals.
							$item_product = isset( $cart_item->product ) ? $cart_item->product : null;
							$item_object  = isset( $cart_item->object ) ? $cart_item->object : null;
							if ( ! is_null( $item_product ) && ! is_null( $item_object ) ) {
								if ( $coupon->is_valid_for_product( $item_product, $item_object ) ) {
									$valid_products[] = $item_product;
								} else {
									$invalid_products[] = $item_product;
								}
							}
						}

						// If cart does not have any valid product then throw Exception.
						if ( 0 === count( $valid_products ) ) {
							$error_message = __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce-smart-coupons' );
							$error_code    = defined( 'E_WC_COUPON_NOT_APPLICABLE' ) ? E_WC_COUPON_NOT_APPLICABLE : 0;
							throw new Exception( $error_message, $error_code );
						} elseif ( count( $invalid_products ) > 0 && ! empty( $exclude_product_attribute_ids ) ) {

							$exclude_product_attribute_ids = explode( '|', $exclude_product_attribute_ids );
							$excluded_products             = array();
							foreach ( $invalid_products as $invalid_product ) {
								$product_attributes = $this->get_product_attributes( $invalid_product );
								if ( ! empty( $product_attributes ) && is_array( $product_attributes ) ) {
									$common_exclude_attribute_ids = array_intersect( $exclude_product_attribute_ids, $product_attributes );
									if ( count( $common_exclude_attribute_ids ) > 0 ) {
										$excluded_products[] = $invalid_product->get_name();
									} else {
										$product_parent_id = is_callable( array( $invalid_product, 'get_parent_id' ) ) ? $invalid_product->get_parent_id() : 0;
										if ( ! empty( $product_parent_id ) ) {
											$parent_product = ( function_exists( 'wc_get_product' ) ) ? wc_get_product( $product_parent_id ) : '';
											if ( ! empty( $parent_product ) ) {
												$parent_product_attribute_ids = $this->get_product_attributes( $parent_product );
												if ( apply_filters( 'wc_sc_check_parent_attributes', true, $invalid_product ) && ! empty( $exclude_product_attribute_ids ) && is_array( $exclude_product_attribute_ids ) ) {
													$exclude_parent_product_attribute_id = array_intersect( $exclude_product_attribute_ids, $parent_product_attribute_ids );
													if ( count( $exclude_parent_product_attribute_id ) > 0 ) {
														$excluded_products[] = $invalid_product->get_name();
													}
												}
											}
										}
									}
								}
							}

							if ( count( $excluded_products ) > 0 ) {
								// If cart contains any excluded product and it is being excluded from our excluded product attributes then throw Exception.
								/* translators: 1. Singular/plural label for product(s) 2. Excluded product names */
								$error_message = sprintf( __( 'Sorry, this coupon is not applicable to the %1$s: %2$s.', 'woocommerce-smart-coupons' ), _n( 'product', 'products', count( $excluded_products ), 'woocommerce-smart-coupons' ), implode( ', ', $excluded_products ) );
								$error_code    = defined( 'E_WC_COUPON_EXCLUDED_PRODUCTS' ) ? E_WC_COUPON_EXCLUDED_PRODUCTS : 0;
								throw new Exception( $error_message, $error_code );
							}
						}
					}
				}
			}

			return $valid;
		}

		/**
		 * Add meta in export headers
		 *
		 * @param  array $headers Existing headers.
		 * @return array
		 */
		public function export_headers( $headers = array() ) {

			$headers['wc_sc_product_attribute_ids']         = __( 'Product Attributes', 'woocommerce-smart-coupons' );
			$headers['wc_sc_exclude_product_attribute_ids'] = __( 'Exclude Attributes', 'woocommerce-smart-coupons' );

			return $headers;

		}

		/**
		 * Post meta defaults for product attribute ids meta
		 *
		 * @param  array $defaults Existing postmeta defaults.
		 * @return array $defaults Modified postmeta defaults
		 */
		public function postmeta_defaults( $defaults = array() ) {

			$defaults['wc_sc_product_attribute_ids']         = '';
			$defaults['wc_sc_exclude_product_attribute_ids'] = '';

			return $defaults;
		}

		/**
		 * Make meta data of product attribute ids protected
		 *
		 * @param bool   $protected Is protected.
		 * @param string $meta_key The meta key.
		 * @param string $meta_type The meta type.
		 * @return bool $protected
		 */
		public function make_action_meta_protected( $protected = false, $meta_key = '', $meta_type = '' ) {

			$sc_product_attribute_keys = array(
				'wc_sc_product_attribute_ids',
				'wc_sc_exclude_product_attribute_ids',
			);

			if ( in_array( $meta_key, $sc_product_attribute_keys, true ) ) {
				return true;
			}

			return $protected;
		}

		/**
		 * Add product's attribute in coupon meta
		 *
		 * @param  array $data The row data.
		 * @param  array $post The POST values.
		 * @return array Modified data
		 */
		public function generate_coupon_attribute_meta( $data = array(), $post = array() ) {

            $product_attribute_ids = ( isset( $post['wc_sc_product_attribute_ids'] ) ) ? wc_clean( wp_unslash( $post['wc_sc_product_attribute_ids'] ) ) : array(); // phpcs:ignore
			$data['wc_sc_product_attribute_ids'] = implode( '|', $product_attribute_ids ); // Store attribute ids as delimited data instead of serialized data.

            $exclude_product_attribute_ids = ( isset( $post['wc_sc_exclude_product_attribute_ids'] ) ) ? wc_clean( wp_unslash( $post['wc_sc_exclude_product_attribute_ids'] ) ) : array(); // phpcs:ignore
			$data['wc_sc_exclude_product_attribute_ids'] = implode( '|', $exclude_product_attribute_ids ); // Store attribute ids as delimited data instead of serialized data.

			return $data;
		}

		/**
		 * Function to copy product's attribute meta in newly generated coupon
		 *
		 * @param  array $args The arguments.
		 */
		public function copy_coupon_attributes_meta( $args = array() ) {

			$new_coupon_id = ( ! empty( $args['new_coupon_id'] ) ) ? absint( $args['new_coupon_id'] ) : 0;
			$coupon        = ( ! empty( $args['ref_coupon'] ) ) ? $args['ref_coupon'] : false;

			if ( empty( $new_coupon_id ) || empty( $coupon ) ) {
				return;
			}

			if ( $this->is_wc_gte_30() ) {
				$product_attribute_ids         = $coupon->get_meta( 'wc_sc_product_attribute_ids' );
				$exclude_product_attribute_ids = $coupon->get_meta( 'wc_sc_exclude_product_attribute_ids' );
			} else {
				$old_coupon_id                 = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
				$product_attribute_ids         = get_post_meta( $old_coupon_id, 'wc_sc_product_attribute_ids', true );
				$exclude_product_attribute_ids = get_post_meta( $old_coupon_id, 'wc_sc_exclude_product_attribute_ids', true );
			}

			$new_coupon = new WC_Coupon( $new_coupon_id );

			if ( $this->is_callable( $new_coupon, 'update_meta_data' ) && $this->is_callable( $new_coupon, 'save' ) ) {
				$new_coupon->update_meta_data( 'wc_sc_product_attribute_ids', $product_attribute_ids );
				$new_coupon->update_meta_data( 'wc_sc_exclude_product_attribute_ids', $exclude_product_attribute_ids );
				$new_coupon->save();
			} else {
				update_post_meta( $new_coupon_id, 'wc_sc_product_attribute_ids', $product_attribute_ids );
				update_post_meta( $new_coupon_id, 'wc_sc_exclude_product_attribute_ids', $exclude_product_attribute_ids );
			}

		}

		/**
		 * Get product attribute terms id-name map.
		 *
		 * @param string|int[] $term The searched term or array of term IDs.
		 * @param bool         $for_ajax Whether this function is called via Ajax.
		 *
		 * @return array Map of term IDs and their respective labels.
		 */
		public function search_values( $term = '', $for_ajax = true ) {
			$rule_values = array();

			if ( empty( $term ) ) {
				return $rule_values;
			}

			if ( ! current_user_can( 'edit_products' ) ) {
				wp_die( -1 );
			}

			$attributes = wc_get_attribute_taxonomies();

			if ( empty( $attributes ) || ! is_array( $attributes ) ) {
				return $rule_values;
			}

			if ( ! $for_ajax && ! is_array( $term ) ) {
				$term = (array) $term;
			}

			$search_text        = is_string( $term ) ? strtolower( trim( $term ) ) : '';
			$term_ids           = ! $for_ajax ? array_map( 'intval', $term ) : array();
			$matched_taxonomies = array();

			if ( ! empty( $search_text ) ) {

				foreach ( $attributes as $attribute_obj ) {
					if ( empty( $attribute_obj->attribute_name ) ) {
						continue;
					}

					$attribute_name  = $attribute_obj->attribute_name;
					$attribute_label = ! empty( $attribute_obj->attribute_label ) ? $attribute_obj->attribute_label : $attribute_name;

					// Match partial.
					if ( preg_match( '/\b' . preg_quote( $search_text, '/' ) . '/i', strtolower( $attribute_label ) ) ) {
						$matched_taxonomies[] = wc_attribute_taxonomy_name( $attribute_name );
					}
				}
			}

			// Prepare query arguments for retrieving terms.
			$args = apply_filters(
				'wc_sc_search_attribute_terms_args',
				array(
					'taxonomy'   => $matched_taxonomies,
					'orderby'    => 'name',
					'hide_empty' => false,
					'number'     => 100,
				),
				$term,
				$matched_taxonomies
			);

			if ( $for_ajax && $search_text && empty( $matched_taxonomies ) ) {
				$args['name__like'] = $search_text;
			} elseif ( ! empty( $term_ids ) ) {
				$args['include'] = $term_ids;
			}

			// Fetch the terms.
			$attribute_terms = get_terms( $args );

			if ( is_wp_error( $attribute_terms ) || empty( $attribute_terms ) || ! is_array( $attribute_terms ) ) {
				return $rule_values;
			}

			// Process the terms and map them to their labels.
			foreach ( $attribute_terms as $term_obj ) {

				if ( empty( $term_obj->term_id ) || empty( $term_obj->taxonomy ) || ! taxonomy_is_product_attribute( $term_obj->taxonomy ) ) {
					continue;
				}

				$taxonomy       = $term_obj->taxonomy;
				$taxonomy_label = wc_attribute_label( $taxonomy );

				$rule_values[ $term_obj->term_id ] = sprintf(
					'%1$s : %2$s',
					esc_html( ! empty( $taxonomy_label ) ? $taxonomy_label : $taxonomy ),
					esc_html( $term_obj->name ? $term_obj->name : $term_obj->term_id )
				);
			}

			return $rule_values;
		}

		/**
		 * Handles AJAX request to search for attribute terms.
		 *
		 * Sends a JSON error response if the current user does not have permission to edit products.
		 *
		 * @return void Sends a JSON response.
		 */
		public function search_attribute_terms_ajax() {
			$security = isset( $_GET['security'] ) ? sanitize_text_field( wp_unslash( $_GET['security'] ) ) : '';

			if ( empty( $security ) || ! wp_verify_nonce( $security, 'wc_sc_search_attribute_terms' ) ) {
				wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
			}

			if ( ! current_user_can( 'edit_products' ) ) {
				wp_send_json_error( __( 'Unauthorized', 'woocommerce-smart-coupons' ) );
			}

			$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

			$results = $this->search_values( $term );

			$response = array();

			foreach ( $results as $id => $label ) {
				$response[] = array(
					'id'   => $id,
					'text' => $label,
				);
			}

			wp_send_json( $response );
		}
	}
}

WC_SC_Coupons_By_Product_Attribute::get_instance();
