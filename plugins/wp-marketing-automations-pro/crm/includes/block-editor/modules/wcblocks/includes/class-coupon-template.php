<?php

if ( ! class_exists( 'BWFBE_WC_Coupon_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_WC_Coupon_Template {
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

		public static $order;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_shortcode( 'bwfbe_coupon', [ $this, 'coupon_block' ] );
		}


		public function coupon_block( $atts, $content ) {
			ob_start();

			$attributes = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );

			$defaults = [
				'padding'               => [
					'desktop' => [
						'top'    => '8',
						'right'  => '16',
						'bottom' => '8',
						'left'   => '16',
						'unit'   => 'px'
					],
					'mobile'  => [
						'top'    => '8',
						'right'  => '16',
						'bottom' => '8',
						'left'   => '16',
						'unit'   => 'px'
					],
				],
				'headingText'           => '<strong>10% OFF DISCOUNT<strong>',
				'msg'                   => "As thanks for shopping with us, we're giving you a discount coupon to use on your next purchase.",
				'code'                  => 'TAKE10OFF',
				'btnText'               => 'SHOP NOW',
				'url'                   => get_site_url(),
				'codeBorder'            => [
					'desktop' => [
						'style'        => 'dashed',
						'left'         => '2',
						'right'        => '2',
						'top'          => '2',
						'bottom'       => '2',
						'unit'         => 'px',
						'color_top'    => '#353030',
						'color_right'  => '#353030',
						'color_bottom' => '#353030',
						'color_left'   => '#353030',
					],
				],
				'codeAutoWidth'         => [
					'desktop' => false,
					'mobile'  => false,
				],
				'codeWidth'             => [
					'desktop' => [
						'value' => '100',
						'unit'  => 'px'
					]
				],
				'codePadding'           => [
					'desktop' => [
						'top'    => '8',
						'right'  => '8',
						'bottom' => '8',
						'left'   => '8',
						'unit'   => 'px'
					],
				],
				'btnAutoWidth'          => [
					'desktop' => false,
					'mobile'  => false,
				],
				'btnWidth'              => [
					'desktop' => [
						'value' => '100',
						'unit'  => 'px'
					]
				],
				'alignment'             => [
					'desktop' => 'center',
				],
				'btnURL'                => get_site_url(),
				'btnTarget'             => '_blank',
				'uniqueID'              => '',
				'headingFont'           => [
					'desktop' => [
						'size' => '18'
					]
				],
				'msgFont'               => [
					'desktop' => [
						'size' => '14'
					]
				],
				'btnTextFont'           => [
					'desktop' => [
						'size' => '18'
					]
				],
				'enabledContentOptions' => [
					'desktop' => [ 'heading', 'paragraph', 'code', 'button' ]
				],
			];

			$this->settings = wp_parse_args( $attributes, $defaults );

			$this->settings['classname'] = [
				'bwf-email-coupon',
				'bwf-email-coupon-' . ( $attributes['uniqueID'] ?? '' )
			];

			$this->wrapper_selector = '.bwf-email-coupon.bwf-email-coupon-' . $this->settings['uniqueID'];

			$this->tableAttrs = 'cellpadding="0" cellspacing="0" role="presentation" border="0"';

			$isHeadingEmpty = empty( str_replace( ' ', '', $this->settings['headingText'] ) );
			$isMsgEmpty     = empty( str_replace( ' ', '', $this->settings['msg'] ) );

			$alignment = isset( $this->settings['alignment'] ) && isset( $this->settings['alignment']['desktop'] ) ? $this->settings['alignment']['desktop'] : 'center';
			?>
            <tr>
                <td class="<?php echo implode( ' ', $this->settings['classname'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo $this->get_block_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $this->getAlignment(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> align="<?php echo $alignment; //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="width: 100%;<?php echo $this->getAlignment(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
						<?php if ( $this->is_section_enable( 'heading' ) || $this->is_section_enable( 'paragraph' ) ) { ?>
                            <tr>
                                <td class="heading-wrap" style="<?php echo $this->getAlignment(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
									<?php if ( $this->is_section_enable( 'heading' ) ) { ?>
                                        <p class="coupon-heading" style="<?php echo $this->get_heading_style( '.coupon-heading', $isHeadingEmpty ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo $this->settings['headingText']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
									<?php } ?>

									<?php if ( $this->is_section_enable( 'paragraph' ) ) { ?>
                                        <p class="coupon-msg" style="<?php echo $this->get_msg_style( '.coupon-msg' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo $this->settings['msg']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
									<?php } ?>
                                </td>
                            </tr>
						<?php } ?>
						<?php if ( $this->is_section_enable( 'code' ) ) { ?>
                            <tr>
                                <td class="coupone-code-outer-wrap" style="<?php echo 'padding:16px 0 0;mso-padding-alt:16px 0 0 0;' ?>" align="<?php echo $alignment; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="<?php echo $this->getCodeWidth(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                        <tr>
                                            <td class="coupone-code-wrap" style="<?php echo $this->get_code_wrap_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                <p class="coupon-code" style="<?php echo $this->get_code_text_style( '.coupon-code' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
													<?php echo $this->settings['code']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
						<?php } ?>
						<?php if ( $this->is_section_enable( 'button' ) ) { ?>
                            <tr>
                                <td class="coupon-btn-wrap" align="<?php echo $alignment; //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo 'padding:16px 0 0;mso-padding-alt:16px 0 0 0;' ?>">
                                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="<?php echo $this->getButtonWidth(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                        <tr>
                                            <td class="coupon-btn" style="<?php echo $this->get_btn_style( '.coupon-btn' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                <a class="coupon-btn-text" href="<?php echo ! empty( $this->settings['btnURL'] ) ? $this->settings['btnURL'] : get_site_url(); //phpcs:ignore WordPress.Security.EscapeOutput ?>" target="_blank" style="<?php echo $this->get_btn_text_style( '.coupon-btn-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>" rel="noopener noreferrer">
													<?php echo $this->settings['btnText']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                </a>
                                            </td>
                                        <tr>
                                    </table>
                                </td>
                            </tr>
						<?php } ?>
                    </table>
                </td>
            </tr>
			<?php
			$this->getRespAlignment();
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
			$style = 'word-break:break-word;text-align: center;';
			$style .= bwf_css()->getPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->getMsoPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->getVisibilityCss( $this->settings, 'desktop' );

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getPadding( $this->settings, 'padding', 'mobile', true );
				$mStyle                 .= bwf_css()->getVisibilityCss( $this->settings, 'mobile' );
				$this->responsive_style .= sprintf( '%1$s {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}

		/**
		 * Get Heading style
		 */
		private function get_heading_style( $classname = '', $isHeadingEmpty = false ) {
			$default_style = $this->get_default_style();
			$style         = 'font-weight:600;';
			$style         .= $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'headingFont', 'desktop' );
			$style         .= bwf_css()->getColor( $this->settings, 'headingColor', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'headingFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		/**
		 * Get Message style
		 */
		private function get_msg_style( $classname = '' ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'msgFont', 'desktop' );
			$style         .= bwf_css()->getColor( $this->settings, 'msgColor', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );
			$style         .= 'padding-top: 8px; mso-padding-alt: 8px 0 0 0;';

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'msgFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		/**
		 * Get Code wrapper style
		 */
		public function get_code_wrap_style() {
			$style = 'text-align: center;padding: 10px 0px; mso-padding-alt: 10px 0px;border-color: #0073aa;';
			$style .= bwf_css()->getBorderWidth( $this->settings, 'codeBorder', 'desktop' );
			$style .= bwf_css()->getBorderColor( $this->settings, 'codeBorder', 'desktop' );
			$style .= bwf_css()->getBorderStyle( $this->settings, 'codeBorder', 'desktop' );
			$style .= bwf_css()->getBorderRadius( $this->settings, 'codeBorder', 'desktop', false, '', 'buttonBorder' );
			$style .= bwf_css()->getPadding( $this->settings, 'codePadding', 'desktop' );

			$style .= bwf_css()->getMsoPadding( $this->settings, 'codePadding', 'desktop' );

			$mStyle                 = bwf_css()->getPadding( $this->settings, 'codePadding', 'desktop' );
			$this->responsive_style .= sprintf( '%1$s .coupone-code-wrap {%2$s}', $this->wrapper_selector, $mStyle );

			return $style;
		}

		/**
		 * Get Code text style
		 */
		public function get_code_text_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'codeFont', 'desktop', false, $default_style['font-size'] );
			$style         .= bwf_css()->getColor( $this->settings, 'codeColor', 'desktop' );
			$style         .= 'line-height:1.5';

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'codeFont', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		public function getButtonWidth() {
			$style = bwf_css()->handleAutoWidth( $this->settings, 'btnAutoWidth', 'btnWidth', 'desktop', [ 'buttonAuto', 'buttonSize' ], false, 'value' );
			$style .= 'border-collapse:separate;';

			$mStyle                 = bwf_css()->handleAutoWidth( $this->settings, 'btnAutoWidth', 'btnWidth', 'mobile', [ 'buttonAuto', 'buttonSize' ], true, 'value' );
			$this->responsive_style .= sprintf( '%1$s .coupon-btn-wrap > table {%2$s}', $this->wrapper_selector, $mStyle );

			return $style;
		}

		public function getCodeWidth() {
			$style = bwf_css()->handleAutoWidth( $this->settings, 'codeAutoWidth', 'codeWidth', 'desktop', [ 'buttonAuto', 'buttonSize' ], false, 'value' );
			$style .= 'border-collapse:separate;';

			$mStyle                 = bwf_css()->handleAutoWidth( $this->settings, 'codeAutoWidth', 'codeWidth', 'mobile', [ 'buttonAuto', 'buttonSize' ], true, 'value' );
			$this->responsive_style .= sprintf( '%1$s .coupone-code-outer-wrap > table {%2$s}', $this->wrapper_selector, $mStyle );

			return $style;
		}

		public function getAlignment() {
			$style = isset( $this->settings['alignment'] ) && isset( $this->settings['alignment']['desktop'] ) ? 'text-align: ' . $this->settings['alignment']['desktop'] . ';' : 'text-align: center;';

			return $style;
		}

		public function getRespAlignment() {
			$mStyle  = isset( $this->settings['alignment'] ) && isset( $this->settings['alignment']['mobile'] ) ? 'text-align: ' . ( $isTable ? ' -webkit-' : '' ) . $this->settings['alignment']['mobile'] . ' !important;' : '';
			$mStyle1 = isset( $this->settings['alignment'] ) && isset( $this->settings['alignment']['mobile'] ) ? 'text-align: -webkit-' . ( $isTable ? ' -webkit-' : '' ) . $this->settings['alignment']['mobile'] . ' !important;' : '';
			if ( ! empty( $mStyle ) ) {
				$this->responsive_style .= sprintf( '%1$s, %1$s .heading-wrap {%2$s}', $this->wrapper_selector, $mStyle );
				$this->responsive_style .= sprintf( '%1$s .coupon-btn-wrap, %1$s .coupone-code-outer-wrap {%2$s}', $this->wrapper_selector, $mStyle1 );
			}

			return '';
		}

		/**
		 * Get Button style
		 */
		public function get_btn_style() {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= 'mso-padding-alt: 10px 0px;text-align: center;';
			$style         .= bwf_css()->getBgColor( $this->settings, 'btnBgColor', 'desktop', 'buttonBackground', false, 'background:#0073aa;' );
			$style         .= bwf_css()->getBorderRadius( $this->settings, 'btnBorder', 'desktop', false, 'border-radius:3px;', 'buttonBorder' );
			$style         .= bwf_css()->getBorderWidth( $this->settings, 'btnBorder', 'desktop' );
			$style         .= bwf_css()->getBorderColor( $this->settings, 'btnBorder', 'desktop' );
			$style         .= bwf_css()->getBorderStyle( $this->settings, 'btnBorder', 'desktop' );
			$style         .= bwf_css()->getMsoPadding( $this->settings, 'btnPadding', 'desktop', false, '', 'buttonPadding' );

			return $style;
		}

		/**
		 * Get Buton text style
		 */
		public function get_btn_text_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = 'padding: 10px 0px;text-decoration:none;display:inline-block;mso-padding-alt:0;line-height:1.5;';
			$style         .= bwf_css()->getBgColor( $this->settings, 'btnBgColor', 'desktop', 'buttonBackground', false, 'background:#0073aa;' );
			$style         .= bwf_css()->getColor( $this->settings, 'btnTextColor', 'desktop', 'buttonColor', false, 'color: #ffffff;' );
			$style         .= $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'btnTextFont', 'desktop', 'buttonFont', 'font-size: 16px;' );
			$style         .= bwf_css()->getPadding( $this->settings, 'btnPadding', 'desktop', false, '', 'buttonPadding' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'btnTextFont', 'mobile', 'buttonFont' );
				$mStyle                 .= bwf_css()->getPadding( $this->settings, 'btnPadding', 'mobile' );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		/**
		 * Block default and common style
		 */
		private function get_default_style( $type = '' ) {
			$defaultStyle = [
				'font-size'  => 'font-size:16px;',
				'text-style' => 'margin:0;line-height: 1.5;' . bwf_css()->getFontFamily( $this->settings ),
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
BWFBE_WC_Coupon_Template::get_instance();