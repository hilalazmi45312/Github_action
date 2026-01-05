<?php

/**
 * Block editor Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BWFCRM_Block_Editor class
 */
class BWFCRM_Block_Editor {

	/**
	 * Email editor global settings data
	 * @assign value by BWFCRM_Block_Editor::get_prepared_email_data
	 */
	public static $global_settings = [];

	/**
	 * Email editor global var settings data
	 * @var array
	 */
	public static $global_settings_var = [];

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->load_modules();
		self::set_global_css_variables();
		add_shortcode( 'bwfbe_email_block_visibility', [ $this, 'bwfan_email_block_visibility' ] );
		add_shortcode( 'bwfan_email_block_visibility', [ $this, 'bwfan_email_block_visibility' ] );
	}

	/**
	 * Validates block editor rules and returns blocks content
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return mixed|string
	 */
	public function bwfan_email_block_visibility( $atts, $content ) {
		$rules = [];
		if ( isset( $atts['rules'] ) ) {
			$rules = json_decode( mb_convert_encoding( urldecode( $atts['rules'] ), 'ISO-8859-1', 'UTF-8' ), true );
		}

		if ( ! class_exists( 'BWFAN_Generic_Rule_Controller' ) ) {
			return BWFAN_Common::decode_merge_tags( $content );
		}
		$generic_rule_ins = new BWFAN_Generic_Rule_Controller( $rules );

		if ( $generic_rule_ins->is_match() ) {
			// Check for conditional content includes block shortcodes
			$content2 = urldecode( str_replace( "%u", "\u", $content ) );
			$pattern = '/\\[bwfbe_.*?\\](.*?)\\[\\/bwfbe_.*?\\]/s';
			preg_match_all( $pattern, $content2, $closing_merge_tags );
			$closetags = $closing_merge_tags[0] ?? [];

			// If conditional content includes block shortcodes do conditional decoding
			if( ! empty( $closetags ) ) {
				$new_content = self::handle_conditional_decoding( $content );	
			} else { // If conditional content does not include block shortcodes do normal decoding
				$new_content = self::decode_content( $content, true );
			}

			// fallback if decoding fails
			if ( empty( $new_content ) ) {
				$new_content = self::decode_content( $content, true );
			}

			$content = html_entity_decode( $new_content );
			$content = method_exists( 'BWFAN_Common', 'correct_shortcode_string' ) ? BWFAN_Common::correct_shortcode_string( $content, 5 ) : $content;
			$content = do_shortcode( $content );
			$content = BWFAN_Common::decode_merge_tags( $content );

			return $content;
		}

		return '';
	}

	/**
	 * Load blocks data
	 *
	 * @return void
	 */
	public function load_modules() {
		if( ! class_exists( 'BWF_EMAILBLOCKS_CSS' ) ) {
			require_once BWFAN_PRO_PLUGIN_DIR . '/crm/includes/block-editor/email-editor/class-css-functions.php';
		}
		if ( bwfan_is_woocommerce_active() && ! class_exists( 'BWFBE_WC_BLOCKS' ) ) {
			require_once BWFAN_PRO_PLUGIN_DIR . '/crm/includes/block-editor/modules/wcblocks/wc-blocks.php';
		}
	}

	public static function get_product_placeholder() {
		return BWFAN_PRO_PLUGIN_URL . '/crm/includes/block-editor/img/placeholder.png';
	}

	/**
	 * Handle block editor attributes
	 *
	 * @param $content
	 * @param $decode
	 * @param $is_attribute
	 *
	 * @return array|false|mixed|string|string[]|null
	 */
	public static function decode_content( $content = '', $decode = false, $is_attribute = true ) {
		$content = $is_attribute ? urldecode( str_replace( "%u", "\u", $content ) ) : urldecode( $content );

		// for decoding utf-16 character
		$content = preg_replace_callback( '/%u([0-9A-F]{4})%u([0-9A-F]{4})/', function ( $matches ) {
			$surrogate1 = hexdec( $matches[1] );
			$surrogate2 = hexdec( $matches[2] );
			if ( $surrogate1 >= 0xD800 && $surrogate1 <= 0xDBFF && $surrogate2 >= 0xDC00 && $surrogate2 <= 0xDFFF ) {
				$codePoint = 0x10000 + ( ( $surrogate1 - 0xD800 ) << 10 ) + ( $surrogate2 - 0xDC00 );

				return mb_convert_encoding( pack( 'N', $codePoint ), 'UTF-8', 'UTF-32BE' );
			} else {
				return '';
			}
		}, $content );

		// decode unicode
		$content = preg_replace_callback( "/%u([0-9a-fA-F]{4})/", function ( $match ) {
			return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
		}, $content );

		if ( mb_detect_encoding($content, 'UTF-8', true ) !== 'UTF-8' ) {
			$content = mb_convert_encoding( $content, 'UTF-8', 'ISO-8859-1' );
		}

		return $decode ? json_decode( $content ) : $content;
	}

	/**
	 * Handle conditional decoding
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	public static function handle_conditional_decoding( $content ) {
		// decode unicode
		$content = preg_replace_callback( "/%u([0-9a-fA-F]{4})/", function ( $match ) {
			return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
		}, $content );

		// for decoding utf-16 character
		$content = preg_replace_callback( '/%u([0-9A-F]{4})%u([0-9A-F]{4})/', function ( $matches ) {
			$surrogate1 = hexdec( $matches[1] );
			$surrogate2 = hexdec( $matches[2] );
			if ( $surrogate1 >= 0xD800 && $surrogate1 <= 0xDBFF && $surrogate2 >= 0xDC00 && $surrogate2 <= 0xDFFF ) {
				$codePoint = 0x10000 + ( ( $surrogate1 - 0xD800 ) << 10 ) + ( $surrogate2 - 0xDC00 );

				return mb_convert_encoding( pack( 'N', $codePoint ), 'UTF-8', 'UTF-32BE' );
			} else {
				return '';
			}
		}, $content );


		if ( mb_detect_encoding($content, 'UTF-8', true ) !== 'UTF-8' ) {
			$content = mb_convert_encoding( $content, 'UTF-8', 'ISO-8859-1' );
		}

		$content = urldecode( str_replace( "%u", "\u", $content ) );

		return json_decode( $content );
	}

	public static function getGlobalConstValue( $settings = [], $attr = '', $screen = 'desktop', $subattr = '' ) {
		if ( empty( $settings ) || empty( $attr ) ) {
			return '';
		}
		$exists = isset( $settings[ $attr ][ $screen ] ) ? $settings[ $attr ][ $screen ] : [];
		if ( ! $exists ) {
			return '';
		}

		switch ( $attr ) {
			case 'background':
			case 'contentBackground':
			case 'buttonBackground':
				return isset( $exists['color'] ) ? $exists['color'] : '';
			case 'width':
				$unit = isset( $exists['unit'] ) ? $exists['unit'] : 'px';

				return isset( $exists['value'] ) ? $exists['value'] . $unit : '';
			case 'color':
			case 'linkColor':
			case 'buttonColor':
			case 'linkLineType':
			case 'align':
				return $exists;
			case 'buttonFont':
			case 'font':
			case 'fontH1':
			case 'fontH2':
			case 'fontH3':
			case 'fontH4':
			case 'fontH5':
			case 'fontH6':
			case 'backupFont':
				if ( ! empty( $subattr ) ) {
					$unit = $subattr === 'size' ? 'px' : '';

					return isset( $exists[ $subattr ] ) ? $exists[ $subattr ] . $unit : '';
				}

				return '';
			case 'buttonSize':
				if ( isset( $settings['buttonAuto'] ) && isset( $settings['buttonAuto'][ $screen ] ) && $settings['buttonAuto'][ $screen ] ) {
					return 'auto';
				}

				return isset( $exists['value'] ) ? $exists['value'] . '%' : '';
			case 'buttonBorder':
				$default = '';

				$borderSetting = isset( $settings['buttonBorder'][ $screen ] ) ? $settings['buttonBorder'][ $screen ] : [
					'top_left'     => '8',
					'top_right'    => '8',
					'bottom_left'  => '8',
					'bottom_right' => '8',
					'radius_unit'  => 'px',
				];

				$getValue = function ( $attributeKey ) use ( $borderSetting ) {
					return isset( $borderSetting[ $attributeKey ] ) && $borderSetting[ $attributeKey ] !== '' ? $borderSetting[ $attributeKey ] : null;
				};

				$topLeft     = $getValue( 'top-left' );
				$topRight    = $getValue( 'top-right' );
				$bottomLeft  = $getValue( 'bottom-left' );
				$bottomRight = $getValue( 'bottom-right' );
				$unit        = isset( $borderSetting['radius_unit'] ) ? $borderSetting['radius_unit'] : 'px';

				if ( $topLeft === null && $topRight === null && $bottomLeft === null && $bottomRight === null ) {
					return $default;
				}

				$borderRadius = sprintf( '%s%s %s%s %s%s %s%s', $topLeft !== null ? $topLeft : '8', $unit, $topRight !== null ? $topRight : '8', $unit, $bottomRight !== null ? $bottomRight : '8', $unit, $bottomLeft !== null ? $bottomLeft : '8', $unit );

				return $borderRadius;
			case 'buttonPadding':
				$default = '';

				$borderSetting = isset( $settings['buttonPadding'][ $screen ] ) ? $settings['buttonPadding'][ $screen ] : [];

				$getValue = function ( $attributeKey ) use ( $borderSetting ) {
					return isset( $borderSetting[ $attributeKey ] ) && $borderSetting[ $attributeKey ] !== '' ? $borderSetting[ $attributeKey ] : null;
				};

				$top    = $getValue( 'top' );
				$right  = $getValue( 'right' );
				$bottom = $getValue( 'bottom' );
				$left   = $getValue( 'left' );
				$unit   = isset( $borderSetting['unit'] ) ? $borderSetting['unit'] : 'px';

				if ( $top === null && $right === null && $bottom === null && $left === null ) {
					return $default;
				}

				$padding = sprintf( '%s%s %s%s %s%s %s%s', $top !== null ? $top : '0', $unit, $right !== null ? $right : '0', $unit, $bottom !== null ? $bottom : '0', $unit, $left !== null ? $left : '0', $unit );

				return $padding;
			default:
				return '';
		}
	}

	public static function set_global_css_variables() {
		$setting = BWFAN_Common::get_block_editor_settings();

		if ( ! isset( $setting['setting'] ) ) {
			return;
		}

		$global_settings       = $setting['setting'];
		self::$global_settings = $global_settings;
		$backupfont            = self::getGlobalConstValue( $global_settings, 'font', 'desktop', 'family' );

		self::$global_settings_var = [
			'var(--bwfbe-linkLineType-desktop)'            => self::getGlobalConstValue( $global_settings, 'linkLineType' ),
			'var(--bwfbe-linkColor-desktop)'               => self::getGlobalConstValue( $global_settings, 'linkColor' ),
			'var(--bwfbe-font-desktop-family)'             => self::getGlobalConstValue( $global_settings, 'font', 'desktop', 'family' ) . ( ! empty( $backupfont ) ? ",$backupfont" : '' ),
			'var(--bwfbe-font-desktop-size)'               => self::getGlobalConstValue( $global_settings, 'font', 'desktop', 'size' ),
			'var(--bwfbe-color-desktop)'                   => self::getGlobalConstValue( $global_settings, 'color' ),
			'var(--bwfbe-buttonColor-desktop)'             => self::getGlobalConstValue( $global_settings, 'buttonColor' ),
			'var(--bwfbe-buttonBackground-desktop)'        => self::getGlobalConstValue( $global_settings, 'buttonBackground' ),
			'var(--bwfbe-buttonFont-desktop-size)'         => self::getGlobalConstValue( $global_settings, 'buttonFont', 'desktop', 'size' ),
			'var(--bwfbe-width-desktop-value)'             => self::getGlobalConstValue( $global_settings, 'width' ),
			'var(--bwfbe-align-desktop)'                   => self::getGlobalConstValue( $global_settings, 'align' ),
			'var(--bwfbe-background-desktop-color)'        => self::getGlobalConstValue( $global_settings, 'background' ),
			'var(--bwfbe-contentBackground-desktop-color)' => self::getGlobalConstValue( $global_settings, 'contentBackground' ),
			'var(--bwfbe-buttonWidth-desktop-value)'       => self::getGlobalConstValue( $global_settings, 'buttonSize', 'desktop' ),
			'var(--bwfbe-buttonBorder-desktop-radius)'     => self::getGlobalConstValue( $global_settings, 'buttonBorder', 'desktop' ),
			'var(--bwfbe-buttonPadding-desktop)'           => self::getGlobalConstValue( $global_settings, 'buttonPadding', 'desktop' ),
			'var(--bwfbe-fontH1-desktop-size)'             => self::getGlobalConstValue( $global_settings, 'fontH1', 'desktop', 'size' ),
			'var(--bwfbe-fontH3-desktop-size)'             => self::getGlobalConstValue( $global_settings, 'fontH3', 'desktop', 'size' ),
			'var(--bwfbe-fontH4-desktop-size)'             => self::getGlobalConstValue( $global_settings, 'fontH4', 'desktop', 'size' ),
			'var(--bwfbe-fontH5-desktop-size)'             => self::getGlobalConstValue( $global_settings, 'fontH5', 'desktop', 'size' ),
			'var(--bwfbe-fontH2-desktop-size)'             => self::getGlobalConstValue( $global_settings, 'fontH2', 'desktop', 'size' ),
			'var(--bwfbe-fontH6-desktop-size)'             => self::getGlobalConstValue( $global_settings, 'fontH6', 'desktop', 'size' ),
		];
	}

	/**
	 * Decode specific block
	 */
	public static function decode_specific_blocks_before( $block_content = '', $shortcodes = [] ) {
		if ( empty( $block_content ) || empty( $shortcodes ) ) {
			return $block_content;
		}

		BWFAN_Merge_Tag_Loader::set_data( [
			'body_pre_decoding' => true,
		] );

		foreach ( $shortcodes as $codes ) {
			$pattern = '/\\[' . $codes . '.*?\\](.*?)\\[\\/' . $codes . '.*?\\]/s';
			preg_match_all( $pattern, $block_content, $closing_merge_tags );
			$closetags = $closing_merge_tags[0] ?? [];
			foreach ( $closetags as $s ) {
				$decode_val    = do_shortcode( $s );
				$block_content = str_ireplace( $s, $decode_val ?: '', $block_content );
			}
		}

		BWFAN_Merge_Tag_Loader::set_data( [
			'body_pre_decoding' => false,
		] );

		return $block_content;
	}

	/**
	 * Emogrify parsed output
	 *
	 * @param string $css
	 * @param string $email_body
	 * @param bool $return_dom
	 *
	 * @return string
	 */
	public static function emogrifier_parsed_output( $css, $email_body, $return_dom = false ) {
		if ( empty( $email_body ) || empty( $css ) ) {
			return $email_body;
		}

		if ( ! BWFAN_Common::supports_emogrifier() ) {
			$email_body = '<style type="text/css">' . $css . '</style>' . $email_body;

			return $email_body;
		}

		$emogrifier_class = '\\BWF_Pelago\\Emogrifier';
		if ( ! class_exists( $emogrifier_class ) ) {
			include_once BWFAN_PLUGIN_DIR . '/libraries/class-emogrifier.php';
		}
		try {
			/** @var \BWF_Pelago\Emogrifier $emogrifier */
			$emogrifier = new $emogrifier_class( $email_body, $css );
			if ( $return_dom ) {
				$email_body = $emogrifier->emogrify();
			} else {
				$email_body = $emogrifier->emogrifyBodyContent();
			}
		} catch ( Exception $e ) {
			BWFAN_Core()->logger->log( $e->getMessage(), 'send_email_emogrifier' );
		}

		return $email_body;
	}
}

new BWFCRM_Block_Editor();