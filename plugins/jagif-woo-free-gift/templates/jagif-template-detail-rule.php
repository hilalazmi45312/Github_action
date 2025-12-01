<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
wp_nonce_field( 'jagif_save_rule', '_jagif_rule_nonce' );
global $post;
$settings = VIJAGIF_WOO_FREE_GIFT_Data::get_instance();

$jagif_rule = get_post_meta( $post->ID, 'jagif-woo_free_gift_rules', true );
$jagif_rule_enable = get_post_meta( $post->ID, 'jagif-woo_free_gift_enable', true );
$jagif_rule_description = get_post_meta( $post->ID, 'jagif-woo_free_gift_description', true );

if ( isset( $jagif_rule['jagif_conditions'] ) && ! empty( $jagif_rule['jagif_conditions'] ) ) {
	$jagif_conditions = $jagif_rule['jagif_conditions'];
} else {
	$jagif_conditions = '';
}
?>
<div class="vi-ui form fluid styled accordion jagif-accordion-wrap-init" id="jagif-detail-rule">
    <div class="fields jagif-enable-rules">
        <div class="four wide field">
            <label class="vi-ui fluid label"><?php esc_html_e( 'Enable', 'jagif-woo-free-gift' ); ?></label>
        </div>
        <div class="vi-ui thirteen wide field">
            <div class="vi-ui field toggle checkbox">
                <input id="jagif_input_enable_gift" type="checkbox" <?php checked( $jagif_rule_enable , 'true' ) ?> tabindex="0" class="hidden"
                       value="true" name="jagif_input_enable_gift">
                <label for="jagif_input_enable_gift"></label>
            </div>
        </div>
    </div>
    <div class="title jagif-title-pack active">
        <i class="dropdown icon"></i>
		<?php esc_html_e( 'Gift pack', 'jagif-woo-free-gift' ); ?>
    </div>
    <div class="content jagif-content-pack active">
        <div class="field jagif-rule-pack-cond-wrap">
            <div class="field jagif-rule-pack-wrap">
                <select class="jagif-input-search-gift" id="jagif-input-search-gift"
                        name="jagif_input_search_gift[]" multiple>
					<?php
					if (
						isset( $jagif_rule['jagif_input_search_gift'] ) &&
						is_array( $jagif_rule['jagif_input_search_gift'] ) &&
						! empty( $jagif_rule['jagif_input_search_gift'] )
					) {
						foreach ( $jagif_rule['jagif_input_search_gift'] as $gift_id ) {
							$product = wc_get_product( $gift_id );
							if ( $product && $product->is_in_stock() ) {
								$title = $product->get_name();
								?>
                                <option value="<?php echo esc_attr( $gift_id ); ?>"
                                        selected="selected">
									<?php echo esc_html( $title . '( ID: ' . $gift_id . ' )' ); ?></option>
								<?php
							}
						}
					}
					?>
                </select>
                <p class="jagif-description">
					<?php echo sprintf( '%s %s %s',
						esc_html( 'If you don\'t have a gift pack yet, you need to create a new' ),
						sprintf( '<a href="%s" target="_blank">%s</a>',
							esc_url( 'post-new.php?post_type=product&jagif_type=gp' ),
							esc_html( 'gift pack' ) ),
						esc_html( 'to be found and add into here.' ) );
					?>

                </p>
            </div>
        </div>
    </div>
    <!-- Rule-->
    <div class="title jagif-title-rules active">
        <i class="dropdown icon"></i>
		<?php esc_html_e( 'Rule', 'jagif-woo-free-gift' ); ?>
    </div>
    <div class="content jagif-content-rules active">
        <div class="field jagif-rule-cond-wrap">
            <div class="field jagif-rule-wrap jagif-root-rule-wrap">
				<?php
				if ( is_array( $jagif_conditions ) && count( $jagif_conditions ) > 0 ) {
					$count_condition = 0;
					foreach ( $jagif_conditions as $condition_item ) {
						wc_get_template( 'jagif-template-type-rule.php',
							array(
								'rule_type'  => $condition_item['type'],
								'rule_value' => $condition_item['value'],
								'rule_index' => $count_condition,
							),
							'',
							VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
						$count_condition ++;
					}
				} else {
					wc_get_template( 'jagif-template-type-rule.php',
						array(
							'rule_type'  => 'in_product',
							'rule_value' => '',
							'rule_index' => 0,
						),
						'',
						VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
                }
				?>
            </div>
            <span class="vi-ui positive mini button jagif-add-condition-btn">
                  <?php esc_html_e( 'Add Conditions(AND)', 'jagif-woo-free-gift' ); ?>
            </span>
        </div>
    </div>
    <div class="title jagif-description-title-pack active">
        <i class="dropdown icon"></i>
		<?php esc_html_e( 'Description', 'jagif-woo-free-gift' ); ?>
    </div>
    <div class="content jagif-description-pack active">
        <div class="field jagif-description-pack-cond-wrap">
            <div class="field jagif-description-pack-wrap">
                <textarea id="jagif_input_description_gift" rows="4" cols="50" tabindex="0" class="jagif-input-description-gift"
                       value="<?php echo esc_attr( $jagif_rule_description ) ?>" name="jagif_input_description_gift"><?php echo wp_kses_post( $jagif_rule_description ) ?></textarea>
            </div>
        </div>
        <div class="field jagif-description-premium-container-wrap">
            <div class="field jagif-description-premium-wrap">
                <label for="woo-stcr-checkout-countdown-id-on-checkout-page"><?php esc_html_e( 'Description type',
			            'jagif-woo-free-gift' ) ?></label>
                <a class="vi-ui button yellow" href="https://1.envato.market/15YMmg" target="_blank">
		            <?php esc_html_e( 'Unlock This Feature', 'jagif-woo-free-gift' ); ?>
                </a>
                <p class="description">
		            <?php esc_html_e( 'Fixed text with replace shortcode or Auto-generated',
			            'jagif-woo-free-gift' ) ?>
                </p>
            </div>
        </div>
    </div>
</div>
<div id="jagif-rule-template" class="vi-ui form jagif-hidden">
	<?php
	wc_get_template( 'jagif-template-type-rule.php',
		array(
			'rule_type'  => 'in_product',
			'rule_value' => '',
			'rule_index' => 0,
		),
		'',
		VIJAGIF_WOO_FREE_GIFT_TEMPLATES );
	?>
</div>