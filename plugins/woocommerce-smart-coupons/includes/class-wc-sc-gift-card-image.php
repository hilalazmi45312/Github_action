<?php
/**
 * Gift Card Image Upload Handler - Public Images
 *
 * @author  "StoreApps <support@storeapps.org>"
 * @since   9.55.0
 * @version 1.1.0
 *
 * @package woocommerce-smart-coupons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

if ( ! class_exists( 'WC_SC_Gift_Card_Image' ) ) {
	/**
	 * Class for handling gift card image uploads
	 */
	class WC_SC_Gift_Card_Image {
		/**
		 * Variable to hold an instance of WC_SC_Gift_Card_Image
		 *
		 * @var $instance
		 */
		private static $instance = null;


		/**
		 * Allowed image types for upload
		 *
		 * @var array
		 */
		private $allowed_types = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' );

		/**
		 * Maximum file size in bytes (2MB)
		 *
		 * @var int
		 */
		private $max_file_size = 2097152;

		/**
		 * Public upload directory path
		 *
		 * @var string
		 */
		private $upload_dir = '';

		/**
		 * Public upload directory URL
		 *
		 * @var string
		 */
		private $upload_url = '';

		/**
		 * Constructor
		 */
		private function __construct() {
			// Allow developers to filter max upload size.
			$this->max_file_size = apply_filters(
				'wc_sc_custom_gift_max_file_size',
				2097152 // 2MB.
			);

			$this->init_upload_directories();
			$this->init_hooks();
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
		 * Get a single instance of WC_SC_Gift_Card_Image
		 *
		 * @return WC_SC_Gift_Card_Image Singleton object of WC_SC_Gift_Card_Image
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Add product option for enabling gift card images.
		 */
		public function add_product_gift_certificate_option() {
			/**
			 * Filter: Control whether the gift card image option should be shown.
			 *
			 * @param bool $show_gift_card_image Whether to show the gift card image option. Default comes from the wc_sc_enable_gift_card_image setting.
			 */
			$show_option = apply_filters(
				'wc_sc_show_gift_card_image_option',
				'yes'
			);

			if ( ! $show_option ) {
				return;
			}

			global $post, $store_credit_label;

			$singular_store_credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );

			// Check if this product has associated coupons.
			$product = wc_get_product( $post->ID );
			if ( ! $product ) {
				return;
			}

			echo '<div class="options_group smart-coupons-field">';
			woocommerce_wp_checkbox(
				array(
					'id'          => '_wc_sc_enable_custom_gift_image',
					'label'       => _x( 'Image upload', 'Product setting title for image upload on product edit page', 'woocommerce-smart-coupons' ),
					/* translators: %s: Label for gift card */
					'description' => sprintf( _x( 'Enable to allow users to upload image that will be sent along with %s email', 'Product setting title for image upload on product edit page', 'woocommerce-smart-coupons' ), $singular_store_credit_label ) . ' <a class="thickbox" href="' . add_query_arg( array( 'TB_iframe' => 'true' ), 'https://woocommerce.com/wp-content/uploads/2012/08/smart-coupons-gift-card-image.png' ) . '"><small>' . __( '[Preview]', 'woocommerce-smart-coupons' ) . '</small></a>',
				)
			);
			echo '</div>';
		}

		/**
		 * Initialize hooks
		 */
		private function init_hooks() {
			// Product meta fields.
			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_gift_certificate_option' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_gift_certificate_option' ), 10, 2 );
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'woocommerce_product_gift_certificate_variable' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'woocommerce_process_product_meta_gift_certificate_variable' ), 10, 2 );

			// Frontend product page.
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_upload_field' ) );
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_upload' ), 10, 3 );
			add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
			add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );

			// Order processing.
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_item_meta' ), 10, 4 );
			add_action( 'woocommerce_order_item_meta_end', array( $this, 'display_order_item_meta' ), 10, 4 );
			add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'remove_meta_from_order_display' ), 10, 2 );
			add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'display_gift_image_as_img_tag' ), 10, 3 );
			// Hide meta keys completely from order details.
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_gift_image_meta_keys' ) );

			// Scripts and styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );

			// AJAX handlers for custom upload.
			add_action( 'wp_ajax_wc_sc_upload_gift_image', array( $this, 'handle_ajax_upload' ) );
			add_action( 'wp_ajax_nopriv_wc_sc_upload_gift_image', array( $this, 'handle_ajax_upload' ) );
			add_action( 'wp_ajax_wc_sc_remove_gift_image', array( $this, 'handle_ajax_remove' ) );
			add_action( 'wp_ajax_nopriv_wc_sc_remove_gift_image', array( $this, 'handle_ajax_remove' ) );

			// Email modification and routing.
			add_filter( 'wc_sc_email_coupon_design_args', array( $this, 'modify_email_coupon_design' ), 10, 2 );
		}

		/**
		 * Initialize upload directories for public access
		 */
		private function init_upload_directories() {
			$upload_dir       = wp_get_upload_dir();
			$this->upload_dir = trailingslashit( $upload_dir['basedir'] ) . 'wc-sc-gift-card-images/';
			$this->upload_url = trailingslashit( $upload_dir['baseurl'] ) . 'wc-sc-gift-card-images/';

			// Define protection files similar to WooCommerce approach.
			$files = array(
				array(
					'base'    => $this->upload_dir,
					'file'    => 'index.html',
					'content' => '',
				),
				array(
					'base'    => $this->upload_dir,
					'file'    => '.htaccess',
					'content' => 'Options -Indexes',
				),
			);

			// Create directory and protection files.
			foreach ( $files as $file ) {
				if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
					$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
					if ( $file_handle ) {
						fwrite( $file_handle, $file['content'] ); // phpcs:ignore
						fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
					}
				}
			}
		}

		/**
		 * Add uploaded image to cart item data
		 *
		 * @param array $cart_item_data Cart item data.
		 * @param int   $product_id Product ID.
		 * @param int   $variation_id Variation ID.
		 * @return array
		 */
		public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
			if ( 'yes' !== get_post_meta( $product_id, '_wc_sc_enable_custom_gift_image', true ) ) {
				return $cart_item_data;
			}

			$token = isset( $_POST['wc_sc_gift_image_token'] ) ? wp_unslash( $_POST['wc_sc_gift_image_token'] ) : ''; // phpcs:ignore
			if ( ! empty( $token ) ) {
				$token    = sanitize_text_field( $token );
				$filename = wp_unslash( $_POST['wc_sc_gift_image_filename'] ); // phpcs:ignore
				$filename = isset( $filename ) ? sanitize_text_field( $filename ) : '';

				if ( $token ) {
					// Get upload data from transient.
					$upload_data = get_transient( 'wc_sc_upload_' . $token );
					if ( $upload_data ) {
						// Move file to permanent location with a unique name.
						$permanent_filename = $this->move_to_permanent_storage( $upload_data['filename'] );
						if ( $permanent_filename ) {
							$cart_item_data['wc_sc_custom_gift_image'] = array(
								'filename'      => $permanent_filename,
								'original_name' => $filename,
							);

							// Delete transient as it's no longer needed.
							delete_transient( 'wc_sc_upload_' . $token );
						}
					}
				}
			}

			return $cart_item_data;
		}

		/**
		 * Move the uploaded file to permanent storage
		 *
		 * @param string $temp_filename Temporary filename.
		 * @return string|false Permanent filename or false on failure.
		 */
		private function move_to_permanent_storage( $temp_filename ) {
			$temp_path = $this->upload_dir . $temp_filename;
			if ( ! file_exists( $temp_path ) ) {
				return false;
			}

			// Generate unique permanent filename.
			$extension          = pathinfo( $temp_filename, PATHINFO_EXTENSION );
			$permanent_filename = wp_generate_uuid4() . '.' . $extension;
			$permanent_path     = $this->upload_dir . $permanent_filename;

			// Initialize WP_Filesystem.
			global $wp_filesystem;
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			if ( ! WP_Filesystem() ) {
				return false;
			}

			// Move file to permanent location using WP_Filesystem for better Windows compatibility.
			if ( $wp_filesystem->move( $temp_path, $permanent_path, true ) ) {
				return $permanent_filename;
			}

			return false;
		}

		/**
		 * Save product gift certificate option
		 *
		 * @param int    $post_id Product ID.
		 * @param object $post The post object.
		 */
		public function save_product_gift_certificate_option( $post_id = 0, $post = null ) {
			$post_id                         = absint( $post_id );
			$product                         = ( ! empty( $post_id ) ) ? wc_get_product( $post_id ) : null;
			$is_callable_product_update_meta = $this->is_callable( $product, 'update_meta_data' );
			$enable_custom_image = isset( $_POST['_wc_sc_enable_custom_gift_image'] ) && 'yes' === $_POST['_wc_sc_enable_custom_gift_image'] ? 'yes' : 'no'; // phpcs:ignore
			if ( true === $is_callable_product_update_meta ) {
				$product->update_meta_data( '_wc_sc_enable_custom_gift_image', $enable_custom_image );
				if ( $this->is_callable( $product, 'save' ) ) {
					$product->save();
				}
			} else {
				update_post_meta( $post_id, '_wc_sc_enable_custom_gift_image', $enable_custom_image );
			}
		}

		/**
		 * Display upload field on product page
		 */
		public function display_upload_field() {
			global $product;

			if ( ! $this->should_display_upload_field( $product ) ) {
				return;
			}

			$this->render_upload_field_html();
		}

		/**
		 * Check if upload field should be displayed
		 *
		 * @param WC_Product $product Product object.
		 * @return bool
		 */
		private function should_display_upload_field( $product ) {
			if ( ! $product ) {
				return false;
			}

			if ( 'yes' !== get_post_meta( $product->get_id(), '_wc_sc_enable_custom_gift_image', true ) ) {
				return false;
			}

			// Check if product has store credit coupons.
			$coupon_titles = get_post_meta( $product->get_id(), '_coupon_title', true );
			return ! empty( $coupon_titles );
		}

		/**
		 * Render the upload field HTML
		 */
		private function render_upload_field_html() {

			global $store_credit_label;

			$singular_store_credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );

			?>
			<div class="wc-sc-gift-card-image-upload">
				<label for="wc_sc_gift_image_file">
					<?php
					echo sprintf(
						/* translators: %s: Label for gift card */
						esc_html_x(
							'Add image to %s',
							'label on product page for uploading gift image',
							'woocommerce-smart-coupons'
						),
						esc_html( $singular_store_credit_label )
					);
					?>
				</label>

				<div class="wc-sc-upload-controls">
					<input type="file"
						id="wc_sc_gift_image_file"
						name="wc_sc_gift_image_file"
						accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
						style="display:none;" />

					<button type="button" class="button wc-sc-upload-image-button" id="wc_sc_gift_image_button">
						<?php esc_html_e( 'Choose Image', 'woocommerce-smart-coupons' ); ?>
					</button>

					<small class="description">
						<?php
						printf(
							/* translators: %s: Maximum file size */
							esc_html__( 'Maximum file size: %s. Allowed formats: JPG, PNG, GIF, WebP', 'woocommerce-smart-coupons' ),
							esc_html( size_format( $this->max_file_size ) )
						);
						?>
					</small>
				</div>

				<div class="wc-sc-gift-image-preview" style="display:none; position: relative;">
					<button type="button" class="wc-sc-remove-image" aria-label="<?php esc_attr_e( 'Remove Image', 'woocommerce-smart-coupons' ); ?>">×</button>
					<img src="" alt="<?php esc_attr_e( 'Selected gift certificate image', 'woocommerce-smart-coupons' ); ?>" />
				</div>

				<input type="hidden" name="wc_sc_gift_image_token" id="wc_sc_gift_image_token" value="" />
				<input type="hidden" name="wc_sc_gift_image_filename" id="wc_sc_gift_image_filename" value="" />
			</div>
			<?php
		}

		/**
		 * Validate upload on add to cart
		 *
		 * @param bool $passed Validation status.
		 * @param int  $product_id Product ID.
		 * @param int  $quantity Quantity.
		 * @return bool
		 */
		public function validate_upload( $passed, $product_id, $quantity ) {
			if ( ! $passed ) {
				return $passed;
			}

			if ( 'yes' !== get_post_meta( $product_id, '_wc_sc_enable_custom_gift_image', true ) ) {
				return $passed;
			}

			$token = isset( $_POST['wc_sc_gift_image_token'] ) ? wp_unslash( $_POST['wc_sc_gift_image_token'] ) : ''; // phpcs:ignore
			// Image upload is optional, so only validate if provided.
			if ( ! empty( $token ) ) {
				$token = sanitize_text_field( $token );

				if ( ! $this->validate_uploaded_image_token( $token ) ) {
					wc_add_notice( __( 'Invalid image selected. Please select a valid image.', 'woocommerce-smart-coupons' ), 'error' );
					return false;
				}
			}

			return $passed;
		}

		/**
		 * Validate uploaded image token
		 *
		 * @param string $token Upload token.
		 * @return bool
		 */
		private function validate_uploaded_image_token( $token ) {
			if ( empty( $token ) ) {
				return false;
			}

			// Check if token exists in transient (temporary storage).
			$upload_data = get_transient( 'wc_sc_upload_' . $token );
			if ( ! $upload_data ) {
				return false;
			}

			// Validate file still exists.
			$file_path = $this->upload_dir . $upload_data['filename'];
			if ( ! file_exists( $file_path ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Display custom image in cart
		 *
		 * @param array $item_data Item data.
		 * @param array $cart_item Cart item.
		 * @return array
		 */
		public function display_cart_item_data( $item_data, $cart_item ) {
			$is_cart_block_default     = is_callable( array( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_cart_block_default' ) ) ? CartCheckoutUtils::is_cart_block_default() : false;
			$is_checkout_block_default = is_callable( array( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_checkout_block_default' ) ) ? CartCheckoutUtils::is_checkout_block_default() : false;

			if ( $is_cart_block_default || $is_checkout_block_default ) {
				return $item_data;
			}

			global $store_credit_label;

			$singular_store_credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );

			if ( isset( $cart_item['wc_sc_custom_gift_image'] ) ) {
				try {
					$image_url   = $this->get_image_url( $cart_item['wc_sc_custom_gift_image']['filename'] );
					$item_data[] = array(
						/* translators: %s: Label for gift card */
						'key'     => sprintf( __( '%s Image', 'woocommerce-smart-coupons' ), $singular_store_credit_label ),
						'value'   => $this->get_image_preview_html( $image_url, 50 ),
						'display' => '',
					);
				} catch ( \Exception $e ) {
					// Log error and skip image display.
					$this->log( 'error', 'Error fetching gift image: ' . $e->getMessage() ); // phpcs:ignore
				}
			}

			return $item_data;
		}

		/**
		 * Modify email coupon design to include a custom image
		 *
		 * @param array $args Email design args.
		 * @param array $coupon_data Coupon data.
		 * @return array
		 */
		public function modify_email_coupon_design( $args, $coupon_data ) {
			// Check if this product has gift certificate image support enabled.
			$has_image_support = $this->coupon_has_image_support( $coupon_data['coupon_code'] );

			if ( ! $has_image_support ) {
				return $args;
			}

			$custom_image_url = '';

			// Check if this coupon is from an order with a custom image.
			if ( ! empty( $coupon_data['order_id'] ) ) {
				$custom_image = $this->get_custom_image_for_coupon( $coupon_data['order_id'], $coupon_data['coupon_code'] );
				if ( $custom_image ) {
					$custom_image_url = $this->get_image_url( $custom_image );
				}
			}

			// If no custom image, use fallback image when image support is enabled.
			if ( empty( $custom_image_url ) ) {
				$custom_image_url = $this->get_fallback_image_url();
			}

			// Set the image URL and design.
			$args['custom_image_url'] = $custom_image_url;
			$args['design']           = 'custom-upload';

			return $args;
		}


		/**
		 * Get custom image filename for a coupon
		 *
		 * @param int    $order_id Order ID.
		 * @param string $coupon_code Coupon code.
		 * @param int    $coupon_parent Parent product ID that generated the coupon.
		 * @return string|false
		 */
		private function get_custom_image_for_coupon( $order_id, $coupon_code, $coupon_parent = null ) {

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return false;
			}

			// If no parent provided, try to get it from coupon meta.
			if ( ! $coupon_parent ) {
				$coupon        = new WC_Coupon( $coupon_code );
				$coupon_parent = get_post_meta( $coupon->get_id(), 'coupon_generated_by_product_id', true );
			}

			foreach ( $order->get_items() as $item_id => $item ) {
				$filename = $item->get_meta( '_wc_sc_custom_gift_image_filename' );

				if ( $filename ) {
					$product_id   = $item->get_product_id();
					$variation_id = $item->get_variation_id();

					// Check if this item's product matches the coupon parent.
					// Handle both regular products and variable products.
					$is_match = false;

					if ( $product_id === $coupon_parent ) {
						$is_match = true;
					} elseif ( $variation_id && $variation_id === $coupon_parent ) {
						$is_match = true;
					} else {
						// Check parent-child relationships.
						$product_parent       = wp_get_post_parent_id( $product_id );
						$coupon_parent_parent = wp_get_post_parent_id( $coupon_parent );

						if ( $coupon_parent && $product_parent === $coupon_parent ) {
							$is_match = true;
						} elseif ( $coupon_parent && $coupon_parent_parent === $product_id ) {
							$is_match = true;
						}
					}

					if ( $is_match ) {
						return $filename;
					}
				}
			}

			// Fallback: If no exact match found but there's a custom image in the order, use it.
			foreach ( $order->get_items() as $item_id => $item ) {
				$filename = $item->get_meta( '_wc_sc_custom_gift_image_filename' );
				if ( $filename ) {
						return $filename;
				}
			}

			return false;
		}

		/**
		 * Get image preview HTML
		 *
		 * @param string $url  Image URL.
		 * @param int    $size Max size in pixels.
		 * @return string
		 */
		private function get_image_preview_html( $url, $size = 100 ) {
			global $store_credit_label;

			$singular_store_credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );
			return sprintf(
				/* translators: 1. Image URL, 2. Image URL, 3. Alternative name for image, 4. Size of the image */
				'<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="%s" style="max-width: %dpx; border: 1px solid #ddd; cursor: pointer;" /></a>',
				esc_url( $url ),
				esc_url( $url ),
				/* translators: %s: Label for gift card */
				sprintf( esc_attr__( '%s image', 'woocommerce-smart-coupons' ), $singular_store_credit_label ),
				$size
			);
		}

		/**
		 * Enqueue script for the Gift card Upload.
		 */
		public function enqueue_frontend_scripts() {
			if ( ! is_product() ) {
				return;
			}

			global $post;
			if ( 'yes' !== get_post_meta( $post->ID, '_wc_sc_enable_custom_gift_image', true ) ) {
				return;
			}

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script(
				'wc-sc-gift-card-image',
				plugins_url( 'assets/js/gift-card-image' . $suffix . '.js', WC_SC_PLUGIN_FILE ),
				array( 'jquery' ),
				'1.0.0',
				true
			);

			wp_localize_script(
				'wc-sc-gift-card-image',
				'wc_sc_custom_gift',
				array(
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'upload_nonce'  => wp_create_nonce( 'wc_sc_upload_nonce' ),
					'max_size'      => $this->max_file_size,
					'allowed_types' => $this->allowed_types,
					'messages'      => array(
						'select_image' => __( 'Select Gift Certificate Image', 'woocommerce-smart-coupons' ),
						'use_image'    => __( 'Use this image', 'woocommerce-smart-coupons' ),
						'error_size'   => __( 'File size exceeds maximum allowed size.', 'woocommerce-smart-coupons' ),
						'error_type'   => __( 'Invalid file type. Please upload an image (JPG, PNG, GIF, or WebP).', 'woocommerce-smart-coupons' ),
						'uploading'    => __( 'Uploading...', 'woocommerce-smart-coupons' ),
					),
				)
			);

			wp_register_style( 'wc-sc-gift-card-image', false, array(), $this->get_version() );
			wp_enqueue_style( 'wc-sc-gift-card-image' );

			// Add inline styles.
			$this->add_inline_styles();
		}

		/**
		 * Add inline styles for the upload field
		 */
		private function add_inline_styles() {
			$custom_css = '
				.wc-sc-gift-card-image-upload {
					margin-bottom: 20px;
					padding: 15px;
					background: #f9f9f9;
					border: 1px solid #e0e0e0;
					border-radius: 4px;
				}
				.wc-sc-gift-card-image-upload label {
					display: block;
					margin-bottom: 10px;
					font-weight: 600;
					color: #333;
				}
				.wc-sc-upload-controls {
					margin-bottom: 10px;
				}
				.wc-sc-upload-controls .button {
					margin-right: 10px;
					padding: 5px 16px;
					font-size: 14px;
				}
				.wc-sc-upload-controls .description {
					display: inline-block;
					color: #666;
					font-size: 13px;
					margin-left: 10px;
				}
				.wc-sc-gift-image-preview {
					position: relative;
					margin-top: 15px;
					padding: 10px;
					background: white;
					border: 1px solid #ddd;
					border-radius: 4px;
					text-align: center;
				}
				.wc-sc-gift-image-preview img {
					display: block;
					margin: 0 auto;
					max-width: 200px;
					max-height: 200px;
					border: 1px solid #e0e0e0;
					border-radius: 4px;
					padding: 5px;
					background: white;
				}
				.wc-sc-gift-image-preview .wc-sc-remove-image {
					position: absolute;
					top: 5px;
					right: 5px;
					background: #9f9f9f;
					color: #fff;
					border: none;
					border-radius: 50%;
					width: 24px;
					height: 24px;
					line-height: 22px;
					text-align: center;
					font-size: 16px;
					font-weight: bold;
					cursor: pointer;
					padding: 0;
				}
				.wc-sc-gift-image-preview .wc-sc-remove-image:hover {
					background: #c82333;
				}
				.wc-sc-custom-gift-image-display img {
					border-radius: 4px;
					box-shadow: 0 2px 4px rgba(0,0,0,0.1);
				}
			';

			wp_add_inline_style( 'wc-sc-gift-card-image', $custom_css );
		}

		/**
		 * Handle AJAX image upload using WordPress upload functions
		 */
		public function handle_ajax_upload() {
			// Security check.
			if ( ! check_ajax_referer( 'wc_sc_upload_nonce', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'woocommerce-smart-coupons' ) ) );
			}

			$gift_image = wp_unslash( $_FILES['gift_image'] ); // phpcs:ignore
			if ( ! isset( $gift_image ) ) {
				wp_send_json_error( array( 'message' => __( 'No file uploaded', 'woocommerce-smart-coupons' ) ) );
			}

			$file = $gift_image;

			// Validate file type.
			$file_type = wp_check_filetype( $file['name'] );
			if ( ! in_array( $file_type['type'], $this->allowed_types, true ) ) {
				wp_send_json_error(
					array( 'message' => __( 'Invalid file type. Please upload an image file.', 'woocommerce-smart-coupons' ) )
				);
			}

			// Validate file size.
			if ( $file['size'] > $this->max_file_size ) {
				wp_send_json_error(
					array(
						'message' => sprintf(
							/* translators: %s: Maximum file size */
							__( 'File size exceeds maximum allowed size of %s.', 'woocommerce-smart-coupons' ),
							size_format( $this->max_file_size )
						),
					)
				);
			}

			// Generate a unique filename for temporary storage.
			$extension     = pathinfo( $file['name'], PATHINFO_EXTENSION );
			$temp_filename = 'temp_' . wp_generate_uuid4() . '.' . $extension;
			$temp_path     = $this->upload_dir . $temp_filename;

			// Move uploaded file to our directory.
			if ( ! move_uploaded_file( $file['tmp_name'], $temp_path ) ) {
				wp_send_json_error( array( 'message' => __( 'Failed to save uploaded file.', 'woocommerce-smart-coupons' ) ) );
			}

			// Generate token for this upload.
			$token = wp_generate_uuid4();

			// Store upload data in transient (expires in 1 hour).
			set_transient(
				'wc_sc_upload_' . $token,
				array(
					'filename'      => $temp_filename,
					'original_name' => $file['name'],
					'uploaded_at'   => time(),
				),
				HOUR_IN_SECONDS
			);

			// Return success with preview URL.
			wp_send_json_success(
				array(
					'token'       => $token,
					'filename'    => $file['name'],
					'preview_url' => $this->get_image_url( $temp_filename ),
				)
			);
		}

		/**
		 * Save custom image to order item meta
		 *
		 * @param WC_Order_Item $item Order item.
		 * @param string        $cart_item_key Cart item key.
		 * @param array         $values Cart item values.
		 * @param WC_Order      $order Order object.
		 */
		public function save_order_item_meta( $item, $cart_item_key, $values, $order ) {
			if ( isset( $values['wc_sc_custom_gift_image'] ) ) {
				$filename      = $values['wc_sc_custom_gift_image']['filename'];
				$original_name = $values['wc_sc_custom_gift_image']['original_name'];

				// Store image reference in order meta.
				$item->add_meta_data( '_wc_sc_custom_gift_image_filename', $filename );
				$item->add_meta_data( '_wc_sc_custom_gift_image_original', $original_name );
			}
		}

		/**
		 * Display a custom image in the order details
		 *
		 * @param int           $item_id Item ID.
		 * @param WC_Order_Item $item Order item.
		 * @param WC_Order      $order Order object.
		 * @param bool          $plain_text Plain text.
		 */
		public function display_order_item_meta( $item_id, $item, $order, $plain_text = false ) {

			$filename = $item->get_meta( '_wc_sc_custom_gift_image_filename' );

			$allowed_tags = array(
				'img' => array(
					'src'    => true,
					'alt'    => true,
					'style'  => true,
					'width'  => true,
					'height' => true,
				),
			);

			global $store_credit_label;

			$singular_store_credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );

			if ( $filename && ! $plain_text ) {
				$image_url = $this->get_image_url( $filename );
				echo '<p class="wc-sc-custom-gift-image-display">';
				/* translators: %s: Label for gift card */
				echo '<strong>' . sprintf( esc_html__( '%s Image:', 'woocommerce-smart-coupons' ), esc_html( $singular_store_credit_label ) ) . '</strong><br/>';
				echo wp_kses(
					$this->get_image_preview_html( $image_url, 100 ),
					$allowed_tags
				);
				echo '</p>';
			}

		}

		/**
		 * Handle AJAX image removal
		 */
		public function handle_ajax_remove() {
			// Security check.
			if ( ! check_ajax_referer( 'wc_sc_upload_nonce', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed', 'woocommerce-smart-coupons' ) ) );
			}

			$token = sanitize_text_field( wp_unslash( $_POST['token'] ) ); // phpcs:ignore

			if ( ! isset( $token ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request', 'woocommerce-smart-coupons' ) ) );
			}

			// Get upload data from transient.
			$upload_data = get_transient( 'wc_sc_upload_' . $token );
			if ( $upload_data ) {
				// Delete the temporary file.
				$file_path = $this->upload_dir . $upload_data['filename'];
				if ( file_exists( $file_path ) ) {
					$this->safe_delete_file( $upload_data['filename'], $this->upload_dir ); // phpcs:ignore
				}

				// Delete transient.
				delete_transient( 'wc_sc_upload_' . $token );
			}

			wp_send_json_success();
		}

		/**
		 * Get public image URL
		 *
		 * @param string $filename Image filename.
		 * @return string
		 */
		private function get_image_url( $filename ) {
			if ( empty( $filename ) ) {
				return '';
			}
			return $this->upload_url . $filename;
		}

		/**
		 * Get fallback image URL for gift certificates
		 *
		 * @return string Fallback image URL
		 */
		public function get_fallback_image_url() {
			/**
			 * Filter: Allow customization of the fallback gift certificate image
			 *
			 * @param string $fallback_url Default fallback image URL
			 */
			$fallback_url = apply_filters(
				'wc_sc_gift_certificate_fallback_image_url',
				WC_SC_PLUGIN_URL . 'assets/images/giftcard/gift-card.jpg'
			);

			return $fallback_url;
		}

		/**
		 * Customizes the display key for the gift image meta in order details.
		 *
		 * @param string       $display_key Display name of the meta key.
		 * @param WC_Meta_Data $meta        Meta data object.
		 *
		 * @return string Modified display key for the gift message meta.
		 */
		public function remove_meta_from_order_display( $display_key, $meta ) {
			// Hide specific meta keys.
			if ( '_wc_sc_custom_gift_image_filename' === $meta->key ) {
				return __( 'Gift Image', 'woocommerce-smart-coupons' );
			}
			return $display_key;
		}

		/**
		 * Check if a coupon has gift certificate image support enabled
		 *
		 * @param string $coupon_code Coupon code to check.
		 * @return bool True if image support is enabled, false otherwise
		 */
		public function coupon_has_image_support( $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );

			// First check if the coupon has a parent product with image support enabled.
			$coupon_parent = get_post_meta( $coupon->get_id(), 'coupon_generated_by_product_id', true );
			if ( $coupon_parent ) {
				$image_support = get_post_meta( $coupon_parent, '_wc_sc_enable_custom_gift_image', true );
				if ( 'yes' === $image_support ) {
					return true;
				}
			}

			// If coupon doesn't have parent product with image support enabled, return false.
			return false;
		}

		/**
		 * Coupon fields for variation
		 *
		 * @param int     $loop           Position in the loop.
		 * @param array   $variation_data Variation data.
		 * @param WP_Post $variation Post data.
		 */
		public function woocommerce_product_gift_certificate_variable( $loop = 0, $variation_data = array(), $variation = null ) {
			if ( ! $variation || ! isset( $variation->ID ) ) {
				return;
			}

			$variation_id = $variation->ID;
			try {

				global $store_credit_label;

				$singular_store_credit_label = ! empty( $store_credit_label['singular'] ) ? ucwords( $store_credit_label['singular'] ) : __( 'Store Credit', 'woocommerce-smart-coupons' );

				$product = ( ! empty( $variation_id ) && function_exists( 'wc_get_product' ) ) ? wc_get_product( $variation_id ) : null;
				if ( ! $product ) {
					return;
				}

				$is_callable_product_get_meta = $this->is_callable( $product, 'get_meta' );

				$enable_custom_gift_image = ( true === $is_callable_product_get_meta ) ? $product->get_meta( '_wc_sc_enable_custom_gift_image' ) : get_post_meta( $variation_id, '_wc_sc_enable_custom_gift_image', true );

				?>
				<div class="smart_coupons_product_options_variable smart-coupons-field">
					<!-- Gift card image Option -->
					<p class="form-field enable_custom_gift_image_field post_<?php echo esc_attr( $variation_id ); ?> form-row form-row-full">
						<label class="tips" data-tip="<?php echo esc_attr__( 'Enable to allow users to upload custom images for this gift certificate variation', 'woocommerce-smart-coupons' ); ?>">
							<?php echo esc_html__( 'Image upload', 'woocommerce-smart-coupons' ); ?>
							<input type="checkbox"
								class="checkbox wc-sc-variation-gift-image"
								id="_wc_sc_enable_custom_gift_image_<?php echo esc_attr( $variation_id ); ?>"
								name="_wc_sc_enable_custom_gift_image[<?php echo esc_attr( $variation_id ); ?>][<?php echo esc_attr( $loop ); ?>]"
								style="margin: 0.1em 0.5em 0.1em 0 !important;"
								value="yes"
								<?php checked( $enable_custom_gift_image, 'yes' ); ?> />
							<small class="description" style="display: block; margin-top: 5px; color: #666;">
								<?php /* translators: %s: Label for gift card */ ?>
								<?php echo sprintf( esc_html__( 'Enable to allow users to upload image that will be sent along with %s email.', 'woocommerce-smart-coupons' ), esc_html( $singular_store_credit_label ) ); ?>
							</small>
						</label>
					</p>
				</div>
				<?php
			} catch ( \Throwable $e ) {
				$this->log( 'error', 'Error in woocommerce_product_gift_certificate_variable: ' . $e->getMessage() ); // phpcs:ignore
			}
		}

		/**
		 * Save variation gift certificate option
		 *
		 * @param int $variation_id Variation ID.
		 * @param int $i Loop index.
		 */
		public function woocommerce_process_product_meta_gift_certificate_variable( $variation_id = 0, $i = 0 ) {
			try {
				if ( empty( $variation_id ) ) {
					return;
				}

				$variation_id = absint( $variation_id );
				$variation    = ( ! empty( $variation_id ) ) ? wc_get_product( $variation_id ) : null;
				$is_callable  = $this->is_callable( $variation, 'update_meta_data' );

				// Check if checkbox is set for this variation.
				$enable_custom_gift_image = ( isset( $_POST['_wc_sc_enable_custom_gift_image'][ $variation_id ][ $i ] ) ) ? 'yes' : 'no'; // phpcs:ignore

				if ( true === $is_callable ) {
					$variation->update_meta_data( '_wc_sc_enable_custom_gift_image', $enable_custom_gift_image );
				} else {
					update_post_meta( $variation_id, '_wc_sc_enable_custom_gift_image', $enable_custom_gift_image );
				}

				if ( $this->is_callable( $variation, 'save' ) ) {
					$variation->save();
				}
			} catch ( \Throwable $e ) {
				$this->log( 'error', 'Error in woocommerce_process_product_meta_gift_certificate_variable: ' . $e->getMessage() ); // phpcs:ignore
			}
		}

		/**
		 * Converts gift image filename to an HTML image tag for display in order details.
		 *
		 * @param string        $display_value Display value of the meta (filename).
		 * @param WC_Meta_Data  $meta Meta data object.
		 * @param WC_Order_Item $order_item Order item object.
		 *
		 * @return string Modified display value with image tag.
		 */
		public function display_gift_image_as_img_tag( $display_value, $meta, $order_item ) {
			// Check if this is our gift image meta.
			if ( '_wc_sc_custom_gift_image_filename' === $meta->key ) {
				$filename = $display_value;

				// Check if we have a valid filename.
				if ( ! empty( $filename ) && is_string( $filename ) ) {
					$image_url = $this->upload_url . $filename;

					// Check if the image file actually exists.
					$image_path = $this->upload_dir . $filename;

					if ( file_exists( $image_path ) ) {
						// Create clickable image tag with link to open in new tab.
						$image_tag = sprintf(
							'<a href="%s" target="_blank" rel="noopener noreferrer"><img src="%s" alt="%s" style="max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" /></a>',
							esc_url( $image_url ),
							esc_url( $image_url ),
							esc_attr__( 'Gift Image', 'woocommerce-smart-coupons' )
						);
						return $image_tag;
					} else {
						// If file doesn't exist, show a placeholder or error message.
						return __( 'Gift Image (file not found)', 'woocommerce-smart-coupons' );
					}
				}
			}

			return $display_value;
		}

		/**
		 * Hide specific meta keys completely from order item meta display.
		 *
		 * @param array $hidden_meta Array of meta keys to hide.
		 *
		 * @return array Modified array with additional meta keys to hide.
		 */
		public function hide_gift_image_meta_keys( $hidden_meta ) {
			// Add our meta keys to the hidden list.
			$hidden_meta[] = '_wc_sc_custom_gift_image_original';

			return $hidden_meta;
		}

		/**
		 * Securely delete a file inside WordPress using WP_Filesystem.
		 *
		 * @param string $file   Relative or absolute file path.
		 * @param string $base_dir Base directory where deletions are allowed.
		 * @return bool          True if deleted, false otherwise.
		 */
		public function safe_delete_file( $file, $base_dir ) {
			try {
				global $wp_filesystem;

				// Initialize WP_Filesystem if not already.
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}
				WP_Filesystem();

				if ( ! $wp_filesystem ) {
					$this->log( 'error', 'wp_safe_delete_file: WP_Filesystem not initialized' );
					return false;
				}

				// Normalize paths.
				$base_dir = wp_normalize_path( realpath( $base_dir ) );
				$target   = wp_normalize_path( realpath( $base_dir . '/' . $file ) );

				// Ensure the resolved path is valid and inside base directory.
				if ( ! $target || strpos( $target, $base_dir ) !== 0 ) {
					$this->log( 'error', 'wp_safe_delete_file: Invalid path ' . $file );
					return false;
				}

				// File must exist and be a file.
				if ( ! $wp_filesystem->exists( $target ) ) {
					$this->log( 'error', 'wp_safe_delete_file: File not found ' . $target );
					return false;
				}

				// Check MIME type for sanity (only allow safe types).
				$mime          = mime_content_type( $target );
				$allowed_mimes = $this->allowed_types;

				if ( ! in_array( $mime, $allowed_mimes, true ) ) {
					$this->log( 'error', 'wp_safe_delete_file: MIME type ' . $mime . ' not allowed for ' . $target );
					return false;
				}

				// Attempt deletion.
				return $wp_filesystem->delete( $target, false, 'f' );
			} catch ( \Throwable $e ) {
				$this->log( 'error', 'Error in safe_delete_file: ' . $e->getMessage() ); // phpcs:ignore
			}
			return false;
		}

	}
}

// Initialize the class.
WC_SC_Gift_Card_Image::get_instance();
