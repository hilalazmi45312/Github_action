<?php
/**
 * The markup for a new set of conditions
 *
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="product-extra-conditional-row new-conditional-row">
	<div class="product-extra-field-third">
		<select class="pewc-condition-field pewc-condition-select" name="" id="" data-group-id="" data-item-id="" data-condition-id="">
		<?php
		// retrieve all attributes, this gets removed on page load
		$all_attributes = wc_get_attribute_taxonomies();
		$all_attributes_array = array();
		$all_attributes_string = '';
		if ( ! empty( $all_attributes ) ) {

			foreach ( $all_attributes as $cond_attribute ) {
				$key = 'pa_'.$cond_attribute->attribute_name;
				$attr_terms = get_terms( $key );

				if ( ! empty( $attr_terms ) && ! is_wp_error( $attr_terms ) ) {
					$all_attributes_string .= '<option data-type="attribute" value="' . esc_attr( $key ) . '">' . esc_html( $cond_attribute->attribute_label ) . '</option>';
					// save the attribute terms in an array that can be used by global add-ons
					foreach ( $attr_terms as $index => $attr_term ) {
						$all_attributes_array[$key][$index] = array(
							'id' => $attr_term->term_id,
							'value' => $attr_term->name,
							'slug' => $attr_term->slug
						);
					}
				}
			}
			if ( ! empty( $all_attributes_string ) ) {
				echo '<optgroup id="pewc-all-attributes-optgroup" label="Attributes">';
				echo $all_attributes_string;
				echo '</optgroup>';
			}
		}
		?>
		</select>
		<input type="hidden" class="pewc-hidden-field-type" name="" id="" value="">
	</div>
	<div class="product-extra-field-sixth">
		<select class="pewc-condition-rule pewc-condition-select" name="" id="" data-group-id="" data-item-id="" data-condition-id="">
			<?php $rules = pewc_get_rules();
			foreach( $rules as $key=>$value ) {
				echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
			} ?>
		</select>
	</div>
	<div class="product-extra-field-half product-extra-field-last pewc-condition-value-field">
		<span class="remove-condition pewc-action"><?php _e( 'Remove', 'pewc' ); ?></span>
	</div>
</div><!-- .new-conditional-row -->

<div class="new-condition-value-field">
	<input class="pewc-condition-value pewc-input-text" type="text" name="" id="" data-group-id="" data-item-id="" data-condition-id="" value="">
	<input class="pewc-condition-value pewc-input-number" type="number" step="<?php echo apply_filters( 'pewc_condition_value_step', 1, 0 ); ?>" name="" id="" data-group-id="" data-item-id="" data-condition-id="" value="">
	<select class="pewc-condition-value pewc-value-select" name="" id="" data-group-id="" data-item-id="" data-condition-id=""></select>
	<input class="pewc-condition-value pewc-value-checkbox" type="hidden" name="" id="" data-group-id="" data-item-id="" data-condition-id="">
	<span class="pewc-checked-placeholder"><?php _e( 'Checked', 'pewc' ); ?></span>
</div>

<?php
if ( ! empty( $all_attributes_array ) ) {
	echo '<input type="hidden" id="pewc_all_attributes_json" value="' . esc_attr( json_encode( $all_attributes_array ) ) . '">';
}

// 3.22.0
$all_roles_array = pewc_get_all_roles();
if ( ! empty( $all_roles_array ) ) {
	echo '<input type="hidden" id="pewc_all_roles_json" value="' . esc_attr( json_encode( $all_roles_array ) ) . '">';
}
?>
