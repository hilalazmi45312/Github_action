<?php
/**
 * Product Fee Settings.
 *
 * @package Extra Fees for WooCommerce/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Product_Fee_Settings' ) ) {

	/**
	 * Class EFW_Product_Fee_Settings.
	 */
	class EFW_Product_Fee_Settings {

		/**
		 * Class Initialization.
		 */
		public static function init() {
			// Add Fee Settings for Simple Product.
			add_filter( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'simple_product_settings' ), 10 );
			// Save Fee Settings for Simple Product.
			add_action( 'woocommerce_process_product_meta_simple', array( __CLASS__, 'save_simple_product_settings' ), 10 );
			// Save Fee Settings for Simple Subscription Product.
			add_action( 'woocommerce_process_product_meta_subscription', array( __CLASS__, 'save_simple_product_settings' ), 10 );
			// Save Fee Settings for Simple Booking Product.
			add_action( 'woocommerce_process_product_meta_booking', array( __CLASS__, 'save_simple_product_settings' ), 10 );
			// Save Fee Settings for Simple Accommodation Booking Product.
			add_action( 'woocommerce_process_product_meta_accommodation-booking', array( __CLASS__, 'save_simple_product_settings' ), 10 );
			// Add Fee Settings for Variable Product.
			add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'variable_product_settings' ), 10, 3 );
			// Save Fee Settings for Variable Product.
			add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variable_product_settings' ), 10, 2 );
			// Add Fee Settings in Add New Category Page
			add_action( 'product_cat_add_form_fields', array( __CLASS__, 'new_category_settings' ) );
			// Add Fee Settings in Edit Category Page
			add_action( 'product_cat_edit_form_fields', array( __CLASS__, 'edit_category_settings' ), 10, 2 );
			// Save Fee Settings in Add New Category Page
			add_action( 'created_term', array( __CLASS__, 'save_category_settings' ), 10, 3 );
			// Save Fee Settings in Add New Category Page
			add_action( 'edit_term', array( __CLASS__, 'save_category_settings' ), 10, 3 );
			// Add error message after validation.
			add_action( 'admin_notices', array( __CLASS__, 'add_error' ) );
		}

		/**
		 * Product Fee Settings for Simple Product.
		 */
		public static function simple_product_settings() {
			if ( ! is_admin() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
				return;
			}

			global $post;

			$args = array(
				'post_parent' => $post->ID,
			);

			$rule_ids = efw_get_fee_rule_ids( $args );

			$enable          = get_post_meta( $post->ID, '_efw_enable_fee', true );
			$fee_from   = get_post_meta( $post->ID, '_efw_fee_from', true );
			$fee_text_from   = get_post_meta( $post->ID, '_efw_text_from', true );
			$fee_text        = get_post_meta( $post->ID, '_efw_fee_text', true );
			$fee_description = get_post_meta( $post->ID, '_efw_fee_description', true );
			$fee_type        = get_post_meta( $post->ID, '_efw_fee_type', true );
			$fixed_value     = get_post_meta( $post->ID, '_efw_fixed_value', true );
			$percent_value   = get_post_meta( $post->ID, '_efw_percent_value', true );

			include EFW_ABSPATH . 'inc/admin/menu/views/simple/simple-product-fee-settings.php';
		}

		/**
		 * Save Simple Product Settings.
		 *
		 * @param int $post_id Post ID.
		 */
		public static function save_simple_product_settings( $post_id ) {
			if ( ! is_admin() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
				return;
			}

			$update = self::validate_fields_for_simple( true, $_REQUEST );

			if ( ! $update ) {
				return;
			}

			if ( isset( $_REQUEST['efw_enable_fee'] ) ) {
				update_post_meta( $post_id, '_efw_enable_fee', 'yes' );
			} else {
				update_post_meta( $post_id, '_efw_enable_fee', 'no' );
			}

			if ( isset( $_REQUEST['efw_fee_from'] ) ) {
				$fee_from = filter_input( INPUT_POST, 'efw_fee_from' );
				update_post_meta( $post_id, '_efw_fee_from', $fee_from );
			}

			if ( isset( $_REQUEST['efw_text_from'] ) ) {
				$fee_text_from = filter_input( INPUT_POST, 'efw_text_from' );
				update_post_meta( $post_id, '_efw_text_from', $fee_text_from );
			}

			if ( isset( $_REQUEST['efw_fee_text'] ) ) {
				$fee_text = filter_input( INPUT_POST, 'efw_fee_text' );
				update_post_meta( $post_id, '_efw_fee_text', $fee_text );
			}

			if ( isset( $_REQUEST['efw_fee_description'] ) ) {
				$fee_description = filter_input( INPUT_POST, 'efw_fee_description' );
				update_post_meta( $post_id, '_efw_fee_description', $fee_description );
			}

			if ( isset( $_REQUEST['efw_fee_type'] ) ) {
				$fee_type = filter_input( INPUT_POST, 'efw_fee_type' );
				update_post_meta( $post_id, '_efw_fee_type', $fee_type );
			}

			if ( isset( $_REQUEST['efw_fixed_value'] ) ) {
				$fixed_value = filter_input( INPUT_POST, 'efw_fixed_value' );
				update_post_meta( $post_id, '_efw_fixed_value', $fixed_value );
			}

			if ( isset( $_REQUEST['efw_percent_value'] ) ) {
				$percent_value = filter_input( INPUT_POST, 'efw_percent_value' );
				update_post_meta( $post_id, '_efw_percent_value', $percent_value );
			}

			if ( isset( $_REQUEST['efw_product_fees'] ) ) {

				$product_fees = wc_clean( wp_unslash( $_REQUEST['efw_product_fees'] ) );

				foreach ( $product_fees as $rule_id => $rules ) {
					if ( 'new' === $rule_id ) {

						$rule_post_args = array(
							'post_parent' => $post_id,
						);

						foreach ( $rules as $rule ) {
							$error[ $rule_id ][] = efw_get_error_msg_for_product( $rule );

							if ( ! efw_check_is_array( array_filter( $error[ $rule_id ] ) ) ) {
								$rule['efw_settings_level'] = 'product';
								efw_create_new_fee_rule( $rule, $rule_post_args );
							}
						}
					} else {
						$error[ $rule_id ][] = efw_get_error_msg_for_product( $rules );

						if ( ! efw_check_is_array( array_filter( $error[ $rule_id ] ) ) ) {
							efw_update_fee_rule( $rule_id, $rules );
						}
					}
				}

				set_transient( 'efw_rule_errors', $error, 45 );
			}
		}

		/**
		 * Validate Fields for Simple before save.
		 */
		public static function validate_fields_for_simple( $update, $fields ) {
			if ( isset( $fields['efw_enable_fee'] ) ) {
				if ( isset( $fields['efw_text_from'] ) && ( '2' === $fields['efw_text_from'] ) && empty( $fields['efw_fee_text'] ) ) {
					WC_Admin_Meta_Boxes::add_error( esc_html__( 'Fee Text cannot be empty', 'extra-fees-for-woocommerce' ) );
					$update = false;
				}

				if ( isset( $fields['efw_fee_type'] ) ) {
					if ( '1' === $fields['efw_fee_type'] ) {
						if ( empty( $fields['efw_fixed_value'] ) ) {
							WC_Admin_Meta_Boxes::add_error( esc_html__( 'Fixed Fee Value cannot be empty', 'extra-fees-for-woocommerce' ) );
							$update = false;
						}
					}

					if ( '2' === $fields['efw_fee_type'] ) {
						if ( empty( $fields['efw_percent_value'] ) ) {
							WC_Admin_Meta_Boxes::add_error( esc_html__( 'Fee Value in Percent cannot be empty', 'extra-fees-for-woocommerce' ) );
							$update = false;
						}
					}
				}
			}

			return $update;
		}

		/**
		 * Product Fee Settings for Variable Product.
		 */
		public static function variable_product_settings( $loop, $variation_data, $variations ) {
			if ( ! is_admin() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
				return;
			}

			$product_id = $variations->ID;

			$args = array(
				'post_parent' => $product_id,
			);

			$rule_ids = efw_get_fee_rule_ids( $args );

			$enable          = get_post_meta( $product_id, '_efw_enable_fee', true );
			$fee_from   = get_post_meta( $product_id, '_efw_fee_from', true );
			$fee_text_from   = get_post_meta( $product_id, '_efw_text_from', true );
			$fee_text        = get_post_meta( $product_id, '_efw_fee_text', true );
			$fee_description = get_post_meta( $product_id, '_efw_fee_description', true );
			$fee_type        = get_post_meta( $product_id, '_efw_fee_type', true );
			$fixed_value     = get_post_meta( $product_id, '_efw_fixed_value', true );
			$percent_value   = get_post_meta( $product_id, '_efw_percent_value', true );

			include EFW_ABSPATH . 'inc/admin/menu/views/variable/variable-product-fee-settings.php';
		}

		/**
		 * Save Variable Product Settings.
		 */
		public static function save_variable_product_settings( $variation_id, $i ) {
			if ( ! is_admin() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
				return;
			}

			$update = self::validate_fields_for_variable( true, $_REQUEST, $i, $variation_id );

			if ( ! $update ) {
				return;
			}

			if ( isset( $_REQUEST['efw_enable_fee'][ $i ] ) ) {
				update_post_meta( $variation_id, '_efw_enable_fee', 'yes' );
			} else {
				update_post_meta( $variation_id, '_efw_enable_fee', 'no' );
			}

			if ( isset( $_REQUEST['efw_fee_from'][ $i ] ) ) {
				$fee_from = wc_clean( wp_unslash( $_REQUEST['efw_fee_from'][ $i ] ) );
				update_post_meta( $variation_id, '_efw_fee_from', $fee_from );
			}

			if ( isset( $_REQUEST['efw_text_from'][ $i ] ) ) {
				$fee_text_from = wc_clean( wp_unslash( $_REQUEST['efw_text_from'][ $i ] ) );
				update_post_meta( $variation_id, '_efw_text_from', $fee_text_from );
			}

			if ( isset( $_REQUEST['efw_fee_text'][ $i ] ) ) {
				$fee_text = wc_clean( wp_unslash( $_REQUEST['efw_fee_text'][ $i ] ) );
				update_post_meta( $variation_id, '_efw_fee_text', $fee_text );
			}

			if ( isset( $_REQUEST['efw_fee_description'][ $i ] ) ) {
					$request     = $_REQUEST;
				$fee_description = wp_unslash( $request['efw_fee_description'][ $i ] );
				update_post_meta( $variation_id, '_efw_fee_description', $fee_description );
			}

			if ( isset( $_REQUEST['efw_fee_type'][ $i ] ) ) {
				$fee_type = wc_clean( wp_unslash( $_REQUEST['efw_fee_type'][ $i ] ) );
				update_post_meta( $variation_id, '_efw_fee_type', $fee_type );
			}

			if ( isset( $_REQUEST['efw_fixed_value'][ $i ] ) ) {
				$fixed_value = wc_clean( wp_unslash( $_REQUEST['efw_fixed_value'][ $i ] ) );
				update_post_meta( $variation_id, '_efw_fixed_value', $fixed_value );
			}

			if ( isset( $_REQUEST['efw_percent_value'][ $i ] ) ) {
				$percent_value = wc_clean( wp_unslash( $_REQUEST['efw_percent_value'][ $i ] ) );
				update_post_meta( $variation_id, '_efw_percent_value', $percent_value );
			}

			if ( isset( $_REQUEST['efw_product_fees'][ $i ] ) ) {

				$product_fees = wc_clean( wp_unslash( $_REQUEST['efw_product_fees'][ $i ] ) );

				$rule_post_args = array(
					'post_parent' => $variation_id,
				);

				foreach ( $product_fees as $rule_id => $rules ) {
					if ( 'new' == $rule_id ) {

						foreach ( $rules as $rule ) {
							$error[ $rule_id ][] = efw_get_error_msg_for_product( $rule );

							if ( ! efw_check_is_array( array_filter( $error[ $rule_id ] ) ) ) {
								$rule['efw_settings_level'] = 'product';
								efw_create_new_fee_rule( $rule, $rule_post_args );
							}
						}
					} else {
						$error[ $rule_id ][] = efw_get_error_msg_for_product( $rules );

						if ( ! efw_check_is_array( array_filter( $error[ $rule_id ] ) ) ) {
							efw_update_fee_rule( $rule_id, $rules );
						}
					}
				}

				set_transient( 'efw_rule_errors', $error, 45 );
			}
		}

		/**
		 * Validate Fields for Variable before save.
		 */
		public static function validate_fields_for_variable( $update, $fields, $i, $variation_id ) {
			$variation = new WC_Product_Variation( $variation_id );
			if ( isset( $fields['efw_enable_fee'][ $i ] ) && isset( $_REQUEST['efw_fee_from'][ $i ] )) {
				$fee_from = wc_clean( wp_unslash( $_REQUEST['efw_fee_from'][ $i ] ) );
				if ('1' != $fee_from) {
					return $update;
				}

				if ( isset( $fields['efw_text_from'][ $i ] ) && ( '2' == $fields['efw_text_from'][ $i ] ) && empty( $fields['efw_fee_text'][ $i ] ) ) {
					/* translators: %s : Variation Title */
					WC_Admin_Meta_Boxes::add_error( sprintf( esc_html__( '%s : Fee Text cannot be empty', 'extra-fees-for-woocommerce' ), $variation->get_formatted_name() ) );
					$update = false;
				}

				if ( isset( $fields['efw_fee_type'][ $i ] ) ) {
					if ( '1' == $fields['efw_fee_type'][ $i ] ) {
						if ( empty( $fields['efw_fixed_value'][ $i ] ) ) {
							/* translators: %s : Variation Title */
							WC_Admin_Meta_Boxes::add_error( sprintf( esc_html__( '%s : Fixed Fee Value cannot be empty', 'extra-fees-for-woocommerce' ), $variation->get_formatted_name() ) );
							$update = false;
						}
					}

					if ( '2' == $fields['efw_fee_type'][ $i ] ) {
						if ( empty( $fields['efw_percent_value'][ $i ] ) ) {
							/* translators: %s : Variation Title */
							WC_Admin_Meta_Boxes::add_error( sprintf( esc_html__( '%s : Fee Value in Percent cannot be empty', 'extra-fees-for-woocommerce' ), $variation->get_formatted_name() ) );
							$update = false;
						}
					}
				}
			}

			return $update;
		}

		/**
		 * Product Fee Settings in Add New Category Page.
		 */
		public static function new_category_settings() {
			if ( ! is_admin() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
				return;
			}

			include EFW_ABSPATH . 'inc/admin/menu/views/category/new-category-product-fee-settings.php';
		}

		/**
		 * Product Fee Settings in Edit Category Page.
		 */
		public static function edit_category_settings( $term, $taxonomy ) {
			if ( ! is_admin() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
				return;
			}

			$enable          = get_term_meta( $term->term_id, '_efw_enable_fee', true );
			$fee_text_from   = get_term_meta( $term->term_id, '_efw_text_from', true );
			$fee_text        = get_term_meta( $term->term_id, '_efw_fee_text', true );
			$fee_description = get_term_meta( $term->term_id, '_efw_fee_description', true );
			$fee_type        = get_term_meta( $term->term_id, '_efw_fee_type', true );
			$fixed_value     = get_term_meta( $term->term_id, '_efw_fixed_value', true );
			$percent_value   = get_term_meta( $term->term_id, '_efw_percent_value', true );

			include EFW_ABSPATH . 'inc/admin/menu/views/category/edit-category-product-fee-settings.php';
		}

		/**
		 * Product Fee Settings in Edit Category Page.
		 */
		public static function save_category_settings( $term_id, $tt_id, $taxonomy ) {
			if ( isset( $_REQUEST['efw_enable_fee'] ) ) {
				update_term_meta( $term_id, '_efw_enable_fee', 'yes' );
			} else {
				update_term_meta( $term_id, '_efw_enable_fee', 'no' );
			}

			if ( isset( $_REQUEST['efw_text_from'] ) ) {
				update_term_meta( $term_id, '_efw_text_from', wc_clean( wp_unslash( $_REQUEST['efw_text_from'] ) ) );
			}

			if ( isset( $_REQUEST['efw_fee_text'] ) ) {
				update_term_meta( $term_id, '_efw_fee_text', wc_clean( wp_unslash( $_REQUEST['efw_fee_text'] ) ) );
			}

			if ( isset( $_REQUEST['efw_fee_description'] ) ) {
				update_term_meta( $term_id, '_efw_fee_description', wc_clean( wp_unslash( $_REQUEST['efw_fee_description'] ) ) );
			}

			if ( isset( $_REQUEST['efw_fee_type'] ) ) {
				update_term_meta( $term_id, '_efw_fee_type', wc_clean( wp_unslash( $_REQUEST['efw_fee_type'] ) ) );
			}

			if ( isset( $_REQUEST['efw_fixed_value'] ) ) {
				update_term_meta( $term_id, '_efw_fixed_value', wc_clean( wp_unslash( $_REQUEST['efw_fixed_value'] ) ) );
			}

			if ( isset( $_REQUEST['efw_percent_value'] ) ) {
				update_term_meta( $term_id, '_efw_percent_value', wc_clean( wp_unslash( $_REQUEST['efw_percent_value'] ) ) );
			}
		}

		public static function add_error() {
			$rule_errors = get_transient( 'efw_rule_errors' );
			if ( efw_check_is_array( $rule_errors ) ) {
				foreach ( $rule_errors as $errors ) {

					if ( ! efw_check_is_array( $errors ) ) {
						continue;
					}

					foreach ( $errors as $error ) {

						if ( ! efw_check_is_array( $error ) ) {
							continue;
						}
						?>
						<div class="notice notice-error is-dismissible">
							<?php
							foreach ( $error as $err_msg ) :
								?>
								<p><?php echo esc_html( $err_msg ); ?></p>
								<?php
							endforeach;
							?>
						</div>
						<?php
					}
				}

				delete_transient( 'efw_rule_errors' );
			}
		}
	}

	EFW_Product_Fee_Settings::init();
}
