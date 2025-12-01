<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VIJAGIF_WOO_FREE_GIFT_Admin_Product
 */
class VIJAGIF_WOO_FREE_GIFT_Admin_Product {
	private $settings;

	public function __construct() {
		$this->settings = VIJAGIF_WOO_FREE_GIFT_DATA::get_instance();
		add_action( 'woocommerce_product_data_panels', array( $this, 'jagif_product_data_panels' ) );
		add_action( 'woocommerce_process_product_meta_jagif-gift', array( $this, 'jagif_save_option_fields' ), 99 );

		add_action( 'woocommerce_process_product_meta_simple', array(
			$this,
			'woocommerce_process_product_meta_simple_variable'
		) );
		add_action( 'woocommerce_process_product_meta_variable', array(
			$this,
			'woocommerce_process_product_meta_simple_variable'
		) );

		add_filter( 'product_type_selector', array( $this, 'jagif_product_type_selector' ) );
		add_filter( 'woocommerce_product_filters', array( $this, 'jagif_product_filters' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'jagif_product_data_tabs' ), 50, 1 );
		add_filter( 'woocommerce_product_class', array( $this, 'jagif_woocommerce_product_class' ), 10, 2 );
		add_filter( 'display_post_states', array( $this, 'jagif_display_post_states' ), 10, 2 );
	}

	public function jagif_display_post_states( $states, $post ) {
		if ( 'product' == get_post_type( $post->ID ) ) {
			if ( ( $_product = wc_get_product( $post->ID ) ) && $_product->is_type( 'jagif-gift' ) ) {
				$states[] = apply_filters( 'jagif_post_states', '<span class="jagif-state">' . esc_html__( 'Gift Pack', 'jagif-woo-free-gift' ) . '</span>', $_product );
			}
		}

		return $states;
	}

	public function jagif_woocommerce_product_class( $classname, $product_type ) {
		if ( $product_type == 'jagif-gift' ) {
			$classname = 'VIJAGIF_FREE_GIFT_Product_Gift';
		}

		return $classname;
	}


	public function jagif_product_type_selector( $types ) {
		$types['jagif-gift'] = esc_html__( 'Gift Pack', 'jagif-woo-free-gift' );

		return $types;
	}

	function jagif_product_filters( $filters ) {
		$filters = str_replace( 'jagif-gift', esc_html__( 'jagif-gift', 'jagif-woo-free-gift' ), $filters );

		return $filters;
	}

	function jagif_product_data_tabs( $tabs ) {
		$tabs['jagif-gift']     = array(
			'label'  => esc_html__( 'Gift Pack', 'jagif-woo-free-gift' ),
			'target' => 'jagif_settings',
			'class'  => array( 'show_if_jagif' ),
		);
		$tabs['jagif-add-gift'] = array(
			'label'  => esc_html__( 'Free Gift', 'jagif-woo-free-gift' ),
			'target' => 'jagif_add_gift',
			'class'  => array( 'show_if_simple', 'show_if_variable' ),
		);

		return $tabs;
	}

	public function jagif_product_data_panels() {
		global $post;
		$post_id      = $post->ID;
		$jagif_gift   = get_post_meta( $post_id, 'jagif-woo_free_gift_gift', true );

		$jagif_display_gift      = ! empty( $jagif_gift ) ? $jagif_gift : [];

		$gift_pack = get_post_meta( $post_id, 'jagif_gift_pack_in_product', true ) ?? '';
		?>
        <!--Add gift pack for product-->
        <div id='jagif_add_gift' class='panel woocommerce_options_panel'>
            <div class="jagif_add_gift_pack_field ">
                <label><?php esc_html_e( 'Choose gift pack: ', 'jagif-woo-free-gift' ); ?></label>
                <div class="item_column add-gift-pack">
                    <select class="jagif-add-gift-pack_id" name="jagif_add_gift_pack_id">
						<?php
						if ( $gift_pack ) {
							$product = wc_get_product( $gift_pack );
							if ( $product ) {
								?>
                                <option data-title="<?php echo esc_html( $product->get_title() ); ?>"
                                        value="<?php echo esc_attr( $gift_pack ); ?>"
                                        selected="selected"><?php echo esc_html( $product->get_title() . ' ( ID: ' . $gift_pack . ' )' ); ?></option>
								<?php
							}
						} ?>
                    </select>
                </div>
                <div class="jagif-btn-action-gift-pack">
                    <button class="jagif-button button btn-create-gift-pack"
                            type="button">
						<?php echo sprintf( '<a href="%s" target="_blank">%s</a>',
							esc_url( 'post-new.php?post_type=product&jagif_type=gp' ),
							esc_html__( 'Create new gift pack', 'jagif-woo-free-gift' ) ) ?>
                    </button>
                </div>
            </div>
        </div>
        <!--Gift pack-->
        <div id='jagif_settings' class='panel woocommerce_options_panel jagif_table show_if_jagif'>
            <div class="jagif-metaboxes-wrapper">

                <div id="jagif-list" class="jagif-list-group vi-ui sortable">
					<?php
					if ( sizeof( $jagif_display_gift ) > 0 ) {
						$count_gift = 0;
						foreach ( $jagif_display_gift as $gift_item ) {
							$archive    = $gift_item['archive'] ?? 1;
							$archive_id = $gift_item['archive_id'] ?? '';
							$product    = wc_get_product( $archive_id );
							if ( $product && $product->is_in_stock() && $product->is_purchasable() ) {
								$item_title   = $product->get_name();
								$product_type = $product->get_type() == 'simple' ? '' : ' ( ' . $product->get_type() . ' )';
							} else {
								$item_title   = '';
								$product_type = '';
							}
							?>
                            <div class="condition_item list-group-item open">
                                <h3>
                                    <a href="#"
                                       class="del_row_product delete"><?php esc_html_e( 'Remove', 'jagif-woo-free-gift' ); ?></a>
                                    <div class="jagif-sort tips sort"
                                         data-tip="<?php esc_attr_e( 'Drag and drop to set gift item order', 'jagif-woo-free-gift' ); ?>"></div>
                                    <strong class="jagif_gift_name"><?php echo esc_html( $item_title . ' ( ID: ' . $archive_id . ' )' . $product_type ); ?></strong>
                                </h3>
                                <div class="jagif-gift-item-content">
                                    <div class="jagif-detail-gift-item product_gift">
                                        <label><?php esc_html_e( 'Select product:', 'jagif-woo-free-gift' ); ?></label>
                                        <div class="item_column option_archive_id">
                                            <select class="jagif-display_gift_archive_id "
                                                    name=jagif-display_gift[<?php echo esc_attr( $count_gift ); ?>][archive_id]">
												<?php
												if ( $item_title ) {
													?>
                                                    <option data-title="<?php echo esc_html( $item_title ); ?>"
                                                            value="<?php echo esc_attr( $archive_id ); ?>"
                                                            selected="selected"><?php echo esc_html( $item_title . ' ( ID: ' . $archive_id . ' )' . $product_type ); ?></option>
													<?php
												} ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="jagif-detail-gift-item product_gift_qty">
                                        <label><?php esc_html_e( 'Quantity:', 'jagif-woo-free-gift' ); ?></label>
                                        <div class="item_column archive">
                                            <input class="jagif-display_gift_archive"
                                                   name="jagif-display_gift[<?php echo esc_attr( $count_gift ); ?>][archive]"
                                                   type="number" min="1" step="1"
                                                   value="<?php echo esc_attr( $archive ) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
							<?php
							$count_gift ++;
						}
						?>
						<?php
					} else {
						?>
                        <div class="condition_item list-group-item open">
                            <h3>
                                <a href="#"
                                   class="del_row_product delete"><?php esc_html_e( 'Remove', 'jagif-woo-free-gift' ); ?></a>
                                <div class="jagif-sort tips sort"
                                     data-tip="<?php esc_attr_e( 'Drag and drop to set gift item order', 'jagif-woo-free-gift' ); ?>"></div>
                                <strong class="jagif_gift_name"></strong>
                            </h3>
                            <div class="jagif-gift-item-content">
                                <div class="jagif-detail-gift-item product_gift">
                                    <label><?php esc_html_e( 'Select product:', 'jagif-woo-free-gift' ); ?></label>
                                    <div class="item_column option_archive_id">
                                        <select class="jagif-display_gift_archive_id"
                                                name=jagif-display_gift[0][archive_id]">
                                        </select>
                                    </div>
                                </div>
                                <div class="jagif-detail-gift-item product_gift_qty">
                                    <label><?php esc_html_e( 'Quantity:', 'jagif-woo-free-gift' ); ?></label>
                                    <div class="item_column archive">
                                        <input class="jagif-display_gift_archive"
                                               name="jagif-display_gift[0][archive]"
                                               type="number" min="1" step="1"
                                               value="1">
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
					}
					?>
                </div>
            </div>
            <div class="jagif-toolbar jagif-toolbar-bot">
                    <span class="jagif-expand-close">
                        <a href="#" class="expand_all"><?php esc_html_e( 'Expand', 'jagif-woo-free-gift' ); ?></a>/
                        <a href="#" class="close_all"><?php esc_html_e( 'Close', 'jagif-woo-free-gift' ); ?></a>
                    </span>
                <button type="button"
                        class="button jagif_add_row_product"><?php esc_html_e( 'Add new gift', 'jagif-woo-free-gift' ); ?></button>
            </div>
        </div>
		<?php
	}

	function jagif_save_option_fields( $post_id ) {
		if ( ! current_user_can( "edit_post", $post_id ) ) {
			return;
		}
		if ( isset( $_REQUEST['_jagif_admin_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_jagif_admin_nonce'] ) ), 'jagif_admin_nonce' ) ) {
			return;
		}
		if ( isset( $_POST['product-type'] ) && ( wc_clean( wp_unslash( $_POST['product-type'] ) ) == 'jagif-gift' ) ) {
			$jagif_display_gift = isset( $_POST['jagif-display_gift'] ) ? wc_clean( wp_unslash( $_POST['jagif-display_gift'] ) ) : array();

			if ( count( $jagif_display_gift ) > 0 ) {
				foreach ( $jagif_display_gift as $key => $gift_item ) {
					if ( ! isset( $gift_item['archive_id'] ) ) {
						unset( $jagif_display_gift[ $key ] );
					}
				}
			}
			$jagif_display_gift = array_values( $jagif_display_gift );
			update_post_meta( $post_id, 'jagif-woo_free_gift_gift', $jagif_display_gift );

			if ( empty( $jagif_display_gift ) ) {
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
			} else {
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'private' ) );
            }
		}
	}

	function woocommerce_process_product_meta_simple_variable( $post_id ) {
		if ( ! current_user_can( "edit_post", $post_id ) ) {
			return;
		}
		if ( isset( $_REQUEST['_jagif_admin_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_jagif_admin_nonce'] ) ), 'jagif_admin_nonce' ) ) {
			return;
		}
		$gift_pack_id = isset( $_POST['jagif_add_gift_pack_id'] ) ? wc_clean( wp_unslash( $_POST['jagif_add_gift_pack_id'] ) ) : '';
		update_post_meta( $post_id, 'jagif_gift_pack_in_product', $gift_pack_id );
	}
}
