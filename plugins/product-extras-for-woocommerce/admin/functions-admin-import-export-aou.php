<?php
/**
 * Functions for importing/exporting Product Add-Ons groups/fields from one product to another product/global group and vice versa
 * @since 3.23.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue import/export script used on the admin side
 * @since 3.23.0
 */
function pewc_enqueue_import_export_aou_script() {
	// 3.24.3, added this check so that script is only loaded if pewc-admin-script has been enqueued. Fixes an error that appears in Query Monitor
	if ( ! wp_script_is( 'pewc-admin-script', 'enqueued' ) || wp_script_is( 'pewc-admin-import-export-aou-script', 'enqueued' ) ) {
		return;
	}
	$version = defined( 'PEWC_SCRIPT_DEBUG' ) && PEWC_SCRIPT_DEBUG ? time() : PEWC_PLUGIN_VERSION;
	wp_register_script( 'pewc-admin-import-export-aou-script', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/' . 'admin-import-export-aou.js', array( 'pewc-admin-script' ), $version, true );
	wp_enqueue_script( 'pewc-admin-import-export-aou-script' );
}
add_action( 'admin_enqueue_scripts', 'pewc_enqueue_import_export_aou_script', 100 );

/**
 * Add import/export buttons
 * @since 3.23.0
 */
function pewc_add_import_export_aou_buttons( $groups, $post_id ) {

	wp_nonce_field( 'pewc_import_export', 'pewc_import_export' );
	echo '<div class="options_group">';
	printf(
		'<h2><strong>%s</strong></h2>
		<p>
			<a href="#" class="button pewc-import-aou-groups">%s</a> 
			<a href="#" class="button pewc-export-aou-groups">%s</a>
			<a href="#" class="button pewc-export-aou-selected-group">%s</a>
			<a href="#" class="button pewc-export-aou-cancel">%s</a>
		</p>',
		__( 'Import/Export Add-Ons', 'pewc' ),
		__( 'Import Groups', 'pewc' ),
		__( 'Export Groups', 'pewc' ),
		__( 'Export Selected Group(s)', 'pewc' ),
		__( 'Cancel', 'pewc' )
	);
	echo '</div>';

}
add_action( 'pewc_end_tab_options', 'pewc_add_import_export_aou_buttons', 10, 2 );

/**
 * Output container for the new import/export function
 * @since 3.23.0
 */
function pewc_add_import_export_aou_container() {
	global $post;

	if ( empty( $post->ID ) ) {
		return;
	}

	$product_id = $post->ID;
	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		return;
	}

	wp_nonce_field( 'pewc_import_export_nonce', 'pewc_import_export_nonce' ); ?>

	<div id="pewc_import_export_aou_container" data-pewc-admin-url="<?php echo esc_attr( admin_url() . 'post.php?action=edit&post=' ) ?>" data-product-id="<?php echo $product_id; ?>">
		<div class="pewc_import_export_aou_inner">
			<div id="pewc_export_aou_fields_container"></div>
			<div id="pewc_import_aou_fields_container"></div>

			<div id="pewc_import_aou_groups_container" class="containers">
				<h2><?php _e( 'Import Add-On Groups', 'pewc' ); ?></h2>
				<p class="pewc-import-aou-groups-settings">
					<?php _e( 'Choose whether to import groups from one or more products or from existing global groups. You\'ll be able to select which groups to import. All fields within the groups will also be imported.', 'pewc' ); ?>
				</p>
				<p>
					<select id="pewc_import_groups_source">
						<option value="product"><?php _e( 'Product(s)', 'pewc' ); ?></option>
						<option value="global"><?php _e( 'Global', 'pewc' ); ?></option>
					</select> 
				</p>
				<p>
					<span id="pewc_import_groups_from_products" class="pewc-import-groups-sources">
						<select multiple="multiple" class="wc-product-search" id="pewc_import_groups_products_list" data-action="woocommerce_json_search_products" data-placeholder="Search for product(s)" data-exclude="<?php echo esc_attr( $product_id ); ?>"></select>
					</span>
				</p>
				<div id="pewc_import_aou_groups_list"></div>
			</div>

			<div id="pewc_export_aou_groups_container" class="containers">
				<h2><?php _e( 'Export Groups', 'pewc' ); ?></h2>
				<p><strong><?php _e( 'Groups: ', 'pewc' ); ?></strong><span id="pewc_export_groups_list"></span></p>
				
				<p class="pewc-export-aou-groups-settings"><?php _e( 'Choose where to export these groups to - either to specific products or to global groups. All fields within your selected groups will also be exported.', 'pewc' ); ?></p>
				<p>
					<select id="pewc_export_groups_destination">
						<option value="product"><?php _e( 'Product(s)', 'pewc' ); ?></option>
						<option value="global"><?php _e( 'Global', 'pewc' ); ?></option>
					</select>
				</p>
				<p>
					<span id="pewc_export_groups_to_products" class="pewc-export-groups-destinations">
						<select multiple="multiple" class="wc-product-search" id="pewc_export_groups_products_list" data-action="woocommerce_json_search_products" data-placeholder="Search for product(s)" data-exclude="<?php echo esc_attr( $product_id ); ?>"></select>
					</span>
				</p>
			</div>

			<p class="pewc-import-export-aou-buttons">
				<button id="pewc_import_export_aou_import" type="button" class="button-primary"><?php _e( 'Import', 'pewc' ); ?></button> 
				<button id="pewc_import_export_aou_export" type="button" class="button-primary"><?php _e( 'Export', 'pewc' ); ?></button> 
				<button id="pewc_import_export_aou_cancel" type="button" class="button"><?php _e( 'Cancel', 'pewc' ); ?></button>
				<button id="pewc_import_export_aou_close" type="button" class="button"><?php _e( 'Close', 'pewc' ); ?></button>
			</p>
			<p class="pewc-import-export-aou-exporting"><?php _e( 'Exporting...', 'pewc' ); ?></p>
			<p class="pewc-import-export-aou-importing"><?php _e( 'Importing...', 'pewc' ); ?></p>
			<p class="pewc-import-export-aou-view-global"><?php
			$enable_groups_as_post_types = pewc_enable_groups_as_post_types();
			if ( $enable_groups_as_post_types ) {
				$globals_url = admin_url() . 'edit.php?post_type=pewc_group';
				$globals_title = __( 'Go to Global Groups', 'pewc' );
			} else {
				$globals_url = admin_url() . 'admin.php?page=global';
				$globals_title = __( 'Go to Global Add-Ons', 'pewc' );
			}
			?><a href="<?php echo $globals_url; ?>" target="_blank"><?php echo $globals_title; ?></a></p>
			<p class="pewc-import-export-aou-view-products"><?php _e( 'Go to', 'pewc' ); ?>: <span class="product-list"></span></p>
			<div class="pewc-loading"><span class="spinner"></span></div>
		</div>
	</div>

	<?php

}
add_action( 'admin_footer', 'pewc_add_import_export_aou_container' );

/**
 * Load groups from sources (either from products or global)
 * @since 3.23.0
 */
function pewc_import_aou_load_groups() {

	if( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'pewc_import_export_nonce' ) ) {
		wp_send_json_error( 'nonce_fail' );
		exit;
	}

	$groups_to_load = $_POST['groups_to_load'];

	if ( ! is_array( $groups_to_load ) || empty( $groups_to_load ) ) {
		wp_send_json_error( 'no_ids' );
		exit;
	}

	// get groups
	$all_groups = array();
	foreach ( $groups_to_load as $parent_id ) {
		if ( $parent_id > 0 ) {
			// get the groups from this product
			$groups = pewc_get_group_order( $parent_id );
		} else {
			// get global groups
			$groups = get_option( 'pewc_global_group_order', '' );
		}
		if ( ! empty( $groups ) ) {
			$groups_array = explode( ',', $groups );
			foreach ( $groups_array as $group_id ) {
				$all_groups[$group_id] = array(
					'parent_id' => $parent_id,
					'group_title' => pewc_get_group_title( $group_id, array(), true ),
				);
				if ( $parent_id > 0 ) {
					// get product details
					$product = wc_get_product( $parent_id );
					if ( $product ) {
						$all_groups[$group_id]['product_title'] = $product->get_name();
					}
				}
			}
		}
	}

	if ( ! empty( $all_groups ) ) {
		$return = array(
			'data' => json_encode( $all_groups )
		);
	} else {
		$enable_groups_as_post_types = pewc_enable_groups_as_post_types();
		if ( $enable_groups_as_post_types ) {
			$globals_title = __( 'Global Groups', 'pewc' );
		} else {
			$globals_title = __( 'Global Add-Ons', 'pewc' );
		}
		$return = array(
			'data' => '',
			// 'message' => __( 'No ' . $globals_title.' found', 'pewc' ),
			'message' => __( 'No groups found in your selection. Please try again.', 'pewc' ),
		);
	}
	wp_send_json( $return );
	exit;

}
add_action( 'wp_ajax_pewc_import_aou_load_groups', 'pewc_import_aou_load_groups' );

/**
 * Process import or export of groups to product(s) or global
 * @since 3.23.0
 */
function pewc_import_export_aou_process() {

	if( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'pewc_import_export_nonce' ) ) {
		wp_send_json_error( 'nonce_fail' );
		exit;
	}

	$groups_to_process = $_POST['groups_to_process'];
	$destination_parent = $_POST['destination_parent'];

	if ( ! is_array( $groups_to_process ) || ! is_array( $destination_parent ) || empty( $groups_to_process ) || empty( $destination_parent ) ) {
		wp_send_json_error( 'no_ids' );
		exit;
	}

	foreach ( $destination_parent as $parent_id ) {
		// $duplicate: false, $product: false, $overwrite: false, $is_assigned: true, $groups_to_process, $destination_parent
		pewc_duplicate_groups_and_fields( false, false, false, true, $groups_to_process, $parent_id );
		if ( $parent_id > 0 ) {
			delete_transient( 'pewc_extra_fields_' . $parent_id );
		}
	}

	wp_send_json( array(
		'data'	=> json_encode( $groups_to_process )
	) );
	exit;

}
add_action( 'wp_ajax_pewc_import_export_aou_process', 'pewc_import_export_aou_process' );

/**
 * Register bulk actions on the Global Groups page for import/export
 * @since 3.23.0
 */
function pewc_register_bulk_actions( $bulk_actions ) {

	$new_actions = array();
	$i = 0;
	$rows = count( $bulk_actions );
	foreach( $bulk_actions as $key => $action ) {
		if ( $rows-1 === $i ) {
			// we're at the last element, add our custom action first
			$new_actions['pewc_export'] = __( 'Export', 'pewc' );
		}
		$new_actions[$key] = $action;
		$i++;
	}

	return $new_actions;

}
add_filter( 'bulk_actions-edit-pewc_group', 'pewc_register_bulk_actions', 10, 1 );

/**
 * Handle export bulk actions. Import isn't needed in Bulk Actions because we don't have to select a group to initiate the Import.
 * @since 3.23.0
 */
function pewc_handle_bulk_actions( $redirect_to, $action, $post_ids ) {

	if ( 'pewc_export' === $action ) {
		$redirect_to = admin_url() . 'admin.php?page=pewc-export-global-groups&pewc_global_ids=' . implode( ',', $post_ids );;
	}
	return $redirect_to;

}
add_filter( 'handle_bulk_actions-edit-pewc_group', 'pewc_handle_bulk_actions', 10, 3 );

/**
 * Add the Import button on the Global Groups page
 * @since 3.23.0
 */
function pewc_import_button_global_groups( $position ) {

	if ( 'pewc_group' === get_current_screen()->post_type ) {
		echo '<a class="button" href="' . admin_url() . 'admin.php?page=pewc-import-global-groups' . '">' . __( 'Import Groups', 'pewc' ) . '</a>';
	}

}
add_action( 'manage_posts_extra_tablenav', 'pewc_import_button_global_groups', 10, 1 );

/**
 * Register the Import/Export pages for Global Groups
 * @since 3.23.0
 */
function pewc_register_import_export_aou_pages() {
	add_submenu_page(
		'pewc_home',
		__( 'Import Groups', 'pewc' ),
		__( 'Import Groups', 'pewc' ),
		apply_filters( 'pewc_import_export_aou_capability', 'manage_woocommerce' ),
		'pewc-import-global-groups',
		'pewc_import_global_groups_callback'
	);

	add_submenu_page(
		'pewc_home',
		__( 'Export Groups', 'pewc' ),
		__( 'Export Groups', 'pewc' ),
		apply_filters( 'pewc_import_export_aou_capability', 'manage_options' ),
		'pewc-export-global-groups',
		'pewc_export_global_groups_callback'
	);
}
add_action( 'admin_menu', 'pewc_register_import_export_aou_pages', 199 );

/**
 * We remove the menus so that the Import/Export pages don't appear under the AOU menu in the admin, but the AOU menu remains open when we're on the Import/Export page
 * @since 3.23.0
 */
function pewc_remove_import_export_aou_menus() {
	remove_submenu_page( 'pewc_home', 'pewc-import-global-groups' );  // 'parent-slug', 'subpage-slug'
	remove_submenu_page( 'pewc_home', 'pewc-export-global-groups' );  // 'parent-slug', 'subpage-slug'
}
add_action( 'admin_head', 'pewc_remove_import_export_aou_menus' );

/**
 * Displays the Import Groups page in the admin
 * @since 3.23.0
 */
function pewc_import_global_groups_callback() {

	wp_nonce_field( 'pewc_import_export_nonce', 'pewc_import_export_nonce' );
	?>
	<div class="wrap pewc-import-global-groups" data-pewc-global-groups-url="<?php echo admin_url() . 'edit.php?post_type=pewc_group'; ?>">
		<h1><?php echo __( 'Import Groups', 'pewc'); ?></h1>
		<p><a href="<?php echo 'edit.php?post_type=pewc_group'; ?>">&lt;&lt; <?php _e( 'Go back to Global Groups', 'pewc' ); ?></a></p>
		<p class="pewc-import-aou-groups-settings">Import from products: 
			<span id="pewc_import_groups_from_products" class="pewc-import-groups-sources">
				<select multiple="multiple" class="wc-product-search" id="pewc_import_groups_products_list" data-action="woocommerce_json_search_products" data-placeholder="Search for product(s)" style="width: auto !important; min-width:300px;"></select>
			</span>
		</p>
		<div id="pewc_import_aou_groups_list"></div>
		<div class="pewc-loading"><span class="spinner"></span></div>
		<p class="pewc-import-export-aou-importing"><?php _e( 'Importing...', 'pewc' ); ?></p>
		<p class="pewc-import-export-aou-buttons">
			<button id="pewc_import_export_aou_import" type="button" class="button-primary"><?php _e( 'Import', 'pewc' ); ?></button> 
		</p>
	</div>
	<?php

}

/**
 * Displays the Export Groups page in the admin
 * @since 3.23.0
 */
function pewc_export_global_groups_callback() {

	$global_groups = array();
	if ( ! empty( $_GET['pewc_global_ids'] ) ) {
		// validate
		$pewc_global_ids = explode( ',', $_GET['pewc_global_ids'] );
		foreach ( $pewc_global_ids as $global_id ) {
			$global_id = (int) $global_id;
			$group_post = get_post( $global_id );
			if ( 'pewc_group' === $group_post->post_type && 0 === $group_post->post_parent ) {
				// valid, add to array
				$global_groups[] = $global_id;
			}
		}
	}
	wp_nonce_field( 'pewc_import_export_nonce', 'pewc_import_export_nonce' );
	?>
	<div class="wrap pewc-export-global-groups" data-pewc-global-groups-export="[<?php echo implode( ',', $global_groups ); ?>]" data-pewc-edit-product-url="<?php echo esc_attr( admin_url() . 'post.php?action=edit&post=' ); ?>">
		<h1><?php echo __( 'Export Groups', 'pewc'); ?></h1>
		<p><a href="<?php echo 'edit.php?post_type=pewc_group'; ?>">&lt;&lt; <?php _e( 'Go back to Global Groups', 'pewc' ); ?></a></p>
		<?php
		if ( empty( $global_groups ) ) {
			?>
			<p><?php _e( 'No Global Groups selected for export.', 'pewc' ); ?></p>
			<?php
		} else {
			?>
			<p><strong>Groups: </strong><?php echo implode( ',', $global_groups ); ?></p>
			<p class="pewc-export-aou-groups-settings"><?php _e( 'Export to products:', 'pewc' ); ?></p>
			<p>
				<span id="pewc_export_groups_to_products" class="pewc-export-groups-destinations">
					<select multiple="multiple" class="wc-product-search" id="pewc_export_groups_products_list" data-action="woocommerce_json_search_products" data-placeholder="Search for product(s)" style="width: auto !important; min-width:300px;"></select>
				</span>
			</p>
			<p class="pewc-import-export-aou-exporting"><?php _e( 'Exporting...', 'pewc' ); ?></p>
			<p class="pewc-import-export-aou-buttons">
				<button id="pewc_import_export_aou_export" type="button" class="button-primary"><?php _e( 'Export', 'pewc' ); ?></button> 
			</p>
			<p class="pewc-import-export-aou-view-products"><?php _e( 'Go to', 'pewc' ); ?>: <span class="product-list"></span></p>
			<div class="pewc-loading"><span class="spinner"></span></div>
			<?php
		}
		?>
	</div>
	<?php

}
