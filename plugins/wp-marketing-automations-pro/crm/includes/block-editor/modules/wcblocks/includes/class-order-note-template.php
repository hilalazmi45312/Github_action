<?php

if ( ! class_exists( 'BWFBE_WCOrder_Note_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_WCOrder_Note_Template {

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
			add_shortcode( 'bwfbe_order_note', [ $this, 'order_note_block' ] );
		}

		public function order_note_block( $atts, $content ) {
			$mergetagdata     = BWFAN_Merge_Tag_Loader::get_data();
			self::$is_preview = isset( $mergetagdata['is_preview'] ) && $mergetagdata['is_preview'];
			self::$order      = isset( $mergetagdata['order_id'] ) && ! empty( $mergetagdata['order_id'] ) ? wc_get_order( $mergetagdata['order_id'] ) : '';

			if ( ! self::$is_preview && ( ! self::$order instanceof WC_Order || empty( self::$order->get_customer_note() ) ) ) {
				return '';
			}
			do_action( 'bwfan_email_setup_locale', BWFAN_Common::get_order_language( self::$order ) );

			ob_start();

			$attributes = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );

			$defaults = [
				'padding'     => [
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
				'headingText' => '<strong>Customer Note</strong>',
				'uniqueID'    => '',
				'headingFont' => [
					'desktop' => [
						'size' => '18'
					]
				]
			];

			$this->settings = wp_parse_args( $attributes, $defaults );

			$this->settings['classname'] = [
				'bwf-email-order-note',
				'bwf-email-order-note-' . ( $attributes['uniqueID'] ?? '' )
			];

			$this->wrapper_selector = '.bwf-email-order-note.bwf-email-order-note-' . $this->settings['uniqueID'];

			$this->tableAttrs = 'cellpadding="0" cellspacing="0" role="presentation" border="0"';

			?>
            <tr>
                <td class="<?php echo implode( ' ', $this->settings['classname'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo $this->get_block_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <p class="order-note-heading" style="<?php echo $this->get_order_note_heading_style( '.bwf-email-order-note .order-note-heading' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
						<?php echo $this->settings['headingText']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                    </p>
                    <p class="order-note-content" style="<?php echo $this->get_content_style( '.bwf-email-order-note .order-note-content' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
						<?php
						if ( ! self::$is_preview && self::$order instanceof WC_Order ) {
							echo self::$order->get_customer_note(); //phpcs:ignore WordPress.Security.EscapeOutput
						} else {
							echo esc_html__( 'This is a sample note the real not will appear.', 'wp-marketing-automations-pro' );
						}
						?>
                    </p>

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


		/**
		 * Get Block Container style
		 */
		private function get_block_wrapper_style() {
			$style = 'word-break:break-word;';
			$style .= bwf_css()->getPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->getMsoPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->textAlign( $this->settings, 'alignment', 'desktop' );
			$style .= bwf_css()->getVisibilityCss( $this->settings, 'desktop' );


			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getPadding( $this->settings, 'padding', 'mobile', true );
				$mStyle                 .= bwf_css()->textAlign( $this->settings, 'alignment', 'mobile', true );
				$mStyle                 .= bwf_css()->getVisibilityCss( $this->settings, 'mobile' );
				$this->responsive_style .= sprintf( '%1$s {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}

		/**
		 * Get Block Container style
		 */
		private function get_order_note_heading_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = 'padding: 0px 0px 16px;mso-padding-alt: 0px 0px 16px;font-size:24px;margin:0;';
			$style         .= bwf_css()->getFontFamily( $this->settings );
			$style         .= bwf_css()->getFontSize( $this->settings, 'headingFont', 'desktop', false );
			$style         .= bwf_css()->getColor( $this->settings, 'headingColor', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'headingFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}

		private function get_content_style( $classname ) {
			$default_style = $this->get_default_style();
			$style         = 'margin:0;';
			$style         .= $default_style['font-size'];
			$style         .= bwf_css()->getFontFamily( $this->settings );
			$style         .= bwf_css()->getFontSize( $this->settings, 'font', 'desktop', 'font' );
			$style         .= bwf_css()->getColor( $this->settings, 'color', 'desktop' );
			$style         .= bwf_css()->getBold( $this->settings, 'contentBold', 'desktop' );
			$style         .= bwf_css()->getItalic( $this->settings, 'contentItalic', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'font', 'mobile', 'font' );
				$style                  .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
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
				'text-style' => 'margin:0;' . bwf_css()->getFontFamily( $this->settings ),
				'color'      => bwf_css()->getColor( $this->settings, 'color', 'desktop' )
			];

			if ( ! empty( $type ) && is_string( $type ) ) {
				return $defaultStyle[ $type ];
			}

			return $defaultStyle;
		}
	}
}
BWFBE_WCOrder_Note_Template::get_instance();