<?php
if ( ! function_exists( 'vi_include_folder' ) ) {
	function vi_include_folder( $path, $prefix = '', $ext = array( 'php' ) ) {

		/*Include all files in payment folder*/
		if ( ! is_array( $ext ) ) {
			$ext = explode( ',', $ext );
			$ext = array_map( 'trim', $ext );
		}
		$sfiles = scandir( $path );
		foreach ( $sfiles as $sfile ) {
			if ( $sfile != '.' && $sfile != '..' ) {
				if ( is_file( $path . "/" . $sfile ) ) {
					$ext_file  = pathinfo( $path . "/" . $sfile );
					$file_name = $ext_file['filename'];
					if ( $ext_file['extension'] ) {
						if ( in_array( $ext_file['extension'], $ext ) ) {
							$class = preg_replace( '/\W/i', '_', $prefix . ucfirst( $file_name ) );

							if ( ! class_exists( $class ) ) {
								require_once $path . $sfile;
								if ( class_exists( $class ) ) {
									new $class;
								}
							}
						}
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'jagif_create_custom_product_type' ) ):
	function jagif_create_custom_product_type() {
		class VIJAGIF_FREE_GIFT_Product_Gift extends WC_Product {
			protected $items = null;

			public function __construct( $product ) {
				$this->product_type = 'jagif-gift';
				parent::__construct( $product );
			}
		}
	}
endif;

if ( ! class_exists( 'VIJAGIF_WOO_FREE_GIFT_Function' ) ) {
	class VIJAGIF_WOO_FREE_GIFT_Function {
		public $css_check, $settings, $helper;
		protected static $instance = null;


		public function __construct() {
			$this->css_check = 0;
			$this->settings  = VIJAGIF_WOO_FREE_GIFT_DATA::get_instance();
			$this->helper    = VIJAGIF_HELPER::get_instance();
		}

		public static function get_instance( $new = false ) {
			if ( $new || null === self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		public function check_assign_gift( $assign_id = '', $quantity = 1, $cart_data = '' ) {
			$search_arr_gift   = array();
			$cart_product_ids  = array();
			$cart_category_ids = array();
			$single_gift       = array();

			if ( $assign_id === '' ) {
				return array();
			}
			$gift_in_product = get_post_meta( $assign_id, 'jagif_gift_pack_in_product', true );
			$gift_product    = wc_get_product( $assign_id );

			$product_cat_ids   = wc_get_product_term_ids( $assign_id, 'product_cat' );
			$display_condition = $this->helper->jagif_get_display_conditions() ?? [];

			if ( ! empty( $gift_in_product ) && wc_get_product( $gift_in_product )->is_in_stock() ) {
				$single_gift_block = $this->get_gift_item( array( $gift_in_product ) );
				if ( ! empty( $single_gift_block ) && is_array( $single_gift_block ) ) {
					$single_gift = array(
						'rule_id'       => 'single',
						'gift_id'       => array(
							$single_gift_block[0]['pack_id']
						),
						'is_apply'      => 1,
						'already_count' => 0,
					);
				}
			}

			global $woocommerce;

			$cart_items = $woocommerce->cart->get_cart();

			$cart_gifted = array();
			$cart_rules  = array();
			$cart_packs  = array();
			foreach ( $cart_items as $cart_item ) {
				if ( ! isset( $cart_item['jagif_rule_id'] ) && ! isset( $cart_item['jagif_pack_id'] ) ) {
					$current_id         = $cart_item['product_id'] ?? '';
					$cart_product_ids[] = $current_id;
					$cart_category_ids  = array_merge( wc_get_product_term_ids( $current_id, 'product_cat' ), $cart_category_ids );
				}
				if ( isset( $cart_item['jagif_rule_id'] ) ) {
					$cart_rules[] = $cart_item['jagif_rule_id'];
				}
				if ( isset( $cart_item['jagif_pack_id'] ) ) {
					$cart_packs[] = $cart_item['jagif_pack_id'];
				}

				//check gift rule item already in cart - return array rule id
				if ( isset( $cart_item['jagif_pack_id'] ) && $cart_item['jagif_pack_id'] ) {
					$cart_gifted[] = $cart_item['jagif_pack_id'];
				}

				if ( isset( $cart_item['jagif_pack_id'] ) && isset( $cart_item['jagif_rule_id'] ) ) {
					$cart_item_pack_data = get_post_meta( $cart_item['jagif_pack_id'], 'jagif-woo_free_gift_gift', true );
					if ( empty( $cart_packs_data ) ) {
						$cart_pack_data['pack_id']    = $cart_item['jagif_pack_id'];
						$cart_pack_data['rule_id']    = $cart_item['jagif_rule_id'];
						$cart_pack_data['product_id'] = $cart_item['jagif_parent_id'];
						$cart_pack_data['pack_qty']   = 1;
						if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
							if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
							     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
								$cart_pack_data['pack_qty'] = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
							}
						}
						$cart_packs_data[] = $cart_pack_data;
					} else {
						$isset_pack_data = false;
						foreach ( $cart_packs_data as $cart_pack_key => $cart_pack_value ) {
							if ( $cart_item['jagif_pack_id'] == $cart_pack_value['pack_id']
							     && $cart_item['jagif_rule_id'] == $cart_pack_value['rule_id']
							     && $cart_item['jagif_parent_id'] == $cart_pack_value['product_id'] ) {
								$add_qty = 1;
								if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
									if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
									     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
										$add_qty = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
									}
								}
								$cart_packs_data[ $cart_pack_key ]['pack_qty'] += $add_qty;
								$isset_pack_data                               = true;
							}
						}
						if ( ! $isset_pack_data ) {
							$cart_pack_data['pack_id']    = $cart_item['jagif_pack_id'];
							$cart_pack_data['rule_id']    = $cart_item['jagif_rule_id'];
							$cart_pack_data['product_id'] = $cart_item['jagif_parent_id'];
							$cart_pack_data['pack_qty']   = 1;
							if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
								if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
								     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
									$cart_pack_data['pack_qty'] = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
								}
							}
							$cart_packs_data[] = $cart_pack_data;
						}
					}
				}
			}
			$cart_product_ids  = array_unique( $cart_product_ids );
			$cart_category_ids = array_unique( $cart_category_ids );
			$cart_gifted       = array_unique( $cart_gifted );
			$cart_packs        = array_unique( $cart_packs );
			$cart_rules        = array_unique( $cart_rules );
			$cart_gift_pack    = [];
			$override_array    = array();
			$single_rule_array = array();

			if ( $this->settings->get_params( 'override_type' ) == 'all' ) {
				return array();
			}
			// validate gift qty for cart pack
			if ( ! empty( $cart_packs_data ) ) {
				foreach ( $cart_packs_data as $p_key => $p_val ) {
					$p_items = get_post_meta( $p_val['pack_id'], 'jagif-woo_free_gift_gift', true );
					$p_count = 1;
					if ( $p_items && is_array( $p_items ) ) {
						$p_count = count( $p_items );
					}
					$cart_packs_data[ $p_key ]['pack_qty'] = (int) ( (int) $cart_packs_data[ $p_key ]['pack_qty'] / $p_count );
				}
			}
			// check qty of single rule in cart
			if ( ! empty( $cart_packs_data ) && ! empty( $single_gift ) && isset( $gift_in_product ) && ! empty( $assign_id ) ) {
				foreach ( $cart_packs_data as $s_val ) {
					if ( 'single' == $s_val['rule_id'] && $gift_in_product == $s_val['pack_id'] && $assign_id == $s_val['product_id'] ) {
						$single_gift = array();
					}
				}
			}

			if ( $this->settings->get_params( 'override_type' ) == 'part' && ! empty( $single_gift ) ) {
				return $this->get_gift_item( array( $gift_in_product ) );
			}
			if ( ! isset( $display_condition ) && ! is_array( $display_condition ) ) {
				if ( ! empty( $single_gift ) ) {
					return array( $single_gift );
				}

				return [];
			}

			$find_matching_pack = [];
			foreach ( $display_condition as $key => $value ) {
				if ( in_array( $value['rule_id'], $cart_rules ) || ! empty( array_intersect( $value['gift_id'], $cart_packs ) ) ) {
					continue;
				}
				$cond                  = [];
				$include_list_product  = $value['in_product'] ? $value['in_product'] : [];
				$include_list_category = $value['in_category'] ? $value['in_category'] : [];
				$exclude_list_product  = $value['ex_product'] ? $value['ex_product'] : [];
				$exclude_list_category = $value['ex_category'] ? $value['ex_category'] : [];

				$cond[] = ( ! is_array( $include_list_product ) && $include_list_product == 'all' ) ||
				          in_array( $assign_id, $include_list_product ) || empty( $include_list_product ) ||
				          ! empty( array_intersect( $cart_product_ids, $include_list_product ) );
				$cond[] = ! empty( array_intersect( $product_cat_ids, $include_list_category ) ) || empty( $include_list_category ) ||
				          ! empty( array_intersect( $cart_category_ids, $include_list_category ) );

				$cond[] = ( ! is_array( $exclude_list_product ) && $exclude_list_product != 'all' ) ||
				          ( ! in_array( $assign_id, $exclude_list_product ) && empty( array_intersect( $cart_product_ids, $exclude_list_product ) ) ) ||
				          empty( $exclude_list_product );
				$cond[] = ( empty( array_intersect( $product_cat_ids, $exclude_list_category ) ) &&
				            empty( array_intersect( $cart_category_ids, $exclude_list_category ) ) ) ||
				          empty( $exclude_list_category );

				if ( ! in_array( false, $cond ) ) {
					$search_arr_gift = array(
						'rule_id' => $value['rule_id'],
						'gift_id' => $value['gift_id'],
					);
					if ( $value['override_gift'] == 1 ) {
						$override_array[] = array(
							'override' => $value['override_gift'],
							'order'    => $value['order'] ? $value['order'] : 0,
							'rule_id'  => $value['rule_id'],
						);
					}
				} else {
					continue;
				}
				$find_matching_pack[] = $search_arr_gift;
			}
			$find_matching_pack = self::check_override_rules( '', $find_matching_pack, $override_array, array(), $single_rule_array );
			if ( isset( $single_gift ) && ! empty( $single_gift ) ) {
				if ( $this->settings->get_params( 'override_type' ) != 'all' ) {
					array_unshift( $find_matching_pack, $single_gift );
				} else {
					if ( empty( $find_matching_pack ) ) {
						$find_matching_pack[] = $single_gift;
					}
				}
			}
			$output = isset( $find_matching_pack[0]['gift_id'] ) ? $find_matching_pack[0]['gift_id'] : array();

			return $this->get_gift_item( $output );
		}

		public function get_default_gift( $assign_id = '', $quantity = 1, $cart_item_data = '' ) {
			$search_arr_gift   = array();
			$cart_product_ids  = array();
			$cart_category_ids = array();
			$single_gift       = array();

			if ( $assign_id === '' ) {
				return array();
			}
			$gift_in_product = get_post_meta( $assign_id, 'jagif_gift_pack_in_product', true );
			$gift_product    = wc_get_product( $assign_id );

			$product_cat_ids   = wc_get_product_term_ids( $assign_id, 'product_cat' );
			$display_condition = $this->helper->jagif_get_display_conditions() ?? [];

			if ( ! empty( $gift_in_product ) && wc_get_product( $gift_in_product )->is_in_stock() ) {
				$single_gift_block = $this->get_gift_item( array( $gift_in_product ) );
				$single_gift       = array(
					'rule_id'       => 'single',
					'gift_id'       => array(
						$single_gift_block[0]['pack_id'] => $single_gift_block
					),
					'is_apply'      => 1,
					'already_count' => 0,
				);
			}

			global $woocommerce;
			if ( ! $woocommerce ) {
				return [];
			}
			$cart_items = $woocommerce->cart->get_cart();

			$cart_gifted = array();
			$cart_rules  = array();
			$cart_packs  = array();
			foreach ( $cart_items as $cart_item ) {

				if ( ! isset( $cart_item['jagif_rule_id'] ) && ! isset( $cart_item['jagif_pack_id'] ) ) {
					$current_id         = $cart_item['product_id'];
					$cart_product_ids[] = $current_id;
					$cart_category_ids  = array_merge( wc_get_product_term_ids( $current_id, 'product_cat' ), $cart_category_ids );
				}
				if ( isset( $cart_item['jagif_rule_id'] ) ) {
					$cart_rules[] = $cart_item['jagif_rule_id'];
				}
				if ( isset( $cart_item['jagif_pack_id'] ) ) {
					$cart_packs[] = $cart_item['jagif_pack_id'];
				}

				if ( isset( $cart_item['jagif_pack_id'] ) && isset( $cart_item['jagif_rule_id'] ) ) {
					$cart_item_pack_data = get_post_meta( $cart_item['jagif_pack_id'], 'jagif-woo_free_gift_gift', true );
					if ( empty( $cart_packs_data ) ) {
						$cart_pack_data['pack_id']    = $cart_item['jagif_pack_id'];
						$cart_pack_data['rule_id']    = $cart_item['jagif_rule_id'];
						$cart_pack_data['product_id'] = $cart_item['jagif_parent_id'];
						$cart_pack_data['pack_qty']   = 1;
						if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
							if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
							     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
								$cart_pack_data['pack_qty'] = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
							}
						}
						$cart_packs_data[] = $cart_pack_data;
					} else {
						$isset_pack_data = false;
						foreach ( $cart_packs_data as $cart_pack_key => $cart_pack_value ) {
							if ( $cart_item['jagif_pack_id'] == $cart_pack_value['pack_id']
							     && $cart_item['jagif_rule_id'] == $cart_pack_value['rule_id']
							     && $cart_item['jagif_parent_id'] == $cart_pack_value['product_id'] ) {
								$add_qty = 1;
								if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
									if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
									     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
										$add_qty = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
									}
								}
								$cart_packs_data[ $cart_pack_key ]['pack_qty'] += $add_qty;
								$isset_pack_data                               = true;
							}
						}
						if ( ! $isset_pack_data ) {
							$cart_pack_data['pack_id']    = $cart_item['jagif_pack_id'];
							$cart_pack_data['rule_id']    = $cart_item['jagif_rule_id'];
							$cart_pack_data['product_id'] = $cart_item['jagif_parent_id'];
							$cart_pack_data['pack_qty']   = 1;
							if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
								if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
								     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
									$cart_pack_data['pack_qty'] = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
								}
							}
							$cart_packs_data[] = $cart_pack_data;
						}
					}
				}

				//check gift rule item already in cart - return array rule id
				if ( isset( $cart_item['jagif_pack_id'] ) && $cart_item['jagif_pack_id'] ) {
					$cart_gifted[] = $cart_item['jagif_pack_id'];
				}
			}
			$cart_product_ids  = array_unique( $cart_product_ids );
			$cart_category_ids = array_unique( $cart_category_ids );
			$cart_gifted       = array_unique( $cart_gifted );
			$cart_packs        = array_unique( $cart_packs );
			$cart_rules        = array_unique( $cart_rules );
			$cart_gift_pack    = [];
			$override_array    = array();
			$single_rule_array = array();

			// validate gift qty for cart pack
			if ( ! empty( $cart_packs_data ) ) {
				foreach ( $cart_packs_data as $p_key => $p_val ) {
					$p_items = get_post_meta( $p_val['pack_id'], 'jagif-woo_free_gift_gift', true );
					$p_count = 1;
					if ( $p_items && is_array( $p_items ) ) {
						$p_count = count( $p_items );
					}
					$cart_packs_data[ $p_key ]['pack_qty'] = (int) ( (int) $cart_packs_data[ $p_key ]['pack_qty'] / $p_count );
				}
			}

			if ( $this->settings->get_params( 'override_type' ) == 'part' && ! empty( $single_gift ) ) {
				return array( $single_gift );
			}
			if ( ! isset( $display_condition ) && ! is_array( $display_condition ) ) {
				if ( ! empty( $single_gift ) ) {
					return array( $single_gift );
				}

				return [];
			}
			$find_matching_pack = [];
			foreach ( $display_condition as $key => $value ) {
				if ( in_array( $value['rule_id'], $cart_rules ) || ! empty( array_intersect( $value['gift_id'], $cart_packs ) ) ) {
					continue;
				}
				$cond                  = [];
				$include_list_product  = $value['in_product'] ? $value['in_product'] : [];
				$include_list_category = $value['in_category'] ? $value['in_category'] : [];
				$exclude_list_product  = $value['ex_product'] ? $value['ex_product'] : [];
				$exclude_list_category = $value['ex_category'] ? $value['ex_category'] : [];

				$cond[] = ( ! is_array( $include_list_product ) && $include_list_product == 'all' ) ||
				          in_array( $assign_id, $include_list_product ) || empty( $include_list_product ) ||
				          ! empty( array_intersect( $cart_product_ids, $include_list_product ) );
				$cond[] = ! empty( array_intersect( $product_cat_ids, $include_list_category ) ) || empty( $include_list_category ) ||
				          ! empty( array_intersect( $cart_category_ids, $include_list_category ) );

				$cond[] = ( ! is_array( $exclude_list_product ) && $exclude_list_product != 'all' ) ||
				          ( ! in_array( $assign_id, $exclude_list_product ) && empty( array_intersect( $cart_product_ids, $exclude_list_product ) ) ) ||
				          empty( $exclude_list_product );
				$cond[] = ( empty( array_intersect( $product_cat_ids, $exclude_list_category ) ) &&
				            empty( array_intersect( $cart_category_ids, $exclude_list_category ) ) ) ||
				          empty( $exclude_list_category );

				if ( ! in_array( false, $cond ) ) {
					$search_arr_gift = array(
						'rule_id' => $value['rule_id'],
						'gift_id' => $value['gift_id'],
					);
					if ( $value['override_gift'] == 1 ) {
						$override_array[] = array(
							'override' => $value['override_gift'],
							'order'    => $value['order'] ? $value['order'] : 0,
							'rule_id'  => $value['rule_id'],
						);
					}
				} else {
					continue;
				}
				if ( is_array( $value['gift_id'] ) && ! empty( $value['gift_id'] ) ) {
					$search_arr_gift['gift_id'] = self::gift_items_from_pack( $value['gift_id'] );
				}
				$find_matching_pack[] = $search_arr_gift;
			}
			$find_matching_pack = self::check_override_rules( '', $find_matching_pack, $override_array, array(), $single_rule_array );

			if ( isset( $single_gift ) && ! empty( $single_gift ) ) {
				if ( $this->settings->get_params( 'override_type' ) != 'all' ) {
					array_unshift( $find_matching_pack, $single_gift );
				} else {
					if ( empty( $find_matching_pack ) ) {
						$find_matching_pack[] = $single_gift;
					}
				}
			}

			return $find_matching_pack;
		}

		public function scan_rule( $mode = 'all', $assign_id = '', $quantity = 0, $price_add = 0, $additional_data = array() ) {
			$cart_product_ids  = array();
			$cart_category_ids = array();
			$single_gift       = array();

			global $woocommerce;
			if ( isset( $additional_data['cart'] ) && ! empty( $additional_data['cart'] ) ) {
				$wc_cart = $additional_data['cart'];
			} else {
				$wc_cart = $woocommerce->cart;
			}
			$cart_items = $wc_cart->get_cart();

			if ( $assign_id && $mode != 'qty' && $mode != 'remove' ) {
				$gift_id_in_product = get_post_meta( $assign_id, 'jagif_gift_pack_in_product', true );
				$gift_in_product    = $this->get_gift_item( array( $gift_id_in_product ) );
				$gift_product       = wc_get_product( $assign_id );
				$product_cat_ids    = wc_get_product_term_ids( $assign_id, 'product_cat' );

				if ( ! empty( $gift_in_product ) ) {
					$single_gift = array(
						'rule_id'       => 'single',
						'gift_id'       => array(
							$gift_in_product[0]['pack_id'] => $gift_in_product
						),
						'is_apply'      => true,
						'already_count' => 0,
					);
				}
			}
			$display_condition = $this->helper->jagif_get_display_conditions() ?? [];

			$cart_rules      = array();
			$cart_packs_data = array();
			foreach ( $cart_items as $cart_item ) {
				if ( ! isset( $cart_item['jagif_rule_id'] ) && ! isset( $cart_item['jagif_pack_id'] ) ) {
					$current_id         = $cart_item['product_id'];
					$current_product    = wc_get_product( $current_id );
					$cart_product_ids[] = $current_id;
					$cart_category_ids  = array_merge( wc_get_product_term_ids( $current_id, 'product_cat' ), $cart_category_ids );
				}
				if ( isset( $cart_item['jagif_rule_id'] ) ) {
					$cart_rules[] = $cart_item['jagif_rule_id'];
				}
				if ( isset( $cart_item['jagif_pack_id'] ) && isset( $cart_item['jagif_rule_id'] ) ) {
					$cart_item_pack_data = get_post_meta( $cart_item['jagif_pack_id'], 'jagif-woo_free_gift_gift', true );
					if ( empty( $cart_packs_data ) ) {
						$cart_pack_data['pack_id']    = $cart_item['jagif_pack_id'];
						$cart_pack_data['rule_id']    = $cart_item['jagif_rule_id'];
						$cart_pack_data['product_id'] = $cart_item['jagif_parent_id'];
						$cart_pack_data['pack_qty']   = 1;
						if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
							if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
							     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
								$cart_pack_data['pack_qty'] = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
							}
						}
						$cart_packs_data[] = $cart_pack_data;
					} else {
						$isset_pack_data = false;
						foreach ( $cart_packs_data as $cart_pack_key => $cart_pack_value ) {
							if ( $cart_item['jagif_pack_id'] == $cart_pack_value['pack_id']
							     && $cart_item['jagif_rule_id'] == $cart_pack_value['rule_id']
							     && $cart_item['jagif_parent_id'] == $cart_pack_value['product_id'] ) {
								$add_qty = 1;
								if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
									if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
									     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
										$add_qty = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
									}
								}
								$cart_packs_data[ $cart_pack_key ]['pack_qty'] += $add_qty;
								$isset_pack_data                               = true;
							}
						}
						if ( ! $isset_pack_data ) {
							$cart_pack_data['pack_id']    = $cart_item['jagif_pack_id'];
							$cart_pack_data['rule_id']    = $cart_item['jagif_rule_id'];
							$cart_pack_data['product_id'] = $cart_item['jagif_parent_id'];
							$cart_pack_data['pack_qty']   = 1;
							if ( $cart_item_pack_data && isset( $cart_item['jagif_index'] ) && isset( $cart_item_pack_data[ $cart_item['jagif_index'] ] ) ) {
								if ( isset( $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) &&
								     $cart_item['quantity'] != $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] ) {
									$cart_pack_data['pack_qty'] = (int) ( $cart_item['quantity'] / $cart_item_pack_data[ $cart_item['jagif_index'] ]['archive'] );
								}
							}
							$cart_packs_data[] = $cart_pack_data;
						}
					}
				}
			}
			$cart_product_ids  = array_unique( $cart_product_ids );
			$cart_category_ids = array_unique( $cart_category_ids );
			$cart_rules        = array_unique( $cart_rules );
			$cart_gift_pack    = [];
			$override_array    = array();
			$single_rule_array = array();

			if ( $mode == 'single_atc' && isset( $additional_data['cart_data'] ) && ! empty( $additional_data['cart_data'] ) && is_array( $additional_data['cart_data'] ) ) {
				if ( isset( $additional_data['cart_data']['cats'] ) && ! empty( $additional_data['cart_data']['cats'] ) ) {
					$cart_category_ids = $additional_data['cart_data']['cats'];
				}
				if ( isset( $additional_data['cart_data']['ids'] ) && ! empty( $additional_data['cart_data']['ids'] ) ) {
					$cart_product_ids = $additional_data['cart_data']['ids'];
				}
			}

			// validate gift qty for cart pack
			if ( ! empty( $cart_packs_data ) ) {
				foreach ( $cart_packs_data as $p_key => $p_val ) {
					$p_items = get_post_meta( $p_val['pack_id'], 'jagif-woo_free_gift_gift', true );
					$p_count = 1;
					if ( $p_items && is_array( $p_items ) ) {
						$p_count = count( $p_items );
					}
					$cart_packs_data[ $p_key ]['pack_qty'] = (int) ( (int) $cart_packs_data[ $p_key ]['pack_qty'] / $p_count );
				}
			}

			if ( $this->settings->get_params( 'override_type' ) == 'part' && ! empty( $single_gift ) ) {
				return array( $single_gift );
			}
			if ( ! isset( $display_condition ) && ! is_array( $display_condition ) ) {
				if ( ! empty( $single_gift ) ) {
					return array( $single_gift );
				}

				return [];
			}
			foreach ( $display_condition as $key => $value ) {

				//old check cart rule
				if ( $mode == 'remove' && empty( $cart_product_ids ) ) {
					continue;
				}
				if ( in_array( $value['rule_id'], $cart_rules ) ) {
					if ( ! in_array( $mode, array( 'remove' ) ) ) {
						continue;
					}
				}

				$cond                  = [];
				$include_list_product  = $value['in_product'] ? $value['in_product'] : [];
				$include_list_category = $value['in_category'] ? $value['in_category'] : [];
				$exclude_list_product  = $value['ex_product'] ? $value['ex_product'] : [];
				$exclude_list_category = $value['ex_category'] ? $value['ex_category'] : [];

				if ( $assign_id && $mode != 'qty' && $mode != 'remove' ) {
					$cond['in_product']  = ( ! is_array( $include_list_product ) && $include_list_product == 'all' ) ||
					                       in_array( $assign_id, $include_list_product ) || empty( $include_list_product ) ||
					                       ! empty( array_intersect( $cart_product_ids, $include_list_product ) );
					$cond['in_category'] = ! empty( array_intersect( $product_cat_ids, $include_list_category ) ) || empty( $include_list_category ) ||
					                       ! empty( array_intersect( $cart_category_ids, $include_list_category ) );
					$cond['ex_product']  = ( ! is_array( $exclude_list_product ) && $exclude_list_product != 'all' ) ||
					                       ( ! in_array( $assign_id, $exclude_list_product ) && empty( array_intersect( $cart_product_ids, $exclude_list_product ) ) ) ||
					                       empty( $exclude_list_product );
					$cond['ex_category'] = ( empty( array_intersect( $product_cat_ids, $exclude_list_category ) ) && empty( array_intersect( $cart_category_ids, $exclude_list_category ) ) ) ||
					                       empty( $exclude_list_category );
				} else {
					$cond['in_product']  = ( ! is_array( $include_list_product ) && $include_list_product == 'all' ) ||
					                       ! empty( array_intersect( $cart_product_ids, $include_list_product ) ) || empty( $include_list_product );
					$cond['in_category'] = ! empty( array_intersect( $cart_category_ids, $include_list_category ) ) || empty( $include_list_category );
					$cond['ex_product']  = ( ! is_array( $exclude_list_product ) && $exclude_list_product != 'all' ) ||
					                       empty( array_intersect( $cart_product_ids, $exclude_list_product ) ) || empty( $exclude_list_product );
					$cond['ex_category'] = empty( array_intersect( $cart_category_ids, $exclude_list_category ) ) || empty( $exclude_list_category );
				}

				$rule_status = array(
					'rule_id'  => $value['rule_id'],
					'gift_id'  => $value['gift_id'],
					'message'  => '',
					'override' => $value['override_gift'],
					'order'    => $value['order'],
					'is_apply' => in_array( false, $cond ) ? false : true
				);
				if ( 'all' == $mode && ! $rule_status['is_apply'] ) {
					$f_key = array();
					foreach ( $cond as $c_key => $c_val ) {
						if ( ! $c_val ) {
							$f_key[] = $c_key;
						};
					}
				}
				if ( $value['override_gift'] == 1 ) {
					$override_array[] = array(
						'override' => $value['override_gift'],
						'order'    => $value['order'] ? $value['order'] : 0,
						'rule_id'  => $value['rule_id'],
					);
				}
				if ( ! $rule_status['is_apply'] ) {
					$no_apply[] = $value['rule_id'];
				}
				if ( is_array( $value['gift_id'] ) && ! empty( $value['gift_id'] ) ) {
					$rule_status['gift_id'] = self::gift_items_from_pack( $value['gift_id'] );
				}
				if ( $mode == 'all' ) {
					$rule_status['message'] = self::get_rule_message( $cond, array(
						'in_product'  => $include_list_product,
						'ex_product'  => $exclude_list_product,
						'in_cat'      => $include_list_category,
						'ex_cat'      => $exclude_list_product,
						'rule_id'     => $value['rule_id'],
						'description' => $value['description']
					) );
				}
				if ( $mode == 'atc' || $mode == 'remove' || $mode == 'qty' || $mode == 'single_atc' ) {
					if ( $rule_status['is_apply'] ) {
						$cart_gift_pack[] = $rule_status;
					}
					continue;
				}
				$cart_gift_pack[] = $rule_status;
			}

			if ( isset( $no_apply ) ) {
				$cart_gift_pack = self::check_override_rules( $mode, $cart_gift_pack, $override_array, $no_apply, $single_rule_array );
			} else {
				$cart_gift_pack = self::check_override_rules( $mode, $cart_gift_pack, $override_array, array(), $single_rule_array );
			}

			if ( isset( $single_gift ) && ! empty( $single_gift ) ) {
				if ( $this->settings->get_params( 'override_type' ) != 'all' ) {
					array_unshift( $cart_gift_pack, $single_gift );
				} else {
					if ( empty( $cart_gift_pack ) ) {
						$cart_gift_pack[] = $single_gift;
					} else {
						$is_exits = false;
						foreach ( $cart_gift_pack as $_pack ) {
							if ( $_pack['is_apply'] == 1 ) {
								$is_exits = true;
							}
						}
						if ( ! $is_exits ) {
							$cart_gift_pack = array( $single_gift );
						}
					}
				}
			}

			return $cart_gift_pack;
		}

		public function get_rule_message( $cond, $rule_data ) {
			$prefix            = '';
			$rule_message      = '';
			$rule_all_product  = false;
			$data_rule_product = get_post_meta( $rule_data['rule_id'], 'jagif-woo_free_gift_rules', true );
			if ( is_array( $data_rule_product ) && isset( $data_rule_product['jagif_conditions'] ) && ! empty( $data_rule_product['jagif_conditions'] ) ) {
				foreach ( $data_rule_product['jagif_conditions'] as $data_rule_conditions ) {
					if ( is_array( $data_rule_conditions ) && isset( $data_rule_conditions['type'] ) && isset( $data_rule_conditions['value'] ) ) {
						if ( $data_rule_conditions['value'] == 'in_product' && $data_rule_conditions['value'] ) {
							$rule_all_product = true;
						}
					}
				}
			}
			if ( isset( $rule_data['description'] ) ) {
				return $rule_data['description'];
			}
			foreach ( $cond as $key => $value ) {
				if ( ! $value ) {
					switch ( $key ) {
						case 'in_product':
							if ( empty( $rule_message ) ) {
								$rule_message .= $prefix . esc_html__( ' product ', 'jagif-woo-free-gift' );
							} else {
								$rule_message .= esc_html__( ' and have product ', 'jagif-woo-free-gift' );
							}
							if ( $rule_all_product ) {
								$pr_text = esc_html__( 'in cart', 'jagif-woo-free-gift' );
							} else {
								$pr_text = '';
								foreach ( $rule_data['in_product'] as $prd_id ) {
									$in_prd = wc_get_product( $prd_id );
									if ( $in_prd ) {
										if ( ! empty( $pr_text ) ) {
											$pr_text .= esc_html__( ' or ', 'jagif-woo-free-gift' ) . wp_kses_post( $in_prd->get_title() );
										} else {
											$pr_text = $in_prd->get_title();
										}
									}
								}
							}
							$rule_message .= $pr_text;
							break;
						case 'in_category':
							if ( empty( $rule_message ) ) {
								$rule_message .= $prefix . esc_html__( ' product with category ', 'jagif-woo-free-gift' );
							} else {
								$rule_message .= esc_html__( ' and have product with category ', 'jagif-woo-free-gift' );
							}
							foreach ( $rule_data['in_category'] as $cat_id ) {
								$term = get_term_by( 'id', $cat_id, 'product_cat', 'ARRAY_A' );
								if ( $term ) {
									if ( isset( $pr_text ) ) {
										$pr_text .= esc_html__( ' or ', 'jagif-woo-free-gift' ) . wp_kses_post( $term['name'] );
									} else {
										$pr_text = $term['name'];
									}
								}
							}
							$rule_message .= $pr_text;
							break;
						default:
							break;
					}
				}
			}
			if ( empty( $rule_message ) ) {
				$rule_message = esc_html__( 'Continue shopping to get gift', 'jagif-woo-free-gift' );
			} else {
				$rule_message = esc_html__( 'You will be eligible for free gift if you will have',
						'jagif-woo-free-gift' ) . $rule_message;
			}

			return $rule_message;
		}

		public function detect_override_rules( $mode, $display_conditions ) {
			if ( ! is_array( $display_conditions ) || ! is_object( $display_conditions ) ) {
				return $display_conditions;
			}
			if ( count( $display_conditions ) == 1 || $mode == 'all' ) {
				return $display_conditions;
			}
			$override_exits = false;
			foreach ( $display_conditions as $display_condition ) {
				if ( $display_conditions['override_gift'] == 1 ) {
					$override_exits = true;
				}
			}
			if ( $override_exits ) {
				foreach ( $display_conditions as $_key => $_display_condition ) {
					if ( $display_conditions['override_gift'] == 1 ) {
						if ( ! isset( $max_gift ) || ! isset( $max_order ) ) {
							$max_gift  = $_key;
							$max_order = isset( $display_conditions['order'] ) && ! empty( $display_conditions['order'] ) ? (int) $display_conditions['order'] : 0;
						} else {
							$order = isset( $display_conditions['order'] ) && ! empty( $display_conditions['order'] ) ? (int) $display_conditions['order'] : 0;
							if ( $max_order < $order ) {
								$max_gift  = $_key;
								$max_order = $order;
							}
						}
					} else {
						continue;
					}
				}
				if ( isset( $max_gift ) || isset( $max_order ) ) {
					return array( $display_conditions[ $max_gift ] );
				}
			} else {
				return $display_conditions;
			}

			return $display_conditions;
		}

		public function check_override_rules( $mode, $cart_gift_pack, $override_array, $no_apply, $single_rule_array ) {
			$override_pass = array();
			if ( ! empty( $override_array ) ) {
				if ( ! empty( $mode ) ) {
					if ( ! empty( $no_apply ) ) {
						foreach ( $override_array as $o_key => $o_value ) {
							if ( in_array( $o_value['rule_id'], $no_apply ) ) {
								$override_pass[] = $o_value['rule_id'];
								unset( $override_array[ $o_key ] );
							}
						}
					}
				}
				$override_id = 0;
				$_order      = 0;
				foreach ( $override_array as $override_pack ) {
					if ( $override_id == 0 ) {
						$override_id = $override_pack['rule_id'];
					}
					if ( $override_pack['order'] >= $_order ) {
						if ( $override_pack['order'] == $_order ) {
							if ( $_order == 0 ) {
								$override_rule = $override_pack['rule_id'];
							} else {
								if ( (int) $override_pack['rule_id'] > (int) $override_id ) {
									$override_id   = (int) $override_pack['rule_id'];
									$override_rule = $override_pack['rule_id'];
								}
							}
						} else {
							$override_id   = (int) $override_pack['rule_id'];
							$override_rule = $override_pack['rule_id'];
						}
						$_order = $override_pack['order'];
					}
				}

				$override_ids = array( $override_id );
				foreach ( $cart_gift_pack as $_key => $_pack ) {
					if ( ! empty( $mode ) ) {
						if ( ! in_array( $_pack['rule_id'], $override_ids ) ) {
							if ( $mode == 'all' ) {
								if ( ! in_array( $_pack['rule_id'], $override_pass ) ) {
									unset( $cart_gift_pack[ $_key ] );
								}
							} else {
								unset( $cart_gift_pack[ $_key ] );
							}
						}
					} else {
						if ( ! in_array( $_pack['rule_id'], $override_ids ) ) {
							unset( $cart_gift_pack[ $_key ] );
						}
					}
				}
			}

			return $cart_gift_pack;
		}

		public function sort_display_gift( $gift_list ) {
			if ( empty( $gift_list ) || ! is_array( $gift_list ) ) {
				return $gift_list;
			}
			$apply_gift = $not_gift = array();
			foreach ( $gift_list as $gift ) {
				if ( $gift['is_apply'] ) {
					$apply_gift[] = $gift;
				} else {
					$not_gift[] = $gift;
				}
			}
			$gift_sort = array_merge( $apply_gift, $not_gift );

			return $gift_sort;
		}

		public function get_price( $product, $product_id ) {
			if ( ! $product && ! $product_id ) {
				return 0;
			}
			switch ( $product->get_type() ) {
				case 'variable':
					$def_attr = get_post_meta( $product_id, '_default_attributes', true );
					if ( $def_attr ) {
						foreach ( $product->get_available_variations() as $_variation ) {
							$def = true;
							if ( ! is_array( $_variation['attributes'] ) || ! is_array( $def_attr ) || count( $def_attr ) != count( $_variation['attributes'] ) ) {
								return 0;
							}
							foreach ( $def_attr as $defkey => $defval ) {
								if ( $_variation['attributes'][ 'attribute_' . $defkey ] != $defval && ! empty( $_variation['attributes'][ 'attribute_' . $defkey ] ) ) {
									$def = false;
								}
							}
							if ( $def ) {
								$variation_def = wc_get_product( $_variation['variation_id'] );
								if ( ! $variation_def ) {
									return 0;
								}

								return $variation_def->get_price();
							};
						}

						return 0;
					} else {
						return 0;
					}
					break;
				case 'variation':
				case 'simple':
					return $product->get_price();
					break;
				default:
					return 0;
					break;
			}
		}

		public function gift_items_from_pack( $product_gift_ids ) {
			$arr_gift_item = array();
			if ( empty( $product_gift_ids ) || ! is_array( $product_gift_ids ) ) {
				return array();
			}
			foreach ( $product_gift_ids as $gift_id ) {
				$arr_item = array();
				$arr_gift = get_post_meta( $gift_id, 'jagif-woo_free_gift_gift', true ) ? get_post_meta( $gift_id, 'jagif-woo_free_gift_gift', true ) : [];
				if ( $arr_gift && is_array( $arr_gift ) ) {
					foreach ( $arr_gift as $item_gift ) {
						if ( ! isset( $item_gift['archive_id'] ) ) {
							continue;
						}
						$product_gift_item = wc_get_product( $item_gift['archive_id'] );
						if ( $product_gift_item->is_type( 'variable' ) ) {
							$check_stock = false;
							if ( ! $product_gift_item->get_children() ) {
								continue;
							}
							foreach ( $product_gift_item->get_children() as $children_id ) {
								if ( wc_get_product( $children_id )->is_in_stock() ) {
									$check_stock = true;
								}
							}
							if ( ! $check_stock ) {
								continue;
							}
						} else {
							if ( ! $product_gift_item->is_in_stock() ) {
								continue;
							}
						}
						$arr_item[] = array(
							'archive_id' => $item_gift['archive_id'],
							'archive'    => $item_gift['archive'] ? $item_gift['archive'] : 1
						);
					}
					$arr_gift_item[ $gift_id ] = $arr_item;
				}
			}

			return $arr_gift_item;
		}

		public function get_gift_item( $product_gift_ids ) {
			$arr_gift_item = array();
			if ( empty( $product_gift_ids ) ) {
				return array();
			}
			foreach ( $product_gift_ids as $gift_id ) {
				$arr_item = array();
				$arr_gift = get_post_meta( $gift_id, 'jagif-woo_free_gift_gift', true ) ?? [];
				if ( $arr_gift ) {
					$num_gift = 0;
					foreach ( $arr_gift as $item_gift ) {
						if ( ! isset( $item_gift['archive_id'] ) ) {
							break;
						}
						$product_gift_item = wc_get_product( $item_gift['archive_id'] );
						if ( ! $product_gift_item ) {
							continue;
						}
						if ( $product_gift_item->is_type( 'variable' ) ) {
							$check_stock = false;
							if ( ! $product_gift_item->get_children() ) {
								continue;
							}
							foreach ( $product_gift_item->get_children() as $children_id ) {
								if ( wc_get_product( $children_id )->is_in_stock() ) {
									$check_stock = true;
								}
							}
							if ( ! $check_stock ) {
								continue;
							}
							$item_gift['pack_id'] = $gift_id;
							array_push( $arr_gift_item, $item_gift );
						} else {
							if ( ! $product_gift_item->is_in_stock() ) {
								continue;
							}
							$item_gift['pack_id'] = $gift_id;
							array_push( $arr_gift_item, $item_gift );
						}
						$num_gift ++;
					}
				}
			}

			return $arr_gift_item;
		}

		public function get_display_conditions() {
			$final_arr         = array();
			$all_product_cat   = $this->helper->get_all_product_cat();
			$get_all_post_type = $this->helper->get_all_post_type();

			if ( count( $get_all_post_type ) > 0 ) {
				//loop all rule
				foreach ( $get_all_post_type as $key => $item ) {
					$arr_display_conditions  = array(
						'gift_id'       => 'array()',
						'override_gift' => false,
						'order'         => 0,
						'ex_product'    => array(),
						'in_product'    => array(),
						'ex_category'   => array(),
						'in_category'   => array(),
					);
					$jagif_get_gift          = array();
					$arr_check               = array();
					$jagif_input_search_gift = $item['jagif_input_search_gift'] ?? [];
					$jagif_conditions        = $item['jagif_conditions'] ?? [];

					if ( empty( $jagif_input_search_gift ) || empty( $jagif_conditions ) ) {
						continue;
					}
					foreach ( $jagif_input_search_gift as $gift ) {
						array_push( $jagif_get_gift, $gift );
					}
					$arr_display_conditions['gift_id']       = array_unique( $jagif_get_gift );
					$arr_display_conditions['rule_id']       = $key;
					$arr_display_conditions['description']   = isset( $item['description'] ) ? $item['description'] : '';
					$arr_display_conditions['override_gift'] = isset( $item['override']['enable'] ) ? $item['override']['enable'] == 1 ? true : false : false;
					$arr_display_conditions['order']         = isset( $item['override']['priority'] ) ? $item['override']['priority'] : 0;
					foreach ( $jagif_conditions as $item_condition ) {
						if ( ! empty( $item_condition ) ) {
							switch ( $item_condition['type'] ) {
								case 'ex_product':
									if ( isset( $item_condition['value'] ) && ! empty( $item_condition['value'] ) && is_array( $item_condition['value'] ) ) {
										foreach ( $item_condition['value'] as $product_id ) {
											array_push( $arr_display_conditions['ex_product'], $product_id );
										}
									} else {
										$arr_display_conditions['ex_product'] = 'all';
//										$all_product = isset( $all_product ) ? $all_product : $this->helper->get_all_product();
//										foreach ( $all_product as $product_id ) {
//											array_push( $arr_display_conditions['ex_product'], $product_id );
//										}
									}
									break;
								case 'in_product':
									if ( isset( $item_condition['value'] ) && ! empty( $item_condition['value'] ) && is_array( $item_condition['value'] ) ) {
										foreach ( $item_condition['value'] as $product_id ) {
											array_push( $arr_display_conditions['in_product'], $product_id );
										}
									} else {
										$arr_display_conditions['in_product'] = 'all';
//										$all_product = isset( $all_product ) ? $all_product : $this->helper->get_all_product();
//										foreach ( $all_product as $product_id ) {
//											array_push( $arr_display_conditions['in_product'], $product_id );
//										}
									}
									break;
								case 'ex_category':
									if ( isset( $item_condition['value'] ) && ! empty( $item_condition['value'] ) ) {
										foreach ( $item_condition['value'] as $cat_id ) {
											array_push( $arr_display_conditions['ex_category'], $cat_id );
										}
									} else {
										foreach ( $all_product_cat as $cat_id ) {
											array_push( $arr_display_conditions['ex_category'], $cat_id );
										}
									}
									break;
								case 'in_category':
									if ( isset( $item_condition['value'] ) && ! empty( $item_condition['value'] ) ) {
										foreach ( $item_condition['value'] as $cat_id ) {
											array_push( $arr_display_conditions['in_category'], $cat_id );
										}
									} else {
										foreach ( $all_product_cat as $cat_id ) {
											array_push( $arr_display_conditions['in_category'], $cat_id );
										}
									}
									break;
								default:
									break;
							}
						}
					}

					foreach ( $arr_display_conditions as $part_key => $part_arr ) {
						if ( is_array( $part_arr ) && $part_key != 'time' && $part_key != 'date' ) {
							$arr_display_conditions[ $part_key ] = array_unique( $part_arr );
						}
					}
					array_push( $final_arr, $arr_display_conditions );
				}
			}

			return update_option( 'jagif_display_conditions', $final_arr );
		}

		public function jagif_get_gift_item_ids( $product_id ) {
			$arr_gift_ids   = array();
			$gift_item_data = [];
			$get_gift_item  = $this->get_default_gift( $product_id );
			if ( isset( $get_gift_item ) && empty( $get_gift_item ) ) {
				return '';
			}

			foreach ( $get_gift_item as $rule_value ) {
				if ( ! isset( $rule_value['gift_id'] ) || ( isset( $rule_value['gift_id'] ) && empty( $rule_value['gift_id'] ) ) ) {
					continue;
				}
				foreach ( $rule_value['gift_id'] as $pack_k => $pack_v ) {
					if ( ! is_array( $pack_v ) || empty( $pack_v ) ) {
						continue;
					}
					if ( ! empty( $gift_item_data ) && in_array( $pack_k, $gift_item_data ) ) {
						continue;
					}
					$purchase_ids = array();
					foreach ( $pack_v as $item_v ) {
						if ( isset( $item_v['archive'] ) && isset( $item_v['archive_id'] ) ) {
							$item_id      = $item_v['archive_id'];
							$product_gift = wc_get_product( $item_id );
							if ( $product_gift && $product_gift->is_type( 'variable' ) ) {
								if ( ! $product_gift->get_children() ) {
									continue;
								}
								foreach ( $product_gift->get_children() as $children_id ) {
									$child_prd = wc_get_product( $children_id );
									if ( $child_prd->is_in_stock() && $child_prd->is_purchasable() ) {
										$purchase_ids[] = $children_id;
										break;
									} else {
										continue;
									}
								}
							} else {
								$purchase_ids[] = $item_id;
							}
						}
					}
					if ( ! empty( $purchase_ids ) ) {
						$arr_gift_ids[]   = array(
							'gift_ids' => $purchase_ids,
							'pack_id'  => $pack_k,
							'rule_id'  => $rule_value['rule_id']
						);
						$gift_item_data[] = $pack_k;
						break;
					} else {
						continue;
					}
				}
			}

			return $arr_gift_ids;
		}

		function jagif_add_to_cart_items( $items, $cart_item_key, $product_id, $quantity ) {
			WC()->cart->cart_contents[ $cart_item_key ]['jagif_key'] = $cart_item_key;
			$cart_items                                              = WC()->cart->get_cart();
			if ( is_array( $items ) && ( count( $items ) > 0 ) ) {
				for ( $i = 0; $i < count( $items ); $i ++ ) {
					foreach ( $cart_items as $cart_item ) {
						if ( isset( $cart_item['jagif_rule_id'] ) && $cart_item['jagif_rule_id'] == $items[ $i ]['rule_id'] ) {
							continue;
						}
					}
					$_id        = $items[ $i ]['archive_id'];
					$_qty       = $items[ $i ]['archive'];
					$rule_id    = isset( $items[ $i ]['rule_id'] ) ? $items[ $i ]['rule_id'] : 'single';
					$pack_id    = $items[ $i ]['pack_id'];
					$jagif_type = $items[ $i ]['jagif_type'];
					$qty_order  = get_post_meta( $pack_id, 'jagif_qty_gift_order', true ) ?? 1;
//					$max_qty_add_to_cart = $_qty * $qty_order;
					$_product = wc_get_product( $items[ $i ]['archive_id'] );
//					$max_pack            = $this->settings->get_params( 'max_gp_per_product' );
					if ( ! $_product || ( $_qty <= 0 ) ) {
						continue;
					}
					$_variation_id = '';
					$_variation    = array();

					if ( $_product instanceof WC_Product_Variation ) {
						if ( $_product->is_purchasable() && $_product->get_price() &&
						     ( ( $_product->get_manage_stock() && $_product->get_stock_quantity() ) || ( ! $_product->get_manage_stock() ) ) ) {
							$_variation_any = false;
							$_variation_id  = $_id;
							$_id            = $_product->get_parent_id();
							$_variation     = wc_get_product_variation_attributes( $_variation_id );
							if ( isset( $items[ $i ]['jagif_variation'] ) ) {
								$variation_data = $items[ $i ]['jagif_variation'];
								if ( is_array( $variation_data ) || is_object( $variation_data ) ) {
									$_variation = self::decode_variations( $variation_data[0] );
								}
							}
							foreach ( $_variation as $v_key => $v_val ) {
								if ( empty( $v_val ) ) {
									$_variation_any = true;
								}
							}
							if ( $_variation_any ) {
								$variable_id  = $_product->get_parent_id();
								$variable_prd = wc_get_product( $variable_id );
								$any_detail   = self::get_variation_from_any( $variable_prd, $_product, $_variation, $_variation_any );
								$_variation   = self::decode_variations( $any_detail['data'] );
							}
						} else {
							continue;
						}
					}
					// add to cart data
					$_data = array(
						'jagif_index'      => $i,
						'jagif_qty'        => $_qty,
						'jagif_rule_id'    => $rule_id,
						'jagif_pack_id'    => $pack_id,
						'jagif_type'       => $jagif_type,
						'jagif_parent_id'  => $product_id,
						'jagif_parent_key' => $cart_item_key,
					);
					// add to cart
					$_key = WC()->cart->add_to_cart( $_id, $_qty * $quantity, $_variation_id, $_variation, $_data );
					if ( empty( $_key ) ) {
						// can't add the gift product
						if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'] ) ) {
							$keys = WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'];
							foreach ( $keys as $key ) {
								// remove all bundled products
								WC()->cart->remove_cart_item( $key );
							}
							WC()->cart->remove_cart_item( $cart_item_key );
							// break out of the loop
							break;
						}
					} elseif ( ! isset( WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'] ) || ! in_array( $_key, WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'], true ) ) {
						// save current key
						WC()->cart->cart_contents[ $_key ]['jagif_key'] = $_key;
						// add keys for parent
						WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'][] = $_key;

					}
				}
			}
		}

		function atc_single_pack( $product_id, $cart_item_key = '', $quantity = 1 ) {
			if ( ! $product_id ) {
				return;
			}
			$_single_product = wc_get_product( $product_id );
			if ( ! $_single_product || ! is_object( $_single_product ) ) {
				return;
			}
			$gift_id_in_product = $_single_product->get_meta( 'jagif_gift_pack_in_product', true );
			$single_gift_data   = get_post_meta( $gift_id_in_product, 'jagif-woo_free_gift_gift', true );
			$single_gift_data1  = $this->get_gift_item( array( $gift_id_in_product ) );
			if ( ! $single_gift_data || empty( $single_gift_data ) ) {
				return;
			}
			foreach ( $single_gift_data as $sing_index => $sing_gift_val ) {
				if ( ! isset( $sing_gift_val['archive_id'] ) || ! isset( $sing_gift_val['archive'] ) ) {
					continue;
				}
				$atc_qty       = (int) ( (int) $sing_gift_val['archive'] * (int) $quantity );
				$product_infor = self::get_variation_array( $sing_gift_val['archive_id'] );
				if ( ! isset( $product_infor['id'] ) || ! isset( $product_infor['variation_id'] )
				     || ! isset( $product_infor['variation'] ) || ! isset( $product_infor['type'] ) ) {
					continue;
				}
				// add to cart data
				$_data = array(
					'jagif_index'      => $sing_index,
					'jagif_qty'        => $sing_gift_val['archive'],
					'jagif_rule_id'    => 'single',
					'jagif_pack_id'    => $gift_id_in_product,
					'jagif_type'       => $product_infor['type'],
					'jagif_parent_id'  => $product_id,
					'jagif_parent_key' => $cart_item_key,
				);
				// add to cart
				$_key = WC()->cart->add_to_cart( $product_infor['id'], $atc_qty, $product_infor['variation_id'], $product_infor['variation'], $_data );
			}
		}

		public function get_variation_array( $product_id ) {
			$_product = wc_get_product( $product_id );
			if ( ! $_product ) {
				return;
			}
			$_product_type = $_product->get_type();
			if ( 'simple' == $_product_type ) {
				return array( 'id' => $product_id, 'variation_id' => '', 'variation' => '', 'type' => $_product_type );
			}
			if ( $_product instanceof WC_Product_Variation ) {
				if ( $_product->is_purchasable() && $_product->get_price() &&
				     ( ( $_product->get_manage_stock() && $_product->get_stock_quantity() ) || ( ! $_product->get_manage_stock() ) ) ) {
					$_variation_any = false;
					$_variation_id  = $product_id;
					$_id            = $_product->get_parent_id();
					$_variation     = wc_get_product_variation_attributes( $_variation_id );
					foreach ( $_variation as $v_key => $v_val ) {
						if ( empty( $v_val ) ) {
							$_variation_any = true;
						}
					}
					if ( $_variation_any ) {
						$variable_id  = $_product->get_parent_id();
						$variable_prd = wc_get_product( $variable_id );
						$any_detail   = self::get_variation_from_any( $variable_prd, $_product, $_variation, $_variation_any );
						$_variation   = self::decode_variations( $any_detail['data'] );
					}

					//return
					return array(
						'id'           => $_id,
						'variation_id' => $_variation_id,
						'variation'    => $_variation,
						'type'         => $_product_type
					);
				}
			}

			if ( $_product instanceof WC_Product_Variable ) {
				$product_children = $_product->get_children();
				if ( count( $product_children ) ) {
					$_variation_id = '';
					$_variation    = '';
					for ( $j = 0; $j < count( $product_children ); $j ++ ) {
						$variation_prd = wc_get_product( $product_children[ $j ] );
						if ( $variation_prd->is_purchasable() && $variation_prd->get_price() &&
						     ( ( $variation_prd->get_manage_stock() && $variation_prd->get_stock_quantity() ) || ( ! $variation_prd->get_manage_stock() ) ) ) {
							$_variation_any = false;
							$_variation_id  = $product_children[ $j ];
							$_variation     = wc_get_product_variation_attributes( $_variation_id );
							foreach ( $_variation as $v_key => $v_val ) {
								if ( empty( $v_val ) ) {
									$_variation_any = true;
								}
							}
							if ( $_variation_any ) {
								$any_detail = self::get_variation_from_any( $_product, $_product, $_variation, $_variation_any );
								$_variation = self::decode_variations( $any_detail['data'] );
							}
							break;
						} else {
							continue;
						}
					}

					//return
					return array(
						'id'           => $product_id,
						'variation_id' => $_variation_id,
						'variation'    => $_variation,
						'type'         => $_product_type
					);
				}
			}
		}

		public function remove_cart_gift( $cart, $mode = 'all', $product_id = 0, $remove_item_key = '' ) {
			$cart_items = $cart->get_cart();
			switch ( $mode ) {
				case 'remove':
					//if no simple product then remove all gift
					$cart_ids = array();
					foreach ( $cart_items as $_cart_item_single ) {
						if ( ! isset( $_cart_item_single['jagif_pack_id'] ) && ! isset( $_cart_item_single['jagif_rule_id'] ) ) {
							$cart_ids[] = $_cart_item_single['product_id'];
						}
					}
					if ( count( $cart_ids ) == 0 && count( $cart_items ) > 0 ) {
						foreach ( $cart_items as $_key_single => $_cart_single_gift ) {
							$cart->remove_cart_item( $_key_single );
						}

						return;
					}
					//check remove gift single-rule
					foreach ( $cart_items as $_key => $_cart_item ) {
						if ( isset( $_cart_item['jagif_pack_id'] ) && isset( $_cart_item['jagif_rule_id'] ) ) {
							if ( empty( $_cart_item['jagif_rule_id'] ) || $_cart_item['jagif_rule_id'] == 'single' ) {
								if ( isset( $_cart_item['jagif_parent_id'] ) && $product_id == $_cart_item['jagif_parent_id'] ) {
									if ( $remove_item_key ) {
										if ( $_cart_item['jagif_parent_key'] == $remove_item_key ) {
											$cart->remove_cart_item( $_key );
										} elseif ( ! in_array( $product_id, $cart_ids ) ) {
											$cart->remove_cart_item( $_key );
										}
									} elseif ( ! in_array( $product_id, $cart_ids ) ) {
										$cart->remove_cart_item( $_key );
									}
								}
							} else {
								$cart->remove_cart_item( $_key );
							}
						}
					}
					break;
				case 'qty':
					//if no simple product then remove all gift
					$cart_ids    = array();
					$parent_data = array();
					foreach ( $cart_items as $_cart_item_single ) {
						if ( ! isset( $_cart_item_single['jagif_pack_id'] ) && ! isset( $_cart_item_single['jagif_rule_id'] ) ) {
							$cart_ids[] = $_cart_item_single['product_id'];
						}
					}
					if ( count( $cart_ids ) == 0 && count( $cart_items ) > 0 ) {
						foreach ( $cart_items as $_key_single => $_cart_single_gift ) {
							$cart->remove_cart_item( $_key_single );
						}

						return;
					}
					//check remove gift single-rule
					if ( isset( $remove_item_key ) && isset( $remove_item_key['key'] )
					     && isset( $cart_items[ $remove_item_key['key'] ] ) && isset( $cart_items[ $remove_item_key['key'] ]['jagif_ids'] ) ) {
						foreach ( $cart_items as $_key => $_cart_item ) {
							if ( isset( $_cart_item['jagif_pack_id'] ) && isset( $_cart_item['jagif_rule_id'] ) ) {
								if ( empty( $_cart_item['jagif_rule_id'] ) || $_cart_item['jagif_rule_id'] == 'single' ) {
									if ( isset( $_cart_item['jagif_parent_id'] ) && $product_id == $_cart_item['jagif_parent_id'] ) {
										if ( $remove_item_key ) {

										} elseif ( ! in_array( $product_id, $cart_ids ) ) {
											$cart->remove_cart_item( $_key );
										}
									}
								} else {
									//remove all global rule gift
									$cart->remove_cart_item( $_key );
								}
							}
						}
						//add sing gift if parrent qty more than gift qty
						self::after_qty_single_gift( $cart, $product_id );
					}
					break;
				default:
					foreach ( $cart_items as $_key => $_cart_item ) {
						if ( isset( $_cart_item['jagif_pack_id'] ) || isset( $_cart_item['jagif_rule_id'] ) ) {
							$cart->remove_cart_item( $_key );
						}
					}
					break;
			}
		}

		function get_cart_normal_count( $cart, $s_product_id ) {
			$cart_items = $cart->get_cart();
			$o_count    = 0;
			foreach ( $cart_items as $cart_item ) {
				if ( ! isset( $cart_item['jagif_pack_id'] ) && ! isset( $cart_item['jagif_rule_id'] )
				     && isset( $cart_item['product_id'] ) && $cart_item['product_id'] == $s_product_id ) {
					$o_count += $cart_item['quantity'];
				}
			}

			return $o_count;
		}

		function after_qty_single_gift( $cart, $product_id ) {
			if ( empty( $cart ) ) {
				$cart = WC()->cart;
			}
			$_single_product = wc_get_product( $product_id );
			if ( ! $_single_product || ! is_object( $_single_product ) ) {
				return;
			}
			$parent_data       = array();
			$parent_count      = $gift_count = 0;
			$parent_gift_count = array();
			$cart_items        = $cart->get_cart();
			//pack in single
			$is_parrent_of_single = $_single_product->get_meta( 'jagif_gift_pack_in_product', true );
			if ( ! $is_parrent_of_single ) {
				return;
			}
			//pack data
			$pack_item_data = get_post_meta( $is_parrent_of_single, 'jagif-woo_free_gift_gift', true );
			//search for product available single gift
			foreach ( $cart_items as $_key => $_cart_item ) {
				if ( isset( $_cart_item['jagif_ids'] ) && $product_id == $_cart_item['product_id'] ) {
					$cart_parent_data['qty']  = $_cart_item['quantity'];
					$cart_parent_data['key']  = $_cart_item['key'];
					$cart_parent_data['gift'] = 0;
					$parent_gift_count        = 0;
					//total gift of
					foreach ( $cart_items as $single_cart_key => $single_cart_value ) {
						if ( isset( $single_cart_value['jagif_parent_key'] ) && $_key == $single_cart_value['jagif_parent_key']
						     && isset( $single_cart_value['jagif_index'] ) && isset( $single_cart_value['jagif_pack_id'] )
						     && $single_cart_value['jagif_pack_id'] = $is_parrent_of_single && isset( $pack_item_data[ $single_cart_value['jagif_index'] ] )
						                                              && is_array( $pack_item_data[ $single_cart_value['jagif_index'] ] ) && isset( $pack_item_data[ $single_cart_value['jagif_index'] ]['archive'] ) ) {
							$cart_parent_data['gift'] = (int) ( $single_cart_value['quantity'] / $pack_item_data[ $single_cart_value['jagif_index'] ]['archive'] );
						}
					}
					$parent_data[] = $cart_parent_data;
				}
			}
			if ( empty( $parent_data ) ) {
				return;
			}
			foreach ( $parent_data as $parent_data_value ) {
				$parent_count += $parent_data_value['qty'];
				$gift_count   += $parent_data_value['gift'];
			}
			//remain add gift
			$remain_gift = $gift_count;
			foreach ( $parent_data as $cart_parent_value ) {
				foreach ( $cart_items as $_cart_key => $_cart_value ) {
					if ( isset( $_cart_value['jagif_parent_key'] ) && $_cart_value['jagif_parent_key'] == $cart_parent_value['key']
					     && isset( $_cart_value['jagif_index'] ) && isset( $pack_item_data[ $_cart_value['jagif_index'] ] )
					     && isset( $pack_item_data[ $_cart_value['jagif_index'] ]['archive'] ) ) {
						$pack_def_qty         = $pack_item_data[ $_cart_value['jagif_index'] ]['archive'];
						$gift_item_update_qty = isset( $cart_parent_value['qty'] ) && ! empty( $cart_parent_value['qty'] ) ? (int) $cart_parent_value['qty'] : 1;
						$pack_new_qty         = (int) ( $pack_def_qty * $gift_item_update_qty );
						$cart->set_quantity( $_cart_key, $pack_new_qty );
					}
				}
			}
		}

		function jagif_add_to_cart_auto( $mode = 'all', $cart_item_key = 0, $product_id = 0, $quantity = 0, $variation_id = '', $variation = '', $cart_item_data = '', $wc_cart = '' ) {
			//check cart
			global $jagif_cart_data;
			switch ( $mode ) {
				case 'resolve':
					$cart_items_array = $cart_item_key['items'];
					$cart_item_key    = $cart_item_key['key'];
					if ( empty( $variation_id ) ) {
						$adding_product = wc_get_product( $product_id );
					} else {
						$adding_product = wc_get_product( $variation_id );
					}
					if ( ! $adding_product ) {
						return;
					}
					$adding_price    = VIJAGIF_HELPER::price_currency_display( floatval( $adding_product->get_price() ), get_woocommerce_currency() );
					$rule_gift_items = self::scan_rule( 'atc', $product_id, (float) $quantity, $adding_price * (float) $quantity, array( 'key' => $cart_item_key ) );
					break;
				case 'single':
					if ( empty( $variation_id ) ) {
						$adding_product = wc_get_product( $product_id );
					} else {
						$adding_product = wc_get_product( $variation_id );
					}
					if ( ! $adding_product ) {
						return;
					}
					$adding_price    = VIJAGIF_HELPER::price_currency_display( floatval( $adding_product->get_price() ), get_woocommerce_currency() );
					$rule_gift_items = self::scan_rule( 'atc', $product_id, (float) $quantity, $adding_price * (float) $quantity, array( 'single_cart_data' => $cart_item_data ) );
					break;
				case 'resolve_atc':
					$cart_items_array = $cart_item_key['items'];
					$cart_item_key    = $cart_item_key['key'];
					if ( empty( $variation_id ) ) {
						$adding_product = wc_get_product( $product_id );
					} else {
						$adding_product = wc_get_product( $variation_id );
					}
					if ( ! $adding_product ) {
						return;
					}
					$cart_keys_before = is_array( $cart_item_data ) && isset( $jagif_cart_data['cart_keys'] ) ? $jagif_cart_data['cart_keys'] : '';
					$adding_price     = VIJAGIF_HELPER::price_currency_display( floatval( $adding_product->get_price() ), get_woocommerce_currency() );
					$rule_gift_items  = self::scan_rule( 'single_atc', $product_id, (float) $quantity, $adding_price * (float) $quantity,
						array( 'key' => $cart_item_key, 'cart_data' => $wc_cart, 'cart_keys' => $cart_keys_before ) );
					$wc_cart          = '';
					break;
				case 'single_atc':
					if ( empty( $variation_id ) ) {
						$adding_product = wc_get_product( $product_id );
					} else {
						$adding_product = wc_get_product( $variation_id );
					}
					if ( ! $adding_product ) {
						return;
					}
					$cart_keys_before = is_array( $cart_item_data ) && isset( $cart_item_data['cart_keys'] ) ? $cart_item_data['cart_keys'] : '';
					$adding_price     = VIJAGIF_HELPER::price_currency_display( floatval( $adding_product->get_price() ), get_woocommerce_currency() );
					$rule_gift_items  = self::scan_rule( 'single_atc', $product_id, (float) $quantity, $adding_price * (float) $quantity,
						array(
							'single_cart_data' => $cart_item_data,
							'cart_data'        => $wc_cart,
							'cart_keys'        => $cart_keys_before,
							'key'              => $cart_item_key
						) );
					$wc_cart          = '';
					break;
				case 'remove':
					$remove_product_id = ! empty( $variation_id ) ? $variation_id : $product_id;
					$rule_gift_items   = self::scan_rule( 'remove', $remove_product_id, 0, 0 );
					break;
				case 'qty':
					$rule_gift_items = self::scan_rule( 'qty', '', $cart_item_key['qty'], 0, array(
						'cart' => $wc_cart,
						'key'  => $cart_item_key['key']
					) );
					$cart_item_key   = $cart_item_key['key'];
					break;
				case 'restored':
					if ( $wc_cart ) {
						$cart_restored_item = $wc_cart->cart_contents[ $cart_item_key ];
					} else {
						$cart_restored_item = WC()->cart->cart_contents[ $cart_item_key ];
					}
					$restored_price = 0;
					if ( isset( $cart_restored_item['line_subtotal'] ) && isset( $cart_restored_item['line_subtotal_tax'] ) ) {
						$restored_price = $cart_restored_item['line_subtotal'] + $cart_restored_item['line_subtotal_tax'];
					}
					$rule_gift_items = self::scan_rule( 'atc', $product_id, $quantity, $restored_price, array( 'single_cart_data' => $cart_restored_item ) );
					break;
				default:
					$rule_gift_items = self::scan_rule( 'atc' );
					break;
			}
			if ( ! empty( $rule_gift_items ) ) {
				$added_data_cart = false;
				foreach ( $rule_gift_items as $rule_gift_item ) {
					$gift_pack_qty = 1;
					if ( 'single' == $rule_gift_item['rule_id'] && in_array( $mode, array(
							'restored',
							'resolve_atc',
							'single_atc'
						) ) && $quantity > 1 ) {
						$gift_pack_qty = $quantity;
					}
					if ( empty( $rule_gift_item['gift_id'] ) || ! isset( $rule_gift_item['gift_id'] ) ) {
						continue;
					}
					if ( in_array( $mode, array(
							'resolve',
							'resolve_atc'
						) ) && ! empty( $cart_items_array ) && is_array( $cart_items_array ) ) {
						$rule_data_exits = false;
						foreach ( $cart_items_array as $cart_items_array_data ) {
							if ( $cart_items_array_data['rule_id'] == $rule_gift_item['rule_id'] ) {
								$rule_data_exits = true;
							}
						}
						if ( $rule_data_exits ) {
							if ( ! $added_data_cart ) {
								self::jagif_add_to_cart_items( $cart_items_array, $cart_item_key, $product_id, $gift_pack_qty );
							}
							$added_data_cart = true;
							continue;
						}
					}
					$gift_pack    = reset( $rule_gift_item['gift_id'] );
					$gift_pack_id = key( $rule_gift_item['gift_id'] );

					if ( is_array( $gift_pack ) && ( count( $gift_pack ) > 0 ) ) {
						for ( $i = 0; $i < count( $gift_pack ); $i ++ ) {
							$_id        = $gift_pack[ $i ]['archive_id'];
							$_qty       = $gift_pack[ $i ]['archive'];
							$pack_id    = $gift_pack_id;
							$_product   = wc_get_product( $gift_pack[ $i ]['archive_id'] );
							$jagif_type = $_product->get_type();
							if ( ! $_product || ( $_qty <= 0 ) ) {
								continue;
							}
							$_variation_id           = '';
							$item_input_variation_id = '';
							$item_input_variation    = '';
							$_variation              = array();
							if ( 'single' == $rule_gift_item['rule_id'] && isset( $cart_item_data['jagif_ids'] ) && ! empty( $cart_item_data['jagif_ids'] ) ) {
								if ( is_array( $cart_item_data['jagif_ids'] ) ) {
									foreach ( $cart_item_data['jagif_ids'] as $_input_var ) {
										if ( isset( $_input_var[ $i ] ) && ! empty( $_input_var[ $i ] ) ) {
											$item_input_variation_data = explode( '/', $_input_var[ $i ] );
											if ( 2 == count( $item_input_variation_data ) ) {
												$item_input_variation_id = $item_input_variation_data[0];
												$item_input_variation    = $item_input_variation_data[1];
											}
										}
									}
								}
							}

							if ( $_product instanceof WC_Product_Variation ) {
								if ( $_product->is_purchasable() && $_product->get_price() &&
								     ( ( $_product->get_manage_stock() && $_product->get_stock_quantity() ) || ( ! $_product->get_manage_stock() ) ) ) {
									$_variation_any = false;
									$_variation_id  = $_id;
									$_id            = $_product->get_parent_id();
									$_variation     = wc_get_product_variation_attributes( $_variation_id );
									if ( ! empty( $item_input_variation_id ) && ! empty( $item_input_variation ) &&
									     $_variation_id == $item_input_variation_id ) {
										$_variation = self::decode_variations( $item_input_variation );
									} else {
										if ( isset( $items[ $i ]['jagif_variation'] ) ) {
											$variation_data = $gift_pack[ $i ]['jagif_variation'];
											if ( is_array( $variation_data ) || is_object( $variation_data ) ) {
												$_variation = self::decode_variations( $variation_data[1] );
											}
										}
										foreach ( $_variation as $v_key => $v_val ) {
											if ( empty( $v_val ) ) {
												$_variation_any = true;
											}
										}
										if ( $_variation_any ) {
											$variable_id  = $_product->get_parent_id();
											$variable_prd = wc_get_product( $variable_id );
											$any_detail   = self::get_variation_from_any( $variable_prd, $_product, $_variation, $_variation_any );
											$_variation   = self::decode_variations( $any_detail['data'] );
										}
									}
								} else {
									continue;
								}
							}

							if ( $_product instanceof WC_Product_Variable ) {
								$product_children = $_product->get_children();
								if ( count( $product_children ) ) {
									$item_input_product        = '';
									$item_input_product_parent = '';
									if ( ! empty( $item_input_variation_id ) && ! empty( $item_input_variation ) ) {
										$item_input_product = wc_get_product( $item_input_variation_id );
										if ( $item_input_product ) {
											$item_input_product_parent = $item_input_product->get_parent_id();
										}
									}
									if ( ! empty( $item_input_product_parent ) && $_id == $item_input_product_parent ) {
										$_variation_id = $item_input_variation_id;
										$_variation    = self::decode_variations( $item_input_variation );
									} else {
										for ( $j = 0; $j < count( $product_children ); $j ++ ) {
											$variation_prd = wc_get_product( $product_children[ $j ] );
											if ( $variation_prd->is_purchasable() && $variation_prd->get_price() &&
											     ( ( $variation_prd->get_manage_stock() && $variation_prd->get_stock_quantity() ) || ( ! $variation_prd->get_manage_stock() ) ) ) {
												$_variation_any = false;
												$_variation_id  = $product_children[ $j ];
												$_variation     = wc_get_product_variation_attributes( $_variation_id );
												if ( isset( $items[ $j ]['jagif_variation'] ) ) {
													$variation_data = $gift_pack[ $j ]['jagif_variation'];
													if ( is_array( $variation_data ) || is_object( $variation_data ) ) {
														$_variation = self::decode_variations( $variation_data[1] );
													}
												}
												foreach ( $_variation as $v_key => $v_val ) {
													if ( empty( $v_val ) ) {
														$_variation_any = true;
													}
												}
												if ( $_variation_any ) {
													$any_detail = self::get_variation_from_any( $_product, $_product, $_variation, $_variation_any );
													$_variation = self::decode_variations( $any_detail['data'] );
												}
												break;
											} else {
												continue;
											}
										}
									}
								} else {
									continue;
								}
							}
							// add to cart data
							$_data = array(
								'jagif_index'      => $i,
								'jagif_qty'        => $_qty,
								'jagif_rule_id'    => $rule_gift_item['rule_id'],
								'jagif_pack_id'    => $pack_id,
								'jagif_type'       => $jagif_type,
								'jagif_parent_id'  => $mode != 'remove' ? $product_id : '',
								'jagif_parent_key' => $mode != 'remove' ? $cart_item_key : '',
							);
							if ( ! empty( $wc_cart ) ) {
								$_key = $wc_cart->add_to_cart( $_id, $_qty * $gift_pack_qty, $_variation_id, $_variation, $_data );
							} else {
								$_key = WC()->cart->add_to_cart( $_id, $_qty * $gift_pack_qty, $_variation_id, $_variation, $_data );
							}
						}
					}
				}
			}
		}

		function jagif_add_to_cart_qty( $rule_gift_items, $cart_item_key, $cart ) {
			//check cart
			if ( ! empty( $rule_gift_items ) ) {
				$cart_item_data = $cart->cart_contents[ $cart_item_key ];
				$product_id     = $cart_item_data['product_id'];
				foreach ( $rule_gift_items as $rule_gift_item ) {
					if ( empty( $rule_gift_item['gift_id'] ) || ! isset( $rule_gift_item['gift_id'] ) ) {
						continue;
					}
					$gift_pack    = reset( $rule_gift_item['gift_id'] );
					$gift_pack_id = key( $rule_gift_item['gift_id'] );

					if ( is_array( $gift_pack ) && ( count( $gift_pack ) > 0 ) ) {
						for ( $i = 0; $i < count( $gift_pack ); $i ++ ) {
							$_id        = $gift_pack[ $i ]['archive_id'];
							$_qty       = $gift_pack[ $i ]['archive'];
							$pack_id    = $gift_pack_id;
							$_product   = wc_get_product( $gift_pack[ $i ]['archive_id'] );
							$jagif_type = $_product->get_type();

							if ( ! $_product || ( $_qty <= 0 ) ) {
								continue;
							}
							$_variation_id = '';
							$_variation    = array();

							if ( $_product instanceof WC_Product_Variation ) {
								if ( $_product->is_purchasable() && $_product->get_price() &&
								     ( ( $_product->get_manage_stock() && $_product->get_stock_quantity() ) || ( ! $_product->get_manage_stock() ) ) ) {
									$_variation_any = false;
									$_variation_id  = $_id;
									$_id            = $_product->get_parent_id();
									$_variation     = wc_get_product_variation_attributes( $_variation_id );
									if ( isset( $items[ $i ]['jagif_variation'] ) ) {
										$variation_data = $gift_pack[ $i ]['jagif_variation'];
										if ( is_array( $variation_data ) || is_object( $variation_data ) ) {
											$_variation = self::decode_variations( $variation_data[1] );
										}
									}
									foreach ( $_variation as $v_key => $v_val ) {
										if ( empty( $v_val ) ) {
											$_variation_any = true;
										}
									}
									if ( $_variation_any ) {
										$variable_id  = $_product->get_parent_id();
										$variable_prd = wc_get_product( $variable_id );
										$any_detail   = self::get_variation_from_any( $variable_prd, $_product, $_variation, $_variation_any );
										$_variation   = self::decode_variations( $any_detail['data'] );
									}
								} else {
									continue;
								}
							}

							if ( $_product instanceof WC_Product_Variable ) {
								$product_children = $_product->get_children();
								if ( count( $product_children ) ) {
									for ( $j = 0; $j < count( $product_children ); $j ++ ) {
										$variation_prd = wc_get_product( $product_children[ $j ] );
										if ( $variation_prd->is_purchasable() && $variation_prd->get_price() &&
										     ( ( $variation_prd->get_manage_stock() && $variation_prd->get_stock_quantity() ) || ( ! $variation_prd->get_manage_stock() ) ) ) {
											$_variation_any = false;
											$_variation_id  = $product_children[ $j ];
											$_variation     = wc_get_product_variation_attributes( $_variation_id );
											if ( isset( $items[ $j ]['jagif_variation'] ) ) {
												$variation_data = $gift_pack[ $j ]['jagif_variation'];
												if ( is_array( $variation_data ) || is_object( $variation_data ) ) {
													$_variation = self::decode_variations( $variation_data[1] );
												}
											}
											foreach ( $_variation as $v_key => $v_val ) {
												if ( empty( $v_val ) ) {
													$_variation_any = true;
												}
											}
											if ( $_variation_any ) {
												$any_detail = self::get_variation_from_any( $_product, $_product, $_variation, $_variation_any );
												$_variation = self::decode_variations( $any_detail['data'] );
											}
											break;
										} else {
											continue;
										}
									}
								} else {
									continue;
								}
							}
							// add to cart data
							$_data = array(
								'jagif_index'      => $i,
								'jagif_qty'        => $_qty,
								'jagif_rule_id'    => $rule_gift_item['rule_id'],
								'jagif_pack_id'    => $pack_id,
								'jagif_type'       => $jagif_type,
								'jagif_parent_id'  => $product_id,
								'jagif_parent_key' => $cart_item_key,
							);
							$_key  = WC()->cart->add_to_cart( $_id, $_qty, $_variation_id, $_variation, $_data );

							if ( empty( $_key ) ) {
								// can't add the gift product
								if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'] ) ) {
									$keys = WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'];
									foreach ( $keys as $key ) {
										// remove all bundled products
										WC()->cart->remove_cart_item( $key );
									}
									WC()->cart->remove_cart_item( $cart_item_key );
									// break out of the loop
									break;
								}
							} elseif ( ! isset( WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'] ) || ! in_array( $_key, WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'], true ) ) {
								// save current key
								WC()->cart->cart_contents[ $_key ]['jagif_key'] = $_key;
								// add keys for parent
								WC()->cart->cart_contents[ $cart_item_key ]['jagif_keys'][] = $_key;

							}
						}
					}
				}
			}
		}

		public function jagif_get_cart_item_qty( $product_id, $pack_id, $jagif_qty, $parent_new_quantity, $parent_old_quantity, $max_pack_on_product, $max_pack_on_order ) {
			if ( $parent_new_quantity > $max_pack_on_product ) {
				$parent_new_quantity = $max_pack_on_product;
			}

			return $jagif_qty * $parent_new_quantity;
		}

		public function jagif_check_totals_qty_in_cart( $product_id, $pack_id, $jagif_qty, $parent_new_quantity, $parent_old_quantity, $max_pack_on_order ) {
			$check_gifts_quantity = true;
			$sum_qty_in_cart      = $this->jagif_get_total_qty_in_cart( $product_id, $pack_id );
			$qty_check            = $parent_new_quantity * $jagif_qty;
			if ( $parent_new_quantity > $parent_old_quantity && $sum_qty_in_cart >= $max_pack_on_order ) {
				return false;
			}

			return $check_gifts_quantity;
		}

		public function jagif_qty_gift_in_cart( $product_id, $get_gift_item ) {
			if ( isset( $get_gift_item ) && empty( $get_gift_item ) ) {
				return false;
			}
			$check_qty           = true;
			$max_gp_per_product  = $this->settings->get_params( 'max_gp_per_product' );
			$sum_qty_in_cart     = $this->jagif_get_total_qty_in_cart( $get_gift_item[0]['archive_id'], $get_gift_item[0]['pack_id'] );
			$gift_quantity_order = get_post_meta( $get_gift_item[0]['pack_id'], 'jagif_qty_gift_order', true ) ?? 1;
			$max_in_order        = (int) $get_gift_item[0]['archive'] * (int) $gift_quantity_order;
			$sum_qty_in_product  = 0;
			if ( $sum_qty_in_product >= $max_gp_per_product ) {
				return false;
			}
			if ( $sum_qty_in_cart >= $max_in_order ) {
				return false;
			}

			return $check_qty;
		}

		public function jagif_get_total_qty_in_cart( $product_id, $pack_id ) {
			$cart_content    = WC()->cart->cart_contents;
			$sum_qty_in_cart = 0;
			if ( isset( $cart_content ) && ! empty( $cart_content ) ) {
				foreach ( $cart_content as $cart_key => $cart_data ) {
					$qty_item = 0;
					if ( isset( $cart_data['jagif_parent_id'] ) && $cart_data['product_id'] == $product_id && $cart_data['jagif_pack_id'] == $pack_id ) {
						$qty_item = $qty_item + $cart_data['quantity'];
					}
					$sum_qty_in_cart += $qty_item;
				}
			}

			return $sum_qty_in_cart;
		}

		public function scan_qty_gift_in_cart( $pack_id, $get_gift_item ) {
			if ( isset( $get_gift_item ) && empty( $get_gift_item ) ) {
				return false;
			}
			$check_qty           = true;
			$max_gp_per_product  = $this->settings->get_params( 'max_gp_per_product' );
			$sum_qty_in_cart     = $this->jagif_get_total_qty_in_cart( $get_gift_item[0]['archive_id'], $pack_id );
			$gift_quantity_order = get_post_meta( $pack_id, 'jagif_qty_gift_order', true ) ? get_post_meta( $pack_id, 'jagif_qty_gift_order', true ) : 1;
			$max_in_order        = $get_gift_item[0]['archive'] * $gift_quantity_order;
			$sum_qty_in_product  = 0;
			if ( $sum_qty_in_product >= $max_gp_per_product ) {
				return false;
			}
			if ( $sum_qty_in_cart >= $max_in_order ) {
				return false;
			}

			return $check_qty;
		}

		public function jagif_implode_attribute( $attributes ) {
			$result = '';
			if ( is_array( $attributes ) || is_object( $attributes ) ) {
				foreach ( $attributes as $attr_k => $attr_v ) {
					if ( empty( $result ) ) {
						$result = $attr_k . '=' . $attr_v;
					} else {
						$result .= '&' . $attr_k . '=' . $attr_v;
					}
				}
			}

			return $result;
		}

		public static function get_variation_from_any( $pr_prd, $vr_id, $vr_arr, $variation_any ) {
			$vr_pre = self::get_vatiations_any( $pr_prd, $vr_id, $vr_arr );
			$vr_op  = self::get_vatiation_any_default( $vr_pre );

			if ( ! empty( $vr_op ) && $variation_any ) {
				$key_arr = array_keys( $vr_arr );
				if ( is_array( $vr_op ) || is_object( $vr_op ) ) {
					if ( count( $key_arr ) == count( $vr_op ) ) {
						$set_arr = array_combine( $key_arr, $vr_op );
					}
				} else {
					if ( count( $key_arr ) == count( $vr_op ) ) {
						$set_arr = array_combine( $key_arr, $vr_op );
					}
				}
			} else {
				$set_arr = array();
			}
			$o_s                    = self::build_variations( $set_arr );
			$o_string['data']       = $o_s['data'];
			$o_string['data_short'] = $o_s['data_short'];
			$o_string['title']      = self::build_variation_title( $set_arr );

			return $o_string;
		}

		public static function get_variation_specifically( $pr_prd, $vr_id, $vr_arr ) {
			$o_s                    = self::build_variations( $vr_arr );
			$o_string['data']       = $o_s['data'];
			$o_string['data_short'] = $o_s['data_short'];
			$o_string['title']      = self::build_variation_title( $vr_arr );

			return $o_string;
		}

		public static function build_variation_title( $vr_arr ) {
			$o = '';
			if ( is_array( $vr_arr ) || is_object( $vr_arr ) ) {
				foreach ( $vr_arr as $att_k => $att_v ) {
					$cur_key  = substr( $att_k, 10 );
					$cur_term = get_term_by( 'slug', $att_v, $cur_key );
					if ( ! empty( $cur_term ) ) {
						if ( empty( $o ) ) {
							$o = $cur_term->name;
						} else {
							$o .= ' - ' . $cur_term->name;
						}
					}
				}
			}

			return $o;
		}

		public static function build_variations( $vr_arr ) {
			$o['data']       = '';
			$o['data_short'] = '';
			if ( is_array( $vr_arr ) || is_object( $vr_arr ) ) {
				foreach ( $vr_arr as $vr_k => $vr_v ) {
					if ( empty( $o['data'] ) ) {
						$o['data']       = $vr_k . '=' . $vr_v;
						$o['data_short'] = $vr_v;
					} else {
						$o['data']       .= '&' . $vr_k . '=' . $vr_v;
						$o['data_short'] .= ',' . $vr_v;
					}
				}
			}

			return $o;
		}

		public static function get_vatiations_any( $pr_prd, $vr_id, $vr_arr ) {
			$o = [];
			foreach ( $vr_arr as $attr_k => $attr_v ) {
				if ( empty( $attr_v ) ) {
					$cur_key  = substr( $attr_k, 10 );
					$get_atts = $pr_prd->get_variation_attributes();
					if ( isset( $get_atts[ $cur_key ] ) ) {
						$o[] = $get_atts[ $cur_key ];
					}
				} else {
					$o[] = $attr_v;
				}
			}

			return $o;
		}

		public static function get_vatiation_any_default( $any_arr ) {
			if ( is_array( $any_arr ) || is_object( $any_arr ) ) {
				$o = [];
				foreach ( $any_arr as $att_arr ) {
					if ( is_array( $att_arr ) || is_object( $att_arr ) ) {
						$o[] = $att_arr[0];
					} else {
						$o[] = $att_arr;
					}
				}

				return $o;
			} else {
				return $any_arr;
			}
		}

		public static function decode_variations( $vr_arr, $mode = 0 ) {
			$o                    = '';
			$attr_array           = explode( '&', $vr_arr );
			$attr_array_formatted = [];
			foreach ( $attr_array as $attr_array_v ) {
				$attr_str_arr                             = explode( '=', $attr_array_v );
				$attr_array_formatted[ $attr_str_arr[0] ] = isset( $attr_str_arr[1] ) ? $attr_str_arr[1] : '';
			}
			if ( ! $mode ) {
				return $attr_array_formatted;
			}
			foreach ( $attr_array_formatted as $attr_k => $attr_v ) {
				$cur_key  = substr( $attr_k, 10 );
				$cur_term = get_term_by( 'slug', $attr_v, $cur_key );

				if ( ! empty( $cur_term ) ) {
					$o .= ' - ' . $cur_term->name;
				}
			}

			return $o;
		}
	}
}
if ( ! function_exists( 'jagif_dropdown_variation_attribute_options' ) ):
	function jagif_dropdown_variation_attribute_options( $args = array() ) {
		$args = wp_parse_args(
			apply_filters( 'jagif_dropdown_variation_attribute_options_args', $args ),
			array(
				'options'          => false,
				'attribute'        => false,
				'product'          => false,
				'selected'         => false,
				'name'             => '',
				'id'               => '',
				'class'            => '',
				'show_option_none' => esc_html__( 'Choose an option', 'jagif-woo-free-gift' ),
			)
		);

		// Get selected value.
		if ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {
			$selected_key     = 'attribute_' . sanitize_title( $args['attribute'] );
			if ( isset( $_REQUEST['_jagif_frontend_nonce'] ) && ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['_jagif_frontend_nonce'] ) ), 'jagif_frontend_nonce' ) ) {
				$args['selected'] = $args['product']->get_variation_default_attribute( $args['attribute'] );
			} else {
				$args['selected'] = isset( $_REQUEST[ $selected_key ] ) ? wc_clean( wp_unslash( $_REQUEST[ $selected_key ] ) ) : $args['product']->get_variation_default_attribute( $args['attribute'] );
			}
		}

		$options          = $args['options'];
		$product          = $args['product'];
		$attribute        = $args['attribute'];
		$name             = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
		$id               = $args['id'] ? $args['id'] : sanitize_title( $attribute );
		$class            = $args['class'];
		$show_option_none = false;

		if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		$html = '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';

		if ( ! empty( $options ) ) {
			if ( $product && taxonomy_exists( $attribute ) ) {
				$terms = wc_get_product_terms(
					$product->get_id(),
					$attribute,
					array(
						'fields' => 'all',
					)
				);

				foreach ( $terms as $term ) {
					if ( in_array( $term->slug, $options, true ) ) {
						$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'jagif_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
					}
				}
			} else {
				foreach ( $options as $option ) {
					// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
					$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
					$html     .= '<option value="' . esc_attr( $option ) . '" ' . esc_attr( $selected ) . '>' . esc_html( apply_filters( 'jagif_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
				}
			}
		}

		$html .= '</select>';

		echo apply_filters( 'jagif_dropdown_variation_attribute_options_html', $html, wc_clean( $args ) );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
endif;
if ( ! function_exists( 'jagif_dropdown_variation_specifically_options' ) ):
	function jagif_dropdown_variation_specifically_options( $args = array() ) {
		$args = wp_parse_args(
			apply_filters( 'jagif_dropdown_variation_specifically_options_args', $args ),
			array(
				'options'          => false,
				'attribute'        => false,
				'product'          => false,
				'name'             => '',
				'id'               => '',
				'class'            => '',
				'show_option_none' => esc_html__( 'Choose an option', 'jagif-woo-free-gift' ),
			)
		);

		// Get selected value.

		$options          = $args['options'];
		$product          = $args['product'];
		$attribute        = $args['attribute'];
		$name             = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
		$id               = $args['id'] ? $args['id'] : sanitize_title( $attribute );
		$class            = $args['class'];
		$show_option_none = false;
		if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		$html = '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';

		if ( ! empty( $options ) ) {
			$select_count = 0;
			if ( $product && taxonomy_exists( $attribute ) ) {
				$terms         = wc_get_product_terms(
					$product->get_id(),
					$attribute,
					array(
						'fields' => 'all',
					)
				);
				$options_count = count( $terms ) - 1;
				foreach ( $terms as $term ) {
					if ( is_array( $options ) || is_object( $options ) ) {
						if ( in_array( $term->slug, $options, true ) ) {
							$selected = $select_count == $options_count ? 'selected="selected"' : '';
							$select_count ++;
							$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . esc_attr( $selected ) . '>' . esc_html( apply_filters( 'jagif_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
						}
					} else {
						if ( $term->slug == $options ) {
							$selected = 'selected="selected"';
							$select_count ++;
							$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . esc_attr( $selected ) . '>' . esc_html( apply_filters( 'jagif_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
						}
					}
				}
			} else {
				$options_count = count( $options ) - 1;
				foreach ( $options as $option ) {
					// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
					$selected = $select_count == $options_count ? 'selected="selected"' : '';
					$select_count ++;
					$html .= '<option value="' . esc_attr( $option ) . '" ' . esc_attr( $selected ) . '>' . esc_html( apply_filters( 'jagif_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
				}
			}
		}

		$html .= '</select>';

		echo apply_filters( 'jagif_dropdown_variation_specifically_options_html', $html, wc_clean( $args ) );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
endif;
if ( ! function_exists( 'jagif_get_data_attributes' ) ) {
	function jagif_get_data_attributes( $attribute_name_set ) {
		$arr = [];
		foreach ( $attribute_name_set as $key => $value ) {
			array_push( $arr, $key . '=' . $value );
		}
		$data_attrs = $arr ? implode( '&', $arr ) : '';

		return $data_attrs;
	}
}
if ( ! function_exists( 'jagif_get_variation_id_available' ) ) {
	function jagif_get_variation_id_available( $gift_id, $get_gift_item ) {
		$variation_id         = '';
		$product_gift         = wc_get_product( $gift_id );
		$available_variations = $product_gift->get_available_variations();
		foreach ( $available_variations as $variation_values ) {
			if ( wc_get_product( $variation_values['variation_id'] )->is_purchasable() ) {
				$is_default_variation = false;
				foreach ( $variation_values['attributes'] as $key => $attribute_value ) {
					$attribute_name_default = str_replace( 'attribute_', '', $key );
					$default_value          = $product_gift->get_variation_default_attribute( $attribute_name_default );
					if ( $default_value == $attribute_value ) {
						$is_default_variation = true;
					} else {
						$is_default_variation = false;
						break;
					}
				}

				if ( $is_default_variation ) {
					$variation_id = $variation_values['variation_id'];
					break;
				} else {
					// if variation default out of stock
					$variation_id = $variation_values['variation_id'];
				}
			}
		}

		return $variation_id;
	}
}

if ( ! function_exists( 'jagif_check_gift_sibling' ) ) {
	function jagif_check_gift_sibling( $get_gift_item ) {
		$check_gift_sibling = '';
		$var_parent         = '';
		$variable_id        = '';
		$variation_id       = '';
		foreach ( $get_gift_item as $key_item => $gift_id ) {
			$product_gift_id = $gift_id['archive_id'] ?? '';
			$product_gift    = $product_gift_id ? wc_get_product( $product_gift_id ) : '';
			if ( $product_gift->is_type( 'variation' ) ) {
				$var_parent   = $product_gift->get_parent_id();
				$variation_id = $product_gift_id;
			} elseif ( $product_gift->is_type( 'variable' ) ) {
				$variable_id = $product_gift_id;
			}
		}
		if ( $var_parent && $variable_id ) {
			if ( $var_parent == $variable_id ) {
				$check_gift_sibling = $variation_id;
			}
		}

		return $check_gift_sibling;
	}
}

if ( ! function_exists( 'jagif_array_combinations' ) ) {
	function jagif_array_combinations( $arrays, $i = 0 ) {
		$result = [];
		foreach ( $arrays as $v1 ) {
			if ( isset( $result[ $v1['pack_id'] ] ) ) {
				if ( isset( $v1['archive_id'] ) && ! empty( $v1['archive_id'] ) ) {
					array_push( $result[ $v1['pack_id'] ], $v1 );
				}
			} else {
				$result[ $v1['pack_id'] ]['pack_id'] = $v1['pack_id'] ?? [];
				if ( isset( $v1['archive_id'] ) && ! empty( $v1['archive_id'] ) ) {
					array_push( $result[ $v1['pack_id'] ], $v1 );
				}
				unset( $result[ $v1['pack_id'] ]['pack_id'] );
			}
		}

		return $result;
	}
}