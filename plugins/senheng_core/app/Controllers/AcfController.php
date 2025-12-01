<?php

class AcfController
{

    // Register ACF location rules for product variations
    public static function acf_location_rule_values_Post($choices)
    {
        $choices['product_variation'] = 'Product Variation';
        return $choices;
    }

    // public static function acf_update_value_self_pickup($value, $post_id, $field)
    // {
    //     // Consider the box "ticked" if value is truthy or a non-empty array (checkbox field types)
    //     $ticked = is_array($value) ? !empty($value) : (bool)$value;

    //     if ($ticked) {
    //         update_post_meta(
    //             $post_id,
    //             '_wc_local_pickup_plus_local_pickup_product_availability',
    //             'allowed'
    //         );
    //     } else {
    //         delete_post_meta($post_id, '_wc_local_pickup_plus_local_pickup_product_availability');
    //     }

    //     return $value;
    // }
}
