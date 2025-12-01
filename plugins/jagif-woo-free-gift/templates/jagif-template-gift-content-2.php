<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! $get_gift_item ) {
	return;
}

$class_disable_link_gift = ! $enable_link_gift ? 'jagif-disable-link' : '';

?>
<div class="jagif-free-gift-promo-content-1 jagif-free-gift <?php echo esc_attr( $class_type_2 ); ?>">
	<?php
	$qty_item_gift = 0;
	$num_gift      = count( $get_gift_item );
	foreach ( $get_gift_item as $key_item => $gift_id ) {
		$product_gift_id  = $gift_id['archive_id'];
		$product_gift_qty = $gift_id['archive'];
		$product_gift     = wc_get_product( $product_gift_id );
		if ( $product_gift && $product_gift->is_in_stock() && $product_gift->is_purchasable() ) {
			$title = $product_gift->get_name();

			$product_type = $product_gift->get_type();
			if ( $product_type == 'variable' ) {
				$lnk_variation_id    = jagif_get_variation_id_available( $product_gift_id, $get_gift_item );
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
            <div class="item gift-item gift-item-receive free-gift free-gift-id-<?php echo esc_attr( $product_gift_id ) ?>"
                 data-id="<?php echo esc_attr( $product_gift_id ) ?>">
                <a class="image-gift jagif-link-to-gift <?php echo esc_attr( $class_disable_link_gift ) ?>"
                   href="<?php echo esc_url( $_product_permalink ) ?>"
                   title="<?php echo esc_attr( $title ); ?>" target="_blank">
                                <span>
                                    <div class="var-image free-gift-img">
                                        <?php echo wp_kses_post( $image ) ?>
                                        <div class="title-gift"><?php echo esc_html( 'x' . $product_gift_qty ); ?></div>
                                    </div>
                                </span>

                    <div class="name-gift">
                        <span><?php echo esc_html( $title ); ?></span>
                    </div>
                </a>
				<?php if ( $product_type == 'variable' ) {
					$variation_id         = '';
					$attributes           = $product_gift->get_variation_attributes();
					$available_variations = $product_gift->get_available_variations();
					$variation_count      = count( $product_gift->get_children() );
					$default_attributes   = $product_gift->get_default_attributes();
					$variation_id         = jagif_get_variation_id_available( $product_gift_id, $get_gift_item );
					$variations_json      = wp_json_encode( $available_variations );
					$variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
					$product_id           = $product_gift->get_id();
					$product_name         = $product_gift->get_name();
					$attribute_name_set   = wc_get_product( $variation_id )->get_variation_attributes();
					$data_attributes      = jagif_get_data_attributes( $attribute_name_set );
					$name_variation       = $attribute_name_set ? implode( ', ', $attribute_name_set ) : '';
					$data_variation       = $attribute_name_set ? implode( ',', $attribute_name_set ) : '';
					?>
                    <div class="variation">
                        <div class="jagif_option_wrap">
                            <div class="jagif-open-popup-choose-var"
                                 data-attrs="<?php echo esc_attr( $data_attributes ); ?>">
                                    <span class="jagif-var-name"
                                          data-value="<?php echo esc_attr( $data_variation ); ?>"><?php echo esc_html( $name_variation ); ?>
                                    </span>
                                <i class="pencil alternate icon"></i>
                            </div>
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
                    </div>
                    <input type="hidden" name="jagif_id_gift_item" class="jagif_id_gift_item"
                           value="<?php echo esc_attr( absint( $variation_id ) ) ?>">
				<?php } else {
					?>
                    <input type="hidden" name="jagif_id_gift_item" class="jagif_id_gift_item"
                           value="<?php echo esc_attr( absint( $product_gift_id ) ) ?>">
					<?php
				}
				?>
            </div>


		<?php }
		if ( $num_gift > 1 ) {
			if ( $qty_item_gift > 0 ) {
				continue;
			}
			?>
            <div class="operator">
                <svg enable-background="new 0 0 10 10" viewBox="0 0 10 10" x="0" y="0"
                     class="jagif-svg-icon icon-plus-sign">
                    <polygon
                            points="10 4.5 5.5 4.5 5.5 0 4.5 0 4.5 4.5 0 4.5 0 5.5 4.5 5.5 4.5 10 5.5 10 5.5 5.5 10 5.5"></polygon>
                </svg>
            </div>
			<?php
		}
		$qty_item_gift ++;
	} ?>
</div>
