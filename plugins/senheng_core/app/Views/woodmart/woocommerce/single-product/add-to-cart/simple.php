<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="custom-cart-wrapper">
			<div class="custom-cart-actions">
				<div class="quantity-selector">
					<select name="quantity" class="custom-qty-dropdown" id="quantity_<?php echo esc_attr( $product->get_id() ); ?>">
						<?php
						$min_value = apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product );
						$max_value = apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product );
						$input_value = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity();
						
						// Get stock quantity
						$stock_quantity = $product->get_stock_quantity();
						
						// Check if product is in stock
						if ( ! $product->is_in_stock() ) {
							// Out of stock - show only 0
							echo '<option value="0" disabled selected>0</option>';
						} else {
							// Maximum 5 per add-to-cart action
							$max_per_add = 5;
							
							// Determine max options based on actual stock
							if ( $stock_quantity !== null && $stock_quantity > 0 ) {
								// Use actual stock quantity, capped at max_per_add
								$max_options = min( $stock_quantity, $max_per_add );
							} elseif ( $max_value && $max_value > 0 ) {
								// If max purchase quantity is set, use that, capped at max_per_add
								$max_options = min( $max_value, $max_per_add );
							} else {
								// If no stock management and no max value, cap at max_per_add
								$max_options = $max_per_add;
							}
							
							// Ensure minimum is at least 1
							$max_options = max( $min_value, $max_options );
							
							// Generate quantity options (matching variation template structure)
							for ( $i = $min_value; $i <= $max_options; $i++ ) {
								$selected = ( $i == $input_value ) ? 'selected' : '';
								echo '<option value="' . esc_attr( $i ) . '" ' . $selected . '>' . esc_html( $i ) . '</option>';
							}
						}
						?>
					</select>
				</div>
				<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="custom-add-to-basket-btn single_add_to_cart_button">
					<span><?php echo esc_html__( 'Add to Cart', 'woocommerce' ); ?></span>
				</button>
			</div>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

	<?php else : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="custom-cart-wrapper" data-out-of-stock="true">
			<div class="custom-cart-actions">
				<div class="quantity-selector out-of-stock">
					<select name="quantity" class="custom-qty-dropdown" id="quantity_<?php echo esc_attr( $product->get_id() ); ?>" disabled>
						<option value="" disabled selected></option>
					</select>
				</div>
				<button type="button" class="custom-add-to-basket-btn single_add_to_cart_button disabled wc-variation-is-unavailable" disabled>
					<span><?php echo esc_html__( 'Out of stock', 'woocommerce' ); ?></span>
				</button>
			</div>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

	<?php endif; ?>
