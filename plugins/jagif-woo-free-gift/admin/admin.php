<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIJAGIF_WOO_FREE_GIFT_Admin_Admin {
	protected $settings;
	public $functions;

	function __construct() {
		$this->functions = VIJAGIF_WOO_FREE_GIFT_Function::get_instance();
		$this->settings  = VIJAGIF_WOO_FREE_GIFT_DATA::get_instance();
		add_filter( 'plugin_action_links_jagif-woo-free-gift/jagif-woo-free-gift.php',
			array( $this, 'settings_link' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'add_meta_boxes', array( $this, 'jagif_add_custom_meta_box' ) );
		add_action( 'admin_menu', array( $this, 'menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_product' ) );

		add_action( 'edit_form_after_title', array( $this, 'add_detail_rule' ) );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );

		add_action( 'wp_ajax_jagif_product_ajax', array( $this, 'jagif_product_ajax' ) );
		add_action( 'wp_ajax_jagif_gift_pack_ajax', array( $this, 'jagif_gift_pack_ajax' ) );
		add_action( 'wp_ajax_jagif_gift_product_ajax', array( $this, 'jagif_gift_product_ajax' ) );
		add_action( 'wp_ajax_jagif_cats_ajax', array( $this, 'jagif_cats_ajax' ) );
		add_action( 'wp_ajax_jagif_coupon_ajax', array( $this, 'jagif_coupon_ajax' ) );
		add_action( 'wp_ajax_jagif_save_switch', array( $this, 'jagif_save_switch' ) );
		add_action( 'save_post_woo_free_gift_rules', array( $this, 'jagif_save_detail_rule' ) );

		add_filter( 'manage_woo_free_gift_rules_posts_columns', array(
			$this,
			'custom_woo_free_gift_rules_columns'
		), 9 );

		add_action( 'manage_woo_free_gift_rules_posts_custom_column', array(
			$this,
			'show_woo_free_gift_rules_columns'
		), 9 );
	}

	/**
	 * Init Script in Admin
	 */
	public function admin_enqueue_scripts() {
		$current_screen = get_current_screen()->id;
		$suffix         = WP_DEBUG ? '' : 'min.';
		if ( in_array( $current_screen, array(
			'woo-free-gift',
			'toplevel_page_jagif-woo-free-gift'
		) ) ) {
			global $wp_scripts;
			$scripts = $wp_scripts->registered;
			foreach ( $scripts as $k => $script ) {
				preg_match( '/^\/wp-/i', $script->src, $result );
				if ( count( array_filter( $result ) ) < 1 ) {
					if ( $script->handle == 'query-monitor' ) {
						continue;
					}
					wp_dequeue_script( $script->handle );
				}
			}

			/*Stylesheet*/
			wp_enqueue_style( 'button', VIJAGIF_WOO_FREE_GIFT_CSS . 'button.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'accordion', VIJAGIF_WOO_FREE_GIFT_CSS . 'accordion.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'form', VIJAGIF_WOO_FREE_GIFT_CSS . 'form.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'icon', VIJAGIF_WOO_FREE_GIFT_CSS . 'icon.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'label', VIJAGIF_WOO_FREE_GIFT_CSS . 'label.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'dropdown', VIJAGIF_WOO_FREE_GIFT_CSS . 'dropdown.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'segment', VIJAGIF_WOO_FREE_GIFT_CSS . 'segment.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'transition', VIJAGIF_WOO_FREE_GIFT_CSS . 'transition.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'select2', VIJAGIF_WOO_FREE_GIFT_CSS . 'select2.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'grid', VIJAGIF_WOO_FREE_GIFT_CSS . 'grid.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'input', VIJAGIF_WOO_FREE_GIFT_CSS . 'input.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'checkbox', VIJAGIF_WOO_FREE_GIFT_CSS . 'checkbox.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );

			wp_enqueue_style( 'jagif-admin-settings', VIJAGIF_WOO_FREE_GIFT_CSS . 'jagif-admin-settings.' . $suffix . 'css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );

			wp_enqueue_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'sortable', VIJAGIF_WOO_FREE_GIFT_JS . 'Sortable.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'transition', VIJAGIF_WOO_FREE_GIFT_JS . 'transition.min.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'dropdown', VIJAGIF_WOO_FREE_GIFT_JS . 'dropdown.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'checkbox', VIJAGIF_WOO_FREE_GIFT_JS . 'checkbox.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'accordion-semantic', VIJAGIF_WOO_FREE_GIFT_JS . 'accordion.min.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );

		}
		if ( in_array( $current_screen, array( 'jagif_page_woo-free-gift-settings' ) ) ) {
			wp_enqueue_style( 'button', VIJAGIF_WOO_FREE_GIFT_CSS . 'button.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'dropdown', VIJAGIF_WOO_FREE_GIFT_CSS . 'dropdown.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'segment', VIJAGIF_WOO_FREE_GIFT_CSS . 'segment.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'transition', VIJAGIF_WOO_FREE_GIFT_CSS . 'transition.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'input', VIJAGIF_WOO_FREE_GIFT_CSS . 'input.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'menu', VIJAGIF_WOO_FREE_GIFT_CSS . 'menu.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'tab', VIJAGIF_WOO_FREE_GIFT_CSS . 'tab.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'form', VIJAGIF_WOO_FREE_GIFT_CSS . 'form.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'icon', VIJAGIF_WOO_FREE_GIFT_CSS . 'icon.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'checkbox', VIJAGIF_WOO_FREE_GIFT_CSS . 'checkbox.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'jagif-admin-settings', VIJAGIF_WOO_FREE_GIFT_CSS . 'jagif-admin-settings.' . $suffix . 'css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );

			wp_enqueue_script( 'transition', VIJAGIF_WOO_FREE_GIFT_JS . 'transition.min.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'dropdown', VIJAGIF_WOO_FREE_GIFT_JS . 'dropdown.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'address', VIJAGIF_WOO_FREE_GIFT_JS . 'address.min.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'tab', VIJAGIF_WOO_FREE_GIFT_JS . 'tab.js', array( 'jquery' ),VIJAGIF_WOO_FREE_GIFT_VERSION, false );

			wp_enqueue_script( 'jagif-admin-settings-js', VIJAGIF_WOO_FREE_GIFT_JS . 'jagif_admin_settings.' . $suffix . 'js', array( 'jquery' ) ,VIJAGIF_WOO_FREE_GIFT_VERSION, false );
		}
		if ( in_array( $current_screen, array( 'woo_free_gift_rules', 'edit-woo_free_gift_rules' ) ) ) {
			global $wp_scripts, $wp_styles;
			$scripts = $wp_scripts->registered;
//			$styles = $wp_styles->registered;
			foreach ( $scripts as $k => $script ) {
				preg_match( '/bootstrap/i', $k, $result_bootstrap );
				preg_match( '/selectWoo/i', $k, $result_selectWoo );
				if ( count( array_filter( $result_bootstrap ) ) ) {
					unset( $wp_scripts->registered[ $k ] );
					wp_dequeue_script( $script->handle );
				}
				if ( count( array_filter( $result_selectWoo ) ) ) {
					unset( $wp_scripts->registered[ $k ] );
					wp_dequeue_script( $script->handle );
				}
			}

//			foreach ( $styles as $st_k => $style ) {
//				preg_match( '/bootstrap/i', $st_k, $style_result_bootstrap );
//				if ( count( array_filter( $style_result_bootstrap ) ) ) {
//					unset( $wp_styles->registered[ $st_k ] );
//					wp_dequeue_style( $style->handle );
//				}
//			}

			wp_enqueue_style( 'button', VIJAGIF_WOO_FREE_GIFT_CSS . 'button.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'accordion', VIJAGIF_WOO_FREE_GIFT_CSS . 'accordion.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'form', VIJAGIF_WOO_FREE_GIFT_CSS . 'form.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'icon', VIJAGIF_WOO_FREE_GIFT_CSS . 'icon.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'label', VIJAGIF_WOO_FREE_GIFT_CSS . 'label.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'dropdown', VIJAGIF_WOO_FREE_GIFT_CSS . 'dropdown.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'transition', VIJAGIF_WOO_FREE_GIFT_CSS . 'transition.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'select2', VIJAGIF_WOO_FREE_GIFT_CSS . 'select2.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'input', VIJAGIF_WOO_FREE_GIFT_CSS . 'input.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			//wp_enqueue_style( 'popup', VIJAGIF_WOO_FREE_GIFT_CSS . 'popup.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'checkbox', VIJAGIF_WOO_FREE_GIFT_CSS . 'checkbox.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );

			wp_enqueue_style( 'jagif-rule', VIJAGIF_WOO_FREE_GIFT_CSS . 'jagif_rule.' . $suffix . 'css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_script( 'jagif-admin-rule', VIJAGIF_WOO_FREE_GIFT_JS . 'jagif_admin_rule.' . $suffix . 'js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );

			wp_enqueue_script( 'dropdown', VIJAGIF_WOO_FREE_GIFT_JS . 'dropdown.js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'transition', VIJAGIF_WOO_FREE_GIFT_JS . 'transition.min.js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'select2-full', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'checkbox', VIJAGIF_WOO_FREE_GIFT_JS . 'checkbox.js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_script( 'accordion-semantic', VIJAGIF_WOO_FREE_GIFT_JS . 'accordion.min.js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			$woo_country       = WC()->countries->get_countries();
			$jagif_rule_params = array(
				'jagif_nonce'             => wp_create_nonce( 'jagif-nonce' ),
				'jagif_product_error'     => esc_html__( 'Please select several products for this field.', 'jagif-woo-free-gift' ),
				'jagif_product_cat_error' => esc_html__( 'Please select some categories for this field.', 'jagif-woo-free-gift' ),
				'jagif_input_gift_error'  => esc_html__( 'Please select some gift for this field.', 'jagif-woo-free-gift' ),

				'jagif_archive_all'         => esc_html__( 'All Product', 'jagif-woo-free-gift' ),
				'jagif_archive_product'     => esc_html__( 'Product Rule', 'jagif-woo-free-gift' ),
				'jagif_archive_category'    => esc_html__( 'Category Rule', 'jagif-woo-free-gift' ),
				'jagif_archive_on_sale'     => esc_html__( 'On Sale Products', 'jagif-woo-free-gift' ),
				'jagif_archive_item'        => esc_html__( 'Total Item In Cart', 'jagif-woo-free-gift' ),
				'jagif_archive_price'       => esc_html__( 'Cart Total Price', 'jagif-woo-free-gift' ),
				'jagif_calculate_from_cart' => esc_html__( 'Count All Item In Cart', 'jagif-woo-free-gift' ),
				'jagif_calculate_from_rule' => esc_html__( 'Only Count Items That Satisfy In The Product Rule', 'jagif-woo-free-gift' ),

				'jagif_country_list'        => array_map( 'esc_attr', $woo_country ),
				'jagif_country_placeholder' => esc_html__( 'All Country', 'jagif-woo-free-gift' ),
				'jagif_remove_placeholder'  => esc_html__( 'Remove rule', 'jagif-woo-free-gift' ),

				'jagif_schedule_date'            => esc_html__( 'Date', 'jagif-woo-free-gift' ),
				'jagif_schedule_from'            => esc_html__( 'From', 'jagif-woo-free-gift' ),
				'jagif_schedule_to'              => esc_html__( 'To', 'jagif-woo-free-gift' ),
				'jagif_schedule_spec_date'       => esc_html__( 'Specific date', 'jagif-woo-free-gift' ),
				'jagif_schedule_spec_date_title' => esc_html__( 'Date', 'jagif-woo-free-gift' ),
				'jagif_schedule_time'            => esc_html__( 'Time', 'jagif-woo-free-gift' ),

				'jagif_type_include'      => esc_html__( 'INCLUDE', 'jagif-woo-free-gift' ),
				'jagif_type_exclude'      => esc_html__( 'EXCLUDE', 'jagif-woo-free-gift' ),
				'jagif_type_less_than'    => esc_html__( 'Less than ( < )', 'jagif-woo-free-gift' ),
				'jagif_type_greater_than' => esc_html__( 'Greater than ( > )', 'jagif-woo-free-gift' ),
				'jagif_type_equal'        => esc_html__( 'Is equal to ( = )', 'jagif-woo-free-gift' ),

				'jagif_description_type_product' => esc_html__( 'Choose products to be gifted using "INCLUDE". If you want to exclude a few products, choose "EXCLUDE". (You can add multiple Product)', 'jagif-woo-free-gift' ),
				'jagif_description_type_cart'    => esc_html__( 'Combine satisfying conditions in the cart to get a gift', 'jagif-woo-free-gift' ),
				'jagif_description_type_country' => esc_html__( 'Choose countries to be gifted using "INCLUDE". If you want to exclude a few countries, choose "EXCLUDE". (You can add multiple country)', 'jagif-woo-free-gift' ),
				'jagif_description_type_coupon'  => esc_html__( 'Choose coupons to be gifted using "INCLUDE". If you want to exclude a few coupons, choose "EXCLUDE". (You can add multiple coupon)', 'jagif-woo-free-gift' ),

				'jagif_confirm_delete' => esc_html__( 'Are you want to delete this rule?', 'jagif-woo-free-gift' ),
			);
			wp_localize_script( 'jagif-admin-rule', 'jagif_rule_params', $jagif_rule_params );

		}

	}

	public function admin_enqueue_scripts_product() {
		$current_screen = get_current_screen()->id;
		$suffix         = WP_DEBUG ? '' : 'min.';

		if ( in_array( $current_screen, array( 'product' ) ) ) {
			wp_enqueue_style( 'select2', VIJAGIF_WOO_FREE_GIFT_CSS . 'select2.min.css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, false );
			wp_enqueue_style( 'jagif-product', VIJAGIF_WOO_FREE_GIFT_CSS . 'jagif_product.' . $suffix . 'css', array() ,VIJAGIF_WOO_FREE_GIFT_VERSION );
			wp_enqueue_script( 'jagif-admin-product', VIJAGIF_WOO_FREE_GIFT_JS . 'jagif_admin_product.' . $suffix . 'js', array( 'jquery' ), VIJAGIF_WOO_FREE_GIFT_VERSION, true );

			if ( isset( $_REQUEST['_jagif_admin_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_jagif_admin_nonce'] ) ), 'jagif_admin_nonce' ) ) {
				$jagif_product_type = '';
			} else {
				$jagif_product_type = isset( $_GET['jagif_type'] ) ? wc_clean( wp_unslash( $_GET['jagif_type'] ) ) : '';
            }
			$jagif_admin_product_params = array(
				'jagif_nonce'           => wp_create_nonce( 'jagif-nonce' ),
				'jagif_product_type'    => $jagif_product_type,
				'jagif_product_confirm' => esc_html__( 'Remove this product', 'jagif-woo-free-gift' ),
			);
			wp_localize_script( 'jagif-admin-product', 'jagif_admin_product_params', $jagif_admin_product_params );
		}
	}

	/**
	 * Link to Settings
	 *
	 * @param $links
	 *
	 * @return mixed
	 */
	public function settings_link( $links ) {
		$settings_link = sprintf( '<a href="%s?page=woo-free-gift-settings" title="%s">%s</a>', esc_attr( admin_url( 'admin.php' ) ),
			esc_attr__( 'Settings', 'jagif-woo-free-gift' ),
			esc_html__( 'Settings', 'jagif-woo-free-gift' )
		);
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Function init
	 */
	function init() {
		load_plugin_textdomain( 'jagif-woo-free-gift' );
		$this->load_plugin_textdomain();
		/*Register post type*/
		$this->jagif_register_post_type();
		/*Class Villatheme support*/
		if ( class_exists( 'VillaTheme_Support' ) ) {
			new VillaTheme_Support(
				array(
					'support'   => 'https://wordpress.org/support/plugin/jagif-woo-free-gift/',
					'docs'      => 'https://docs.villatheme.com/?item=jagif',
					'review'    => 'https://wordpress.org/support/plugin/jagif-woo-free-gift/reviews/?rate=5#rate-response',
					'pro_url'   => 'https://1.envato.market/15YMmg',
					'css'       => VIJAGIF_WOO_FREE_GIFT_CSS,
					'image'     => VIJAGIF_WOO_FREE_GIFT_IMAGES,
					'slug'      => 'jagif-woo-free-gift',
					'menu_slug' => 'jagif-woo-free-gift',
					'survey_url' => 'https://script.google.com/macros/s/AKfycbzJ8xmGy8EIOBAPK8xrq8_hwW1ZbxIC2zDmEnwELgxp_tMnNNPUQR7X9oAaThXsUvqhAw/exec',
					'version'   => VIJAGIF_WOO_FREE_GIFT_VERSION
				)
			);
		}
		jagif_create_custom_product_type();
	}

	/**
	 * load Language translate
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'jagif-woo-free-gift' );
		// Global + Frontend Locale
		load_textdomain( 'jagif-woo-free-gift', VIJAGIF_WOO_FREE_GIFT_LANGUAGES . "woo-free-gift$locale.mo" );
		load_plugin_textdomain( 'jagif-woo-free-gift', false, VIJAGIF_WOO_FREE_GIFT_LANGUAGES );
	}

	/**
	 * Register a custom menu page.
	 */
	public function menu_page() {
		add_menu_page(
			esc_html__( 'Jagif', 'jagif-woo-free-gift' ),
			esc_html__( 'Jagif', 'jagif-woo-free-gift' ),
			'manage_woocommerce',
			'jagif-woo-free-gift',
			null,
			VIJAGIF_WOO_FREE_GIFT_IMAGES . '/jagif-logo.svg',
			2
		);
		add_submenu_page(
			'jagif-woo-free-gift',
			esc_html__( 'Add New', 'jagif-woo-free-gift' ),
			esc_html__( 'Add New', 'jagif-woo-free-gift' ),
			'manage_woocommerce',
			'post-new.php?post_type=woo_free_gift_rules',
			array(),
			null
		);
		add_submenu_page(
			'jagif-woo-free-gift',
			esc_html__( 'Settings', 'jagif-woo-free-gift' ),
			esc_html__( 'Settings', 'jagif-woo-free-gift' ),
			'manage_woocommerce',
			'woo-free-gift-settings',
			array(
				'VIJAGIF_WOO_FREE_GIFT_Admin_Settings',
				'page_callback'
			),
			null
		);

	}

	/**
	 * Register Custom Post Type for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function jagif_register_post_type() {
		/*
		 * Post type Filter Menus
		 * */
		$label = array(
			"name"               => esc_html__( "Rules", "jagif-woo-free-gift" ),
			"singular_name"      => esc_html__( "Rules", "jagif-woo-free-gift" ),
			"menu_name"          => esc_html__( "Rules", "jagif-woo-free-gift" ),
			"all_items"          => esc_html__( "All Rules", "jagif-woo-free-gift" ),
			"add_new"            => esc_html__( "Add New", "jagif-woo-free-gift" ),
			"add_new_item"       => esc_html__( "Add New Rule", "jagif-woo-free-gift" ),
			"edit_item"          => esc_html__( "Edit Rules", "jagif-woo-free-gift" ),
			"new_item"           => esc_html__( "New Rules", "jagif-woo-free-gift" ),
			"view_item"          => esc_html__( "View Rule", "jagif-woo-free-gift" ),
			"view_items"         => esc_html__( "View Rules", "jagif-woo-free-gift" ),
			"search_items"       => esc_html__( "Search Rules", "jagif-woo-free-gift" ),
			"not_found"          => esc_html__( "No Rule found", "jagif-woo-free-gift" ),
			"not_found_in_trash" => esc_html__( "No Rule in Trash", "jagif-woo-free-gift" ),
			"items_list"         => esc_html__( "Rules List", "jagif-woo-free-gift" ),
		);
		$args  = array(
			'labels'              => $label,
			'description'         => 'Post Type Rules',
			'supports'            => array(
				'title',
			),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'jagif-woo-free-gift',
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'post'
		);

		register_post_type( 'woo_free_gift_rules', $args );
	}

	function remove_row_actions( $actions ) {
		if ( get_post_type() === 'woo_free_gift_rules' ) {
			unset( $actions['view'] );
		}

		return $actions;
	}

	function jagif_add_custom_meta_box() {
		add_meta_box(
			'custom_meta_box-2',       // $id
			'Override Rule',                  // $title
			array( $this, 'show_custom_meta_box_override' ),  // $callback
			'woo_free_gift_rules',                 // $page
			'side',                  // $context
			'high'                     // $priority
		);
	}

	function show_custom_meta_box_override( $post ) {

		$value = get_post_meta( $post->ID, 'jagif-woo_free_gift_override', true );
		if ( empty( $value ) ) {
			$value = array( 'enable' => 0, 'priority' => 0 );
		}

		$enable_html = '<div class="jagif-rule-override-wrap jagif-rule-override-enable-wrap">
            <div class="jagif-rule-override-label">
                <label class="">' . esc_html__( 'Enable', "jagif-woo-free-gift" ) . '</label>
                <span class="jagif-explain-group" data-tooltip="' . esc_html__( 'Enable to use Type of override rule setting to select rule', "jagif-woo-free-gift") .
                       '" data-variation="wide"><i class="question circle icon "></i></span>
            </div>
            <div class="jagif-rule-override-input">';
		$enable_html .= '<div class="vi-ui field toggle checkbox"><input type="checkbox" class="jagif-rule-override-enable" 
            name="jagif-rule-override-enable" value="1"';
		if ( $value['enable'] ) {
			$enable_html .= ' checked="checked"';
		}
		$enable_html .= '><label for="jagif-rule-override-enable"></label></div></div></div>';

		$priority_html = '<div class="jagif-rule-override-wrap jagif-rule-override-priority-wrap">
            <div class="jagif-rule-override-label">
                <label class="">' . esc_html__( 'Priority', "jagif-woo-free-gift" ) . '</label>
                <span class="jagif-explain-group" data-tooltip="' . esc_html__( 'Rule with enable override and have high priority will choose to use', "jagif-woo-free-gift") .
		                 '" data-variation="wide"><i class="question circle icon "></i></span>
            </div>
            <div class="jagif-rule-override-input">';
		$priority_html .= '<div class="vi-ui field toggle checkbox"><input type="number" class="jagif-rule-override-priority" 
            name="jagif-rule-override-priority" min="0" step="1" value="' . esc_attr( $value['priority'] ) . '"';
		$priority_html .= '<label for="jagif-rule-override-priority"></label></div></div></div>';

		echo $enable_html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $priority_html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function add_detail_rule() {
		if ( get_current_screen()->id != 'woo_free_gift_rules' ) {
			return;
		}
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/templates/jagif-template-detail-rule.php';
	}

	public function custom_woo_free_gift_rules_columns( $columns ) {
		$columns['available-gift'] = esc_html__( 'Available Gift Pack ID', 'jagif-woo-free-gift' );
		$columns['rule_enable']    = esc_html__( 'Enable Rule', 'jagif-woo-free-gift' );
		$columns['override_enable']    = esc_html__( 'Override status', 'jagif-woo-free-gift' );
		$columns['override_priority']    = esc_html__( 'Override priority', 'jagif-woo-free-gift' );

		return $columns;
	}

	public function show_woo_free_gift_rules_columns( $name ) {
		global $post;
		$override = get_post_meta( $post->ID, 'jagif-woo_free_gift_override', true );
		switch ( $name ) {
			case 'available-gift':
				$jagif_rule           = get_post_meta( $post->ID, 'jagif-woo_free_gift_rules', true );
				$jagif_available_gift = ! empty( $jagif_rule['jagif_input_search_gift'] ) ? $jagif_rule['jagif_input_search_gift'] : array();

				$list = array();
				if ( ! empty( $jagif_available_gift ) ) {
					foreach ( $jagif_available_gift as $available_gift ) {
						$product_edit_link = get_edit_post_link( $available_gift );
						array_push( $list, sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $product_edit_link ), esc_html( 'ID: ' . $available_gift ) ) );
					}
					$list_edit_link = implode( ', ', $list );
					echo wp_kses_post( $list_edit_link );
				}
				break;
			case 'rule_enable':
				$jagif_enable         = get_post_meta( $post->ID, 'jagif-woo_free_gift_enable', true );
				$jagif_available_gift = ! empty( $jagif_enable ) ? $jagif_enable : 0;
				$enable_html          = '<div class="vi-ui field toggle checkbox"><input type="checkbox" data-id="';
				$enable_html          .= esc_attr( $post->ID ) . '" class="jagif-col-enable" name="jagif-col-enable" value="1"';
				if ( $jagif_available_gift ) {
					$enable_html .= ' checked="checked"';
				}
				$enable_html .= '><label for="jagif-col-enable"></label></div><div class="jagif-col-loader jagif-hidden"></div>';
				echo $enable_html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;
			case 'override_enable':
				if ( isset( $override['enable'] ) ) {
					if ( $override['enable'] == 1 ) {
						echo '<div class="jagif-col-override-enable">' . esc_html_e('Enable', 'jagif-woo-free-gift' ) . '</div>';
                    } else {
						echo '<div class="jagif-col-override-enable">' . esc_html_e('Disable', 'jagif-woo-free-gift' ) . '</div>';
                    }
				}
				break;
			case 'override_priority':
				if ( isset( $override['priority'] ) ) {
					echo '<div class="jagif-col-override-priority">' . esc_html( $override['priority'] ) . '</div>';
				}
				break;
			default:
				break;
		}
	}

	function jagif_gift_pack_ajax() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$return = array();
		$args   = array(
			's'            => isset( $_GET['q'] ) ? wc_clean( wp_unslash( $_GET['q'] ) ) : '',
			'type'         => array(
				'variable',
				'variation',
				'simple',
			),
			'status'       => 'publish',
			'limit'        => - 1,
			'stock_status' => 'instock',
			'orderby'      => 'date',
			'order'        => 'ASC',
		);

		$products = wc_get_products( $args );
		foreach ( $products as $item ) {
			if ( $item->get_type() !== 'jagif-gift' ) {
				$return[] = array( $item->get_id(), $item->get_name(), $item->get_type() );
			}
		}
		echo wp_json_encode( $return );
		die;
	}

	function jagif_product_ajax() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$return = array();
		$args   = array(
			's'            => isset( $_GET['q'] ) ? wc_clean( wp_unslash( $_GET['q'] ) ) : '',
			'type'         => array(
				'variable',
				'simple',
			),
			'status'       => 'publish',
			'limit'        => - 1,
			'stock_status' => 'instock',
		);

		$products = wc_get_products( $args );
		foreach ( $products as $item ) {
			if ( $item->get_type() !== 'jagif-gift' ) {
				$return[] = array( $item->get_id(), $item->get_title(), $item->get_type() );
			}
		}
		echo wp_json_encode( $return );
		die;
	}

	// ajax search gift pack
	function jagif_gift_product_ajax() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$return   = array();
		$args     = array(
			'type'         => 'jagif-gift',
			's'            => isset( $_GET['q'] ) ? wc_clean( wp_unslash( $_GET['q'] ) ) : '',
			'status'       => array(
				'publish',
				'private',
			),
			'limit'        => - 1,
			'stock_status' => 'instock',
		);
		$products = wc_get_products( $args );
		foreach ( $products as $item ) {
			$return[] = array( $item->get_id(), $item->get_title() );
		}
		echo wp_json_encode( $return );
		die;
	}

	// ajax search product category
	function jagif_cats_ajax() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$key_search = isset( $_POST['keysearch'] ) ? sanitize_text_field( wp_unslash( $_POST['keysearch'] ) ) : '';
		$tax_search = isset( $_POST['tax_search'] ) ? sanitize_text_field( wp_unslash( $_POST['tax_search'] ) ) : '';
		if ( $key_search === '-1' ) {
			$arr_tax = get_terms(
				array(
					'taxonomy'   => $tax_search,
					'orderby'    => 'name',
					'order'      => 'ASC',
					'hide_empty' => true,
					'fields'     => 'all',
				)
			);
		} else {
			$arr_tax = get_terms(
				array(
					'taxonomy'   => $tax_search,
					'orderby'    => 'name',
					'order'      => 'ASC',
					'search'     => $key_search,
					'hide_empty' => true
				)
			);
		}

		$items = array();
		if ( count( $arr_tax ) ) {
			foreach ( $arr_tax as $tax_item ) {
				$item    = array(
					'id'   => $tax_item->term_id,
					'text' => $tax_item->name
				);
				$items[] = $item;
			}
		}
		wp_send_json( $items );
		die();
	}

	// ajax search product category
	function jagif_coupon_ajax() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$key_search = isset( $_POST['keysearch'] ) ? sanitize_text_field( wp_unslash( $_POST['keysearch'] ) ) : '';

		$q     = new WP_Query( array(
			'post_status'    => 'publish',
			'post_type'      => 'shop_coupon',
			'posts_per_page' => 10,
			's'              => $key_search,
		) );
		$items = array();
		if ( $q->have_posts() ) :
			global $woocommerce;
			while ( $q->have_posts() ) : $q->the_post();
				$coupon_id = get_the_ID();
				$c         = new WC_Coupon( $coupon_id );
				$aCoupon   = $c->get_data();
				if ( ! empty( $aCoupon ) ) {
					// If the title is set, and is not empty, output it.
					if ( isset( $aCoupon['code'] ) && '' !== $aCoupon['code'] ) {
						$item    = array(
							'id'   => $coupon_id,
							'text' => $aCoupon['code'] . ' (' . $aCoupon['discount_type'] . ')'
						);
						$items[] = $item;
					}
				}
			endwhile;
			wp_reset_postdata();
		endif;
		wp_send_json( $items );
		die();
	}

	function jagif_save_switch() {
		check_ajax_referer( 'jagif-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$data_rule_id     = isset( $_POST['rule_id'] ) ? absint( $_POST['rule_id'] ) : 0;
		$data_rule_enable = isset( $_POST['enable'] ) ? sanitize_text_field( wp_unslash( $_POST['enable'] ) ) : '';
		if ( empty( $data_rule_id ) || $data_rule_enable == '' ) {
			wp_send_json_error();
		}
		if ( $data_rule_enable == 'false' ) {
			$update = update_post_meta( $data_rule_id, 'jagif-woo_free_gift_enable', '' );
		} else {
			$update = update_post_meta( $data_rule_id, 'jagif-woo_free_gift_enable', $data_rule_enable );
		}
		wp_send_json_success( $update );
	}

	private function stripslashes_deep( $value ) {
		$value = is_array( $value ) ? array_map( 'stripslashes_deep', $value ) : stripslashes( $value );

		return $value;
	}

	public function jagif_save_detail_rule( $post_id ) {
		if ( ! current_user_can( "edit_post", $post_id ) ) {
			return $post_id;
		}
		if ( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		if ( isset( $_POST['_jagif_rule_nonce'] ) ) {
			$jagif_rule_nonce = wc_clean( wp_unslash( $_POST['_jagif_rule_nonce'] ) );
			if ( ! isset( $jagif_rule_nonce ) ) {
				return false;
			}
			if ( ! wp_verify_nonce( $jagif_rule_nonce, 'jagif_save_rule' ) ) {
				return false;
			}
		}
		$jagif_input_enable_gift       = isset( $_POST['jagif_input_enable_gift'] ) ? wc_clean( wp_unslash( $_POST['jagif_input_enable_gift'] ) ) : '';
		if ( ! isset( $_POST['jagif_input_enable_gift'] ) && ! isset( $_POST['jagif-rule-override-enable'] ) && ! isset( $_POST['jagif-rule-override-priority'] ) && ! isset( $_POST['jagif_input_search_gift'] ) ) {
			$jagif_input_enable_gift = 'true';
		}
		$save_action = isset( $_POST['action'] ) ? wc_clean( wp_unslash( $_POST['action'] ) ) : '';
		$jagif_input_description_gift  = isset( $_POST['jagif_input_description_gift'] ) ? $this->stripslashes_deep( wc_clean( wp_unslash( $_POST['jagif_input_description_gift'] ) ) ) : '';
		$jagif_input_override_enable   = isset( $_POST['jagif-rule-override-enable'] ) ? wc_clean( wp_unslash( $_POST['jagif-rule-override-enable'] ) ) : '';
		$jagif_input_override_priority = isset( $_POST['jagif-rule-override-priority'] ) ? wc_clean( wp_unslash( $_POST['jagif-rule-override-priority'] ) ) : 0;
		$jagif_input_search_gift       = isset( $_POST['jagif_input_search_gift'] ) ? wc_clean( wp_unslash( $_POST['jagif_input_search_gift'] ) ) : '';
		$jagif_conditions       = isset( $_POST['jagif_conditions'] ) ? wc_clean( wp_unslash( $_POST['jagif_conditions'] ) ) : array();
		$jagif_in_product       = isset( $_POST['jagif_in_product'] ) ? wc_clean( wp_unslash( $_POST['jagif_in_product'] ) ) : array();
		$jagif_ex_product       = isset( $_POST['jagif_ex_product'] ) ? wc_clean( wp_unslash( $_POST['jagif_ex_product'] ) ) : array();
		$jagif_in_category      = isset( $_POST['jagif_in_category'] ) ? wc_clean( wp_unslash( $_POST['jagif_in_category'] ) ) : array();
		$jagif_ex_category      = isset( $_POST['jagif_ex_category'] ) ? wc_clean( wp_unslash( $_POST['jagif_ex_category'] ) ) : array();

		$count_conditions = is_array( $jagif_conditions ) ? count( $jagif_conditions ) : 0;
		$rule_conditions  = array();
		if ( $count_conditions > 0 ) {
			for ( $i = 0; $i < $count_conditions; $i ++ ) {
				switch ( $jagif_conditions[ $i ] ) {
					case 'ex_product':
						if ( isset( $jagif_ex_product[ $i ] ) ) {
							$cond_arr = array(
								'type'  => 'ex_product',
								'value' => $jagif_ex_product[ $i ]
							);
							array_push( $rule_conditions, $cond_arr );
						} else {
							$cond_arr = array(
								'type'  => 'ex_product',
								'value' => ''
							);
							array_push( $rule_conditions, $cond_arr );
						}
						break;
					case 'in_product':
						if ( isset( $jagif_in_product[ $i ] ) ) {
							$cond_arr = array(
								'type'  => 'in_product',
								'value' => $jagif_in_product[ $i ]
							);
							array_push( $rule_conditions, $cond_arr );
						} else {
							$cond_arr = array(
								'type'  => 'in_product',
								'value' => ''
							);
							array_push( $rule_conditions, $cond_arr );
						}
						break;
					case 'ex_category':
						if ( isset( $jagif_ex_category[ $i ] ) ) {
							$cond_arr = array(
								'type'  => 'ex_category',
								'value' => $jagif_ex_category[ $i ]
							);
							array_push( $rule_conditions, $cond_arr );
						} else {
							$cond_arr = array(
								'type'  => 'ex_category',
								'value' => ''
							);
							array_push( $rule_conditions, $cond_arr );
						}
						break;
					case 'in_category':
						if ( isset( $jagif_in_category[ $i ] ) ) {
							$cond_arr = array(
								'type'  => 'in_category',
								'value' => $jagif_in_category[ $i ]
							);
							array_push( $rule_conditions, $cond_arr );
						} else {
							$cond_arr = array(
								'type'  => 'in_category',
								'value' => ''
							);
							array_push( $rule_conditions, $cond_arr );
						}
						break;
					default:
						break;
				}
			}
		}
		$jagif_rule = array(
			'jagif_input_search_gift' => $jagif_input_search_gift,
			'jagif_conditions'        => $rule_conditions,
		);

		if ( ! empty( $rule_conditions ) || ! empty( $jagif_input_search_gift ) ) {
			update_post_meta( $post_id, 'jagif-woo_free_gift_rules', $jagif_rule );
		}
		update_post_meta( $post_id, 'jagif-woo_free_gift_override', array(
			'enable'   => $jagif_input_override_enable,
			'priority' => $jagif_input_override_priority
		) );
		update_post_meta( $post_id, 'jagif-woo_free_gift_description', $jagif_input_description_gift );
		if ( $jagif_input_enable_gift ) {
			update_post_meta( $post_id, 'jagif-woo_free_gift_enable', $jagif_input_enable_gift );
		} else {
			update_post_meta( $post_id, 'jagif-woo_free_gift_enable', '' );
		}

		if ( empty( $rule_conditions ) ) {
			remove_action( 'save_post', 'jagif_save_detail_rule' );
			add_action( 'admin_notices', function () {
				?>
                <div class="error">
                    <p><?php esc_html_e( 'Your rule need to add at least one condition to work!', 'jagif-woo-free-gift' ) ?></p>
                </div>
				<?php
			} );
		}

		if ( get_post_status( $post_id ) != 'publish' && ! empty( $jagif_input_search_gift ) ) {
			remove_action( 'save_post', 'jagif_save_detail_rule' );
			wp_publish_post( $post_id );
		}

		if ( get_post_status( $post_id ) == 'publish' && empty( $jagif_input_search_gift ) && 'inline-save' !== $save_action ) {
			remove_action( 'save_post', 'jagif_save_detail_rule' );
			wp_update_post( array(
				'ID'          => $post_id,
				'post_status' => 'draft'
			) );
			add_action( 'admin_notices', function () {
				?>
                <div class="error">
                    <p><?php esc_html_e( 'Your rule need to add at least one gift pack to work!', 'jagif-woo-free-gift' ) ?></p>
                </div>
				<?php
			} );
		}
	}
}