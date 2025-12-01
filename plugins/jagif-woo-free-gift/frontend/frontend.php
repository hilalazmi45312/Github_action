<?php

use function WPML\FP\Strings\replace;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class VIJAGIF_WOO_FREE_GIFT_Frontend_Frontend {
	protected $settings;
	public $functions;
	public $is_widget;
	public $helper;
	protected $is_customize;

	public function __construct() {
		$this->settings  = new VIJAGIF_WOO_FREE_GIFT_DATA();
		$this->functions = VIJAGIF_WOO_FREE_GIFT_Function::get_instance();
		$this->helper    = VIJAGIF_HELPER::get_instance();
		$enable          = $this->settings->get_params( 'enable' );
		if ( ! empty( $enable ) && $enable == 1 ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'jagif_register_frontend_script' ) );
			add_action( 'wp_ajax_jagif_get_class_icon', array( $this, 'jagif_get_class_icon' ) );

			add_action( 'woocommerce_before_mini_cart', array( $this, 'before_mini_cart' ) );
			add_action( 'woocommerce_after_mini_cart', array( $this, 'after_mini_cart' ) );
			add_filter( 'woocommerce_before_widget_product_list', array( $this, 'before_widget_product_list' ) );
			add_filter( 'woocommerce_after_widget_product_list', array( $this, 'after_widget_product_list' ) );
			add_action( 'woocommerce_before_cart_contents', array( $this, 'before_cart_contents' ) );
			add_action( 'woocommerce_after_cart_contents', array( $this, 'after_cart_contents' ) );

			//gift box positions single product
			add_action( 'woocommerce_before_template_part', array( $this, 'jagif_before_template' ) );
			add_action( 'woocommerce_after_template_part', array( $this, 'jagif_after_template' ) );
			add_action( 'wp', array( $this, 'gift_before_add_to_cart_form' ) );
			add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'insert_after_add_to_cart_form' ) );
			add_action( 'woocommerce_after_single_product_summary', array(
				$this,
				'insert_after_single_product_summary'
			) );
			// add id to atc button
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'jagif_add_gift_ids_to_cart_button' ) );
			// insert icon gift on shop
			// loop bfore image
			add_action( 'woocommerce_before_shop_loop_item', array( $this, 'insert_archive_icon_gift' ), 10 );
			// Add gift ids to add to cart button form
			add_action( 'woocommerce_after_calculate_totals', array( $this, 'jagif_add_custom_price' ), 10 );
			// add to cart
			add_action( 'woocommerce_add_to_cart', array( $this, 'jagif_add_to_cart' ), 10, 6 );
			// cart
			add_action( 'woocommerce_cart_item_removed', array( $this, 'jagif_cart_item_removed' ), 10, 2 );
			add_action( 'woocommerce_cart_item_restored', array( $this, 'jagif_cart_item_restored' ), 10, 2 );
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'jagif_cart_loaded_from_session' ) );
			add_action( 'woocommerce_after_cart_item_quantity_update', array(
				$this,
				'jagif_after_cart_item_quantity_update'
			), 20, 4 );
			add_action( 'woocommerce_after_cart_item_name', array( $this, 'jagif_after_cart_item_name' ), 10, 2 );

			//Ajax
			add_action( 'wp_ajax_jagif_update_cart', array( $this, 'jagif_update_cart' ) );
			add_action( 'wp_ajax_nopriv_jagif_update_cart', array( $this, 'jagif_update_cart' ) );

			add_action( 'wp_ajax_jagif_update_link_gift', array( $this, 'jagif_update_link_gift' ) );
			add_action( 'wp_ajax_nopriv_jagif_update_link_gift', array( $this, 'jagif_update_link_gift' ) );

			//Add data attributes to button add to cart
			// loop after image
			add_filter( 'woocommerce_cart_contents_count', array( $this, 'jagif_cart_contents_count' ) );

			add_filter( 'woocommerce_before_widget_product_review_list', array(
				$this,
				'before_widget_product_review_list'
			) );
			add_filter( 'woocommerce_after_widget_product_review_list', array(
				$this,
				'after_widget_product_review_list'
			) );

			// Add cart item_data
			add_filter( 'woocommerce_add_cart_item_data', array( $this, 'jagif_add_cart_item_data' ), 10, 4 );

			add_filter( 'woocommerce_add_to_cart_quantity', array( $this, 'jagif_add_to_cart_quantity' ), 10, 2 );
			// add to cart
			add_filter( 'woocommerce_get_cart_item_from_session', array(
				$this,
				'jagif_get_cart_item_from_session'
			), 10, 2 );

			add_filter( 'woocommerce_order_formatted_line_subtotal', array(
				$this,
				'jagif_order_formatted_line_subtotal'
			), 10, 2 );

			// set price for product gift
			add_filter( 'woocommerce_get_cart_contents', array( $this, 'jagif_get_cart_contents' ), 10, 1 );
			add_filter( 'woocommerce_cart_item_name', array( $this, 'jagif_cart_item_name' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'jagif_cart_item_subtotal' ), 20, 3 );
			add_filter( 'woocommerce_cart_item_price', array( $this, 'jagif_cart_item_price' ), 20, 3 );
			add_filter( 'woocommerce_cart_item_quantity', array( $this, 'jagif_cart_item_quantity' ), 10, 3 );
			add_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'jagif_widget_cart_item_quantity' ), 10, 3 );

			add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'jagif_cart_item_remove_link' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_class', array( $this, 'jagif_item_class' ), 10, 2 );
			add_filter( 'woocommerce_mini_cart_item_class', array( $this, 'jagif_mini_item_class' ), 10, 2 );
			add_filter( 'woocommerce_order_item_class', array( $this, 'jagif_item_class' ), 10, 2 );
			// Order again
			add_filter( 'woocommerce_order_again_cart_item_data', array(
				$this,
				'jagif_order_again_cart_item_data'
			), 10, 2 );

			// Cart gift notice
			add_action( 'woocommerce_before_cart_table', array( $this, 'jagif_eligibility_message' ) );

			add_filter( 'jagif_get_display_conditions', function ( $list ) {
				return $list;
			} );

			//Cart all in one
			add_filter( 'vi_wcaio_mini_cart_pd_qty', array( $this, 'jagif_wcaio_mini_cart_pd_qty' ), 10, 4 );
		}
	}

	public function before_mini_cart() {
		$this->is_widget = true;
	}

	public function after_mini_cart() {
		$this->is_widget = false;
	}

	public function before_widget_product_list( $html ) {
		$this->is_widget = true;

		return $html;
	}

	public function after_widget_product_list( $html ) {
		$this->is_widget = false;

		return $html;
	}

	public function before_cart_contents() {
		$this->is_widget = true;
	}

	public function after_cart_contents() {
		$this->is_widget = false;
	}

	public function before_widget_product_review_list( $html ) {
		$this->is_widget = true;

		return $html;
	}

	public function after_widget_product_review_list( $html ) {
		$this->is_widget = false;

		return $html;
	}

	public function jagif_register_frontend_script() {
		$this->is_customize = is_customize_preview();
		wp_enqueue_style( 'jagif_icon', VIJAGIF_WOO_FREE_GIFT_CSS . 'icon.min.css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );

		$suffix = WP_DEBUG ? '' : 'min.';
		wp_enqueue_style( 'jagif_frontend', VIJAGIF_WOO_FREE_GIFT_CSS . 'jagif_frontend.' . $suffix . 'css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );
		wp_enqueue_style( 'jagif_popup_gift', VIJAGIF_WOO_FREE_GIFT_CSS . 'jagif-popup-gift.' . $suffix . 'css', array(), VIJAGIF_WOO_FREE_GIFT_VERSION );
		wp_enqueue_script( 'jagif_frontend', VIJAGIF_WOO_FREE_GIFT_JS . 'jagif_frontend.' . $suffix . 'js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, true );

		$args = array(
			'ajaxurl'                 => admin_url( 'admin-ajax.php' ),
			'user_id'                 => md5( 'jagif' . get_current_user_id() ),
			'nonce'                   => wp_create_nonce( 'jagif-nonce' ),
			'alert_stock'             => esc_html__( 'Out of stock.', 'jagif-woo-free-gift' ),
			'cart_update_notice'      => esc_html__( 'Updated', 'jagif-woo-free-gift' ) . ' [s].',
			'gb_display_style'        => $this->settings->get_params( 'gb_display_style' ),
			'show_gift_style'         => $this->settings->get_params( 'show_gift_style' ),
			'i18n_not_choose_va_text' => esc_attr__( 'Please select some gift options before adding this product to your cart.', 'jagif-woo-free-gift' ),
		);
		wp_localize_script( 'jagif_frontend', 'jagif_frontend_param', $args );
		if ( ( is_product() || is_shop() || is_archive() ) && ! $this->is_customize ) {

			$css = $this->get_inline_css();
			wp_add_inline_style( 'jagif_frontend', $css );
		}
		if ( is_product() || is_cart() || is_archive() ) {
			add_action( 'wp_footer', array( $this, 'frontend_html' ) );
		}
		$this->functions->get_display_conditions();

		$custom_css = esc_attr( $this->settings->get_params( 'custom_css' ) );
		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( 'jagif_frontend', esc_attr( $custom_css ) );
		}
		if ( is_cart() ) {
			wp_enqueue_script( 'jagif_cart', VIJAGIF_WOO_FREE_GIFT_JS . 'jagif-cart.' . $suffix . 'js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, true );
		}
	}

	public function frontend_html() {
		?>
        <div class="jagif-variation-wrap jagif-disabled">
            <div class="jagif-popup-var-overlay"></div>
            <div class="jagif-variation-popup jagif-popup-var-content-close"></div>
        </div>
		<?php
	}

	public function jagif_init_frontend() {

	}

	public function jagif_single_popup_icon() {

	}

	public function get_inline_css() {
		$css = '';
		if ( $ic_position_default = $this->settings->get_params( 'ic_position' ) ) {
			$ic_position = $ic_position_default == 0 ? 'left' : 'right';
			$css         .= '.jagif_badge-gift-icon .jagif-icon-gift {
								' . $ic_position . ': 0 ;
							}';
		}
		if ( $ic_color_default = $this->settings->get_params( 'ic_color' ) ) {
			$css         .= '.jagif_badge-gift-icon  div.jagif-icon-gift >i {
								color:' . $ic_color_default . ';
							}';
		}
		if ( $ic_background_default = $this->settings->get_params( 'ic_background' ) ) {
			$css         .= '.jagif_badge-gift-icon  .jagif-icon-gift {
								background-color:' . $ic_background_default . ';
							}';
		}
		if ( $ic_size = $this->settings->get_params( 'ic_size' ) ) {
			$ic_size_mobile = $ic_size > 20 ? $ic_size - 5 : 10;
			$css            .= '.jagif_badge-gift-icon .jagif-icon-gift{
					                width: ' . $ic_size . 'px ;
					                height: ' . $ic_size . 'px ;
					            }
					            @media screen and (max-width: 768px) {
					                .jagif_badge-gift-icon .jagif-icon-gift{
					                    width: ' . $ic_size_mobile . 'px ;
					                    height: ' . $ic_size_mobile . 'px ;
					                }
					            }';
			$css            .= '.jagif_badge-gift-icon .jagif-icon-gift i:before {
					                font-size: ' . $ic_size . 'px ;
					            }
					            @media screen and (max-width: 768px) {
					                .jagif_badge-gift-icon .jagif-icon-gift i:before {
					                    font-size: ' . $ic_size_mobile . 'px ;
					                }
					            }';
		}

		if ( $box_font_size = $this->settings->get_params( 'box_font_size' ) ) {
			$css .= '.jagif-popup-gift-products-wrap, .jagif-free-gift-promo-content {
					        font-size: ' . $box_font_size . 'px ;
					    }';
		}
		if ( $box_title_font_size = $this->settings->get_params( 'box_title_font_size' ) ) {

			$css .= '.jagif-free-gift-promo_title{
					        font-size: ' . $box_title_font_size . 'px ;
					    }';
		}
		if ( $ic_horizontal = $this->settings->get_params( 'ic_horizontal' ) ) {
			$ic_horizontal_mobile = $ic_horizontal > 20 ? $ic_horizontal - 10 : 0;
			$css                  .= '.jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-0{
					                left: ' . $ic_horizontal . 'px ;
					            }
					            .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-1{
					                right: ' . $ic_horizontal . 'px ;
					            }
					            @media screen and (max-width: 768px) {
					                .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-0{
					                    left: ' . $ic_horizontal_mobile . 'px ;
					                }
					            }
					            @media screen and (max-width: 768px) {
					                .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-1{
					                    right: ' . $ic_horizontal_mobile . 'px ;
					                }
					            }';
		}
		if ( $ic_vertical = $this->settings->get_params( 'ic_vertical' ) ) {
			$ic_vertical_mobile = $ic_vertical > 20 ? $ic_vertical - 10 : 0;
			$css                .= '.jagif_badge-gift-icon .jagif-icon-gift{
					                top: ' . $ic_vertical . 'px ;
					            }
					            @media screen and (max-width: 768px) {
					                .jagif_badge-gift-icon .jagif-icon-gift{
					                    top: ' . $ic_vertical_mobile . 'px ;
					                }
					            }';
		}
		$css .= $this->add_inline_style(
			array( '.jagif-free-gift-promo-item .item-gift a' ),
			array( 'gift_name_color' ),
			array( 'color' ),
			array( '' )
		);
		$css .= $this->add_inline_style(
			array( '.jagif-gifts-package .gift-pack-check label' ),
			array( 'gift_title_color' ),
			array( 'color' ),
			array( '' )
		);
		$css .= $this->add_inline_style(
			array( '.jagif-free-gift-promo-item .item-gift a:hover' ),
			array( 'gift_name_hover_color' ),
			array( 'color' ),
			array( '' )
		);
		$css .= $this->add_inline_style(
			array( '.jagif-free-gift-promo_title' ),
			array( 'title_box_color', ),
			array( 'color' ),
			array( '' )
		);
		$css = str_replace( array( "\r", "\n", '\r', '\n' ), ' ', $css );

		return $css;
	}

	public function gift_before_add_to_cart_form() {
		if ( is_admin() ) {
			return;
		}
		if ( is_product() && is_single() ) {
			/*single product page*/
			global $post;
			$product_id = $post->ID;
			$product    = wc_get_product( $product_id );
			if ( $product ) {
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'jagif_cart_before' ) );
				if ( ! $product->is_in_stock() ) {
					return;
				}
			}
		}
	}

	public function jagif_single_product_lightbox_summary() {
		if ( $this->is_customize ) {
			return;
		}
		self::show_single_gift(0 );
	}

	public function jagif_cart_before() {
		self::show_single_gift(3 );
	}

	public function insert_after_add_to_cart_form() {
		self::show_single_gift(0 );
	}

	public function jagif_before_template( $template_name ) {
		switch ( $template_name ) {
			case 'single-product/price.php':
				self::show_single_gift(4 );
				break;
			default:
				return;
		}
	}

	public function insert_after_single_product_summary() {
		self::show_single_gift(1 );
	}

	public function jagif_after_template( $template_name ) {
		switch ( $template_name ) {
			case 'single-product/price.php':
				self::show_single_gift(5 );
				break;
			default:
				return;
		}
	}

	function show_single_gift( $position ) {
		if ( ! is_product() ) {
			return;
		}
		$gb_display_style = $this->settings->get_params( 'gb_display_style' );
		if ( $gb_display_style != $position && ! $this->is_customize ) {
			return;
		}
		$product_id = get_the_ID();
		$product    = wc_get_product( $product_id );
		if ( ! $product->is_in_stock() || ! $product->is_purchasable() ) {
			return;
		}
		$get_gift_item = $this->functions->scan_rule( 'all', $product_id, 1 );
		$get_gift_item = VIJAGIF_HELPER::jagif_get_single_conditions( $get_gift_item );
		if ( isset( $get_gift_item ) && empty( $get_gift_item ) && ! is_array( $get_gift_item ) ) {
			return;
		}
		$check_cart = true;
		if ( ! $check_cart && ! $this->settings->get_params( 'overall_notice' ) ) {
			return;
		}

		$enable_link_gift = $this->settings->get_params( 'enable_link_gift' );
		$box_title        = $this->settings->get_params( 'box_title' );
		$class = trim( implode( ' ', array(
			'jagif-free-gift-wrap-position-' . $position,
			$gb_display_style != $position && $this->is_customize ? 'jagif-disabled' : '',
		) ) );

		$gb_style = $this->settings->get_params( 'show_gift_style' );

		$class_type_1 = trim( implode( ' ', array(
			$gb_display_style == $position ? 'active' : ''
		) ) );

		$temp_param = array(
			'class'            => $class,
			'gb_display_style' => $gb_display_style,
			'box_title'        => $box_title,
			'gb_style'         => $gb_style,
			'class_type_1'     => $class_type_1,
			'enable_link_gift' => $enable_link_gift,
		);
		self::jagif_insert_gift_template( $get_gift_item, $temp_param );
	}

	function jagif_insert_gift_template( $get_gift_item, $params ) {
		$get_gift_item = $this->functions->sort_display_gift( $get_gift_item );
		foreach ( $get_gift_item as $gift_item ) {
			if ( $gift_item['is_apply'] ) {
				?>
                <div class="jagif-free_gift_wrap jagif-gift-available  <?php echo esc_attr( $params['class'] ); ?> jagif<?php echo esc_attr( $gift_item['rule_id'] ) ?>"
                     id="jagif-free_gift_wrap"
                     data-position="<?php echo esc_attr( $params['gb_display_style'] ) ?>"
                     data-rule="<?php echo esc_attr( $gift_item['rule_id'] ) ?>">
                    <div class="jagif-free-gift-promo_title jagif-collapse-title"
                         data-active="1"><?php echo esc_html( $params['box_title'] ); ?></div>
					<?php
					wc_get_template( 'jagif-template-gift-content-1.php', array(
						'get_gift_item'    => $gift_item,
						'class_type_1'     => $params['class_type_1'],
						'enable_link_gift' => $params['enable_link_gift'],
					), '', VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
					?>
                </div>
				<?php
			} else {
				?>
                <div class="jagif-free_gift_wrap jagif-gift-not-available jagif-rule-<?php
				echo esc_attr( $gift_item['rule_id'] ) ?> <?php echo esc_attr( $params['class'] ); ?> "
                     id="jagif-free_gift_wrap"
                     data-position="<?php echo esc_attr( $params['gb_display_style'] ) ?>"
                     data-rule="<?php echo esc_attr( $gift_item['rule_id'] ) ?>">
                    <div class="jagif-free-gift-promo_title jagif-collapse-title"
                         data-active="1"><?php echo esc_html( $params['box_title'] ); ?></div>
					<?php
					wc_get_template( 'jagif-template-gift-min.php', array(
						'get_gift_item'    => $gift_item,
						'class_type_1'     => $params['class_type_1'],
						'enable_link_gift' => $params['enable_link_gift'],
					), '', VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
					?>
                </div>
				<?php
			}
		}
	}

	public function jagif_insert_product_icon_gift( $html ) {
		ob_start();
		if ( is_product() ) {
			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );
			if ( ! $product->is_in_stock() || ! $product->is_purchasable() ) {
				return $html;
			}
			$get_gift_item = $this->functions->check_assign_gift( $product_id );
			if ( isset( $get_gift_item ) && empty( $get_gift_item ) ) {
				return $html;
			}
			$ic_enable_single_product = $this->settings->get_params( 'ic_enable_single_product' );
//			$check_cart               = $this->functions->jagif_qty_gift_in_cart( $product_id, $get_gift_item );
//			if ( ! $check_cart ) {
//				return $html;
//			}
			echo wp_kses_post( $this->jagif_get_image_icon( 'single_product', $ic_enable_single_product ) );
		}
		$result  = ob_get_clean();
		$pattern = '/<img.+?>/';
		preg_match( $pattern, $html, $matches );
		$replace = $result . $matches[0];
		$html    = str_replace( $matches[0], $replace, $html );

		return $html;
	}

	public function insert_archive_icon_gift() {
		if ( is_admin() || is_checkout() ) {
			return;
		}
		if ( $this->is_widget === true ) {
			return;
		}
		$product_id = get_the_ID();
		$product    = wc_get_product( $product_id );
		if ( ! $product->is_in_stock() || ! $product->is_purchasable() ) {
			return;
		}
		$get_gift_item = $this->functions->check_assign_gift( $product_id );
		if ( isset( $get_gift_item ) && empty( $get_gift_item ) ) {
			return;
		}
		$ic_enable_shop = $this->settings->get_params( 'ic_enable_shop' );
//		$check_cart     = $this->functions->jagif_qty_gift_in_cart( $product_id, $get_gift_item );
//		if ( ! $check_cart ) {
//			return;
//		}
		update_option( 'jagif_list_product_gift', $product_id );
		echo wp_kses_post( $this->jagif_get_image_icon( 'archive', $ic_enable_shop ) );
	}

	public function jagif_get_class_icon() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$result   = array(
			'status'  => '',
			'message' => '',
		);
		$settings = new VIJAGIF_WOO_FREE_GIFT_DATA();
		$icon_id  = isset( $_POST['icon_id'] ) ? sanitize_text_field( wp_unslash( $_POST['icon_id'] ) ) : '';
		$type     = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		if ( is_numeric( $icon_id ) && $type && $class = $settings->get_class_icon( $icon_id, $type ) ) {
			$result['status']  = 'success';
			$result['message'] = $class;
		}
		if ( is_numeric( $icon_id ) && $type && $type == 'icon_image' ) {
			$result['status']  = 'success';
			$icon_src = wp_get_attachment_image_src( $icon_id );
			if ( is_array( $icon_src ) ) $icon_src = $icon_src[0];
			$result['message'] = $icon_src;
		}
		wp_send_json( $result );
	}

	public function jagif_get_image_icon( $page, $enable ) {
		$image         = '';
		$data_items    = array();
		$data_packs    = '';
		$product_id    = get_the_ID();
		$gift_items    = $this->functions->jagif_get_gift_item_ids( $product_id );
		$disable       = ( ! is_product() && ! is_archive() && ! is_category() ) || empty( $enable ) ? 'jagif-disabled' : '';
		$icon_position = $this->settings->get_params( 'ic_position' );
		$icon_default  = $this->settings->get_params( 'icon_default' );
		$icon_image    = $this->settings->get_params( 'icon_image' );
		if ( empty( $gift_items ) || ! is_array( $gift_items ) ) {
			return '';
		}
		foreach ( $gift_items as $gift_rule ) {
			if ( $gift_rule['rule_id'] != 'single' ) continue;
			foreach ( $gift_rule['gift_ids'] as $key => $item ) {
				$gift_rule['gift_ids'][ $key ] = wc_get_product( $item )->get_name();
				$data_items[]                  = wc_get_product( $item )->get_name();
			}
			$data_packs = empty( $data_packs ) ? $gift_rule['pack_id'] : ',' . $gift_rule['pack_id'];
		}
		if ( empty( $data_packs ) ) return '';
		$data_gift = array(
			'data-jagif_items'     => wp_json_encode( $data_items ),
			'data-jagif_qty_order' => '',
			'data-jagif_pack_id'   => $data_packs ? $data_packs : '',
			'data-position'        => $icon_position,
			'data-old_position'    => '',
		);
		$class     = array(
			'jagif-icon-gift',
			'jagif-preview-icon-position',
			'jagif-preview-icon-position-' . $icon_position,
			'jagif-preview-icon-is-' . $page,
			$disable
		);
		$class     = trim( implode( ' ', $class ) );
		if ( $this->is_customize ) {
			$image = '<div class="jagif_badge-gift-icon" >';
			if ( ! empty( $icon_image ) ) {
				$is_icon_custom = true;
				$icon_src = wp_get_attachment_image_src( $icon_image );
				if ( is_array( $icon_src ) ) $icon_src = $icon_src[0];
				$image .= sprintf( '<img class="%s" %s src="%s">',
					esc_attr( $class ),
					wc_implode_html_attributes( $data_gift ),
					esc_attr( $icon_src )
				);
			} else {
				$image .= sprintf( '<img class="jagif-hidden %s" %s src="">',
					esc_attr( $class ),
					wc_implode_html_attributes( $data_gift )
				);
			}
			$icon_src = $this->settings->get_class_icon( $icon_default, 'gift_icons' );
			if ( isset( $is_icon_custom ) ) {
				$class_custom = ' jagif-hidden';
			} else {
				$class_custom = '';
			}
			$image .= sprintf( '<div class="%s" %s><i class="%s"></i></div></div>',
				esc_attr( $class ) . $class_custom,
				wc_implode_html_attributes( $data_gift ),
				esc_attr( $icon_src )
			);
		} else {
			if ( ! empty( $icon_image ) ) {
				$icon_src = wp_get_attachment_image_src( $icon_image );
				if ( is_array( $icon_src ) ) $icon_src = $icon_src[0];
				$image = sprintf( '<div class="jagif_badge-gift-icon" ><img class="%s" %s src="%s"></div>',
					esc_attr( $class ),
					wc_implode_html_attributes( $data_gift ),
					esc_attr( $icon_src )
				);
			} else {
				$icon_src = $this->settings->get_class_icon( $icon_default, 'gift_icons' );
				$image = sprintf( '<div class="jagif_badge-gift-icon" ><div class="%s" %s><i class="%s"></i></div></div>',
					esc_attr( $class ),
					wc_implode_html_attributes( $data_gift ),
					esc_attr( $icon_src )
				);
			}
		}

		return $image;
	}

	public static function add_inline_style( $element, $name, $style, $suffix = '' ) {
		if ( ! $element || ! is_array( $element ) ) {
			return '';
		}
		$settings = new VIJAGIF_WOO_FREE_GIFT_DATA();
		$element  = implode( ',', $element );
		$return   = $element . '{';
		if ( is_array( $name ) && count( $name ) ) {
			foreach ( $name as $key => $value ) {
				$get_value  = $settings->get_params( $value );
				$get_suffix = $suffix[ $key ] ? $suffix[ $key ] : '';
				$return     .= $style[ $key ] . ':' . $get_value . $get_suffix . ';';
			}
		}
		$return .= '}';

		return $return;
	}

	public function jagif_loop_add_to_cart_args( $args ) {
		global $product;
		if ( ! $product->is_in_stock() || ! $product->is_purchasable() ) {
			return $args;
		}

		$gift_items = $this->functions->jagif_get_gift_item_ids( $product->get_id() );
		if ( empty( $gift_items ) ) {
			return $args;
		}

		$pack_id       = $gift_items['pack_id'] ? $gift_items['pack_id'] : '';
		$gift_ids      = $gift_items['gift_ids'] ? $gift_items['gift_ids'] : '';
		$list_gift_ids = $gift_ids ? implode( ',', $gift_items['gift_ids'] ) : '';

		if ( $pack_id ) {
			if ( wc_get_product( $pack_id )->is_in_stock() ) {
				$args['attributes']['data-jagif_ids']     = $list_gift_ids;
				$args['attributes']['data-jagif_pack_id'] = $pack_id;
			}
		}

		return $args;
	}

	public function jagif_add_gift_ids_to_cart_button() {
		global $product;
		$product_id    = get_the_ID();
		$rule_ids      = array();
		$pack_ids      = array();
		$gift_ids      = array();
		$list_gift_ids = '';

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_in_stock() || ! $product->is_purchasable() ) {
			return;
		}
		$get_gift_ids = $this->functions->jagif_get_gift_item_ids( $product_id );

		if ( empty( $get_gift_ids ) ) {
			return;
		}
		foreach ( $get_gift_ids as $pack_data ) {
			if ( $pack_data['rule_id'] != 'single' ) {
				continue ;
			} elseif ( $this->settings->get_params( 'override_type' ) == 'all' ) {
				continue;
			};
			$rule_ids[] = $pack_data['rule_id'] ?$pack_data['rule_id'] : '';
			$pack_ids[] = $pack_data['pack_id'] ?$pack_data['pack_id'] : '';
			if ( isset( $pack_data['gift_ids'] ) && is_array( $pack_data['gift_ids'] ) ) {
				foreach ( $pack_data['gift_ids'] as $_item ) {
					if ( empty( $gift_ids[ $pack_data['pack_id'] ] ) ) {
						$gift_ids[ $pack_data['pack_id'] ] = $_item;
					} else {
						$gift_ids[ $pack_data['pack_id'] ] .= ',' . $_item;
					}
				}
			}
		}
		$rule_id       = $rule_ids ? implode( ',', $rule_ids ) : '';
		$pack_id       = $pack_ids ? implode( ',', $pack_ids ) : '';
		$list_gift_ids = $gift_ids ? implode( '|', $gift_ids ) : '';

		if ( $pack_id ) {?>
            <input type="hidden" data-id="<?php echo esc_attr( $product_id ); ?>"
                   name="<?php echo esc_attr( 'jagif_ids' ); ?>" class="jagif-ids"
                   value="<?php echo esc_attr( $list_gift_ids ); ?>">
            <input type="hidden" name="<?php echo esc_attr( 'jagif_pack_id' ); ?>" class="jagif_pack_id"
                   value="<?php echo esc_attr( $pack_id ); ?>">
            <input type="hidden" name="<?php echo esc_attr( 'jagif_rule_id' ); ?>" class="jagif_rule_id"
                   value="<?php echo esc_attr( $rule_id ); ?>">
			<?php
		}
	}

	public function jagif_add_to_cart_quantity( $quantity, $product_id ) {
		$_product = wc_get_product( $product_id );
		if ( $_product ) {
			$check_assign = $this->functions->check_assign_gift( $product_id );
			if ( isset( $_REQUEST['_jagif_frontend_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_jagif_frontend_nonce'] ) ), 'jagif_frontend_nonce' ) ) {
				return $quantity;
			}
			if ( ! empty( $check_assign ) ) {
				if ( isset( $_REQUEST['jagif_ids'] ) && isset( $_REQUEST['jagif_pack_id'] ) ) {
					$ids     = wc_clean( wp_unslash( $_REQUEST['jagif_ids'] ) );
					$ids     = explode( ',', $ids );
					$pack_id = wc_clean( wp_unslash( $_REQUEST['jagif_pack_id'] ) );
				}
				if ( ! empty( $ids ) && ! empty( $pack_id ) ) {
					foreach ( WC()->cart->cart_contents as $cart_content_key => $cart_content ) {
						if ( isset( $cart_content['jagif_items'] ) && $product_id == $cart_content['product_id'] ) {
							if ( isset( $cart_content['jagif_pack_id'] ) && $cart_content['jagif_pack_id'] == $pack_id ) {
								$check_ids_diff = array_diff( $ids, $cart_content['jagif_ids'] );
								if ( $check_ids_diff ) {
									$quantity = $quantity + $cart_content['quantity'];
								}
							}
						}
					}
				}
			}
		}

		return $quantity;
	}

	public function jagif_add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
		if ( isset( $cart_item_data['jagif_rule_id'] ) ) {
			return $cart_item_data;
		}
		if ( isset( $_REQUEST['_jagif_frontend_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_jagif_frontend_nonce'] ) ), 'jagif_frontend_nonce' ) ) {
			return $cart_item_data;
		}
		global $jagif_cart_data;
		$_product = wc_get_product( $product_id );
		if ( $_product ) {
			$this->functions->remove_cart_gift( WC()->cart, 'remove', $product_id );

			$cart_items    = WC()->cart->get_cart();
			$cart_qty = 0;
			$cart_category_ids = array();
			$cart_product_ids = array();
			$cart_subtotal = VIJAGIF_HELPER::price_currency_display( floatval( WC()->cart->subtotal ), get_woocommerce_currency() );
			$cart_items_key = array();
			foreach ( $cart_items as $cart_item ) {
				if ( ! isset( $cart_item['jagif_rule_id'] ) && ! isset( $cart_item['jagif_pack_id'] ) ) {
					$current_id         = $cart_item['product_id'];
					$cart_qty += isset( $cart_item['quantity'] ) ? $cart_item['quantity'] : 0;
					$cart_product_ids[] = $current_id;
					$cart_category_ids  = array_merge( wc_get_product_term_ids( $current_id, 'product_cat' ), $cart_category_ids );
					$cart_items_key[] = $cart_item['key'];
				}
			}
			$cart_category_ids  = array_unique( $cart_category_ids );
			$cart_product_ids  = array_unique( $cart_product_ids );

			$cart_data = array('qty' => $cart_qty, 'subtotal' => $cart_subtotal, 'cats' => $cart_category_ids, 'ids' => $cart_product_ids);
			$jagif_cart_data = array();
			$jagif_cart_data['cart_data'] = $cart_data;
			$jagif_cart_data['cart_keys'] = $cart_items_key;

			$check_assign = $this->functions->check_assign_gift( $product_id, $quantity, $cart_item_data );
			if ( ! empty( $check_assign ) ) {
				if ( isset( $_REQUEST['jagif_ids'] ) && isset( $_REQUEST['jagif_pack_id'] ) && isset( $_REQUEST['jagif_rule_id'] ) ) {
					$ids_arr = wc_clean( wp_unslash( $_REQUEST['jagif_ids'] ) );
					$ids_arr = explode( '|', $ids_arr );
					foreach ( $ids_arr as $val ) {
						$ids[] = explode( ',', $val );
					}
					$pack_id = wc_clean( wp_unslash( $_REQUEST['jagif_pack_id'] ) );
					$rule_id = wc_clean( wp_unslash( $_REQUEST['jagif_rule_id'] ) );
					unset( $_REQUEST['jagif_ids'] );
					unset( $_REQUEST['jagif_pack_id'] );
					unset( $_REQUEST['jagif_rule_id'] );
				}
				if ( ! empty( $ids ) && ! empty( $pack_id ) && ! empty( $rule_id ) ) {
					foreach ( WC()->cart->cart_contents as $cart_content_key => $cart_content ) {
						if ( isset( $cart_content['jagif_items'] ) && $product_id == $cart_content['product_id'] ) {
							if ( isset( $cart_content['jagif_pack_id'], $cart_content['jagif_ids'] ) && $cart_content['jagif_pack_id'] == $pack_id ) {
								$check_ids_diff = array_diff( $ids, $cart_content['jagif_ids'] );
								if ( $check_ids_diff ) {
									array_merge( $ids, $cart_content['jagif_ids'] );
									WC()->cart->remove_cart_item( $cart_content_key );
								}
							}
						}
					}
					$cart_item_data['jagif_ids'] = $ids;
					$cart_item_data['jagif_items'] = $this->get_data( $product_id, $variation_id, $ids, $pack_id, $rule_id, $quantity, $cart_item_data );
				}
			}
		}

		return $cart_item_data;
	}

	function jagif_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		global $jagif_cart_data;
		if ( ! empty( $cart_item_data['jagif_items'] ) ) {
			$this_cart = '';
			if ( isset( $jagif_cart_data['cart_data'] ) && ! empty( $jagif_cart_data['cart_data'] ) ) {
				$this_cart = $jagif_cart_data['cart_data'];
			}
			$this->functions->jagif_add_to_cart_auto( 'resolve_atc', array(
				'key'   => $cart_item_key,
				'items' => $cart_item_data['jagif_items']
			), $product_id, $quantity, $variation_id, $variation, $cart_item_data, $this_cart );
		} elseif ( ! isset( $cart_item_data['jagif_type'] ) ) {
			$this_cart = '';
			if ( isset( $jagif_cart_data['cart_data'] ) && ! empty( $jagif_cart_data['cart_data'] ) ) {
				$this_cart = $jagif_cart_data['cart_data'];
			}
			$this->functions->jagif_add_to_cart_auto( 'single_atc', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data, $this_cart );
		}
	}

	function remove_cart_data($cart_item_key, $input = 1) {
		if ( $input ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['cart_keys'] ) ) {
				unset( WC()->cart->cart_contents[ $cart_item_key ]['cart_keys'] );
			}
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['jagif_ids'] ) ) {
				unset( WC()->cart->cart_contents[ $cart_item_key ]['jagif_ids'] );
			}
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['jagif_items'] ) ) {
				unset( WC()->cart->cart_contents[ $cart_item_key ]['jagif_items'] );
			}
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data'] ) ) {
				if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data']['qty'] ) ) {
					unset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data']['qty'] );
				}
				if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data']['subtotal'] ) ) {
					unset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data']['subtotal'] );
				}
				if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data']['cats'] ) ) {
					unset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data']['cats'] );
				}
				if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data']['ids'] ) ) {
					unset( WC()->cart->cart_contents[ $cart_item_key ]['cart_data']['ids'] );
				}
			}
		} else{

		}
	}

	function jagif_get_cart_item_from_session( $cart_item, $session_values ) {
		if ( isset( $session_values['jagif_ids'] ) && ! empty( $session_values['jagif_ids'] ) ) {
			$cart_item['jagif_ids'] = $session_values['jagif_ids'];
		}

		if ( isset( $session_values['jagif_parent_id'] ) ) {
			$cart_item['jagif_item']       = 'yes';
			$cart_item['jagif_parent_id']  = $session_values['jagif_parent_id'];
			$cart_item['jagif_parent_key'] = $session_values['jagif_parent_key'];
			$cart_item['jagif_qty']        = $session_values['jagif_qty'];
			$cart_item['jagif_pack_id']    = $session_values['jagif_pack_id'];
		}

		return $cart_item;
	}

	function jagif_order_formatted_line_subtotal( $subtotal, $order_item ) {
		if ( isset( $order_item['_jagif_parent_id'] ) ) {
			return '';
		}

		return $subtotal;
	}

	function jagif_get_cart_contents( $cart_contents ) {
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['jagif_parent_id'] ) ) {
				$cart_item['data']->set_price( 0 );
			}
		}

		return $cart_contents;
	}

	function jagif_after_cart_item_quantity_update( $cart_item_key, $new_quantity, $old_quantity, $cart ) {
		if ( isset( $_REQUEST['_jagif_frontend_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_jagif_frontend_nonce'] ) ), 'jagif_frontend_nonce' ) ) {
			return;
		}
		$update_cart = isset( $_POST['update_cart'] ) ? sanitize_text_field( wp_unslash( $_POST['update_cart'] ) ) : '';
		$product_id = $cart->cart_contents[ $cart_item_key ]['product_id'];
		if ( ! empty( $update_cart ) && ! isset( $cart->cart_contents[ $cart_item_key ]['jagif_pack_id'] ) ) {
			$this->functions->remove_cart_gift( $cart, 'qty', $product_id, array( 'key' => $cart_item_key, 'old' => $old_quantity, 'new' => $new_quantity ) );
			$this->functions->jagif_add_to_cart_auto( 'qty', array(
				'key' => $cart_item_key,
				'qty' => $old_quantity
			), $product_id = 0, $quantity = 0, $variation_id = '', $variation = '', $cart_item_data = '', $cart );
		}
	}

	function jagif_cart_item_name( $item_name, $item ) {
		return $item_name;
	}

	function jagif_cart_item_price( $price, $cart_item, $cart_item_key ) {
		$free_label = '<span class="amount"></span>';
		if ( isset( $cart_item['jagif_item'] ) && $cart_item['jagif_item'] == 'yes' ) {

			return $free_label;
		} else {

			return $price;
		}
	}

	function jagif_cart_item_subtotal( $price, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['jagif_item'] ) && $cart_item['jagif_item'] == 'yes' ) {
			$display_type = $this->settings->get_params( 'price_in_cart' );
			if ( $this->is_customize ) {
				$icon_image = $this->settings->get_params( 'icon_image' );
				$check_free = $display_type != "free" ? " jagif-hidden" : ' ';
				$check_null = $display_type != "" ? " jagif-hidden" : ' ';
				$check_zero = $display_type != "0" ? " jagif-hidden" : ' ';
				$check_icon_cus = $display_type != "icon" || empty( $icon_image ) ? " jagif-hidden" : ' ';
				$check_icon_def = $display_type != "icon" || ! empty( $icon_image ) ? " jagif-hidden" : ' ';

				$price_display = '<div class="jagif-cart-icon-customize-free jagif-cart-icon-price' . $check_free . '">' .
				                 esc_html__( 'Free', 'jagif-woo-free-gift' ) . '</div>';
				$price_display .= '<div class="jagif-cart-icon-customize-null jagif-cart-icon-price' . $check_null . '"></div>';
				$price_display .= '<div class="jagif-cart-icon-customize-zero jagif-cart-icon-price' . $check_zero . '">' .
				                  wc_price( VIJAGIF_HELPER::price_currency_display( floatval( 0 ), get_woocommerce_currency(), 'set' ) ) . '</div>';

				if ( ! empty( $icon_image ) ) {
					$icon_src = wp_get_attachment_image_src( $icon_image );
					if ( is_array( $icon_src ) ) {
						$icon_src = $icon_src[0];
					}
				} else {
					$icon_src = '';
				}
				$price_display .= '<img class="jagif-cart-icon-customize-image jagif-cart-icon-price' . $check_icon_cus . '" src="' . esc_url( $icon_src ) . '">';
				$icon_default  = ! empty( $this->settings->get_params( 'icon_default' ) ) ? $this->settings->get_params( 'icon_default' ) : 15;
				$icon_src      = $this->settings->get_class_icon( $icon_default, 'gift_icons' );
				$price_display .= '<div class="jagif-cart-icon-customize-font jagif-cart-icon-price' . $check_icon_def .
				                  '" title="' . esc_attr__( 'Gift', 'jagif-woo-free-gift' ) . '">
						            <i class="' . $icon_src . '"></i></div>';
			} else {
				switch ( $display_type ) {
					case 'free':
						$price_display = esc_html__( 'Free', 'jagif-woo-free-gift' );
						break;
					case '':
						$price_display = '';
						break;
					case 'icon':
						$icon_image = $this->settings->get_params( 'icon_image' );
						if ( ! empty( $icon_image ) ) {
							$icon_src = wp_get_attachment_image_src( $icon_image );
							if ( is_array( $icon_src ) ) {
								$icon_src = $icon_src[0];
							}
							$price_display = '<img class="jagif-cart-icon-price" src="' . esc_url( $icon_src ) . '">';
						} else {
							$icon_default  = ! empty( $this->settings->get_params( 'icon_default' ) ) ? $this->settings->get_params( 'icon_default' ) : 15;
							$icon_src      = $this->settings->get_class_icon( $icon_default, 'gift_icons' );
							$price_display = '<div class="jagif-cart-icon-price" title="' . esc_attr__( 'Gift', 'jagif-woo-free-gift' ) . '">
						    <i class="' . $icon_src . '"></i></div>';
						}
						break;
					default:
						$price_display = wc_price( VIJAGIF_HELPER::price_currency_display( floatval( 0 ), get_woocommerce_currency(), 'set' ) );
						break;
				}
			}
			$item_id = $cart_item['product_id'];
			$item_qty = $cart_item['quantity'];
			$item_product = wc_get_product( $item_id );
			if ( $item_product ) {
				$item_price = $item_product->get_price() * ( int ) $item_qty;
			}
			$price_display = '<div class="jagif-cart-display-price" data-price="' .
			                 VIJAGIF_HELPER::price_currency_display( floatval( $item_price ), get_woocommerce_currency(), 'set' ) . '">' . $price_display . '</div>';
			$original_price = isset( $item_price ) ? wc_price(VIJAGIF_HELPER::price_currency_display( floatval( $item_price ), get_woocommerce_currency(), 'set' ) ) : '';
			$free_label = '<span class="jagif-subtotal-child amount">' . $original_price . $price_display . '</span>';

			return $free_label;
		} else {

			return $price;
		}
	}

	function jagif_cart_item_quantity( $product_quantity, $cart_item_key, $cart_item ) {
		if ( is_cart() ) {
			if ( isset( $cart_item['jagif_parent_id'] ) ) {
				$product_quantity = sprintf( '<div class="quantity jagif-qty-child">
                                                        <p class="txt-qty-val-child">%1$s</p>
                                                      </div>', $cart_item['quantity'] );
			}
		}

		return $product_quantity;
	}

	function jagif_widget_cart_item_quantity( $widget_qty, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['jagif_parent_id'] ) ) {
			switch ( $this->settings->get_params('price_in_cart' ) ) {
				case 'free':
					$price_display = esc_html__( 'Free', 'jagif-woo-free-gift' );
					break;
				case '':
					$price_display = '';
					break;
				case 'icon':
					$icon_image    = $this->settings->get_params( 'icon_image' );
					$icon_default = ! empty( $this->settings->get_params( 'icon_default' ) ) ? $this->settings->get_params( 'icon_default' ) : 15;
					if ( ! empty( $icon_image ) ) {
						$icon_src = wp_get_attachment_image_src( $icon_default );
						if ( is_array( $icon_src ) ) $icon_src = $icon_src[0];
						$price_display = '<img class="jagif-cart-icon-price" src="' . esc_url( $icon_src ) . '">';
					} else {
						$icon_src = $this->settings->get_class_icon( $icon_default, 'gift_icons' );
						$price_display = '<div class="jagif-cart-icon-price" title="' . esc_attr__( 'Free Gift', 'jagif-woo-free-gift' ) . '">
						    <i class="' . $icon_src . '"></i></div>';
					}
					break;
				default:
					$price_display = wc_price(VIJAGIF_HELPER::price_currency_display( floatval( 0 ), get_woocommerce_currency(), 'set' ) );
					break;
			}

			$widget_qty = str_replace('<span class="amount"></span>', '<span class="amount">' . $price_display . '</span>', $widget_qty);
		}

		return $widget_qty;
	}

	function jagif_cart_contents_count( $count ) {
		$cart_contents = WC()->cart->cart_contents;
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( ! empty( $cart_item['jagif_parent_id'] ) ) {
				$count -= $cart_item['quantity'];
			}
		}

		return $count;
	}

	function jagif_cart_item_remove_link( $link, $cart_item_key ) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if ( isset( $cart_item['jagif_item'] ) && $cart_item['jagif_item'] == 'yes' ) {
			return '';
		}

		return $link;
	}

	function jagif_add_custom_price( $cart_object ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		foreach ( $cart_object->get_cart() as $key => $value ) {

			if ( isset( $value['jagif_parent_id'] ) ) {
				$value['data']->set_price( 0 );
			}
		}
	}

	function jagif_cart_item_removed( $cart_item_key, $cart ) {
		if ( ! isset( $cart->removed_cart_contents[ $cart_item_key ]['jagif_rule_id'] ) && ! isset( $cart->removed_cart_contents[ $cart_item_key ]['jagif_pack_id'] ) ) {
			$prd_id = isset( $cart->removed_cart_contents[ $cart_item_key ]['product_id'] ) ? $cart->removed_cart_contents[ $cart_item_key ]['product_id'] : '';
			$this->functions->remove_cart_gift( $cart, 'remove', $prd_id, $cart_item_key );
			$prd_qty      = isset( $cart->removed_cart_contents[ $cart_item_key ]['quantity'] ) ? $cart->removed_cart_contents[ $cart_item_key ]['quantity'] : '';
			$prd_var      = isset( $cart->removed_cart_contents[ $cart_item_key ]['variation_id'] ) ? $cart->removed_cart_contents[ $cart_item_key ]['variation_id'] : '';
			$prd_subtotal = isset( $cart->removed_cart_contents[ $cart_item_key ]['line_subtotal'] ) ? $cart->removed_cart_contents[ $cart_item_key ]['line_subtotal'] : 0;
			$prd_subtotal += isset( $cart->removed_cart_contents[ $cart_item_key ]['line_subtotal_tax'] ) ? $cart->removed_cart_contents[ $cart_item_key ]['line_subtotal_tax'] : 0;
			$prd_total    = isset( $cart->removed_cart_contents[ $cart_item_key ]['line_total'] ) ? $cart->removed_cart_contents[ $cart_item_key ]['line_total'] : 0;
			$prd_total    += isset( $cart->removed_cart_contents[ $cart_item_key ]['line_tax'] ) ? $cart->removed_cart_contents[ $cart_item_key ]['line_tax'] : 0;
			$this->functions->jagif_add_to_cart_auto( 'remove', $cart_item_key, $prd_id, $prd_qty, '', '', array(
				'subtotal' => $prd_subtotal,
				'total'    => $prd_total
			), $cart );
		}
	}

	function jagif_cart_item_restored( $cart_item_key, $cart ) {
		$product_id   = $cart->cart_contents[ $cart_item_key ]['product_id'];
		$quantity     = isset( $cart->cart_contents[ $cart_item_key ]['quantity'] ) ? $cart->cart_contents[ $cart_item_key ]['quantity'] : 0;
		$variation_id = isset( $cart->cart_contents[ $cart_item_key ]['variation_id'] ) ? $cart->cart_contents[ $cart_item_key ]['variation_id'] : '';
		$variation    = isset( $cart->cart_contents[ $cart_item_key ]['variation'] ) ? $cart->cart_contents[ $cart_item_key ]['variation'] : '';
		$this->functions->jagif_add_to_cart_auto( 'restored', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart );
	}

	function jagif_item_class( $class, $cart_item ) {
		if ( isset( $cart_item['jagif_parent_id'] ) ) {
			$class .= ' jagif-cart-item jagif-cart-child';
		} elseif ( isset( $cart_item['jagif_item'] ) ) {
			$class .= ' jagif-cart-item jagif-cart-parent';
		}

		return $class;
	}

	function jagif_mini_item_class( $class, $cart_item ) {
		if ( isset( $cart_item['jagif_parent_id'] ) ) {
			$class .= ' jagif-cart-item jagif-cart-child jagif-mini-cart';
		} elseif ( isset( $cart_item['jagif_item'] ) ) {
			$class .= ' jagif-cart-item jagif-cart-parent jagif-mini-cart';
		}

		return $class;
	}

	function jagif_order_again_cart_item_data( $data, $cart_item ) {
		if ( isset( $cart_item['jagif_items'] ) ) {
			$data['jagif_order_again'] = 'yes';
			$data['jagif_items']       = $cart_item['jagif_items'];
		}

		if ( isset( $cart_item['jagif_parent_id'] ) ) {
			$data['jagif_order_again'] = 'yes';
			$data['jagif_parent_id']   = $cart_item['jagif_parent_id'];
		}

		return $data;
	}

	function jagif_cart_loaded_from_session() {
		$cart_content = WC()->cart->cart_contents;
		foreach ( $cart_content as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['jagif_order_again'], $cart_item['jagif_parent_id'] ) ) {
				WC()->cart->remove_cart_item( $cart_item_key );
			}

			if ( isset( $cart_item['jagif_order_again'], $cart_item['jagif_ids'] ) ) {
				$this->functions->jagif_add_to_cart_items( $cart_item['jagif_items'], $cart_item_key, $cart_item['product_id'], $cart_item['quantity'] );
			}
		}
	}

	function get_data( $product_id, $variation_id, $ids, $pack_id, $rule_id, $quantity, $cart_item_data ) {
		$gift_item_def   = $this->functions->get_default_gift( $product_id, $quantity, $cart_item_data );
		$pack_id         = explode( ',', $pack_id );
		$rule_id         = explode( ',', $rule_id );
		$gift_item_match = array();
		if ( ( isset( $gift_item_def ) && empty( $gift_item_def ) ) ) {
			return false;
		}
		foreach ( $gift_item_def as $gift_aval ) {
			if ( ! empty( $gift_aval ) && isset( $gift_aval['rule_id'] ) && isset( $gift_aval['gift_id'] ) ) {
				if ( ! in_array( $gift_aval['rule_id'], $rule_id ) ) {
					return false;
				}
				if ( ! empty( $gift_aval['gift_id'] ) && is_array( $gift_aval['gift_id'] ) ) {
					foreach ( $gift_aval['gift_id'] as $pack_aval_id => $pack_aval ) {
						if ( ! in_array( $pack_aval_id, $pack_id ) ) {
							return false;
						}
					}
				}
			}
		}
		foreach ( $ids as $_k => $_v ) {
			if ( ! empty( $_v ) && is_array( $_v ) ) {
				foreach ( $_v as $k => $v ) {
					$item_input        = explode( '/', $v );
					$item_variation    = count( $item_input ) <= 1 ? $item_input[0] : [ $item_input[1] ];
					$item_variation_id = $item_input[0];
					$item_qty          = isset( $gift_item_def[ $_k ]['gift_id'][ $pack_id[ $_k ] ][ $k ]['archive'] ) ?
						$gift_item_def[ $_k ]['gift_id'][ $pack_id[ $_k ] ][ $k ]['archive'] : 1;
					$item_pack         = $pack_id[ $_k ];
					$item_rule         = $rule_id[ $_k ];
					$item_type         = 'simple';
					if ( isset( $gift_item_def[ $_k ]['gift_id'][ $pack_id[ $_k ] ][ $k ]['archive_id'] ) ) {
						$item_product = wc_get_product( $gift_item_def[ $_k ]['gift_id'][ $pack_id[ $_k ] ][ $k ]['archive_id'] );
						if ( $item_product ) {
							$item_type = $item_product->get_type();
						}
					}
					$gift_item_match[] = array(
						'archive_id'      => $item_variation_id,
						'archive'         => $item_qty,
						'pack_id'         => $item_pack,
						'rule_id'         => $item_rule,
						'jagif_type'      => $item_type,
						'jagif_variation' => $item_variation,
					);
				}
			}
		}

		return $gift_item_match;
	}

	function jagif_update_cart() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		if ( isset( $_POST['jagif_variable_id'], $_POST['jagif_new_variation_id'], $_POST['jagif_new_variation'],
			$_POST['jagif_item_key'] ) ) {
			$_variable_id  = absint( wc_clean( wp_unslash( $_POST['jagif_variable_id'] ) ) );
			$_variation_id = absint( wc_clean( wp_unslash( $_POST['jagif_new_variation_id'] ) ) );
			$_variation    = wc_clean( wp_unslash( $_POST['jagif_new_variation'] ) );
			$_item_key     = wc_clean( wp_unslash( $_POST['jagif_item_key'] ) );

			if ( empty( $_variable_id ) || empty( $_variation_id )
			     || empty( $_variation ) || empty( $_item_key ) ) {
				return;
			}
			$_variation     = explode( "&", $_variation );
			$_variation_arr = array();
			foreach ( $_variation as $attribute ) {
				$attr = explode( '=', $attribute );
				if ( count( $attr ) != 2 ) {
					wp_send_json_error();
				}
				$_variation_arr[ $attr[0] ] = $attr[1];
			}
			$cart_item_data = WC()->cart->cart_contents[ $_item_key ];

			if ( ! isset( $cart_item_data['jagif_index'], $cart_item_data['jagif_qty'], $cart_item_data['jagif_rule_id'],
				$cart_item_data['jagif_pack_id'], $cart_item_data['jagif_type'], $cart_item_data['jagif_parent_id'],
				$cart_item_data['jagif_parent_key'] ) ) {
				wp_send_json_error();
			}
			$_data = array(
				'jagif_index'      => $cart_item_data['jagif_index'],
				'jagif_qty'        => $cart_item_data['jagif_qty'],
				'jagif_rule_id'    => $cart_item_data['jagif_rule_id'],
				'jagif_pack_id'    => $cart_item_data['jagif_pack_id'],
				'jagif_type'       => $cart_item_data['jagif_type'],
				'jagif_parent_id'  => $cart_item_data['jagif_parent_id'],
				'jagif_parent_key' => $cart_item_data['jagif_parent_key'],
			);

			WC()->cart->remove_cart_item( $_item_key );

			$_key = WC()->cart->add_to_cart( $_variable_id, $cart_item_data['quantity'], $_variation_id, $_variation_arr, $_data );

			if ( $_key ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
			wp_die();
		} else {
			wp_send_json_error();
		}
	}

	function jagif_after_cart_item_name( $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['jagif_item'] ) && $cart_item['jagif_item'] == 'yes' ) {
			if ( isset( $cart_item['product_id'] ) && $cart_item['data']->is_type( 'variation' ) ) {
				if ( $cart_item['jagif_type'] == 'variable' ) {
					$variation            = $this->functions->jagif_implode_attribute( $cart_item['variation'] );
					$variation_qty        = $cart_item['jagif_qty'] ?? '';
					$product_gift         = wc_get_product( $cart_item['product_id'] );
					$attributes           = method_exists( $product_gift, 'get_variation_attributes' ) ? $product_gift->get_variation_attributes() : [];
					$available_variations = method_exists( $product_gift, 'get_available_variations' ) ? $product_gift->get_available_variations() : [];
					$variation_count      = count( $product_gift->get_children() );
					$default_attributes   = $product_gift->get_default_attributes();
					$variations_json      = wp_json_encode( $available_variations );
					$variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
					$product_id           = $product_gift->get_id();
					$product_name         = $product_gift->get_name();
					$name_variation       = $cart_item['variation'] ? implode( ', ', $cart_item['variation'] ) : 'Choose variation';
					$data_variation       = $cart_item['variation'] ? implode( ',', $cart_item['variation'] ) : '';
					?>
                    <div class="jagif-cart-change-variation"
                         data-index="<?php echo esc_attr( $cart_item['jagif_index'] ) ?>"
                         data-parent_id="<?php echo esc_attr( $cart_item['jagif_parent_id'] ) ?>"
                         data-variation_key="<?php echo esc_attr( $cart_item['key'] ) ?> "
                         data-parent_key="<?php echo esc_attr( $cart_item['jagif_parent_key'] ) ?>"
                         data-variation_id="<?php echo esc_attr( $cart_item['variation_id'] ) ?>"
                         data-variation="<?php echo esc_attr( $variation ) ?>"
                         data-variable="<?php echo esc_attr( $cart_item['product_id'] ) ?>"
                         data-quantity="<?php echo esc_attr( $variation_qty ) ?>"
                         data-title="<?php echo esc_attr( $cart_item['data']->get_name() ) ?>"
                         title="<?php esc_attr_e( 'Change', 'jagif-woo-free-gift' ); ?>">
                        <i class="pencil alternate icon"></i>
                        <div class="jagif-variation-dropdown">
                            <div class="jagif-pv-content is_cart_change_var">
                                <div class="var-form jagif-variation-form "
                                     data-product_id="<?php echo esc_attr( absint( $product_id ) ); ?>"
                                     data-product_name="<?php echo esc_attr( $product_name ); ?>"
                                     data-variation_count="<?php echo esc_attr( $variation_count ); ?>"
                                     data-product_variations="<?php echo esc_attr( $variations_attr ); ?>">
                                    <table class="jagif-variations" cellspacing="0">
                                        <tbody>
										<?php
										foreach ( $attributes as $attribute_name => $options ) :
											$selected = $default_attributes[ $attribute_name ] ?? $product_gift->get_variation_default_attribute( $attribute_name );
											?>
                                            <tr>
                                                <td class="label">
                                                    <label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
														<?php echo wp_kses_post( ucfirst( wc_attribute_label( $attribute_name ) ) ); ?>
                                                    </label>
                                                </td>
                                                <td class="value">
													<?php
													jagif_dropdown_variation_attribute_options( apply_filters( 'jagif_dropdown_variation_attribute_options', array(
														'options'   => $options,
														'attribute' => $attribute_name,
														'product'   => $product_gift,
														'selected'  => $selected,
														'class'     => 'jagif-attribute-options jagif-va-attribute-options',
													), $attribute_name, $product_gift ) );
													?>
                                                </td>
                                            </tr>
										<?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <div class="jagif-popup-var-close-wrap"><?php esc_html_e( 'X', 'jagif-woo-free-gift' ); ?></div>
                                    <div class="is-out-of-stock jagif-disabled"><?php esc_html_e( 'Out of stock', 'jagif-woo-free-gift' ); ?></div>
                                    <div class="jagif-btn-choose">
                                        <button type="button" class="jagif-cart-change-variation-popup button alt"
                                                data-index="<?php echo esc_attr( $cart_item['jagif_index'] ) ?>"
                                                data-parent_key="<?php echo esc_attr( $cart_item['jagif_parent_key'] ) ?>"
                                                data-variation_key="<?php echo esc_attr( $cart_item['key'] ) ?> "
                                                data-parent_id="<?php echo esc_attr( $cart_item['jagif_parent_id'] ) ?>"
                                                data-variation_id="<?php echo esc_attr( $cart_item['variation_id'] ) ?>"
                                                data-variation="<?php echo esc_attr( $variation ) ?>"
                                                data-variable_id="<?php echo esc_attr( $cart_item['product_id'] ) ?>"
                                                data-quantity="<?php echo esc_attr( $variation_qty ) ?>"
                                                data-title="<?php echo esc_attr( $cart_item['data']->get_name() ) ?>"><?php esc_html_e( 'Change', 'jagif-woo-free-gift' ); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					<?php
				} elseif ( $cart_item['jagif_type'] == 'variation' ) {
					if ( ! isset( $cart_item['variation_id'] ) || empty( $cart_item['variation_id'] ) ) return;
					$product_id           = $cart_item['variation_id'];
					$product_gift         = wc_get_product( $product_id );
					$product_name         = $product_gift->get_name();
					$variation            = $this->functions->jagif_implode_attribute( $cart_item['variation'] );
					$variation_qty        = $cart_item['jagif_qty'] ?? '';

					$v_attributes = $product_gift->get_variation_attributes();
					if ( ! isset( $variable_prd_id, $variable_prd ) ) {
						$variable_prd_id = $product_gift->get_parent_id();
						$variable_prd    = wc_get_product( $variable_prd_id );
					}
					$v_any        = false;
					foreach ( $v_attributes as $attr_val ) {
						if ( empty( $attr_val ) ) {
							$v_any = true;
						}
					}
					if ( $v_any ) {
						$any_detail      = VIJAGIF_WOO_FREE_GIFT_Function::get_variation_from_any( $variable_prd, $product_gift, $v_attributes, $v_any );
						$data_attributes = $any_detail['data'];
						$name_variation  = $any_detail['title'];
						$data_variation  = $any_detail['data_short'];
						?>
                        <div class="jagif-cart-change-variation"
                             data-index="<?php echo esc_attr( $cart_item['jagif_index'] ) ?>"
                             data-parent_id="<?php echo esc_attr( $cart_item['jagif_parent_id'] ) ?>"
                             data-variation_key="<?php echo esc_attr( $cart_item['key'] ) ?> "
                             data-parent_key="<?php echo esc_attr( $cart_item['jagif_parent_key'] ) ?>"
                             data-variation_id="<?php echo esc_attr( $cart_item['variation_id'] ) ?>"
                             data-variation="<?php echo esc_attr( $variation ) ?>"
                             data-variable="<?php echo esc_attr( $cart_item['product_id'] ) ?>"
                             data-quantity="<?php echo esc_attr( $variation_qty ) ?>"
                             data-title="<?php echo esc_attr( $cart_item['data']->get_name() ) ?>"
                             title="<?php esc_attr_e( 'Change', 'jagif-woo-free-gift' ); ?>">
                            <i class="pencil alternate icon"></i>
                            <div class="jagif-variation-dropdown">
                                <div class="jagif-pv-content is_cart_change_var">
                                    <div class="var-form jagif-variation-form jagif-cart-variation-specifically"
                                         data-product_id="<?php echo esc_attr( absint( $product_id ) ); ?>"
                                         data-product_name="<?php echo esc_attr( $product_name ); ?>"
                                         data-variation_count="1"
                                         data-product_variations="">
                                        <table class="jagif-variations" cellspacing="0">
                                            <tbody>
											<?php
											foreach ( $v_attributes as $attribute_name => $options ) :
												$formated_attribute_name = str_replace( 'attribute_', '', $attribute_name );
												?>
                                                <tr>
                                                    <td class="label">
                                                        <label for="<?php echo esc_attr( sanitize_title( $formated_attribute_name ) ); ?>">
															<?php echo wp_kses_post( wc_attribute_label( $formated_attribute_name ) ); ?>
                                                        </label>
                                                    </td>
                                                    <td class="value">
														<?php
														jagif_dropdown_variation_specifically_options( apply_filters( 'jagif_dropdown_variation_specifically_options', array(
															'options'   => $options,
															'attribute' => $formated_attribute_name,
															'product'   => $variable_prd,
															'class'     => 'jagif-attribute-options jagif-va-attribute-options',
														), $formated_attribute_name, $variable_prd ) );
														?>
                                                    </td>
                                                </tr>
											<?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div class="jagif-popup-var-close-wrap"><?php esc_html_e( 'X', 'jagif-woo-free-gift' ); ?></div>
                                        <div class="jagif-btn-choose">
                                            <button type="button" class="jagif-cart-is-variation jagif-cart-change-variation-popup button alt"
                                                    data-index="<?php echo esc_attr( $cart_item['jagif_index'] ) ?>"
                                                    data-parent_key="<?php echo esc_attr( $cart_item['jagif_parent_key'] ) ?>"
                                                    data-variation_key="<?php echo esc_attr( $cart_item['key'] ) ?> "
                                                    data-parent_id="<?php echo esc_attr( $cart_item['jagif_parent_id'] ) ?>"
                                                    data-variation_id="<?php echo esc_attr( $cart_item['variation_id'] ) ?>"
                                                    data-variation="<?php echo esc_attr( $variation ) ?>"
                                                    data-variable_id="<?php echo esc_attr( $cart_item['product_id'] ) ?>"
                                                    data-quantity="<?php echo esc_attr( $variation_qty ) ?>"
                                                    data-title="<?php echo esc_attr( $cart_item['data']->get_name() ) ?>"><?php esc_html_e( 'Change', 'jagif-woo-free-gift' ); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
					}
				}
			}
		}
	}

	function jagif_update_link_gift() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		$variation_id = isset( $_POST['jagif_variation_id'] ) ? wc_clean( wp_unslash( $_POST['jagif_variation_id'] ) ) : '';
		if ( empty( $variation_id ) ) {
			return;
		}

		$permalink = '';
		$product   = wc_get_product( $variation_id );
		if ( $product ) {
			$permalink = $product->get_permalink();
		}

		wp_send_json( $permalink );

		wp_die();
	}

	function jagif_eligibility_message() {
		if ( $this->settings->get_params( 'cart_notice' ) == 1 ) {
			$get_gift_item = $this->functions->scan_rule();
			if ( isset( $get_gift_item ) && ! empty( $get_gift_item ) ) {
				$jagift_notice = '';
				foreach ( $get_gift_item as $gift_status ) {
					if ( empty( $gift_status['is_apply'] ) ) {
						ob_start();
						?>
                        <div class="jagif_cart_notice_wrap jagif_cart_notice_<?php echo esc_attr( $gift_status['rule_id'] ) ?>">
                            <p class="jagif_cart_notice_text"><?php echo wp_kses_post( $gift_status['message'] ) ?></p>
                        </div>
						<?php
						$jagift_notice .= ob_get_clean();
					}
				}
				echo wp_kses_post( $jagift_notice );
			}
		}

		return;
	}

	function jagif_wcaio_mini_cart_pd_qty( $qty_html, $cart_item_key, $cart_item, $quantity_args) {
		if ( isset( $cart_item['jagif_rule_id'] ) ) {
			return esc_html( 'x ' . $cart_item['quantity'] );
		}
		return $qty_html;
	}

}