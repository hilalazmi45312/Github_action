<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class VIJAGIF_WOO_FREE_GIFT_Frontend_Popup_Gift_Icon {
	protected $settings;
	protected $is_customize, $customize_data;
	public $functions;

	public function __construct() {
		$this->settings  = VIJAGIF_WOO_FREE_GIFT_DATA::get_instance();
		$this->functions = VIJAGIF_WOO_FREE_GIFT_Function::get_instance();
		$enable          = $this->settings->get_params( 'enable' );
		if ( ! empty( $enable ) && $enable == 1 ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'jagif_wp_enqueue_scripts' ), 9 );
			add_action( 'jagif_get_popup_gift_icon', array( $this, 'get_popup_gift_icon' ) );
		}
	}

	public function jagif_wp_enqueue_scripts() {
	    if ( is_customize_preview() ) {
		    wp_enqueue_style( 'jagif-gift-icons', VIJAGIF_WOO_FREE_GIFT_CSS . 'gift-icons.min.css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );
        }
		if ( ! is_product() || is_shop() ) {
		    if ( empty( $this->settings->get_params( 'icon_image' ) ) ) {
                wp_enqueue_style( 'jagif-gift-icons', VIJAGIF_WOO_FREE_GIFT_CSS . 'gift-icons.min.css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );
		    }
			return;
		}
		$this->is_customize = is_customize_preview();
		if ( $this->is_customize ) {
			global $wp_customize;
			$this->customize_data = $wp_customize;
		}
		wp_enqueue_style( 'jagif-gift-icons', VIJAGIF_WOO_FREE_GIFT_CSS . 'gift-icons.min.css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );
		$suffix = WP_DEBUG ? '' : 'min.';
		wp_enqueue_style( 'jagif-popup-gift-icon', VIJAGIF_WOO_FREE_GIFT_CSS . 'jagif-popup-gift-icon.' . $suffix . 'css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );
		if ( is_product() && ! $this->is_customize ) {
			$css = $this->get_inline_css();
			wp_add_inline_style( 'jagif-popup-gift-icon', $css );
		}
		if ( is_product() ) {
			add_action( 'wp_footer', array( $this, 'frontend_html' ) );
		}
	}

	public function frontend_html() {
		$product_id          = wc_get_product()->get_id();
		$scan_gift_items = $this->functions->scan_rule( 'all', $product_id, 1 );
		$scan_gift_items = VIJAGIF_HELPER::jagif_get_single_conditions( $scan_gift_items );

		$check_gift          = ! empty( $scan_gift_items ) || $this->get_params( 'overall_notice' ) ? 'jagif-popup-is-product-gift' : '';
		$gb_display_style    = $this->get_params( 'gb_display_style' );
		$pg_enable_auto_show = $this->get_params( 'pg_enable_auto_show' );
		$class               = array(
			'jagif-popup-gift-icon-wrap',
			'jagif-popup-gift-icon-wrap-' . $this->get_params( 'pg_position' ),
			'jagif-popup-gift-icon-wrap-' . $pg_trigger_type = $this->get_params( 'pg_trigger_type' ),
			$gb_display_style != 2 || $check_gift == '' ? 'jagif-disabled' : '',
			$check_gift,
		);
		if ( ! $this->is_customize ) {
			$class[] = $pg_enable_auto_show == 1 && $gb_display_style == 2 ? 'jagif-popup-auto-show-enable' : '';
		}
		$class = trim( implode( ' ', $class ) );
		?>
        <div class="<?php echo esc_attr( $class ); ?>"
             data-position="<?php echo esc_attr( $this->get_params( 'pg_position' ) ); ?>"
             data-old_position=""
             data-trigger="<?php echo esc_attr( $pg_trigger_type ); ?>">
			<?php
			do_action( 'jagif_get_popup_gift_icon', $scan_gift_items );
			?>
        </div>
		<?php
	}

	public function get_popup_gift_icon( $get_gift_items ) {
		$pg_icon_style        = $this->get_params( 'pg_icon_style' );
		$pg_icon_default_icon = $this->get_params( 'pg_icon' );
		$icon_class           = $this->settings->get_class_icon( $pg_icon_default_icon, 'gift_icons' );
		$wrap_class           = array(
			'jagif-popup-gift-icon',
			'jagif-popup-gift-icon-' . $pg_icon_style,
		);
		$get_gift_item        = 0;
		$wrap_class           = trim( implode( ' ', $wrap_class ) );
		$product_id           = wc_get_product()->get_id();
		$get_gift_items = $this->functions->scan_rule( 'all', $product_id, 1 );
		$get_gift_items = VIJAGIF_HELPER::jagif_get_single_conditions( $get_gift_items );
        if ( isset( $get_gift_items ) && ! empty( $get_gift_items ) && is_array( $get_gift_items )) {
            foreach ( $get_gift_items as $rule_item ) {
	            if ( isset( $rule_item['gift_id'] ) && isset( $rule_item['rule_id'] ) && ! empty( $rule_item['gift_id'] ) && is_array( $rule_item['gift_id'] )) {
	                if ( $rule_item['rule_id'] == 'single' || $rule_item['rule_id'] == '' ) {
		                foreach ( $rule_item['gift_id'] as $pack_item ) {
			                $get_gift_item += count( $pack_item );
		                }
	                }
	            }
            }
        }
		switch ( $pg_icon_style ) {
			case '1':
			case '2':
			case '3':
				?>
                <div class="<?php echo esc_attr( $wrap_class ); ?>"
                     data-display_style="<?php echo esc_attr( $pg_icon_style ); ?>">
                    <i class="<?php echo esc_attr( $icon_class ); ?>"></i>
                    <div class="jagif-popup-gift-count-wrap">
                        <div class="jagif-popup-gift-count">
							<?php echo esc_html(  $get_gift_item ); ?>
                        </div>
                    </div>
                </div>
				<?php
				break;
			default:
				?>
                <div class="<?php echo esc_attr( $wrap_class ); ?>">
                    <i class="<?php echo esc_attr( $icon_class ); ?>"></i>
                </div>
			<?php
		}
	}

	public function get_inline_css() {
		$css           = '';
		$frontend      = 'VIJAGIF_WOO_FREE_GIFT_Frontend_Frontend';
		$pg_horizontal = $this->settings->get_params( 'pg_horizontal' ) ? $this->settings->get_params( 'pg_horizontal' ) : 0;
		$css           .= '.jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap-bottom_left{';
		$css           .= 'left: ' . $pg_horizontal . 'px ;';
		$css           .= '}';
		$css           .= '.jagif-popup-gift-icon-wrap-top_right, .jagif-popup-gift-icon-wrap-bottom_right{';
		$css           .= 'right: ' . $pg_horizontal . 'px ;';
		$css           .= '}';
		$pg_vertical   = $this->settings->get_params( 'pg_vertical' ) ? $this->settings->get_params( 'pg_vertical' ) : 0;
		$css           .= '.jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap-top_right{';
		$css           .= 'top: ' . $pg_vertical . 'px ;';
		$css           .= '}';
		$css           .= '.jagif-popup-gift-icon-wrap-bottom_right, .jagif-popup-gift-icon-wrap-bottom_left{';
		$css           .= 'bottom: ' . $pg_vertical . 'px ;';
		$css           .= '}';

		if ( $this->settings->get_params( 'pg_icon_box_shadow' ) ) {
			$css .= '.jagif-popup-gift-icon-wrap{
                box-shadow: inset 0 0 2px rgba(0,0,0,0.03), 0 4px 10px rgba(0,0,0,0.17);
            }';
		}
		if ( $pg_icon_scale = $this->settings->get_params( 'pg_icon_scale' ) ) {
			$css .= '.jagif-popup-gift-icon-wrap {
                transform: scale(' . $pg_icon_scale . ') ;
            }
            @keyframes jagif-gift-icon-slide_in_left {
                from {
                    transform: translate3d(-100%, 0, 0) scale(' . $pg_icon_scale . ');
                    visibility: hidden;
                }
                to {
                    transform: translate3d(0, 0, 0) scale(' . $pg_icon_scale . ');
                }
            }
            @keyframes jagif-gift-icon-slide_out_left {
                from {
                    transform: translate3d(0, 0, 0) scale(' . $pg_icon_scale . ');
                    visibility: visible;
                    opacity: 1;
                }
                to {
                    transform: translate3d(-100%, 0, 0) scale(' . $pg_icon_scale . ');
                    visibility: hidden;
                    opacity: 0;
                }
            }';
		}
		if ( $pg_icon_hover_scale = $this->settings->get_params( 'pg_icon_hover_scale' ) ) {
			$css .= '@keyframes jagif-gift-icon-mouseenter {
                from {
                    transform: translate3d(0, 0, 0) scale(' . $pg_icon_scale . ');
                }
                to {
                    transform: translate3d(0, 0, 0) scale(' . $pg_icon_hover_scale . ');
                }
            }
            @keyframes jagif-gift-icon-mouseleave {
                from {
                    transform: translate3d(0, 0, 0) scale(' . $pg_icon_hover_scale . ');
                }
                to {
                    transform: translate3d(0, 0, 0) scale(' . $pg_icon_scale . ');
                }
            }
            @keyframes jagif-gift-icon-slide_out_left {
                from {
                    transform: translate3d(0, 0, 0) scale(' . $pg_icon_hover_scale . ');
                    visibility: visible;
                    opacity: 1;
                }
                to {
                    transform: translate3d(-100%, 0, 0) scale(' . $pg_icon_hover_scale . ');
                    visibility: hidden;
                    opacity: 0;
                }
            }
            @keyframes jagif-gift-icon-slide_out_right {
                from {
                    transform: translate3d(0, 0, 0) scale(' . $pg_icon_hover_scale . ');
                    visibility: visible;
                    opacity: 1;
                }
                to {
                    transform: translate3d(100%, 0, 0) scale(' . $pg_icon_hover_scale . ');
                    visibility: hidden;
                    opacity: 0;
                }
            }';
		}
		$css .= $frontend::add_inline_style(
			array( '.jagif-popup-gift-icon-wrap' ),
			array( 'pg_icon_border_radius', 'pg_icon_bg' ),
			array( 'border-radius', 'background' ),
			array( 'px', '' )
		);
		$css .= $frontend::add_inline_style(
			array( '.jagif-popup-gift-icon-wrap .jagif-popup-gift-icon i' ),
			array( 'pg_icon_color' ),
			array( 'color' ),
			array( '' )
		);
		$css .= $frontend::add_inline_style(
			array( '.jagif-popup-gift-icon-wrap .jagif-popup-gift-count-wrap' ),
			array( 'pg_icon_count_bg_color', 'pg_icon_count_color' ),
			array( 'background', 'color' ),
			array( '', '' )
		);
		$css = str_replace( array( "\r", "\n", "\t", '\r', '\n', '\t' ), ' ', $css );

		return $css;
	}

	private function get_params( $name = '' ) {
		if ( $this->customize_data && $name && $setting = $this->customize_data->get_setting( 'jagif_woo_free_gift_params[' . $name . ']' ) ) {
			return $this->customize_data->post_value( $setting, $this->settings->get_params( $name ) );
		} else {
			return $this->settings->get_params( $name );
		}
	}
}