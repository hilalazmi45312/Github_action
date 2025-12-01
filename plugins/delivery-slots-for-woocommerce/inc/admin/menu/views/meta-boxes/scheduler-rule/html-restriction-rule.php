<?php
/**
 * Scheduler rule panel - Restriction rule.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
$name = 'dey_restriction_rule_groups[{{data.group_id}}][{{data.rule_id}}]';
?>
<div class='dey-restriction-rule-wrapper dey-rule-wrapper' data-rule_id='{{data.rule_id}}'>
	<p class='dey-restriction-rule-types-field'>
		<select name="<?php echo esc_attr( $name ); ?>[rule_type]" class='dey-restriction-rule-type'>
			<?php foreach ( dey_get_scheduler_rule_restriction_rule_options() as $rule_option_id => $rule_option_name ) : ?>
				<option value='<?php echo esc_attr( $rule_option_id ); ?>'><?php echo esc_html( $rule_option_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>

	<p class='dey-restriction-rule-type-field dey-restriction-rule-product-type-field'>
		<select name="<?php echo esc_attr( $name ); ?>[product_type]" class='dey-restriction-rule-product-type'>
			<?php foreach ( dey_restriction_rule_product_type_options() as $product_option_id => $product_option_name ) : ?>
				<option value='<?php echo esc_attr( $product_option_id ); ?>'><?php echo esc_html( $product_option_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>

	<p class='dey-restriction-rule-type-field dey-restriction-rule-user-type-field'>
		<select name="<?php echo esc_attr( $name ); ?>[user_type]" class='dey-restriction-rule-user-type'>
			<?php foreach ( dey_restriction_rule_user_type_options() as $user_option_id => $user_option_name ) : ?>
				<option value='<?php echo esc_attr( $user_option_id ); ?>'><?php echo esc_html( $user_option_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class='dey-restriction-rule-type-field dey-restriction-rule-order-type-field'>
		<select class='dey-restriction-rule-order-type' name="<?php echo esc_attr( $name ); ?>[order_type]">
			<?php foreach ( dey_get_restriction_rule_order_type_options() as $order_type_id => $order_type_name ) : ?>
				<option value="<?php echo esc_attr( $order_type_id ); ?>"><?php echo esc_html( $order_type_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class='dey-restriction-rule-field dey-restriction-rule-products-field'>
		<?php
		dey_select2_html(
			array(
				'name'        => $name . '[products]',
				'list_type'   => 'products',
				'action'      => 'dey_json_search_products_and_variations',
				'options'     => array(),
				'placeholder' => __( 'Select products', 'delivery-slots-for-woocommerce' ),
			)
		);
		?>
	</p>

	<p class='dey-restriction-rule-field dey-restriction-rule-product-types-field'>
		<select name="<?php echo esc_attr( $name ); ?>[product_types][]" class='dey_select2' multiple='multiple' data-placeholder='<?php esc_attr_e( 'Select product types', 'delivery-slots-for-woocommerce' ); ?>'>
			<?php foreach ( wc_get_product_types() as $product_type_id => $product_type_name ) : ?>
				<option value='<?php echo esc_attr( $product_type_id ); ?>'><?php echo esc_html( $product_type_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>

	<p class='dey-restriction-rule-field dey-restriction-rule-categories-field'>
		<select name="<?php echo esc_attr( $name ); ?>[categories][]" class='dey_select2' multiple='multiple' data-placeholder='<?php esc_attr_e( 'Select categories', 'delivery-slots-for-woocommerce' ); ?>'>
			<?php foreach ( dey_get_wc_categories() as $category_id => $category_name ) : ?>
				<option value='<?php echo esc_attr( $category_id ); ?>'><?php echo esc_html( $category_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>

	<p class='dey-restriction-rule-field dey-restriction-rule-tags-field'>
		<select name="<?php echo esc_attr( $name ); ?>[tags][]" class='dey_select2' multiple='multiple' data-placeholder='<?php esc_attr_e( 'Select tags', 'delivery-slots-for-woocommerce' ); ?>'>
			<?php foreach ( dey_get_wc_product_tags() as $tag_id => $tag_name ) : ?>
				<option value='<?php echo esc_attr( $tag_id ); ?>'><?php echo esc_html( $tag_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>

	<p class='dey-restriction-rule-field dey-restriction-rule-country-field'>
		<select name="<?php echo esc_attr( $name ); ?>[countries][]" class='dey_select2' multiple='multiple' data-placeholder='<?php esc_attr_e( 'Select countries', 'delivery-slots-for-woocommerce' ); ?>'>
			<?php foreach ( dey_get_countries() as $country_code => $country_name ) : ?>
				<option value='<?php echo esc_attr( $country_code ); ?>'><?php echo esc_html( $country_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class='dey-restriction-rule-field dey-restriction-rule-order-field dey-restriction-rule-order-price-field'>
		<input type='text' class='dey-restriction-rule-price wc_input_price' name="<?php echo esc_attr( $name ); ?>[price]" value=''/>
	</p>
	<p class='dey-restriction-rule-field dey-restriction-rule-order-field dey-restriction-rule-order-count-field'>
		<input type='number' class='dey-restriction-rule-count' name="<?php echo esc_attr( $name ); ?>[count]" value=''/>
	</p>
	<button type='button' class='button dey-remove-restriction-rule dey-remove-rule'><?php esc_html_e( 'Remove Criteria', 'delivery-slots-for-woocommerce' ); ?></button>
</div>
<?php
