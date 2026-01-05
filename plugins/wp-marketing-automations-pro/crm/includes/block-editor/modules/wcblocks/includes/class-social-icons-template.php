<?php

if ( ! class_exists( 'BWFBE_Social_Icons_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_Social_Icons_Template {
		private static $instance;

		private $settings = [];

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_shortcode( 'bwfbe_social_icons', [ $this, 'social_icons_block' ] );
		}

		public function social_icons_block( $atts, $content ) {
			$attributes = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );

			$defaults = [
				'spacing' => [
					'desktop' => 8
				],
				'padding' => [
					'top'    => '10',
					'right'  => '10',
					'bottom' => '10',
					'left'   => '10',
					'unit'   => 'px',
				]
			];

			$block_editor_setting = BWFAN_Common::get_block_editor_settings();
			$icons                = $block_editor_setting['setting']['social'];
			$this->settings       = wp_parse_args( $attributes, $defaults );

			$assetsURL = 'https://d241ue3yrqqwem.cloudfront.net/';
			$size      = $this->settings['size'] . 'px';
			$spacing   = $this->settings['spacing']['desktop'];
			$type      = isset( $this->settings['type'] ) ? $this->settings['type'] : $block_editor_setting['setting']['socialType'];

			ob_start();
			foreach ( $icons as $index => $icon ) : ?>
                <!--[if mso | IE]><td style="width: <?php echo intval( $size ); ?>; height: <?php echo intval( $size ); ?>; padding-right: 0 !important;"><![endif]-->
                <table style="display: inline-block; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class='bwfbe-social-icon-wrap' style="<?php echo $this->getSpacing( $spacing ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
							<?php echo $this->getIconHtml( $icon, $assetsURL, $size, $type ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <!--[if mso | IE]></td><![endif]-->
			<?php endforeach;

			return BWFAN_Common::decode_merge_tags( ob_get_clean() );
		}

		private function getSpacing( $spacing ) {
			$align = 'center';
			switch ( $align ) {
				case 'left':
					return sprintf( 'padding-right: %1$spx; mso-padding-right: %1$spx;', $spacing );
				case 'right':
					return sprintf( 'padding-left: %1$spx; mso-padding-left: %1$spx;', $spacing );
				default:
					return sprintf( 'padding-left: %1$spx; mso-padding-left: %1$spx; padding-right: %1$spx; mso-padding-right: %1$spx;', $spacing / 2 );
			}
		}

		private function getIconHtml( $icon, $assetsURL, $size, $type ) {
			$iconType = $type ?? 'circle';
			$iconHtml = $this->createElement( 'img', [
				'src'    => $assetsURL . 'social-icons/' . $iconType . '/' . $icon['iconName'] . '.png',
				'width'  => $size,
				'height' => $size,
				'alt'    => $icon['iconName'],
			] );

			if ( ! empty( $icon['link'] ) ) {
				$iconHtml = $this->createElement( 'a', [
					'href'   => $icon['link'],
					'target' => '_blank',
				], $iconHtml );
			}

			return $iconHtml;
		}

		private function createElement( $tag, $attributes = [], $content = '' ) {
			$attributeString = '';
			foreach ( $attributes as $key => $value ) {
				$attributeString .= " {$key}='{$value}'";
			}
			$html = "<{$tag}{$attributeString}>{$content}</{$tag}>";

			return $html;
		}
	}
}
BWFBE_Social_Icons_Template::get_instance();
