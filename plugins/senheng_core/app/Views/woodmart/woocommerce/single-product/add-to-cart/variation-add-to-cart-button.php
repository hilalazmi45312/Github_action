<?php
/**
 * Variable product add to cart button
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<div class="custom-cart-wrapper">
		<div class="custom-cart-actions">
			<div class="quantity-selector no-variation">
				<select name="quantity" class="custom-qty-dropdown" id="quantity_<?php echo esc_attr( $product->get_id() ); ?>" disabled="disabled">
					<?php
					// Default range for variable products (will be updated by JavaScript based on variation stock)
					// Initially show 0 since no variation is selected yet
					echo '<option value="0" disabled selected>0</option>';
					?>
				</select>
				<!-- Hidden WooCommerce quantity input for validation -->
				<?php
				do_action( 'woocommerce_before_add_to_cart_quantity' );

				woocommerce_quantity_input(
					array(
						'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
						'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
						'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
						'classes'     => 'custom-qty-dropdown-hidden',
						'custom_attributes' => array(
							'data-no-validation' => 'true',
						),
					)
				);

				do_action( 'woocommerce_after_add_to_cart_quantity' );
				?>
			</div>
            <button type="submit" class="custom-add-to-basket-btn single_add_to_cart_button button alt wc-variation-selection-needed<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" data-default-text="<?php echo esc_attr__( 'Add to Cart', 'woocommerce' ); ?>" data-default-subtext="">
                <span><?php echo esc_html__( 'Select Option', 'woocommerce' ); ?></span>
            </button>
		</div>
	</div>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
