<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIJAGIF_WOO_FREE_GIFT_DATA {
	private static $prefix;
	private $params, $default, $class_icons, $field_args;

	protected static $instance = null;

	public function __construct() {
		self::$prefix = 'jagif-';
		global $jagif_settings;
		if ( ! $jagif_settings ) {
			$jagif_settings = get_option( 'jagif_woo_free_gift_params', array() );
		}
		if ( isset( $jagif_settings['jagif_enable'] ) || isset( $jagif_settings['popup_content_display'] ) ) {
			$jagif_settings_t                             = array();
			$jagif_settings_t['enable']                   = $jagif_settings['jagif_enable'] ?? 1;
			$jagif_settings_t['disable_on_sale']          = $jagif_settings['disable_on_sale'] ?? 0;
			$jagif_settings_t['maximum_gift_items']       = $jagif_settings['maximum_gift_items'] ?? 5;
			$jagif_settings_t['max_gp_per_product']       = $jagif_settings['max_gp_per_product'] ?? 1;
			$jagif_settings_t['pg_enable']                = $jagif_settings['popup_gift_enable'] ?? '';
			$jagif_settings_t['pg_enable_auto_show']      = $jagif_settings['popup_enable_auto_show'] ?? 0;
			$jagif_settings_t['box_font_size']            = $jagif_settings['free_gift_font_size'] ?? 14;
			$jagif_settings_t['pg_display_type']          = $jagif_settings['popup_content_display'] ?? 1;
			$jagif_settings_t['pg_position']              = $jagif_settings['popup_position'] ?? 'bottom_right';
			$jagif_settings_t['gb_display_style']         = $jagif_settings['free_gift_display_style'] ?? 0;
			$jagif_settings_t['show_gift_style']          = $jagif_settings['show_gift_style'] ?? 0;
			$jagif_settings_t['pg_horizontal']            = $jagif_settings['popup_horizontal'] ?? 20;
			$jagif_settings_t['title_box_color']          = $jagif_settings['title_box_color'] ?? '#515151';
			$jagif_settings_t['gift_name_color']          = $jagif_settings['gift_name_color'] ?? '#515151';
			$jagif_settings_t['gift_name_hover_color']    = $jagif_settings['gift_name_hover_color'] ?? '#000000';
			$jagif_settings_t['ic_size']                  = $jagif_settings['icon_size'] ?? 35;
			$jagif_settings_t['ic_color']                 = $jagif_settings['ic_color'] ?? '#000';
			$jagif_settings_t['ic_background']            = $jagif_settings['ic_background'] ?? '';
			$jagif_settings_t['ic_enable_shop']           = $jagif_settings['ic_enable_shop'] ?? 1;
			$jagif_settings_t['ic_enable_single_product'] = $jagif_settings['ic_enable_single_product'] ?? 1;
			$jagif_settings_t['ic_position']              = $jagif_settings['ic_position'] ?? 0;
			$jagif_settings_t['ic_vertical']              = $jagif_settings['icon_vertical'] ?? 35;
			$jagif_settings_t['ic_horizontal']            = $jagif_settings['ic_horizontal'] ?? 0;
			$jagif_settings_t['price_in_cart']            = $jagif_settings['price_in_cart'] ?? 0;
			$jagif_settings_t['pg_vertical']              = $jagif_settings['popup_vertical'] ?? 20;
			$jagif_settings_t['box_title']                = $jagif_settings['box_title'] ? $jagif_settings['box_title'] : 'YOUR GIFT';
			$jagif_settings_t['pg_trigger_type']          = $jagif_settings['popup_show_gift_type'] ?? 'click';
			$jagif_settings_t['pg_icon_style']            = $jagif_settings['popup_gift_icon_default_style'] ?? 1;
			$jagif_settings_t['pg_icon_box_shadow']       = $jagif_settings['popup_gift_icon_box_shadow'] ?? 1;
			$jagif_settings_t['pg_icon_scale']            = $jagif_settings['popup_gift_icon_scale'] ?? 1;
			$jagif_settings_t['pg_icon_hover_scale']      = $jagif_settings['popup_gift_icon_hover_scale'] ?? 1;
			$jagif_settings_t['pg_icon_border_radius']    = $jagif_settings['popup_gift_icon_radius'] ?? 30;
			$jagif_settings_t['icon_default']             = ! empty( $jagif_settings['gift_icon_default'] ) ? $jagif_settings['gift_icon_default'] - 1 : '';
			$jagif_settings_t['pg_icon']                  = ! empty( $jagif_settings['popup_gift_icon'] ) ? $jagif_settings['popup_gift_icon'] - 1 : 2;
			$jagif_settings_t['pg_icon_bg_color']         = $jagif_settings['popup_gift_icon_background'] ?? '#fff';
			$jagif_settings_t['pg_icon_color']            = $jagif_settings['popup_gift_icon_default_color'] ?? '#cc00ff';
			$jagif_settings_t['pg_icon_bg']               = $jagif_settings['popup_gift_icon_default_background'] ?? '#fff';
			$jagif_settings_t['pg_icon_count_color']      = $jagif_settings['pg_icon_count_color'] ?? '#fff';
			$jagif_settings_t['pg_icon_count_bg_color']   = $jagif_settings['pg_icon_count_bg_color'] ?? '#000000';
			$jagif_settings                               = $jagif_settings_t;
			update_option( 'jagif_woo_free_gift_params', $jagif_settings );
		}
		$gift              = array(
			'enable'                   => 1,
			'overall_notice'           => 0,
			'cart_notice'              => 1,
			'override_type'            => 'half',
			'disable_on_sale'          => 0,
			'maximum_gift_items'       => 5,
			'max_gp_per_product'       => 1,
			'gb_display_style'         => 0,
			'show_gift_style'          => 0,
			'jagif_update_key'         => '',
			'ic_enable_shop'           => 1,
			'ic_enable_single_product' => 1,
			'ic_position'              => 0,
			'icon_default'             => 15,
			'icon_image'               => '',
			'ic_vertical'              => 35,
			'ic_horizontal'            => 0,
			'box_font_size'            => 14,
			'ic_size'                  => 35,
			'ic_color'                 => '#000',
			'ic_background'            => '',
			'price_in_cart'            => 0,
			'title_box_color'          => '#515151',
			'gift_name_color'          => '#515151',
			'gift_name_hover_color'    => '#000000',
			//popup gift
			'pg_enable'                => 1,
			'pg_enable_auto_show'      => 0,
			'pg_assign_page'           => '',
			'box_title'                => 'YOUR GIFT',
			'pg_position'              => 'bottom_right',
			'pg_horizontal'            => 20,
			'pg_vertical'              => 150,
			'pg_display_type'          => 1,
			'pg_trigger_type'          => 'click',
			'pg_loading'               => 'default',
			'pg_icon_style'            => 1,
			'pg_icon_box_shadow'       => 1,
			'pg_icon_scale'            => 1,
			'pg_icon_hover_scale'      => 1,
			'pg_icon_border_radius'    => 30,
			'pg_icon'                  => 2,
			'pg_icon_bg_color'         => '#fff',
			'pg_icon_color'            => '#515151',
			'pg_icon_bg'               => '#fff',
			'pg_icon_count_color'      => '#fff',
			'pg_icon_count_bg_color'   => '#000000',
			'custom_css'               => '',
		);
		$this->default     = $gift;
		$this->params      = apply_filters( 'jagif_woo_free_gift_params', wp_parse_args( $jagif_settings, $this->default ) );
		$this->class_icons = array(
			'gift_icons'         => array(
				'jagif_gift_icon-1',
				'jagif_gift_icon-2',
				'jagif_gift_icon-3',
				'jagif_gift_icon-4',
				'jagif_gift_icon-5',
				'jagif_gift_icon-6',
				'jagif_gift_icon-7',
				'jagif_gift_icon-8',
				'jagif_gift_icon-9',
				'jagif_gift_icon-10',
				'jagif_gift_icon-11',
				'jagif_gift_icon-12',
				'jagif_gift_icon-13',
				'jagif_gift_icon-14',
				'jagif_gift_icon-15',
				'jagif_gift_icon-16',
				'jagif_gift_icon-17',
				'jagif_gift_icon-18',
				'jagif_gift_icon-19',
				'jagif_gift_icon-20',
				'jagif_gift_icon-21',
			),
			'icon_gift_position' => array(
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-left.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-right.svg',
			),
			'free_gift_icon'     => array(
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-1.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-2.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-3.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-4.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-5.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-6.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-7.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-8.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-9.svg',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-10.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-11.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-12.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-13.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-14.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-15.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-16.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-17.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-18.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-19.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-20.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-21.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-22.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-23.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-24.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-25.png',
				VIJAGIF_WOO_FREE_GIFT_IMAGES . 'icon-gift/gift-box-26.png',
			),
		);
	}

	public function enable( $prefix ) {
		if ( ! $prefix ) {
			return false;
		}
		if ( ! $this->get_params( $prefix . 'enable' ) ) {
			return false;
		}
		if ( wp_is_mobile() && ! $this->get_params( $prefix . 'mobile_enable' ) ) {
			return false;
		}

		return true;
	}

	public function get_class_icons( $type = '' ) {
		if ( ! $type ) {
			return $this->class_icons;
		}

		return $this->class_icons[ $type ] ?? array();
	}

	public function get_class_icon( $index = 0, $type = '' ) {
		if ( ! $type ) {
			return false;
		}
		$icons = $this->get_class_icons( $type ) ?? array();
		if ( empty( $icons ) ) {
			return false;
		} else {
			return $icons[ $index ] ?? $icons[0];
		}
	}

	public function get_params( $name = "" ) {
		if ( ! $name ) {
			return $this->params;
		} elseif ( isset( $this->params[ $name ] ) ) {
			return apply_filters( 'jagif_woo_free_gift_params_' . $name, $this->params[ $name ] );
		} else {
			return false;
		}
	}

	public function get_default( $name = "" ) {
		if ( ! $name ) {
			return $this->default;
		} elseif ( isset( $this->default[ $name ] ) ) {
			return apply_filters( 'jagif_woo_free_gift_params_default-' . $name, $this->default[ $name ] );
		} else {
			return false;
		}
	}

	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}