<?php
/* Admin HTML Fee Settings for Simple Product */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="efw-extra-fee-wrapper">
	<div class="efw-rules-content">
		<?php
		if ( efw_check_is_array( $rule_ids ) ) {
			foreach ( $rule_ids as $rule_id ) {
				$rule = efw_get_fee_rule( $rule_id );
				include 'global-product-fees.php';
			}
		}
		?>
	</div>
	<div class="efw-add-rule-button-wrapper">
		<button class="efw-add-rule-for-simple button"><?php esc_html_e( 'Add Rule', 'extra-fees-for-woocommerce' ); ?></button>
	</div>
</div>
<?php
