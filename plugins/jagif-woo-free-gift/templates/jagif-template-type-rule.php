<?php
if ( ! isset( $rule_type ) || ! isset( $rule_value ) ) {
	return;
}
$rule_condition = array(
	'ex_product'  => esc_attr__( 'Exclude Product', 'jagif-woo-free-gift' ),
	'in_product'  => esc_attr__( 'Include Product', 'jagif-woo-free-gift' ),
	'ex_category' => esc_attr__( 'Exclude Category', 'jagif-woo-free-gift' ),
	'in_category' => esc_attr__( 'Include Category', 'jagif-woo-free-gift' ),
);

if ( ! array_key_exists( $rule_type, $rule_condition ) ) return;

?>
<div class="vi-ui placeholder segment jagif-condition-wrap-wrap">
    <div class="fields">
        <div class="four wide field">
            <select class="vi-ui fluid dropdown jagif-rule-select "
                    name="">
				<?php
				foreach ( $rule_condition as $key => $value ) {
					?>
                    <option value="<?php echo esc_attr( $key ) ?>"
						<?php selected( $rule_type, $key ) ?> >
						<?php echo esc_html( $value ) ?>
                    </option>
				<?php } ?>
                <option disabled><?php esc_attr_e( 'Items in Cart greater than (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Items in Cart less than (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Cart subtotal price greater than (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Cart subtotal price less than (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Exclude Country (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Include Country (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Exclude Coupon (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Include Coupon (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Exclude User Role (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Include User Role (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Date (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Specific Date (Premium)', 'jagif-woo-free-gift' ) ?></option>
                <option disabled><?php esc_attr_e( 'Time (Premium)', 'jagif-woo-free-gift' ) ?></option>
            </select>
        </div>
        <div class="thirteen wide field jagif-condition-input-wrap">
            <div class="field jagif-condition-wrap jagif-condition-in-product-wrap<?php echo esc_attr( $rule_type == 'in_product' ? '' : ' jagif-hidden' ); ?>">
                <select class="jagif-search-select2 jagif-condition-in-product" data-select-type="product" name=""
                        multiple="">
					<?php if ( $rule_type == 'in_product' && ! empty( $rule_value ) && is_array( $rule_value ) ) {
						foreach ( $rule_value as $product_id ) {
							$in_product = wc_get_product( $product_id );
							if ( $in_product ) {
								$title = $in_product->get_name();
								?>
                                <option value="<?php echo esc_attr( $product_id ); ?>"
                                        selected="selected">
									<?php echo esc_html( $title . '( ID: ' . $product_id . ' )' ); ?></option>
								<?php
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field jagif-condition-wrap jagif-condition-ex-product-wrap<?php echo esc_attr( $rule_type == 'ex_product' ? '' : ' jagif-hidden' ); ?>">
                <select class="jagif-search-select2 jagif-condition-ex-product" data-select-type="product" name=""
                        multiple="">
					<?php if ( $rule_type == 'ex_product' && ! empty( $rule_value ) && is_array( $rule_value ) ) {
						foreach ( $rule_value as $product_id ) {
							$ex_product = wc_get_product( $product_id );
							if ( $ex_product ) {
								$title = $ex_product->get_name();
								?>
                                <option value="<?php echo esc_attr( $product_id ); ?>"
                                        selected="selected">
									<?php echo esc_html( $title . '( ID: ' . $product_id . ' )' ); ?></option>
								<?php
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field jagif-condition-wrap jagif-condition-in-category-wrap<?php echo esc_attr( $rule_type == 'in_category' ? '' : ' jagif-hidden' ); ?>">
                <select class="jagif-search-select2 jagif-condition-in-category" data-select-type="category" name=""
                        multiple="">
					<?php if ( $rule_type == 'in_category' && ! empty( $rule_value ) && is_array( $rule_value ) ) {
						foreach ( $rule_value as $cat_id ) {
							$condition_term = get_term_by( 'id', $cat_id, 'product_cat', OBJECT );
							?>
                            <option value="<?php echo esc_attr( $condition_term->term_id ); ?>"
                                    selected="selected">
								<?php echo esc_html( $condition_term->name . '( ID: ' . $condition_term->term_id . ' )' ); ?>
                            </option>
							<?php
						}
					}
					?>
                </select>
            </div>
            <div class="field jagif-condition-wrap jagif-condition-ex-category-wrap<?php echo esc_attr( $rule_type == 'ex_category' ? '' : ' jagif-hidden' ); ?>">
                <select class="jagif-search-select2 jagif-condition-ex-category" data-select-type="category" name=""
                        multiple="">
					<?php if ( $rule_type == 'ex_category' && ! empty( $rule_value ) && is_array( $rule_value ) ) {
						foreach ( $rule_value as $cat_id ) {
							$condition_term = get_term_by( 'id', $cat_id, 'product_cat', OBJECT );
							?>
                            <option value="<?php echo esc_attr( $condition_term->term_id ); ?>"
                                    selected="selected">
								<?php echo esc_html( $condition_term->name . '( ID: ' . $condition_term->term_id . ' )' ); ?>
                            </option>
							<?php
						}
					}
					?>
                </select>
            </div>
        </div>
        <div class="field jagif-revmove-condition-btn-wrap">
            <span class="jagif-remove-condition-btn" data-tooltip="Remove">
                 <i class="times icon"></i>
            </span>
        </div>
    </div>
</div>