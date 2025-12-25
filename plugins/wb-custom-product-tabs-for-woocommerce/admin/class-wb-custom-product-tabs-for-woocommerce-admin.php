<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://profiles.wordpress.org/webbuilder143/
 * @since      1.0.0
 *
 * @package    Wb_Custom_Product_Tabs_For_Woocommerce
 * @subpackage Wb_Custom_Product_Tabs_For_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wb_Custom_Product_Tabs_For_Woocommerce
 * @subpackage Wb_Custom_Product_Tabs_For_Woocommerce/admin
 * @author     Web Builder 143 <webbuilder143@gmail.com>
 */
class Wb_Custom_Product_Tabs_For_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @since    1.3.1  Enqueued WooCommerce admin styles and Select2 styles for Global tab products meta box.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wb-custom-product-tabs-for-woocommerce-admin.css', array(), $this->version, 'all' );

		global $post_type;

		if ( $post_type && WB_TAB_POST_TYPE === $post_type ) {
			// Enqueue both WooCommerce admin styles and Select2 styles.
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( 'select2' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @since    1.3.1  WooCommerce's Select2 script for Global tab products meta box.
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wb-custom-product-tabs-for-woocommerce-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'wb_custom_tabs_params',
			array(
				'msgs'                 => array(
					'untitled'          => __( 'Untitled', 'wb-custom-product-tabs-for-woocommerce' ),
					'no_data'           => __( 'No data', 'wb-custom-product-tabs-for-woocommerce' ),
					'sure'              => __( 'Are you sure?', 'wb-custom-product-tabs-for-woocommerce' ),
					'title_mandatory'   => __( 'Please fill tab title', 'wb-custom-product-tabs-for-woocommerce' ),
					'content_mandatory' => __( 'Please fill tab content', 'wb-custom-product-tabs-for-woocommerce' ),
					'invalid_video_id'  => __( 'Please enter valid YouTube video ID', 'wb-custom-product-tabs-for-woocommerce' ),
					'inserting'         => __( 'Inserting...', 'wb-custom-product-tabs-for-woocommerce' ),
					'insert'            => __( 'Insert', 'wb-custom-product-tabs-for-woocommerce' ),
					'title_is_empty'    => __( 'Tab title is empty', 'wb-custom-product-tabs-for-woocommerce' ),
				),
				'default_tab_position' => Wb_Custom_Product_Tabs_For_Woocommerce::get_default_tab_position(),
			)
		);

		global $post_type;

		if ( $post_type && WB_TAB_POST_TYPE === $post_type ) {
			// WooCommerce's Select2 script.
			wp_enqueue_script( 'wc-enhanced-select' );
		}
	}


	/**
	 *  @since 1.0.0
	 *  Adding tabs to woocomerce product section
	 */
	public function product_data_tabs( $tabs ) {
		$tabs['wb_custom_tabs'] = array(
			'label'    => __( 'Custom Tabs', 'wb-custom-product-tabs-for-woocommerce' ),
			'target'   => 'wb_custom_tabs',
			'class'    => array(),
			'priority' => 110,
		);
		return $tabs;
	}

	/**
	 *  @since 1.0.0
	 *  Render product tab form in admin section
	 */
	public function product_data_panels() {
		global $post;
		$post_backup = $post;
		$product     = wc_get_product( $post );

		$tabs = Wb_Custom_Product_Tabs_For_Woocommerce::get_product_tabs( $product );
		$post = $post_backup;
		include 'views/product_data_panels.php';
	}

	/**
	 *  Sanitize product tab data before saving
	 *
	 *  @since 1.0.0
	 *  @since 1.1.2 Nickname option added for product specific tabs
	 */
	private function sanitize_tab_input() {
		/**
		 * Extend tab content allowed HTML tags while sanitizing tab content.
		 *
		 *  @since 1.2.0
		 */
		add_filter( 'wp_kses_allowed_html', array( $this, 'extend_tab_content_allowed_html' ) );

		/**
		 * Extend tab content allowed style properties while sanitizing tab content.
		 *
		 *  @since 1.2.0
		 */
		add_filter( 'safe_style_css', array( $this, 'extend_tab_content_allowed_css' ) );

		$out = array();
		for ( $i = 0; $i < 100000; $i++ ) {

			// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce is already verified.
			$title    = isset( $_POST['wb_tab'][ $i ]['title'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['wb_tab'][ $i ]['title'] ) ) ) : '';
			$content  = isset( $_POST['wb_tab'][ $i ]['content'] ) ? trim( wp_kses_post( wp_unslash( $_POST['wb_tab'][ $i ]['content'] ) ) ) : '';
			$position = (int) isset( $_POST['wb_tab'][ $i ]['position'] ) ? sanitize_text_field( wp_unslash( $_POST['wb_tab'][ $i ]['position'] ) ) : 0;
			$nickname = isset( $_POST['wb_tab'][ $i ]['nickname'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['wb_tab'][ $i ]['nickname'] ) ) ) : '';
			$slug     = isset( $_POST['wb_tab'][ $i ]['slug'] ) ? trim( sanitize_title( wp_unslash( $_POST['wb_tab'][ $i ]['slug'] ) ) ) : '';

			if ( $title && $content ) {
				$out[] = array(
					'title'    => $title,
					'content'  => $content,
					'tab_type' => 'local',
					'position' => $position,
					'nickname' => $nickname,
					'slug'     => $slug,
				);
			} else {
				break;
			}
		}

		return $out;
	}

	/**
	 *  Save product tab data to database
	 *
	 *  @since 1.0.0
	 */
	public function process_product_meta( $post_id, $post ) {
		if ( empty( $post_id ) ) {
			return;
		}

		$product            = wc_get_product( $post_id );
		$sanitized_tab_data = $this->sanitize_tab_input();

		// Now sanitize the tab data explicitly before updating the meta.
		$product->update_meta_data( 'wb_custom_tabs', $sanitized_tab_data );

		$product->save();
	}

	/**
	 *  Register global tabs as custom post type
	 *
	 *  @since 1.0.2
	 *  @since 1.2.4 Added compatibility for brands.
	 */
	public function register_global_tabs() {
		
		$taxonomies = Wb_Custom_Product_Tabs_For_Woocommerce::get_taxonomy_list();

		register_post_type(
			WB_TAB_POST_TYPE,
			array(
				'labels'          => array(
					'name'                  => __( 'Global product tabs', 'wb-custom-product-tabs-for-woocommerce' ),
					'singular_name'         => __( 'Global product tab', 'wb-custom-product-tabs-for-woocommerce' ),
					'all_items'             => __( 'Tabs', 'wb-custom-product-tabs-for-woocommerce' ),
					'menu_name'             => _x( 'Tabs', 'Admin menu name', 'wb-custom-product-tabs-for-woocommerce' ),
					'add_new'               => __( 'Add new', 'wb-custom-product-tabs-for-woocommerce' ),
					'add_new_item'          => __( 'Add new tab', 'wb-custom-product-tabs-for-woocommerce' ),
					'edit'                  => __( 'Edit', 'wb-custom-product-tabs-for-woocommerce' ),
					'edit_item'             => __( 'Edit tab', 'wb-custom-product-tabs-for-woocommerce' ),
					'new_item'              => __( 'New tab', 'wb-custom-product-tabs-for-woocommerce' ),
					'view_item'             => __( 'View tab', 'wb-custom-product-tabs-for-woocommerce' ),
					'view_items'            => __( 'View tabs', 'wb-custom-product-tabs-for-woocommerce' ),
					'search_items'          => __( 'Search tabs', 'wb-custom-product-tabs-for-woocommerce' ),
					'not_found'             => __( 'No tabs found', 'wb-custom-product-tabs-for-woocommerce' ),
					'not_found_in_trash'    => __( 'No tabs found in trash', 'wb-custom-product-tabs-for-woocommerce' ),
					'parent'                => __( 'Parent tab', 'wb-custom-product-tabs-for-woocommerce' ),
					'featured_image'        => __( 'Tab image', 'wb-custom-product-tabs-for-woocommerce' ),
					'set_featured_image'    => __( 'Set tab image', 'wb-custom-product-tabs-for-woocommerce' ),
					'remove_featured_image' => __( 'Remove tab image', 'wb-custom-product-tabs-for-woocommerce' ),
					'use_featured_image'    => __( 'Use as tab image', 'wb-custom-product-tabs-for-woocommerce' ),
					'insert_into_item'      => __( 'Insert into tab', 'wb-custom-product-tabs-for-woocommerce' ),
					'uploaded_to_this_item' => __( 'Uploaded to this tab', 'wb-custom-product-tabs-for-woocommerce' ),
					'filter_items_list'     => __( 'Filter tabs', 'wb-custom-product-tabs-for-woocommerce' ),
					'items_list_navigation' => __( 'Tabs navigation', 'wb-custom-product-tabs-for-woocommerce' ),
					'items_list'            => __( 'Tabs list', 'wb-custom-product-tabs-for-woocommerce' ),
					'item_link'             => __( 'Tab Link', 'wb-custom-product-tabs-for-woocommerce' ),
					'item_link_description' => __( 'A link to a tab.', 'wb-custom-product-tabs-for-woocommerce' ),
				),
				'show_ui'         => true,
				'has_archive'     => false,
				'taxonomies'      => $taxonomies,
				'show_in_menu'    => 'edit.php?post_type=product',
				'capability_type' => 'product',
				'map_meta_cap'    => true,
				'show_in_rest'    => apply_filters( 'wb_cptb_enable_global_tab_show_in_rest', false ),
			)
		);
	}

	/**
	 *  Save metabox data.
	 *
	 *  @since 1.0.2
	 *  @since 1.1.0    Added option to save nickname info.
	 *  @since 1.3.1    Added option to save tab products.
	 *  @since 1.3.4    Added option to save tab slug.
	 */
	public function save_meta_box_data( $post_id ) {
		if ( array_key_exists( 'wb_tab_meta_box', $_POST ) ) {

			$tab_position = (int) isset( $_POST['wb_tab_tab_position'] ) ? sanitize_text_field( wp_unslash( $_POST['wb_tab_tab_position'] ) ) : 0;
			update_post_meta( $post_id, '_wb_tab_position', $tab_position );

			$tab_nickname = isset( $_POST['wb_tab_tab_nickname'] ) ? sanitize_text_field( wp_unslash( $_POST['wb_tab_tab_nickname'] ) ) : '';
			update_post_meta( $post_id, '_wb_tab_nickname', $tab_nickname );

			// Save the selected products.
			$existing_product_ids = get_post_meta( $post_id, '_wb_tab_products', true );
			$existing_product_ids = is_array( $existing_product_ids ) ? $existing_product_ids : array();

			$selected_products = isset( $_POST['_wb_tab_products'] ) && is_array( $_POST['_wb_tab_products'] )
				? array_map( 'absint', $_POST['_wb_tab_products'] )
				: array();

			update_post_meta( $post_id, '_wb_tab_products', $selected_products ); // Save the post meta.

			// Skip if both arrays are empty — no cache to clear.
			if ( ! empty( $existing_product_ids ) || ! empty( $selected_products ) ) {

				$changed_product_ids = array_unique(
					array_merge(
						array_diff( $existing_product_ids, $selected_products ),
						array_diff( $selected_products, $existing_product_ids )
					)
				);

				if ( ! empty( $changed_product_ids ) ) {
					foreach ( $changed_product_ids as $product_id ) {
						wp_cache_delete( 'wb_tab_post_ids_for_product_' . $product_id );
					}
				}
			}

			// Save tab slug.
			$tab_slug = isset( $_POST['wb_tab_tab_slug'] ) ? sanitize_title( wp_unslash( $_POST['wb_tab_tab_slug'] ) ) : '';
			update_post_meta( $post_id, '_wb_tab_slug', $tab_slug );
		}
	}

	/**
	 *  Register meta box for global tab custom post type
	 *
	 *  @since 1.0.2
	 *  @since 1.1.0 Moved tab location from `side` to `normal`. Tab title and id updated.
	 *  @since 1.3.1 Added products meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'wb_tab_tab_other_info_meta_box',
			__( 'Other tab info', 'wb-custom-product-tabs-for-woocommerce' ),
			array( $this, '_tab_other_info_meta_box_html' ),
			WB_TAB_POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'wb_tab_products_meta_box',
			__( 'Products', 'wb-custom-product-tabs-for-woocommerce' ),
			array( $this, '_tab_products_meta_box_html' ),
			WB_TAB_POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'wb_tab_other_free_plugins_meta_box',
			__( 'Our other free plugins', 'wb-custom-product-tabs-for-woocommerce' ),
			array( $this, '_other_free_plugins_meta_box_html' ),
			WB_TAB_POST_TYPE,
			'normal',
			'default'
		);

		// Remove slug meta box.
		remove_meta_box( 'slugdiv', WB_TAB_POST_TYPE, 'normal' );
	}

	/**
	 *  Render HTML for tab other info meta box.
	 *
	 *  @since 1.1.0
	 */
	public function _tab_other_info_meta_box_html( $post, $box ) {
		$tab_position = Wb_Custom_Product_Tabs_For_Woocommerce::_get_global_tab_position( $post->ID );
		$tab_nickname = Wb_Custom_Product_Tabs_For_Woocommerce::_get_global_tab_nickname( $post->ID );
		$tab_slug     = Wb_Custom_Product_Tabs_For_Woocommerce::_get_global_tab_slug( $post->ID );
		include WB_TAB_ROOT_PATH . 'admin/views/_global_tab_metabox.php';
	}

	/**
	 * @since 1.0.9
	 * Global tabs, Add new product links on plugins page
	 */
	public function plugin_action_links( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . WB_TAB_POST_TYPE ) ) . '">' . __( 'Global Product tabs', 'wb-custom-product-tabs-for-woocommerce' ) . '</a>';

		$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=wb-product-tab-settings' ) ) . '">' . __( 'Tab settings', 'wb-custom-product-tabs-for-woocommerce' ) . '</a>';

		$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=wb-product-tab-settings&wb_cptb_tab=help' ) ) . '">' . __( 'Help', 'wb-custom-product-tabs-for-woocommerce' ) . '</a>';
		/*
		$links[] = '<a href="https://webbuilder143.com/support-our-work/?utm_source=plugin&utm_medium=plugins-page&utm_campaign=links&utm_id=tabs-plugin&utm_content=donate" target="_blank" style="color:#06bb06; font-weight:700;">' . __( 'Donate', 'wb-custom-product-tabs-for-woocommerce' ) . '</a>'; */
		return $links;
	}


	/**
	 *   @since 1.1.0
	 *   Add nickname column in global tabs listing page
	 */
	public function add_nickname_column( $columns ) {
		$out = array();
		foreach ( $columns as $column_key => $column_title ) {
			$out[ $column_key ] = $column_title;
			if ( 'title' == $column_key ) {
				$out['wb_tab_nickname'] = __( 'Nickname', 'wb-custom-product-tabs-for-woocommerce' );
			}
		}
		return $out;
	}

	/**
	 *   @since 1.1.0
	 *   Add nickname column data, in global tabs listing page
	 */
	public function add_nickname_column_data( $column, $post_id ) {
		if ( 'wb_tab_nickname' == $column ) {
			echo esc_html( Wb_Custom_Product_Tabs_For_Woocommerce::_get_global_tab_nickname( $post_id ) );
		}
	}


	/**
	 *   Add product categories/tags columns in global tabs listing page
	 *
	 *   @since 1.1.3
	 */
	public function add_product_cat_tag_column( $columns ) {
		$out = array();

		foreach ( $columns as $column_key => $column_title ) {
			$out[ $column_key ] = $column_title;

			if ( 'wb_tab_nickname' == $column_key ) {
				$out['wb_tab_product_categories'] = __( 'Product categories', 'wb-custom-product-tabs-for-woocommerce' );
				$out['wb_tab_product_tags']       = __( 'Product tags', 'wb-custom-product-tabs-for-woocommerce' );
			}
		}

		return $out;
	}


	/**
	 *   Add product categories/tags column data, in global tabs listing page
	 *
	 *   @since 1.1.3
	 */
	public function add_product_cat_tag_column_data( $column, $post_id ) {
		if ( 'wb_tab_product_tags' == $column || 'wb_tab_product_categories' == $column ) {
			$this->_get_product_cat_tag_column_data( $post_id, ( 'wb_tab_product_tags' == $column ? 'product_tag' : 'product_cat' ) );
		}
	}


	/**
	 *   Prepare and print data for product categories/tags column data, in global tabs listing page
	 *
	 *   @since 1.1.3
	 */
	private function _get_product_cat_tag_column_data( $post_id, $term = 'product_cat' ) {
		$tab_product_terms = get_the_terms( $post_id, $term );

		if ( $tab_product_terms && is_array( $tab_product_terms ) ) {
			$tab_product_term_names = array_column( $tab_product_terms, 'name' );
			echo esc_html( implode( ', ', $tab_product_term_names ) );
		}
	}


	/**
	 *  Is a custom product tab edit/add new page
	 *
	 *  @since  1.1.5
	 *  @return bool        Is custom product tab page or not
	 */
	public static function is_wb_tab_page() {
		return self::is_a_post_type_page( WB_TAB_POST_TYPE );
	}


	/**
	 *  Is a product edit/add new page
	 *
	 *  @since  1.1.5
	 *  @return bool        Is product page or not
	 */
	public static function is_product_edit_page() {
		return self::is_a_post_type_page( 'product' );
	}


	/**
	 *  Is a post type edit/add new page
	 *
	 *  @since  1.1.5
	 *  @param  string $post_type      Post type
	 *  @return bool        Is post type page or not
	 */
	public static function is_a_post_type_page( $post_type ) {
		$file_name = isset( $_SERVER['SCRIPT_NAME'] ) ? pathinfo( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ), PATHINFO_BASENAME ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'post-new.php' === $file_name && isset( $_GET['post_type'] ) && $post_type === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) {
			return true;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( 'post.php' === $file_name && isset( $_GET['post'] ) && 0 < absint( $_GET['post'] ) && $post_type === get_post_type( absint( $_GET['post'] ) ) ) {
			return true;
		}

		return false;
	}


	/**
	 *  Add YouTube embed button for editor
	 *
	 *  @since 1.1.5
	 */
	public function add_youtube_embed_button( $editor_id = 'content' ) {
		if ( self::is_wb_tab_page() || 'wb_tab_editor' === $editor_id ) {
			?>
			<button type="button" id="<?php echo esc_attr( $editor_id . '-wb_cptb-embed-youtube' ); ?>" class="button wb_cptb-embed-youtube" data-editor="<?php echo esc_attr( $editor_id ); ?>"><span class="dashicons dashicons-youtube" style="margin-top:5px;"></span> <?php esc_html_e( 'Embed YouTube', 'wb-custom-product-tabs-for-woocommerce' ); ?></button>			
			<?php
		}
	}


	/**
	 *  Add YouTube embed and tab edit popup HTML
	 *
	 *  @since 1.1.5
	 *  @since 1.3.4 Moved tab edit popup HTML to this method and renamed this method name.
	 */
	public function add_youtube_embed_and_tab_edit_popup() {
		if ( self::is_wb_tab_page() || self::is_product_edit_page() ) {
			?>
			<div class="wb_cptb_youtube_popup wb_tab_popup">
				<div class="wb_tab_popup_hd">		
					<span class="wb_tab_popup_hd_txt">
						<span class="dashicons dashicons-youtube"></span>
						<?php esc_html_e( 'Embed YouTube', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					</span>
					<span class="wb_tab_popup_close" title="Close">
						<span class="dashicons dashicons-dismiss"></span>
					</span>
				</div>
				<div class="wb_tab_popup_content">
					<div class="wb_tab_panel_frmgrp">
						<label>
							<?php esc_html_e( 'YouTube URL/Video ID', 'wb-custom-product-tabs-for-woocommerce' ); ?>		
						</label>
						<input type="text" name="wb_cptb_youtube_url" class="wb_tabpanel_txt" placeholder="<?php esc_attr_e( 'YouTube URL/Video ID', 'wb-custom-product-tabs-for-woocommerce' ); ?>" value="" style="width:100%;">
						<div class="wb_tab_er"></div>
					</div>
					<div class="wb_tab_panel_frmgrp" style="width:48%;">
						<label><?php esc_html_e( 'Width', 'wb-custom-product-tabs-for-woocommerce' ); ?></label>
						<input type="number" name="wb_cptb_youtube_width" class="wb_tabpanel_txt" value="560" style="width:100%;" placeholder="<?php esc_attr_e( 'Default 560', 'wb-custom-product-tabs-for-woocommerce' ); ?>" step="1" min="50">
						<div class="wb_tab_er"></div>
					</div>
					<div class="wb_tab_panel_frmgrp" style="width:48%; float:right;">
						<label><?php esc_html_e( 'Height', 'wb-custom-product-tabs-for-woocommerce' ); ?></label>
						<input type="number" name="wb_cptb_youtube_height" class="wb_tabpanel_txt" value="315" style="width:100%;" placeholder="<?php esc_attr_e( 'Default 315', 'wb-custom-product-tabs-for-woocommerce' ); ?>" step="1" min="50">
						<div class="wb_tab_er"></div>
					</div>
					<div class="wb_tab_panel_frmgrp" style="text-align:right;">
						<button class="button button-primary wb_tab_done_btn wb_cptb_youtube_insert_btn" type="button"><?php esc_html_e( 'Insert', 'wb-custom-product-tabs-for-woocommerce' ); ?></button>
						<button class="button button-secondary wb_tab_cancel_btn" type="button"><?php esc_html_e( 'Cancel', 'wb-custom-product-tabs-for-woocommerce' ); ?></button>
					</div>
				</div>
			</div>
			<?php
			if ( self::is_product_edit_page() ) {
				?>
				<div class="wb_tab_popup wb_cptb_tab_edit_popup">
					<div class="wb_tab_popup_hd">		
						<span class="wb_tab_popup_hd_txt">
							<span class="dashicons dashicons-edit"></span>
							<?php esc_html_e( 'Edit', 'wb-custom-product-tabs-for-woocommerce' ); ?>
						</span>
						<span class="wb_tab_popup_close" title="<?php esc_attr_e( 'Close', 'wb-custom-product-tabs-for-woocommerce' ); ?>">
							<span class="dashicons dashicons-dismiss"></span>
						</span>
					</div>
					<div class="wb_tab_popup_content">
						<div class="wb_tab_panel_frmgrp" style="width:50%;">
							<label><?php esc_html_e( 'Tab title', 'wb-custom-product-tabs-for-woocommerce' ); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Title for tab', 'wb-custom-product-tabs-for-woocommerce' ); ?>"></span></label>
							<input type="text" name="wb_tab_title" class="wb_tabpanel_txt wb_tab_title_input" placeholder="<?php esc_attr_e( 'Title for tab', 'wb-custom-product-tabs-for-woocommerce' ); ?>" value="">
							<div class="wb_tab_er"></div>
						</div>
						<div class="wb_tab_panel_frmgrp" style="width:50%;">
							<label><?php esc_html_e( 'Tab position', 'wb-custom-product-tabs-for-woocommerce' ); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Tab position', 'wb-custom-product-tabs-for-woocommerce' ); ?>"></span></label>
							<input type="number" min="0" step="1" name="wb_tab_position" class="wb_tabpanel_txt wb_tab_position_input" placeholder="<?php esc_attr_e( 'Tab position', 'wb-custom-product-tabs-for-woocommerce' ); ?>" value="" style="float:left; width:100px;">			
							<div class="wb_tabpanel_hlp" style="margin-top:10px; margin-left:15px;">
								<a href="https://webbuilder143.com/how-to-arrange-woocommerce-custom-product-tabs/?utm_source=plugin&utm_medium=product-edit&utm_campaign=tab-position&utm_content=positioning" target="_blank"><?php esc_html_e( 'Know more', 'wb-custom-product-tabs-for-woocommerce' ); ?> <span class="dashicons dashicons-external" style="text-decoration:none;"></span></a>
							</div>
							<div class="wb_tab_er"></div>
						</div>
						<div class="wb_tab_panel_frmgrp">
							<label><?php esc_html_e( 'Tab content', 'wb-custom-product-tabs-for-woocommerce' ); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Content for tab', 'wb-custom-product-tabs-for-woocommerce' ); ?>"></span></label>
							<?php
							wp_editor(
								'',
								'wb_tab_editor',
								array(
									'editor_class'  => 'wb_tab_rte',
									'editor_height' => 200,
									'textarea_rows' => 6,
									'tinymce'       => array(
										'height' => 190,
									),
								)
							);
							?>
						</div>
						<div class="wb_tab_panel_frmgrp">							
							<div style="float:left; width:28%;">
								<label><?php esc_html_e( 'Tab nickname', 'wb-custom-product-tabs-for-woocommerce' ); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'A unique nickname will be useful for identifying the tab', 'wb-custom-product-tabs-for-woocommerce' ); ?>"></span></label>
								<input type="text" name="wb_tab_nickname" class="wb_tabpanel_txt wb_tab_nickname_input" placeholder="<?php esc_attr_e( 'Tab nickname', 'wb-custom-product-tabs-for-woocommerce' ); ?>" value="" style="float:left;">
								<div class="wb_tab_er"></div>
							</div>

							<div style="float:left; width:44%;">
								<label><?php esc_html_e( 'Tab slug', 'wb-custom-product-tabs-for-woocommerce' ); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'SEO friendly URL for tab. Allowed characters: letters, numbers, and hyphens only.', 'wb-custom-product-tabs-for-woocommerce' ); ?>"></span></label>
								<input type="text" name="wb_tab_slug" class="wb_tabpanel_txt wb_tab_slug_input" placeholder="<?php esc_attr_e( 'Tab slug', 'wb-custom-product-tabs-for-woocommerce' ); ?>" value="" style="float:left;"> <a class="wb_cptb_slug_generate_btn"><?php esc_html_e( 'Generate tab slug from title.', 'wb-custom-product-tabs-for-woocommerce' ); ?></a>
								<div class="wb_tab_er"></div>
							</div>

							<div style="float:left; width:28%;">
								<label>&nbsp;</label>
								<button class="button button-primary wb_tab_done_btn wb_cptb_tab_save_btn" type="button"><?php esc_html_e( 'Done', 'wb-custom-product-tabs-for-woocommerce' ); ?></button>
								<button class="button button-secondary wb_tab_cancel_btn" type="button"><?php esc_html_e( 'Cancel', 'wb-custom-product-tabs-for-woocommerce' ); ?></button>
							</div>
						</div>
					</div>
				</div>
				<div class="wb_tab_popup_overlay"></div>
				<?php
			}
		}
	}


	/**
	 *  Show change log in upgrade notice
	 *
	 *  @since 1.1.5
	 */
	public function changelog_in_upgrade_notice() {
		if ( isset( $data['upgrade_notice'] ) ) {
			$msg = str_replace( array( '<p>', '</p>' ), array( '<div>', '</div>' ), $data['upgrade_notice'] );
			echo '<div class="update-message wb_cptb_upgrade_notice" style="padding-left:20px;">' . wp_kses_post( wpautop( $msg ) ) . '</div>';

			add_action( 'admin_print_footer_scripts', array( $this, 'add_js_css_for_changelog_in_upgrade_notice' ) );
		}
	}


	/**
	 *  Add js css for changelog in upgrade notice
	 *
	 *  @since 1.1.5
	 */
	public function add_js_css_for_changelog_in_upgrade_notice() {
		global $pagenow;

		if ( 'plugins.php' === $pagenow ) {
			?>
				<style type="text/css">
			#wb-custom-product-tabs-for-woocommerce-update .update-message p:last-child{ display:none;}     
			#wb-custom-product-tabs-for-woocommerce-update ul{ margin-left:20px; list-style:disc;}     
			</style>
			<script type="text/javascript">
				jQuery(document).ready(function(){\
					$('#wb-custom-product-tabs-for-woocommerce-update').find('.wb_cptb_upgrade_notice').next('p').remove();
					$('#wb-custom-product-tabs-for-woocommerce-update').find('a.update-link:eq(0)').on('click', function(){
						$('.wb_cptb_upgrade_notice').remove();
					});
				});
			</script>
			<?php
		}
	}


	/**
	 * Review banner on global tabs page.
	 *
	 *  @since 1.1.13
	 */
	public function global_tabs_page_review_banner() {
		global $current_screen;
		if ( 'wb-custom-tabs' !== $current_screen->post_type ) {
			return;
		}

		$msg = sprintf(
			/* translators: Star rating */
			__( 'Click here to rate us %s, If you like the Custom product tabs plugin', 'wb-custom-product-tabs-for-woocommerce' ),
			'⭐️⭐️⭐️⭐️⭐️',
		);
		?>
		<script type="text/javascript"> 
			jQuery(document).ready( function() {
				jQuery('.wp-list-table').after('<a href="https://wordpress.org/support/plugin/wb-custom-product-tabs-for-woocommerce/reviews/?rate=5#new-post" target="_blank" style="display:inline-block; box-shadow:2px 1px 2px 0px #e2d5d5; margin:0px; padding:10px; box-sizing:border-box; margin-bottom:15px; border-left: solid 4px blueviolet; background:#333; color:#fff; text-decoration:none; position:fixed; bottom:0px; z-index:10000; left:50%; transform:translate(-50%, 0%);"><?php echo wp_kses_post( $msg ); ?></a>');


				jQuery('.page-title-action').after('<a style="margin-left:10px; margin-top: 10px; display:inline-block; position: relative; text-decoration:none;" class="button button-primary" href="<?php echo esc_url( admin_url( 'options-general.php?page=wb-product-tab-settings' ) ); ?>"><?php esc_html_e( 'Tab settings', 'wb-custom-product-tabs-for-woocommerce' ); ?></a>');
			});
		</script>
		<?php
	}


	/**
	 * Extend tab content allowed HTML tags while sanitizing tab content.
	 *
	 *  @since  1.2.0
	 *  @since  1.2.2   Added some additional HTML tags.
	 *  @param  array $allowed_tags  Allowed tags.
	 *  @return array   $allowed_tags  Allowed tags.
	 */
	public function extend_tab_content_allowed_html( $allowed_tags ) {

		$custom_allowed_tags = array(
			'a'          => array(
				'href'   => array(),
				'title'  => array(),
				'rel'    => array(),
				'target' => array(),
				'class'  => array(),
				'id'     => array(),
				'style'  => array(),
			),
			'img'        => array(
				'src'    => array(),
				'alt'    => array(),
				'width'  => array(),
				'height' => array(),
				'class'  => array(),
				'id'     => array(),
				'style'  => array(),
			),
			'p'          => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'br'         => array(),
			'strong'     => array(),
			'em'         => array(),
			'span'       => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'div'        => array(
				'class'  => array(),
				'id'     => array(),
				'style'  => array(),
				'data-*' => array(),
			),
			'ul'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'ol'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'li'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'blockquote' => array(
				'cite'  => array(),
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'h1'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'h2'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'h3'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'h4'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'h5'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'h6'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'table'      => array(
				'class'  => array(),
				'id'     => array(),
				'style'  => array(),
				'border' => array(),
			),
			'thead'      => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'tbody'      => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'tr'         => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'td'         => array(
				'class'   => array(),
				'id'      => array(),
				'style'   => array(),
				'colspan' => array(),
				'rowspan' => array(),
			),
			'th'         => array(
				'class'   => array(),
				'id'      => array(),
				'style'   => array(),
				'colspan' => array(),
				'rowspan' => array(),
			),
			'caption'    => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'iframe'     => array(
				'src'             => array(),
				'width'           => array(),
				'height'          => array(),
				'frameborder'     => array(),
				'allowfullscreen' => array(),
			),
			'video'      => array(
				'src'      => array(),
				'width'    => array(),
				'height'   => array(),
				'controls' => array(),
				'autoplay' => array(),
				'loop'     => array(),
				'muted'    => array(),
				'preload'  => array(),
				'poster'   => array(),
			),
			'audio'      => array(
				'src'      => array(),
				'controls' => array(),
				'autoplay' => array(),
				'loop'     => array(),
				'muted'    => array(),
				'preload'  => array(),
			),
			'source'     => array(
				'src'  => array(),
				'type' => array(),
			),
			'embed'      => array(
				'src'               => array(),
				'type'              => array(),
				'width'             => array(),
				'height'            => array(),
				'allowscriptaccess' => array(),
				'allowfullscreen'   => array(),
			),
			'object'     => array(
				'width'  => array(),
				'height' => array(),
				'data'   => array(),
				'type'   => array(),
			),
			'param'      => array(
				'name'  => array(),
				'value' => array(),
			),
			'label'      => array(
				'for'   => array(),
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
		);

		// Merge custom allowed tags with the default ones
		return array_merge( $allowed_tags, $custom_allowed_tags );
	}


	/**
	 * Extend tab content allowed style properties while sanitizing tab content.
	 *
	 *  @since  1.2.0
	 *  @param  array $css  Allowed CSS styles.
	 *  @return array   $css  Allowed CSS styles.
	 */
	public function extend_tab_content_allowed_css( $css ) {

		$css[] = 'display';

		return $css;
	}


	/**
	 * Alter the buttons in the tab content editor.
	 *
	 *  @since  1.2.3
	 *  @param  string[] $buttons    Buttons.
	 *  @param  string   $editor_id  Editor Id.
	 *  @return string[]   $buttons    Buttons.
	 */
	public function alter_editor_buttons( $buttons, $editor_id ) {

		$allowed_editors = array( 'content', 'wb_tab_editor' );

		// Only for tab editors.
		if ( in_array( $editor_id, $allowed_editors ) ) {

			global $post;

			// Check the post type is global tab.
			if ( 'content' === $editor_id &&
				( empty( $post ) || empty( $post->post_type ) || 'wb-custom-tabs' !== $post->post_type )
			) {
				return $buttons;
			}

			// Add color buttons.
			array_push( $buttons, 'forecolor', 'backcolor' );
		}

		return $buttons;
	}


	/**
	 *  Add global tabs to polylang custom post type list.
	 *
	 *  @since  1.2.4
	 *  @param  array $post_types     Post types array.
	 *  @return array   $post_types     Post types array.
	 */
	public function add_global_tabs_to_pll_post_type_list( $post_types ) {

		if ( isset( $post_types['product'] ) ) {
			$post_types[ WB_TAB_POST_TYPE ] = WB_TAB_POST_TYPE;
		}

		return $post_types;
	}


	/**
	 *  Register plugin settings.
	 *
	 *  @since 1.3.0
	 */
	public function register_settings() {
		register_setting(
			'wb_cptb_custom_tab_settings_group',
			'wb_cptb_default_tab_position',
			array(
				'sanitize_callback' => 'absint',
			)
		);
		register_setting(
			'wb_cptb_custom_tab_settings_group',
			'wb_cptb_hide_tab_heading',
			array(
				'sanitize_callback' => 'absint',
			)
		);
		register_setting(
			'wb_cptb_custom_tab_settings_group',
			'wb_cptb_global_tabs_behavior',
			array(
				'sanitize_callback' => 'absint',
			)
		);
		register_setting(
			'wb_cptb_custom_tab_settings_group',
			'wb_cptb_use_custom_the_content',
			array(
				'sanitize_callback' => 'absint',
			)
		);

		/**
		 * 	Register settings to disable WooCommerce default tabs.
		 * 
		 * 	@since 1.6.0
		 */
		register_setting(
		    'wb_cptb_custom_tab_settings_group',
		    'wb_cptb_enable_default_tabs',
		    array(
		        'type' => 'array',
		        'sanitize_callback' => array( $this, 'sanitize_default_tab_status' ),
		        'default' => array('description', 'additional_information', 'reviews'),
		    )
		);
	}

	/**
	 *  Add settings page.
	 *
	 *  @since 1.3.0
	 */
	public function settings_menu() {
		add_options_page(
			__( 'Product Tab Settings', 'wb-custom-product-tabs-for-woocommerce' ),   // Page title.
			__( 'Product Tab Settings', 'wb-custom-product-tabs-for-woocommerce' ),   // Menu title.
			'manage_options',         // Capability.
			'wb-product-tab-settings',   // Menu slug.
			array( $this, 'settings_page' ) // Callback function.
		);
	}

	/**
	 *  Settings page.
	 *
	 *  @since 1.3.0
	 */
	public function settings_page() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required.
		$tab      = isset( $_GET['wb_cptb_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['wb_cptb_tab'] ) ) : 'general'; // @codingStandardsIgnoreLine
		$page_url = admin_url( 'options-general.php?page=wb-product-tab-settings' );

		include_once WB_TAB_ROOT_PATH . 'admin/views/settings.php';
	}


	/**
	 *  Global tab products meta box.
	 *
	 *  @since 1.3.1
	 */
	public function _tab_products_meta_box_html( $post, $box ) {
		$tab_products = Wb_Custom_Product_Tabs_For_Woocommerce::_get_global_tab_products( $post->ID );
		include WB_TAB_ROOT_PATH . 'admin/views/_global_tab_products_metabox.php';
	}


	/**
	 *  Show review banner.
	 *
	 *  @since 1.4.0
	 */
	public function review_banner() {

		// Check if the current screen is allowed to show the review banner.
		$screen = get_current_screen();
		if ( ! $screen ) {
			return; }

		$allowed_screens = array(
			'edit-product',             // Products list
			'product',                  // Product edit or add
			'edit-product_cat',         // Product categories
			'edit-product_tag',         // Product tags
			'edit-pa_brand',            // Brands (assuming custom taxonomy `pa_brand`)
			'edit-product_brand',       // Brands (if using plugin like Perfect Brands)
			'edit-product_attribute',   // Product attributes (taxonomy, not actual WC attribute management)
			'edit-shop_order',          // Orders list
			'shop_order',               // Order edit screen
			'edit-shop_coupon',         // Coupons list
			'shop_coupon',              // Coupon edit screen
			'product_page_product_attributes', // WC attributes management screen
			'wb-custom-tabs', // Global tabs Add/Edit.
			'edit-wb-custom-tabs', // Global tabs listing.
		);

		if ( ! in_array( $screen->id, $allowed_screens ) ) {
			return;
		}

		$banner_state   = get_option( 'wb_cptb_review_banner_state', false );
		$is_show_banner = false;

		if ( false === $banner_state ) { // First time.
			if ( $this->is_tab_count_reached() ) {
				$is_show_banner = true;
				update_option( 'wb_cptb_review_banner_state', 1 );
			}
		} elseif ( 1 === (int) $banner_state ) {
			// Show now.
			$is_show_banner = true;
		} elseif ( 3 === (int) $banner_state ) { // Remind.

			$banner_remind_start = (int) get_option( 'wb_cptb_review_banner_remind_start', 0 );
			$two_week_before     = time() - 1209600; // After two weeks.
			if ( 0 < $banner_remind_start && $two_week_before > $banner_remind_start ) {
				$is_show_banner = true;
			}
		}

		if ( $is_show_banner ) {
			?>
			<div class="notice notice-success wb-tabs-review-notice">
				<p><strong>🎉 Amazing! You've created more than 10 product tabs using Custom Product Tabs for WooCommerce.</strong></p>
				<p>
					We're excited to see that you're getting great value from the plugin. We've spent countless hours refining every feature to make it as smooth and useful as it is today. If it has improved your workflow, we'd really appreciate it if you could leave us a quick 5-star review. It only takes a moment, and your support helps us continue improving the plugin and providing excellent support.
				</p>
				<p>Your feedback matters — and it helps others discover the plugin too!</p>
				<p>
					<a href="https://wordpress.org/support/plugin/wb-custom-product-tabs-for-woocommerce/reviews/?filter=5#new-post" target="_blank" class="button button-primary wb-cptb-tabs-review-action" data-action="review_now">
						Yes! I'll leave a 5-star review ⭐⭐⭐⭐⭐
					</a>
					<a class="button button-secondary wb-cptb-tabs-review-action" data-action="already_reviewed">
						I've already left a review
					</a>
					<a class="button button-secondary wb-cptb-tabs-review-action" data-action="remind_later">
						Remind me after 2 weeks
					</a>
					<a class="button button-secondary wb-cptb-tabs-review-action" data-action="not_satisfied">
						I'm not satisfied with the plugin
					</a>
				</p>
			</div>
			<style>
				.wb-tabs-review-notice .button { margin-right: 10px; margin-top: 5px; }
			</style>
			<script type="text/javascript">
				(function($){
					$('.wb-cptb-tabs-review-action').on('click', function(e){
						
						let review_action = $(this).data('action');

						// Hide the notice.
						$('.wb-tabs-review-notice').fadeOut();

						$.ajax({
							type: 'POST',
							dataType:'json',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							data:{
								action: 'wb_tabs_review_banner_dismiss',
								review_action: review_action,
								_nonce: '<?php echo esc_html( wp_create_nonce( 'wb_tabs_review_dismiss_nonce' ) ); ?>',
							},
							success:function(data){
								if ( ! data.status ) {
									let currentUrl = new URL( window.location.href );
									currentUrl.searchParams.set('wb_cptb_review_action', review_action);
									window.location.href = currentUrl.toString();
								}
							},
							error:function(){
								let currentUrl = new URL( window.location.href );
								currentUrl.searchParams.set('wb_cptb_review_action', review_action);
								window.location.href = currentUrl.toString();
							}
						});
					});
				})(jQuery);
			</script>
			<?php
		}
	}

	/**
	 *  Ajax review banner action.
	 *  Called when the user clicks the review banner buttons.
	 *
	 *  @since 1.4.0
	 */
	public function review_banner_ajax() {
		$nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';
		if ( wp_verify_nonce( $nonce, 'wb_tabs_review_dismiss_nonce' ) ) {
			$review_action = isset( $_POST['review_action'] ) ? sanitize_text_field( wp_unslash( $_POST['review_action'] ) ) : '';
			$this->set_review_banner_state( $review_action );
			wp_send_json( array( 'status' => true ) );
		}

		wp_send_json( array( 'status' => false ) );
	}

	/**
	 *  Non-ajax review banner action.
	 *  This will be called when ajax fails due to nonce expiration etc.
	 *
	 *  @since 1.4.0
	 */
	public function review_banner_non_ajax() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$review_action = isset( $_GET['wb_cptb_review_action'] ) ? sanitize_text_field( wp_unslash( $_GET['wb_cptb_review_action'] ) ) : '';
		$this->set_review_banner_state( $review_action );
	}

	/**
	 *  Set review banner state based on the action.
	 *
	 *  @since 1.4.0
	 *  @param string $review_action Review action.
	 */
	private function set_review_banner_state( $review_action ) {
		switch ( $review_action ) {
			case 'review_now':
			case 'already_reviewed':
			case 'not_satisfied':
				update_option( 'wb_cptb_review_banner_state', 2 ); // Set status 2, means don't show again.
				break;
			case 'remind_later':
				update_option( 'wb_cptb_review_banner_state', 3 );
				update_option( 'wb_cptb_review_banner_remind_start', time() ); // Set current time as start time.
				break;
			default:
				break;
		}
	}

	/**
	 *  Is the minimum tab count reached to show review banner.
	 *
	 *  @since 1.4.0
	 *  @return bool
	 */
	private function is_tab_count_reached() {

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$local_total = $wpdb->get_var(
			$wpdb->prepare(
				"
		        SELECT SUM(
		            CASE 
		                WHEN meta_value LIKE %s 
		                    THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ':', 2), ':', -1) AS UNSIGNED)
		                ELSE 0
		            END
		        ) AS total_count
		        FROM {$wpdb->postmeta}
		        WHERE meta_key = %s
		        ",
				'%' . $wpdb->esc_like( 'a:{' ) . '%',
				'wb_custom_tabs'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$count        = wp_count_posts( 'wb-custom-tabs' );
		$global_total = isset( $count->publish ) ? $count->publish : 0;

		$total = $local_total + $global_total;

		return ( 10 < $total );
	}

	/**
	 *  Metabox HTML to show other plugins promotion.
	 */
	public function _other_free_plugins_meta_box_html() {
		?>
		<p>
			<h3>Add desktop-style sticky notes to your WordPress dashboard!</h3>
			Create draggable, resizable notes with customizable colors and fonts. <br />
			Limit notes to specific pages, archive old ones, and stay organized with ease. <br /><br />
			<a class="button button-primary" target="_blank" href="https://wordpress.org/plugins/wb-sticky-notes/">Click here to download now!</a>
		</p>
		<br />
		<p>
			<h3>Need to log and manage your WordPress emails right from your dashboard?</h3>
			Here's the perfect solution — view all sent emails, access attachments, and even resend them with a click.<br /><br />
			<a class="button button-primary" target="_blank" href="https://wordpress.org/plugins/wb-mail-logger/">Click here to download now!</a>
		</p>
		<?php
	}

	/**
	 *  Translation request banner.
	 *
	 *  @since 1.5.0
	 */
	public function show_translation_request_banner() {
		$languages = get_available_languages();

		$non_english_languages = array_filter(
			$languages,
			function ( $lang ) {
				return strpos( $lang, 'en_' ) !== 0;
			}
		);

		if ( ! empty( $non_english_languages ) ) {
			?>
			<div style="display:inline-block; background-color:#fff3cd; padding:5px 15px; border:solid 1px #ffeeba; border-radius:5px; color:#856404; margin:15px 0px">
					<h4 style="margin:0px; padding:0px; margin-bottom:5px;">💬 Help Us Translate!</h4>
				<p style="margin:0px; padding:0px;">Want to see this plugin in your language? <a href="https://translate.wordpress.org/projects/wp-plugins/wb-custom-product-tabs-for-woocommerce/" target="_blank">Contribute a translation</a> and become a proud WordPress translation contributor. Your support makes a difference! </p>
			</div>
			<?php
		}
	}

	/**
	 * Converts global tab product IDs to slugs when exporting via the WordPress export tool.
	 *
	 * @since 1.5.3
	 * @param array $args Array of export arguments.
	 */
	public function convert_product_id_to_slug_on_export( $args ) {
	    
	    // Only proceed if exporting product tabs or all content types.
	    if ( empty( $args['content'] ) || ( $args['content'] !== WB_TAB_POST_TYPE && $args['content'] !== 'all' ) ) {
	        return;
	    }

	    // Fetch all tabs.
	    $tabs = get_posts( array(
	        'post_type'      => WB_TAB_POST_TYPE,
	        'posts_per_page' => -1,
	        'post_status'    => 'any',
	        'fields'         => 'ids',
	        'suppress_filters' => false,
	    ) );

	    if ( empty( $tabs ) ) {
	        return;
	    }

	    foreach ( $tabs as $tab_id ) {
	        $product_ids = get_post_meta( $tab_id, '_wb_tab_products', true );

	        if ( empty( $product_ids ) || ! is_array( $product_ids ) ) {
	            continue;
	        }

	        $product_slugs = array();

	        foreach ( $product_ids as $product_id ) {
	            $slug = get_post_field( 'post_name', $product_id );
	            if ( $slug ) {
	                $product_slugs[] = $slug;
	            }
	        }

	        // Store or update slugs meta. 
	        update_post_meta( $tab_id, '_wb_tab_products_slug', implode( '|', $product_slugs ) );
	    }
	}

	/**
	 * Remaps global tab product meta during WordPress import.
	 *
	 * This function runs through the `wp_import_post_meta` filter before post meta is inserted.
	 * It checks for the `_wb_tab_products_slug` meta (containing exported product slugs) and 
	 * converts it back to the corresponding product IDs on the import site. The resulting IDs 
	 * are stored in `_wb_tab_products` to maintain proper product associations after import.
	 * 
	 * @since 1.5.3
	 *
	 * @param array $postmeta An array of associative arrays containing the post meta to import.
	 *                        Each meta item includes 'key' and 'value' keys.
	 * @param int   $post_id  The ID of the post being imported.
	 * @param array $post     The full post array being imported, including post type and content.
	 *
	 * @return array Modified post meta array with remapped `_wb_tab_products` values.
	 */
	public function remap_product_ids_based_on_slugs_on_import( $postmeta, $post_id, $post ) {

	    // Only apply for product tabs custom post type.
	    if ( empty( $post['post_type'] ) || $post['post_type'] !== WB_TAB_POST_TYPE ) {
	        return $postmeta;
	    }

	    $product_slugs = array();
	    $has_products_slug = false;

	    // Find the `_wb_tab_products_slug` meta and extract its value.
	    foreach ( $postmeta as $meta ) {
	        if ( isset( $meta['key'] ) && '_wb_tab_products_slug' === $meta['key'] && ! empty( $meta['value'] ) ) {
	            $product_slugs = explode( '|', $meta['value'] );
	            $has_products_slug = ! empty( $product_slugs );
	            break;
	        }
	    }

	    if ( ! $has_products_slug ) {
	        return $postmeta;
	    }

	    // Convert slugs back to product IDs in the import site.
	    $product_ids = array();

	    foreach ( $product_slugs as $slug ) {
	        $product_obj = get_page_by_path( $slug, OBJECT, 'product' );

	        if ( $product_obj && isset( $product_obj->ID ) ) {
	            $product_ids[] = $product_obj->ID;
	        }
	    }

	    // Update the `_wb_tab_products` meta with remapped IDs.
	    $updated = false;
	    foreach ( $postmeta as &$meta ) {
	        if ( isset( $meta['key'] ) && $meta['key'] === '_wb_tab_products' ) {
	            $meta['value'] = maybe_serialize( $product_ids );
	            $updated = true;
	            break;
	        }
	    }
	    unset( $meta );

	    // If the _wb_tab_products meta doesn't exist, create it.
	    if ( ! $updated ) {
	        $postmeta[] = array(
	            'key'   => '_wb_tab_products',
	            'value' => maybe_serialize( $product_ids ),
	        );
	    }

	    return $postmeta;
	}

	/**
	 * Sanitize WooCommerce default tab status option.
	 *
	 * @since 1.6.0
	 * @param mixed $value Array of selected tab slugs.
	 * @return string[] Sanitized tab slugs.
	 */
	public function sanitize_default_tab_status( $value ) {

	    $allowed = array( 'description', 'additional_information', 'reviews' );

	    // Ensure it's an array
	    if ( ! is_array( $value ) ) {
	        return array();
	    }

	    // Sanitize each value
	    $sanitized = array();
	    foreach ( $value as $item ) {
	        $item = sanitize_text_field( $item );
	        if ( in_array( $item, $allowed, true ) ) {
	            $sanitized[] = $item;
	        }
	    }

	    return $sanitized;
	}
}