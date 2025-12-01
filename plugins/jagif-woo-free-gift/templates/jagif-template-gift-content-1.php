<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! $get_gift_item ) {
	return;
}
if ( ! $get_gift_item['gift_id'] || ! is_array( $get_gift_item['gift_id'] ) ) {
	return;
}

$class_disable_link_gift = ! $enable_link_gift ? 'jagif-disable-link' : '';

?>
<div class="jagif-free-gift-promo-content jagif-rule-available jagif-free-gift-items <?php echo esc_attr( $class_type_1 ); ?>">
	<?php
	$qty_item_gift = 1;
	$number_packs  = count( $get_gift_item['gift_id'] );
	foreach ( $get_gift_item['gift_id'] as $pack_id => $packs ) {
		$pack_active_default = $qty_item_gift == 1 ? ' active' : '';
		$post_pack           = get_post( $pack_id );
		if ( $post_pack ) {
			$pack_name = get_the_title( $post_pack );
		}
		?>
        <div class="jagif-gifts-package jagif-gifts-package-position-<?php echo esc_attr( $qty_item_gift ); ?><?php echo esc_attr( $pack_active_default ); ?>"
             data-position="<?php echo esc_attr( $qty_item_gift ); ?>" data-pack="<?php echo esc_attr( $pack_id ) ?>">
			<?php
			if ( $number_packs > 1 ) {
				?>
                <span class="gift-pack-check">
                    <input type="radio" id="gift_pack_<?php echo esc_attr( $qty_item_gift ) ?>"
                           class="gift_pack_active gift_pack_active_<?php echo esc_attr( $pack_id ) ?>"
                           value="<?php echo esc_attr( $pack_id ) ?>"
                           name="gift_pack_active_<?php echo esc_attr( $get_gift_item['rule_id'] ) ?>">
                    <label for="gift_pack_<?php echo esc_attr( $qty_item_gift ) ?>"><?php if ( $pack_name ) {
		                    echo esc_html( $pack_name . ': ' );
	                    } else {
		                    echo esc_html__( 'Pack: ', 'jagif-woo-free-gift' );
	                    }
	                    echo esc_html( $qty_item_gift );
	                    ?></label>
                </span>
				<?php
			}

			foreach ( $packs as $key_item => $gift_id ) {

				$product_gift_id  = $gift_id['archive_id'] ?? '';
				$product_gift_qty = $gift_id['archive'] ?? '';
				$product_gift     = $product_gift_id ? wc_get_product( $product_gift_id ) : '';
				if ( $product_gift && $product_gift->is_in_stock() && $product_gift->is_purchasable() ) {
					$title        = $product_gift->get_name();
					$product_type = $product_gift->get_type();
					if ( $product_type == 'variation' ) {
						$variable_prd_id = $product_gift->get_parent_id();
						$variable_prd = wc_get_product( $variable_prd_id );
						$title = $variable_prd->get_name();
					}
					if ( $product_type == 'variable' ) {
						$lnk_variation_id    = jagif_get_variation_id_available( $product_gift_id, $packs );
						if ( empty( $lnk_variation_id ) ) continue;
						$product_variation   = wc_get_product( $lnk_variation_id );
						$lnk_attr_name       = $product_variation->get_variation_attributes();
						$lnk_data_attributes = jagif_get_data_attributes( $lnk_attr_name );
						$_product_permalink  = $product_variation->get_permalink();
						$image               = $product_variation->get_image();
					} else {
						$_product_permalink = $product_gift->get_permalink();
						$image              = $product_gift->get_image();
					}
					?>
                    <div class="jagif-free-gift-promo-item free-gift free-gift-id-<?php echo esc_attr( $product_gift_id ) ?>"
                         data-id="<?php echo esc_attr( $product_gift_id ) ?>">
                            <span class="jagif-image-gift-item item-gift">
                                <a class="free-gift-img jagif-link-to-gift "
                                   href="<?php echo esc_attr( '' == $class_disable_link_gift ? $_product_permalink : '' ) ?>" target="_blank">
                                            <?php echo wp_kses_post( $image ); ?>
                                </a>
                            </span>
                        <div class="jagif-inline item-gift">
                            <a class="jagif-gift-name jagif-link-to-gift "
                               href="<?php echo esc_attr( '' == $class_disable_link_gift ? $_product_permalink : '' ) ?>"
                               target="_blank"
                               title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $title ); ?>
                            </a>
							<?php
							if ( $product_type == 'variable' ) {
								$variation_id         = '';
								$attributes           = $product_gift->get_variation_attributes();
								$available_variations = $product_gift->get_available_variations();
								$variation_count      = count( $product_gift->get_children() );
								$default_attributes   = $product_gift->get_default_attributes();
								$variation_id         = jagif_get_variation_id_available( $product_gift_id, $packs );
								$variations_json      = wp_json_encode( $available_variations );
								$variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
								$product_id           = $product_gift->get_id();
								$product_name         = $product_gift->get_name();
								$attribute_name_set   = wc_get_product( $variation_id )->get_variation_attributes();
								$data_attributes      = jagif_get_data_attributes( $attribute_name_set );
								$name_variation       = $attribute_name_set ? implode( ', ', $attribute_name_set ) : '';
								$data_variation       = $attribute_name_set ? implode( ',', $attribute_name_set ) : '';

								$variation_any = false;
								foreach ( $attribute_name_set as $attr_k => $attr_v ) {
									if ( empty( $attr_v ) ) {
										$variation_any = true;
									}
								}
								if ( ! $variation_any ) {
									$name_variation      = VIJAGIF_WOO_FREE_GIFT_Function::build_variation_title( $attribute_name_set );
                                }
								$products_children = $product_gift->get_children();
								if ( count( $products_children ) ) {
									for ( $i = 0; $i < count( $products_children ); $i ++ ) {
										$variation_prd = wc_get_product( $products_children[ $i ] );
										if ( $variation_prd->is_purchasable() && $variation_prd->get_price() &&
										     ( ( $variation_prd->get_manage_stock() && $variation_prd->get_stock_quantity() ) || ( ! $variation_prd->get_manage_stock() ) ) ) {
											if ( $variation_any ) {
												$any_detail      = VIJAGIF_WOO_FREE_GIFT_Function::get_variation_from_any( $product_gift, $variation_prd, $attribute_name_set, $variation_any );
												$data_attributes = $any_detail['data'];
												$name_variation  = $any_detail['title'];
												$data_variation  = $any_detail['data_short'];
											}
										}
									}
								}

								?>
                                <div class="jagif_option_wrap">
                                    <div class="jagif-open-popup-choose-var"
                                         data-attrs="<?php echo esc_attr( $data_attributes ); ?>">
                                        <span class="jagif-var-name"
                                              data-value="<?php echo esc_attr( $data_variation ); ?>">
                                                        <?php echo esc_html( $name_variation ); ?>
                                                    </span>
                                        <i class="pencil alternate icon"></i>
                                    </div>
                                    <div class="item-gift-qty"><?php echo esc_html__( 'Qty: ', 'jagif-woo-free-gift' ) . esc_html( $product_gift_qty ) ?></div>
                                    <div class="jagif-variation-dropdown">
                                        <div class="jagif-pv-content">
                                            <div class="var-form jagif-variation-form"
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
																	<?php echo wp_kses_post( wc_attribute_label( $attribute_name ) ); ?>
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
                                                <span title="<?php esc_html_e( 'Close (Esc)', 'jagif-woo-free-gift' ); ?>"
                                                      type="button"
                                                      class="jagif-popup-var-close-wrap">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                         class="feather feather-x">
                                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                                    </svg>
                                                </span>
                                                <div class="is-out-of-stock jagif-disabled"><?php esc_html_e( 'Out of stock', 'jagif-woo-free-gift' ); ?></div>
                                                <div class="is-variation-exists jagif-disabled"><?php esc_html_e( 'Is exists', 'jagif-woo-free-gift' ); ?></div>
                                                <div class="jagif-btn-choose">
                                                    <button type="button"
                                                            class="jagif-dropdown-close-var button alt"
                                                            data-permalink="<?php echo esc_attr( $_product_permalink ); ?>"
                                                            data-product_id="<?php echo esc_attr( absint( $product_id ) ); ?>"><?php esc_html_e( 'Change', 'jagif-woo-free-gift' ); ?></button>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="jagif_id_gift_item" class="jagif_id_gift_item"
                                       value="<?php echo esc_attr( absint( $variation_id ) ) ?>">
							<?php } elseif ( $product_type == 'variation' ) {
								$product_id = $product_gift->get_id();
								$product_name = $product_gift->get_name();
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
								} else {
									$any_detail      = VIJAGIF_WOO_FREE_GIFT_Function::get_variation_specifically( $variable_prd, $product_gift, $v_attributes );
									$data_attributes = $any_detail['data'];
									$name_variation  = $any_detail['title'];
									$data_variation  = $any_detail['data_short'];
                                }
								?>
                                <div class="jagif_option_wrap">
									<?php if ( $v_any ) { ?>
                                        <div class="jagif-open-popup-choose-var jagif-variation-choose-var"
                                             data-attrs="<?php echo esc_attr( $data_attributes ); ?>">
                                            <span class="jagif-var-name"
                                              data-value="<?php echo esc_attr( $data_variation ); ?>">
                                                        <?php echo esc_html( $name_variation ); ?>
                                                    </span>
                                            <i class="pencil alternate icon"></i>
                                        </div>
									<?php } else { ?>
                                        <div class="jagif-variation-choose-var"
                                             data-attrs="<?php echo esc_attr( $data_attributes ); ?>">
                                            <span class="jagif-var-name"
                                                  data-value="<?php echo esc_attr( $data_variation ); ?>">
                                                        <?php echo esc_html( $name_variation ); ?>
                                                    </span>
                                        </div>
                                    <?php } ?>
                                    <div class="item-gift-qty"><?php echo esc_html__( 'Qty: ', 'jagif-woo-free-gift' ) . esc_html( $product_gift_qty ) ?></div>
									<?php if ( $v_any ) { ?>
                                        <div class="jagif-variation-dropdown">
                                            <div class="jagif-pv-content">
                                                <div class="var-form jagif-variation-form"
                                                     data-product_id="<?php echo esc_attr( absint( $product_id ) ); ?>"
                                                     data-product_name="<?php echo esc_attr( $product_name ); ?>"
                                                     data-variation_count="<?php echo esc_attr( 1 ); ?>"
                                                     data-product_variations="<?php echo esc_attr( '$variations_attr' ); ?>">
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
                                                    <span title="<?php esc_html_e( 'Close (Esc)', 'jagif-woo-free-gift' ); ?>"
                                                          type="button"
                                                          class="jagif-popup-var-close-wrap">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                         class="feather feather-x">
                                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                                    </svg>
                                                </span>
                                                    <div class="jagif-btn-choose">
                                                        <button type="button"
                                                                class="jagif-dropdown-close-var button alt"
                                                                data-permalink="<?php echo esc_attr( $_product_permalink ); ?>"
                                                                data-product_id="<?php echo esc_attr( absint( $product_id ) ); ?>"><?php esc_html_e( 'Change', 'jagif-woo-free-gift' ); ?></button>
                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <input type="hidden" name="jagif_id_gift_item" class="jagif_id_gift_item"
                                       value="<?php echo esc_attr( absint( $product_id ) ) ?>">
							<?php } else {
								?>
                                <div class="item-gift-qty"><?php echo esc_html__( 'Qty: ', 'jagif-woo-free-gift' ) . esc_html( $product_gift_qty ) ?></div>
                                <input type="hidden" name="jagif_id_gift_item" class="jagif_id_gift_item"
                                       value="<?php echo esc_attr( absint( $product_gift_id ) ) ?>">
								<?php
							}
							?>
                        </div>
                    </div>
				<?php }
			}
			?>
        </div>
		<?php
		$qty_item_gift ++;
	}
	?>
</div>