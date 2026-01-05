<?php

if ( ! class_exists( 'BWFBE_WCDownloadProduct_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_WCDownloadProduct_Template {

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

		private static $is_preview = false;
		public static $order;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_shortcode( 'bwfbe_downloadable_product', [ $this, 'downloadable_product_block' ] );
		}

		public function downloadable_product_block( $atts, $content ) {
			$mergetagdata     = BWFAN_Merge_Tag_Loader::get_data();
			self::$is_preview = isset( $mergetagdata['is_preview'] ) && $mergetagdata['is_preview'];
			self::$order      = isset( $mergetagdata['order_id'] ) && ! empty( $mergetagdata['order_id'] ) ? wc_get_order( $mergetagdata['order_id'] ) : '';

			$download_products = self::$order instanceof WC_Order ? self::$order->get_downloadable_items() : [];

			if ( ! self::$is_preview && empty( $download_products ) ) {
				return '';
			}

			do_action( 'bwfan_email_setup_locale', BWFAN_Common::get_order_language( self::$order ) );

			ob_start();

			$attributes = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );

			$defaults = [
				'padding'               => [
					'desktop' => [ 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px' ],
					'mobile'  => [ 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'unit' => 'px' ]
				],
				'dividerStyle'          => [ 'desktop' => 'solid' ],
				'btnTextLH'             => [ 'desktop' => [ 'value' => 120 ] ],
				'fileNameBold'          => [ 'desktop' => true ],
				'productTitleBold'      => [ 'desktop' => true ],
				'uniqueID'              => '',
				'productTitleFont'      => [
					'desktop' => [
						'size' => '14'
					]
				],
				'productExpiryFont'     => [
					'desktop' => [
						'size' => '12'
					]
				],
				'productExpiryColor'    => [
					'desktop' => '#82838e'
				],
				'fileNameFont'          => [
					'desktop' => [
						'size' => '14'
					]
				],
				'headingFont'           => [
					'desktop' => [
						'size' => '18'
					]
				],
				'headingText'           => '<strong>Download Files</strong>',
				'dividerStyle'          => [
					'desktop' => 'solid'
				],
				'dividerColor'          => [
					'desktop' => '#f6f6f6'
				],
				'headingBold'           => [
					'desktop' => true
				],
				'productTitleBold'      => [
					'desktop' => true
				],
				'fileNameBold'          => [
					'desktop' => true
				],
				'enabledContentOptions' => [
					'desktop' => [ 'heading', 'title', 'expiry', 'fileName', 'button' ]
				],
			];

			$this->settings = wp_parse_args( $attributes, $defaults );

			$this->settings['classname'] = [
				'bwf-email-down-products',
				'bwf-email-down-products-' . ( $attributes['uniqueID'] ?? '' )
			];

			$this->wrapper_selector = '.bwf-email-down-products.bwf-email-down-products-' . $this->settings['uniqueID'];

			$this->tableAttrs = 'cellpadding="0" cellspacing="0" role="presentation" border="0"';

			$this->downloadable_product_html();

			?>
            <style data-id='woofunnels'>
                @media only screen and (max-width: 768px) {
                    .bwf-email-down-products p, .bwf-email-down-products a {
                        font-size: 13px !important;
                    }

                    .bwf-email-down-products .download-heading p {
                        font-size: 16px !important;
                    }

                    .bwf-email-down-products .file-name {
                        width: 25% !important;
                    }

                    .bwf-email-down-products .download-btn {
                        width: 25% !important;
                    }

                    .bwf-email-down-products .download-btn a {
                        padding: 8px 6px !important;
                    }

                    .bwf-email-down-products .download-btn td {
                        padding: 0px !important;
                        background-color: unset !important;
                    }

                <?php echo $this->responsive_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>

                }
            </style>
			<?php
			$this->responsive_style = '';

			return BWFAN_Common::decode_merge_tags( ob_get_clean() );
		}

		public function downloadable_products_data() {
			$data = ! self::$is_preview && self::$order instanceof WC_Order ? self::$order->get_downloadable_items() : $this->dummy_products();

			return $data;
		}

		public function dummy_products() {
			return [
				[
					"product_id"     => 1,
					"product_name"   => "Test Product 1",
					"download_name"  => "[Download File Name]",
					"access_expires" => "Never",
				],
			];
		}

		/**
		 * Get expired date
		 *
		 * @param string $expiry Expiry date.
		 *
		 * @return string
		 */
		public function get_expired_date( $expiry = '' ) {
			if ( empty( $expiry ) ) {
				return __( 'Never', 'woocommerce' ); //phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			}

			$date_format = get_option( 'date_format' ) ?? 'd M, Y';
			if ( $expiry instanceof DateTime ) {
				return $expiry->format( $date_format );
			}

			if ( strtotime( $expiry ) ) {
				return date_i18n( $date_format, strtotime( $expiry ) );
			}

			return '-';
		}

		public function downloadable_product_html() {
			$counter = 1;

			$classes = implode( ' ', $this->settings['classname'] );
			?>
            <tr>
                <td class="<?php echo $classes; //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo $this->get_block_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%;">
                        <tbody>
						<?php if ( $this->is_section_enable( 'heading' ) ) : ?>
                            <tr>
                                <td class="download-heading" style="padding: 0px 0px 16px;mso-padding-alt: 0px 0px 16px;">
                                    <p class="down-products-heading" style="<?php echo $this->get_heading_style( '.down-products-heading' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
										<?php echo isset( $this->settings['headingText'] ) && $this->settings['headingText'] !== '' ? $this->settings['headingText'] : __( 'Downloads', 'wp-marketing-automations-pro' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    </p>
                                </td>
                            </tr>
						<?php endif; ?>
						<?php foreach ( $this->downloadable_products_data() as $product ) { ?>
                            <tr>
                                <td class="download-product-wrap" style="<?php echo $this->get_divider_style() . ( 1 == $counter && $this->is_section_enable( 'heading' ) ? 'padding: 0 0 24px 0;mso-padding-alt: 0 0 24px 0;' : 'padding: 24px 0px;mso-padding-alt: 24px 0px;' ) . ( count( $this->downloadable_products_data() ) === 1 && $this->is_section_enable( 'heading' ) ? 'border-bottom:0;padding:0;' : "" ) . ( 1 == $counter && ! $this->is_section_enable( 'heading' ) ? $this->get_divider_style( 'top' ) : '' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%;">
                                        <tbody>
                                        <tr>
											<?php if ( $this->is_section_enable( 'title' ) || $this->is_section_enable( 'expiry' ) ) : ?>
                                                <td class="product-info" style="width: 50%;padding: 0px 4px 0px 0px; mso-padding-alt:0px 4px 0px 0px;">
													<?php if ( $this->is_section_enable( 'title' ) ) : ?>
                                                        <p class="down-product-title" style="<?php echo $this->get_product_title_style( '.down-product-title' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
															<?php echo $product['product_name']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                        </p>
													<?php endif; ?>
													<?php if ( $this->is_section_enable( 'expiry' ) ) : ?>
                                                        <p class="down-product-expiry" style="<?php echo $this->get_product_expiry_style( '.down-product-expiry' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
															<?php echo ( isset( $this->settings['productExpiryText'] ) && $this->settings['productExpiryText'] !== '' ? $this->settings['productExpiryText'] : __( 'Expires On', 'wp-marketing-automations-pro' ) ) . ' : ' . ( $this->get_expired_date( $product['access_expires'] ?? '' ) ); //phpcs:ignore WordPress.Security.EscapeOutput
															?>
                                                        </p>
													<?php endif; ?>
                                                </td>
											<?php endif; ?>
											<?php if ( $this->is_section_enable( 'fileName' ) ) : ?>
                                                <td class="file-name" style="width: 30%;padding: 0px 4px 0px 0px; mso-padding-alt:0px 4px 0px 0px;">
                                                    <p style="<?php echo $this->get_file_name_style( '.file-name p' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $product['download_name']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>
                                                </td>
											<?php endif; ?>
											<?php if ( $this->is_section_enable( 'button' ) ) : ?>
                                                <td class="download-btn">
                                                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%;border-collapse:separate;">
                                                        <tbody>
                                                        <tr>
                                                            <td class="download-button-wrap" style="<?php echo $this->get_btn_wrap_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                                <a class="download-button" href="<?php echo esc_url( isset( $product['download_url'] ) ? $product['download_url'] : get_site_url() ) ?>" style="<?php echo $this->get_btn_text_style( '.download-btn a' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>" target="_blank">
																	<?php echo isset( $this->settings['btnText'] ) && $this->settings['btnText'] !== '' ? $this->settings['btnText'] : __( 'Download', 'woocommerce' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
											<?php endif; ?>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
							<?php $counter ++;
						} ?>
                        </tbody>
                    </table>
                </td>
            </tr>
			<?php
		}

		private function is_section_enable( $section = '', $screen = 'desktop' ) {
			$section_enabled = isset( $this->settings['enabledContentOptions'][ $screen ] ) ? $this->settings['enabledContentOptions'][ $screen ] : [];
			if ( in_array( $section, $section_enabled ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get Block Container style
		 */
		private function get_block_wrapper_style() {
			$style = 'word-break:break-word;';
			$style .= bwf_css()->getPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->getMsoPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->getVisibilityCss( $this->settings, 'desktop' );

			if ( isset( $this->wrapper_selector ) ) {
				$mStyle                 = bwf_css()->getPadding( $this->settings, 'padding', 'mobile', true );
				$mStyle                 .= bwf_css()->getVisibilityCss( $this->settings, 'mobile' );
				$this->responsive_style .= sprintf( '%1$s {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}


		/**
		 * Get Block Heading style
		 */
		private function get_heading_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = bwf_css()->getFontSize( $this->settings, 'headingFont', 'desktop', false, 'font-size:24px;' );
			$style         .= bwf_css()->getColor( $this->settings, 'headingColor', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );
			$style         .= $default_style['text-style'];

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'headingFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		/**
		 * Get Product Title style
		 */
		private function get_product_title_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'productTitleFont', 'desktop', false, 'font-size:16px;' );
			$style         .= bwf_css()->getColor( $this->settings, 'productTitleColor', 'desktop' );
			$style         .= bwf_css()->getBold( $this->settings, 'productTitleBold', 'desktop' );
			$style         .= bwf_css()->getItalic( $this->settings, 'productTitleItalic', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'productTitleFont', 'mobile', true );
				$style                  .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}


		/**
		 * Get Product Expiry style
		 */
		private function get_product_expiry_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'productExpiryFont', 'desktop', false, 'font-size:14px;' );
			$style         .= bwf_css()->getColor( $this->settings, 'productExpiryColor', 'desktop', 'color', false, 'color: #8c8f94;' );
			$style         .= bwf_css()->getBold( $this->settings, 'productExpiryBold', 'desktop' );
			$style         .= bwf_css()->getItalic( $this->settings, 'productExpiryItalic', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );
			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'productExpiryFont', 'mobile', true );
				$style                  .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}


		/**
		 * Get File Name style
		 */
		private function get_file_name_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'fileNameFont', 'desktop', false, 'font-size:14px;' );
			$style         .= bwf_css()->getColor( $this->settings, 'fileNameColor', 'desktop' );
			$style         .= bwf_css()->getBold( $this->settings, 'fileNameBold', 'desktop' );
			$style         .= bwf_css()->getItalic( $this->settings, 'fileNameItalic', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'fileNameFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}


		/**
		 * Get Download button style
		 */
		private function get_btn_wrap_style( $classname = '' ) {
			$style = 'cursor: auto;text-align: center;mso-padding-alt:8px 20px;border-radius: 3px;';
			$style .= bwf_css()->getFontSize( $this->settings, 'btnTextFont', 'desktop', 'buttonFont', 'font-size:15px;' );
			$style .= bwf_css()->getBgColor( $this->settings, 'btnBgColor', 'desktop', 'buttonBackground', false, 'background-color: #236fa1;' );
			$style .= bwf_css()->getBorderStyle( $this->settings, 'btnBorder', 'desktop' );
			$style .= bwf_css()->getBorderWidth( $this->settings, 'btnBorder', 'desktop' );
			$style .= bwf_css()->getBorderColor( $this->settings, 'btnBorder', 'desktop' );
			$style .= bwf_css()->getBorderRadius( $this->settings, 'btnBorder', 'desktop', false, '', 'buttonBorder' );

			return $style;
		}

		/**
		 * Get Download button text style
		 */
		private function get_btn_text_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = 'word-break:normal;padding: 8px 20px; mso-padding-alt: 0px;display: inline-block;text-decoration: none;text-transform:none;line-height:1.5;';
			$style         .= bwf_css()->getFontSize( $this->settings, 'btnTextFont', 'desktop', 'buttonFont', false, 'font-size:15px;' );
			$style         .= bwf_css()->getBgColor( $this->settings, 'btnBgColor', 'desktop', 'buttonBackground', false, 'background:#236fa1;' );
			$style         .= bwf_css()->getBorderRadius( $this->settings, 'btnBorder', 'desktop', false, '', 'buttonBorder' );
			$style         .= bwf_css()->getColor( $this->settings, 'btnTextColor', 'desktop', 'buttonColor', false, 'color: #ffffff;' );
			$style         .= $default_style['text-style'];

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'btnTextFont', 'mobile', 'buttonFont' );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		/**
		 * Get Divider style
		 */
		private function get_divider_style( $position = 'bottom' ) {
			if ( $this->settings['dividerColor']['desktop'] === 'none' ) {
				return '';
			}
			$dividerColor = isset( $this->settings['dividerColor']['desktop'] ) ? $this->settings['dividerColor']['desktop'] : '';
			$dividerStyle = isset( $this->settings['dividerStyle']['desktop'] ) ? $this->settings['dividerStyle']['desktop'] : '';
			$css          = 'border-' . $position . ': 1px ' . $dividerStyle . ' ' . $dividerColor . ';';

			return $css;
		}


		/**
		 * Block default and common style
		 */
		private function get_default_style( $type = '' ) {
			$defaultStyle = [
				'font-size'  => 'font-size:15px;',
				'text-style' => 'margin:0;line-height:1.5;' . bwf_css()->getFontFamily( $this->settings ),
				'color'      => bwf_css()->getColor( $this->settings, 'color', 'desktop' )
			];

			if ( ! empty( $type ) && is_string( $type ) ) {
				return $defaultStyle[ $type ];
			}

			return $defaultStyle;
		}
	}
}
BWFBE_WCDownloadProduct_Template::get_instance();