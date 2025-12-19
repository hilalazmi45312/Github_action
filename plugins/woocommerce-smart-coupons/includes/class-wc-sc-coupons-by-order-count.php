<?php
/**
 * Class to handle feature: Coupons Qualified on Order Count
 *
 * @author      StoreApps
 * @category    Admin
 * @package     woocommerce-smart-coupons/includes
 * @version     1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Coupons_By_Order_Count' ) ) {

	/**
	 * Class WC_SC_Coupons_By_Order_Count
	 */
	class WC_SC_Coupons_By_Order_Count {

		/**
		 * Instance of the class
		 *
		 * @var WC_SC_Coupons_By_Order_Count
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {
			add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'usage_restriction' ), 10, 2 );
			add_action( 'woocommerce_coupon_options_save', array( $this, 'process_meta' ), 10, 2 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'validate' ), 11, 3 );
			add_filter( 'wc_smart_coupons_export_headers', array( $this, 'export_headers' ) );
			add_filter( 'wc_sc_export_coupon_meta', array( $this, 'export_coupon_meta_data' ), 10, 2 );
			add_filter( 'smart_coupons_parser_postmeta_defaults', array( $this, 'postmeta_defaults' ) );
			add_filter( 'sc_generate_coupon_meta', array( $this, 'generate_coupon_meta' ), 10, 2 );
			add_filter( 'is_protected_meta', array( $this, 'make_action_meta_protected' ), 10, 3 );
			add_action( 'wc_sc_new_coupon_generated', array( $this, 'copy_coupon_meta' ) );
		}

		/**
		 * Singleton get instance
		 *
		 * @return WC_SC_Coupons_By_Order_Count
		 */
		public static function get_instance() {
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
		 * @return mixed of function call
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
		 * Render admin field for Order Count restriction
		 *
		 * @param int       $coupon_id Coupon ID.
		 * @param WC_Coupon $coupon    The coupon object.
		 */
		public function usage_restriction( $coupon_id = 0, $coupon = null ) {
			try {
				$restriction_data = array(
					'operator' => '',
					'count'    => '',
				);

				if ( ! ( $coupon instanceof WC_Coupon ) ) {
					$coupon = ( ! empty( $coupon_id ) ) ? new WC_Coupon( $coupon_id ) : null;
				}

				if ( $this->is_callable( $coupon, 'get_meta' ) ) {
					$saved = $coupon->get_meta( 'wc_sc_order_count_restriction', true );
				} else {
					$saved = $this->get_post_meta( $coupon_id, 'wc_sc_order_count_restriction', true );
				}

				if ( ! empty( $saved ) && is_array( $saved ) ) {
					$restriction_data = wp_parse_args( $saved, $restriction_data );
				}
				?>
				<div class="options_group smart-coupons-field">
					<p class="form-field">
						<label for="wc_sc_order_count_restriction_operator"><?php echo esc_html( _x( 'Number of orders made', 'Coupon restriction label', 'woocommerce-smart-coupons' ) ); ?></label>
						<select id="wc_sc_order_count_restriction_operator" name="wc_sc_order_count_restriction[operator]" style="width: 25%;">
							<option value=""><?php echo esc_html( _x( 'No Restriction', 'Operator option', 'woocommerce-smart-coupons' ) ); ?></option>
							<option value="=" <?php selected( $restriction_data['operator'], '=' ); ?>><?php echo esc_html( ucfirst( $this->operator_to_word( '=' ) ) ); ?></option>
							<option value="<" <?php selected( $restriction_data['operator'], '<' ); ?>><?php echo esc_html( ucfirst( $this->operator_to_word( '<' ) ) ); ?></option>
							<option value=">" <?php selected( $restriction_data['operator'], '>' ); ?>><?php echo esc_html( ucfirst( $this->operator_to_word( '>' ) ) ); ?></option>
						</select>
						<input type="number" min="1" step="1" style="width: 25%;" name="wc_sc_order_count_restriction[count]" value="<?php echo esc_attr( $restriction_data['count'] ); ?>" placeholder="<?php echo esc_attr( _x( 'Order count', 'Placeholder for order count', 'woocommerce-smart-coupons' ) ); ?>" />
						<?php
							$statuses = get_option( 'wc_sc_valid_order_statuses_for_coupon_auto_generation', wc_get_is_paid_statuses() );

							$status_labels = array();
						if ( ! empty( $statuses ) && is_array( $statuses ) ) {
							foreach ( $statuses as $status ) {
								$status_slug     = str_replace( 'wc-', '', $status );
								$status_labels[] = wc_get_order_status_name( $status_slug );
							}
						}

							$status_text = $status_labels ? implode( ', ', $status_labels ) : esc_html__( 'completed', 'woocommerce-smart-coupons' );

							$tooltip_text = sprintf(
								/* translators: %s: order statuses */
								esc_html_x(
									'Coupon will apply only when customer order count meets this condition. Order(s) since all time with status: %s.',
									'Combined tooltip for Order count restriction',
									'woocommerce-smart-coupons'
								),
								$status_text
							);

							echo wc_help_tip( $tooltip_text ); // phpcs:ignore
						?>
					</p>
				</div>
				<?php
			} catch ( Exception $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Save coupon meta
		 *
		 * @param int       $post_id Coupon ID.
		 * @param WC_Coupon $coupon  Coupon object.
		 */
		public function process_meta( $post_id = 0, $coupon = null ) {
			try {
				if ( empty( $post_id ) ) {
					return;
				}

				$coupon = new WC_Coupon( $coupon );

				$raw = isset( $_POST['wc_sc_order_count_restriction'] ) ? wp_unslash( $_POST['wc_sc_order_count_restriction'] ) : array(); // phpcs:ignore

				// Ensure array to avoid notices if something posts a string.
				if ( ! is_array( $raw ) ) {
					$raw = array();
				}

				$operator = ! empty( $raw['operator'] ) ? $raw['operator'] : ''; // phpcs:ignore
				if ( ! in_array( $operator, array( '=', '<', '>' ), true ) ) {
					$operator = '';
				}

				$count = isset( $raw['count'] ) ? absint( $raw['count'] ) : 0;

				$value = array(
					'operator' => $operator,
					'count'    => $count,
				);

				if (
					$this->is_callable( $coupon, 'update_meta_data' ) &&
					$this->is_callable( $coupon, 'delete_meta_data' ) &&
					$this->is_callable( $coupon, 'save' )
				) {
					if ( empty( $operator ) && empty( $count ) ) {
						// Remove meta when no restriction set to avoid stale data.
						$coupon->delete_meta_data( 'wc_sc_order_count_restriction' );
					} else {
						$coupon->update_meta_data( 'wc_sc_order_count_restriction', $value );
					}
					$coupon->save();
				} else {
					if ( empty( $operator ) && empty( $count ) ) {
						// Remove meta when no restriction set to avoid stale data.
						$this->delete_meta_data( $post_id, 'wc_sc_order_count_restriction' );
					} else {
						$this->update_meta_data( $post_id, 'wc_sc_order_count_restriction', $value );
					}
				}
			} catch ( Exception $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Convert operator symbol to readable English word
		 *
		 * @param string $operator Operator symbol (=, <, >).
		 * @return string
		 */
		private function operator_to_word( $operator ) {
			switch ( $operator ) {
				case '=':
					return _x( 'equal to', 'Operator word for order restriction', 'woocommerce-smart-coupons' );
				case '<':
					return _x( 'less than', 'Operator word for order restriction', 'woocommerce-smart-coupons' );
				case '>':
					return _x( 'greater than', 'Operator word for order restriction', 'woocommerce-smart-coupons' );
				default:
					return '';
			}
		}

		/**
		 * Get customer's order count.
		 *
		 * @param int   $customer_id Customer ID.
		 * @param array $statuses    Array of order statuses.
		 * @return int
		 */
		private function get_customer_order_count( $customer_id, $statuses ) {

			if ( empty( $customer_id ) || empty( $statuses ) || ! is_array( $statuses ) ) {
				return 0;
			}

			$args = array(
				'customer_id' => $customer_id,
				'status'      => $statuses,
				'return'      => 'ids',
				'limit'       => -1,
			);

			$order_ids = wc_get_orders( $args );

			return is_array( $order_ids ) ? count( $order_ids ) : 0;
		}

		/**
		 * Validate coupon
		 *
		 * @param bool         $valid     Is valid or not.
		 * @param WC_Coupon    $coupon    Coupon object.
		 * @param WC_Discounts $discounts Discount object.
		 * @return bool | Exception
		 * @throws Exception When validation fails.
		 */
		public function validate( $valid, $coupon, $discounts ) {

			if ( false === $valid || empty( $coupon ) ) {
				return $valid;
			}

			if ( ! $coupon instanceof WC_Coupon ) {
				return $valid;
			}

			if ( $this->is_wc_gte_30() ) {
				$coupon_id = ( is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
			} else {
				$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
			}

			$coupon = ( ! empty( $coupon_id ) ) ? new WC_Coupon( $coupon_id ) : null;

			if ( $this->is_callable( $coupon, 'get_meta' ) ) {
				$restriction = $coupon->get_meta( 'wc_sc_order_count_restriction', true );
			} else {
				$restriction = $this->get_post_meta( $coupon_id, 'wc_sc_order_count_restriction', true );
			}

			$restriction = (array) $restriction;

			if ( empty( $restriction ) || ! is_array( $restriction ) ) {
				return $valid;
			}

			$operator = ! empty( $restriction['operator'] ) ? $restriction['operator'] : '';
			$count    = isset( $restriction['count'] ) ? absint( $restriction['count'] ) : 0;

			if ( empty( $operator ) || empty( $count ) ) {
				return $valid;
			}

			if ( ! is_user_logged_in() ) {
				$message = esc_html( _x( 'You must be logged in to use this coupon.', 'Coupon login required message', 'woocommerce-smart-coupons' ) );

				$this->throw_validation_error( $message, $coupon, $operator, $count, 0 );
			}

			$customer_id = get_current_user_id();

			/**
			 * Filters the order statuses used to determine the counted orders for coupon logic.
			 *
			 * This filter allows customization of which WooCommerce order statuses are considered
			 * when calculating the number of orders associated with a coupon. By default, only
			 * orders with the 'wc-completed' status are counted.
			 *
			 * @hook wc_sc_coupons_by_order_count_statuses
			 *
			 * @param array     $statuses Array of order status slugs to be counted. Default: array( 'wc-completed' ).
			 * @param WC_Coupon $coupon   The coupon object for which the order count is being calculated.
			 *
			 * @return array Modified array of order status slugs to be counted.
			 */
			$statuses = apply_filters( 'wc_sc_coupons_by_order_count_statuses', get_option( 'wc_sc_valid_order_statuses_for_coupon_auto_generation', wc_get_is_paid_statuses() ), $coupon );

			$order_count = $this->get_customer_order_count( $customer_id, $statuses );

			/**
			 * Filter the computed order count for a user when determining coupon eligibility by order count.
			 *
			 * This filter allows customization of the order count used for coupon logic.
			 *
			 * @since 9.52.0
			 *
			 * @param int   $order_count The computed order count for the user.
			 * @param array $args {
			 *     Arguments passed to the filter.
			 *
			 *     @type int       $customer_id The current user's ID.
			 *     @type WC_Coupon $coupon      The coupon object.
			 * }
			 * @return int Modified order count.
			 */
			$order_count = (int) apply_filters(
				'wc_sc_coupons_by_order_count_customer_order_total',
				$order_count,
				array(
					'customer_id' => $customer_id,
					'coupon'      => $coupon,
				)
			);

			$is_valid = false;
			switch ( $operator ) {
				case '=':
					$is_valid = ( $order_count === $count );
					break;
				case '<':
					$is_valid = ( $order_count < $count );
					break;
				case '>':
					$is_valid = ( $order_count > $count );
					break;
			}

			if ( ! $is_valid ) {
				$message = sprintf(
					// translators: %1$s: coupon code, %2$s: operator in words, %3$d: required order count.
					_x( 'Coupon code %1$s is valid only if your total completed orders are %2$s %3$d.', 'Validation error for order count restriction', 'woocommerce-smart-coupons' ),
					'<code>' . $coupon->get_code() . '</code>',
					$this->operator_to_word( $operator ),
					$count
				);

				$this->throw_validation_error( $message, $coupon, $operator, $count, $customer_id );
			}

			return $valid;
		}

		/**
		 * Throw a validation error exception with proper message and filters.
		 *
		 * @param string    $message     The error message to be displayed.
		 * @param WC_Coupon $coupon      Coupon object.
		 * @param string    $operator    Operator symbol.
		 * @param int       $count       Required order count.
		 * @param int       $customer_id Customer ID (0 if guest).
		 * @throws Exception If the coupon validation fails based on the order count and operator.
		 */
		private function throw_validation_error( $message, $coupon, $operator, $count, $customer_id ) {
			/**
			 * Filter validation failure message before display/throw.
			 *
			 * @param string $message  Final message.
			 * @param array  $args {
			 *     Arguments passed to the filter.
			 *
			 *     @type WC_Coupon $coupon   Coupon object.
			 *     @type int       $count    Required order count.
			 *     @type string    $operator Operator used (=, <, >).
			 *     @type int       $user_id  Current user ID.
			 * }
			 */
			$message = apply_filters(
				'wc_sc_coupons_by_order_count_error_message',
				$message,
				array(
					'original_message' => $message,
					'coupon_obj'       => $coupon,
					'count'            => $count,
					'operator'         => $operator,
					'user_id'          => $customer_id,
				)
			);

			throw new Exception( $message );
		}

		/**
		 * Export headers
		 *
		 * @param array $headers Headers.
		 * @return array
		 */
		public function export_headers( $headers = array() ) {
			$headers['wc_sc_order_count_restriction'] = _x( 'Order Count Restriction', 'Export header', 'woocommerce-smart-coupons' );
			return $headers;
		}

		/**
		 * Export coupon meta
		 *
		 * @param string $meta_value Meta value.
		 * @param array  $args       Args.
		 * @return string
		 */
		public function export_coupon_meta_data( $meta_value = '', $args = array() ) {
			if ( ! empty( $args['meta_key'] ) && 'wc_sc_order_count_restriction' === $args['meta_key'] && ! empty( $args['meta_value'] ) ) {
				return $args['meta_value'];
			}
			return $meta_value;
		}

		/**
		 * Postmeta defaults
		 *
		 * @param array $defaults Defaults.
		 * @return array
		 */
		public function postmeta_defaults( $defaults = array() ) {
			$defaults['wc_sc_order_count_restriction'] = '';
			return $defaults;
		}

		/**
		 * Generate coupon meta
		 *
		 * @param array $data Row data.
		 * @param array $post Post data.
		 * @return array
		 */
		public function generate_coupon_meta( $data = array(), $post = array() ) {
			if ( ! empty( $post['wc_sc_order_count_restriction'] ) ) {
				$data['wc_sc_order_count_restriction'] = maybe_serialize( $post['wc_sc_order_count_restriction'] );
			}
			return $data;
		}

		/**
		 * Copy coupon meta to generated coupon
		 *
		 * @param array $args Args.
		 */
		public function copy_coupon_meta( $args = array() ) {
			$this->copy_coupon_meta_data(
				$args,
				array( 'wc_sc_order_count_restriction' )
			);
		}

		/**
		 * Protect meta key
		 *
		 * @param bool   $protected Is protected.
		 * @param string $meta_key  Meta key.
		 * @param string $meta_type Meta type.
		 * @return bool
		 */
		public function make_action_meta_protected( $protected, $meta_key, $meta_type ) {
			if ( 'wc_sc_order_count_restriction' === $meta_key ) {
				return true;
			}
			return $protected;
		}
	}
}

WC_SC_Coupons_By_Order_Count::get_instance();
