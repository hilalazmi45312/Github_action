<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIJAGIF_WOO_FREE_GIFT_Admin_Design {
	protected $settings, $admin, $customize;
	public $function;

	public function __construct() {
		$this->function = VIJAGIF_WOO_FREE_GIFT_Function::get_instance();
		$this->admin    = 'VIJAGIF_WOO_FREE_GIFT_Admin_Settings';
		$this->settings = VIJAGIF_WOO_FREE_GIFT_DATA::get_instance();
		$enable         = $this->settings->get_params( 'enable' );
		if ( ! empty( $enable ) && $enable == 1 ) {
			add_action( 'customize_register', array( $this, 'design_option_customizer' ) );
			add_action( 'customize_preview_init', array( $this, 'customize_preview_init' ) );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'customize_controls_enqueue_scripts' ) );
			add_action( 'customize_controls_print_scripts', array( $this, 'customize_controls_print_scripts' ), 30 );
			add_action( 'wp_print_styles', array( $this, 'customize_controls_print_styles' ) );
		}
	}

	public function customize_controls_enqueue_scripts() {
		$this->admin::enqueue_style(
			array( 'jagif-gift-icons' ),
			array( 'gift-icons.min.css' )
		);
		$this->admin::enqueue_style(
			array( 'jagif-customize-preview' ),
			array( 'customize-preview.css' )
		);
		$this->admin::enqueue_script(
			array( 'jagif-customize-setting' ),
			array( 'customize-setting.js' ),
			array( array( 'jquery', 'jquery-ui-button' ) ),
			'enqueue', true
		);
		$available_id = get_option( 'jagif_list_product_gift' );
		$product      = wc_get_product( $available_id );
		$link         = $product ? $product->get_permalink() : '';
		$args         = array(
			'cart_url'                 => esc_js( wc_get_page_permalink( 'cart' ) ),
			'checkout_url'             => esc_js( wc_get_page_permalink( 'checkout' ) ),
			'shop_url'                 => esc_js( wc_get_page_permalink( 'shop' ) ),
			'single_product'           => esc_js( wc_get_page_permalink( 'single_product' ) ),
			'single_product_customize' => esc_js( $link ),
		);
		wp_localize_script( 'jagif-customize-setting', 'jagif_preview_setting', $args );
	}

	public function customize_controls_print_scripts() {
		if ( ! is_customize_preview() ) {
			return;
		}
		?>
        <script type="text/javascript">
            (function ($) {
                $(document).ready(function () {
                    wp.customize('jagif_woo_free_gift_params[gb_display_style]', function (value) {
                        value.bind(function (newval) {
                            switch (newval) {
                                case '2':
                                    $('#customize-control-jagif_woo_free_gift_params-pg_position').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_enable_auto_show').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_box_shadow').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_horizontal').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_vertical').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_color').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_bg').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_count_color').css('display', 'block');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_count_bg_color').css('display', 'block');
                                    break;
                                default:
                                    $('#customize-control-jagif_woo_free_gift_params-pg_position').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_enable_auto_show').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_box_shadow').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_horizontal').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_vertical').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_color').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_bg').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_count_color').css('display', 'none');
                                    $('#customize-control-jagif_woo_free_gift_params-pg_icon_count_bg_color').css('display', 'none');
                                    break;
                            }
                        });
                    });
                });
            })(jQuery);
        </script>
		<?php
	}

	public function customize_preview_init() {
		$this->admin::enqueue_script(
			array( 'jagif-customize-preview' ),
			array( 'customize-preview.js' ),
			array( array( 'jquery', 'customize-preview' ) ),
			'enqueue', true
		);
		$args = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'jagif_nonce' => wp_create_nonce( 'jagif-nonce' ),
			'icon_image' => ! empty( $this->settings->get_params('icon_image' ) ) ? 1 : 0,
			'icon_default' => $this->settings->get_class_icon( $this->settings->get_params('icon_default' ), 'gift_icons' ),
		);
		wp_localize_script( 'jagif-customize-preview', 'jagif_preview', $args );
	}

	public function customize_controls_print_styles() {
		if ( ! is_customize_preview() ) {
			return;
		}
		global $wp_customize;
		$this->customize = $wp_customize;
		?>
        <style id="jagif-custom-css" type="text/css">
            <?php
            $custom_css = esc_attr( $this->settings->get_params( 'custom_css' ) );
            if ( ! empty( $custom_css ) ) {
                echo esc_attr( $custom_css );
            }
            ?>
        </style>
        <style type="text/css" id="jagif-preview-ic_size">
            <?php
				$ic_size = $this->get_params_customize('ic_size') ? $this->get_params_customize('ic_size') : 0;
				$ic_size_mobile = $ic_size > 20 ? $ic_size - 10 : 10;
				?>

            .jagif_badge-gift-icon .jagif-icon-gift {
                width: <?php echo sprintf('%spx', esc_attr( $ic_size ) ); ?>;
                height: <?php echo sprintf('%spx', esc_attr( $ic_size ) ); ?>;
            }

            .jagif_badge-gift-icon .jagif-icon-gift i:before {
                font-size: <?php echo sprintf('%spx', esc_attr( $ic_size ) ); ?>;
            }


            @media screen and (max-width: 768px) {
                .jagif_badge-gift-icon .jagif-icon-gift {
                    width: <?php echo sprintf('%spx', esc_attr( $ic_size_mobile ) ); ?>;
                    height: <?php echo sprintf('%spx', esc_attr( $ic_size_mobile ) ); ?>;
                }

                .jagif_badge-gift-icon .jagif-icon-gift i:before {
                    font-size: <?php echo sprintf('%spx', esc_attr( $ic_size_mobile ) ); ?>;
                }
            }
        </style>
        <style type="text/css" id="jagif-preview-box_font_size">
            <?php
				$box_font_size = $this->get_params_customize('box_font_size') ? $this->get_params_customize('box_font_size') : 14;
				?>
            .jagif-popup-gift-products-wrap, .jagif-free-gift-promo-content, .jagif-free-gift-promo-content-1 {
                font-size: <?php echo sprintf('%spx', esc_attr( $box_font_size ) ); ?>;
            }

        </style>
        <style type="text/css" id="jagif-preview-ic_horizontal">
            <?php
			$ic_horizontal = $this->get_params_customize('ic_horizontal') ? $this->get_params_customize('ic_horizontal') : 0;
			$ic_horizontal_mobile = $ic_horizontal > 20 ? $ic_horizontal - 10 : 0;
			?>
            .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-0 {
                left: <?php echo sprintf('%spx', esc_attr( $ic_horizontal ) ); ?>;
            }

            .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-1 {
                right: <?php echo sprintf('%spx', esc_attr( $ic_horizontal ) ); ?>;
            }

            @media screen and (max-width: 768px) {
                .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-0 {
                    left: <?php echo sprintf('%spx', esc_attr( $ic_horizontal_mobile ) ); ?>;
                }
            }

            @media screen and (max-width: 768px) {
                .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-1 {
                    right: <?php echo sprintf('%spx', esc_attr( $ic_horizontal_mobile ) ); ?>;
                }
            }
        </style>
        <style type="text/css" id="jagif-preview-ic_vertical">
            <?php
			$ic_vertical = $this->get_params_customize('ic_vertical') ? $this->get_params_customize('ic_vertical') : 0;
			$ic_vertical_mobile = $ic_vertical > 10 ? $ic_vertical - 10 : 0;
			?>
            .jagif_badge-gift-icon .jagif-icon-gift {
                top: <?php echo sprintf('%spx', esc_attr( $ic_vertical ) ); ?>;
            }

            @media screen and (max-width: 768px) {
                .jagif_badge-gift-icon .jagif-icon-gift {
                    top: <?php echo sprintf('%spx', esc_attr( $ic_vertical_mobile ) ); ?>;
                }
            }
        </style>
        <style type="text/css" id="jagif-preview-pg_horizontal">
            <?php
			$pg_vertical = $this->get_params_customize('pg_horizontal') ? $this->get_params_customize('pg_horizontal') : 0;
			$pg_vertical_mobile = $pg_vertical > 20 ? 20- $pg_vertical : 0;
			?>
            .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_left {
                left: <?php echo sprintf('%spx', esc_attr( $pg_vertical ) ); ?>;
            }

            .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_right, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_right {
                right: <?php echo sprintf('%spx', esc_attr( $pg_vertical ) ); ?>;
            }

            @media screen and (max-width: 768px) {
                .jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap-bottom_left {
                    left: <?php echo sprintf('%spx', esc_attr( $pg_vertical_mobile ) ); ?>;
                }

                .jagif-popup-gift-icon-wrap-bottom_right, .jagif-popup-gift-icon-wrap-top_right {
                    right: <?php echo sprintf('%spx', esc_attr( $pg_vertical_mobile ) ); ?>;
                }
            }
        </style>

        <style type="text/css" id="jagif-preview-pg_vertical">
            <?php
            $pg_vertical = $this->get_params_customize('pg_vertical') ? $this->get_params_customize('pg_vertical') : 0;
            $pg_vertical_mobile = $pg_vertical > 20 ? 20- $pg_vertical : 0;
            ?>
            .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_right {
                top: <?php echo sprintf('%spx',  esc_attr( $pg_vertical ) ); ?>;
            }

            .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_right, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_left {
                bottom: <?php echo sprintf('%spx', esc_attr( $pg_vertical ) ); ?>;
            }

            @media screen and (max-width: 768px) {
                .jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap-top_right {
                    top: <?php echo sprintf('%spx', esc_attr( $pg_vertical_mobile ) ); ?>;
                }

                .jagif-popup-gift-icon-wrap-bottom_right, .jagif-popup-gift-icon-wrap-bottom_left {
                    bottom: <?php echo sprintf('%spx', esc_attr( $pg_vertical_mobile ) ); ?>;
                }
            }
        </style>
        <style type="text/css" id="jagif-preview-pg_icon_box_shadow">
            <?php
			if ($this->get_params_customize('pg_icon_box_shadow')){
				?>
            .jagif-popup-gift-icon-wrap {
                box-shadow: inset 0 0 2px rgba(0, 0, 0, 0.03), 0 4px 10px rgba(0, 0, 0, 0.17);
            }

            <?php
			}
			 ?>
        </style>
		<?php
		$this->add_preview_style( 'ic_color', '.jagif_badge-gift-icon div.jagif-icon-gift >i', 'color', '' );
		$this->add_preview_style( 'ic_background', '.jagif_badge-gift-icon .jagif-icon-gift', 'background-color', '' );
		$this->add_preview_style( 'title_box_color', '.jagif-free-gift-promo_title', 'color', '' );
		$this->add_preview_style( 'pg_icon_color', '.jagif-popup-gift-icon-wrap .jagif-popup-gift-icon i', 'color', '' );
		$this->add_preview_style( 'pg_icon_bg', '.jagif-popup-gift-icon-wrap', 'background', '' );
		$this->add_preview_style( 'pg_icon_count_bg_color', '.jagif-popup-gift-icon-wrap .jagif-popup-gift-count-wrap', 'background', '' );
		$this->add_preview_style( 'pg_icon_count_color', '.jagif-popup-gift-icon-wrap .jagif-popup-gift-count-wrap', 'color', '' );
		$this->add_preview_style( 'gift_name_color',
			'.jagif-free-gift-promo-item .item-gift a, .gift-item-receive .name-gift span, .gift-item-receive a.jagif-open-dropdown-choose-var',
			'color', '' );
		$this->add_preview_style( 'gift_name_hover_color',
			'.jagif-free-gift-promo-item .item-gift a:hover, .gift-item-receive .name-gift span:hover, .gift-item-receive a.jagif-open-dropdown-choose-var:hover',
			'color', '' );
	}

	private function add_preview_style( $name, $element, $style, $suffix = '' ) {
		$id = 'jagif-preview-' . $name;
		?>
        <style type="text/css" id="<?php echo esc_attr( $id ); ?>">
            <?php
			$css = $element.'{';
			if($value = $this->get_params_customize($name)){
				$css .= $style.': '.$value.$suffix.' ;';
			}
			$css .= '}';
			echo wp_kses_post($css);
			 ?>
        </style>
		<?php
	}

	protected function get_params_customize( $name = '' ) {
		if ( ! $name ) {
			return '';
		}

		return $this->customize->post_value( $this->customize->get_setting( 'jagif_woo_free_gift_params[' . $name . ']' ), $this->settings->get_params( $name ) );
	}

	public function design_option_customizer( $wp_customize ) {
		$wp_customize->add_panel( 'jagif_design', array(
			'priority'       => 20,
			'capability'     => 'manage_options',
			'theme_supports' => '',
			'title'          => esc_html__( 'Jagif - Woo Gift Box', 'jagif-woo-free-gift' ),
		) );

		$this->add_section_design_icon_gift( $wp_customize );
		$this->add_section_design_single_product( $wp_customize );
		$this->add_section_design_custom_css( $wp_customize );
	}

	protected function add_section_design_icon_gift( $wp_customize ) {
		$wp_customize->add_section( 'jagif_design_icon_gift', array(
			'priority'       => 20,
			'capability'     => 'manage_options',
			'theme_supports' => '',
			'title'          => esc_html__( 'Icon Gift', 'jagif-woo-free-gift' ),
			'panel'          => 'jagif_design',
		) );
		// Show Icon Gift In Shop Page
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[ic_enable_shop]',
			array(
				'default'           => $this->settings->get_default( 'ic_enable_shop' ),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control( new JAGIF_Customize_Checkbox_Control( $wp_customize, 'jagif_woo_free_gift_params[ic_enable_shop]', array(
				'label'    => esc_html__( 'Show on shop page', 'jagif-woo-free-gift' ),
				'settings' => 'jagif_woo_free_gift_params[ic_enable_shop]',
				'section'  => 'jagif_design_icon_gift',
			) )
		);
		//Position Icon
		$icon_gift_position = $this->settings->get_class_icons( 'icon_gift_position' );
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[ic_position]', array(
			'default'    => $this->settings->get_default( 'ic_position' ),
			'type'       => 'option',
			'capability' => 'manage_options',
			'transport'  => 'postMessage',
		) );
		$wp_customize->add_control( new JAGIF_Customize_Radio_Icon( $wp_customize, 'jagif_woo_free_gift_params[ic_position]', array(
			'label'   => esc_html__( 'Position', 'jagif-woo-free-gift' ),
			'section' => 'jagif_design_icon_gift',
			'choices' => $icon_gift_position,
		) ) );
		//WP Customize Site Icon Control
		$wp_customize->add_setting(	'jagif_woo_free_gift_params[icon_image]', array(
			'type'       => 'option',
			'capability' => 'manage_options',
			'transport'  => 'postMessage', // Previewed with JS in the Customizer controls window.
		) );

		$wp_customize->add_control(
			new WP_Customize_Cropped_Image_Control(
				$wp_customize,
				'jagif_woo_free_gift_params[icon_image]',
				array(
					'label'       => esc_html__( 'Custom icon', 'jagif-woo-free-gift' ),
					'description' => '<p>' . esc_html__( 'Gift Icons should be square.', 'jagif-woo-free-gift' ) . '</p>',
					'section'     => 'jagif_design_icon_gift',
					'button_labels' => array(
						'select'       => esc_html__( 'Select gift icon', 'jagif-woo-free-gift' ),
						'change'       => esc_html__( 'Change icon', 'jagif-woo-free-gift' ),
						'remove'       => esc_html__( 'Remove', 'jagif-woo-free-gift' ),
						'default'      => esc_html__( 'Default', 'jagif-woo-free-gift' ),
						'placeholder'  => esc_html__( 'No icon selected', 'jagif-woo-free-gift' ),
						'frame_title'  => esc_html__( 'Select icon', 'jagif-woo-free-gift' ),
						'frame_button' => esc_html__( 'Choose icon', 'jagif-woo-free-gift' ),
					),
					'height'      => 512,
					'width'       => 512,
				)
			)
		);
		// Choose Icon
		$gift_icons   = $this->settings->get_class_icons( 'gift_icons' );
		$gift_icons_t = array();
		foreach ( $gift_icons as $k => $class ) {
			$gift_icons_t[ $k ] = '<i class="' . $class . '"></i>';
		}
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[icon_default]', array(
			'default'    => $this->settings->get_default( 'icon_default' ),
			'type'       => 'option',
			'capability' => 'manage_options',
			'transport'  => 'postMessage',
		) );

		$wp_customize->add_control( new JAGIF_Customize_Radio_Popup_Control( $wp_customize, 'jagif_woo_free_gift_params[icon_default]', array(
			'label'   => esc_html__( 'Icon', 'jagif-woo-free-gift' ),
			'section' => 'jagif_design_icon_gift',
			'choices' => $gift_icons_t,
		) ) );

		// Icon Gift Icon Vertical(px)
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[ic_vertical]', array(
			'default'           => $this->settings->get_default( 'ic_vertical' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new JAGIF_Customize_Range_Control( $wp_customize, 'jagif_woo_free_gift_params[ic_vertical]',
			array(
				'label'       => esc_html__( 'Cart icon vertical distance(px)', 'jagif-woo-free-gift' ),
				'section'     => 'jagif_design_icon_gift',
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 200,
					'step' => 1,
					'id'   => 'jagif-ic_vertical',
				),
			)
		) );
		// Icon Gift Icon Horizontal(px)
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[ic_horizontal]', array(
			'default'           => $this->settings->get_default( 'ic_horizontal' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new JAGIF_Customize_Range_Control( $wp_customize, 'jagif_woo_free_gift_params[ic_horizontal]',
			array(
				'label'       => esc_html__( 'Cart icon horizontal distance(px)', 'jagif-woo-free-gift' ),
				'section'     => 'jagif_design_icon_gift',
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 100,
					'step' => 1,
					'id'   => 'jagif-ic_horizontal',
				),
			)
		) );
		//Font Size for Icon Gift(px)
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[ic_size]', array(
			'default'           => $this->settings->get_default( 'ic_size' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new JAGIF_Customize_Range_Control( $wp_customize, 'jagif_woo_free_gift_params[ic_size]',
			array(
				'label'       => esc_html__( 'Font Size for Gift Icon(px)', 'jagif-woo-free-gift' ),
				'section'     => 'jagif_design_icon_gift',
				'input_attrs' => array(
					'min'  => 20,
					'max'  => 100,
					'step' => 1,
					'id'   => 'jagif-ic_size',
				),
			)
		) );
		//Color for Icon Gift
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[ic_color]', array(
			'default'           => $this->settings->get_default( 'ic_color' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'jagif_woo_free_gift_params[ic_color]',
				array(
					'label'    => esc_html__( 'Color for gift icon', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_icon_gift',
					'settings' => 'jagif_woo_free_gift_params[ic_color]',
					'active_callback' => array( $this, 'popup_fields_callback' ),
				) )
		);
		//Background color for Icon Gift
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[ic_background]', array(
			'default'           => $this->settings->get_default( 'ic_background' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'jagif_woo_free_gift_params[ic_background]',
				array(
					'label'    => esc_html__( 'Background color for gift icon', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_icon_gift',
					'settings' => 'jagif_woo_free_gift_params[ic_background]',
					'active_callback' => array( $this, 'popup_fields_callback' ),
				) )
		);
		//Gift price on cart
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[price_in_cart]', array(
			'default'           => $this->settings->get_default( 'price_in_cart' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'jagif_woo_free_gift_params[price_in_cart]', array(
			'label'   => esc_html__( 'Display gift price on cart', 'jagif-woo-free-gift' ),
			'type'    => 'select',
			'section' => 'jagif_design_icon_gift',
			'choices' => array(
				0 => esc_html__( 'Price 0', 'jagif-woo-free-gift' ),
				'free' => esc_html__( 'Text "Free"', 'jagif-woo-free-gift' ),
				'' => esc_html__( 'Blank string', 'jagif-woo-free-gift' ),
				'icon' => esc_html__( 'Gift icon', 'jagif-woo-free-gift' ),
			),
		) );
	}

// Customize single product
	protected function add_section_design_single_product( $wp_customize ) {
		$wp_customize->add_section( 'jagif_design_single_product', array(
			'priority'       => 20,
			'capability'     => 'manage_options',
			'theme_supports' => '',
			'title'          => esc_html__( 'Single Product', 'jagif-woo-free-gift' ),
			'panel'          => 'jagif_design',
		) );
		//Gift Box Display Position
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[gb_display_style]', array(
			'default'           => $this->settings->get_default( 'gb_display_style' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'jagif_woo_free_gift_params[gb_display_style]', array(
			'label'   => esc_html__( 'Gift Box Position', 'jagif-woo-free-gift' ),
			'type'    => 'select',
			'section' => 'jagif_design_single_product',
			'choices' => array(
				'3' => esc_html__( 'Before Add To Cart form', 'jagif-woo-free-gift' ),
				'0' => esc_html__( 'After Add To Cart form', 'jagif-woo-free-gift' ),
				'4' => esc_html__( 'Before Price', 'jagif-woo-free-gift' ),
				'5' => esc_html__( 'After Price', 'jagif-woo-free-gift' ),
				'1' => esc_html__( 'Before Product Tab', 'jagif-woo-free-gift' ),
				'2' => esc_html__( 'Popup', 'jagif-woo-free-gift' ),
			),
		) );

		// Box Title
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[box_title]', array(
			'default'           => $this->settings->get_default( 'box_title' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'jagif_woo_free_gift_params[box_title]',
			array(
				'type'        => 'text',
				'section'     => 'jagif_design_single_product',
				'label'       => esc_html__( 'Box Title', 'jagif-woo-free-gift' ),
				'description' => esc_html__( 'The title of gift box', 'jagif-woo-free-gift' ),
			)
		);
		//Font Size for Gift Box(px)
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[box_font_size]', array(
			'default'           => '14',
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new JAGIF_Customize_Range_Control( $wp_customize, 'jagif_woo_free_gift_params[box_font_size]',
			array(
				'label'       => esc_html__( 'Font Size for Gift Box(px)', 'jagif-woo-free-gift' ),
				'section'     => 'jagif_design_single_product',
				'input_attrs' => array(
					'min'  => 10,
					'max'  => 25,
					'step' => 1,
				),
			)
		) );

		//Title Box Color
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[title_box_color]', array(
			'default'           => $this->settings->get_default( 'title_box_color' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'jagif_woo_free_gift_params[title_box_color]',
				array(
					'label'    => esc_html__( 'Title Box Color', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_single_product',
					'settings' => 'jagif_woo_free_gift_params[title_box_color]',
				) )
		);

		// Gift Item Name Color
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[gift_name_color]', array(
			'default'           => $this->settings->get_default( 'gift_name_color' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'jagif_woo_free_gift_params[gift_name_color]',
				array(
					'label'    => esc_html__( 'Gift Item Color', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_single_product',
					'settings' => 'jagif_woo_free_gift_params[gift_name_color]',
				) )
		);

		// Gift Name Hover Color
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[gift_name_hover_color]', array(
			'default'           => $this->settings->get_default( 'gift_name_hover_color' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'jagif_woo_free_gift_params[gift_name_hover_color]',
				array(
					'label'    => esc_html__( 'Gift Name Hover Color', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_single_product',
					'settings' => 'jagif_woo_free_gift_params[gift_name_hover_color]',
				) )
		);
		//Popup Gift Icon Position
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_position]', array(
			'default'           => $this->settings->get_default( 'pg_position' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'jagif_woo_free_gift_params[pg_position]', array(
				'label'   => esc_html__( 'Popup Gift Icon Position', 'jagif-woo-free-gift' ),
				'section' => 'jagif_design_single_product',
				'type'    => 'select',
				'active_callback' => array( $this, 'popup_fields_callback' ),
				'choices' => array(
					'top_left'     => esc_html__( 'Top Left', 'jagif-woo-free-gift' ),
					'top_right'    => esc_html__( 'Top Right', 'jagif-woo-free-gift' ),
					'bottom_left'  => esc_html__( 'Bottom Left', 'jagif-woo-free-gift' ),
					'bottom_right' => esc_html__( 'Bottom Right', 'jagif-woo-free-gift' ),
				),
			)
		);
		// Popup Choose Icon
		$gift_icons   = $this->settings->get_class_icons( 'gift_icons' );
		$gift_icons_t = array();
		foreach ( $gift_icons as $k => $class ) {
			$gift_icons_t[ $k ] = '<i class="' . $class . '"></i>';
		}
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_icon]', array(
			'default'    => $this->settings->get_default( 'pg_icon' ),
			'type'       => 'option',
			'capability' => 'manage_options',
			'transport'  => 'postMessage',
		) );
		$wp_customize->add_control( new JAGIF_Customize_Radio_Popup_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_icon]', array(
			'label'   => esc_html__( 'Popup Icon', 'jagif-woo-free-gift' ),
			'section' => 'jagif_design_single_product',
			'choices' => $gift_icons_t,
			'active_callback' => array( $this, 'popup_fields_callback' ),
		) ) );
		// Enable Auto Show Popup Gifts
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_enable_auto_show]',
			array(
				'default'           => $this->settings->get_default( 'pg_enable_auto_show' ),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control( new JAGIF_Customize_Checkbox_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_enable_auto_show]', array(
				'label'    => esc_html__( 'Enable Auto Show Popup Gifts', 'jagif-woo-free-gift' ),
				'settings' => 'jagif_woo_free_gift_params[pg_enable_auto_show]',
				'section'  => 'jagif_design_single_product',
				'active_callback' => array( $this, 'popup_fields_callback' ),
			) )
		);
		// Enable Popup Icon Box Shadow
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_icon_box_shadow]',
			array(
				'default'           => $this->settings->get_default( 'pg_icon_box_shadow' ),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control( new JAGIF_Customize_Checkbox_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_icon_box_shadow]', array(
				'label'    => esc_html__( 'Enable Popup Icon Box Shadow', 'jagif-woo-free-gift' ),
				'settings' => 'jagif_woo_free_gift_params[pg_icon_box_shadow]',
				'section'  => 'jagif_design_single_product',
				'active_callback' => array( $this, 'popup_fields_callback' ),
			) )
		);
		//Popup Gift Icon Horizontal(px)
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_horizontal]',
			array(
				'default'           => $this->settings->get_default( 'pg_horizontal' ),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'postMessage',
			) );
		$wp_customize->add_control( new JAGIF_Customize_Range_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_horizontal]',
			array(
				'label'       => esc_html__( 'Popup Icon Horizontal(px)', 'jagif-woo-free-gift' ),
				'section'     => 'jagif_design_single_product',
				'active_callback' => array( $this, 'popup_fields_callback' ),
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 200,
					'step' => 1,
					'id'   => 'jagif-pg_horizontal',
				),
			)
		) );
		// Popup Gift Vertical(px)
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_vertical]',
			array(
				'default'           => $this->settings->get_default( 'pg_vertical' ),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'postMessage',
			) );
		$wp_customize->add_control( new JAGIF_Customize_Range_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_vertical]',
			array(
				'label'       => esc_html__( 'Popup Icon Vertical(px)', 'jagif-woo-free-gift' ),
				'section'     => 'jagif_design_single_product',
				'active_callback' => array( $this, 'popup_fields_callback' ),
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 200,
					'step' => 1,
					'id'   => 'jagif-pg_vertical',
				),
			)
		) );
		// Popup Icon Color
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_icon_color]', array(
			'default'           => $this->settings->get_default( 'pg_icon_color' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_icon_color]',
				array(
					'label'    => esc_html__( 'Popup Icon Color', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_single_product',
					'settings' => 'jagif_woo_free_gift_params[pg_icon_color]',
					'active_callback' => array( $this, 'popup_fields_callback' ),
				) )
		);
		// Popup Icon Background
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_icon_bg]', array(
			'default'           => $this->settings->get_default( 'pg_icon_bg' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_icon_bg]',
				array(
					'label'    => esc_html__( 'Popup Icon Background', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_single_product',
					'settings' => 'jagif_woo_free_gift_params[pg_icon_bg]',
					'active_callback' => array( $this, 'popup_fields_callback' ),
				) )
		);
		// Popup Icon Count Text Color
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_icon_count_color]', array(
			'default'           => $this->settings->get_default( 'pg_icon_count_color' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_icon_count_color]',
				array(
					'label'    => esc_html__( 'Popup Icon Count Text Color', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_single_product',
					'settings' => 'jagif_woo_free_gift_params[pg_icon_count_color]',
					'active_callback' => array( $this, 'popup_fields_callback' ),
				) )
		);
		// Popup Icon Count Background
		$wp_customize->add_setting( 'jagif_woo_free_gift_params[pg_icon_count_bg_color]', array(
			'default'           => $this->settings->get_default( 'pg_icon_count_bg_color' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'jagif_woo_free_gift_params[pg_icon_count_bg_color]',
				array(
					'label'    => esc_html__( 'Popup Icon Count Background', 'jagif-woo-free-gift' ),
					'section'  => 'jagif_design_single_product',
					'settings' => 'jagif_woo_free_gift_params[pg_icon_count_bg_color]',
					'active_callback' => array( $this, 'popup_fields_callback' ),
				) )
		);

	}

	protected function add_section_design_custom_css( $wp_customize ) {

		$wp_customize->add_section( 'jagif_design_custom_css', array(
			'priority'       => 20,
			'capability'     => 'manage_options',
			'theme_supports' => '',
			'title'          => esc_html__( 'Custom CSS', 'jagif-woo-free-gift' ),
			'panel'          => 'jagif_design',
		) );

		$wp_customize->add_setting( 'jagif_woo_free_gift_params[custom_css]', array(
			'default'           => $this->settings->get_default( 'custom_css' ),
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		) );
		$wp_customize->add_control( 'jagif_woo_free_gift_params[custom_css]', array(
			'type'     => 'textarea',
			'priority' => 10,
			'section'  => 'jagif_design_custom_css',
			'label'    => esc_html__( 'Custom CSS', 'jagif-woo-free-gift' )
		) );
	}

	function popup_fields_callback() {
		return $this->settings->get_params( 'gb_display_style' ) == 2;
	}
}