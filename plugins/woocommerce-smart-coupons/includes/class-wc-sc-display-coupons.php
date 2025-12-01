<?php
/**
 * Smart Coupons Display
 *
 * @author      StoreApps
 * @since       3.3.0
 * @version     4.16.1
 *
 * @package     woocommerce-smart-coupons/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Display_Coupons' ) ) {

	/**
	 * Class for handling display feature for coupons
	 */
	class WC_SC_Display_Coupons {

		/**
		 * Variable to hold instance of WC_SC_Display_Coupons
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Custom endpoint name.
		 *
		 * @var string
		 */
		public static $endpoint;

		/**
		 * Session key for available coupons
		 *
		 * @var $session_key_available_coupons
		 */
		public $session_key_available_coupons = 'wc_sc_display_available_coupons';

		/**
		 * Constructor
		 */
		private function __construct() {

			add_action( 'wp_ajax_sc_get_available_coupons', array( $this, 'get_available_coupons_html' ) );
			add_action( 'wp_ajax_nopriv_sc_get_available_coupons', array( $this, 'get_available_coupons_html' ) );

			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'show_attached_gift_certificates' ) );
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'override_add_to_cart_button' ), 10, 3 );

			add_action( 'woocommerce_before_cart', array( $this, 'show_available_coupons_after_cart_table' ) );
			add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'show_available_coupons_before_checkout_form' ), 11 );

			add_filter( 'wc_sc_show_as_valid', array( $this, 'show_as_valid' ), 10, 2 );

			add_action( 'wp_loaded', array( $this, 'myaccount_display_coupons' ) );

			add_action( 'add_meta_boxes', array( $this, 'add_generated_coupon_details' ) );
			add_action( 'woocommerce_view_order', array( $this, 'generated_coupon_details_view_order' ) );
			add_action( 'woocommerce_email_after_order_table', array( $this, 'generated_coupon_details_after_order_table' ), 10, 3 );

			add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'woocommerce_update_order_review_fragments' ) );

			add_action( 'init', array( $this, 'endpoint_hooks' ) );

			add_filter( 'woocommerce_available_variation', array( $this, 'modify_available_variation' ), 10, 3 );

			add_action( 'wp_loaded', array( $this, 'maybe_enable_store_notice_for_coupon' ) );

			add_action( 'wc_ajax_wc_sc_get_attached_coupons', array( $this, 'ajax_get_attached_coupons' ) );

			add_filter( 'safecss_filter_attr_allow_css', array( $this, 'check_safecss' ), 20, 2 );

			add_filter( 'woocommerce_demo_store', array( $this, 'maybe_change_store_notice' ), 10, 2 );

			add_action( 'woocommerce_cart_item_set_quantity', array( $this, 'available_coupons_on_cart_update' ), 99 );
			add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'available_coupons_on_cart_update' ), 99 );
			add_action( 'woocommerce_cart_item_removed', array( $this, 'available_coupons_on_cart_update' ), 99 );

			add_action( 'wp_footer', array( $this, 'storewide_notice' ) );

		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return result of function call
		 */
		public function __call( $function_name, $arguments = array() ) {

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
		 * Get single instance of WC_SC_Display_Coupons
		 *
		 * @return WC_SC_Display_Coupons Singleton object of WC_SC_Display_Coupons
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Function to show available coupons accordion
		 *
		 * @param string $available_coupons_heading Existing heading.
		 * @param string $page The page.
		 * @param array  $args Additional arguments.
		 */
		public function show_available_coupons( $available_coupons_heading = '', $page = '', $args = array() ) {
			if ( empty( $page ) ) {
				global $post;
				if ( is_object( $post ) || isset( $post->post_name ) ) {
					$page = $post->post_name;
				}
			}
			ob_start();
			$this->get_show_available_coupons_html( $available_coupons_heading, $args );
			$coupons_html = ob_get_clean();

			if ( empty( $coupons_html ) ) {
				if ( has_block( 'woocommerce/checkout' ) ) {
					wc_enqueue_js(
						"
						const selectors = [
							'.wp-block-woocommerce-smart-coupons-send-coupon-form',
							'.wp-block-woocommerce-smart-coupons-available-coupons'
						];

						function hideIfEmpty(block) {
							if (block) {
								const innerDivs = block.querySelectorAll('div');
								if (
									innerDivs.length === 0 ||
									(innerDivs.length === 1 && innerDivs[0].innerHTML.trim() === '')
								) {
									block.style.display = 'none';
								}
							}
						}

						function checkAndHideBlocks() {
							selectors.forEach(selector => {
								const block = document.querySelector(selector);
								hideIfEmpty(block);
							});
						}

						// Run once initially
						checkAndHideBlocks();

						// Observe changes in the body (DOM mutations)
						const observer = new MutationObserver(function(mutations) {
							mutations.forEach(function(mutation) {
								if (mutation.addedNodes.length > 0) {
									checkAndHideBlocks();
								}
							});
						});

						observer.observe(document.body, { childList: true, subtree: true });
					"
					);
				}
				return;
			}

			if ( is_ajax() ) {
				echo $coupons_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {

				if ( ! wp_style_is( 'smart-coupon' ) ) {
					wp_enqueue_style( 'smart-coupon' );
				}

				$design           = get_option( 'wc_sc_setting_coupon_design', 'basic' );
				$background_color = get_option( 'wc_sc_setting_coupon_background_color', '#39cccc' );
				$foreground_color = get_option( 'wc_sc_setting_coupon_foreground_color', '#30050b' );
				$third_color      = get_option( 'wc_sc_setting_coupon_third_color', '#39cccc' );

				$valid_designs = $this->get_valid_coupon_designs();

				if ( ! in_array( $design, $valid_designs, true ) ) {
					$design = 'basic';
				}

				if ( 'custom-design' !== $design ) {
					if ( ! wp_style_is( 'smart-coupon-designs' ) ) {
						wp_enqueue_style( 'smart-coupon-designs' );
					}

					$backtrace = wp_list_pluck( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), 'function' ); // phpcs:ignore

					if ( ! in_array( 'load_script_data_for_blocks', $backtrace, true ) ) {
						?>
						<style type="text/css">
							:root {
								--sc-color1: <?php echo esc_html( $background_color ); ?>;
								--sc-color2: <?php echo esc_html( $foreground_color ); ?>;
								--sc-color3: <?php echo esc_html( $third_color ); ?>;
							}
						</style>
						<?php
					}
				}
				?>
				<style>
					#coupons_list {
						margin-bottom: <?php echo ( ( ! has_block( 'woocommerce/checkout' ) && ( ! is_user_logged_in() || is_checkout() ) ) || ( is_user_logged_in() && is_cart() ) ) ? '1.5em' : '0'; ?>;
					}
					.sc-accordion-header {
						display: flex;
						align-items: center;
						justify-content: space-between;
						border: 1px solid;
						padding: 1em .5rem;
						border-radius: 4px;
						cursor: pointer;
					}
					a.sc-accordion-header {
						padding-left: 0.7rem;
						padding-right: 0.7rem;
						color: #6d6d6d;
					}
					a.sc-accordion-header:focus{
						outline: none;
					}
					.sc-accordion-content {
						display: block;
						max-height: 0;
						overflow: hidden;
						background: #fdfdfd;
						transition: max-height 0.5s ease;
						overflow-y: auto;
					}
					.sc-accordion-content.open {
						max-height: 15rem;
					}
					#coupons_list[data-slide-enabled="false"] .sc-accordion-content {
						transition: none !important;
						display: none;
					}

					#coupons_list[data-slide-enabled="false"] .sc-accordion-content.open {
						display: block;
					}
					.sc-accordion-header .sc-arrow-icon {
						margin-left: 0.5em;
						vertical-align: middle;
						transition: transform 0.5s ease-in-out;
					}
					#coupons_list[data-slide-enabled="false"] .sc-accordion-header .sc-arrow-icon {
						transition: none !important;
					}
					.sc-accordion-header.open .sc-arrow-icon {
						transform: rotate(180deg);
					}

					.sc-accordion-content .blockOverlay {
						position: relative;
						height: 9rem;
					}
					.sc-accordion-content .sc-no-coupon {
						display: flex;
						justify-content: center;
						align-items: center;
						text-align: center;
						height: 9rem;
					}
				</style>
				<div id="coupons_list" data-slide-enabled="<?php echo apply_filters( 'wc_sc_enable_coupon_slide_animation', true ) ? 'true' : 'false'; ?>">
					<div class="sc-accordion-header">
						<?php echo esc_html__( stripslashes( $available_coupons_heading ), 'woocommerce-smart-coupons' );// phpcs:ignore ?>
						<svg class="sc-arrow-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
							<path d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.24 4.38a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"/>
						</svg>
					</div>
					<div class="sc-accordion-content"></div>
				</div>
				<?php
				if ( did_action( 'wc_smart_coupons_frontend_styles_and_scripts' ) <= 0 && ! wp_doing_ajax() ) {
					$this->frontend_styles_and_scripts( array( 'page' => $page ) );
				}
			}
		}

		/**
		 * Function to get available coupons
		 *
		 * @param string $available_coupons_heading Existing heading.
		 * @param array  $args Additional arguments.
		 *
		 * @return mixed HTML for available coupons.
		 */
		public function get_show_available_coupons_html( $available_coupons_heading = '', $args = array() ) {

			$max_coupon_to_show = get_option( 'wc_sc_setting_max_coupon_to_show', 5 );
			$max_coupon_to_show = apply_filters( 'wc_sc_max_coupon_to_show', $max_coupon_to_show, array( 'source' => $this ) );
			$max_coupon_to_show = absint( $max_coupon_to_show );

			$coupons = array();
			if ( $max_coupon_to_show > 0 ) {
				$coupons = ( ! in_array( $this->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) ? $this->sc_get_available_coupons_list_old( array(), $args ) : $this->sc_get_available_coupons_list( array(), $args );
			}

			if ( empty( $coupons ) ) {
				do_action(
					'wc_sc_no_available_coupons',
					array(
						'available_coupons_heading' => $available_coupons_heading,
					)
				);

				return;
			}

			$design        = get_option( 'wc_sc_setting_coupon_design', 'basic' );
			$valid_designs = $this->get_valid_coupon_designs();
			if ( ! in_array( $design, $valid_designs, true ) ) {
				$design = 'basic';
			}
			$show_coupon_description = get_option( 'smart_coupons_show_coupon_description', 'no' );

			?>
			<div id="sc-cc">
				<div id="all_coupon_container" class="sc-coupons-list">
					<?php
						$coupons_applied = ( is_object( WC()->cart ) && is_callable( array( WC()->cart, 'get_applied_coupons' ) ) ) ? WC()->cart->get_applied_coupons() : array();
					foreach ( $coupons as $code ) {

						$coupon_id = ( isset( $code->id ) && ! empty( $code->id ) ) ? absint( $code->id ) : ( isset( $code->ID ) && ! empty( $code->ID ) ? absint( $code->ID ) : 0 );

						if ( empty( $coupon_id ) ) {
							continue;
						}

						if ( $max_coupon_to_show <= 0 ) {
							break;
						}

						$coupon = new WC_Coupon( $coupon_id );

						if ( ! $coupon instanceof WC_Coupon || ! $this->is_callable( $coupon, 'get_code' ) ) {
							continue;
						}

						if ( in_array( strtolower( $coupon->get_code() ), array_map( 'strtolower', $coupons_applied ), true ) ) {
							continue;
						}

						if ( 'woocommerce_before_my_account' !== current_filter() && ! $this->is_valid( $coupon ) ) {

							// Filter to allow third party developers to show coupons which are invalid due to cart requirements like minimum order total or products.
							$wc_sc_force_show_coupon = apply_filters( 'wc_sc_force_show_invalid_coupon', false, array( 'coupon' => $coupon ) );
							if ( false === $wc_sc_force_show_coupon ) {
								continue;
							}
						}

						if ( $this->is_wc_gte_30() ) {
							if ( ! is_object( $coupon ) || ! is_callable( array( $coupon, 'get_id' ) ) ) {
								continue;
							}
							$coupon_id = $coupon->get_id();
							if ( empty( $coupon_id ) ) {
								continue;
							}
							$is_free_shipping = ( $coupon->get_free_shipping() ) ? 'yes' : 'no';
							$discount_type    = $coupon->get_discount_type();
							$expiry_date      = $coupon->get_date_expires();
							$coupon_code      = $coupon->get_code();
						} else {
							$coupon_id        = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
							$is_free_shipping = ( ! empty( $coupon->free_shipping ) ) ? $coupon->free_shipping : '';
							$discount_type    = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
							$expiry_date      = ( ! empty( $coupon->expiry_date ) ) ? $coupon->expiry_date : '';
							$coupon_code      = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
						}

						$coupon_amount = $this->get_amount( $coupon, true );

						$is_show_zero_amount_coupon = true;

						if ( ( empty( $coupon_amount ) ) && ( ( ! empty( $discount_type ) && ! in_array( $discount_type, array( 'free_gift', 'smart_coupon' ), true ) ) || ( 'yes' !== $is_free_shipping ) ) ) {
							if ( 'yes' !== $is_free_shipping ) {
								$is_show_zero_amount_coupon = false;
							}
						}

						$is_show_zero_amount_coupon = apply_filters( 'show_zero_amount_coupon', $is_show_zero_amount_coupon, array( 'coupon' => $coupon ) );

						if ( false === $is_show_zero_amount_coupon ) {
							continue;
						}

						if ( $this->is_wc_gte_30() && $expiry_date instanceof WC_DateTime ) {
							$expiry_date = ( is_callable( array( $expiry_date, 'getTimestamp' ) ) ) ? $expiry_date->getTimestamp() : null;
						} elseif ( ! is_int( $expiry_date ) ) {
							$expiry_date = $this->strtotime( $expiry_date );
						}

						if ( ! empty( $expiry_date ) && is_int( $expiry_date ) ) {
							$expiry_time = ( $this->is_callable( $coupon, 'get_meta' ) ) ? (int) $coupon->get_meta( 'wc_sc_expiry_time' ) : (int) get_post_meta( $coupon_id, 'wc_sc_expiry_time', true );
							if ( ! empty( $expiry_time ) ) {
								$expiry_date += $expiry_time; // Adding expiry time to expiry date.
							}
						}

						if ( empty( $discount_type ) || ( ! empty( $expiry_date ) && time() > $expiry_date ) ) {
							continue;
						}

						$coupon_post = get_post( $coupon_id );

						$coupon_data = $this->get_coupon_meta_data( $coupon );

						$coupon_type = ( ! empty( $coupon_data['coupon_type'] ) ) ? $coupon_data['coupon_type'] : '';

						if ( 'yes' === $is_free_shipping ) {
							if ( ! empty( $coupon_type ) ) {
								$coupon_type .= __( ' & ', 'woocommerce-smart-coupons' );
							}
							$coupon_type .= __( 'Free Shipping', 'woocommerce-smart-coupons' );
						}

						$coupon_description = '';
						if ( ! empty( $coupon_post->post_excerpt ) && 'yes' === $show_coupon_description ) {
							$coupon_description = $coupon_post->post_excerpt;
						}

						$is_percent = $this->is_percent_coupon( array( 'coupon_object' => $coupon ) );

						$args = array(
							'coupon_object'      => $coupon,
							'coupon_amount'      => $coupon_amount,
							'amount_symbol'      => ( true === $is_percent ) ? '%' : get_woocommerce_currency_symbol(),
							'discount_type'      => wp_strip_all_tags( $coupon_type ),
							'coupon_description' => ( ! empty( $coupon_description ) ) ? $coupon_description : wp_strip_all_tags( $this->generate_coupon_description( array( 'coupon_object' => $coupon ) ) ),
							'coupon_code'        => $coupon_code,
							'coupon_expiry'      => ( ! empty( $expiry_date ) ) ? $this->get_expiration_format( $expiry_date ) : __( 'Never expires', 'woocommerce-smart-coupons' ),
							'thumbnail_src'      => $this->get_coupon_design_thumbnail_src(
								array(
									'design'        => $design,
									'coupon_object' => $coupon,
								)
							),
							'classes'            => 'apply_coupons_credits',
							'template_id'        => $design,
							'is_percent'         => $is_percent,
						);

						wc_get_template( 'coupon-design/' . $design . '.php', $args, '', plugin_dir_path( WC_SC_PLUGIN_FILE ) . 'templates/' );

						$max_coupon_to_show--;

					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Get available coupon's HTML
		 */
		public function get_available_coupons_html() {
			check_ajax_referer( 'sc-get-available-coupons', 'security' );
			$this->show_available_coupons_before_checkout_form();
			die();
		}

		/**
		 * Function to show available coupons before checkout form
		 */
		public function show_available_coupons_before_checkout_form() {
			WC()->session->__unset( $this->session_key_available_coupons );
			$smart_coupon_cart_page_text = get_option( 'smart_coupon_cart_page_text' );
			$smart_coupon_cart_page_text = ( ! empty( $smart_coupon_cart_page_text ) ) ? $smart_coupon_cart_page_text : __( 'Available Coupons (click on a coupon to use it)', 'woocommerce-smart-coupons' );
			$this->show_available_coupons( $smart_coupon_cart_page_text );

		}

		/**
		 * Check if store credit is valid based on amount
		 *
		 * @param  boolean $is_valid Validity.
		 * @param  array   $args     Additional arguments.
		 * @return boolean           Validity.
		 */
		public function show_as_valid( $is_valid = false, $args = array() ) {

			$coupon = ( ! empty( $args['coupon_obj'] ) ) ? $args['coupon_obj'] : false;

			if ( empty( $coupon ) ) {
				return $is_valid;
			}

			if ( $this->is_wc_gte_30() ) {
				$discount_type = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_discount_type' ) ) ) ? $coupon->get_discount_type() : '';
			} else {
				$discount_type = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
			}

			$coupon_amount = $this->get_amount( $coupon, true );

			if ( true === $is_valid && 'smart_coupon' === $discount_type && empty( $coupon_amount ) ) {
				return false;
			}

			return $is_valid;
		}

		/**
		 * Hooks for handling display of coupons on My Account page
		 */
		public function myaccount_display_coupons() {

			$is_show_on_my_account = get_option( 'woocommerce_smart_coupon_show_my_account', 'yes' );

			if ( 'yes' !== $is_show_on_my_account ) {
				return;
			}

			if ( $this->is_wc_gte_26() ) {
				add_filter( 'woocommerce_get_query_vars', array( $this, 'sc_add_query_vars' ) );
				// Change the My Account page title.
				add_filter( 'the_title', array( $this, 'sc_endpoint_title' ) );
				// Inserting our new tab/page into the My Account page.
				add_filter( 'woocommerce_account_menu_items', array( $this, 'sc_new_menu_items' ) );
				add_action( 'woocommerce_account_' . self::$endpoint . '_endpoint', array( $this, 'sc_endpoint_content' ) );
			} else {
				add_action( 'woocommerce_before_my_account', array( $this, 'show_smart_coupon_balance' ) );
				add_action( 'woocommerce_before_my_account', array( $this, 'generated_coupon_details_before_my_account' ) );
			}

		}

		/**
		 * Function to show gift certificates that are attached with the product
		 */
		public function show_attached_gift_certificates() {
			global $post;

			if ( empty( $post->ID ) ) {
				return;
			}

			$is_show_associated_coupons = get_option( 'smart_coupons_is_show_associated_coupons', 'no' );

			if ( 'yes' !== $is_show_associated_coupons ) {
				return;
			}

			add_action( 'wp_footer', array( $this, 'attached_coupons_styles_and_scripts' ) );

			?>
			<div class="clear"></div>
			<div class="gift-certificates" style="display: none;"></div>
			<?php

		}

		/**
		 * Attached coupons styles and scripts
		 */
		public function attached_coupons_styles_and_scripts() {
			global $post;
			if ( ! wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery' );
			}
			$product_type = WC_Product_Factory::get_product_type( $post->ID );
			?>
			<script type="text/javascript">
				jQuery(function(){
					let couponDetails = {};
					let product_type = decodeURIComponent( '<?php echo rawurlencode( (string) $product_type ); ?>' );

					function reload_attached_coupons( product_id ) {
						if ( product_id != '' && product_id != undefined ) {
							jQuery('div.gift-certificates').empty().hide();

							if ( product_id in couponDetails ) {
								jQuery('div.gift-certificates').replaceWith( couponDetails[product_id] );
								return;
							}

							jQuery.ajax({
								url: '?wc-ajax=wc_sc_get_attached_coupons',
								type: 'POST',
								dataType: 'html',
								data: {
									product_id: product_id,
									security: decodeURIComponent( '<?php echo rawurlencode( wp_create_nonce( 'wc-sc-get-attached-coupons' ) ); ?>' )
								},
								success: function( response ) {
									if ( null !== response && typeof 'undefined' !== response && '' !== response ) {
										couponDetails[ product_id ] = response;
										jQuery('div.gift-certificates').replaceWith( response );
									}
								}
							});
						}
					}

					setTimeout(function(){
						let product_types = [ 'variable', 'variable-subscription', 'subscription_variation' ];
						if ( product_types.includes( product_type ) ) {
							var default_variation_id = jQuery('input[name=variation_id]').val();
							if ( default_variation_id != '' && default_variation_id != undefined ) {
								jQuery('input[name=variation_id]').val( default_variation_id ).trigger( 'change' );
							}
						} else {
							reload_attached_coupons( decodeURIComponent( '<?php echo rawurlencode( $post->ID ); ?>' ) );
						}
					}, 10);

					jQuery('input[name=variation_id]').on('change', function(){
						let variation_id = jQuery('input[name=variation_id]').val();
						reload_attached_coupons( variation_id );
					});

					jQuery('a.reset_variations').on('click', function(){
						jQuery('div.gift-certificates').hide();
					});
				});
			</script>
			<?php
		}

		/**
		 * Get attached coupon's details via AJAX
		 */
		public function ajax_get_attached_coupons() {

			check_ajax_referer( 'wc-sc-get-attached-coupons', 'security' );

			global $store_credit_label;

			$response = '';

			$post_product_id = ( ! empty( $_POST['product_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : 0; // phpcs:ignore

			if ( ! empty( $post_product_id ) ) {

				$_product = wc_get_product( $post_product_id );

				$coupon_titles = $this->get_coupon_titles( array( 'product_object' => $_product ) );

				if ( $this->is_wc_gte_30() ) {
					$product_type = ( is_object( $_product ) && is_callable( array( $_product, 'get_type' ) ) ) ? $_product->get_type() : '';
				} else {
					$product_type = ( ! empty( $_product->product_type ) ) ? $_product->product_type : '';
				}

				$sell_sc_at_less_price         = get_option( 'smart_coupons_sell_store_credit_at_less_price', 'no' );
				$generated_credit_includes_tax = $this->is_generated_store_credit_includes_tax();

				if ( 'yes' === $sell_sc_at_less_price ) {
					if ( is_a( $_product, 'WC_Product_Variable' ) ) {
						$price = ( is_object( $_product ) && is_callable( array( $_product, 'get_variation_regular_price' ) ) ) ? $_product->get_variation_regular_price( 'max' ) : 0;
					} else {
						$price = ( is_object( $_product ) && is_callable( array( $_product, 'get_regular_price' ) ) ) ? $_product->get_regular_price() : 0;
					}
				} else {
					if ( is_a( $_product, 'WC_Product_Variable' ) ) {
						$price = ( is_object( $_product ) && is_callable( array( $_product, 'get_variation_price' ) ) ) ? $_product->get_variation_price( 'max' ) : 0;
					} else {
						$price = ( is_object( $_product ) && is_callable( array( $_product, 'get_price' ) ) ) ? $_product->get_price() : 0;
					}
				}

				if ( $coupon_titles && count( $coupon_titles ) > 0 && ! empty( $price ) ) {

					$all_discount_types              = wc_get_coupon_types();
					$smart_coupons_product_page_text = get_option( 'smart_coupon_product_page_text' );
					$smart_coupons_product_page_text = ( ! empty( $smart_coupons_product_page_text ) ) ? $smart_coupons_product_page_text : __( 'You will get following coupon(s) when you buy this item:', 'woocommerce-smart-coupons' );

					$list_started = true;

					ob_start();

					foreach ( $coupon_titles as $coupon_title ) {

						$coupon = new WC_Coupon( $coupon_title );

						$is_callable_coupon_get_meta = $this->is_callable( $coupon, 'get_meta' );

						if ( $this->is_wc_gte_30() ) {
							if ( ! is_object( $coupon ) || ! is_callable( array( $coupon, 'get_id' ) ) ) {
								continue;
							}
							$coupon_id = $coupon->get_id();
							if ( empty( $coupon_id ) ) {
								continue;
							}
							$discount_type               = $coupon->get_discount_type();
							$is_free_shipping            = ( $coupon->get_free_shipping() ) ? 'yes' : 'no';
							$product_ids                 = $coupon->get_product_ids();
							$excluded_product_ids        = $coupon->get_excluded_product_ids();
							$product_categories          = $coupon->get_product_categories();
							$excluded_product_categories = $coupon->get_excluded_product_categories();
						} else {
							$coupon_id                   = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
							$discount_type               = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
							$is_free_shipping            = ( ! empty( $coupon->free_shipping ) ) ? $coupon->free_shipping : '';
							$product_ids                 = ( ! empty( $coupon->product_ids ) ) ? $coupon->product_ids : array();
							$excluded_product_ids        = ( ! empty( $coupon->exclude_product_ids ) ) ? $coupon->exclude_product_ids : array();
							$product_categories          = ( ! empty( $coupon->product_categories ) ) ? $coupon->product_categories : array();
							$excluded_product_categories = ( ! empty( $coupon->exclude_product_categories ) ) ? $coupon->exclude_product_categories : array();
						}

						$coupon_amount = $this->get_amount( $coupon, true );

						$is_pick_price_of_product = ( true === $is_callable_coupon_get_meta ) ? $coupon->get_meta( 'is_pick_price_of_product' ) : get_post_meta( $coupon_id, 'is_pick_price_of_product', true );

						if ( $list_started && ! empty( $discount_type ) ) {
							echo '<div class="gift-certificates">';
							echo '<br /><p>' . esc_html( wp_unslash( $smart_coupons_product_page_text ) ) . '';
							echo '<ul>';
							$list_started = false;
						}

						switch ( $discount_type ) {

							case 'smart_coupon':
								/* translators: %s: singular name for store credit */
								$credit_label = ! empty( $store_credit_label['singular'] ) ? sprintf( __( '%s of ', 'woocommerce-smart-coupons' ), esc_html( ucwords( $store_credit_label['singular'] ) ) ) : __( 'Store Credit of ', 'woocommerce-smart-coupons' );
								if ( 'yes' === $is_pick_price_of_product ) {
									$amount = ( $price > 0 ) ? $credit_label . wc_price( $price ) : '';
								} else {
									$amount = ( ! empty( $coupon_amount ) ) ? $credit_label . wc_price( $coupon_amount ) : '';
								}

								break;

							case 'fixed_cart':
								$amount = wc_price( $coupon_amount ) . esc_html__( ' discount on your entire purchase', 'woocommerce-smart-coupons' );
								break;

							case 'fixed_product':
								if ( ! empty( $product_ids ) || ! empty( $excluded_product_ids ) || ! empty( $product_categories ) || ! empty( $excluded_product_categories ) ) {
									$discount_on_text = esc_html__( 'some products', 'woocommerce-smart-coupons' );
								} else {
									$discount_on_text = esc_html__( 'all products', 'woocommerce-smart-coupons' );
								}
								$amount = wc_price( $coupon_amount ) . esc_html__( ' discount on ', 'woocommerce-smart-coupons' ) . $discount_on_text;
								break;

							case 'percent_product':
								if ( ! empty( $product_ids ) || ! empty( $excluded_product_ids ) || ! empty( $product_categories ) || ! empty( $excluded_product_categories ) ) {
									$discount_on_text = esc_html__( 'some products', 'woocommerce-smart-coupons' );
								} else {
									$discount_on_text = esc_html__( 'all products', 'woocommerce-smart-coupons' );
								}
								$amount = $coupon_amount . '%' . esc_html__( ' discount on ', 'woocommerce-smart-coupons' ) . $discount_on_text;
								break;

							case 'percent':
								if ( ! empty( $product_ids ) || ! empty( $excluded_product_ids ) || ! empty( $product_categories ) || ! empty( $excluded_product_categories ) ) {
									$discount_on_text = esc_html__( 'some products', 'woocommerce-smart-coupons' );
								} else {
									$discount_on_text = esc_html__( 'your entire purchase', 'woocommerce-smart-coupons' );
								}
								$max_discount_text = '';
								$max_discount      = $this->get_post_meta( $coupon_id, 'wc_sc_max_discount', true, true );
								if ( ! empty( $max_discount ) && is_numeric( $max_discount ) ) {
									/* translators: %s: Maximum coupon discount amount */
									$max_discount_text = sprintf( __( ' upto %s', 'woocommerce-smart-coupons' ), wc_price( $max_discount ) );
								}
								$amount = $coupon_amount . '%' . esc_html__( ' discount', 'woocommerce-smart-coupons' ) . $max_discount_text . esc_html__( ' on ', 'woocommerce-smart-coupons' ) . $discount_on_text;
								break;

							default:
								$default_coupon_type = ( ! empty( $all_discount_types[ $discount_type ] ) ) ? $all_discount_types[ $discount_type ] : ucwords( str_replace( array( '_', '-' ), ' ', $discount_type ) );
								$coupon_amount       = apply_filters( 'wc_sc_coupon_amount', $coupon_amount, $coupon );
								/* translators: 1. Discount type 2. Discount amount */
								$amount = sprintf( esc_html__( '%1$s coupon of %2$s', 'woocommerce-smart-coupons' ), $default_coupon_type, $coupon_amount );
								$amount = apply_filters( 'wc_sc_coupon_description', $amount, $coupon );
								break;

						}

						if ( 'yes' === $is_free_shipping && in_array( $discount_type, array( 'fixed_cart', 'fixed_product', 'percent_product', 'percent' ), true ) ) {
							/* translators: Add more detail to coupon description */
							$amount = sprintf( esc_html__( '%s Free Shipping', 'woocommerce-smart-coupons' ), ( ( ! empty( $coupon_amount ) ) ? $amount . esc_html__( ' &', 'woocommerce-smart-coupons' ) : '' ) );
						}

						if ( ! empty( $amount ) ) {
							// Allow third party developers to modify the text being shown for the linked coupons.
							$amount = apply_filters( 'wc_sc_linked_coupon_text', $amount, array( 'coupon' => $coupon ) );

							// Mostly escaped earlier hence not escaping because it might have some HTML code.
							echo '<li>' . $amount . '</li>'; // phpcs:ignore
						}
					}

					if ( ! $list_started ) {
						echo '</ul></p></div>';
					}

					$response = ob_get_clean();

				}
			}

			echo wp_kses_post( $response );

			die();
		}

		/**
		 * Function to allow CSS from this plugin
		 *
		 * @param boolean $allow_css Whether the CSS in the test string is considered safe.
		 * @param string  $css_test_string The CSS string to test.
		 * @return boolean
		 */
		public function check_safecss( $allow_css = false, $css_test_string = '' ) {
			$backtrace           = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );  // phpcs:ignore
			$backtrace_functions = wp_list_pluck( $backtrace, 'function' );
			$allowed_css         = array(
				'sc-color',
			);
			if ( false === $allow_css && in_array( 'sc_endpoint_content', $backtrace_functions, true ) ) {
				foreach ( $allowed_css as $css ) {
					if ( false !== strpos( $css_test_string, $css ) ) {
						$allow_css = true;
						break;
					}
				}
			}
			return $allow_css;
		}

		/**
		 * Replace Add to cart button with Select Option button for products which are created for purchasing credit, on shop page
		 *
		 * @param string           $html Original add to cart button HTML.
		 * @param mixed|WC_Product $product The product object.
		 * @param array            $args Additional arguments.
		 * @return string HTML of add to cart button
		 */
		public function override_add_to_cart_button( $html = '', $product = null, $args = array() ) {

			if ( is_null( $product ) || empty( $product ) || ! $product instanceof WC_Product ) {
				return $html;
			}
			$coupons = $this->get_coupon_titles( array( 'product_object' => $product ) );
			if ( empty( $coupons ) ) {
				return $html;
			}
			if ( $this->is_coupon_amount_pick_from_product_price( $coupons ) && ! ( $product->get_price() > 0 ) ) {

				$html = sprintf(
					'<a href="%s" class="%s">%s</a>',
					esc_url( $product->get_permalink() ),
					esc_attr( ! empty( $args['class'] ) ? $args['class'] : 'button' ),
					esc_html( get_option( 'sc_gift_certificate_shop_loop_button_text', __( 'Select options', 'woocommerce-smart-coupons' ) ) )
				);

			}
			return $html;
		}

		/**
		 * Function to show available coupons after cart table
		 */
		public function show_available_coupons_after_cart_table() {

			$smart_coupon_cart_page_text = get_option( 'smart_coupon_cart_page_text' );
			$smart_coupon_cart_page_text = ( ! empty( $smart_coupon_cart_page_text ) ) ? $smart_coupon_cart_page_text : __( 'Available Coupons (click on a coupon to use it)', 'woocommerce-smart-coupons' );
			$this->show_available_coupons( $smart_coupon_cart_page_text );

		}

		/**
		 * Function to display current balance associated with Gift Certificate
		 */
		public function show_smart_coupon_balance() {
			global $store_credit_label;

			$smart_coupon_myaccount_page_text = get_option( 'smart_coupon_myaccount_page_text' );

			/* translators: %s: plural name for store credit */
			$smart_coupons_myaccount_page_text = ( ! empty( $smart_coupon_myaccount_page_text ) ) ? $smart_coupon_myaccount_page_text : ( ! empty( $store_credit_label['plural'] ) ? sprintf( __( 'Available Coupons & %s', 'woocommerce-smart-coupons' ), ucwords( $store_credit_label['plural'] ) ) : __( 'Available Coupons & Store Credits', 'woocommerce-smart-coupons' ) );
			$this->show_available_coupons( $smart_coupons_myaccount_page_text, 'myaccount' );

		}

		/**
		 * Display generated coupon's details on My Account page
		 */
		public function generated_coupon_details_before_my_account() {
			$show_coupon_received_on_my_account = get_option( 'show_coupon_received_on_my_account', 'no' );

			if ( is_user_logged_in() && 'yes' === $show_coupon_received_on_my_account ) {
				$user_id = get_current_user_id();
				$this->get_generated_coupon_data( '', $user_id, true, true );
			}
		}

		/**
		 * Add new query var.
		 *
		 * @param array $vars The query vars.
		 * @return array
		 */
		public function sc_add_query_vars( $vars = array() ) {

			$vars[ self::$endpoint ] = self::$endpoint;
			return $vars;
		}

		/**
		 * Set endpoint title.
		 *
		 * @param string $title The title of coupon page.
		 * @return string
		 */
		public function sc_endpoint_title( $title ) {
			global $wp_query;

			$is_endpoint = isset( $wp_query->query_vars[ self::$endpoint ] );

			if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
				// New page title.
				$title = __( 'Coupons', 'woocommerce-smart-coupons' );
				remove_filter( 'the_title', array( $this, 'sc_endpoint_title' ) );
			}

			return $title;
		}

		/**
		 * Insert the new endpoint into the My Account menu.
		 *
		 * @param array $items Existing menu items.
		 * @return array
		 */
		public function sc_new_menu_items( $items ) {

			// Remove the menu items.
			if ( isset( $items['edit-address'] ) ) {
				$edit_address = $items['edit-address'];
				unset( $items['edit-address'] );
			}

			if ( isset( $items['payment-methods'] ) ) {
				$payment_methods = $items['payment-methods'];
				unset( $items['payment-methods'] );
			}

			if ( isset( $items['edit-account'] ) ) {
				$edit_account = $items['edit-account'];
				unset( $items['edit-account'] );
			}

			if ( isset( $items['customer-logout'] ) ) {
				$logout = $items['customer-logout'];
				unset( $items['customer-logout'] );
			}

			// Insert our custom endpoint.
			$items[ self::$endpoint ] = __( 'Coupons', 'woocommerce-smart-coupons' );

			// Insert back the items.
			if ( ! empty( $edit_address ) ) {
				$items['edit-address'] = $edit_address;
			}
			if ( ! empty( $payment_methods ) ) {
				$items['payment-methods'] = $payment_methods;
			}
			if ( ! empty( $edit_account ) ) {
				$items['edit-account'] = $edit_account;
			}
			if ( ! empty( $logout ) ) {
				$items['customer-logout'] = $logout;
			}

			return $items;
		}

		/**
		 * Get coupon HTML
		 *
		 * @param  array $coupon_data the coupon data.
		 * @return string Coupon's HTML
		 */
		public function get_coupon_html( $coupon_data = array() ) {

			ob_start();

			$design                  = ( ! empty( $coupon_data['design'] ) ) ? $coupon_data['design'] : null;
			$coupon                  = ( ! empty( $coupon_data['coupon_object'] ) ) ? $coupon_data['coupon_object'] : null;
			$is_free_shipping        = ( ! empty( $coupon_data['is_free_shipping'] ) ) ? $coupon_data['is_free_shipping'] : null;
			$show_coupon_description = ( ! empty( $coupon_data['show_coupon_description'] ) ) ? $coupon_data['show_coupon_description'] : null;
			$coupon_description      = ( ! empty( $coupon_data['coupon_description'] ) ) ? $coupon_data['coupon_description'] : null;

			$coupon_amount = $this->get_amount( $coupon, true );

			$coupon_type = ( ! empty( $coupon_data['coupon_type'] ) ) ? $coupon_data['coupon_type'] : '';

			if ( 'yes' === $is_free_shipping ) {
				if ( ! empty( $coupon_type ) ) {
					$coupon_type .= __( ' & ', 'woocommerce-smart-coupons' );
				}
				$coupon_type .= __( 'Free Shipping', 'woocommerce-smart-coupons' );
			}

			$coupon_description = ( 'yes' === $show_coupon_description ) ? $coupon_description : '';

			$is_percent = $this->is_percent_coupon( array( 'coupon_object' => $coupon ) );

			$args = array(
				'coupon_object'      => $coupon,
				'coupon_amount'      => $coupon_amount,
				'amount_symbol'      => ( true === $is_percent ) ? '%' : get_woocommerce_currency_symbol(),
				'discount_type'      => wp_strip_all_tags( $coupon_type ),
				'coupon_description' => ( ! empty( $coupon_description ) ) ? $coupon_description : wp_strip_all_tags( $this->generate_coupon_description( array( 'coupon_object' => $coupon ) ) ),
				'coupon_code'        => $coupon_data['coupon_code'],
				'coupon_expiry'      => ( ! empty( $coupon_data['expiry_date'] ) ) ? $coupon_data['expiry_date'] : __( 'Never expires', 'woocommerce-smart-coupons' ),
				'thumbnail_src'      => $this->get_coupon_design_thumbnail_src(
					array(
						'design'        => $design,
						'coupon_object' => $coupon,
					)
				),
				'classes'            => 'apply_coupons_credits',
				'template_id'        => $design,
				'is_percent'         => $is_percent,
			);

			wc_get_template( 'coupon-design/' . $design . '.php', $args, '', plugin_dir_path( WC_SC_PLUGIN_FILE ) . 'templates/' );

			$html = ob_get_clean();

			return $html;

		}

		/**
		 * Endpoint HTML content.
		 * To show available coupons on My Account page
		 */
		public function sc_endpoint_content() {
			global $store_credit_label, $woocommerce_smart_coupon;

			$max_coupon_to_show = absint( get_option( 'wc_sc_setting_max_coupon_to_show', 5 ) );
			$max_coupon_to_show = get_option( 'wc_sc_setting_max_coupon_to_show_on_myaccount', ( $max_coupon_to_show * 10 ) );
			$max_coupon_to_show = apply_filters( 'wc_sc_max_coupon_to_show_on_myaccount', $max_coupon_to_show, array( 'source' => $this ) );
			$max_coupon_to_show = absint( $max_coupon_to_show );

			$coupons = array();
			if ( $max_coupon_to_show > 0 ) {
				$coupons = ( ! in_array( $this->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) ? $this->sc_get_available_coupons_list_old( array() ) : $this->sc_get_available_coupons_list( array() );
			}

			if ( empty( $coupons ) ) {
				?>
				<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
					<?php echo esc_html__( 'Sorry, No coupons available for you.', 'woocommerce-smart-coupons' ); ?>
				</div>
				<?php
				return false;
			}

			if ( ! wp_style_is( 'smart-coupon' ) ) {
				wp_enqueue_style( 'smart-coupon' );
			}

			$coupons_applied = ( is_object( WC()->cart ) && is_callable( array( WC()->cart, 'get_applied_coupons' ) ) ) ? WC()->cart->get_applied_coupons() : array();

			$available_coupons_heading = get_option( 'smart_coupon_myaccount_page_text' );

			/* translators: %s: plural name for store credit */
			$available_coupons_heading = ( ! empty( $available_coupons_heading ) ) ? $available_coupons_heading : ( ! empty( $store_credit_label['plural'] ) ? sprintf( __( 'Available Coupons & %s', 'woocommerce-smart-coupons' ), ucwords( $store_credit_label['plural'] ) ) : __( 'Available Coupons & Store Credits', 'woocommerce-smart-coupons' ) );

			$design                  = get_option( 'wc_sc_setting_coupon_design', 'basic' );
			$background_color        = get_option( 'wc_sc_setting_coupon_background_color', '#39cccc' );
			$foreground_color        = get_option( 'wc_sc_setting_coupon_foreground_color', '#30050b' );
			$third_color             = get_option( 'wc_sc_setting_coupon_third_color', '#39cccc' );
			$show_coupon_description = get_option( 'smart_coupons_show_coupon_description', 'no' );

			$valid_designs = $this->get_valid_coupon_designs();

			if ( ! in_array( $design, $valid_designs, true ) ) {
				$design = 'basic';
			}

			?>
			<style type="text/css"><?php echo esc_html( wp_strip_all_tags( $this->get_coupon_styles( $design ), true ) ); // phpcs:ignore ?></style>
			<?php
			if ( 'custom-design' !== $design ) {
				if ( ! wp_style_is( 'smart-coupon-designs' ) ) {
					wp_enqueue_style( 'smart-coupon-designs' );
				}
				?>
					<style type="text/css">
						:root {
							--sc-color1: <?php echo esc_html( $background_color ); ?>;
							--sc-color2: <?php echo esc_html( $foreground_color ); ?>;
							--sc-color3: <?php echo esc_html( $third_color ); ?>;
						}
					</style>
					<?php
			}
			?>
			<style type="text/css">
				.wc_sc_coupon_actions_wrapper {
					float: right;
				}
			</style>
			<h2><?php echo esc_html__( stripslashes( $available_coupons_heading ), 'woocommerce-smart-coupons' ); // phpcs:ignore ?></h2>
			<p><?php echo esc_html__( 'List of coupons which are valid & available for use. Click on the coupon to use it. The coupon discount will be visible only when at least one product is present in the cart.', 'woocommerce-smart-coupons' ); ?></p>

			<div class="woocommerce-Message woocommerce-Message--info woocommerce-info" style="display:none;">
				<?php echo esc_html__( 'Sorry, No coupons available for you.', 'woocommerce-smart-coupons' ); ?>
			</div>

			<?php

			$coupon_block_data = array(
				'smart_coupons'   => array(
					'html' => array(),
				),
				'valid_coupons'   => array(
					'html' => array(),
				),
				'invalid_coupons' => array(
					'html' => array(),
				),
			);

			$total_store_credit = 0;
			$coupons_to_print   = array();

			foreach ( $coupons as $code ) {

				$coupon_id = ( isset( $code->id ) && ! empty( $code->id ) ) ? absint( $code->id ) : 0;

				if ( empty( $coupon_id ) ) {
					continue;
				}

				if ( $max_coupon_to_show <= 0 ) {
					break;
				}

				$coupon = new WC_Coupon( $coupon_id );

				if ( ! $coupon instanceof WC_Coupon || ! $this->is_callable( $coupon, 'get_code' ) ) {
					continue;
				}

				if ( in_array( strtolower( $coupon->get_code() ), array_map( 'strtolower', $coupons_applied ), true ) ) {
					continue;
				}

				if ( $this->is_wc_gte_30() ) {
					$is_free_shipping = ( $coupon->get_free_shipping() ) ? 'yes' : 'no';
					$discount_type    = $coupon->get_discount_type();
					$expiry_date      = $coupon->get_date_expires();
					$coupon_code      = $coupon->get_code();
				} else {
					$is_free_shipping = ( ! empty( $coupon->free_shipping ) ) ? $coupon->free_shipping : '';
					$discount_type    = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
					$expiry_date      = ( ! empty( $coupon->expiry_date ) ) ? $coupon->expiry_date : '';
					$coupon_code      = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
				}

				$coupon_amount = $this->get_amount( $coupon, true );

				if ( $this->is_wc_gte_30() && $expiry_date instanceof WC_DateTime ) {
					$expiry_date = ( is_callable( array( $expiry_date, 'getTimestamp' ) ) ) ? $expiry_date->getTimestamp() : null;
				} elseif ( ! is_int( $expiry_date ) ) {
					$expiry_date = $this->strtotime( $expiry_date );
				}

				if ( ! empty( $expiry_date ) && is_int( $expiry_date ) ) {
					$expiry_time = ( $this->is_callable( $coupon, 'get_meta' ) ) ? (int) $coupon->get_meta( 'wc_sc_expiry_time' ) : (int) get_post_meta( $coupon_id, 'wc_sc_expiry_time', true );
					if ( ! empty( $expiry_time ) ) {
						$expiry_date += $expiry_time; // Adding expiry time to expiry date.
					}
				}

				if ( empty( $discount_type ) ) {
					continue;
				}

				$coupon_post = get_post( $coupon_id );

				$coupon_data = $this->get_coupon_meta_data( $coupon );

				$block_data                            = array();
				$block_data['coupon_code']             = $coupon_code;
				$block_data['coupon_amount']           = $coupon_data['coupon_amount'];
				$block_data['coupon_type']             = $coupon_data['coupon_type'];
				$block_data['is_free_shipping']        = $is_free_shipping;
				$block_data['coupon_object']           = $coupon;
				$block_data['design']                  = $design;
				$block_data['show_coupon_description'] = $show_coupon_description;

				if ( ! empty( $coupon_post->post_excerpt ) && 'yes' === $show_coupon_description ) {
					$block_data['coupon_description'] = $coupon_post->post_excerpt;
				}

				if ( ! empty( $expiry_date ) ) {
					$block_data['expiry_date'] = $this->get_expiration_format( $expiry_date );
				} else {
					$block_data['expiry_date'] = '';
				}

				$show_as_valid = apply_filters( 'wc_sc_show_as_valid', $this->is_valid( $coupon ), array( 'coupon_obj' => $coupon ) );

				if ( true === $show_as_valid ) {
					$coupons_to_print[] = $block_data['coupon_code'];
					$html               = $this->get_coupon_html( $block_data );
					if ( 'smart_coupon' === $discount_type ) {
						$total_store_credit                          += $coupon_amount;
						$coupon_block_data['smart_coupons']['html'][] = $html;
					} else {
						$coupon_block_data['valid_coupons']['html'][] = $html;
					}
				} else {
					$block_data['is_invalid'] = 'yes';
					$html                     = $this->get_coupon_html( $block_data );
					$coupon_block_data['invalid_coupons']['html'][] = $html;
				}

				$max_coupon_to_show--;

			}

			$coupon_block_data['smart_coupons']['total'] = $total_store_credit;

			$is_print = get_option( 'smart_coupons_is_print_coupon', 'yes' );
			$is_print = apply_filters( 'wc_sc_myaccount_show_print_button', wc_string_to_bool( $is_print ), array( 'source' => $woocommerce_smart_coupon ) );

			if ( true === $is_print && ! empty( $coupons_to_print ) ) {
				$print_url = add_query_arg(
					array(
						'print-coupons' => 'yes',
						'source'        => 'wc-smart-coupons',
						'coupon-codes'  => implode(
							',',
							$coupons_to_print
						),
					)
				);
				?>
				<span class="wc_sc_coupon_actions_wrapper">
					<a target="_blank" href="<?php echo esc_url( $print_url ); ?>" class="button"><?php echo esc_html( _n( 'Print coupon', 'Print coupons', count( $coupons_to_print ), 'woocommerce-smart-coupons' ) ); ?></a>
				</span>
				<?php
			}
			?>
			<?php

			$allowed_html = wp_kses_allowed_html( 'post' );

			$additional_allowed_html = array(
				'svg'    => array(
					'fill'                => true,
					'viewbox'             => true,
					'stroke'              => true,
					'class'               => true,
					'style'               => true,
					'xmlns'               => true,
					'preserveaspectratio' => true,
				),
				'path'   => array(
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'stroke-width'    => true,
					'd'               => true,
					'fill'            => true,
					'class'           => true,
				),
				'g'      => array(
					'stroke-miterlimit' => true,
					'stroke-linecap'    => true,
					'stroke-linejoin'   => true,
					'stroke-width'      => true,
				),
				'circle' => array(
					'cx' => true,
					'cy' => true,
					'r'  => true,
				),
				'defs'   => array(),
				'style'  => array(),
			);

			$additional_allowed_html = apply_filters( 'wc_sc_kses_allowed_html_for_coupon', $additional_allowed_html, array( 'source' => $this ) );

			if ( ! empty( $additional_allowed_html ) ) {
				foreach ( $additional_allowed_html as $tag => $attributes ) {
					if ( ! empty( $attributes ) && array_key_exists( $tag, $allowed_html ) ) {
						$allowed_html[ $tag ] = array_merge( $allowed_html[ $tag ], $attributes );
					} else {
						$allowed_html[ $tag ] = $attributes;
					}
				}
			}

			$smart_coupons_block = '';

			if ( ! empty( $coupon_block_data['smart_coupons']['html'] ) ) {
				$smart_coupons_block = implode( '', $coupon_block_data['smart_coupons']['html'] );
			}

			$smart_coupons_block = trim( $smart_coupons_block );

			if ( ! empty( $smart_coupons_block ) ) {
				?>
					<div id='sc_coupons_list'>
						<h4><?php echo ! empty( $store_credit_label['plural'] ) ? esc_html( ucwords( $store_credit_label['plural'] ) ) : esc_html__( 'Store Credits', 'woocommerce-smart-coupons' ); ?></h4>
						<div id="sc-cc">
							<div id="all_coupon_container" class="sc-coupons-list">
							<?php echo wp_kses( $smart_coupons_block, $allowed_html ); // phpcs:ignore ?>
							</div>
						</div>
					<?php
					if ( ! empty( $coupon_block_data['smart_coupons']['total'] ) && 0 !== $coupon_block_data['smart_coupons']['total'] ) {
						?>
						<div class="wc_sc_total_available_store_credit"><?php echo esc_html__( 'Total Credit Amount', 'woocommerce-smart-coupons' ) . ': ' . wc_price( $coupon_block_data['smart_coupons']['total'] ); // phpcs:ignore ?></div>
						<?php
					}
					?>
					<br>
				</div>
			<?php } ?>
			<?php
			$valid_coupons_block = '';

			if ( ! empty( $coupon_block_data['valid_coupons']['html'] ) ) {
				$valid_coupons_block = implode( '', $coupon_block_data['valid_coupons']['html'] );
			}

			$valid_coupons_block = trim( $valid_coupons_block );

			if ( ! empty( $valid_coupons_block ) ) {
				?>
					<div id="coupons_list">
						<h4><?php echo esc_html__( 'Discount Coupons', 'woocommerce-smart-coupons' ); ?></h4>
						<div id="sc-cc">
							<div id="all_coupon_container" class="sc-coupons-list">
							<?php echo wp_kses( $valid_coupons_block, $allowed_html ); // phpcs:ignore ?>
							</div>
						</div>
					</div>
					<br>
			<?php } ?>
			<?php
			// to show user specific coupons on My Account.
			$this->generated_coupon_details_before_my_account();

			$is_show_invalid_coupons = get_option( 'smart_coupons_show_invalid_coupons_on_myaccount', 'no' );
			if ( 'yes' === $is_show_invalid_coupons ) {
				$invalid_coupons_block = '';

				if ( ! empty( $coupon_block_data['invalid_coupons']['html'] ) ) {
					$invalid_coupons_block = implode( '', $coupon_block_data['invalid_coupons']['html'] );
				}

				$invalid_coupons_block = trim( $invalid_coupons_block );

				if ( ! empty( $invalid_coupons_block ) ) {
					?>
					<br>
					<div id='invalid_coupons_list'>
						<h2>
						<?php
						echo wp_kses(
							apply_filters(
								'wc_sc_invalid_coupons_list_title',
								__( 'Invalid / Used Coupons', 'woocommerce-smart-coupons' ),
								array(
									'source'          => $this,
									'all_coupon_data' => $coupon_block_data,
								)
							),
							$allowed_html
						);
						?>
							</h2>
						<p>
						<?php
						echo wp_kses(
							apply_filters(
								'wc_sc_invalid_coupons_list_description',
								__( 'List of coupons which can not be used. The reason can be based on its usage restrictions, usage limits, expiry date.', 'woocommerce-smart-coupons' ),
								array(
									'source'          => $this,
									'all_coupon_data' => $coupon_block_data,
								)
							),
							$allowed_html
						);
						?>
							</p>
						<div id="sc-cc">
							<div id="all_coupon_container" class="sc-coupons-list">
								<?php echo wp_kses( $invalid_coupons_block, $allowed_html ); // phpcs:ignore ?>
							</div>
						</div>
					</div>
					<?php
				}
			}

			if ( did_action( 'wc_smart_coupons_frontend_styles_and_scripts' ) <= 0 && ! wp_doing_ajax() ) {
				$this->frontend_styles_and_scripts( array( 'page' => 'checkout' ) );
			}

			$js = "var total_store_credit = '" . $total_store_credit . "';
					if ( total_store_credit == 0 ) {
						jQuery('#sc_coupons_list').hide();
					}

					if( jQuery('div#all_coupon_container').children().length == 0 ) {
						jQuery('#coupons_list').hide();
					}

					if( jQuery('div.woocommerce-MyAccount-content').children().length == 0 ) {
						jQuery('.woocommerce-MyAccount-content').append(jQuery('.woocommerce-Message.woocommerce-Message--info.woocommerce-info'));
						jQuery('.woocommerce-Message.woocommerce-Message--info.woocommerce-info').show();
					}

					/* to show scroll bar for core coupons */
					var coupons_list = jQuery('#coupons_list');
					var coupons_list_height = coupons_list.height();

					if ( coupons_list_height > 400 ) {
						coupons_list.css('height', '400px');
						coupons_list.css('overflow-y', 'scroll');
					} else {
						coupons_list.css('height', '');
						coupons_list.css('overflow-y', '');
					}
			";

			wc_enqueue_js( $js );

		}

		/**
		 * Function to refresh session on Cart Update.
		 */
		public function available_coupons_on_cart_update() {
			WC()->session->__unset( $this->session_key_available_coupons );
		}

		/**
		 * Function to get available coupons list
		 *
		 * @param array $coupons The coupons.
		 * @param array $args Additional arguments.
		 * @return array Modified coupons.
		 */
		public function sc_get_available_coupons_list( $coupons = array(), $args = array() ) {

			if ( ! class_exists( 'WC_SC_Coupon_Data_Store' ) ) {
				if ( file_exists( trailingslashit( WP_PLUGIN_DIR . '/' . WC_SC_PLUGIN_DIRNAME ) . 'includes/class-wc-sc-coupon-data-store.php' ) ) {
					include_once trailingslashit( WP_PLUGIN_DIR . '/' . WC_SC_PLUGIN_DIRNAME ) . 'includes/class-wc-sc-coupon-data-store.php';
				}
			}
			global $wpdb;

			$cart                      = is_object( WC() ) && isset( WC()->cart ) ? WC()->cart : null;
			$wc_session                = ! empty( WC()->session ) ? WC()->session : null;
			$display_available_coupons = ( ! empty( $wc_session ) && is_a( $wc_session, 'WC_Session' ) && is_callable( array( $wc_session, 'get' ) ) ) ? $wc_session->get( $this->session_key_available_coupons ) : array();
			$set_in_session            = apply_filters(
				$this->session_key_available_coupons . '_session',
				true,
				array(
					'source' => $this,
					'args'   => $args,
				)
			);
			if ( empty( $display_available_coupons ) || ! $set_in_session ) {

				$max_coupon_to_show         = absint( apply_filters( 'wc_sc_max_coupon_to_show', get_option( 'wc_sc_setting_max_coupon_to_show', 5 ), array( 'source' => $this ) ) );
				$limit                      = $max_coupon_to_show * apply_filters( 'wc_sc_max_display_coupons_multiplier', get_option( 'wc_sc_max_display_coupons_multiplier', 50 ) );
				$query_customer_email       = '';
				$query_user_role            = '';
				$query_exclude_user_role    = '';
				$exclude_applied_coupon_ids = array();
				$cart_product_ids           = array();
				$cart_category_ids          = array();
				$cart_attribute_ids         = array();

				$subtotal                  = ( wc_prices_include_tax() && $cart->display_prices_including_tax() ) ? $cart->get_subtotal() + $cart->get_subtotal_tax() : $cart->get_subtotal();
				$selected_payment_method   = WC()->session->get( 'chosen_payment_method' );
				$selected_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
				$cart_items                = $cart->get_cart();

				// Prepare product IDs for matching.

				if ( ! empty( $cart_items ) ) {
					if ( ! class_exists( 'WC_SC_Coupons_By_Product_Attribute' ) ) {
						include_once 'class-wc-sc-coupons-by-product-attribute.php';
					}
					$wc_sc_product_attribute = WC_SC_Coupons_By_Product_Attribute::get_instance();

					foreach ( $cart_items as $item ) {
						if ( ! isset( $item['data'] ) || empty( $item['data'] ) ) {
							continue;
						}
						$product = $item['data'];
						if ( ! $product instanceof WC_Product || ! $this->is_callable( $product, 'get_id' ) ) {
							continue;
						}
						$cart_product_ids[] = $product->get_id();
						if ( ! empty( $product->get_parent_id() ) ) {
							$cart_product_ids[] = $product->get_parent_id();
							$cart_category_ids  = array_merge( $cart_category_ids, wc_get_product_cat_ids( $product->get_parent_id() ) );
						}
						$cart_category_ids  = array_merge( $cart_category_ids, wc_get_product_cat_ids( $product->get_id() ) );
						$cart_attribute_ids = array_merge( $cart_attribute_ids, $wc_sc_product_attribute->get_product_attributes( $product ) );
					}
				}

				$query_available_coupons = $wpdb->prepare(
					"SELECT  id
							
						FROM {$wpdb->prefix}wc_smart_coupons 
							WHERE wc_sc_auto_apply_coupon <> %d
							AND ( date_expires IS NULL OR  date_expires >= DATE_ADD(NOW(), INTERVAL 5 MINUTE))",
					1
				);

				// Condition for minimum & maximum spend.
				$query_available_coupons .= " AND ( minimum_amount = '' OR  minimum_amount IS NULL OR  minimum_amount <= " . $subtotal . ') ';
				$query_available_coupons .= " AND ( maximum_amount = '' OR  maximum_amount IS NULL OR  maximum_amount >= " . $subtotal . ') ';

				// Condition for selected payment method.
				if ( ! empty( $selected_payment_method ) && is_string( $selected_payment_method ) ) {
					$query_available_coupons .= $wpdb->prepare( ' AND ( wc_sc_payment_method_ids = %s OR wc_sc_payment_method_ids IS NULL OR wc_sc_payment_method_ids LIKE %s )', 'a:0:{}', '%' . $wpdb->esc_like( '"' . $selected_payment_method . '"' ) . '%' );
				}

				// Condition for selected shipping method.
				if ( ! empty( $selected_shipping_methods ) && is_array( $selected_shipping_methods ) ) {
					$sub_query = '';
					foreach ( $selected_shipping_methods as $shipping_method ) {
						$shipping_method = explode( ':', $shipping_method )[0];
						$sub_query      .= $wpdb->prepare( ' OR wc_sc_shipping_method_ids LIKE %s', '%' . $wpdb->esc_like( '"' . $shipping_method . '"' ) . '%' );

					}
					$query_available_coupons .= $wpdb->prepare( " AND ( wc_sc_shipping_method_ids = %s OR wc_sc_shipping_method_ids IS NULL $sub_query ) ", 'a:0:{}' ); // phpcs:ignore
				}

				// Filter by product ids.
				if ( ! empty( $cart_product_ids ) && is_array( $cart_product_ids ) ) {
					$cart_product_ids  = array_unique( $cart_product_ids );
					$sub_query         = '';
					$exclude_sub_query = '';
					foreach ( $cart_product_ids as $id ) {
						$sub_query         .= $wpdb->prepare( ' OR product_ids LIKE %s', '%' . $wpdb->esc_like( $id ) . '%' );
						$exclude_sub_query .= $wpdb->prepare( ' OR exclude_product_ids NOT LIKE %s', '%' . $wpdb->esc_like( $id ) . '%' );
					}
					$query_available_coupons .= " AND (product_ids = '' OR product_ids IS NULL $sub_query )";

					// Filter by exclude product ids.
					$query_available_coupons .= " AND (exclude_product_ids = '' OR exclude_product_ids IS NULL $exclude_sub_query )";
				}

				// Filter by cart_attribute_ids.
				if ( ! empty( $cart_attribute_ids ) && is_array( $cart_attribute_ids ) ) {
					$cart_attribute_ids = array_unique( $cart_attribute_ids );
					$sub_query          = '';
					$exclude_sub_query  = '';
					foreach ( $cart_attribute_ids as $id ) {
						$sub_query         .= $wpdb->prepare( ' OR wc_sc_product_attribute_ids LIKE %s', '%' . $wpdb->esc_like( $id ) . '%' );
						$exclude_sub_query .= $wpdb->prepare( ' OR wc_sc_exclude_product_attribute_ids NOT LIKE %s', '%' . $wpdb->esc_like( $id ) . '%' );
					}
					$query_available_coupons .= " AND (wc_sc_product_attribute_ids IN ('', 'a:0:{}') OR wc_sc_product_attribute_ids IS NULL $sub_query )";

					// Filter by exclude category ids.
					$query_available_coupons .= " AND (wc_sc_exclude_product_attribute_ids IN ('', 'a:0:{}') OR wc_sc_exclude_product_attribute_ids IS NULL $exclude_sub_query )";
				}

				// Filter by category ids.
				if ( ! empty( $cart_category_ids ) && is_array( $cart_category_ids ) ) {
					$cart_category_ids = array_unique( $cart_category_ids );
					$sub_query         = '';
					$exclude_sub_query = '';
					foreach ( $cart_category_ids as $cat_id ) {
						$sub_query         .= $wpdb->prepare( ' OR product_categories LIKE %s', '%' . $wpdb->esc_like( 'i:' . $cat_id ) . '%' );
						$exclude_sub_query .= $wpdb->prepare( ' OR exclude_product_categories NOT LIKE %s', '%' . $wpdb->esc_like( 'i:' . $cat_id ) . '%' );
					}
					$query_available_coupons .= " AND ( product_categories IN ('', 'a:0:{}') OR product_categories IS NULL $sub_query )";

					// Filter by exclude category ids.
					$query_available_coupons .= " AND ( exclude_product_categories IN ('', 'a:0:{}') OR exclude_product_categories IS NULL $exclude_sub_query )";
				}

				if ( is_user_logged_in() ) {

					global $current_user;

					if ( ! empty( $current_user->user_email ) && ! empty( $current_user->ID ) ) {
						$user_roles = isset( $current_user->roles ) ? $current_user->roles : array();

						$billing_email = function_exists( 'WC' ) && isset( WC()->customer ) && WC()->customer instanceof WC_Customer && is_callable( array( WC()->customer, 'get_billing_email' ) ) ? WC()->customer->get_billing_email() : get_user_meta( $current_user->ID, 'billing_email', true );

						$query_customer_email .= $wpdb->prepare( '( sc_is_visible_storewide = %d)', 1 );

						if ( $billing_email !== $current_user->user_email ) {
							$query_customer_email .= $wpdb->prepare(
								' OR ( customer_email LIKE %s OR  customer_email LIKE %s)',
								'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%',
								'%' . $wpdb->esc_like( '"' . $billing_email . '"' ) . '%'
							);
							// add exclude customer email.
							$query_customer_email .= $wpdb->prepare(
								"AND ( wc_sc_excluded_customer_email = '' OR wc_sc_excluded_customer_email IS NULL OR wc_sc_excluded_customer_email NOT LIKE %s AND  wc_sc_excluded_customer_email NOT LIKE %s)",
								'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%',
								'%' . $wpdb->esc_like( '"' . $billing_email . '"' ) . '%'
							);
						} else {
							$query_customer_email .= $wpdb->prepare(
								' OR ( customer_email LIKE %s)',
								'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%'
							);
							// add exclude customer email.
							$query_customer_email .= $wpdb->prepare(
								"AND ( wc_sc_excluded_customer_email = '' OR wc_sc_excluded_customer_email IS NULL OR wc_sc_excluded_customer_email NOT LIKE %s )",
								'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%'
							);
						}

						if ( ! is_scalar( $user_roles ) && ! empty( $user_roles ) ) {
							$query_user_roles         = array();
							$query_exclude_user_roles = array();
							foreach ( $user_roles as $role ) {
								$query_user_roles[]         = $wpdb->prepare( ' wc_sc_user_role_ids LIKE %s', '%' . $wpdb->esc_like( '"' . $role . '"' ) . '%' );
								$query_exclude_user_roles[] = $wpdb->prepare( ' wc_sc_exclude_user_role_ids NOT LIKE %s', '%' . $wpdb->esc_like( '"' . $role . '"' ) . '%' );
							}
							if ( ! empty( $query_user_roles ) ) {
								$query_user_role .= ' (' . implode( ' OR ', $query_user_roles ) . ')';
							}

							if ( ! empty( $query_exclude_user_roles ) ) {
								$query_exclude_user_role .= ' (' . implode( ' AND ', $query_exclude_user_roles ) . ')';
							}
						}
					}
				} else {
					$query_available_coupons .= $wpdb->prepare( ' AND  discount_type != %s', 'smart_coupon' );
					$query_customer_email     = $wpdb->prepare( '(( customer_email = %s OR  customer_email IS NULL) AND  sc_is_visible_storewide = %d)', 'a:0:{}', 1 );
					$query_user_role          = $wpdb->prepare( '(( wc_sc_user_role_ids = %s OR  wc_sc_user_role_ids IS NULL) AND  sc_is_visible_storewide = %d)', 'a:0:{}', 1 );
				}

				if ( ! empty( trim( $query_customer_email ) ) || ! empty( trim( $query_user_role ) ) || ! empty( trim( $query_exclude_user_role ) ) ) {
					$query_available_coupons .= ' AND (';
					if ( ! empty( trim( $query_customer_email ) ) ) {
						$query_available_coupons .= $query_customer_email;
					}
					if ( ! empty( trim( $query_user_role ) ) ) {
						$query_available_coupons .= ' OR ' . $query_user_role;
					}
					if ( ! empty( trim( $query_exclude_user_role ) ) ) {
						$query_available_coupons .= ' AND ' . $query_exclude_user_role;
					}
					$query_available_coupons .= ')';
				}

				$query_available_coupons  .= " ORDER BY  id DESC LIMIT $limit";
				$display_available_coupons = $wpdb->get_results( // phpcs:ignore
					$query_available_coupons // phpcs:ignore
				);

				if ( ! empty( $wc_session ) && is_a( $wc_session, 'WC_Session' ) && is_callable( array( $wc_session, 'set' ) ) ) {
					$wc_session->set( $this->session_key_available_coupons, $display_available_coupons );
				}
			}
			$coupons = array_merge( $coupons, $display_available_coupons );
			return apply_filters(
				$this->session_key_available_coupons,
				$coupons,
				array(
					'source' => $this,
					'args'   => $args,
				)
			);

		}

		/**
		 * Function to get available coupons list
		 *
		 * @param array $coupons The coupons.
		 * @param array $args Additional arguments.
		 * @return array Modified coupons.
		 */
		public function sc_get_available_coupons_list_old( $coupons = array(), $args = array() ) {

			global $wpdb;

			$global_coupons = array();

			$wpdb->query( $wpdb->prepare( 'SET SESSION group_concat_max_len=%d', 999999 ) ); // phpcs:ignore

			$global_coupons = wp_cache_get( 'wc_sc_global_coupons', 'woocommerce_smart_coupons' );

			if ( false === $global_coupons ) {
				$global_coupons_ids = get_option( 'sc_display_global_coupons' );

				if ( ! empty( $global_coupons_ids ) ) {
					$global_coupons = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							"SELECT *
								FROM {$wpdb->prefix}posts
								WHERE FIND_IN_SET (ID, (SELECT GROUP_CONCAT(option_value SEPARATOR ',') FROM {$wpdb->prefix}options WHERE option_name = %s)) > 0
									AND post_type = %s
								GROUP BY ID
								ORDER BY post_date DESC",
							'sc_display_global_coupons',
							'shop_coupon'
						)
					);
					wp_cache_set( 'wc_sc_global_coupons', $global_coupons, 'woocommerce_smart_coupons' );
					$this->maybe_add_cache_key( 'wc_sc_global_coupons' );
				}
			}

			if ( is_scalar( $global_coupons ) ) {
				$global_coupons = array();
			}

			$global_coupons = apply_filters( 'wc_smart_coupons_global_coupons', $global_coupons );

			$args['global_coupons'] = $global_coupons;

			if ( is_user_logged_in() ) {

				global $current_user;

				if ( ! empty( $current_user->user_email ) && ! empty( $current_user->ID ) ) {

					$count_option_current_user = wp_cache_get( 'wc_sc_current_users_option_name_' . $current_user->ID, 'woocommerce_smart_coupons' );

					if ( false === $count_option_current_user ) {
						$count_option_current_user = $wpdb->get_col( // phpcs:ignore
							$wpdb->prepare(
								"SELECT option_name
									FROM {$wpdb->prefix}options
									WHERE option_name LIKE %s
									ORDER BY option_id DESC",
								$wpdb->esc_like( 'sc_display_custom_credit_' . $current_user->ID . '_' ) . '%'
							)
						);
						wp_cache_set( 'wc_sc_current_users_option_name_' . $current_user->ID, $count_option_current_user, 'woocommerce_smart_coupons' );
						$this->maybe_add_cache_key( 'wc_sc_current_users_option_name_' . $current_user->ID );
					}

					if ( count( $count_option_current_user ) > 0 ) {
						$count_option_current_user = substr( strrchr( $count_option_current_user[0], '_' ), 1 );
						$count_option_current_user = ( ! empty( $count_option_current_user ) ) ? $count_option_current_user + 2 : 1;
					} else {
						$count_option_current_user = 1;
					}

					$coupon_ids = array();
					$coupon_ids = apply_filters( 'wc_sc_available_coupon_ids', $coupon_ids, $args );

					$option_nm = 'sc_display_custom_credit_' . $current_user->ID . '_' . $count_option_current_user;
					$wpdb->query( $wpdb->prepare( 'SET SESSION group_concat_max_len=%d', 999999 ) ); // phpcs:ignore
					$wpdb->delete( $wpdb->prefix . 'options', array( 'option_name' => $option_nm ) ); // WPCS: db call ok.
					$query_string = "REPLACE INTO {$wpdb->prefix}options (option_name, option_value, autoload)
										SELECT %s,
											IFNULL(GROUP_CONCAT(DISTINCT p.id SEPARATOR ','), ''),
											%s
										FROM {$wpdb->prefix}posts AS p
											JOIN {$wpdb->prefix}postmeta AS pm
												ON(pm.post_id = p.ID
													AND p.post_type = %s
													AND p.post_status = %s
													AND pm.meta_key = %s
													AND pm.meta_value LIKE %s";

					$query_args = array(
						$option_nm,
						'no',
						'shop_coupon',
						'publish',
						'customer_email',
						'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%',
					);

					if ( ! empty( $coupon_ids ) ) {
						$how_many      = count( $coupon_ids );
						$placeholders  = array_fill( 0, $how_many, '%d' );
						$query_string .= ' AND p.ID IN (' . implode( ',', $placeholders ) . ')';
						$query_args    = array_merge( $query_args, $coupon_ids );
					}

					$query_string .= ')';

					$wpdb->query( // phpcs:ignore
						$wpdb->prepare( $query_string, $query_args ) // phpcs:ignore
					);

					$customer_coupon_ids = get_option( $option_nm );

					// Only execute rest of the queries if coupons found.
					if ( ! empty( $customer_coupon_ids ) ) {
						$wpdb->query( // phpcs:ignore
							$wpdb->prepare(
								"UPDATE {$wpdb->prefix}options
									SET option_value = (SELECT IFNULL(GROUP_CONCAT(post_id SEPARATOR ','), '')
														FROM {$wpdb->prefix}postmeta
														WHERE meta_key = %s
															AND CAST(meta_value AS SIGNED) >= '0'
															AND FIND_IN_SET(post_id, (SELECT option_value FROM (SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s) as temp )) > 0 )
									WHERE option_name = %s",
								'coupon_amount',
								$option_nm,
								$option_nm
							)
						);
					}
					$coupons = wp_cache_get( 'wc_sc_all_coupon_id_for_user_' . $current_user->ID, 'woocommerce_smart_coupons' );

					if ( false === $coupons ) {
						$coupons = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								"SELECT *
									FROM {$wpdb->prefix}posts
									WHERE FIND_IN_SET (ID, (SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s)) > 0
										AND post_status = %s
										AND post_type = %s
									GROUP BY ID
									ORDER BY post_date DESC",
								$option_nm,
								'publish',
								'shop_coupon'
							)
						);
						wp_cache_set( 'wc_sc_all_coupon_id_for_user_' . $current_user->ID, $coupons, 'woocommerce_smart_coupons' );
						$this->maybe_add_cache_key( 'wc_sc_all_coupon_id_for_user_' . $current_user->ID );
					}

					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							"DELETE FROM {$wpdb->prefix}options WHERE option_name = %s",
							$option_nm
						)
					);
				}
			}

			$coupons = array_merge( $coupons, $global_coupons );

			$unique_id_to_code = array_unique( array_reverse( wp_list_pluck( $coupons, 'post_title', 'ID' ), true ) );

			$unique_ids = array_map( 'absint', array_keys( $unique_id_to_code ) );

			foreach ( $coupons as $index => $coupon ) {
				if ( empty( $coupon->ID ) || ! in_array( absint( $coupon->ID ), $unique_ids, true ) ) {
					unset( $coupons[ $index ] );
				}
			}

			return $coupons;

		}

		/**
		 * Function to get available coupons from options table until migration is not complete
		 *
		 * @return array coupons.
		 */
		public function sc_get_available_older_coupons() {
			$global_coupons = array();
			$coupons        = array();
			$global_coupons = wp_cache_get( 'wc_sc_global_coupons', 'woocommerce_smart_coupons' );
			if ( false === $global_coupons ) {
				$global_coupons_ids = get_option( 'sc_display_global_coupons' );
				if ( ! empty( $global_coupons_ids ) ) {
					$global_coupons_ids = array_map( 'intval', explode( ',', $global_coupons_ids ) );
					$global_coupons     = get_posts(
						array(
							'post__in'       => $global_coupons_ids,
							'post_type'      => 'shop_coupon',
							'post_status'    => 'publish',
							'posts_per_page' => -1,
						)
					);
				}
			}

			if ( is_scalar( $global_coupons ) ) {
				$global_coupons = array();
			}

			$global_coupons = apply_filters( 'wc_smart_coupons_global_coupons', $global_coupons );

			if ( is_user_logged_in() ) {
				global $current_user;
				$coupons = ( false === wp_cache_get( 'wc_sc_all_coupon_id_for_user_' . $current_user->ID, 'woocommerce_smart_coupons' ) ) ? array() : wp_cache_get( 'wc_sc_all_coupon_id_for_user_' . $current_user->ID, 'woocommerce_smart_coupons' );
			}

			$coupons = array_merge( $coupons, $global_coupons );
			if ( empty( $coupons ) ) {
				return $coupons;
			}
			$unique_id_to_code = array_unique( array_reverse( wp_list_pluck( $coupons, 'post_title', 'ID' ), true ) );

			$unique_ids = array_map( 'absint', array_keys( $unique_id_to_code ) );

			foreach ( $coupons as $index => $coupon ) {
				if ( empty( $coupon->ID ) || ! in_array( absint( $coupon->ID ), $unique_ids, true ) ) {
					unset( $coupons[ $index ] );
				}
			}

			return $coupons;
		}



		/**
		 * Include frontend styles & scripts
		 *
		 * @param array $args Arguments.
		 */
		public function frontend_styles_and_scripts( $args = array() ) {

			if ( is_account_page() ) {
				$show_myaccount_menu_icon = apply_filters(
					'wc_sc_show_myaccount_menu_icon',
					$this->sc_get_option( 'wc_sc_show_myaccount_menu_icon' ),
					array(
						'source' => $this,
						'args'   => $args,
					)
				);

				if ( 'yes' === $show_myaccount_menu_icon ) {
					if ( empty( self::$endpoint ) ) {
						self::$endpoint = self::get_endpoint();
					}
					?>
					<style type="text/css">
						.woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--<?php echo esc_html( self::$endpoint ); ?> a:before {
							font-family: "dashicons";
							content: "\f524";
						}
					</style>
					<?php
				}
			}

			$page_id                     = get_the_ID();
			$has_available_coupons_block = has_block( 'woocommerce-smart-coupons/available-coupons', absint( $page_id ) );

			if ( empty( $args['page'] ) && ! $has_available_coupons_block ) {
				return;
			}

			if ( apply_filters( 'wc_sc_apply_coupon_via_ajax', true, array( 'source' => $this ) ) ) {
				if ( $has_available_coupons_block ) { // Code to handle apply coupon via blocks.
					$js = "
						function wc_sc_apply_coupon_js() {
							let coupon = this;
							coupon.style.opacity = '0.5';
							let couponCode = coupon.getAttribute('data-coupon_code');
							couponCode = (couponCode || '').trim();
							if (!couponCode) {
								scShowStoreNotice(\"" . esc_js( _x( 'Please enter a valid coupon code.', 'Notice when coupon code is empty', 'woocommerce-smart-coupons' ) ) . "\", 'error');
								return;
							}
							scApplyCoupon(couponCode);
						}

						function scApplyCoupon(couponCode) {
							const { CART_STORE_KEY } = window?.wc?.wcBlocksData || {};
							const cartDispatch = window?.wp?.data?.dispatch?.(CART_STORE_KEY);

							if (!typeof cartDispatch?.applyCoupon === 'function') {
								console.error('WooCommerce Blocks cart store is not available.');
								return;
							}

							cartDispatch.applyCoupon(couponCode).then((result) => {
								if (result?.errors?.length) {
									const errorMessages = result.errors.map((e) => e.message).join('\\n');
									scShowStoreNotice(errorMessages, 'error');
								} else {
									scShowStoreNotice(
										'" . esc_js( _x( 'Coupon code', 'Prefix for successful coupon notice', 'woocommerce-smart-coupons' ) ) . " ' + couponCode + ' " . esc_js( _x( 'has been applied to your cart.', 'Suffix for successful coupon notice', 'woocommerce-smart-coupons' ) ) . "',
										'info'
									);
								}
							})
							.catch((error) => {
								console.error('Unexpected error applying coupon:', error);
								scShowStoreNotice(error?.message, 'error');
							});
						}

						function scShowStoreNotice(message, status = 'success') {
							let txt = document.createElement('textarea');
							txt.innerHTML = message;
							message = txt?.value;
							try {
								window?.wp?.data?.dispatch('core/notices')?.createNotice(
									status,
									message,
									{
										id: 'coupon-form',
										type: 'snackbar',
										context: 'wc/checkout',
									}
								);
							} catch (err) {
								console.warn('Notice failed to show:', err, message);
							}
						}

						window.addEventListener('load', () => {
							setTimeout(function() {
								let sendCouponFormDiv = document.getElementsByClassName('wp-block-woocommerce-smart-coupons-send-coupon-form');
								Array.from(sendCouponFormDiv).forEach(childDiv => {
									childDiv.style.display = childDiv.children.length === 0 ? 'none' : 'block';
								});
							}, 1000);

							const { CART_STORE_KEY } = window?.wc?.wcBlocksData || {};
							const { select, subscribe } = window?.wp?.data || {};

							if (!CART_STORE_KEY || !select || !subscribe) {
								console.warn('WooCommerce Blocks store or wp.data is unavailable.');
								return;
							}

							let previousCart = JSON.stringify(select(CART_STORE_KEY).getCartData());

							subscribe(() => {
								const currentCartData = select(CART_STORE_KEY).getCartData();
								if (!currentCartData) return;

								const currentCart = JSON.stringify(currentCartData);

								if (currentCart !== previousCart) {
									previousCart = currentCart;
									sc_load_coupons();
								}
							});
						});
					";
				} else {
					$url               = WC_AJAX::get_endpoint( 'apply_coupon' );
					$wp_security_nonce = wp_create_nonce( 'apply-coupon' );
					$js                = "
					jQuery(document).on('updated_wc_div updated_checkout', function() {
						sc_load_coupons();
					});

					function scroll_to_notices( scrollElement ){
						if ( scrollElement.length ) {
							jQuery( 'html, body' ).animate( {
								scrollTop: ( scrollElement.offset().top - 100 )
							}, 1000 );
						}
					}

					let applyCouponAjaxRequest = null;
					function wc_sc_apply_coupon_js() {
						let coupon_code = jQuery(this).data('coupon_code');
						if( coupon_code != '' && coupon_code != undefined ) {
							jQuery(this).css('opacity', '0.5');
							let data = {
								coupon_code: coupon_code, 
								security: '" . $wp_security_nonce . "'
							};

							// Abort the previous request if it exists
							if ( applyCouponAjaxRequest != null ) {
								applyCouponAjaxRequest.abort();
							}
										
							applyCouponAjaxRequest = jQuery.ajax( {
								type:     'POST',
								url:      '" . $url . "',
								data:     data,
								dataType: 'html',";
					if ( is_cart() ) {
						$js .= "
									success: function( response ) {
										jQuery(
											'.woocommerce-error, .woocommerce-message, .woocommerce-info, ' +
											'.is-error, .is-info, .is-success, .coupon-error-notice'
										).remove();
										$(document.body).trigger('wc_update_cart');
										jQuery(document).on('updated_wc_div', function() {
											if( response ){
												jQuery(
													'.woocommerce-error, .woocommerce-message, .woocommerce-info, ' +
													'.is-error, .is-info, .is-success, .coupon-error-notice'
												).remove();
												let target =
													jQuery( '.woocommerce-notices-wrapper:first' ) ||
													jQuery( '.wc-empty-cart-message' ).closest( '.woocommerce' ) ||
													jQuery( '.woocommerce-cart-form' );
												target.prepend( response )
											}
										});
									}";
					} elseif ( is_checkout() ) {
						$js .= "success: function( response ) {             
										if ( response ) {
											jQuery( '.woocommerce-error, .woocommerce-message' ).remove();
											jQuery( 'form.woocommerce-checkout' ).before( response );
											jQuery( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
										}                          
									},
									complete: function() {
										jQuery( '.sc-coupons-list' ).removeClass( 'processing' ).unblock();
									}";
					} else {
						$js .= "success: function( response ) {
										jQuery( '.woocommerce-error, .woocommerce-message, .woocommerce-info' ).remove();
									
										if( jQuery('body #sc_coupons_list').length ){
											jQuery( '#sc_coupons_list' ).before( response );    
										}else{
											jQuery( '#coupons_list' ).before( response );   
										}
										scroll_to_notices( jQuery( '[role=\"alert\"]' ) );
									},
									complete: function() {
										sc_load_coupons();
										jQuery( '.sc-coupons-list' ).removeClass( 'processing' ).unblock();
									}";
					}
								$js .= '
							} );
						}
					}';
				}
			} else {
				$js = "
					function wc_sc_apply_coupon_js() {
						let coupon_code = jQuery(this).data('coupon_code');

						if( coupon_code != '' && coupon_code != undefined ) {

							jQuery(this).css('opacity', '0.5');
							var url = '" . trailingslashit( home_url() ) . ( ( strpos( home_url(), '?' ) === false ) ? '?' : '&' ) . ( ( ! empty( $args['page'] ) ) ? 'sc-page=' . $args['page'] : '' ) . "&coupon-code='+coupon_code;
							jQuery(location).attr('href', url);

						}
					}
				";
			}

			$js .= "
				let currentXHR = null;
				window.sc_load_coupons = function() {
					const content = document.querySelector('div#coupons_list .sc-accordion-content');
					
					if (!content) return;

					// Show loader
					content.innerHTML = '<div class=\"woocommerce\"><div class=\"blockUI blockOverlay\" style=\"display: block !important;\"></div></div>';

					// Abort previous request if still pending
					if (currentXHR && currentXHR.readyState !== 4) {
						currentXHR.abort();
					}

					// Create new request
					currentXHR = new XMLHttpRequest();
					currentXHR.open('POST', '" . admin_url( 'admin-ajax.php' ) . "', true);
					currentXHR.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

					currentXHR.onload = function () {
						if (currentXHR.status === 200) {
							const response = currentXHR.responseText;
							if (response) {
								content.innerHTML = response;
								if (!content.querySelector('.apply_coupons_credits')) {
								content.innerHTML = '<div class=\"sc-no-coupon\"> " . esc_html( _x( 'Sorry, No coupons available for you.', 'Message shown when there are no available coupons for the customer', 'woocommerce-smart-coupons' ) ) . " </div>';
							}
								content.dataset.loaded = 'true';
							}
						}
					};

					currentXHR.onerror = function () {
						content.innerHTML = '<p>" . esc_html__( 'Error loading content.', 'woocommerce-smart-coupons' ) . "</p>';
					};

					const params = 'action=sc_get_available_coupons&security=" . esc_js( wp_create_nonce( 'sc-get-available-coupons' ) ) . "';
					currentXHR.send(params);
				}

				document.addEventListener('click', function (e) {
					let header = e.target.closest('#coupons_list .sc-accordion-header');
					if (header) {
						e.preventDefault();
						let content = header.nextElementSibling;
						let container = document.getElementById('coupons_list');
						let isSlideEnabled = container.dataset.slideEnabled === 'true';
						header.classList.toggle('open');
						content.classList.toggle('open');

						if (!isSlideEnabled) {
							content.style.display = content.classList.contains('open') ? 'block' : 'none';
						}

						if (!content.dataset.loaded) {
							sc_load_coupons();
						}
					}
				});

				document.body.addEventListener('click', function(event) {
					const target = event.target.closest('.apply_coupons_credits');
					if (target) {
						wc_sc_apply_coupon_js.call(target);
					}
				});

				document.querySelectorAll('div#invalid_coupons_list div#all_coupon_container .sc-coupon').forEach(function(el) {
					el.classList.remove('apply_coupons_credits');
				});
			";

			wc_enqueue_js( $js );

			do_action( 'wc_smart_coupons_frontend_styles_and_scripts' );

		}

		/**
		 * Generate & add available coupons fragments
		 *
		 * @param  array $fragments Existing fragments.
		 * @return array $fragments
		 */
		public function woocommerce_update_order_review_fragments( $fragments = array() ) {

			if ( ! empty( $_POST['post_data'] ) ) { // phpcs:ignore
				wp_parse_str( $_POST['post_data'], $posted_data ); // phpcs:ignore
				if ( empty( $_REQUEST['billing_email'] ) && ! empty( $posted_data['billing_email'] ) ) { // phpcs:ignore
					$_REQUEST['billing_email'] = $posted_data['billing_email'];
				}
			}

			ob_start();
			$this->show_available_coupons_before_checkout_form();
			$fragments['wc_sc_available_coupons'] = ob_get_clean();

			return $fragments;
		}

		/**
		 * Get endpoint
		 *
		 * @return string The endpoint
		 */
		public static function get_endpoint() {
			self::$endpoint = get_option( 'woocommerce_myaccount_wc_sc_dashboard_endpoint', 'wc-smart-coupons' );
			return self::$endpoint;
		}

		/**
		 * Hooks for handle endpoint
		 */
		public function endpoint_hooks() {
			global $woocommerce_smart_coupon;
			if ( empty( self::$endpoint ) ) {
				self::$endpoint = self::get_endpoint();
			}
			if ( $this->is_wc_gte_34() ) {
				add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_endpoint_account_settings' ) );
			} else {
				add_filter( 'woocommerce_account_settings', array( $this, 'add_endpoint_account_settings' ) );
			}
		}

		/**
		 * Add UI option for changing Smart Coupons endpoints in WC settings
		 *
		 * @param mixed $settings Existing settings.
		 * @return mixed $settings
		 */
		public function add_endpoint_account_settings( $settings ) {

			$sc_endpoint_setting = array(
				'title'    => __( 'Coupons', 'woocommerce-smart-coupons' ),
				'desc'     => __( 'Endpoint for the My Account &rarr; Coupons page', 'woocommerce-smart-coupons' ),
				'id'       => 'woocommerce_myaccount_wc_sc_dashboard_endpoint',
				'type'     => 'text',
				'default'  => 'wc-smart-coupons',
				'desc_tip' => true,
			);

			$after_key = 'woocommerce_myaccount_view_order_endpoint';

			$after_key = apply_filters(
				'wc_sc_endpoint_account_settings_after_key',
				$after_key,
				array(
					'settings' => $settings,
					'source'   => $this,
				)
			);

			WC_Smart_Coupons::insert_setting_after( $settings, $after_key, $sc_endpoint_setting );

			return $settings;
		}

		/**
		 * Fetch generated coupon's details
		 *
		 * Either order_ids or user_ids required
		 *
		 * @param array|int $order_ids Order IDs.
		 * @param array|int $user_ids User IDs.
		 * @param boolean   $html Whether to return only data or html code, optional, default:false.
		 * @param boolean   $header Whether to add a header above the list of generated coupon details, optional, default:false.
		 * @param string    $layout Possible values 'box' or 'table' layout to show generated coupons details, optional, default:box.
		 *
		 * @return array $generated_coupon_data associative array containing generated coupon's details
		 */
		public function get_generated_coupon_data( $order_ids = '', $user_ids = '', $html = false, $header = false, $layout = 'box' ) {
			global $wpdb;

			if ( ! is_array( $order_ids ) ) {
				$order_ids = ( ! empty( $order_ids ) ) ? array( $order_ids ) : array();
			}

			if ( ! is_array( $user_ids ) ) {
				$user_ids = ( ! empty( $user_ids ) ) ? array( $user_ids ) : array();
			}

			$user_order_ids = array();

			if ( ! empty( $user_ids ) ) {

				if ( $this->is_hpos() ) {
					$user_order_ids_query = $wpdb->prepare(
						"SELECT DISTINCT id FROM {$wpdb->prefix}wc_orders
							WHERE %d
								AND customer_id",
						1
					);
				} else {
					$user_order_ids_query = $wpdb->prepare(
						"SELECT DISTINCT post_id FROM {$wpdb->prefix}postmeta
							WHERE %d
								AND meta_value",
						1
					);
				}

				if ( count( $user_ids ) === 1 ) {
					$user_order_ids_query .= $wpdb->prepare( ' = %d', current( $user_ids ) );
				} else {
					$how_many              = count( $user_ids );
					$placeholders          = array_fill( 0, $how_many, '%d' );
					$user_order_ids_query .= $wpdb->prepare( ' IN ( ' . implode( ',', $placeholders ) . ' )', $user_ids ); // phpcs:ignore
				}

				if ( ! $this->is_hpos() ) {
					$user_order_ids_query .= $wpdb->prepare( ' AND meta_key = %s', '_customer_user' );
				}

				$unique_user_ids = array_unique( $user_ids );

				$user_order_ids = wp_cache_get( 'wc_sc_order_ids_by_user_id_' . implode( '_', $unique_user_ids ), 'woocommerce_smart_coupons' );

				if ( false === $user_order_ids ) {
					$user_order_ids = $wpdb->get_col( $user_order_ids_query ); // phpcs:ignore
					wp_cache_set( 'wc_sc_order_ids_by_user_id_' . implode( '_', $unique_user_ids ), $user_order_ids, 'woocommerce_smart_coupons' );
					$this->maybe_add_cache_key( 'wc_sc_order_ids_by_user_id_' . implode( '_', $unique_user_ids ) );
				}
			}

			$new_order_ids = array_unique( array_merge( $user_order_ids, $order_ids ) );

			$generated_coupon_data = array();
			foreach ( $new_order_ids as $id ) {
				$order                      = function_exists( 'wc_get_order' ) ? wc_get_order( $id ) : null;
				$is_callable_order_get_meta = $this->is_callable( $order, 'get_meta' );
				$data                       = ( true === $is_callable_order_get_meta ) ? $order->get_meta( 'sc_coupon_receiver_details' ) : get_post_meta( $id, 'sc_coupon_receiver_details', true );
				if ( empty( $data ) ) {
					continue;
				}
				$wc_email_settings = get_option( 'woocommerce_wc_sc_combined_email_coupon_settings', array( 'enabled' => 'no' ) );

				if ( 'yes' === $wc_email_settings['enabled'] ) {
					/**
					 * Filters the combined receiver details for coupons before sending the email.
					 *
					 * Developers can use this filter to customize the list of coupons sent to each recipient
					 * based on specific conditions or business rules.
					 *
					 * @param array $data Array of details for the combined coupon receiver.
					 *                                        Includes coupon details like coupon code, email, message, etc.
					 * @param array $args {
					 *     @type WC_Order $order The order object associated with these coupons.
					 * }
					 *
					 * @return array $data Modified array of email to coupons details.
					 */
					$data = apply_filters(
						'wc_sc_filter_coupons_for_email',
						$data,
						array(
							'order_obj' => $order,
						)
					);
				}

				$from = $this->is_callable( $order, 'get_billing_email' ) ? $order->get_billing_email() : get_post_meta( $id, '_billing_email', true );
				if ( empty( $generated_coupon_data[ $from ] ) ) {
					$generated_coupon_data[ $from ] = array();
				}
				$generated_coupon_data[ $from ] = array_merge( $generated_coupon_data[ $from ], $data );
			}

			$backtrace           = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );  // phpcs:ignore
			$backtrace_functions = wp_list_pluck( $backtrace, 'function' );
			if ( in_array( 'sc_generated_coupon_data_metabox', $backtrace_functions, true ) ) {
				reset( $order_ids );
				$order_id   = current( $order_ids );
				$from       = $this->get_post_meta( $order_id, '_billing_email', true );
				$coupon_ids = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						"SELECT DISTINCT p.ID
							FROM {$wpdb->posts} AS p
								LEFT JOIN {$wpdb->postmeta} AS pm
									ON (p.ID = pm.post_id)
							WHERE p.post_type = %s
								AND p.post_status = %s
								AND pm.meta_key = %s
								AND (pm.meta_value = %s
										OR pm.meta_value = %d )",
						'shop_coupon',
						'future',
						'generated_from_order_id',
						$order_id,
						$order_id
					)
				);
				if ( ! empty( $coupon_ids ) && is_array( $coupon_ids ) ) {
					foreach ( $coupon_ids as $coupon_id ) {
						$coupon_receiver_details = $this->get_post_meta( $coupon_id, 'wc_sc_coupon_receiver_details', true );
						$from                    = ( ! empty( $coupon_receiver_details['gift_certificate_sender_email'] ) ) ? $coupon_receiver_details['gift_certificate_sender_email'] : $from;
						if ( empty( $generated_coupon_data[ $from ] ) || ! is_array( $generated_coupon_data[ $from ] ) ) {
							$generated_coupon_data[ $from ] = array();
						}
						$generated_coupon_data[ $from ][] = array(
							'code'     => ( ! empty( $coupon_receiver_details['coupon_details']['code'] ) ) ? $coupon_receiver_details['coupon_details']['code'] : '',
							'amount'   => ( ! empty( $coupon_receiver_details['coupon_details']['amount'] ) ) ? $coupon_receiver_details['coupon_details']['amount'] : 0,
							'email'    => ( ! empty( $coupon_receiver_details['gift_certificate_receiver_email'] ) ) ? $coupon_receiver_details['gift_certificate_receiver_email'] : '',
							'message'  => ( ! empty( $coupon_receiver_details['message_from_sender'] ) ) ? $coupon_receiver_details['message_from_sender'] : '',
							'order_id' => ( ! empty( $order_id ) ) ? $order_id : 0,
						);
					}
				}
				if ( empty( $generated_coupon_data ) ) {
					$order         = ( ! empty( $order_id ) ) ? wc_get_order( $order_id ) : null;
					$order_actions = array();
					if ( ! class_exists( 'WC_SC_Coupon_Process' ) ) {
						include_once 'class-wc-sc-coupon-process.php';
					}
					$wc_sc_coupon_process = ( is_callable( 'WC_SC_Coupon_Process::get_instance' ) ) ? WC_SC_Coupon_Process::get_instance() : null;
					if ( $this->is_callable( $wc_sc_coupon_process, 'order_actions' ) ) {
						$order_actions = $wc_sc_coupon_process->order_actions( array(), $order );
					}
					if ( ! empty( $order_actions ) ) {
						$target_actions   = array( 'wc_sc_regenerate_coupons', 'wc_sc_regenerate_resend_coupons' );
						$all_actions      = array_keys( $order_actions );
						$action_intersect = array_intersect( $target_actions, $all_actions );
						if ( ! empty( $action_intersect ) ) {
							?>
							<p>
								<?php
									/* translators: 1. Link to jump to 'Order actions' metabox 2. Text 'Order actions' 3. Text 'arrow' */
									echo sprintf( esc_html_x( 'Coupons are not generated for this order. You can regenerate it from %1$s. Select an appropriate action from the %2$s dropdown menu and hit the %3$s button next to it.', 'Generated coupons metabox - Order edit admin page', 'woocommerce-smart-coupons' ), '<strong><a href="#woocommerce-order-actions">' . esc_html_x( 'Order actions', 'Generated coupons metabox - Order edit admin page', 'woocommerce-smart-coupons' ) . '</a></strong>', '<strong>' . esc_html_x( 'Order actions', 'Generated coupons metabox - Order edit admin page', 'woocommerce-smart-coupons' ) . '</strong>', '<strong>' . esc_html_x( 'arrow', 'Generated coupons metabox - Order edit admin page', 'woocommerce-smart-coupons' ) . '</strong>' );
								?>
							</p>
							<div>
								<ul class="ul-disc">
									<?php
									foreach ( $order_actions as $action => $label ) {
										switch ( $action ) {
											case 'wc_sc_regenerate_coupons':
												?>
												<li>
													<?php
														/* translators: 1. Label for Order action for regenerating coupons */
														echo sprintf( esc_html_x( 'Select %s to only regenerate coupons. No email will be sent for this.', 'Generated coupons metabox - Order edit admin page', 'woocommerce-smart-coupons' ), '<strong>' . esc_html( $label ) . '</strong>' );
													?>
												</li>
												<?php
												break;
											case 'wc_sc_regenerate_resend_coupons':
												?>
												<li>
													<?php
														/* translators: 1. Label for Order action for regenerating & resending coupons */
														echo sprintf( esc_html_x( 'Select %s to regenerate as well as resend coupons to the recipients via email.', 'Generated coupons metabox - Order edit admin page', 'woocommerce-smart-coupons' ), '<strong>' . esc_html( $label ) . '</strong>' );
													?>
												</li>
												<?php
												break;
										}
									}
									?>
								</ul>
							</div>
							<?php
						}
					}
				}
			}

			if ( empty( $generated_coupon_data ) ) {
				return;
			}

			if ( $html ) {

				ob_start();
				if ( 'table' === $layout ) {
					$this->get_generated_coupon_data_table( $generated_coupon_data );
				} else {
					$this->get_generated_coupon_data_box( $generated_coupon_data );
				}
				$coupon_details_html_content = ob_get_clean();

				$found_coupon = ( 'table' === $layout ) ? ( strpos( $coupon_details_html_content, 'coupon_received_row' ) !== false ) : ( strpos( $coupon_details_html_content, '<details' ) !== false );

				if ( $found_coupon && ! empty( $coupon_details_html_content ) ) {

					echo '<div id="generated_coupon_data_container" style="padding: 2em 0 2em;">';

					if ( $header ) {
						echo '<h2>' . esc_html__( 'Coupon Received', 'woocommerce-smart-coupons' ) . '</h2>';
						echo '<p>' . esc_html__( 'List of coupons & their details which you have received from the store. Click on the coupon to see the details.', 'woocommerce-smart-coupons' ) . '</p>';
					}

					echo $coupon_details_html_content; // phpcs:ignore

					echo '</div>';

				}

				return;

			}

			return $generated_coupon_data;
		}

		/**
		 * HTML code to display generated coupon's data in box layout
		 *
		 * @param array $generated_coupon_data Associative array containing generated coupon's details.
		 */
		public function get_generated_coupon_data_box( $generated_coupon_data = array() ) {
			if ( empty( $generated_coupon_data ) ) {
				return;
			}

			$design           = get_option( 'wc_sc_setting_coupon_design', 'basic' );
			$background_color = get_option( 'wc_sc_setting_coupon_background_color', '#39cccc' );
			$foreground_color = get_option( 'wc_sc_setting_coupon_foreground_color', '#30050b' );
			$third_color      = get_option( 'wc_sc_setting_coupon_third_color', '#39cccc' );

			$show_coupon_description = get_option( 'smart_coupons_show_coupon_description', 'no' );

			$valid_designs = $this->get_valid_coupon_designs();

			if ( ! in_array( $design, $valid_designs, true ) ) {
				$design = 'basic';
			}

			$current_filter = current_filter();
			if ( 'woocommerce_email_after_order_table' === $current_filter ) {
				$design = 'email-coupon';
			}

			$email = $this->get_current_user_email();
			$js    = "
					var switchMoreLess = function() {
						var total = jQuery('details').length;
						var open = jQuery('details[open]').length;
						if ( open == total ) {
							jQuery('a#more_less').text('" . __( 'Less details', 'woocommerce-smart-coupons' ) . "');
						} else {
							jQuery('a#more_less').text('" . __( 'More details', 'woocommerce-smart-coupons' ) . "');
						}
					};
					switchMoreLess();

					jQuery('a#more_less').on('click', function(){
						var current = jQuery('details').attr('open');
						if ( current == '' || current == undefined ) {
							jQuery('details').attr('open', 'open');
							jQuery('a#more_less').text('" . __( 'Less details', 'woocommerce-smart-coupons' ) . "');
						} else {
							jQuery('details').removeAttr('open');
							jQuery('a#more_less').text('" . __( 'More details', 'woocommerce-smart-coupons' ) . "');
						}
					});

					jQuery('summary.generated_coupon_summary').on('mouseup', function(){
						setTimeout( switchMoreLess, 10 );
					});

					jQuery('span.expand_collapse').show();

					var generated_coupon_element = jQuery('#all_generated_coupon');
					var generated_coupon_container_height = generated_coupon_element.height();
					if ( generated_coupon_container_height > 400 ) {
						generated_coupon_element.css('height', '400px');
						generated_coupon_element.css('overflow-y', 'scroll');
					} else {
						generated_coupon_element.css('height', '');
						generated_coupon_element.css('overflow-y', '');
					}

					jQuery('#all_generated_coupon').on('click', '.coupon-container', function(){
						setTimeout(function(){
							var current_element = jQuery(this).find('details');
							var is_open = current_element.attr('open');
							if ( is_open == '' || is_open == undefined ) {
									current_element.attr('open', 'open');
							} else {
								current_element.removeAttr('open');
							}
						}, 1);
					});

				";

			wc_enqueue_js( $js );

			?>
			<style type="text/css">
				.coupon-container {
					margin: .2em;
					box-shadow: 0 0 5px #e0e0e0;
					display: inline-table;
					text-align: center;
					cursor: pointer;
					max-width: 49%;
					padding: .55em;
					line-height: 1.4em;
				}
				.coupon-container.previews { cursor: inherit }

				.coupon-content {
					padding: 0.2em 1.2em;
				}

				.coupon-content .code {
					font-family: monospace;
					font-size: 1.2em;
					font-weight:700;
				}

				.coupon-content .coupon-expire,
				.coupon-content .discount-info {
					font-family: Helvetica, Arial, sans-serif;
					font-size: 1em;
				}
				.coupon-content .discount-description {
					font: .7em/1 Helvetica, Arial, sans-serif;
					width: 250px;
					margin: 10px inherit;
					display: inline-block;
				}

				.generated_coupon_details { padding: 0.6em 1em 0.4em 1em; text-align: left; }
				.generated_coupon_data { border: solid 1px lightgrey; margin-bottom: 5px; margin-right: 5px; width: 50%; }
				.generated_coupon_details p { margin: 0; }
				span.expand_collapse { text-align: right; display: block; margin-bottom: 1em; cursor: pointer; }
				.float_right_block { float: right; }
				summary::-webkit-details-marker { display: none; }
				details[open] summary::-webkit-details-marker { display: none; }
				span.wc_sc_coupon_actions_wrapper {
					display: block;
					text-align: right;
				}
			</style>
			<style type="text/css"><?php echo esc_html( wp_strip_all_tags( $this->get_coupon_styles( $design ), true ) ); // phpcs:ignore ?></style>
			<?php
			if ( 'custom-design' !== $design ) {
				if ( ! wp_style_is( 'smart-coupon-designs' ) ) {
					wp_enqueue_style( 'smart-coupon-designs' );
				}
				?>
					<style type="text/css">
						:root {
							--sc-color1: <?php echo esc_html( $background_color ); ?>;
							--sc-color2: <?php echo esc_html( $foreground_color ); ?>;
							--sc-color3: <?php echo esc_html( $third_color ); ?>;
						}
					</style>
					<?php
			}
			?>
			<div class="generated_coupon_data_wrapper">
				<span class="expand_collapse" style="display: none;">
					<a id="more_less"><?php echo esc_html__( 'More details', 'woocommerce-smart-coupons' ); ?></a>
				</span>
				<div id="sc-cc">
					<div id="all_generated_coupon" class="sc-coupons-list">
					<?php
					$is_meta_box         = false;
					$backtrace           = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );  // phpcs:ignore
					$backtrace_functions = wp_list_pluck( $backtrace, 'function' );
					if ( in_array( 'sc_generated_coupon_data_metabox', $backtrace_functions, true ) ) {
						$is_meta_box = true;
					}
					foreach ( $generated_coupon_data as $from => $data ) {
						$order        = null;
						$coupon_codes = wp_list_pluck( $data, 'code' );
						$posts        = $this->get_post_by_title( $coupon_codes, ARRAY_A, 'shop_coupon' );
						foreach ( $data as $coupon_data ) {

							if ( ! is_admin() && ! empty( $coupon_data['email'] ) && ! empty( $email ) && $coupon_data['email'] !== $email ) {
								continue;
							}

							if ( empty( $this->sc_coupon_exists( $coupon_data['code'] ) ) ) {
								continue;
							}

							$coupon   = new WC_Coupon( $coupon_data['code'] );
							$order_id = ( ! empty( $coupon_data['order_id'] ) ) ? absint( $coupon_data['order_id'] ) : 0;
							$order    = ( ! is_a( $order, 'WC_Order' ) && ! empty( $order_id ) ) ? wc_get_order( $order_id ) : $order;

							if ( $this->is_wc_gte_30() ) {
								if ( ! is_object( $coupon ) || ! is_callable( array( $coupon, 'get_id' ) ) ) {
									continue;
								}
								$coupon_id = $coupon->get_id();
								if ( empty( $coupon_id ) ) {
									if ( true === $is_meta_box ) {
										$coupon_code           = ( ! empty( $coupon_data['code'] ) ) ? $coupon_data['code'] : '';
										$sanitized_coupon_code = sanitize_title( $coupon_code ); // The generated string will be checked in an array key to locate post object.
										$coupon_post_obj       = ( ! empty( $posts[ $sanitized_coupon_code ] ) ) ? $posts[ $sanitized_coupon_code ] : null;
										if ( ! empty( $coupon_post['ID'] ) ) {
											$coupon    = new WC_Coupon( $coupon_post['ID'] );
											$coupon_id = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
										} else {
											continue;
										}
									} else {
										continue;
									}
								}
								$is_free_shipping = ( $coupon->get_free_shipping() ) ? 'yes' : 'no';
								$discount_type    = $coupon->get_discount_type();
								$expiry_date      = $coupon->get_date_expires();
								$coupon_code      = $coupon->get_code();
							} else {
								$coupon_id        = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
								$is_free_shipping = ( ! empty( $coupon->free_shipping ) ) ? $coupon->free_shipping : '';
								$discount_type    = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
								$expiry_date      = ( ! empty( $coupon->expiry_date ) ) ? $coupon->expiry_date : '';
								$coupon_code      = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
							}

							$coupon_amount = $this->get_amount( $coupon, true, $order );

							if ( empty( $coupon_id ) || empty( $discount_type ) ) {
								continue;
							}

							$coupon_post = get_post( $coupon_id );

							$coupon_meta = $this->get_coupon_meta_data( $coupon );

							$coupon_type = ( ! empty( $coupon_meta['coupon_type'] ) ) ? $coupon_meta['coupon_type'] : '';

							if ( 'yes' === $is_free_shipping ) {
								if ( ! empty( $coupon_type ) ) {
									$coupon_type .= __( ' & ', 'woocommerce-smart-coupons' );
								}
								$coupon_type .= __( 'Free Shipping', 'woocommerce-smart-coupons' );
							}

							if ( $this->is_wc_gte_30() && $expiry_date instanceof WC_DateTime ) {
								$expiry_date = ( is_callable( array( $expiry_date, 'getTimestamp' ) ) ) ? $expiry_date->getTimestamp() : null;
							} elseif ( ! is_int( $expiry_date ) ) {
								$expiry_date = $this->strtotime( $expiry_date );
							}

							if ( ! empty( $expiry_date ) && is_int( $expiry_date ) ) {
								$expiry_time = ( $this->is_callable( $coupon, 'get_meta' ) ) ? (int) $coupon->get_meta( 'wc_sc_expiry_time' ) : (int) get_post_meta( $coupon_id, 'wc_sc_expiry_time', true );
								if ( ! empty( $expiry_time ) ) {
									$expiry_date += $expiry_time; // Adding expiry time to expiry date.
								}
							}

							$coupon_description = '';
							if ( ! empty( $coupon_post->post_excerpt ) && 'yes' === $show_coupon_description ) {
								$coupon_description = $coupon_post->post_excerpt;
							}

							$is_percent = $this->is_percent_coupon( array( 'coupon_object' => $coupon ) );

							$args = array(
								'coupon_object'      => $coupon,
								'coupon_amount'      => $coupon_amount,
								'amount_symbol'      => ( true === $is_percent ) ? '%' : get_woocommerce_currency_symbol(),
								'discount_type'      => wp_strip_all_tags( $coupon_type ),
								'coupon_description' => ( ! empty( $coupon_description ) ) ? $coupon_description : wp_strip_all_tags( $this->generate_coupon_description( array( 'coupon_object' => $coupon ) ) ),
								'coupon_code'        => $coupon_code,
								'coupon_expiry'      => ( ! empty( $expiry_date ) ) ? $this->get_expiration_format( $expiry_date ) : __( 'Never expires', 'woocommerce-smart-coupons' ),
								'thumbnail_src'      => $this->get_coupon_design_thumbnail_src(
									array(
										'design'        => $design,
										'coupon_object' => $coupon,
									)
								),
								'classes'            => '',
								'template_id'        => $design,
								'is_percent'         => $is_percent,
							);

							?>
							<div>
								<details>
									<summary class="generated_coupon_summary">
										<?php wc_get_template( 'coupon-design/' . $design . '.php', $args, '', plugin_dir_path( WC_SC_PLUGIN_FILE ) . 'templates/' ); ?>
									</summary>
									<div class="generated_coupon_details">
										<?php
										if ( apply_filters(
											'wc_sc_show_email_addresses',
											( $from !== $coupon_data['email'] ),
											array(
												'source' => $this,
												'args'   => $args,
											)
										) ) {
											?>
										<p><strong><?php echo esc_html__( 'Sender', 'woocommerce-smart-coupons' ); ?>:</strong> <?php echo esc_html( $from ); ?></p>
										<p><strong><?php echo esc_html__( 'Receiver', 'woocommerce-smart-coupons' ); ?>:</strong> <?php echo esc_html( $coupon_data['email'] ); ?></p>
										<?php } ?>
										<?php if ( ! empty( $coupon_data['message'] ) ) { ?>
											<p><strong><?php echo esc_html__( 'Message', 'woocommerce-smart-coupons' ); ?>:</strong> <?php echo esc_html( $coupon_data['message'] ); ?></p>
										<?php } ?>
									</div>
								</details>
							</div>
							<?php
						}
					}
					?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * HTML code to display generated coupon's details is table layout
		 *
		 * @param array $generated_coupon_data Associative array of generated coupon's details.
		 */
		public function get_generated_coupon_data_table( $generated_coupon_data = array() ) {
			if ( empty( $generated_coupon_data ) ) {
				return;
			}
			$email = $this->get_current_user_email();
			?>
				<div class="woocommerce_order_items_wrapper">
					<table class="woocommerce_order_items">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Code', 'woocommerce-smart-coupons' ); ?></th>
								<th><?php echo esc_html__( 'Amount', 'woocommerce-smart-coupons' ); ?></th>
								<th><?php echo esc_html__( 'Receiver', 'woocommerce-smart-coupons' ); ?></th>
								<th><?php echo esc_html__( 'Message', 'woocommerce-smart-coupons' ); ?></th>
								<th><?php echo esc_html__( 'Sender', 'woocommerce-smart-coupons' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $generated_coupon_data as $from => $data ) {
								$email = ( ! empty( $email ) ) ? $email : $from;
								foreach ( $data as $coupon_data ) {
									if ( ! is_admin() && ! empty( $coupon_data['email'] ) && $coupon_data['email'] !== $email ) {
										continue;
									}
									echo '<tr class="coupon_received_row">';
									echo '<td>' . esc_html( $coupon_data['code'] ) . '</td>';
									echo '<td>' . wc_price( $coupon_data['amount'] ) . '</td>'; // phpcs:ignore
									echo '<td>' . esc_html( $coupon_data['email'] ) . '</td>';
									echo '<td>' . esc_html( $coupon_data['message'] ) . '</td>';
									echo '<td>' . esc_html( $from ) . '</td>';
									echo '</tr>';
								}
							}
							?>
						</tbody>
					</table>
				</div>
			<?php
		}

		/**
		 * Get current user's email
		 *
		 * @return string $email
		 */
		public function get_current_user_email() {
			$current_user = wp_get_current_user();
			if ( ! $current_user instanceof WP_User ) {
				return;
			}
			$billing_email = get_user_meta( $current_user->ID, 'billing_email', true );
			$email         = ( ! empty( $billing_email ) ) ? $billing_email : $current_user->user_email;
			return $email;
		}

		/**
		 * Display generated coupons details after Order table
		 *
		 * @param  WC_Order $order         The order.
		 * @param  boolean  $sent_to_admin Whether sent to admin.
		 * @param  boolean  $plain_text    Whether a plain text email.
		 */
		public function generated_coupon_details_after_order_table( $order = false, $sent_to_admin = false, $plain_text = false ) {

			if ( $this->is_wc_gte_30() ) {
				$order_id      = ( is_object( $order ) && is_callable( array( $order, 'get_id' ) ) ) ? $order->get_id() : 0;
				$order_refunds = ( ! empty( $order ) && is_callable( array( $order, 'get_refunds' ) ) ) ? $order->get_refunds() : array();
			} else {
				$order_id      = ( ! empty( $order->id ) ) ? $order->id : 0;
				$order_refunds = ( ! empty( $order->refunds ) ) ? $order->refunds : array();
			}

			if ( ! empty( $order_refunds ) ) {
				return;
			}

			if ( ! empty( $order_id ) ) {
				$this->get_generated_coupon_data( $order_id, '', true, true );
			}
		}

		/**
		 * Display generated coupons details on View Order page
		 *
		 * @param int $order_id The order id.
		 */
		public function generated_coupon_details_view_order( $order_id = 0 ) {
			if ( ! empty( $order_id ) ) {
				$this->get_generated_coupon_data( $order_id, '', true, true );
			}
		}

		/**
		 * Metabox on Order Edit Admin page to show generated coupons during the order
		 */
		public function add_generated_coupon_details() {
			global $post, $theorder;

			if ( is_a( $theorder, 'WC_Order' ) ) {
				$post_type = 'shop_order';
			} elseif ( ! empty( $post->ID ) ) {
				$post_type = ( ! empty( $post->ID ) ) ? $this->get_post_type( $post->ID ) : $this->get_post_type();
			}

			if ( empty( $post_type ) || 'shop_order' !== $post_type ) {
				return;
			}

			$screen = ( $this->is_hpos() ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

			add_meta_box( 'sc-generated-coupon-data', __( 'Generated coupons', 'woocommerce-smart-coupons' ), array( $this, 'sc_generated_coupon_data_metabox' ), $screen, 'normal' );
		}

		/**
		 * Metabox content (Generated coupon's details)
		 */
		public function sc_generated_coupon_data_metabox() {
			global $post, $theorder;
			$order_id = ( is_object( $theorder ) && is_a( $theorder, 'WC_Order' ) && $this->is_callable( $theorder, 'get_id' ) ) ? $theorder->get_id() : ( ! empty( $post->ID ) ? $post->ID : 0 );
			if ( ! empty( $order_id ) ) {
				$this->get_generated_coupon_data( $order_id, '', true, false );
			}
		}

		/**
		 * Modify available variation
		 *
		 * @param array                $found_variation The found variation.
		 * @param WC_Product_Variable  $product The variable product object.
		 * @param WC_Product_Variation $variation The variation object.
		 * @return array
		 */
		public function modify_available_variation( $found_variation = array(), $product = null, $variation = null ) {
			if ( is_a( $product, 'WC_Product_Variable' ) ) {
				if ( $this->is_wc_gte_30() ) {
					$product_id = ( is_object( $product ) && is_callable( array( $product, 'get_id' ) ) ) ? $product->get_id() : 0;
				} else {
					$product_id = ( ! empty( $product->id ) ) ? $product->id : 0;
				}

				$coupons = $this->get_coupon_titles( array( 'product_object' => $variation ) );

				if ( ! empty( $coupons ) && $this->is_coupon_amount_pick_from_product_price( $coupons ) ) {
					if ( is_a( $variation, 'WC_Product_Variation' ) ) {
						$found_variation['price_including_tax']      = wc_round_discount( wc_get_price_including_tax( $variation ), 2 );
						$found_variation['price_including_tax_html'] = wc_price( $found_variation['price_including_tax'] );
						$found_variation['price_excluding_tax']      = wc_round_discount( wc_get_price_excluding_tax( $variation ), 2 );
						$found_variation['price_excluding_tax_html'] = wc_price( $found_variation['price_excluding_tax'] );
						if ( is_callable( array( $variation, 'get_regular_price' ) ) ) {
							$regular_price                                       = $variation->get_regular_price();
							$found_variation['regular_price_including_tax']      = wc_round_discount( wc_get_price_including_tax( $variation, array( 'price' => $regular_price ) ), 2 );
							$found_variation['regular_price_including_tax_html'] = wc_price( $found_variation['regular_price_including_tax'] );
							$found_variation['regular_price_excluding_tax']      = wc_round_discount( wc_get_price_excluding_tax( $variation, array( 'price' => $regular_price ) ), 2 );
							$found_variation['regular_price_excluding_tax_html'] = wc_price( $found_variation['regular_price_excluding_tax'] );
						}
					}
				}
			}
			return $found_variation;
		}

		/**
		 * Function to check & enable store notice for coupon
		 */
		public function maybe_enable_store_notice_for_coupon() {
			$coupon_code = get_option( 'smart_coupons_storewide_offer_coupon_code' );
			if ( ! empty( $coupon_code ) ) {
				$coupon_id                         = wc_get_coupon_id_by_code( $coupon_code );
				$coupon                            = new WC_Coupon( $coupon_id );
				$coupon_status                     = ( $this->is_wc_greater_than( '6.1.2' ) && $this->is_callable( $coupon, 'get_status' ) ) ? $coupon->get_status() : get_post_status( $coupon_id );
				$wc_sc_setting_store_notice_design = $this->sc_get_option( 'wc_sc_setting_store_notice_design' );
				if ( 'publish' === $coupon_status ) {
					if ( empty( $wc_sc_setting_store_notice_design ) ) {
						update_option( 'wc_sc_setting_store_notice_design', 'notification', 'no' );
					}
				} elseif ( 'publish' !== $coupon_status ) {
					if ( ! empty( $wc_sc_setting_store_notice_design ) ) {
						update_option( 'wc_sc_setting_store_notice_design', '', 'no' );
					}
				}
			}
		}

		/**
		 * Check if the store notice needs any change & apply the change if required
		 *
		 * @param string $displayed_notice The notice to be displayed.
		 * @param string $raw_notice The raw notice.
		 * @return string
		 */
		public function maybe_change_store_notice( $displayed_notice = '', $raw_notice = '' ) {
			$current_currency = get_woocommerce_currency();
			$base_currency    = get_option( 'woocommerce_currency' );
			if ( $base_currency !== $current_currency ) {
				$storewide_offer_coupon_code = get_option( 'smart_coupons_storewide_offer_coupon_code' );
				if ( ! empty( $storewide_offer_coupon_code ) && false !== stripos( $raw_notice, '<code>' . $storewide_offer_coupon_code . '</code>' ) ) {
					if ( ! empty( $this->sc_coupon_exists( $storewide_offer_coupon_code ) ) ) {
						$coupon     = new WC_Coupon( $storewide_offer_coupon_code );
						$is_percent = $this->is_percent_coupon( array( 'coupon_object' => $coupon ) );
						if ( false === $is_percent ) {
							$coupon_amount    = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_amount' ) ) ) ? $coupon->get_amount( 'edit' ) : 0;
							$search           = get_woocommerce_currency_symbol( $base_currency ) . $coupon_amount;
							$replace          = get_woocommerce_currency_symbol( $current_currency ) . $this->convert_price( $coupon_amount, $current_currency, $base_currency );
							$displayed_notice = str_replace( $search, $replace, $displayed_notice );
						}
					}
				}
			}
			return $displayed_notice;
		}

		/**
		 * Display storewide notice
		 */
		public function storewide_notice() {
			$coupon_code = get_option( 'smart_coupons_storewide_offer_coupon_code' );
			$design      = get_option( 'wc_sc_setting_store_notice_design' );

			$coupon_id = ( ! empty( $coupon_code ) ) ? wc_get_coupon_id_by_code( $coupon_code ) : 0;
			if ( empty( $coupon_id ) ) {
				return;
			}

			$coupon      = new WC_Coupon( $coupon_id );
			$description = $this->generate_storewide_offer_coupon_description( array( 'coupon_object' => $coupon ) );
			update_option( 'smart_coupons_store_notice', wp_filter_post_kses( $description ), 'no' );
			$coupon_url = $this->generate_coupon_url( array( 'coupon-code' => $coupon_code ) );

			$args = array(
				'title_1'      => __( 'Insane deal', 'woocommerce-smart-coupons' ),
				'title_2'      => __( 'Black Friday', 'woocommerce-smart-coupons' ),
				'description'  => ( ! empty( $description ) ) ? $description : __( 'get this & several other products at a huge discount', 'woocommerce-smart-coupons' ),
				'button_label' => __( 'Shop now!', 'woocommerce-smart-coupons' ),
				'button_url'   => $coupon_url,
			);

			$html = wc_get_template( 'store-notice-design/' . $design . '.php', $args, '', plugin_dir_path( WC_SC_PLUGIN_FILE ) . 'templates/' );

			?>
			<script type="text/javascript">
				var content = '<?php echo esc_html( $html ); ?>';
				document.body.append(content);
			</script>
			<?php
		}

	}

}

WC_SC_Display_Coupons::get_instance();
