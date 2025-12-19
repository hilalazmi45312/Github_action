<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'WCPB_Product_Badges_Admin' ) ) {

	class WCPB_Product_Badges_Admin {

		public function __construct() {

			add_action( 'admin_head', array( $this, 'activation_notice_dismiss' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueues_block_editor' ) );
			add_action( 'admin_notices', array( $this, 'notices') );
			add_action( 'init', array( $this, 'post_type' ), 0 );
			add_action( 'manage_edit-wcpb_product_badge_columns', array( $this, 'post_type_columns' ) );
			add_action( 'pre_get_posts', array( $this, 'post_type_columns_default_orderby' ) );
			add_action( 'manage_edit-wcpb_product_badge_sortable_columns', array( $this, 'post_type_columns_sortable' ) );
			add_action( 'manage_wcpb_product_badge_posts_custom_column', array( $this, 'post_type_columns_values' ) );
			add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'meta_box_product_level_badge_select_save' ) );
			add_action( 'wp_ajax_wcpb_product_badges_select2_ajax_display_products_specific_categories', array( $this, 'select2_ajax_display_products_specific_categories' ) );
			add_action( 'wp_ajax_wcpb_product_badges_select2_ajax_display_products_specific_tags', array( $this, 'select2_ajax_display_products_specific_tags' ) );
			add_action( 'wp_ajax_wcpb_product_badges_select2_ajax_display_products_specific_products', array( $this, 'select2_ajax_display_products_specific_products' ) );
			add_action( 'wp_ajax_wcpb_product_badges_select2_ajax_display_products_specific_shipping_classes', array( $this, 'select2_ajax_display_products_specific_shipping_classes' ) );
			add_action( 'save_post_wcpb_product_badge', array( $this, 'save_badge' ) );
			add_filter( 'woocommerce_get_sections_products', array( $this, 'settings_section' ) );
			add_filter( 'woocommerce_get_settings_products', array( $this, 'settings_fields' ), 10, 2 );

		}

		public function activation_notice_dismiss() {

			if ( isset( $_GET['wcpb_product_badges_activation_notice_dismiss'] ) ) {

				if ( '1' == sanitize_text_field( $_GET['wcpb_product_badges_activation_notice_dismiss'] ) ) {

					delete_transient( 'wcpb_product_badges_activation_notice' );

				}

			}

		}

		public function enqueues() {

			$post_type = get_post_type();

			wp_enqueue_script( 'jquery' );

			wp_enqueue_style(
				'wcpb-product-badges-admin',
				plugins_url( 'assets/css/admin.min.css', __DIR__ ), // Enqueued everywhere as contains styles outside product badge post type
				array(),
				WCPB_PRODUCT_BADGES_VERSION,
				'all'
			);

			if ( 'wcpb_product_badge' == $post_type || 'product' == $post_type ) {

				wp_enqueue_script(
					'wcpb-product-badges-admin',
					plugins_url( 'assets/js/admin.min.js', __DIR__ ),
					array(
						'jquery',
						'wp-i18n',
						'wp-color-picker',
					),
					WCPB_PRODUCT_BADGES_VERSION,
					true
				);

				wp_set_script_translations(
					'wcpb-product-badges-admin',
					'wcpb-product-badges',
					plugin_dir_path( __DIR__ ) . 'languages'
				);

				// Color picker

				wp_enqueue_style( 'wp-color-picker' );

				// Flatpickr

				wp_enqueue_script(
					'wcpb-product-badges-flatpickr',
					plugins_url( 'libraries/flatpickr/flatpickr.min.js', __DIR__ ),
					array(),
					WCPB_PRODUCT_BADGES_VERSION,
					true
				);

				wp_enqueue_style(
					'wcpb-product-badges-flatpickr',
					plugins_url( 'libraries/flatpickr/flatpickr.min.css', __DIR__ ),
					array(),
					WCPB_PRODUCT_BADGES_VERSION,
					'all'
				);

				// Select2

				wp_enqueue_script(
					'wcpb-product-badges-select2',
					plugins_url( 'libraries/select2/js/select2.min.js', __DIR__ ),
					array(
						'jquery',
					),
					WCPB_PRODUCT_BADGES_VERSION,
					true
				);

				wp_enqueue_style(
					'wcpb-product-badges-select2',
					plugins_url( 'libraries/select2/css/select2.min.css', __DIR__ ),
					array(),
					WCPB_PRODUCT_BADGES_VERSION,
					'all'
				);

				// ThickBox

				add_thickbox();

			}

		}

		public function enqueues_block_editor() {

			// Enqueue the public styles within the block editor so badge postioning is same as frontend within the editor

			wp_enqueue_style(
				'wcpb-product-badges-public',
				plugins_url( 'assets/css/public.min.css', __DIR__ ),
				array(),
				WCPB_PRODUCT_BADGES_VERSION,
				'all'
			);

		}

		public function notices() {

			if ( !empty( get_transient( 'wcpb_product_badges_activation_notice' ) ) ) {

				if ( current_user_can( 'edit_plugins' ) ) {

					echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Product Badges has been activated.', 'wcpb-product-badges' ) . '</strong><p><a href="' . esc_url( 'https://woocommerce.com/document/product-badges/' ) . '" target="_blank">' . esc_html__( 'Read documentation', 'wcpb-product-badges' ) . '</a><br><a href="' . esc_url( get_admin_url() . 'admin.php?page=wc-settings&tab=products&section=wcpb-product-badges' ) . '">' . esc_html__( 'Configure settings', 'wcpb-product-badges' ) . '</a><br><a href="' . esc_url( get_admin_url() . 'edit.php?post_type=wcpb_product_badge' ) . '">' . esc_html__( 'Manage badges', 'wcpb-product-badges' ) . '</a><br><a href="' . esc_url( add_query_arg( 'wcpb_product_badges_activation_notice_dismiss', '1' ) ) . '">' . esc_html__( 'Dismiss notice', 'wcpb-product-badges' ) . '</a></p></div>';

				}

			}

			if ( 'wcpb_product_badge' == get_post_type() ) {

				$compatibility_mode_product_loops_position = get_option( 'wcpb_product_badges_compatibility_mode_product_loops_position' );
				$compatibility_mode_product_pages = get_option( 'wcpb_product_badges_compatibility_mode_product_pages' );
				$multiple_badges_per_product = get_option( 'wcpb_product_badges_multiple_badges_per_product' );

				$notices = array();
				$settings_link = 'admin.php?page=wc-settings&tab=products&section=wcpb-product-badges';

				// Link targets are blank link as if clicking from the add/edit badge page we want to ensure any changes are not lost

				// translators: %s: ssettings link
				$notices[] = wp_kses_post( sprintf( __( '%s to enable multiple badges per product or if you are experiencing any badge display issues.', 'wcpb-product-badges' ), '<a href="' . esc_url( get_admin_url() . 'admin.php?page=wc-settings&tab=products&section=wcpb-product-badges' ) . '">' . __( 'Configure settings', 'wcpb-product-badges' ) . '</a>' ) );

				if ( 'yes' == $compatibility_mode_product_loops_position ) {

					$notices[] = '<a href="' . $settings_link . '">' . esc_html__( 'Compatibility mode product loops position is enabled', 'wcpb-product-badges' ) . '</a>';

				}

				if ( '' !== $compatibility_mode_product_pages ) {

					$notices[] = '<a href="' . $settings_link . '">' . esc_html__( 'Compatibility mode product pages is enabled', 'wcpb-product-badges' ) . '</a>';

				}

				if ( 'yes' == $multiple_badges_per_product ) {

					$notices[] = '<a href="' . $settings_link . '">' . esc_html__( 'Multiple badges per product is enabled', 'wcpb-product-badges' ) . '</a>';

				}

				if ( !empty( $notices ) ) {

					echo '<div class="notice notice-info"><p>' . implode( '<br>', map_deep( $notices, 'wp_kses_post' ) ) . '</p></div>';

				}

			}

		}

		public function post_type() {

			$labels = array(
				'name'						=> __( 'Product Badges', 'wcpb-product-badges' ),
				'singular_name'				=> __( 'Product Badge', 'wcpb-product-badges' ),
				'menu_name'					=> __( 'Badges', 'wcpb-product-badges' ),
				'name_admin_bar'			=> __( 'Product Badge', 'wcpb-product-badges' ),
				'archives'					=> __( 'Archives', 'wcpb-product-badges' ),
				'attributes'				=> __( 'Attributes', 'wcpb-product-badges' ),
				'parent_item_colon'			=> __( 'Parent Product Badge:', 'wcpb-product-badges' ),
				'all_items'					=> __( 'Badges', 'wcpb-product-badges' ), // Would be "All Product Badges" but would mean the dashboard menu would carry that name, so renamed to Badges
				'add_new_item'				=> __( 'Add New Product Badge', 'wcpb-product-badges' ),
				'add_new'					=> __( 'Add New', 'wcpb-product-badges' ),
				'new_item'					=> __( 'New Product Badge', 'wcpb-product-badges' ),
				'edit_item'					=> __( 'Edit Product Badge', 'wcpb-product-badges' ),
				'update_item'				=> __( 'Update Product Badge', 'wcpb-product-badges' ),
				'view_item'					=> __( 'View Product Badge', 'wcpb-product-badges' ),
				'view_items'				=> __( 'View Product Badges', 'wcpb-product-badges' ),
				'search_items'				=> __( 'Search Product Badges', 'wcpb-product-badges' ),
				'not_found'					=> __( 'Not found', 'wcpb-product-badges' ),
				'not_found_in_trash'		=> __( 'Not found in Trash', 'wcpb-product-badges' ),
				'featured_image'			=> __( 'Badge image custom', 'wcpb-product-badges' ),
				'set_featured_image'		=> __( 'Set custom badge image', 'wcpb-product-badges' ),
				'remove_featured_image'		=> __( 'Remove image', 'wcpb-product-badges' ),
				'use_featured_image'		=> __( 'Use as image', 'wcpb-product-badges' ),
				'insert_into_item'			=> __( 'Insert into product badge', 'wcpb-product-badges' ),
				'uploaded_to_this_item'		=> __( 'Uploaded to this product badge', 'wcpb-product-badges' ),
				'items_list'				=> __( 'Product badges list', 'wcpb-product-badges' ),
				'items_list_navigation'		=> __( 'Product badges list navigation', 'wcpb-product-badges' ),
				'filter_items_list'			=> __( 'Filter product badges list', 'wcpb-product-badges' ),
				'item_published'			=> __( 'Product badge published', 'wcpb-product-badges' ),
				'item_published_privately'	=> __( 'Product badge published privately', 'wcpb-product-badges' ),
				'item_reverted_to_draft'	=> __( 'Product badge reverted to draft', 'wcpb-product-badges' ),
				'item_scheduled'			=> __( 'Product badge scheduled', 'wcpb-product-badges' ),
				'item_updated'				=> __( 'Product badge updated', 'wcpb-product-badges' ),
			);

			$args = array(
				'description'			=> '',
				'labels'				=> $labels,
				'supports'				=> array( 'title', 'thumbnail', 'page-attributes' ),
				'hierarchical'			=> false,
				'public'				=> false,
				'show_ui'				=> true,
				'show_in_menu'			=> 'edit.php?post_type=product',
				'menu_position'			=> 0,
				'menu_icon'				=> '',
				'show_in_admin_bar'		=> true,
				'show_in_nav_menus'		=> false,
				'can_export'			=> true,
				'has_archive'			=> false,
				'exclude_from_search'	=> true,
				'publicly_queryable'	=> false,
				'show_in_rest'			=> false,
			);

			register_post_type( 'wcpb_product_badge', $args );

		}

		public function post_type_columns( $post_columns ) {

			$date_label = $post_columns['date'];
			unset( $post_columns['date'] );
			$post_columns['type'] = __( 'Type', 'wcpb-product-badges' );
			$post_columns['position'] = __( 'Position', 'wcpb-product-badges' );
			$post_columns['visibility'] = __( 'Visiblity', 'wcpb-product-badges' );
			$post_columns['products'] = __( 'Products', 'wcpb-product-badges' );
			$post_columns['order'] = __( 'Order', 'wcpb-product-badges' );
			$post_columns['date'] = $date_label;
			return $post_columns;

		}

		public function post_type_columns_default_orderby( $query ) {

			if ( is_admin() && 'wcpb_product_badge' == $query->get( 'post_type' ) ) {

				$query->set( 'orderby', 'menu_order' ); // We do not set the orderby as this would stop clicking the order column heading sorting from working

			}

		}

		public function post_type_columns_sortable( $columns ) {

			$columns['order'] = 'order';
			return $columns;

		}

		public function post_type_columns_values( $name ) {

			global $post;

			if ( 'type' == $name ) {

				$type = get_post_meta( $post->ID, '_wcpb_product_badges_badge_type', true );

				if ( 'image_library' == $type ) {

					$type = __( 'Image library', 'wcpb-product-badges' );

				} elseif ( 'image_custom' == $type ) {

					$type = __( 'Image custom', 'wcpb-product-badges' );

				} elseif ( 'countdown' == $type ) {

					$type = __( 'Countdown', 'wcpb-product-badges' );

				} elseif ( 'text' == $type ) {

					$type = __( 'Text', 'wcpb-product-badges' );

				} elseif ( 'code' == $type ) {

					$type = __( 'Code', 'wcpb-product-badges' );

				} else {

					$type = '';

				}

				echo esc_html( $type );

			} elseif ( 'position' == $name ) {

				$position = get_post_meta( $post->ID, '_wcpb_product_badges_badge_position', true );

				if ( 'top_left' == $position ) {

					$position = __( 'Top left', 'wcpb-product-badges' );

				} elseif ( 'top_right' == $position ) {

					$position = __( 'Top right', 'wcpb-product-badges' );

				} elseif ( 'bottom_left' == $position ) {

					$position = __( 'Bottom left', 'wcpb-product-badges' );

				} elseif ( 'bottom_right' == $position ) {

					$position = __( 'Bottom right', 'wcpb-product-badges' );

				} else {

					$position = '';

				}

				echo esc_html( $position );

			} elseif ( 'visibility' == $name ) {

				$visibility = get_post_meta( $post->ID, '_wcpb_product_badges_display_visibility', true );

				if ( 'all' == $visibility ) {

					$visibility = __( 'All', 'wcpb-product-badges' );

				} elseif ( 'product_pages' == $visibility ) {

					$visibility = __( 'Product pages', 'wcpb-product-badges' );

				} elseif ( 'product_loops' == $visibility ) {

					$visibility = __( 'Product loops', 'wcpb-product-badges' );

				} else {

					$visibility = '';

				}

				echo esc_html( $visibility );

			} elseif ( 'products' == $name ) {

				$products = get_post_meta( $post->ID, '_wcpb_product_badges_display_products', true );

				if ( 'specific' == $products ) {

					$products = __( 'Specific', 'wcpb-product-badges' );

				} elseif ( 'all' == $products ) {

					$products = __( 'All', 'wcpb-product-badges' );

				} elseif ( 'sale' == $products ) {

					$products = __( 'Sale', 'wcpb-product-badges' );

				} elseif ( 'non_sale' == $products ) {

					$products = __( 'Non-sale', 'wcpb-product-badges' );

				} elseif ( 'out_of_stock' == $products ) {

					$products = __( 'Out of stock', 'wcpb-product-badges' );

				} elseif ( 'low_stock' == $products ) {

					$products = __( 'Low stock', 'wcpb-product-badges' );

				} elseif ( 'on_backorder' == $products ) {

					$products = __( 'On backorder', 'wcpb-product-badges' );

				} elseif ( 'featured' == $products ) {

					$products = __( 'Featured', 'wcpb-product-badges' );

				} elseif ( 'custom_rules' == $products ) {

					$products = __( 'Custom rules', 'wcpb-product-badges' );

				} else {

					$products = '';

				}

				echo esc_html( $products );

			} elseif ( 'order' == $name ) {

				echo esc_html( $post->menu_order );

			}

		}

		public function meta_boxes() {

			// Badge post type

			add_meta_box(
				'wcpb-product-badges-display',
				__( 'Display', 'wcpb-product-badges' ),
				array( $this, 'meta_box_display' ),
				'wcpb_product_badge',
				'normal',
				'default'
			);

			add_meta_box(
				'wcpb-product-badges-badge',
				__( 'Badge', 'wcpb-product-badges' ),
				array( $this, 'meta_box_badge' ),
				'wcpb_product_badge',
				'normal',
				'default'
			);

			// Product post type

			add_meta_box(
				'wcpb-product-badges-product-level-badge-select',
				__( 'Badges', 'wcpb-product-badges' ),
				array( $this, 'meta_box_product_level_badge_select' ),
				'product',
				'side',
				'default'
			);

		}

		public function meta_box_display() {

			global $post;
			$post_id = $post->ID;
			$display_visibility = get_post_meta( $post_id, '_wcpb_product_badges_display_visibility', true );
			$display_products = get_post_meta( $post_id, '_wcpb_product_badges_display_products', true );
			$display_products_specific_categories = get_post_meta( $post_id, '_wcpb_product_badges_display_products_specific_categories', true );
			$display_products_specific_categories = ( !empty( $display_products_specific_categories ) ? $display_products_specific_categories : array() );
			$display_products_specific_tags = get_post_meta( $post_id, '_wcpb_product_badges_display_products_specific_tags', true );
			$display_products_specific_tags = ( !empty( $display_products_specific_tags ) ? $display_products_specific_tags : array() );
			$display_products_specific_products = get_post_meta( $post_id, '_wcpb_product_badges_display_products_specific_products', true );
			$display_products_specific_products = ( !empty( $display_products_specific_products ) ? $display_products_specific_products : array() );
			$display_products_specific_shipping_classes = get_post_meta( $post_id, '_wcpb_product_badges_display_products_specific_shipping_classes', true );
			$display_products_specific_shipping_classes = ( !empty( $display_products_specific_shipping_classes ) ? $display_products_specific_shipping_classes : array() );

			$multiple_badges_per_product = get_option( 'wcpb_product_badges_multiple_badges_per_product' );

			?>

			<div>
				<p>
					<strong><?php esc_html_e( 'Visibility', 'wcpb-product-badges' ); ?></strong>
					<input type="radio" id="wcpb-product-badges-display-visibility-all" name="wcpb_product_badges_display_visibility" value="all"<?php echo ( '' == $display_visibility || 'all' == $display_visibility ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-visibility-all"><?php esc_html_e( 'All', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-visibility-product-pages" name="wcpb_product_badges_display_visibility" value="product_pages"<?php echo ( 'product_pages' == $display_visibility ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-visibility-product-pages"><?php esc_html_e( 'Product pages', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-visibility-product-loops" name="wcpb_product_badges_display_visibility" value="product_loops"<?php echo ( 'product_loops' == $display_visibility ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-visibility-product-loops"><?php esc_html_e( 'Product loops', 'wcpb-product-badges' ); ?></label>
				</p>
				<p class="wcpb-product-badges-field-info"><?php esc_html_e( 'Product pages are individual product pages, product loops are the display of products on the shop, category and search pages, and areas such as related products and WooCommerce product blocks.', 'wcpb-product-badges' ); ?></p>
				<p>
					<strong><?php esc_html_e( 'Products', 'wcpb-product-badges' ); ?></strong>
					<input type="radio" id="wcpb-product-badges-display-products-specific" name="wcpb_product_badges_display_products" value="specific"<?php echo ( '' == $display_products || 'specific' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-specific"><?php esc_html_e( 'Specific', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-products-all" name="wcpb_product_badges_display_products" value="all"<?php echo ( 'all' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-all"><?php esc_html_e( 'All', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-products-sale" name="wcpb_product_badges_display_products" value="sale"<?php echo ( 'sale' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-sale"><?php esc_html_e( 'Sale', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-products-non-sale" name="wcpb_product_badges_display_products" value="non_sale"<?php echo ( 'non_sale' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-non-sale"><?php esc_html_e( 'Non-sale', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-products-out-of-stock" name="wcpb_product_badges_display_products" value="out_of_stock"<?php echo ( 'out_of_stock' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-out-of-stock"><?php esc_html_e( 'Out of stock', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-products-low-stock" name="wcpb_product_badges_display_products" value="low_stock"<?php echo ( 'low_stock' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-low-stock"><?php esc_html_e( 'Low stock', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-products-on-backorder" name="wcpb_product_badges_display_products" value="on_backorder"<?php echo ( 'on_backorder' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-on-backorder"><?php esc_html_e( 'On backorder', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-products-featured" name="wcpb_product_badges_display_products" value="featured"<?php echo ( 'featured' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-featured"><?php esc_html_e( 'Featured', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-display-products-custom-rules" name="wcpb_product_badges_display_products" value="custom_rules"<?php echo ( 'custom_rules' == $display_products ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-display-products-custom-rules"><?php esc_html_e( 'Custom rules', 'wcpb-product-badges' ); ?></label>
				</p>
				<?php if ( 'no' == $multiple_badges_per_product ) { ?>
					<p class="wcpb-product-badges-field-info">
						<?php
						// translators: %s: settings link
						echo wp_kses_post( sprintf( __( 'Where there are multiple product badges assigned to a product then only one product badge will display and is prioritized by the order number set from high to low (see "Attributes" meta box). If you wish to assign multiple badges per product instead of one, see %s.', 'wcpb-product-badges' ), '<a href="admin.php?page=wc-settings&tab=products&section=wcpb-product-badges">' . __( 'settings', 'wcpb-product-badges' ) . '</a>' ) );
						?>
					</p>
				<?php } ?>
			</div>

			<div id="wcpb-product-badges-display-products-specific-expand">
				<div>
					<label>
						<?php esc_html_e( 'Products', 'wcpb-product-badges' ); ?><br>
						<select class="wcpb-product-badges-select2-ajax-display-products-specific-products" name="wcpb_product_badges_display_products_specific_products[]" multiple="multiple">
							<?php
							foreach ( $display_products_specific_products as $product_id ) {
								?>
								<option value="<?php echo esc_html( $product_id ); ?>" selected><?php echo wp_kses_post( get_the_title( $product_id ) ) . ' ' . esc_html__( '-', 'wcpb-product-badges' ) . ' ' . esc_html__( 'ID:', 'wcpb-product-badges' ) . ' ' . esc_html( $product_id ); ?></option>
								<?php
							}
							?>
						</select>
					</label>
				</div>
				<div>
					<label><?php esc_html_e( 'Categories', 'wcpb-product-badges' ); ?><br>
						<select class="wcpb-product-badges-select2-ajax-display-products-specific-categories" name="wcpb_product_badges_display_products_specific_categories[]" multiple="multiple">
							<?php
							foreach ( $display_products_specific_categories as $category_id ) {
								$term_names = array();
								foreach ( get_ancestors( $category_id, 'product_cat' ) as $ancestor_id ) {
									$term_names[] = get_term( $ancestor_id, 'product_cat' )->name;
								}
								$term_names[] = get_term( $category_id, 'product_cat' )->name;
								$category_name = implode( ' > ', $term_names ) . ' ' . esc_html__( '-', 'wcpb-product-badges' ) . ' ' . esc_html__( 'ID:', 'wcpb-product-badges' ) . ' ' . esc_html( $category_id );
								?>
								<option value="<?php echo esc_html( $category_id ); ?>" selected><?php echo wp_kses_post( $category_name ); ?></option>
								<?php
							}
							?>
						</select>
					</label>
				</div>
				<div>
					<label><?php esc_html_e( 'Tags', 'wcpb-product-badges' ); ?><br>
						<select class="wcpb-product-badges-select2-ajax-display-products-specific-tags" name="wcpb_product_badges_display_products_specific_tags[]" multiple="multiple">
							<?php
							foreach ( $display_products_specific_tags as $tag_id ) {
								?>
								<option value="<?php echo esc_html( $tag_id ); ?>" selected><?php echo wp_kses_post( get_term( $tag_id )->name ) . ' ' . esc_html__( '-', 'wcpb-product-badges' ) . ' ' . esc_html__( 'ID:', 'wcpb-product-badges' ) . ' ' . esc_html( $tag_id ); ?></option>
								<?php
							}
							?>
						</select>
					</label>
				</div>
				<div>
					<label>
						<?php esc_html_e( 'Shipping classes', 'wcpb-product-badges' ); ?><br>
						<select class="wcpb-product-badges-select2-ajax-display-products-specific-shipping-classes" name="wcpb_product_badges_display_products_specific_shipping_classes[]" multiple="multiple">
							<?php
							foreach ( $display_products_specific_shipping_classes as $shipping_class_id ) {
								?>
								<option value="<?php echo esc_html( $shipping_class_id ); ?>" selected><?php echo wp_kses_post( get_term( $shipping_class_id )->name ) . ' ' . esc_html__( '-', 'wcpb-product-badges' ) . ' ' . esc_html__( 'ID:', 'wcpb-product-badges' ) . ' ' . esc_html( $shipping_class_id ); ?></option>
								<?php
							}
							?>
						</select>
					</label>
				</div>
			</div>

			<div id="wcpb-product-badges-display-products-custom-rules-expand">
				<div>
					<p><strong><?php esc_html_e( 'How to use custom rules:', 'wcpb-product-badges' ); ?></strong></p>
					<p><?php esc_html_e( 'Custom rules are applied via a WordPress filter hook. To create custom rules, add code to your theme\'s functions.php file or via a code snippets plugin:', 'wcpb-product-badges' ); ?></p>
					<?php // Indenting has to be like this to preserve the formatting in the code block ?>
					<pre>function my_custom_badge_rules( $display, $product, $badge_id ) {
	if ( $badge_id == <?php echo esc_html( $post_id ); ?> && $product->get_price() > 100 ) {
		return true;
	}
	return false;
}
add_filter( 'wcpb_product_badges_custom_rules_display', 'my_custom_badge_rules', 10, 3 );</pre>
					<p><strong><?php esc_html_e( 'Parameters:', 'wcpb-product-badges' ); ?></strong></p>
					<ul>
						<li><code>$display</code> - <?php esc_html_e( 'Boolean, default false', 'wcpb-product-badges' ); ?></li>
						<li><code>$product</code> - <?php esc_html_e( 'WC_Product object', 'wcpb-product-badges' ); ?></li>
						<?php // translators: %s: badge ID ?>
						<li><code>$badge_id</code> - <?php printf( esc_html__( 'The badge ID (the badge you\'re currently editing is %s)', 'wcpb-product-badges' ), esc_html( $post_id ) ); ?></li>
					</ul>
					<p><strong><?php esc_html_e( 'Example:', 'wcpb-product-badges' ); ?></strong></p>
					<p>
						<?php
						// translators: %s: badge ID
						echo sprintf( esc_html__( 'The example above shows how to display this badge (ID: %s) only for products with a price greater than 100.', 'wcpb-product-badges' ), esc_html( $post_id ) );
						?>
					</p>
				</div>
			</div>

			<?php

		}

		public function meta_box_badge() {

			global $_wp_admin_css_colors;
			global $post;

			$post_id = $post->ID;
			$badge_type = get_post_meta( $post_id, '_wcpb_product_badges_badge_type', true );
			$badge_position = get_post_meta( $post_id, '_wcpb_product_badges_badge_position', true );
			$badge_offset_pixels = get_post_meta( $post_id, '_wcpb_product_badges_badge_offset_pixels', true );
			$badge_offset_pixels = ( '' == $badge_offset_pixels ? '10' : ( (int) $badge_offset_pixels >= 0 ? $badge_offset_pixels : '10' ) ); // If not set (new badge) set to default of 10, if set then use, if anything else (shouldn't be) use default of 10
			$badge_size_width = get_post_meta( $post_id, '_wcpb_product_badges_badge_size_width', true );
			$badge_size_width = ( '' == $badge_size_width ? '120' : ( (int) $badge_size_width >= 0 ? $badge_size_width : '120' ) ); // If not set (new badge) set to default of 120, if set then use, if anything else (shouldn't be) use default of 120
			$badge_animation = get_post_meta( $post_id, '_wcpb_product_badges_badge_animation', true );
			$badge_animation = ( empty( $badge_animation ) ? 'none' : $badge_animation );  // If not set (new badge) set to default of none, if set then use, if anything else (shouldn't be) use default of none
			$badge_animation_duration = get_post_meta( $post_id, '_wcpb_product_badges_badge_animation_duration', true );
			$badge_animation_duration = ( empty( $badge_animation_duration ) ? '1000' : $badge_animation_duration ); // If not set (new badge) set to default of 1000, if set then use, if anything else (shouldn't be) use default of 1000

			$badge_image_library_image = get_post_meta( $post_id, '_wcpb_product_badges_badge_image_library_image', true );

			$badge_countdown_countdown_to = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_countdown_to', true );
			$badge_countdown_text_before = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_text_before', true );
			$badge_countdown_text_after = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_text_after', true );
			$badge_countdown_text_align = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_text_align', true );
			$badge_countdown_text_align = ( empty( $badge_countdown_text_align ) ? 'center' : $badge_countdown_text_align ); // Default
			$badge_countdown_text_color = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_text_color', true );
			$badge_countdown_background_color = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_background_color', true );
			$badge_countdown_font_weight = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_font_weight', true );
			$badge_countdown_font_weight = ( empty( $badge_countdown_font_weight ) ? 'normal' : $badge_countdown_font_weight ); // Default
			$badge_countdown_font_style = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_font_style', true );
			$badge_countdown_font_style = ( empty( $badge_countdown_font_style ) ? 'normal' : $badge_countdown_font_style ); // Default
			$badge_countdown_font_size = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_font_size', true );
			$badge_countdown_font_size = ( empty( $badge_countdown_font_size ) ? '12' : $badge_countdown_font_size ); // Default
			$badge_countdown_padding_top = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_padding_top', true );
			$badge_countdown_padding_top = ( empty( $badge_countdown_padding_top ) ? '0' : $badge_countdown_padding_top ); // Default
			$badge_countdown_padding_right = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_padding_right', true );
			$badge_countdown_padding_right = ( empty( $badge_countdown_padding_right ) ? '0' : $badge_countdown_padding_right ); // Default
			$badge_countdown_padding_bottom = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_padding_bottom', true );
			$badge_countdown_padding_bottom = ( empty( $badge_countdown_padding_bottom ) ? '0' : $badge_countdown_padding_bottom ); // Default
			$badge_countdown_padding_left = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_padding_left', true );
			$badge_countdown_padding_left = ( empty( $badge_countdown_padding_left ) ? '0' : $badge_countdown_padding_left ); // Default
			$badge_countdown_border_radius_top_left = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_border_radius_top_left', true );
			$badge_countdown_border_radius_top_left = ( empty( $badge_countdown_border_radius_top_left ) ? '0' : $badge_countdown_border_radius_top_left ); // Default
			$badge_countdown_border_radius_top_right = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_border_radius_top_right', true );
			$badge_countdown_border_radius_top_right = ( empty( $badge_countdown_border_radius_top_right ) ? '0' : $badge_countdown_border_radius_top_right ); // Default
			$badge_countdown_border_radius_bottom_left = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_border_radius_bottom_left', true );
			$badge_countdown_border_radius_bottom_left = ( empty( $badge_countdown_border_radius_bottom_left ) ? '0' : $badge_countdown_border_radius_bottom_left ); // Default
			$badge_countdown_border_radius_bottom_right = get_post_meta( $post_id, '_wcpb_product_badges_badge_countdown_border_radius_bottom_right', true );
			$badge_countdown_border_radius_bottom_right = ( empty( $badge_countdown_border_radius_bottom_right ) ? '0' : $badge_countdown_border_radius_bottom_right ); // Default

			$badge_text_text = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_text', true );
			$badge_text_text_align = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_text_align', true );
			$badge_text_text_align = ( empty( $badge_text_text_align ) ? 'center' : $badge_text_text_align ); // Default
			$badge_text_text_color = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_text_color', true );
			$badge_text_background_color = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_background_color', true );
			$badge_text_font_weight = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_font_weight', true );
			$badge_text_font_weight = ( empty( $badge_text_font_weight ) ? 'normal' : $badge_text_font_weight ); // Default
			$badge_text_font_style = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_font_style', true );
			$badge_text_font_style = ( empty( $badge_text_font_style ) ? 'normal' : $badge_text_font_style ); // Default
			$badge_text_font_size = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_font_size', true );
			$badge_text_font_size = ( empty( $badge_text_font_size ) ? '12' : $badge_text_font_size ); // Default
			$badge_text_padding_top = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_padding_top', true );
			$badge_text_padding_top = ( empty( $badge_text_padding_top ) ? '0' : $badge_text_padding_top ); // Default
			$badge_text_padding_right = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_padding_right', true );
			$badge_text_padding_right = ( empty( $badge_text_padding_right ) ? '0' : $badge_text_padding_right ); // Default
			$badge_text_padding_bottom = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_padding_bottom', true );
			$badge_text_padding_bottom = ( empty( $badge_text_padding_bottom ) ? '0' : $badge_text_padding_bottom ); // Default
			$badge_text_padding_left = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_padding_left', true );
			$badge_text_padding_left = ( empty( $badge_text_padding_left ) ? '0' : $badge_text_padding_left ); // Default
			$badge_text_border_radius_top_left = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_border_radius_top_left', true );
			$badge_text_border_radius_top_left = ( empty( $badge_text_border_radius_top_left ) ? '0' : $badge_text_border_radius_top_left ); // Default
			$badge_text_border_radius_top_right = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_border_radius_top_right', true );
			$badge_text_border_radius_top_right = ( empty( $badge_text_border_radius_top_right ) ? '0' : $badge_text_border_radius_top_right ); // Default
			$badge_text_border_radius_bottom_left = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_border_radius_bottom_left', true );
			$badge_text_border_radius_bottom_left = ( empty( $badge_text_border_radius_bottom_left ) ? '0' : $badge_text_border_radius_bottom_left ); // Default
			$badge_text_border_radius_bottom_right = get_post_meta( $post_id, '_wcpb_product_badges_badge_text_border_radius_bottom_right', true );
			$badge_text_border_radius_bottom_right = ( empty( $badge_text_border_radius_bottom_right ) ? '0' : $badge_text_border_radius_bottom_right ); // Default

			$badge_code_code = get_post_meta( $post_id, '_wcpb_product_badges_badge_code_code', true );

			wp_nonce_field( 'wcpb_product_badge_save', 'wcpb_product_badge_save_nonce' );

			?>

			<div>
				<p>
					<strong><?php esc_html_e( 'Type', 'wcpb-product-badges' ); ?></strong>
					<input type="radio" id="wcpb-product-badges-badge-type-image-library" name="wcpb_product_badges_badge_type" value="image_library"<?php echo ( '' == $badge_type || 'image_library' == $badge_type ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-type-image-library"><?php esc_html_e( 'Image library', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-badge-type-image-custom" name="wcpb_product_badges_badge_type" value="image_custom"<?php echo ( 'image_custom' == $badge_type ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-type-image-custom"><?php esc_html_e( 'Image custom', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-badge-type-countdown" name="wcpb_product_badges_badge_type" value="countdown"<?php echo ( 'countdown' == $badge_type ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-type-countdown"><?php esc_html_e( 'Countdown', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-badge-type-text" name="wcpb_product_badges_badge_type" value="text"<?php echo ( 'text' == $badge_type ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-type-text"><?php esc_html_e( 'Text', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-badge-type-code" name="wcpb_product_badges_badge_type" value="code"<?php echo ( 'code' == $badge_type ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-type-code"><?php esc_html_e( 'Code', 'wcpb-product-badges' ); ?></label>
				</p>
				<div id="wcpb-product-badges-badge-image-library-filters">
					<strong><?php esc_html_e( 'Image library filters', 'wcpb-product-badges' ); ?></strong>
					<div><?php // Options added dynamically, do not indent as JS condition will not be met ?></div>
				</div>
				<p>
					<strong><?php esc_html_e( 'Position', 'wcpb-product-badges' ); ?></strong>
					<input type="radio" id="wcpb-product-badges-badge-position-top-left" name="wcpb_product_badges_badge_position" value="top_left"<?php echo ( 'top_left' == $badge_position ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-position-top-left"><?php esc_html_e( 'Top left', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-badge-position-top-right" name="wcpb_product_badges_badge_position" value="top_right"<?php echo ( '' == $badge_position || 'top_right' == $badge_position ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-position-top-right"><?php esc_html_e( 'Top right', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-badge-position-bottom-left" name="wcpb_product_badges_badge_position" value="bottom_left"<?php echo ( 'bottom_left' == $badge_position ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-position-bottom-left"><?php esc_html_e( 'Bottom left', 'wcpb-product-badges' ); ?></label><br>
					<input type="radio" id="wcpb-product-badges-badge-position-bottom-right" name="wcpb_product_badges_badge_position" value="bottom_right"<?php echo ( 'bottom_right' == $badge_position ? ' checked' : '' ); ?>>
					<label for="wcpb-product-badges-badge-position-bottom-right"><?php esc_html_e( 'Bottom right', 'wcpb-product-badges' ); ?></label>
				</p>
				<p>
					<strong><?php esc_html_e( 'Offset', 'wcpb-product-badges' ); ?></strong>
					<label>
						<?php esc_html_e( 'Pixels', 'wcpb-product-badges' ); ?><br>
						<input type="number" id="wcpb-product-badges-badge-offset-pixels" name="wcpb_product_badges_badge_offset_pixels" value="<?php echo esc_html( $badge_offset_pixels ); ?>" step="1" min="0" required><br>
					</label>
					<p class="wcpb-product-badges-field-info"><?php esc_html_e( 'Offsets the badge from the edge. Set to 0 for no offset from the edge.', 'wcpb-product-badges' ); ?></p>
				</p>
				<p>
					<strong><?php esc_html_e( 'Size', 'wcpb-product-badges' ); ?></strong>
					<label>
						<?php esc_html_e( 'Width', 'wcpb-product-badges' ); ?><br>
						<input type="number" id="wcpb-product-badges-badge-size-width" name="wcpb_product_badges_badge_size_width" value="<?php echo esc_html( $badge_size_width ); ?>" step="1" min="0" required><br>
					</label>
					<p class="wcpb-product-badges-field-info">
						<?php
						// translators: %s: responsive breakpoints link
						echo wp_kses_post( sprintf( __( 'Height is automatically calculated from width, learn how to use different widths for %s. Set to 0 to fit to content. Use of 0 is only recommended for countdown or text based badges.', 'wcpb-product-badges' ), '<a href="#TB_inline?&width=600&height=550&inlineId=wcpb-product-badges-responsive-breakpoints" class="thickbox" title="' . __( 'Responsive breakpoints', 'wcpb-product-badges' ) . '">' . __( 'responsive breakpoints', 'wcpb-product-badges' ) . '</a>' ) );
						?>
					</p>
					<div id="wcpb-product-badges-responsive-breakpoints">
						<p><?php esc_html_e( 'The width set on the badge is used across all breakpoints e.g. desktop, tablet and mobile. The height is automatically calculated based on the width set.', 'wcpb-product-badges' ); ?></p>
						<p><?php esc_html_e( 'If you wish to use different widths for specific breakpoints you can do so using some custom CSS added into your theme options/stylesheet.', 'wcpb-product-badges' ); ?></p>
						<p><?php esc_html_e( 'This is done via CSS using media queries and targeting the elements which have the following class:', 'wcpb-product-badges' ); ?></p>
						<p><code>wcpb-product-badges-badge</code></p>
						<p><strong><?php esc_html_e( 'Example CSS', 'wcpb-product-badges' ); ?></strong></p>
						<p><code>@media only screen and ( max-width: 600px ) { .wcpb-product-badges-badge { width: 80px !important; } }</code></p>
						<p><?php esc_html_e( 'This example sets the badge width to 80px when the browser window is 600px wide or less. You can add multiple media queries and different widths depending on how you would like badges to be sized at different breakpoints.', 'wcpb-product-badges' ); ?></p>
					</div>
				</p>
				<p>
					<strong><?php esc_html_e( 'Animation', 'wcpb-product-badges' ); ?></strong>
					<select name="wcpb_product_badges_badge_animation" id="wcpb-product-badges-badge-animation">
						<option value="none"<?php echo ( 'none' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'None', 'wcpb-product-badges' ); ?></option>
						<option value="bounce"<?php echo ( 'bounce' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Bounce', 'wcpb-product-badges' ); ?></option>
						<option value="breathing"<?php echo ( 'breathing' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Breathing', 'wcpb-product-badges' ); ?></option>
						<option value="jiggle"<?php echo ( 'jiggle' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Jiggle', 'wcpb-product-badges' ); ?></option>
						<option value="rotate"<?php echo ( 'rotate' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Rotate', 'wcpb-product-badges' ); ?></option>
						<option value="shadow-pulse"<?php echo ( 'shadow-pulse' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Shadow Pulse', 'wcpb-product-badges' ); ?></option>
						<option value="shake"<?php echo ( 'shake' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Shake', 'wcpb-product-badges' ); ?></option>
						<option value="sway"<?php echo ( 'sway' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Sway', 'wcpb-product-badges' ); ?></option>
						<option value="tilt"<?php echo ( 'tilt' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Tilt', 'wcpb-product-badges' ); ?></option>
						<option value="wave"<?php echo ( 'wave' == $badge_animation ? ' selected' : '' ); ?>><?php esc_html_e( 'Wave', 'wcpb-product-badges' ); ?></option>
					</select>
				</p>
				<p>
					<label for="wcpb-product-badges-badge-animation-duration"><?php esc_html_e( 'Duration (milliseconds)', 'wcpb-product-badges' ); ?></label><br>
					<input type="number" name="wcpb_product_badges_badge_animation_duration" id="wcpb-product-badges-badge-animation-duration" value="<?php echo esc_html( $badge_animation_duration ); ?>" min="500" max="5000" step="100">
				</p>
			</div>

			<div id="wcpb-product-badges-badge-image-library-expand">
				<style>
					.wcpb-product-badges-badge-image-library-image-selected > div {
						border: 3px solid <?php echo esc_html( $_wp_admin_css_colors[get_user_option('admin_color')]->colors[2] ); ?> !important;
					}
				</style>
				<input type="hidden" name="wcpb_product_badges_badge_image_library_image" id="wcpb-product-badges-badge-image-library-image" value="<?php echo esc_html( $badge_image_library_image ); ?>">
				<?php
				$images = $this->badge_image_library_images();
				$filters = array();
				if ( !empty( $images ) ) {

					foreach ( $images as $image_file => $image_data ) {

						$filters['type'][] = $image_data['type'];
						$filters['color'][] = $image_data['color'];

						?>

						<div data-image="<?php echo esc_html( $image_file ); ?>" class="wcpb-product-badges-badge-image-library-image wcpb-product-badges-badge-image-library-image-filter-all-type wcpb-product-badges-badge-image-library-image-filter-all-color <?php echo 'wcpb-product-badges-badge-image-library-image-filter-type-' . esc_html( $image_data['type'] ) . ' wcpb-product-badges-badge-image-library-image-filter-color-' . esc_html( $image_data['color'] ); ?><?php echo ( $image_file == $badge_image_library_image ? ' wcpb-product-badges-badge-image-library-image-selected' : '' ); ?>">
							<div style="background-image: url(<?php echo esc_url( WCPB_PRODUCT_BADGES_BADGES_URL ); ?><?php echo esc_html( $image_file ); ?>)"></div>
						</div>

						<?php

					}

					$filters['type'] = array_unique( $filters['type'] );
					$filters['color'] = array_unique( $filters['color'] );

					?>

					<div id="wcpb-product-badges-badge-image-library-filters-before-append" data-filters-no-results-text="<?php esc_html_e( 'No library images available for your selected filters, try changing the filters set.', 'wcpb-product-badges' ); ?>">
						<?php
						foreach ( $filters as $filter_name => $filter_values ) {
							asort( $filter_values );
							?>
							<label>
								<?php echo esc_html( ucfirst( $filter_name ) ); ?><br>
								<select data-filter="<?php echo esc_html( $filter_name ); ?>">
									<option value="wcpb-product-badges-badge-image-library-image-filter-all-<?php echo esc_html( $filter_name ); ?>"><?php esc_html_e( 'All', 'wcpb-product-badges' ); ?></option>
									<?php
									foreach ( $filter_values as $filter_value_key => $filter_value ) {

										$filter_value_display_text = $filter_value; // Fallback

										if ( 'type' == $filter_name ) {

											if ( 'availability' == $filter_value ) {

												$filter_value_display_text = __( 'Availability', 'wcpb-product-badges' );

											} elseif ( 'black-friday' == $filter_value ) {

												$filter_value_display_text = __( 'Black friday', 'wcpb-product-badges' );

											} elseif ( 'christmas' == $filter_value ) {

												$filter_value_display_text = __( 'Christmas', 'wcpb-product-badges' );

											} elseif ( 'cyber-monday' == $filter_value ) {

												$filter_value_display_text = __( 'Cyber monday', 'wcpb-product-badges' );

											} elseif ( 'easter' == $filter_value ) {

												$filter_value_display_text = __( 'Easter', 'wcpb-product-badges' );

											} elseif ( 'fathers-day' == $filter_value ) {

												$filter_value_display_text = __( 'Father\'s day', 'wcpb-product-badges' );

											} elseif ( 'general' == $filter_value ) {

												$filter_value_display_text = __( 'General', 'wcpb-product-badges' );

											} elseif ( 'halloween' == $filter_value ) {

												$filter_value_display_text = __( 'Halloween', 'wcpb-product-badges' );

											} elseif ( 'mothers-day' == $filter_value ) {

												$filter_value_display_text = __( 'Mother\'s day', 'wcpb-product-badges' );

											} elseif ( 'valentines' == $filter_value ) {

												$filter_value_display_text = __( 'Valentine\'s', 'wcpb-product-badges' );

											}

										} elseif ( 'color' == $filter_name ) {

											if ( 'black' == $filter_value ) {

												$filter_value_display_text = __( 'Black', 'wcpb-product-badges' );

											} elseif ( 'blue' == $filter_value ) {

												$filter_value_display_text = __( 'Blue', 'wcpb-product-badges' );

											} elseif ( 'gray' == $filter_value ) {

												$filter_value_display_text = __( 'Gray', 'wcpb-product-badges' );

											} elseif ( 'green' == $filter_value ) {

												$filter_value_display_text = __( 'Green', 'wcpb-product-badges' );

											} elseif ( 'orange' == $filter_value ) {

												$filter_value_display_text = __( 'Orange', 'wcpb-product-badges' );

											} elseif ( 'pink' == $filter_value ) {

												$filter_value_display_text = __( 'Pink', 'wcpb-product-badges' );

											} elseif ( 'purple' == $filter_value ) {

												$filter_value_display_text = __( 'Purple', 'wcpb-product-badges' );

											} elseif ( 'red' == $filter_value ) {

												$filter_value_display_text = __( 'Red', 'wcpb-product-badges' );

											} elseif ( 'white' == $filter_value ) {

												$filter_value_display_text = __( 'White', 'wcpb-product-badges' );

											} elseif ( 'yellow' == $filter_value ) {

												$filter_value_display_text = __( 'Yellow', 'wcpb-product-badges' );

											}

										}

										?>
										<option value="wcpb-product-badges-badge-image-library-image-filter-<?php echo esc_html( $filter_name ); ?>-<?php echo esc_html( $filter_value ); ?>"><?php echo esc_html( $filter_value_display_text ); ?></option>
										<?php
									}
									?>
								</select>
							</label>
							<?php
						}
						?>
					</div>

				<?php } ?>

			</div>

			<div id="wcpb-product-badges-badge-image-custom-expand">
				<p><?php esc_html_e( 'Select a custom image using the "Badge image custom" meta box.', 'wcpb-product-badges' ); ?></p>
			</div>

			<div id="wcpb-product-badges-badge-countdown-expand">
				<div>
					<p>
						<label>
							<?php esc_html_e( 'Countdown to', 'wcpb-product-badges' ); ?><br>
							<input type="text" name="wcpb_product_badges_badge_countdown_countdown_to" class="wcpb-product-badges-flatpickr" value="<?php echo esc_html( $badge_countdown_countdown_to ); ?>">
						</label>
					</p>
					<p>
						<label>
							<?php esc_html_e( 'Text before', 'wcpb-product-badges' ); ?><br>
							<input type="text" name="wcpb_product_badges_badge_countdown_text_before" value="<?php echo esc_html( $badge_countdown_text_before ); ?>">
						</label>
					</p>
					<p>
						<label>
							<?php esc_html_e( 'Text after', 'wcpb-product-badges' ); ?><br>
							<input type="text" name="wcpb_product_badges_badge_countdown_text_after" value="<?php echo esc_html( $badge_countdown_text_after ); ?>">
						</label>
					</p>
					<p>
						<label>
							<?php esc_html_e( 'Text align', 'wcpb-product-badges' ); ?><br>
							<select name="wcpb_product_badges_badge_countdown_text_align">
								<option value="center"<?php echo ( 'center' == $badge_countdown_text_align ? ' selected' : '' ); ?>><?php esc_html_e( 'Center', 'wcpb-product-badges' ); ?></option>
								<option value="left"<?php echo ( 'left' == $badge_countdown_text_align ? ' selected' : '' ); ?>><?php esc_html_e( 'Left', 'wcpb-product-badges' ); ?></option>
								<option value="right"<?php echo ( 'right' == $badge_countdown_text_align ? ' selected' : '' ); ?>><?php esc_html_e( 'Right', 'wcpb-product-badges' ); ?></option>
							</select>
						</label>
					</p>
					<p>
						<label for="wcpb-product-badges-badge-countdown-text-color"><?php esc_html_e( 'Text color', 'wcpb-product-badges' ); ?></label><br>
						<input type="text" name="wcpb_product_badges_badge_countdown_text_color" id="wcpb-product-badges-badge-countdown-text-color" class="wcpb-product-badges-color-picker" value="<?php echo esc_html( $badge_countdown_text_color ); ?>">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-countdown-background-color"><?php esc_html_e( 'Background color', 'wcpb-product-badges' ); ?></label><br>
						<input type="text" name="wcpb_product_badges_badge_countdown_background_color" id="wcpb-product-badges-badge-countdown-background-color" class="wcpb-product-badges-color-picker" value="<?php echo esc_html( $badge_countdown_background_color ); ?>">
					</p>
				</div>
				<div>
					<p>
						<label for="wcpb-product-badges-badge-countdown-font-size"><?php esc_html_e( 'Font size', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_font_size" id="wcpb-product-badges-badge-countdown-font-size" value="<?php echo esc_html( $badge_countdown_font_size ); ?>" min="1">
					</p>
					<p>
						<label>
							<?php esc_html_e( 'Font style', 'wcpb-product-badges' ); ?><br>
							<select name="wcpb_product_badges_badge_countdown_font_style">
								<option value="normal"<?php echo ( 'normal' == $badge_countdown_font_style ? ' selected' : '' ); ?>><?php esc_html_e( 'Normal', 'wcpb-product-badges' ); ?></option>
								<option value="italic"<?php echo ( 'italic' == $badge_countdown_font_style ? ' selected' : '' ); ?>><?php esc_html_e( 'Italic', 'wcpb-product-badges' ); ?></option>
							</select>
						</label>
					</p>
					<p>
						<label>
							<?php esc_html_e( 'Font weight', 'wcpb-product-badges' ); ?><br>
							<select name="wcpb_product_badges_badge_countdown_font_weight">
								<option value="normal"<?php echo ( 'normal' == $badge_countdown_font_weight ? ' selected' : '' ); ?>><?php esc_html_e( 'Normal', 'wcpb-product-badges' ); ?></option>
								<option value="bold"<?php echo ( 'bold' == $badge_countdown_font_weight ? ' selected' : '' ); ?>><?php esc_html_e( 'Bold', 'wcpb-product-badges' ); ?></option>
							</select>
						</label>
					</p>
				</div>
				<div>
					<p>
						<label for="wcpb-product-badges-badge-countdown-padding-top"><?php esc_html_e( 'Padding top', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_padding_top" id="wcpb-product-badges-badge-countdown-padding-top" value="<?php echo esc_html( $badge_countdown_padding_top ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-countdown-padding-right"><?php esc_html_e( 'Padding right', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_padding_right" id="wcpb-product-badges-badge-countdown-padding-right" value="<?php echo esc_html( $badge_countdown_padding_right ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-countdown-padding-bottom"><?php esc_html_e( 'Padding bottom', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_padding_bottom" id="wcpb-product-badges-badge-countdown-padding-bottom" value="<?php echo esc_html( $badge_countdown_padding_bottom ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-countdown-padding-left"><?php esc_html_e( 'Padding left', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_padding_left" id="wcpb-product-badges-badge-countdown-padding-left" value="<?php echo esc_html( $badge_countdown_padding_left ); ?>" min="0">
					</p>
				</div>
				<div>
					<p>
						<label for="wcpb-product-badges-badge-countdown-border-radius-top-left"><?php esc_html_e( 'Border radius top left', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_border_radius_top_left" id="wcpb-product-badges-badge-countdown-border-radius-top-left" value="<?php echo esc_html( $badge_countdown_border_radius_top_left ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-countdown-border-radius-top-right"><?php esc_html_e( 'Border radius top right', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_border_radius_top_right" id="wcpb-product-badges-badge-countdown-border-radius-top-right" value="<?php echo esc_html( $badge_countdown_border_radius_top_right ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-countdown-border-radius-bottom-left"><?php esc_html_e( 'Border radius bottom left', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_border_radius_bottom_left" id="wcpb-product-badges-badge-countdown-border-radius-bottom-left" value="<?php echo esc_html( $badge_countdown_border_radius_bottom_left ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-countdown-border-radius-bottom-right"><?php esc_html_e( 'Border radius bottom right', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_countdown_border_radius_bottom_right" id="wcpb-product-badges-badge-countdown-border-radius-bottom-right" value="<?php echo esc_html( $badge_countdown_border_radius_bottom_right ); ?>" min="0">
					</p>
				</div>
			</div>

			<div id="wcpb-product-badges-badge-text-expand">
				<div>
					<p>
						<label>
							<?php esc_html_e( 'Text', 'wcpb-product-badges' ); ?><br>
							<input type="text" name="wcpb_product_badges_badge_text_text" value="<?php echo esc_html( $badge_text_text ); ?>">
						</label>
					</p>
					<p class="wcpb-product-badges-field-info">
						<?php
						// translators: %1$s - product price, %2$s - stock quantity or status, %3$s - sale price if on sale, %4$s - regular price, %5$s - discount percentage if on sale.
						printf( esc_html__( 'Use these placeholders to display product information: %1$s - product price, %2$s - stock quantity or status, %3$s - sale price if on sale, %4$s - regular price, %5$s - discount percentage if on sale.', 'wcpb-product-badges' ), '[price]', '[stock]', '[sale_price]', '[regular_price]', '[discount_percentage]' );
						?>
					</p>
					<p>
						<label>
							<?php esc_html_e( 'Text align', 'wcpb-product-badges' ); ?><br>
							<select name="wcpb_product_badges_badge_text_text_align">
								<option value="center"<?php echo ( 'center' == $badge_text_text_align ? ' selected' : '' ); ?>><?php esc_html_e( 'Center', 'wcpb-product-badges' ); ?></option>
								<option value="left"<?php echo ( 'left' == $badge_text_text_align ? ' selected' : '' ); ?>><?php esc_html_e( 'Left', 'wcpb-product-badges' ); ?></option>
								<option value="right"<?php echo ( 'right' == $badge_text_text_align ? ' selected' : '' ); ?>><?php esc_html_e( 'Right', 'wcpb-product-badges' ); ?></option>
							</select>
						</label>
					</p>
					<p>
						<label for="wcpb-product-badges-badge-text-text-color"><?php esc_html_e( 'Text color', 'wcpb-product-badges' ); ?></label><br>
						<input type="text" name="wcpb_product_badges_badge_text_text_color" id="wcpb-product-badges-badge-text-text-color" class="wcpb-product-badges-color-picker" value="<?php echo esc_html( $badge_text_text_color ); ?>">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-text-background-color"><?php esc_html_e( 'Background color', 'wcpb-product-badges' ); ?></label><br>
						<input type="text" name="wcpb_product_badges_badge_text_background_color" id="wcpb-product-badges-badge-text-background-color" class="wcpb-product-badges-color-picker" value="<?php echo esc_html( $badge_text_background_color ); ?>">
					</p>
				</div>
				<div>
					<p>
						<label for="wcpb-product-badges-badge-text-font-size"><?php esc_html_e( 'Font size', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_font_size" id="wcpb-product-badges-badge-text-font-size" value="<?php echo esc_html( $badge_text_font_size ); ?>" min="1">
					</p>
					<p>
						<label>
							<?php esc_html_e( 'Font style', 'wcpb-product-badges' ); ?><br>
							<select name="wcpb_product_badges_badge_text_font_style">
								<option value="normal"<?php echo ( 'normal' == $badge_text_font_style ? ' selected' : '' ); ?>><?php esc_html_e( 'Normal', 'wcpb-product-badges' ); ?></option>
								<option value="italic"<?php echo ( 'italic' == $badge_text_font_style ? ' selected' : '' ); ?>><?php esc_html_e( 'Italic', 'wcpb-product-badges' ); ?></option>
							</select>
						</label>
					</p>
					<p>
						<label>
							<?php esc_html_e( 'Font weight', 'wcpb-product-badges' ); ?><br>
							<select name="wcpb_product_badges_badge_text_font_weight">
								<option value="normal"<?php echo ( 'normal' == $badge_text_font_weight ? ' selected' : '' ); ?>><?php esc_html_e( 'Normal', 'wcpb-product-badges' ); ?></option>
								<option value="bold"<?php echo ( 'bold' == $badge_text_font_weight ? ' selected' : '' ); ?>><?php esc_html_e( 'Bold', 'wcpb-product-badges' ); ?></option>
							</select>
						</label>
					</p>
				</div>
				<div>
					<p>
						<label for="wcpb-product-badges-badge-text-padding-top"><?php esc_html_e( 'Padding top', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_padding_top" id="wcpb-product-badges-badge-text-padding-top" value="<?php echo esc_html( $badge_text_padding_top ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-text-padding-right"><?php esc_html_e( 'Padding right', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_padding_right" id="wcpb-product-badges-badge-text-padding-right" value="<?php echo esc_html( $badge_text_padding_right ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-text-padding-bottom"><?php esc_html_e( 'Padding bottom', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_padding_bottom" id="wcpb-product-badges-badge-text-padding-bottom" value="<?php echo esc_html( $badge_text_padding_bottom ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-text-padding-left"><?php esc_html_e( 'Padding left', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_padding_left" id="wcpb-product-badges-badge-text-padding-left" value="<?php echo esc_html( $badge_text_padding_left ); ?>" min="0">
					</p>
				</div>
				<div>
					<p>
						<label for="wcpb-product-badges-badge-text-border-radius-top-left"><?php esc_html_e( 'Border radius top left', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_border_radius_top_left" id="wcpb-product-badges-badge-text-border-radius-top-left" value="<?php echo esc_html( $badge_text_border_radius_top_left ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-text-border-radius-top-right"><?php esc_html_e( 'Border radius top right', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_border_radius_top_right" id="wcpb-product-badges-badge-text-border-radius-top-right" value="<?php echo esc_html( $badge_text_border_radius_top_right ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-text-border-radius-bottom-left"><?php esc_html_e( 'Border radius bottom left', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_border_radius_bottom_left" id="wcpb-product-badges-badge-text-border-radius-bottom-left" value="<?php echo esc_html( $badge_text_border_radius_bottom_left ); ?>" min="0">
					</p>
					<p>
						<label for="wcpb-product-badges-badge-text-border-radius-bottom-right"><?php esc_html_e( 'Border radius bottom right', 'wcpb-product-badges' ); ?></label><br>
						<input type="number" name="wcpb_product_badges_badge_text_border_radius_bottom_right" id="wcpb-product-badges-badge-text-border-radius-bottom-right" value="<?php echo esc_html( $badge_text_border_radius_bottom_right ); ?>" min="0">
					</p>
				</div>
			</div>

			<div id="wcpb-product-badges-badge-code-expand">
				<p>
					<label><?php esc_html_e( 'Code', 'wcpb-product-badges' ); ?><br>
						<textarea type="text" name="wcpb_product_badges_badge_code_code" placeholder="<?php esc_html_e( 'Add your code here, it is recommended you use a block element for the contents of your badge to ensure it fits to the badge size width.', 'wcpb-product-badges' ); ?>"><?php echo esc_html( $badge_code_code ); ?></textarea>
					</label>
				</p>
				<p>
					<?php
					// translators: %s: wp_kses_post() documentation link
					echo wp_kses_post( sprintf( __( 'Any code entered is output through %s which sanitizes the code for allowed tags.', 'wcpb-product-badges' ), '<a href="' . esc_url( 'https://developer.wordpress.org/reference/functions/wp_kses_post/' ) . '" target="_blank">wp_kses_post()</a>' ) );
					?>
				</p>
			</div>

			<?php

		}

		public function meta_box_product_level_badge_select() {

			global $post;
			$product_id = $post->ID;

			$badges = get_posts(
				array(
					'numberposts'	=> -1,
					'post_type'		=> 'wcpb_product_badge',
					'post_status'	=> 'publish',
					'order'			=> 'DESC',
					'orderby'		=> 'title',
					'fields'		=> 'ids',
				)
			);

			if ( !empty( $badges ) ) {

				?>

				<select name="wcpb_product_badges_meta_box_product_level_badge_select[]" class="wcpb-product-badges-select2-meta-box-product-level-badge-select wcpb-product-badges-select2" multiple>
					<?php
					foreach ( $badges as $badge_id ) {
						$display_products = get_post_meta( $badge_id, '_wcpb_product_badges_display_products', true );
						if ( 'specific' !== $display_products ) {
							continue;
						}
						$badge_specific_products = get_post_meta( $badge_id, '_wcpb_product_badges_display_products_specific_products', true );
						?>
						<option value="<?php echo esc_html( $badge_id ); ?>"<?php echo esc_attr( ( in_array( $product_id, $badge_specific_products ) ? ' selected': '' ) ); ?>><?php echo wp_kses_post( get_the_title( $badge_id ) ); ?></option>
						<?php
					}
					?>
				</select>
				<input type="hidden" name="wcpb_product_badges_meta_box_product_level_badge_select_removals" value="">

				<p class="wcpb-product-badges-meta-box-product-level-badge-select-info">
					<?php
					// translators: %s: badges link
					echo wp_kses_post( sprintf( __( '%s which use the "specific" display option can be selected here.', 'wcpb-product-badges' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=wcpb_product_badge' ) ) . '">' . __( 'Badges', 'wcpb-product-badges' ) . '</a>' ) );
					?>
				</p>

				<?php

				if ( 'no' == get_option( 'wcpb_product_badges_multiple_badges_per_product' ) ) {

					?>

					<p class="wcpb-product-badges-meta-box-product-level-badge-select-info">
						<?php
						echo wp_kses_post(
							sprintf(
								// translators: %s: settings URL
								__( '<a href="%s">Multiple badges per product</a> is disabled, so only one badge will display on this product.', 'wcpb-product-badges' ),
								esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=wcpb-product-badges' ) )
							)
						);
						?>
					</p>

					<?php

				}

			} else {

				?>

				<p>
					<?php esc_html_e( 'No product badges have been created yet.', 'wcpb-product-badges' ); ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wcpb_product_badge' ) ); ?>"><?php esc_html_e( 'Create one now.', 'wcpb-product-badges' ); ?></a>
				</p>

				<?php

			}

		}

		public function meta_box_product_level_badge_select_save( $product_id ) {

			if ( isset( $_POST['woocommerce_meta_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {

				$badges = array(
					'add'		=> isset( $_POST['wcpb_product_badges_meta_box_product_level_badge_select'] ) ? array_map( 'sanitize_text_field', $_POST['wcpb_product_badges_meta_box_product_level_badge_select'] ) : array(),
					'remove'	=> isset( $_POST['wcpb_product_badges_meta_box_product_level_badge_select_removals'] ) ? explode( ',', sanitize_text_field( $_POST['wcpb_product_badges_meta_box_product_level_badge_select_removals'] ) ) : array(),
				);

				foreach ( $badges as $action => $badge_ids ) {

					if ( !empty( $badge_ids ) ) {

						foreach ( $badge_ids as $badge_id ) {

							$badge_specific_products = get_post_meta( $badge_id, '_wcpb_product_badges_display_products_specific_products', true );

							if ( !is_array( $badge_specific_products ) ) {

								$badge_specific_products = array();

							}

							if ( 'add' === $action && !in_array( $product_id, $badge_specific_products ) ) {

								$badge_specific_products[] = $product_id;

							} elseif ( 'remove' === $action && in_array( $product_id, $badge_specific_products ) ) {

								$badge_specific_products = array_diff( $badge_specific_products, array( $product_id ) );

							}

							update_post_meta( $badge_id, '_wcpb_product_badges_display_products_specific_products', $badge_specific_products );

						}

					}

				}

			}

		}

		public function select2_ajax_display_products_specific_categories() {

			$taxonomy = 'product_cat';

			$categories = get_terms(
				array(
					'fields'		=> 'ids',
					'hide_empty'	=> false,
					'order'			=> 'asc',
					'orderby'		=> 'title',
					'taxonomy'		=> $taxonomy,
				)
			);

			$json = [];

			if ( !empty( $categories ) ) {

				foreach ( $categories as $term_id ) {

					$term_names = [];

					foreach ( get_ancestors( $term_id, $taxonomy ) as $ancestor_id ) {

						$term_names[] = get_term( $ancestor_id, $taxonomy )->name;

					}

					$term_names = array_reverse( $term_names );
					$term_names[] = get_term( $term_id, $taxonomy )->name;

					$text = implode( ' > ', $term_names ) . ' ' . esc_html__( '-', 'wcpb-product-badges' ) . ' ' . esc_html__( 'ID:', 'wcpb-product-badges' ) . ' ' . esc_html( $term_id );

					if ( isset( $_GET['search_term'] ) && '' !== $_GET['search_term'] ) {

						if ( stristr( $text, sanitize_text_field( $_GET['search_term'] ) ) ) {

							$json[] = array(
								'id'	=> $term_id,
								'text'	=> $text,
							);

						}

					} else {

						$json[] = array(
							'id'	=> $term_id,
							'text'	=> $text,
						);

					}

				}

				usort( $json, function ( $item1, $item2 ) {
					return $item1['text'] <=> $item2['text'];
				});

			}

			echo wp_json_encode( $json );

			exit;

		}

		public function select2_ajax_display_products_specific_tags() {

			$tags = get_terms(
				array(
					'hide_empty'	=> false,
					'order'			=> 'asc',
					'orderby'		=> 'title',
					'taxonomy'		=> 'product_tag',
				)
			);

			$json = [];

			if ( !empty( $tags ) ) {

				foreach ( $tags as $tag ) {

					$text = wp_kses_post( $tag->name ) . ' ' . esc_html__( '-', 'wcpb-product-badges' ) . ' ' . esc_html__( 'ID:', 'wcpb-product-badges' ) . ' ' . esc_html( $tag->term_id );

					if ( isset( $_GET['search_term'] ) && '' !== $_GET['search_term'] ) {

						if ( stristr( $text, sanitize_text_field( $_GET['search_term'] ) ) ) {

							$json[] = array(
								'id'	=> $tag->term_id,
								'text'	=> $text,
							);

						}

					} else {

						$json[] = array(
							'id'	=> $tag->term_id,
							'text'	=> $text,
						);

					}

				}

			}

			echo wp_json_encode( $json );

			exit;

		}

		public function select2_ajax_display_products_specific_products() {

			$products = get_posts(
				array(
					'fields'			=> 'ids',
					'order'				=> 'asc',
					'orderby'			=> 'title',
					'post_type'			=> 'product',
					'posts_per_page'	=> -1,
				)
			);

			$json = [];

			if ( !empty( $products ) ) {

				foreach ( $products as $product_id ) {

					$text = wp_kses_post( get_the_title( $product_id ) ) . ' ' . esc_html__( '-', 'wcpb-product-badges' ) . ' ' . esc_html__( 'ID:', 'wcpb-product-badges' ) . ' ' . esc_html( $product_id );

					if ( isset( $_GET['search_term'] ) && '' !== $_GET['search_term'] ) {

						if ( stristr( $text, sanitize_text_field( $_GET['search_term'] ) ) ) {

							$json[] = array(
								'id'	=> $product_id,
								'text'	=> $text,
							);

						}

					} else {

						$json[] = array(
							'id'	=> $product_id,
							'text'	=> $text,
						);

					}

				}

			}

			echo wp_json_encode( $json );

			exit;

		}

		public function select2_ajax_display_products_specific_shipping_classes() {

			$shipping_classes = WC()->shipping()->get_shipping_classes();

			$json = [];

			if ( !empty( $shipping_classes ) ) {

				foreach ( $shipping_classes as $shipping_class ) {

					$text = wp_kses_post( $shipping_class->name ) . ' ' . esc_html__( '-', 'wcpb-product-badges' ) . ' ' . esc_html__( 'ID:', 'wcpb-product-badges' ) . ' ' . esc_html( $shipping_class->term_id );

					if ( isset( $_GET['search_term'] ) && '' !== $_GET['search_term'] ) {

						if ( stristr( $text, sanitize_text_field( $_GET['search_term'] ) ) ) {

							$json[] = array(
								'id'	=> $shipping_class->term_id ,
								'text'	=> $text,
							);

						}

					} else {

						$json[] = array(
							'id'	=> $shipping_class->term_id ,
							'text'	=> $text,
						);

					}

				}

			}

			echo wp_json_encode( $json );

			exit;

		}

		public function save_badge( $post_id ) {

			if ( isset( $_POST['wcpb_product_badge_save_nonce'] ) ) {

				if ( wp_verify_nonce( sanitize_key( $_POST['wcpb_product_badge_save_nonce'] ), 'wcpb_product_badge_save' ) ) {

					if ( !empty( $_POST ) ) {

						foreach ( $_POST as $key => $value ) {

							if ( strpos( $key, 'wcpb_product_badges_' ) === 0 ) {

								update_post_meta( $post_id, '_' . $key, $value );

							}

							// If the select2 fields are empty set them to empty (if they have nothing selected they aren't in $_POST so if trying to remove existing items from them they wouldn't get emptied without these conditions), it is set to an empty array as the conditions based off this meta use in_array functions which will throw a warning if not an empty array

							if ( !isset( $_POST['wcpb_product_badges_display_products_specific_categories'] ) ) {

								update_post_meta( $post_id, '_wcpb_product_badges_display_products_specific_categories', array() );

							}

							if ( !isset( $_POST['wcpb_product_badges_display_products_specific_tags'] ) ) {

								update_post_meta( $post_id, '_wcpb_product_badges_display_products_specific_tags', array() );

							}

							if ( !isset( $_POST['wcpb_product_badges_display_products_specific_products'] ) ) {

								update_post_meta( $post_id, '_wcpb_product_badges_display_products_specific_products', array() );

							}

							if ( !isset( $_POST['wcpb_product_badges_display_products_specific_shipping_classes'] ) ) {

								update_post_meta( $post_id, '_wcpb_product_badges_display_products_specific_shipping_classes', array() );

							}



						}

					}

				}

			}

		}

		public function settings_section( $sections ) {

			$sections['wcpb-product-badges'] = __( 'Product badges', 'wcpb-product-badges' );
			return $sections;

		}

		public function settings_fields( $settings, $current_section ) {

			// ID used as field ID and becomes option_name

			if ( 'wcpb-product-badges' == $current_section ) {

				$product_badges_settings[] = array(
					'name'	=> esc_html__( 'Product badges', 'wcpb-product-badges' ),
					'type'	=> 'title',
				);

				$product_badges_settings[] = array(
					'name'			=> esc_html__( 'Compatibility mode product loops position', 'wcpb-product-badges' ),
					'id'			=> 'wcpb_product_badges_compatibility_mode_product_loops_position',
					'type'			=> 'checkbox',
					'desc'			=> esc_html__( 'Enable compatibility mode product loops position', 'wcpb-product-badges' ),
					'desc_tip'		=> esc_html__( 'If you have product badge position and/or product image display issues on product loops it is likely that your theme, plugins/extensions and/or development changes have amended the standard WooCommerce product image display functionality. By default this extension automatically attempts to position badges in product loops via the standard WooCommerce product image display functionality. Alternatively, you can enable this setting which will attempt to position product badges in product loops using an alternative method. When enabled badges may be displayed outside product images in some areas of your website, this is most likely to occur with bottom positioned badges. In this scenario if you wish to keep bottom positioned badges then we recommend applying custom CSS to position as required. Default is disabled.', 'wcpb-product-badges' ),
					'checkboxgroup'	=> 'start',
				);

				$product_badges_settings[] = array(
					'name'			=> esc_html__( 'Compatibility mode product pages', 'wcpb-product-badges' ),
					'id'			=> 'wcpb_product_badges_compatibility_mode_product_pages',
					'type'			=> 'text',
					// translators: %s: example
					'desc'			=> esc_html__( 'If you have product badge and/or product image display issues on product pages it is likely that your theme, plugins/extensions and/or development changes have amended the standard WooCommerce product image/gallery display functionality. By default this extension automatically attempts to display badges in product pages via the standard WooCommerce product image/gallery display functionality. Alternatively, you can populate the above with CSS selectors that contain product images on your product page and badges will attempt to be added to those elements. You may need to use multiple and specifically targeted CSS selectors for products with one image versus products within multiple images. If you are unsure which CSS selectors to use then please contact this extension\'s support for help before populating the above. Set to empty to disable. Default is disabled.', 'wcpb-product-badges' ) . '<br>' . wp_kses_post( sprintf( __( 'Example: %s', 'wcpb-product-badges' ), '<code>.example-single-product-image-wrapper .example-single-product-image, .example-multiple-product-image-wrapper .example-multiple-product-image</code>' ) ),
				);

				$product_badges_settings[] = array(
					'name'			=> esc_html__( 'Multiple badges per product', 'wcpb-product-badges' ),
					'id'			=> 'wcpb_product_badges_multiple_badges_per_product',
					'type'			=> 'checkbox',
					'desc'			=> esc_html__( 'Enable multiple badges per product', 'wcpb-product-badges' ),
					'desc_tip'		=> esc_html__( 'When enabled if more than one badge is assigned to a product then all assigned badges will be displayed instead of one. In this scenario the order attribute used for prioritizing badges for display over others is ignored. This setting may cause clashes between badges. When enabled the standard WooCommerce magnify icon will be hidden to avoid clashes. Additionally your theme, plugins/extensions and/or development changes may have added other icons to product images which you may need to hide using custom CSS. Default is disabled.', 'wcpb-product-badges' ),
					'checkboxgroup'	=> 'start',
				);

				return $product_badges_settings;

			} else {

				return $settings;

			}

		}

		public function badge_image_library_images() {

			// All fonts (see fonts) used are sourced from Google Fonts licensed under the Open Font License, this excludes any fonts already included on an image taken from the source URL shown
			// Parts of the image maybe sourced from openclipart.org (see sources) which is a directory of entirely public domain imagery
			// All images in .svg format with fonts converted to curves
			// All inner array values should A-Z/0-9 lower case with hyphens as spaces only
			// Requires type/color nice value to be defined where filters output

			$images = array();

			$images['000001.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'green',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/189026/snowman',
				),
			);

			$images['000002.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/178125/circle-tag',
				),
			);

			$images['000003.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'green',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/178123/horizontal-tag',
				),
			);

			$images['000004.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'orange',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/178124/vertical-tag',
				),
			);

			$images['000005.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/21143/heart-glossy-two',
				),
			);

			$images['000006.svg'] = array(
				'type'		=> 'easter',
				'color'		=> 'pink',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/191066/blue-bunny',
				),
			);

			$images['000007.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/312118/santa-claus',
				),
			);

			$images['000008.svg'] = array(
				'type'		=> 'halloween',
				'color'		=> 'gray',
				'fonts'		=> array(
					'mountains-of-christmas',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/163945/ghost',
				),
			);

			$images['000009.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'gray',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/34567/tango-input-mouse',
				),
			);

			$images['000010.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000011.svg'] = array(
				'type'		=> 'halloween',
				'color'		=> 'gray',
				'fonts'		=> array(
					'caveat-brush',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/103309/skull',
				),
			);

			$images['000012.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/312063/santa-claus',
				),
			);

			$images['000013.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'yellow',
				'fonts'		=> array(
					'calligraffiti',
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/235645/blonde-cartoon-cupid',
				),
			);

			$images['000014.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/275136/keyboard',
				),
			);

			$images['000015.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'green',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/173812/christmas-ribbon',
				),
			);

			$images['000016.svg'] = array(
				'type'		=> 'easter',
				'color'		=> 'pink',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/192661/pink-rabbit-lapin-rose',
				),
			);

			$images['000017.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/139003/price-tag',
				),
			);

			$images['000018.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'caveat-brush',
				),
				'sources'	=> array(),
			);

			$images['000019.svg'] = array(
				'type'		=> 'halloween',
				'color'		=> 'orange',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/86065/halloween-pumpkin-smile',
				),
			);

			$images['000020.svg'] = array(
				'type'		=> 'easter',
				'color'		=> 'blue',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/240214/easter-egg-7',
				),
			);

			$images['000021.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(),
				'sources'	=> array(
					'https://openclipart.org/detail/319600/grungy-sale-stencil-text',
				),
			);

			$images['000022.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'green',
				'fonts'		=> array(
					'press-start-2p',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/169900/circuit-board',
				),
			);

			$images['000023.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'caveat-brush',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/189339/snowman',
				),
			);

			$images['000024.svg'] = array(
				'type'		=> 'easter',
				'color'		=> 'yellow',
				'fonts'		=> array(),
				'sources'	=> array(
					'https://openclipart.org/detail/298980/happy-easter-greeting-card',
				),
			);

			$images['000025.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/295082/perfect-heart',
				),
			);

			$images['000026.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000027.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'blue',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/22511/hand-cursor',
				),
			);

			$images['000028.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'pink',
				'fonts'		=> array(
					'lobster',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/21617/pink-lace-heart',
				),
			);

			$images['000029.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'lobster',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/166352/snow-man',
				),
			);

			$images['000030.svg'] = array(
				'type'		=> 'halloween',
				'color'		=> 'gray',
				'fonts'		=> array(
					'mountains-of-christmas',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/12031/halloween-pumpkins',
				),
			);

			$images['000031.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'yellow',
				'fonts'		=> array(
					'caveat-brush',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/297220/male-computer-user-1',
				),
			);

			$images['000032.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'caveat-brush',
				),
				'sources'	=> array(),
			);

			$images['000033.svg'] = array(
				'type'		=> 'easter',
				'color'		=> 'yellow',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/216717/3-easter-eggs',
				),
			);

			$images['000034.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'red',
				'fonts'		=> array(
					'caveat-brush',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/304119/heart-9',
				),
			);

			$images['000035.svg'] = array(
				'type'		=> 'halloween',
				'color'		=> 'orange',
				'fonts'		=> array(
					'lato',
					'mountains-of-christmas',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/306877/broom-riding-witch-silhouette',
				),
			);

			$images['000036.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(),
				'sources'	=> array(
					'https://openclipart.org/detail/291736/black-friday-sign',
				),
			);

			$images['000037.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000038.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'pink',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/221719/price-tag',
				),
			);

			$images['000039.svg'] = array(
				'type'		=> 'halloween',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/86689/plain-black-bat',
				),
			);

			$images['000040.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'mountains-of-christmas',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/288849/christmas-tree-silhouette',
				),
			);

			$images['000041.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'red',
				'fonts'		=> array(
					'calligraffiti',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/269956/cupid-silhouette',
				),
			);

			$images['000042.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'green',
				'fonts'		=> array(
					'press-start-2p',
				),
				'sources'	=> array(),
			);

			$images['000043.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'white',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/199524/primary-folder-binary',
				),
			);

			$images['000044.svg'] = array(
				'type'		=> 'halloween',
				'color'		=> 'gray',
				'fonts'		=> array(),
				'sources'	=> array(
					'https://openclipart.org/detail/228164/happy-halloween',
				),
			);

			$images['000045.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/90949/christmas-tree',
				),
			);

			$images['000046.svg'] = array(
				'type'		=> 'halloween',
				'color'		=> 'gray',
				'fonts'		=> array(
					'caveat-brush',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/91135/jack-o-lantern-randy',
				),
			);

			$images['000047.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'yellow',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000048.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'white',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000049.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000050.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'green',
				'fonts'		=> array(
					'mountains-of-christmas',
				),
				'sources'	=> array(),
			);

			$images['000051.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'purple',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000052.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000053.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'orange',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000054.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'green',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000055.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'white',
				'fonts'		=> array(
					'lobster',
				),
				'sources'	=> array(),
			);

			$images['000056.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000057.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'red',
				'fonts'		=> array(
					'nothing-you-could-do',
				),
				'sources'	=> array(),
			);

			$images['000058.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'pink',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000059.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'purple',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000060.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'green',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000061.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'green',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/12591/alarm-clock',
				),
			);

			$images['000062.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'lobster',
				),
				'sources'	=> array(),
			);

			$images['000063.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'white',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000064.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'blue',
				'fonts'		=> array(
					'lobster',
				),
				'sources'	=> array(),
			);

			$images['000065.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000066.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'blue',
				'fonts'		=> array(
					'nothing-you-could-do',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/171043/valentines-day-gift-box',
				),
			);

			$images['000067.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000068.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'pink',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000069.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/237996/package',
				),
			);

			$images['000070.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000071.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'orange',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000072.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/7315/flames',
				),
			);

			$images['000073.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000074.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000075.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000076.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000077.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000078.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000079.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000080.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000081.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lobster',
				),
				'sources'	=> array(),
			);

			$images['000082.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000083.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000084.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000085.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000086.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000087.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000088.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000089.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'green',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000090.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000091.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000092.svg'] = array(
				'type'		=> 'black-friday',
				'color'		=> 'black',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000093.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'black',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000094.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000095.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'pink',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000096.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'blue',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000097.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000098.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'black',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000099.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000100.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'green',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000101.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'pink',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000102.svg'] = array(
				'type'		=> 'cyber-monday',
				'color'		=> 'black',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000103.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000104.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000105.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'mountains-of-christmas',
				),
				'sources'	=> array(),
			);

			$images['000106.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000107.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000108.svg'] = array(
				'type'		=> 'christmas',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000109.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'purple',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000110.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'blue',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000111.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000112.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000113.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'green',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000114.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'pink',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000115.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'blue',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000116.svg'] = array(
				'type'		=> 'valentines',
				'color'		=> 'white',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000117.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'white',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000118.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'purple',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000119.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000120.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000121.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000122.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000123.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000124.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000125.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000126.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000127.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000128.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000129.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000130.svg'] = array(
				'type'		=> 'availability',
				'color'		=> 'red',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000131.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000132.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000133.svg'] = array(
				'type'		=> 'general',
				'color'		=> 'green',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000134.svg'] = array(
				'type'		=> 'fathers-day',
				'color'		=> 'blue',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000135.svg'] = array(
				'type'		=> 'mothers-day',
				'color'		=> 'pink',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(),
			);

			$images['000136.svg'] = array(
				'type'		=> 'mothers-day',
				'color'		=> 'orange',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			$images['000137.svg'] = array(
				'type'		=> 'fathers-day',
				'color'		=> 'green',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(),
			);

			$images['000138.svg'] = array(
				'type'		=> 'mothers-day',
				'color'		=> 'purple',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/264077/flower-80',
				),
			);

			$images['000139.svg'] = array(
				'type'		=> 'fathers-day',
				'color'		=> 'black',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/9618/soccer-ball',
				),
			);

			$images['000140.svg'] = array(
				'type'		=> 'mothers-day',
				'color'		=> 'orange',
				'fonts'		=> array(
					'roboto',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/21274/valentine-orange-hearts',
				),
			);

			$images['000141.svg'] = array(
				'type'		=> 'fathers-day',
				'color'		=> 'black',
				'fonts'		=> array(
					'anton',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/93997/father-day-icon',
				),
			);

			$images['000142.svg'] = array(
				'type'		=> 'mothers-day',
				'color'		=> 'green',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(
					'https://openclipart.org/detail/314748/bouquet-3',
				),
			);

			$images['000143.svg'] = array(
				'type'		=> 'fathers-day',
				'color'		=> 'blue',
				'fonts'		=> array(
					'lato',
				),
				'sources'	=> array(),
			);

			return $images;

		}

	}

}
