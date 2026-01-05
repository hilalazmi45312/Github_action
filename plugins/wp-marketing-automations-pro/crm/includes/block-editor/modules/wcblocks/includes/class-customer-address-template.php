<?php

if ( ! class_exists( 'BWFBE_WCAddress_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_WCAddress_Template {

		/**
		 * Instance.
		 *
		 * @access private
		 * @var object Instance
		 * @since 1.2.0
		 */
		private static $instance;

		private $responsive_style;

		private $wrapper_selector = '';

		private $tableAttrs = '';

		private $settings = [];

		private $g_sets = []; // global settings

		public static $order;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_shortcode( 'bwfbe_customer_addresses', [ $this, 'customer_addresses_block' ] );
		}

		public function customer_addresses_block( $atts, $content ) {

			self::$order = ! empty( BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) ) ? wc_get_order( BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) ) : '';

			do_action( 'bwfan_email_setup_locale', BWFAN_Common::get_order_language( self::$order ) );

			ob_start();

			$attributes = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );
			$defaults   = [
				'padding'         => [
					'desktop' => [
						'top'    => '8',
						'right'  => '16',
						'bottom' => '8',
						'left'   => '16',
						'unit'   => 'px'
					],
					'mobile'  => [
						'top'    => '8',
						'right'  => '8',
						'bottom' => '8',
						'left'   => '8',
						'unit'   => 'px'
					],
				],
				'showBilling'     => true,
				'showShipping'    => true,
				'billingHeading'  => 'Billing Address',
				'shippingHeading' => 'Shipping Address',
				'headingBold'     => [ 'desktop' => true ],
				'uniqueID'        => '',
				'headingFont'     => [
					'desktop' => [
						'size' => '18'
					]
				],
				'addrFont'        => [
					'desktop' => [
						'size' => '13'
					]
				],
				'alignment'       => [
					'desktop' => 'left',
				],
			];


			$this->settings = wp_parse_args( $attributes, $defaults );
			$this->g_sets   = isset( $this->settings['globals'] ) ? $this->settings['globals'] : [];

			$this->settings['classname'] = [
				'bwf-email-order-address',
				'bwf-email-order-address-' . ( $attributes['uniqueID'] ?? '' )
			];

			$this->wrapper_selector = '.bwf-email-order-address.bwf-email-order-address-' . $this->settings['uniqueID'];

			$this->tableAttrs = 'cellpadding="0" cellspacing="0" role="presentation" border="0"';

			$this->customer_addresses_html();

			?>
            <style data-id='woofunnels'>
                .bwf-email-order-address .bwfbe-addr-wrap .bwfbe-addr-content.bwfbe-addr span {
                    font-size: 0;
                }

                @media only screen and (max-width: 768px) {
                    .bwf-email-order-address .bwfbe-addr-wrap,
                    .bwf-email-order-address .bwfbe-addr-gap {
                        display: inline-block !important;
                    }

                    .bwf-email-order-address .bwfbe-addr-wrap {
                        width: 100% !important;
                    }

                    .bwf-email-order-address .bwfbe-addr-wrap .bwfbe-addr-content.bwfbe-addr br {
                        display: none;
                    }

                    .bwf-email-order-address .bwfbe-addr-wrap .bwfbe-addr-content.bwfbe-addr span {
                        font-size: inherit !important;
                    }

                <?php echo $this->responsive_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>

                }
            </style>
			<?php
			$this->responsive_style = '';


			return BWFAN_Common::decode_merge_tags( ob_get_clean() );
		}

		public function customer_address_data() {
			$mergetagdata    = BWFAN_Merge_Tag_Loader::get_data();
			$is_preview      = isset( $mergetagdata['is_preview'] ) && $mergetagdata['is_preview'];
			$has_order_exist = ! $is_preview && self::$order ? true : false;
			$dummy_data      = $this->customer_dummy_address();
			$filter_callback = function ( $args ) {
				$args['{first_name}'] = '';
				$args['{last_name}']  = '';
				$args['{name}']       = '';

				return $args;
			};
			add_filter( 'woocommerce_formatted_address_replacements', $filter_callback );

			$data = [
				'billingHeading'  => $this->settings['billingHeading'],
				'shippingHeading' => $this->settings['shippingHeading'],
				'shipping_name'   => $has_order_exist ? self::$order->get_formatted_shipping_full_name() : $dummy_data['name'],
				'billing_name'    => $has_order_exist ? self::$order->get_formatted_billing_full_name() : $dummy_data['name'],
				'address'         => $has_order_exist ? self::$order->get_formatted_billing_address() : $dummy_data['address'],
				'shipping'        => $has_order_exist ? self::$order->get_formatted_shipping_address() : $dummy_data['address'],
				'billing_email'   => $has_order_exist ? self::$order->get_billing_email() : $dummy_data['email'],
				'billing_phone'   => $has_order_exist ? self::$order->get_billing_phone() : $dummy_data['phone'],
				'shipping_phone'  => $has_order_exist ? self::$order->get_shipping_phone() : $dummy_data['phone'],
			];
			remove_filter( 'woocommerce_formatted_address_replacements', $filter_callback );

			return $data;
		}

		public function customer_dummy_address() {
			return [
				'name'    => 'John Doe',
				'address' => '548 Market St 70640<br/>San Francisco CA<br/>United States',
				'phone'   => '94104-5401',
				'email'   => 'johndoe@gmail.com',
			];
		}

		public function customer_addresses_html() {
			$showBilling  = $this->settings['showBilling'];
			$showShipping = $this->settings['showShipping'];

			if ( ! $showBilling && ! $showShipping ) {
				return '';
			}
			$data          = $this->customer_address_data();
			$show_shipping = ( self::$order && ! wc_ship_to_billing_address_only() && self::$order->needs_shipping_address() && $data['shipping'] && $showShipping ) || ( ! self::$order && $showShipping ) ? true : false;

			$width_style = $show_shipping && $showBilling ? 'width:50%;' : 'width:100%;';

			$classes   = implode( ' ', $this->settings['classname'] );
			$alignment = isset( $this->settings['alignment']['desktop'] ) ? $this->settings['alignment']['desktop'] : 'left';
			?>
            <tr>
                <td class="<?php echo $classes; ?>" style="<?php echo $this->get_block_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <table <?php echo is_rtl() ? 'dir="rtl"' : '' ?> <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%;<?php echo $this->get_block_align_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                        <tbody>
                        <tr>
                            <td style="font-size:0" align="<?php echo $alignment; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                <!--[if mso | IE]><table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%"><tr><![endif]-->
								<?php if ( $showBilling ) : ?>
                                    <!--[if mso | IE]><td  class="bwfbe-addr-wrap bwf-billing-addr" style="<?php echo $width_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>" valign="top"><![endif]-->
                                    <div class="bwfbe-addr-wrap bwf-billing-addr" style="display:table-cell;vertical-align:top;<?php echo $width_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>" valign="top">
                                        <p class="bwfbe-addr-head" style="<?php echo $this->get_heading_style( '.bwfbe-addr-wrap .bwfbe-addr-head' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo $data['billingHeading']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
                                        <p class="bwfbe-addr-content bwfbe-addr-name" style="<?php echo $this->get_content_style( '.bwfbe-addr-wrap .bwfbe-addr-content' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo $data['billing_name']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
                                        <p class="bwfbe-addr-content bwfbe-addr" style="<?php echo $this->get_content_style( '.bwfbe-addr-wrap .bwfbe-addr-content' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo str_replace( "<br/>", '<span>, </span><br/>', $data['address'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
                                        <p class="bwfbe-addr-content bwfbe-addr-phone-mail" style="<?php echo $this->get_content_style( '.bwfbe-addr-wrap .bwfbe-addr-content' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php if ( $data['billing_phone'] ) : ?>
												<?php echo $data['billing_phone']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
											<?php endif; ?>
											<?php if ( $data['billing_email'] ) : ?>
                                                <br/><?php echo esc_html( $data['billing_email'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
											<?php endif; ?>
                                        </p>
                                    </div>
                                    <!--[if mso | IE]></td><![endif]-->
								<?php endif; ?>
								<?php if ( $showBilling && $show_shipping ) : ?>
                                    <!--[if mso | IE]>
                                    <td class="bwfbe-addr-gap" style="width:16px;" valign="top"><![endif]-->
                                    <div class="bwfbe-addr-gap" style="display:table-cell;vertical-align:top;width:16px;height:16px;" valign="top">
                                        <table width="16" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:16px;">
                                            <tbody>
                                            <tr>
                                                <td></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!--[if mso | IE]></td><![endif]-->
								<?php endif; ?>

								<?php if ( $show_shipping ) : ?>
                                    <!--[if mso | IE]><td  class="bwfbe-addr-wrap bwf-shipping-addr" style="<?php echo $width_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>" valign="top"><![endif]-->
                                    <div class="bwfbe-addr-wrap bwf-shipping-addr" style="display:table-cell;vertical-align:top;<?php echo $width_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>" valign="top">
                                        <p class="bwfbe-addr-head" style="<?php echo $this->get_heading_style( '.bwfbe-addr-wrap .bwfbe-addr-head' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo $data['shippingHeading']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
                                        <p class="bwfbe-addr-content bwfbe-addr-name" style="<?php echo $this->get_content_style( '.bwfbe-addr-wrap .bwfbe-addr-content' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo $data['shipping_name']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
                                        <p class="bwfbe-addr-content bwfbe-addr" style="<?php echo $this->get_content_style( '.bwfbe-addr-wrap .bwfbe-addr-content' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo str_replace( '<br/>', '<span>, </span><br/>', $data['shipping'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
										<?php if ( $data['shipping_phone'] ) : ?>
                                            <p class="bwfbe-addr-content bwfbe-addr-phone-mail" style="<?php echo $this->get_content_style( '.bwfbe-addr-wrap .bwfbe-addr-content' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
												<?php echo $data['shipping_phone']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                            </p>
										<?php endif; ?>
                                    </div>
                                    <!--[if mso | IE]></td><![endif]-->
								<?php endif; ?>

                                <!--[if mso | IE]></tr></table><![endif]-->

                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
			<?php
		}


		/**
		 * Get Block Container style
		 */
		private function get_block_wrapper_style() {
			$style = 'word-break:break-word;';
			$style .= bwf_css()->getPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->getMsoPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->textAlign( $this->settings, 'alignment', 'desktop', false, is_rtl() ? 'text-align:right;' : '' );
			$style .= bwf_css()->getVisibilityCss( $this->settings, 'desktop' );


			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getPadding( $this->settings, 'padding', 'mobile', true );
				$mStyle                 .= bwf_css()->textAlign( $this->settings, 'alignment', 'mobile', true );
				$mStyle                 .= bwf_css()->getVisibilityCss( $this->settings, 'mobile' );
				$this->responsive_style .= sprintf( '%1$s {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}

		private function get_block_align_style() {
			$style = '';
			$style .= bwf_css()->textAlign( $this->settings, 'alignment', 'desktop', false, is_rtl() ? 'text-align:right;' : '' );

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->textAlign( $this->settings, 'alignment', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}

		/**
		 * Get Block Container style
		 */
		private function get_heading_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= 'font-weight:600;padding: 0px 0px 10px; mso-padding-alt: 0px 0px 10px;font-size:16px;';
			$style         .= bwf_css()->getFontFamily( $this->settings );
			$style         .= bwf_css()->getFontSize( $this->settings, 'headingFont', 'desktop' );
			$style         .= bwf_css()->getColor( $this->settings, 'headingColor', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'headingFont', 'mobile' );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_content_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = 'font-size:15px;';
			$style         .= $default_style['text-style'];

			$style .= bwf_css()->getFontFamily( $this->settings );
			$style .= bwf_css()->getFontSize( $this->settings, 'addrFont' );
			$style .= bwf_css()->getFontStyles( $this->settings, 'addrFont', 'desktop', false, false );
			$style .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop', false, 'line-height: 1.5;' );
			$style .= bwf_css()->getColor( $this->settings, 'addrColor', 'desktop' );
			$style .= bwf_css()->getBold( $this->settings, 'addrBold', 'desktop' );
			$style .= bwf_css()->getItalic( $this->settings, 'addrItalic', 'desktop' );
			$style .= $default_style['font-family'];

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'addrFont', 'mobile' );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}


		/**
		 * Block default and common style
		 */
		private function get_default_style( $type = '' ) {
			$defaultStyle = [
				'font-size'   => 'font-size:16px;',
				'font-family' => bwf_css()->getFontFamily( $this->settings, 'font', 'desktop' ),
				'text-style'  => 'margin:0;line-height:1.6;',
				'color'       => bwf_css()->getColor( $this->settings, 'Gcolor', 'desktop' )
			];

			if ( empty( $this->settings['font']['desktop']['family'] ) && ! empty( $this->settings['global']['fontFamily'] ) ) {
				$defaultStyle['font-family'] = 'font-family:' . $this->settings['global']['fontFamily'] . ';';
			}

			if ( ! empty( $type ) && is_string( $type ) ) {
				return $defaultStyle[ $type ];
			}

			return $defaultStyle;
		}

	}
}
BWFBE_WCAddress_Template::get_instance();
