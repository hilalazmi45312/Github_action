<?php
/**
 * Main class for Smart Coupons Email
 *
 * @author      StoreApps
 * @since       4.4.1
 * @version     1.4.0
 *
 * @package     woocommerce-smart-coupons/includes/emails/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Email' ) ) {
	/**
	 * The Smart Coupons Email class
	 *
	 * @extends \WC_Email
	 */
	class WC_SC_Email extends WC_Email {

		/**
		 * Email args defaults
		 *
		 * @var array
		 */
		public $email_args = array(
			'email'                         => '',
			'coupon'                        => array(),
			'discount_type'                 => 'smart_coupon',
			'smart_coupon_type'             => '',
			'receiver_name'                 => '',
			'message_from_sender'           => '',
			'gift_certificate_sender_name'  => '',
			'gift_certificate_sender_email' => '',
			'from'                          => '',
			'sender'                        => '',
			'is_gift'                       => false,
		);

		/**
		 * Get shop page url
		 *
		 * @return string $url Shop page url
		 */
		public function get_url() {
			try {
				global $woocommerce_smart_coupon;

				if ( $woocommerce_smart_coupon->is_wc_gte_30() ) {
					$page_id = wc_get_page_id( 'shop' );
				} else {
					$page_id = woocommerce_get_page_id( 'shop' );
				}

				$url = ( get_option( 'permalink_structure' ) ) ? get_permalink( $page_id ) : get_post_type_archive_link( 'product' );

			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}

				$url = home_url( '/' );
			}

			return $url;
		}

		/**
		 * Function to get sender name.
		 *
		 * @return string $sender_name Sender name.
		 */
		public function get_sender_name() {
			if ( isset( $this->email_args['gift_certificate_sender_name'] ) && ! empty( $this->email_args['gift_certificate_sender_name'] ) ) {
				$sender_name = $this->email_args['gift_certificate_sender_name'];
			} else {
				$sender_name = is_callable( array( $this, 'get_blogname' ) ) ? $this->get_blogname() : '';
			}

			return $sender_name;
		}

		/**
		 * Function to get sender email.
		 *
		 * @return string $sender_email Sender email.
		 */
		public function get_sender_email() {

			$sender_email = isset( $this->email_args['gift_certificate_sender_email'] ) ? $this->email_args['gift_certificate_sender_email'] : '';

			return $sender_email;
		}

		/**
		 * Initialize Settings Form Fields
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce-smart-coupons' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );

			$this->form_fields = array(
				'enabled'    => array(
					'title'   => __( 'Enable/Disable', 'woocommerce-smart-coupons' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce-smart-coupons' ),
					'default' => 'yes',
				),
				'email_type' => array(
					'title'       => __( 'Email type', 'woocommerce-smart-coupons' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce-smart-coupons' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
				'subject'    => array(
					'title'       => __( 'Subject', 'woocommerce-smart-coupons' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'    => array(
					'title'       => __( 'Email heading', 'woocommerce-smart-coupons' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
			);
		}

		/**
		 * Function to update SC admin email settings when WC email settings get updated
		 */
		public function process_admin_options() {
			// Save regular options.
			parent::process_admin_options();

			$is_email_enabled = $this->get_field_value( 'enabled', $this->form_fields['enabled'] );

			if ( ! empty( $is_email_enabled ) ) {
				update_option( 'smart_coupons_is_send_email', $is_email_enabled, 'no' );
			}
		}

		/**
		 * Function to get coupon type for current coupon being sent.
		 *
		 * @return string $coupon_type Coupon type.
		 */
		public function get_coupon_type() {
			global $store_credit_label, $woocommerce_smart_coupon;

			try {

				$discount_type = $this->object->get_discount_type();
				$is_gift       = isset( $this->email_args['is_gift'] ) ? $this->email_args['is_gift'] : '';

				if ( 'smart_coupon' === $discount_type && 'yes' === $is_gift ) {
					$smart_coupon_type = __( 'Gift Card', 'woocommerce-smart-coupons' );
				} else {
					$smart_coupon_type = __( 'Store Credit', 'woocommerce-smart-coupons' );
				}

				if ( ! empty( $store_credit_label['singular'] ) ) {
					$smart_coupon_type = ucwords( $store_credit_label['singular'] );
				}

				$coupon_type = ( 'smart_coupon' === $discount_type && ! empty( $smart_coupon_type ) ) ? $smart_coupon_type : __( 'coupon', 'woocommerce-smart-coupons' );
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}

				$coupon_type = __( 'coupon', 'woocommerce-smart-coupons' );
			}

			return $coupon_type;
		}

		/**
		 * Function to get coupon expiry date/time for current coupon being sent.
		 *
		 * @return string $coupon_expiry Coupon expiry.
		 */
		public function get_coupon_expiry() {

			global $woocommerce_smart_coupon, $wpdb;

			try {
				$coupon = $this->object;
				if ( $woocommerce_smart_coupon->is_wc_gte_30() ) {
					$coupon_id = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
				} else {
					$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
				}
				// phpcs:disable
				// Get the expiration date and schedule reminder.
				$expiration_date = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT UNIX_TIMESTAMP(date_expires) FROM {$wpdb->prefix}wc_smart_coupons WHERE id = %d",
						$coupon_id
					)
				);
				$expiration_date = $expiration_date ? $woocommerce_smart_coupon->get_expiration_format( $expiration_date ) : esc_html__( 'Never expires', 'woocommerce-smart-coupons' );
				// phpcs:enable
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}

				$expiration_date = 0;
			}

			return $expiration_date;
		}

		/**
		 * Generate Coupon Design HTML.
		 *
		 * @param int       $coupon_id The coupon ID.
		 * @param WC_Coupon $coupon The coupon object.
		 * @return string HTML output of the coupon design.
		 */
		public function get_coupon_design_html( $coupon_id, $coupon ) {
			global $woocommerce_smart_coupon;

			try {
				if ( empty( $coupon_id ) ) {
					return '';
				}

				if ( empty( $coupon ) ) {
					$coupon = new WC_Coupon( $coupon_id );
				}

				if ( ! $coupon instanceof WC_Coupon ) {
					return '';
				}

				// Get coupon meta data.
				$coupon_data = $woocommerce_smart_coupon->get_coupon_meta_data( $coupon );

				if ( $woocommerce_smart_coupon->is_wc_gte_30() ) {
					$is_free_shipping = ( $coupon->get_free_shipping() ) ? 'yes' : 'no';
					$expiry_date      = $coupon->get_date_expires();
					$coupon_code      = $coupon->get_code();
				} else {
					$is_free_shipping = ( ! empty( $coupon->free_shipping ) ) ? $coupon->free_shipping : '';
					$expiry_date      = ( ! empty( $coupon->expiry_date ) ) ? $coupon->expiry_date : '';
					$coupon_code      = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
				}

				$design                  = get_option( 'wc_sc_setting_coupon_design', 'basic' );
				$background_color        = get_option( 'wc_sc_setting_coupon_background_color', '#39cccc' );
				$foreground_color        = get_option( 'wc_sc_setting_coupon_foreground_color', '#30050b' );
				$third_color             = get_option( 'wc_sc_setting_coupon_third_color', '#39cccc' );
				$show_coupon_description = get_option( 'smart_coupons_show_coupon_description', 'no' );

				// Check if the design is valid.
				$valid_designs = $woocommerce_smart_coupon->get_valid_coupon_designs();
				if ( ! in_array( $design, $valid_designs, true ) ) {
					$design = 'basic';
				}

				$design = ( 'custom-design' !== $design ) ? 'email-coupon' : $design;

				// Coupon-specific parameters.
				$coupon_amount      = $woocommerce_smart_coupon->get_amount( $coupon, true );
				$is_percent         = ( $coupon->get_discount_type() === 'percent' );
				$coupon_description = ( 'yes' === $show_coupon_description ) ? $coupon->get_description() : '';

				$coupon_styles = $woocommerce_smart_coupon->get_coupon_styles( $design, array( 'is_email' => 'yes' ) );
				$coupon_type   = ( ! empty( $coupon_data['coupon_type'] ) ) ? $coupon_data['coupon_type'] : '';

				if ( 'yes' === $is_free_shipping ) {
					if ( ! empty( $coupon_type ) ) {
						$coupon_type .= __( ' & ', 'woocommerce-smart-coupons' );
					}
					$coupon_type .= __( 'Free Shipping', 'woocommerce-smart-coupons' );
				}

				if ( ! empty( $expiry_date ) ) {
					if ( $woocommerce_smart_coupon->is_wc_gte_30() && $expiry_date instanceof WC_DateTime ) {
						$expiry_date = ( is_callable( array( $expiry_date, 'getTimestamp' ) ) ) ? $expiry_date->getTimestamp() : null;
					} elseif ( ! is_int( $expiry_date ) ) {
						$expiry_date = strtotime( $expiry_date );
					}
					if ( ! empty( $expiry_date ) && is_int( $expiry_date ) ) {
						$expiry_time = (int) $woocommerce_smart_coupon->get_post_meta( $coupon_id, 'wc_sc_expiry_time', true );
						if ( ! empty( $expiry_time ) ) {
							$expiry_date += $expiry_time; // Adding expiry time to expiry date.
						}
					}
				}

				$coupon_target              = '';
				$wc_url_coupons_active_urls = get_option( 'wc_url_coupons_active_urls' ); // From plugin WooCommerce URL coupons.
				if ( ! empty( $wc_url_coupons_active_urls ) ) {
					$coupon_target = ( ! empty( $wc_url_coupons_active_urls[ $coupon_id ]['url'] ) ) ? $wc_url_coupons_active_urls[ $coupon_id ]['url'] : '';
				}
				if ( ! empty( $coupon_target ) ) {
					$coupon_target = home_url( '/' . $coupon_target );
				} else {
					$coupon_target = home_url( '/?sc-page=shop&coupon-code=' . $coupon_code );
				}

				$coupon_target = apply_filters( 'sc_coupon_url_in_email', $coupon_target, $coupon );
				// Template arguments.
				$args = array(
					'coupon_object'      => $coupon,
					'coupon_amount'      => $coupon_amount,
					'amount_symbol'      => ( $is_percent ) ? '%' : get_woocommerce_currency_symbol(),
					'discount_type'      => wp_strip_all_tags( $coupon_type ),
					'coupon_description' => ! empty( $coupon_description ) ? $coupon_description : wp_strip_all_tags( $woocommerce_smart_coupon->generate_coupon_description( array( 'coupon_object' => $coupon ) ) ),
					'coupon_code'        => $coupon->get_code(),
					'coupon_expiry'      => ( ! empty( $expiry_date ) ) ? $woocommerce_smart_coupon->get_expiration_format( $expiry_date ) : _x( 'Never expires', 'coupon never expires', 'woocommerce-smart-coupons' ),
					'thumbnail_src'      => $woocommerce_smart_coupon->get_coupon_design_thumbnail_src(
						array(
							'design'        => $design,
							'coupon_object' => $coupon,
						)
					),
					'classes'            => '',
					'template_id'        => $design,
					'is_percent'         => $is_percent,
				);

				// Output the design template.
				ob_start();
				?>
				<style type="text/css">
					.coupon-container {
						margin: .2em;
						box-shadow: 0 0 5px #e0e0e0;
						display: inline-table;
						text-align: center;
						cursor: pointer;
						padding: .55em;
						line-height: 1.4em;
					}

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
				</style>
				<style type="text/css"><?php echo ( isset( $coupon_styles ) && ! empty( $coupon_styles ) ) ? esc_html( wp_strip_all_tags( $coupon_styles, true ) ) : ''; // phpcs:ignore ?></style>
				<?php if ( 'custom-design' !== $design ) { ?>
					<style type="text/css">
						:root {
							--sc-color1: <?php echo esc_html( $background_color ); ?>;
							--sc-color2: <?php echo esc_html( $foreground_color ); ?>;
							--sc-color3: <?php echo esc_html( $third_color ); ?>;
						}
					</style>
				<?php } ?>
				<div style="margin: 10px 0;" title="<?php echo esc_attr__( 'Click to visit store. This coupon will be applied automatically.', 'woocommerce-smart-coupons' ); ?>">
					<a href="<?php echo esc_url( $coupon_target ); ?>" style="color: #444;">
						<?php wc_get_template( 'coupon-design/' . $design . '.php', $args, '', plugin_dir_path( WC_SC_PLUGIN_FILE ) . 'templates/' ); ?>
					</a>
				</div>
				<?php
				return ob_get_clean();
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}

				ob_end_clean();
				return '';
			}

		}

	}

}
