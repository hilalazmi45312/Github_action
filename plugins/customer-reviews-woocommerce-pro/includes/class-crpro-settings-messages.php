<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Messages_Settings' ) ) :

	class CRPRO_Messages_Settings {

		public function __construct() {
			add_filter( 'cr_settings_messages_tab', array( $this, 'messages_settings_tab' ), 10, 2 );
			add_filter( 'cr_settings_messages_templates', array( $this, 'messages_templates' ), 10, 1 );
			add_filter( 'cr_settings_wa_template_description', array( $this, 'wa_template_description' ), 10, 2 );
			add_filter( 'cr_settings_wa_template_enabled', array( $this, 'wa_template_enabled' ), 10, 2 );
			add_filter( 'cr_settings_wa_template_title', array( $this, 'wa_template_title' ), 10, 2 );
			add_filter( 'cr_settings_wa_template_message', array( $this, 'wa_template_message' ), 10, 2 );
			add_filter( 'cr_settings_messages_sections', array( $this, 'display_wa_template' ), 10, 2 );
		}

		public function messages_settings_tab( $show_tab, $verified_reviews ) {
			if ( 'yes' === $verified_reviews ) {
				$show_tab = true;
			}
			return $show_tab;
		}

		public function messages_templates( $templates ) {
			$verified_reviews = get_option( 'ivole_verified_reviews', 'no' );
			if ( 'no' !== $verified_reviews ) {
				$templates = array(
					'wa_api_review_reminder' => 'wa_api_review_reminder',
					'wa_api_review_discount' => 'wa_api_review_discount'
				);
			}
			return $templates;
		}

		public function wa_template_description( $description, $template ) {
			switch( $template ) {
				case 'wa_api_review_reminder':
					$description = __( 'Review reminders include an invitation to write a review. They are sent to customers who recently purchased something from your store.', 'customer-reviews-woocommerce-pro' );
					break;
				case 'wa_api_review_discount':
					$description = __( 'Review for discount messages provide discount coupons to customers who wrote reviews.', 'customer-reviews-woocommerce-pro' );
					break;
				default:
					break;
			}
			return $description;
		}

		public function wa_template_enabled( $enabled, $template ) {
			switch( $template ) {
				case 'wa_api_review_reminder':
					$enabled = 'yes' === get_option( 'ivole_enable', 'no' ) ? true : false;
					break;
				case 'wa_api_review_discount':
					$coupon_enable_option = CR_Review_Discount_Settings::get_review_discounts();
					foreach( $coupon_enable_option as $coupon_enable ) {
						if (
							'wa' === $coupon_enable['channel'] &&
							$coupon_enable['enabled']
						) {
							$enabled = true;
							break;
						}
					}
					break;
				default:
					break;
			}
			return $enabled;
		}

		public function wa_template_title( $title, $template ) {
			switch( $template ) {
				case 'wa_api_review_reminder':
					$title = __( 'Review Reminder (WhatsApp API)', 'customer-reviews-woocommerce-pro' );
					break;
				case 'wa_api_review_discount':
					$title = __( 'Review for Discount (WhatsApp API)', 'customer-reviews-woocommerce-pro' );
					break;
				default:
					break;
			}
			return $title;
		}

		public function wa_template_message( $message, $template ) {
			switch( $template ) {
				case 'wa_api_review_reminder':
				case 'wa_api_review_discount':
					$message = sprintf(
						__( 'Message templates for WhatsApp API need to be configured in the <a href="%1$s" target="_blank" rel="noopener noreferrer">CusRev dashboard</a>%2$s. Unfortunately, it is not yet possible to configure them directly in the WordPress dashboard.', 'customer-reviews-woocommerce-pro' ),
						'https://www.cusrev.com/dashboard',
						'<img src="' . untrailingslashit( plugin_dir_url( dirname( __FILE__ ) ) ) . '/img/external-link.png" class="cr-product-feed-categories-ext-icon">'
					);
					break;
				default:
					break;
			}
			return $message;
		}

		public function display_wa_template( $section, $section_slug ) {
			if (
				'wa_api_review_reminder' === $section_slug ||
				'wa_api_review_discount' === $section_slug
			) {
				$section = $this->output_wa_template_fields( $section_slug );
			}
			return $section;
		}

		public function output_wa_template_fields( $template ) {
			ob_start();
			echo '<h2>' . esc_html( $this->wa_template_title( '', $template ) );
			wc_back_link(
				__( 'Return to messages', 'customer-reviews-woocommerce-pro' ),
				admin_url( 'admin.php?page=cr-reviews-settings&tab=messages' )
			);
			echo '</h2>';
			echo wpautop(
				wp_kses_post(
					$this->wa_template_description( '', $template )
				)
			);
			echo '<table class="form-table">';
			WC_Admin_Settings::output_fields( $this->get_wa_template_fields( $template ) );
			echo '</table>';

			return ob_get_clean();
		}

		private function get_wa_template_fields( $template ) {
			$fields = array();
			$class_send_test = 'cr-test-wa-reminder-input';
			$fields[10] = array(
				'title'    => __( 'Message', 'customer-reviews-woocommerce-pro' ),
				'type'     => 'exteditable',
				'desc'     => $this->wa_template_message( '', $template ),
				'id'       => 'crpro' . $template,
				'desc_tip' => __( 'Message that will be sent to customers via WhatsApp API.', 'customer-reviews-woocommerce-pro' ),
				'autoload' => false
			);
			if ( 'wa_api_review_discount' === $template ) {
				$class_send_test = 'cr-test-wa-coupon-input';
				$fields[20] = CR_Review_Discount_Settings::get_media_count_field( 'cr_wa_test_media_count' );
			}
			$fields[30] = array(
				'title'       => __( 'Send Test', 'customer-reviews-woocommerce-pro' ),
				'type'        => 'waapitest',
				'desc'        => __( 'Send a test message to this phone number by WhatsApp. You must save changes before sending a test message.', 'customer-reviews-woocommerce-pro' ),
				'default'     => '',
				'placeholder' => 'Phone number',
				'css'         => 'min-width:300px;',
				'desc_tip'    => true,
				'class' => $class_send_test
			);
			return $fields;
		}

	}

endif;
