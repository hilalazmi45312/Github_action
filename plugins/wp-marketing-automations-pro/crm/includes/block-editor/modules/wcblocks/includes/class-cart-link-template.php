<?php

if ( ! class_exists( 'BWFBE_WC_Cart_Link_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_WC_Cart_Link_Template {
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

		public static $cart_data;
		private static $is_preview = false;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_shortcode( 'bwfbe_cart_link', [ $this, 'cart_link_block' ] );
		}

		public function cart_link_block( $atts, $content ) {
			self::$cart_data = [];

			$mergetagdata     = BWFAN_Merge_Tag_Loader::get_data();
			$lang             = $mergetagdata['language'] ?? '';
			self::$is_preview = isset( $mergetagdata['is_preview'] ) && $mergetagdata['is_preview'];

			if ( isset( $mergetagdata['cart_details'] ) ) {
				//set cart
				if ( isset( $mergetagdata['cart_details']['items'] ) ) {
					$mergetagdata['cart_details']['items'] = maybe_unserialize( $mergetagdata['cart_details']['items'] );
				}
				self::$cart_data = $mergetagdata['cart_details'];
			} else if ( isset( $mergetagdata['cart_abandoned_id'] ) && ! empty( $mergetagdata['cart_abandoned_id'] ) ) {
				// get cart data
				$cart_details = BWFAN_Model_Abandonedcarts::get( intval( $mergetagdata['cart_abandoned_id'] ) );
				if ( isset( $cart_details['items'] ) ) {
					$cart_details['items'] = maybe_unserialize( $cart_details['items'] );
				}
				self::$cart_data = $cart_details;
			}

			$screen     = 'desktop';
			$attributes = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );
			if ( ! self::$is_preview && isset( self::$cart_data['token'] ) ) {
				$coupon = isset( $attributes['coupon'] ) ? $attributes['coupon'] : '';
				$url    = BWFAN_Common::wc_get_cart_recovery_url( self::$cart_data['token'], BWFAN_Common::decode_merge_tags( $coupon ), $lang );
			} else {
				$url = site_url();
			}

			ob_start();

			$defaults       = array(
				'content'          => 'Continue to Checkout',
				'rowHasBgImage'    => false,
				'target'           => '_blank',
				'uniqueID'         => '',
				'containerPadding' => [
					'desktop' => [
						'top'    => '8',
						'right'  => '16',
						'bottom' => '8',
						'left'   => '16',
						'unit'   => 'px'
					],
				],
			);
			$this->settings = wp_parse_args( $attributes, $defaults );

			$alignment           = isset( $this->settings['alignment'] ) ? $this->settings['alignment'][ $screen ] : 'center';
			$hasContainerPadding = isset( $this->settings['containerPadding'] ) && isset( $this->settings['containerPadding'][ $screen ] ) && ! empty( $this->settings['containerPadding'][ $screen ]['left'] );
			$rowHasBgImage       = isset( $this->settings['rowHasBgImage'] ) && $this->settings['rowHasBgImage'];
			$hasUrl              = isset( $url ) && ! empty( $url );

			$this->settings['classname'] = [
				'bwf-email-cart-link',
				'bwf-email-cart-link-' . $this->settings['uniqueID']
			];

			$this->wrapper_selector = '.bwf-email-cart-link.bwf-email-cart-link-' . $this->settings['uniqueID'];

			$this->tableAttrs = 'cellpadding="0" cellspacing="0" role="presentation" border="0"';

			?>
            <tr>
                <td align="<?php echo $alignment; //phpcs:ignore WordPress.Security.EscapeOutput ?>" class="<?php echo implode( ' ', $this->settings['classname'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo $this->get_block_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
					<?php if ( $rowHasBgImage && $hasContainerPadding ) { ?>
                        '<!--[if mso | IE]><span style="<?php echo 'margin-left: ' . $this->settings['containerPadding'][ $screen ]['left'] . $this->settings['containerPadding'][ $screen ]['unit'] . ';'; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><![endif]-->
					<?php } ?>
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> role="presentation" class="bwf-email-cart-link-container" style="border-collapse:separate;<?php echo $this->getButtonWidth(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                        <tbody>
                        <tr>
                            <td align="center" class="bwf-cart-link-btn-wrap" style="font-size:16px;<?php echo $this->getBtnStyle(); //phpcs:ignore WordPress.Security.EscapeOutput ?>" bgcolor="<?php ( isset( $this->settings['bgColor'][ $screen ] ) ? $this->settings['bgColor'][ $screen ] : '' ); ?>">
								<?php if ( $hasUrl ) { ?>
                                <a class="bwf-cart-link-btn" href="<?php echo $url; //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo $this->getBtnTextStyle(); //phpcs:ignore WordPress.Security.EscapeOutput ?>" target="_blank">
									<?php } else { ?>
                                    <p class="bwf-cart-link-btn" style="margin: 0;<?php echo $this->getBtnTextStyle(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
										<?php } ?>
										<?php echo $this->settings['content']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
										<?php echo $hasUrl ? '</a>' : '</p>'; ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
					<?php if ( $rowHasBgImage && $hasContainerPadding ) { ?>
                        <!--[if mso | IE]></span><![endif]-->
					<?php } ?>
                </td>
            </tr>
			<?php

			?>
            <style data-id='woofunnels'>
                @media only screen and (max-width: 768px) {

                <?php echo $this->responsive_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>

                }
            </style>
			<?php
			$this->responsive_style = '';

			return BWFAN_Common::decode_merge_tags( ob_get_clean() );
		}

		public function getBorderStyle() {
			$style = bwf_css()->getBorderRadius( $this->settings, 'border', 'desktop', false, '', 'buttonBorder' );
			if ( isset( $this->settings['border'] ) && isset( $this->settings['border']['desktop'] ) ) {
				$style .= bwf_css()->getBorderWidth( $this->settings, 'border', 'desktop' );
				$style .= bwf_css()->getBorderColor( $this->settings, 'border', 'desktop' );
				$style .= ( isset( $this->settings['border']['desktop']['style'] ) && ! in_array( $this->settings['border']['desktop']['style'], [
					'',
					'none'
				] ) ? ( bwf_css()->getBorderStyle( $this->settings, 'border', 'desktop' ) ) : 'border:none;' );
			}

			return $style;
		}

		public function getPadding() {
			return bwf_css()->getPadding( $this->settings, 'padding', 'desktop', false, 'padding: 10px 20px;', 'buttonPadding' ) . 'mso-padding-alt: 0px;';
		}

		public function getAlignment() {
			$style = isset( $this->settings['alignment'] ) ? bwf_css()->getAlignment( $this->settings, 'alignment', 'desktop' ) : 'margin: auto;';
		}

		public function getButtonWidth() {
			$style = bwf_css()->handleAutoWidth( $this->settings, 'autoWidth', 'widthPercent', 'desktop', [ 'buttonAuto', 'buttonSize' ], false, 'value' );
			$style .= 'border-collapse:separate;';

			$mStyle                 = bwf_css()->handleAutoWidth( $this->settings, 'autoWidth', 'widthPercent', 'mobile', '', true, 'value' );
			$this->responsive_style .= sprintf( '%1$s .bwf-email-cart-link-container  {%2$s}', $this->wrapper_selector, $mStyle );

			return $style;
		}

		public function getBtnStyle() {
			$borderStyle = $this->getBorderStyle();
			$style       = bwf_css()->getBgColor( $this->settings, 'bgColor', 'desktop', 'buttonBackground', false, 'background:#0073aa;' );
			$style       .= bwf_css()->getFontFamily( $this->settings );
			$style       .= bwf_css()->getFontSize( $this->settings, 'font', 'desktop', 'buttonFont' );
			$style       .= ( $borderStyle ?? 'border-style:none;' );
			$style       .= bwf_css()->getMsoPadding( $this->settings, 'padding', 'desktop', false, 'mso-padding-alt:10px 20px;', 'buttonPadding' );
			$style       .= bwf_css()->textAlign( $this->settings, 'textAlignment', 'desktop' );
			$style       .= 'cursor: auto;';

			return $style;
		}

		public function getBtnTextStyle() {
			$style = bwf_css()->getBgColor( $this->settings, 'bgColor', 'desktop', 'buttonBackground', false, 'background:#0073aa;' );
			$style .= bwf_css()->getFontFamily( $this->settings );
			$style .= bwf_css()->getFontSize( $this->settings, 'font', 'desktop', 'buttonFont' );
			$style .= 'display: inline-block;';
			$style .= ( $borderStyle ?? 'border-style:none;' );
			$style .= $this->getPadding();
			$style .= bwf_css()->getColor( $this->settings, 'color', 'desktop', 'buttonColor', false );
			$style .= bwf_css()->getBorderRadius( $this->settings, 'border', 'desktop', false, '', 'buttonBorder' );
			$style .= 'text-decoration: none; text-transform:none;line-height:1.5;';

			$mStyle                 = bwf_css()->getFontSize( $this->settings, 'font', 'mobile', 'buttonFont' );
			$mStyle                 .= bwf_css()->getPadding( $this->settings, 'padding', 'mobile', true );
			$this->responsive_style .= sprintf( '%1$s .bwf-cart-link-btn {%2$s}', $this->wrapper_selector, $mStyle );

			return $style;
		}

		/**
		 * Get Block Container style
		 */
		private function get_block_wrapper_style() {
			$style = 'word-break:break-word;';
			$style .= bwf_css()->getPadding( $this->settings, 'containerPadding', 'desktop', false, 'padding: 10px; mso-padding-alt:10px;' );
			$style .= bwf_css()->getMsoPadding( $this->settings, 'containerPadding', 'desktop' );
			$style .= bwf_css()->getVisibilityCss( $this->settings, 'desktop' );

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getPadding( $this->settings, 'containerPadding', 'mobile', true );
				$mStyle                 .= isset( $this->settings['alignment'] ) ? '-webkit-' . $this->settings['alignment']['mobile'] : '';
				$mStyle                 .= bwf_css()->getVisibilityCss( $this->settings, 'mobile' );
				$this->responsive_style .= sprintf( '%1$s {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}


		/**
		 * Block default and common style
		 */
		private function get_default_style( $type = '' ) {
			$defaultStyle = [
				'font-size'  => 'font-size:16px;',
				'text-style' => 'margin:0;line-height: 1.5;' . bwf_css()->getFontStyles( $this->settings, 'font', 'desktop' ),
				'color'      => bwf_css()->getColor( $this->settings, 'color', 'desktop' )
			];

			if ( ! empty( $type ) && is_string( $type ) ) {
				return $defaultStyle[ $type ];
			}

			return $defaultStyle;
		}

		function globalSettingsCheck( $attrName, $otherCheck = true ) {
			$global = BWFCRM_Block_Editor::$global_settings;

			return isset( $global ) && isset( $global[ $attrName ] ) && $otherCheck;
		}
	}
}
BWFBE_WC_Cart_Link_Template::get_instance();
