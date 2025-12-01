<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Local_Emails' ) ) :

	class CRPRO_Local_Emails {

		public static $def_secnd_rmdr_body = "Hi {customer_first_name},\n\nWe hope you are enjoying your recent purchase from us! We just wanted to follow up on our previous email and kindly ask if you would be willing to share your thoughts on your order.\n\nWe are sorry for the extra message — we truly appreciate your time and would not reach out again if it was not important to us. Your feedback helps us improve and assists other customers in making informed decisions.\n\nIf you have a moment, please consider leaving a review.\n\nThank you for choosing us. We truly appreciate your support!\n\nBest regards,\n{site_title}";

		public function __construct() {
			add_filter( 'cr_local_review_reminder_subject', array( $this, 'advanced_review_reminder_subject' ), 10, 2 );
			add_filter( 'cr_local_review_discount_subject', array( $this, 'advanced_review_discount_subject' ), 10, 2 );
			add_filter( 'cr_local_review_reminder_template', array( $this, 'advanced_review_reminder_template' ), 10, 2 );
			add_filter( 'cr_local_review_discount_template', array( $this, 'advanced_review_discount_template' ), 10, 2 );
			add_filter( 'cr_email_template_id', array( $this, 'email_template_id' ), 10, 2 );
		}

		// filter for the review reminder email template
		public function advanced_review_reminder_template( $template, $args ) {
			if ( 'review_reminder_2' === $args['template_id'] ) {
				return $this->advanced_email_template( $template, $args, 'ivole_email_review_reminder_2' );
			} else {
				return $this->advanced_email_template( $template, $args, 'ivole_email_review_reminder' );
			}
		}

		// filter for the review discount email template
		public function advanced_review_discount_template( $template, $args ) {
			return $this->advanced_email_template( $template, $args, 'ivole_email_review_discount' );
		}

		public function advanced_email_template( $template, $args, $email_type ) {
			$email_template = get_option( $email_type, array() );
			if( isset( $email_template['enabled'] ) && $email_template['enabled'] ) {
				if(
					isset( $email_template['email'] ) &&
					is_array( $email_template['email'] )
				) {
					$lang = strtoupper( $args['language'] );
					// try to find an email template in the language of customer order
					if(
						isset( $email_template['email'][$lang] ) &&
						is_array( $email_template['email'][$lang] ) &&
						isset( $email_template['email'][$lang]['templateInline'] )
					) {
						$template = $email_template['email'][$lang]['templateInline'];
						$template = self::replace_variables_template( $template, $args, $email_type );
					} else {
						// as a fallback try to use an English language template
						$lang = 'EN';
						if(
							isset( $email_template['email'][$lang] ) &&
							is_array( $email_template['email'][$lang] ) &&
							isset( $email_template['email'][$lang]['templateInline'] )
						) {
							$template = $email_template['email'][$lang]['templateInline'];
							$template = self::replace_variables_template( $template, $args, $email_type );
						}
					}
				}
			}
			return $template;
		}

		// filter for the review reminder email subject
		public function advanced_review_reminder_subject( $subject, $args ) {
			if ( 'review_reminder_2' === $args['template_id'] ) {
				return $this->advanced_email_subject( $subject, $args, 'ivole_email_review_reminder_2' );
			} else {
				return $this->advanced_email_subject( $subject, $args, 'ivole_email_review_reminder' );
			}
		}

		// filter for the review discount email subject
		public function advanced_review_discount_subject( $subject, $args ) {
			return $this->advanced_email_subject( $subject, $args, 'ivole_email_review_discount' );
		}

		public function advanced_email_subject( $subject, $args, $email_type ) {
			$email_template = get_option( $email_type, array() );
			if( isset( $email_template['enabled'] ) && $email_template['enabled'] ) {
				if(
					isset( $email_template['email'] ) &&
					is_array( $email_template['email'] )
				) {
					$lang = strtoupper( $args['language'] );
					// try to find an email template in the language of customer order
					if(
						isset( $email_template['email'][$lang] ) &&
						is_array( $email_template['email'][$lang] ) &&
						isset( $email_template['email'][$lang]['subject'] )
					) {
						$subject = $email_template['email'][$lang]['subject'];
						$subject = self::replace_variables_subject( $subject, $args );
					} else {
						// as a fallback try to use an English language template
						$lang = 'EN';
						if(
							isset( $email_template['email'][$lang] ) &&
							is_array( $email_template['email'][$lang] ) &&
							isset( $email_template['email'][$lang]['subject'] )
						) {
							$subject = $email_template['email'][$lang]['subject'];
							$subject = self::replace_variables_subject( $subject, $args );
						}
					}
				}
			}
			return $subject;
		}

		private static function replace_variables_template( $email_message, $args, $email_type ) {
			//  list products and list products with pics
			$list_products = '';
			$list_products_w_pics = '';
			$list_products_no_price = '';
			$list_products_no_price_w_pics = '';
			$price_args = array( 'currency' => $args['currency'] );
			if( is_array( $args['items'] ) ) {
				foreach( $args['items'] as $item ) {
					$tmp = $item['name'] . ' / ' . self::crpro_price( $item['price'], $price_args ) . '<br>';
					$tmp_no_price = $item['name'] . '<br>';
					$list_products .= $tmp;
					$list_products_no_price .= $tmp_no_price;
					$list_products_w_pics .= sprintf( '<img src="%1$s" style="max-width: 50px; max-height: 50px; vertical-align: middle; margin: 5px 10px 5px 0;"><span style="vertical-align: middle;">%2$s</span><br>', $item['image'], $tmp );
					$list_products_no_price_w_pics .= sprintf( '<img src="%1$s" style="max-width: 50px; max-height: 50px; vertical-align: middle; margin: 5px 10px 5px 0;"><span style="vertical-align: middle;">%2$s</span><br>', $item['image'], $tmp_no_price );
				}
			}

			// unsubscribe link
			$unsubscribe_url = '';
			$unsubscribe_page = get_option( 'ivole_unsubscribe_page', '' );
			if( $unsubscribe_page ) {
				$unsubscribe_url = get_permalink( $unsubscribe_page );
				if( $args['is_test'] ) {
					$query_args = array(
						'cr-test' => 'yes',
						'cr-unsubscribe' => $args['email']
					);
				} else {
					$query_args = array(
						'cr-unsubscribe' => $args['email']
					);
				}
				$unsubscribe_url = esc_url( add_query_arg( $query_args, $unsubscribe_url ) );
			}

			if (
				'ivole_email_review_reminder' === $email_type ||
				'ivole_email_review_reminder_2' === $email_type
			) {
				$vars = array(
					'{{reviewLink}}',
					'{{shop name}}',
					'{{customer first name}}',
					'{{customer last name}}',
					'{{customer name}}',
					'{{order id}}',
					'{{order date}}',
					'{{list products}}',
					'{{list products with pics}}',
					'{{list products no price}}',
					'{{list products no price with pics}}',
					'{{unsubscribeLink}}'
				);
				$replacement = array(
					$args['review_form'],
					CRPRO_Form_Editor_Settings::get_shop_name(),
					$args['firstname'],
					$args['lastname'],
					trim( $args['firstname'] . ' ' . $args['lastname'] ),
					$args['order_id'],
					$args['order_date'],
					$list_products,
					$list_products_w_pics,
					$list_products_no_price,
					$list_products_no_price_w_pics,
					$unsubscribe_url
				);
				// optional tracking pixel
				if (
					'yes' === get_option( 'ivole_track_reminder_open', 'no' ) &&
					class_exists( 'CR_Local_Forms' )
				) {
					$pixel_src = esc_url( get_home_url() . '/' . CR_Local_Forms::PIXEL_SLUG . '/' . $args['email_id'] . '.png' );
					$cr_email_pixel = sprintf( CR_Local_Forms::PIXEL_DIV, $pixel_src );
					$insert_pos = strpos( $email_message, '</body>' );
					if ( false !== $insert_pos ) {
						$email_message = substr_replace( $email_message, $cr_email_pixel, $insert_pos, 0 );
					}
				}
			} elseif ( 'ivole_email_review_discount' === $email_type ) {
				$vars = array(
					'{{shop name}}',
					'{{customer first name}}',
					'{{customer last name}}',
					'{{customer name}}',
					'{{order id}}',
					'{{order date}}',
					'{{list products}}',
					'{{list products with pics}}',
					'{{list products no price}}',
					'{{list products no price with pics}}',
					'{{unsubscribeLink}}',
					'{{discount amount}}',
					'{{coupon code}}'
				);
				$replacement = array(
					CRPRO_Form_Editor_Settings::get_shop_name(),
					$args['firstname'],
					$args['lastname'],
					trim( $args['firstname'] . ' ' . $args['lastname'] ),
					$args['order_id'],
					$args['order_date'],
					$list_products,
					$list_products_w_pics,
					$list_products_no_price,
					$list_products_no_price_w_pics,
					$unsubscribe_url,
					$args['discount_amount'],
					$args['discount_code']
				);
			} else {
				$vars = array();
				$replacement = array();
			}

			for( $i = 0; $i < count( $vars ); $i++ ) {
				$email_message = str_replace( $vars[$i], $replacement[$i], $email_message );
			}
			return $email_message;
		}

		private static function replace_variables_subject( $email_subject, $args ) {
			$vars = array(
				'{customer_first_name}',
				'{customer_last_name}',
				'{customer_name}',
				'{order_id}'
			);
			$replacement = array(
				$args['firstname'],
				$args['lastname'],
				trim( $args['firstname'] . ' ' . $args['lastname'] ),
				$args['order_id']
			);
			for( $i = 0; $i < count( $vars ); $i++ ) {
				$email_subject = str_replace( $vars[$i], $replacement[$i], $email_subject );
			}
			return $email_subject;
		}

		private static function crpro_price( $price, $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'ex_tax_label'       => false,
					'currency'           => '',
					'decimal_separator'  => wc_get_price_decimal_separator(),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'decimals'           => wc_get_price_decimals(),
					'price_format'       => get_woocommerce_price_format(),
				)
			);

			$unformatted_price = $price;
			$negative          = $price < 0;
			$price             = floatval( $negative ? $price * -1 : $price );
			$price             = number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

			if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
				$price = wc_trim_zeros( $price );
			}

			$formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], '<span class="cr-Price-currencySymbol">' . get_woocommerce_currency_symbol( $args['currency'] ) . '</span>', $price );
			$return          = '<span class="cr-Price-amount amount"><bdi>' . $formatted_price . '</bdi></span>';

			return apply_filters( 'crpro_price', $return, $price, $args, $unformatted_price );
		}

		public function email_template_id( $template, $sequence ) {
			if ( 2 === $sequence ) {
				$template = 'review_reminder_2';
			}
			return $template;
		}

	}

endif;
