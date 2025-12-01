<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! $get_gift_item ) {
	return;
}
if ( isset( $eligibility ) ) {
	$eligibility = false;
}
if ( ! $get_gift_item['gift_id'] || ! is_array( $get_gift_item['gift_id'] ) ) {
	return;
}

$class_disable_link_gift = ! $enable_link_gift ? 'jagif-disable-link' : '';

?>
<div class="jagif-free-gift-items <?php echo esc_attr( $class_type_1 ); ?>">
    <div class="jagif-eligibility-notice"><?php echo wp_kses_post( $get_gift_item['message'] ) ?></div>
	<?php
	$qty_item_gift = 1;
	$number_packs  = count( $get_gift_item['gift_id'] );
	foreach ( $get_gift_item['gift_id'] as $pack_id => $packs ) {
		$pack_active_default = $qty_item_gift == 1 ? ' active' : '';
		$post_pack = get_post( $pack_id );
		if ( $post_pack ) {
			$pack_name = get_the_title( $post_pack );
		}
		?>
		<div class="jagif-gifts-package jagif-gifts-package-position-<?php echo esc_attr( $qty_item_gift ); ?><?php echo esc_attr($pack_active_default); ?>" data-position="<?php echo esc_attr( $qty_item_gift ); ?>">
			<?php
			if ( $number_packs > 1 ) {
				?>
				<span class="gift-pack-check">
                    <label for="gift_pack_<?php echo esc_attr( $qty_item_gift ) ?>"><?php if ( $pack_name ) {
		                    echo esc_html( $pack_name . ': ' );
	                    } else {
		                    echo esc_html__( 'Pack: ', 'jagif-woo-free-gift' );
	                    } echo esc_html( $qty_item_gift );
	                    ?></label>
                </span>
				<?php
			}

			foreach ( $packs as $key_item => $gift_id ) {

				$product_gift_id  = $gift_id['archive_id'] ? $gift_id['archive_id'] : '';
				$product_gift_qty = $gift_id['archive'] ? $gift_id['archive'] : '';
				$product_gift     = $product_gift_id ? wc_get_product( $product_gift_id ) : '';
				if ( $product_gift && $product_gift->is_in_stock() && $product_gift->is_purchasable() ) {
					$title        = $product_gift->get_name();
					$product_type = $product_gift->get_type();
					if ( $product_type == 'variable' ) {
						$lnk_variation_id    = jagif_get_variation_id_available( $product_gift_id, $packs );
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
							<div class="item-gift-qty"><?php echo esc_html__( 'Qty: ', 'jagif-woo-free-gift' ) . esc_html( $product_gift_qty ) ?></div>
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