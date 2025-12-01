<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class VIJAGIF_WOO_FREE_GIFT_Frontend_Popup_Gift_Content {
	public static $settings, $cache, $function;
	protected $is_customize, $customize_data;
	protected static $instance = null;

	public function __construct() {
		self::$settings = VIJAGIF_WOO_FREE_GIFT_DATA::get_instance();
		self::$function = VIJAGIF_WOO_FREE_GIFT_Function::get_instance();
		$enable         = self::$settings->get_params( 'enable' );
		if ( ! empty( $enable ) && $enable == 1 ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'jagif_wp_enqueue_scripts' ) );
		}
	}

	public function jagif_wp_enqueue_scripts() {
		if ( is_checkout() || is_cart() ) {
			return;
		}
		$this->is_customize = is_customize_preview();
		if ( $this->is_customize ) {
			global $wp_customize;
			$this->customize_data = $wp_customize;
		}

		$suffix = WP_DEBUG ? '' : 'min.';
		wp_enqueue_style( 'jagif-popup-gift-content', VIJAGIF_WOO_FREE_GIFT_CSS . 'jagif-popup-gift-content.' . $suffix . 'css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );
		wp_enqueue_script( 'jagif-popup-gift', VIJAGIF_WOO_FREE_GIFT_JS . 'jagif-popup-gift.' . $suffix . 'js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, true );
		wp_enqueue_style( 'jagif-gift-icons', VIJAGIF_WOO_FREE_GIFT_CSS . 'gift-icons.min.css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );
		if ( ! $this->is_customize && is_product() ) {
			$args = array(
				'wc_ajax_url' => WC_AJAX::get_endpoint( "%%endpoint%%" ),
			);
			wp_localize_script( 'jagif-popup-gift', 'jagif_pg_params', $args );
			$css = $this->get_inline_css();
			wp_add_inline_style( 'jagif-popup-gift-content', $css );
			add_action( 'wp_footer', array( $this, 'frontend_html' ) );
		}
		if ( $this->is_customize && is_product() ) {
			add_action( 'wp_footer', array( $this, 'frontend_customize_html' ) );
		}
	}

	public function frontend_html() {
		$gb_display_style = self::$settings->get_params( 'gb_display_style' );
		$gb_style         = self::$settings->get_params( 'show_gift_style' );
		$enable_link_gift = self::$settings->get_params( 'enable_link_gift' );
		$pg_position      = self::$settings->get_params( 'pg_position' );
		$box_title        = self::$settings->get_params( 'box_title' );
		if ( $gb_display_style != 2 ) {
			return;
		}
		$class = trim( implode( ' ', array(
			'jagif-popup-gift-content-close',
			'jagif-popup-gift-content-wrap',
			'jagif-free-gift-wrap-position-2',
			'jagif-popup-gift-content-wrap-customize',
			'jagif-popup-gift-content-wrap-logged'
		) ) );

		$class_type_1 = trim( implode( ' ', array(
			$gb_display_style == 2 ? 'active' : ''
		) ) );

		$class_type_2 = trim( implode( ' ', array(
			$gb_style != 1 ? 'jagif-disabled' : '',
			$gb_style == 1 && $gb_display_style == 2 ? 'active' : '',
		) ) );

		$class1        = trim( implode( ' ', array(
			'jagif-popup-gift',
			'jagif-popup-gift-1',
			'jagif-popup-gift-' . $pg_position,
		) ) );
		$product_id    = wc_get_product()->get_id();
		$get_gift_item = self::$function->scan_rule( 'all', $product_id, 1 );
		$get_gift_item = VIJAGIF_HELPER::jagif_get_single_conditions( $get_gift_item );
		if ( isset( $get_gift_item ) && empty( $get_gift_item ) ) {
			return;
		}
		$check_cart = true;
		foreach ( $get_gift_item as $gift_key => $gift_item ) {
			if ( $gift_item['is_apply'] ) {
				if ( is_array( $gift_item['gift_id'] ) ) {
					foreach ( $gift_item['gift_id'] as $pack_id => $gift_pack ) {
						$qty_cart = self::$function->scan_qty_gift_in_cart( $pack_id, $gift_pack );
						if ( ! $qty_cart ) {
							$check_cart = false;
						}
					}
				}
			}
		}
		if ( ! $check_cart && ! self::$settings->get_params( 'overall_notice' ) ) {
			return;
		}
		?>
        <div class="jagif-popup-gift-wrap jagif-popup-<?php echo esc_attr( $product_id ) ?>" data-empty_enable="2"
             data-effect_after_atc="open"
             data-fly_to_cart="1">
            <div class="jagif-popup-gift-overlay jagif-disabled"></div>
            <div class="<?php echo esc_attr( $class1 ); ?>"
                 data-type="1"
                 data-old_position=""
                 data-position="<?php echo esc_attr( $pg_position ); ?>"
                 data-effect="<?php echo esc_attr( 'zoom' ); ?>">
                <div class="<?php echo esc_attr( $class ); ?>">
                    <span title="<?php esc_html_e( 'Close (Esc)', 'jagif-woo-free-gift' ); ?>" type="button"
                          class="jagif-free-gift-popup-close">
		                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="feather feather-x">
		                        <line x1="18" y1="6" x2="6" y2="18"></line>
		                        <line x1="6" y1="6" x2="18" y2="18"></line>
		                    </svg>
                    </span>
                    <div class="jagif-popup-slide_wrap" data-slide="0">
						<?php
						if ( count( $get_gift_item ) > 1 ) {
							?>
                            <div class="jagif-slide-left">
                                <span title="<?php esc_html_e( 'Previous', 'jagif-woo-free-gift' ); ?>" type="button" class="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="feather feather-x">
                                        <line x1="6" y1="12" x2="16" y2="4"></line>
                                        <line x1="6" y1="12" x2="16" y2="20"></line>
                                    </svg>
                                </span>
                            </div>
                            <div class="jagif-slide-right">
                                <span title="<?php esc_html_e( 'Next', 'jagif-woo-free-gift' ); ?>" type="button" class="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="feather feather-x">
                                        <line x1="8" y1="4" x2="18" y2="12"></line>
                                        <line x1="8" y1="20" x2="18" y2="12"></line>
                                    </svg>
                                </span></div>
							<?php
						}
						$slide_active = 0;
						foreach ( $get_gift_item as $gift_item ) {
							if ( $gift_item['is_apply'] ) {
								if ( ! $slide_active ) {
									$slide_active = 1;
								} else {
									$slide_active ++;
								}
								?>
                                <div class="jagif-free_gift_wrap jagif-gift-available jagif-gift-item-slide jagif-rule-<?php
								echo esc_attr( $gift_item['rule_id'] ) ?><?php if ( $slide_active == 1 )
									echo esc_attr( ' jagif-slide-item-active' ) ?>" id="jagif-free_gift_wrap"
                                     data-position="<?php echo esc_attr( $gb_display_style ) ?>"
                                     data-rule="<?php echo esc_attr( $gift_item['rule_id'] ) ?>">
                                    <div class="jagif-popup-gift-header-wrap">
                                        <div class="jagif-popup-gift-header-title-wrap">
                                            <div class="jagif-free-gift-promo_title jagif-popup-rule-title"><?php echo esc_html( $box_title ); ?></div>
                                        </div>
                                    </div>
									<?php
										wc_get_template( 'jagif-template-gift-content-1.php', array(
											'get_gift_item'    => $gift_item,
											'class_type_1'     => $class_type_1,
											'enable_link_gift' => $enable_link_gift,
										), '', VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
									?>
                                </div>
								<?php
							}
						}
						foreach ( $get_gift_item as $gift_item ) {
							if ( ! $gift_item['is_apply'] ) {
								if ( ! $slide_active ) {
									$slide_active = 1;
								} else {
									$slide_active ++;
								}
								?>
                                <div class="jagif-free_gift_wrap jagif-gift-not-available jagif-rule-<?php
								echo esc_attr( $gift_item['rule_id'] ) ?><?php if ( $slide_active == 1 )
									echo esc_attr( ' jagif-slide-item-active' ) ?> jagif-gift-item-slide"
                                     id="jagif-free_gift_wrap"
                                     data-position="<?php echo esc_attr( $gb_display_style ) ?>">
                                    <div class="jagif-popup-gift-header-wrap">
                                        <div class="jagif-popup-gift-header-title-wrap">
                                            <div class="jagif-free-gift-promo_title jagif-popup-rule-title"><?php echo esc_html( $box_title ); ?></div>
                                        </div>
                                    </div>
									<?php
										wc_get_template( 'jagif-template-gift-min.php', array(
											'get_gift_item'    => $gift_item,
											'class_type_1'     => $class_type_1,
											'enable_link_gift' => $enable_link_gift,
										), '', VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
									?>
                                </div>
								<?php
							}
						}
						?>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	public function frontend_customize_html() {
		$gb_display_style = self::$settings->get_params( 'gb_display_style' );
		$gb_style         = self::$settings->get_params( 'show_gift_style' );
		$enable_link_gift = self::$settings->get_params( 'enable_link_gift' );
		$pg_position      = self::$settings->get_params( 'pg_position' );
		$box_title        = self::$settings->get_params( 'box_title' );

		$class = trim( implode( ' ', array(
			'jagif-popup-gift-content-close',
			'jagif-popup-gift-content-wrap',
			'jagif-free-gift-wrap-position-2',
			'jagif-popup-gift-content-wrap-customize',
			'jagif-popup-gift-content-wrap-logged'
		) ) );

		$class_type_1 = trim( implode( ' ', array(
			$gb_display_style == 2 ? 'active' : ''
		) ) );

		$class_type_2 = trim( implode( ' ', array(
			$gb_style != 1 ? 'jagif-disabled' : '',
			$gb_style == 1 && $gb_display_style == 2 ? 'active' : '',
		) ) );

		$class1        = trim( implode( ' ', array(
			'jagif-popup-gift',
			'jagif-popup-gift-1',
			'jagif-popup-gift-' . $pg_position,
		) ) );
		$product_id    = wc_get_product()->get_id();
		$get_gift_item = self::$function->scan_rule( 'all', $product_id, 1 );
		if ( isset( $get_gift_item ) && empty( $get_gift_item ) && ! is_array( $get_gift_item ) ) {
			return;
		}
		$check_cart = true;
		foreach ( $get_gift_item as $gift_item ) {
			if ( $gift_item['is_apply'] ) {
				if ( is_array( $gift_item['gift_id'] ) ) {
					foreach ( $gift_item['gift_id'] as $pack_id => $gift_pack ) {
						$qty_cart = self::$function->scan_qty_gift_in_cart( $pack_id, $gift_pack );
						if ( ! $qty_cart ) {
							$check_cart = false;
						}
					}
				}
			}
		}
		if ( ! $check_cart ) {
			return;
		}
		?>
        <div class="jagif-popup-gift-wrap" data-empty_enable="2"
             data-effect_after_atc="open"
             data-fly_to_cart="1">
            <div class="jagif-popup-gift-overlay jagif-disabled"></div>
            <div class="<?php echo esc_attr( $class1 ); ?>"
                 data-type="1"
                 data-old_position=""
                 data-position="<?php echo esc_attr( $pg_position ); ?>"
                 data-effect="<?php echo esc_attr( 'zoom' ); ?>">
                <div class="<?php echo esc_attr( $class ); ?>">
                    <span title="<?php esc_html_e( 'Close (Esc)', 'jagif-woo-free-gift' ); ?>" type="button"
                          class="jagif-free-gift-popup-close">
		                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="feather feather-x">
		                        <line x1="18" y1="6" x2="6" y2="18"></line>
		                        <line x1="6" y1="6" x2="18" y2="18"></line>
		                    </svg>
                    </span>
                    <div class="jagif-popup-slide_wrap">
						<?php
						if ( count( $get_gift_item ) > 1 ) {
							?>
                            <div class="jagif-slide-left">
                                <span title="<?php esc_html_e( 'Previous', 'jagif-woo-free-gift' ); ?>" type="button" class="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="feather feather-x">
                                        <line x1="6" y1="12" x2="16" y2="4"></line>
                                        <line x1="6" y1="12" x2="16" y2="20"></line>
                                    </svg>
                                </span>
                            </div>
                            <div class="jagif-slide-right">
                                <span title="<?php esc_html_e( 'Next', 'jagif-woo-free-gift' ); ?>" type="button" class="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="feather feather-x">
                                        <line x1="8" y1="4" x2="18" y2="12"></line>
                                        <line x1="8" y1="20" x2="18" y2="12"></line>
                                    </svg>
                                </span>
                            </div>
							<?php
						}
						$slide_active = 0;
						foreach ( $get_gift_item as $gift_item ) {
							if ( $gift_item['is_apply'] ) {
								if ( ! $slide_active ) {
									$slide_active = 1;
								} else {
									$slide_active ++;
								}
								?>
                                <div class="jagif-free_gift_wrap jagif-gift-item-slide jagif-rule-<?php
								echo esc_attr( $gift_item['rule_id'] ) ?><?php if ( $slide_active == 1 )
									echo esc_attr( ' jagif-slide-item-active' ) ?>"
                                     id="jagif-free_gift_wrap"
                                     data-position="<?php echo esc_attr( $gb_display_style ) ?>">
                                    <div class="jagif-popup-gift-header-wrap">
                                        <div class="jagif-popup-gift-header-title-wrap">
                                            <div class="jagif-free-gift-promo_title jagif-popup-rule-title"><?php echo esc_html( $box_title ); ?></div>
                                        </div>
                                    </div>
									<?php
										wc_get_template( 'jagif-template-gift-content-1.php', array(
											'get_gift_item'    => $gift_item,
											'class_type_1'     => $class_type_1,
											'enable_link_gift' => $enable_link_gift,
										), '', VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
									?>
                                </div>
								<?php
							}
						}
						foreach ( $get_gift_item as $gift_item ) {
							if ( ! $gift_item['is_apply'] ) {
								if ( ! $slide_active ) {
									$slide_active = 1;
								} else {
									$slide_active ++;
								}
								?>
                                <div class="jagif-free_gift_wrap jagif-gift-not-available jagif-rule-<?php
								echo esc_attr( $gift_item['rule_id'] ) ?><?php if ( $slide_active == 1 )
									echo esc_attr( ' jagif-slide-item-active' ) ?> jagif-gift-item-slide"
                                     id="jagif-free_gift_wrap"
                                     data-position="<?php echo esc_attr( $gb_display_style ) ?>">
                                    <div class="jagif-popup-gift-header-wrap">
                                        <div class="jagif-popup-gift-header-title-wrap">
                                            <div class="jagif-free-gift-promo_title jagif-popup-rule-title"><?php echo esc_html( $box_title ); ?></div>
                                        </div>
                                    </div>
                                    <?php
                                    wc_get_template( 'jagif-template-gift-min.php', array(
                                        'get_gift_item'    => $gift_item,
                                        'class_type_1'     => $class_type_1,
                                        'enable_link_gift' => $enable_link_gift,
                                    ), '', VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
									?>
                                </div>
								<?php
							}
						}
						?>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	public function get_inline_css() {
		$css      = '';
		$frontend = 'VIJAGIF_WOO_FREE_GIFT_Frontend_Frontend';
		if ( $sc_horizontal = self::$settings->get_params( 'pg_horizontal' ) ) {
			$sc_horizontal_mobile = $sc_horizontal > 20 ? 20 - $sc_horizontal : 0;
			$css                  .= '.jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-top_left,
            .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-bottom_left{
                left: ' . $sc_horizontal . 'px ;
            }
            .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-top_right,
            .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-bottom_right{
                right: ' . $sc_horizontal . 'px ;
            }
            @media screen and (max-width: 768px) {
                .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-top_left .jagif-popup-gift-content-wrap,
                .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-bottom_left .jagif-popup-gift-content-wrap{
                    left: ' . $sc_horizontal_mobile . 'px ;
                }
                .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-top_right .jagif-popup-gift-content-wrap,
                .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-bottom_right .jagif-popup-gift-content-wrap{
                    right: ' . $sc_horizontal_mobile . 'px ;
                }
            }
            ';
		}
		if ( $sc_vertical = self::$settings->get_params( 'pg_vertical' ) ) {
			$sc_vertical_mobile = $sc_vertical > 20 ? 20 - $sc_vertical : 0;
			$css                .= '.jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-top_left,
            .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-top_right{
                top: ' . $sc_vertical . 'px ;
            }
            .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-bottom_right,
            .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-bottom_left{
                bottom: ' . $sc_vertical . 'px ;
            }
            @media screen and (max-width: 768px) {
                .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-top_left .jagif-popup-gift-content-wrap,
                .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-top_right .jagif-popup-gift-content-wrap{
                    top: ' . $sc_vertical_mobile . 'px ;
                }
                .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-bottom_right .jagif-popup-gift-content-wrap,
                .jagif-popup-gift.jagif-popup-gift-1.jagif-popup-gift-bottom_left .jagif-popup-gift-content-wrap{
                    bottom: ' . $sc_vertical_mobile . 'px ;
                }
            }';
		}
		$css .= $frontend::add_inline_style(
			array( '.jagif-popup-gift .jagif-popup-gift-content-wrap' ),
			array( 'pg_radius' ),
			array( 'border-radius' ),
			array( 'px' )
		);
		$css .= $frontend::add_inline_style(
			array( '.jagif-popup-gift .jagif-popup-gift-header-wrap' ),
			array( 'pg_header_bg_color', 'pg_header_border_style', 'pg_header_border_color' ),
			array( 'background', 'border-style', 'border-color' ),
			array( '', '', '' )
		);

		$css .= $frontend::add_inline_style(
			array( '.jagif-popup-gift .jagif-popup-gift-products .jagif-popup-gift-pd-img-wrap img' ),
			array( 'pg_pd_img_border_radius' ),
			array( 'border-radius' ),
			array( 'px' )
		);

		$css = str_replace( array( "\r", "\n", '\r', '\n' ), ' ', $css );

		return $css;
	}

	public function get_params( $name = '' ) {
		if ( $this->customize_data && $name && $setting = $this->customize_data->get_setting( 'jagif_woo_free_gift_params[' . $name . ']' ) ) {
			return $this->customize_data->post_value( $setting, self::$settings->get_params( $name ) );
		} else {
			return self::$settings->get_params( $name );
		}
	}

	public static function is_customize_preview() {
		if ( isset( self::$cache['is_customize_preview'] ) ) {
			return self::$cache['is_customize_preview'];
		}

		return self::$cache['is_customize_preview'] = is_customize_preview();
	}

	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}