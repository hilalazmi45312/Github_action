<?php
/**
 * Fee Validation.
 *
 * @package Extra Fees for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'EFW_Fee_Validation' ) ) {

	/**
	 * Class.
	 */
	class EFW_Fee_Validation {

		/**
		 * Fee Type.
		 *
		 * @var string
		 * */
		protected $fee_type;

		/**
		 * Gateway/Order ID.
		 *
		 * @var string/int
		 * */
		protected $type_id;

		/**
		 * Cart Object.
		 *
		 * @var object
		 * */
		protected $cart_object;

		/**
		 * User ID.
		 *
		 * @var int
		 * */
		protected $user_id;

		/**
		 * Date filter.
		 *
		 * @var bool
		 * */
		protected $date_filter;

		/**
		 * Product filter.
		 *
		 * @var bool
		 * */
		protected $product_filter;

		/**
		 * User filter.
		 *
		 * @var bool
		 * */
		protected $user_filter;

		/**
		 * Order Object.
		 *
		 * @var bool
		 * */
		protected $order;

		/**
		 * Class Initialization.
		 *
		 * @param string $type Fee Type.
		 * @param string $type_id Gateway/Order ID.
		 * @param string $cart_object Cart/Order Object.
		 * @param string $user_id User ID.
		 * @param string $order Order.
		 */
		public function __construct( $type, $type_id, $cart_object, $user_id, $order ) {
			$this->fee_type    = $type;
			$this->type_id     = $type_id;
			$this->cart_object = $cart_object;
			$this->user_id     = $user_id;
			$this->order       = $order;
		}

		/**
		 * Is valid fee?
		 *
		 * @param string $type Fee Type.
		 * @param string $type_id Gateway/Order ID.
		 * @param string $cart_object Cart/Order Object.
		 * @param string $user_id User ID.
		 *
		 * @return bool
		 */
		public static function is_valid( $type, $type_id, $cart_object, $user_id, $order = false ) {
			$validation = new self( $type, $type_id, $cart_object, $user_id, $order );

			return $validation->validate_fee();
		}

		/**
		 * User Filter data.
		 *
		 * @return bool
		 */
		public function get_user_filter_data() {

			if ( 'order' === $this->fee_type ) {
				$user_filter_data = array(
					'user_filter_type'  => get_option( 'efw_ordertotalfee_user_filter' ),
					'include_users'     => get_option( 'efw_ordertotalfee_include_users' ),
					'exclude_users'     => get_option( 'efw_ordertotalfee_exclude_users' ),
					'include_user_role' => get_option( 'efw_ordertotalfee_include_userrole' ),
					'exclude_user_role' => get_option( 'efw_ordertotalfee_exclude_userrole' ),
				);
			} elseif ( 'gateway' === $this->fee_type ) {
				$user_filter_data = array(
					'user_filter_type'  => get_option( 'efw_user_filter_type_for_' . $this->type_id ),
					'include_users'     => get_option( 'efw_include_user_for_' . $this->type_id ),
					'exclude_users'     => get_option( 'efw_exclude_user_for_' . $this->type_id ),
					'include_user_role' => get_option( 'efw_include_userrole_for_' . $this->type_id ),
					'exclude_user_role' => get_option( 'efw_exclude_userrole_for_' . $this->type_id ),
				);
			} elseif ( 'shipping' === $this->fee_type ) {
				$user_filter_data = array(
					'user_filter_type'  => get_option( 'efw_shipping_user_filter_type_' . $this->type_id ),
					'include_users'     => get_option( 'efw_shipping_include_users_' . $this->type_id ),
					'exclude_users'     => get_option( 'efw_shipping_exclude_users_' . $this->type_id ),
					'include_user_role' => get_option( 'efw_shipping_include_userroles_' . $this->type_id ),
					'exclude_user_role' => get_option( 'efw_shipping_exclude_userroles_' . $this->type_id ),
				);
			}

			return $user_filter_data;
		}

		/**
		 * Fee Validation.
		 *
		 * @return bool
		 */
		public function validate_fee() {
			$return = true;

			if ( ! $this->validate_user() ) {
				$return = false;
			} elseif ( ! $this->validate_product_category() ) {
				$return = false;
			} elseif ( ! $this->validate_coupon() ) {
				$return = false;
			}

			return $return;
		}

		/**
		 * User Validation.
		 *
		 * @return bool
		 */
		public function validate_user() {
			$return = true;

			$filter_data      = $this->get_user_filter_data();
			$user_filter_type = $filter_data['user_filter_type'];

			switch ( $user_filter_type ) {
				case '2':
					$return       = false;
					$include_user = $filter_data['include_users'];
					if ( in_array( $this->user_id, $include_user ) ) {
						return true;
					}

					break;
				case '3':
					$exclude_user = $filter_data['exclude_users'];
					if ( in_array( $this->user_id, $exclude_user ) ) {
						return false;
					}

					break;
				case '4':
					$user              = get_userdata( $this->user_id );
					$return            = false;
					$include_user_role = $filter_data['include_user_role'];
					// Logged in user restriction.
					if ( is_object($user) && efw_check_is_array( $user->roles ) ) {
						foreach ( $user->roles as $role ) {
							if ( in_array( $role, $include_user_role ) ) {
								return true;
							}
						}
					} elseif ( in_array( 'guest', $include_user_role ) ) {// Guest user restriction.
							return true;
					}

					break;
				case '5':
					$user = get_userdata( $this->user_id );

					$exclude_user_role = $filter_data['exclude_user_role'];
					// Logged in user restriction.
					if ( is_object($user) && efw_check_is_array( $user->roles ) ) {
						foreach ( $user->roles as $role ) {
							if ( in_array( $role, $exclude_user_role ) ) {
								return false;
							}
						}
					} elseif ( in_array( 'guest', $exclude_user_role ) ) {// Guest user restriction.
							return false;
					}

					break;

			}

			return $return;
		}

		/**
		 * Product/Category filter data.
		 *
		 * @return bool
		 */
		public function get_product_filter_data() {

			if ( 'order' === $this->fee_type ) {
				$product_filter_data = array(
					'product_filter_type' => get_option( 'efw_ordertotalfee_product_filter' ),
					'include_products'    => get_option( 'efw_ordertotalfee_include_products' , array()),
					'exclude_products'    => get_option( 'efw_ordertotalfee_exclude_products' , array()),
					'include_additional_products'    => get_option( 'efw_ordertotalfee_include_additional_products' , array()),
					'exclude_additional_products'    => get_option( 'efw_ordertotalfee_exclude_additional_products' , array()),
					'include_categories'  => get_option( 'efw_ordertotalfee_include_categories' , array()),
					'exclude_categories'  => get_option( 'efw_ordertotalfee_exclude_categories' , array()),
				);
			} elseif ( 'gateway' === $this->fee_type ) {
				$product_filter_data = array(
					'product_filter_type' => get_option( 'efw_product_filter_type_for_' . $this->type_id ),
					'include_products'    => get_option( 'efw_include_product_for_' . $this->type_id , array()),
					'include_additional_products'    => get_option( 'efw_include_additional_product_for_' . $this->type_id , array()),
					'exclude_products'    => get_option( 'efw_exclude_product_for_' . $this->type_id , array()),
					'exclude_additional_products'    => get_option( 'efw_exclude_additional_product_for_' . $this->type_id , array()),
					'include_categories'  => get_option( 'efw_include_category_for_' . $this->type_id , array()),
					'exclude_categories'  => get_option( 'efw_exclude_category_for_' . $this->type_id , array()),
				);
			} elseif ( 'shipping' === $this->fee_type ) {
				$product_filter_data = array(
					'product_filter_type' => get_option( 'efw_shipping_product_filter_type_' . $this->type_id ),
					'include_products'    => get_option( 'efw_shipping_include_products_' . $this->type_id , array()),
					'include_additional_products'    => get_option( 'efw_shipping_include_additional_products_' . $this->type_id , array()),
					'exclude_products'    => get_option( 'efw_shipping_exclude_products_' . $this->type_id , array()),
					'exclude_additional_products'    => get_option( 'efw_shipping_exclude_additional_products_' . $this->type_id , array()),
					'include_categories'  => get_option( 'efw_shipping_include_categories_' . $this->type_id , array()),
					'exclude_categories'  => get_option( 'efw_shipping_exclude_categories_' . $this->type_id , array()),
				);
			}

			return $product_filter_data;
		}

		/**
		 * Product/Category Validation.
		 *
		 * @return bool
		 */
		public function validate_product_category() {
			$return      = true;
			$filter_data = $this->get_product_filter_data();

			$product_filter_type = $filter_data['product_filter_type'];

			// Return if selected as all products.
			if ( '1' === $product_filter_type ) {
				return $return;
			}

			$cart_contents = ( is_object( $this->cart_object ) && efw_check_is_array( $this->cart_object->get_cart() ) ) ? $this->cart_object->get_cart() : $this->cart_object;

			if ( ! efw_check_is_array( $cart_contents ) ) {
				return $return;
			}

			foreach ( $cart_contents as $cart_content ) {

				switch ( $product_filter_type ) {
					case '2':
						$return          = false;
						$include_product = $filter_data['include_products'];
						$product_id      = empty( $cart_content['variation_id'] ) ? $cart_content['product_id'] : $cart_content['variation_id'];
						// Return if any selected products in the cart.
						if ( in_array( $product_id, $include_product ) ) {
							return true;
						}

						break;
					case '3':
						$exclude_product = $filter_data['exclude_products'];
						$product_id      = empty( $cart_content['variation_id'] ) ? $cart_content['product_id'] : $cart_content['variation_id'];
						// excluded products.
						if ( in_array( $product_id, $exclude_product ) ) {
							return false;
						}

						break;
					case '4':
						$return = false;
						// included categories.
						$product_categories = get_the_terms( $cart_content['product_id'], 'product_cat' );
						$include_category   = $filter_data['include_categories'];
						$include_additional_products   = $filter_data['include_additional_products'];

						if ( efw_check_is_array( $product_categories ) ) {
							foreach ( $product_categories as $product_category ) {
								// return if any selected categories products in the cart.
								if ( in_array( $product_category->term_id, $include_category ) ) {
									return true;
								}
							}
						}

						$product_id      = empty( $cart_content['variation_id'] ) ? $cart_content['product_id'] : $cart_content['variation_id'];
						// Return if any selected products in the cart.
						if ( in_array( $product_id, $include_additional_products ) ) {
							return true;
						}

						break;
					case '5':
						// excluded categories.
						$product_categories = get_the_terms( $cart_content['product_id'], 'product_cat' );
						$exclude_category   = $filter_data['exclude_categories'];
						$exclude_additional_products   = $filter_data['exclude_additional_products'];

						if ( efw_check_is_array( $product_categories ) ) {
							foreach ( $product_categories as $product_category ) {
								if ( in_array( $product_category->term_id, $exclude_category ) ) {
									return false;
								}
							}
						}

						$product_id      = empty( $cart_content['variation_id'] ) ? $cart_content['product_id'] : $cart_content['variation_id'];
						// Return if any selected products in the cart.
						if ( in_array( $product_id, $exclude_additional_products ) ) {
							return false;
						}
				}
			}

			return $return;
		}

		/**
		 * Coupon Validation.
		 *
		 * @return bool
		 */
		public function validate_coupon() {
			$return      = true;
			$applied_coupons = $this->order ? $this->order->get_coupon_codes() : WC()->cart->get_applied_coupons() ;

			if ( ! efw_check_is_array( $applied_coupons ) ) {
				return $return;
			}

			foreach ( $applied_coupons as $code ) {
				$coupon_obj = new WC_Coupon( $code );

				if ('order' == $this->fee_type) {
					$enable_fee = get_post_meta( $coupon_obj->get_id(), '_efw_enable_order_fee', true );
					if ('yes' == $enable_fee) {
						return false;
					}
				} else if ('gateway' == $this->fee_type) {
					$enable_fee = get_post_meta( $coupon_obj->get_id(), '_efw_enable_gateway_fee', true );
					if ('yes' == $enable_fee) {
						return false;
					}
				} else if ('shipping' == $this->fee_type) {
					$enable_fee = get_post_meta( $coupon_obj->get_id(), '_efw_enable_shipping_fee', true );
					if ('yes' == $enable_fee) {
						return false;
					}
				}               
			}

			return $return;
		}
	}
}
