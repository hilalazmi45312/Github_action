<?php
/**
 * Webtoffee Product feed unintall feedback
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WT_ProductFeed_Uninstall_Feedback' ) ) :

	/**
	 * Class for catch Feedback on uninstall
	 */
	class WT_ProductFeed_Uninstall_Feedback {
		/**
		 * Construvtor
		 */
		public function __construct() {
			add_action( 'admin_footer', array( $this, 'deactivate_scripts' ) );
			add_action( 'wp_ajax_productfeed_submit_uninstall_reason', array( $this, 'send_uninstall_reason' ) );
		}
		/**
		 * Uninstall reason
		 *
		 * @return array
		 */
		private function get_uninstall_reasons() {

			$reasons = array(
				array(
					'id' => 'could-not-understand',
					'text' => __(
						'I couldn\'t understand how to make it work',
						'product-feed-woocommerce'
					),
					'type' => 'textarea',
					'placeholder' => __(
						'Would you like us to assist you?',
						'product-feed-woocommerce'
					),
				),
				array(
					'id' => 'found-better-plugin',
					'text' => __(
						'I found a better plugin',
						'product-feed-woocommerce'
					),
					'type' => 'text',
					'placeholder' => __(
						'Which plugin?',
						'product-feed-woocommerce'
					),
				),
				array(
					'id' => 'not-have-that-feature',
					'text' => __(
						'The plugin is great, but I need specific feature that you don\'t support',
						'product-feed-woocommerce'
					),
					'type' => 'textarea',
					'placeholder' => __(
						'Could you tell us more about that feature?',
						'product-feed-woocommerce'
					),
				),
				array(
					'id' => 'is-not-working',
					'text' => __(
						'The plugin is not working',
						'product-feed-woocommerce'
					),
					'type' => 'textarea',
					'placeholder' => __(
						'Could you tell us a bit more whats not working?',
						'product-feed-woocommerce'
					),
				),
				array(
					'id' => 'looking-for-other',
					'text' => __(
						'It\'s not what I was looking for',
						'product-feed-woocommerce'
					),
					'type' => 'textarea',
					'placeholder' => 'Could you tell us a bit more?',
				),
				array(
					'id' => 'did-not-work-as-expected',
					'text' => __(
						'The plugin didn\'t work as expected',
						'product-feed-woocommerce'
					),
					'type' => 'textarea',
					'placeholder' => __(
						'What did you expect?',
						'product-feed-woocommerce'
					),
				),
				array(
					'id' => 'other',
					'text' => __(
						'Other',
						'product-feed-woocommerce'
					),
					'type' => 'textarea',
					'placeholder' => __(
						'Could you tell us a bit more?',
						'product-feed-woocommerce'
					),
				),
			);

			return $reasons;
		}
		/**
		 * Script render for de-activate popup
		 *
		 * @global string $pagenow
		 * @return void
		 */
		public function deactivate_scripts() {

			global $pagenow;
			if ( 'plugins.php' != $pagenow ) {
				return;
			}
			$reasons = $this->get_uninstall_reasons();
			?>
			<div class="productfeed-modal" id="productfeed-productfeed-modal">
				<div class="productfeed-modal-wrap">
					<div class="productfeed-modal-header">
						<h3>
						<?php
						echo esc_html(
							'If you have a moment, please let us know why you are deactivating:',
							'product-feed-woocommerce'
						);
						?>
							</h3>
					</div>
					<div class="productfeed-modal-body">
						<ul class="reasons">
							<?php foreach ( $reasons as $reason ) { ?>
								<li data-type="<?php echo esc_attr( $reason['type'] ); ?>" data-placeholder="<?php echo esc_attr( $reason['placeholder'] ); ?>">
									<label><input type="radio" name="selected-reason" value="<?php echo esc_html( $reason['id'] ); ?>"> <?php echo esc_html( $reason['text'] ); ?></label>
								</li>
							<?php } ?>
						</ul>
						<div class="wt-uninstall-feedback-privacy-policy">
							<?php esc_html_e( 'We do not collect any personal data when you submit this form. It\'s your feedback that we value.', 'product-feed-woocommerce' ); ?>
							<a href="https://www.webtoffee.com/privacy-policy/" target="_blank"><?php esc_html_e( 'Privacy Policy', 'product-feed-woocommerce' ); ?></a>
						</div>
					</div>
					<div class="productfeed-modal-footer">
						<a href="#" class="dont-bother-me">
						<?php
						echo esc_html(
							'I rather wouldn\'t say',
							'product-feed-woocommerce'
						);
						?>
															</a>
						<button class="button-primary productfeed-model-submit">
						<?php
						echo esc_html(
							'Submit & Deactivate',
							'product-feed-woocommerce'
						);
						?>
																				</button>
						<button class="button-secondary productfeed-model-cancel">
						<?php
						echo esc_html(
							'Cancel',
							'product-feed-woocommerce'
						);
						?>
																					</button>
					</div>
				</div>
			</div>

			<style type="text/css">
				.productfeed-modal {
					position: fixed;
					z-index: 99999;
					top: 0;
					right: 0;
					bottom: 0;
					left: 0;
					background: rgba(0,0,0,0.5);
					display: none;
				}
				.productfeed-modal.modal-active {display: block;}
				.productfeed-modal-wrap {
					width: 50%;
					position: relative;
					margin: 10% auto;
					background: #fff;
				}
				.productfeed-modal-header {
					border-bottom: 1px solid #eee;
					padding: 8px 20px;
				}
				.productfeed-modal-header h3 {
					line-height: 150%;
					margin: 0;
				}
				.productfeed-modal-body {padding: 5px 20px 20px 20px;}
				.productfeed-modal-body .input-text,.productfeed-modal-body textarea {width:75%;}
				.productfeed-modal-body .reason-input {
					margin-top: 5px;
					margin-left: 20px;
				}
				.productfeed-modal-footer {
					border-top: 1px solid #eee;
					padding: 12px 20px;
					text-align: right;
				}
			</style>
			<script type="text/javascript">
				(function ($) {
					$(function () {
						var modal = $('#productfeed-productfeed-modal');
						var deactivateLink = '';
						$('#the-list').on('click', 'a.productfeed-deactivate-link', function (e) {
							e.preventDefault();
							modal.addClass('modal-active');
							deactivateLink = $(this).attr('href');
							modal.find('a.dont-bother-me').attr('href', deactivateLink).css('float', 'left');
						});
						modal.on('click', 'button.productfeed-model-cancel', function (e) {
							e.preventDefault();
							modal.removeClass('modal-active');
						});
						modal.on('click', 'input[type="radio"]', function () {
							var parent = $(this).parents('li:first');
							modal.find('.reason-input').remove();
							var inputType = parent.data('type'),
									inputPlaceholder = parent.data('placeholder'),
									reasonInputHtml = '<div class="reason-input">' + (('text' === inputType) ? '<input type="text" class="input-text" size="40" />' : '<textarea rows="5" cols="45"></textarea>') + '</div>';

							if (inputType !== '') {
								parent.append($(reasonInputHtml));
								parent.find('input, textarea').attr('placeholder', inputPlaceholder).focus();
							}
						});

						modal.on('click', 'button.productfeed-model-submit', function (e) {
							e.preventDefault();
							var button = $(this);
							if (button.hasClass('disabled')) {
								return;
							}
							var $radio = $('input[type="radio"]:checked', modal);
							var $selected_reason = $radio.parents('li:first'),
							$input = $selected_reason.find('textarea, input[type="text"]');

							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'productfeed_submit_uninstall_reason',
									_wpnonce: '<?php echo wp_kses_post( wp_create_nonce( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ); ?>',
									reason_id: (0 === $radio.length) ? 'none' : $radio.val(),
									reason_info: (0 !== $input.length) ? $input.val().trim() : ''
								},
								beforeSend: function () {
									button.addClass('disabled');
									button.text('Processing...');
								},
								complete: function () {
									window.location.href = deactivateLink;
								}
							});
						});
					});
				}(jQuery));
			</script>
			<?php
		}
		/**
		 * Send uninstall feedback
		 *
		 * @global type $wpdb
		 */
		public function send_uninstall_reason() {

			// phpcs:ignore Nonce and user role check handled by check_write_access method.
			if ( ! Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
				return;
			}

			global $wpdb;

			$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
			if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				wp_send_json_error();
			}
			if ( ! isset( $_POST['reason_id'] ) ) {
				wp_send_json_error();
			}

			$data = array(
				'reason_id' => sanitize_text_field( wp_unslash( $_POST['reason_id'] ) ),
				'plugin' => 'productfeed',
				'auth' => 'productfeed_uninstall_1234#',
				'date' => gmdate( 'M d, Y h:i:s A' ),
				'url' => '',
				'user_email' => '',
				'reason_info' => isset( $_REQUEST['reason_info'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['reason_info'] ) ) ) : '',
				'software' => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
				'php_version' => phpversion(),
				'mysql_version' => $wpdb->db_version(),
				'wp_version' => get_bloginfo( 'version' ),
				'wc_version' => ( ! defined( 'WC_VERSION' ) ) ? '' : WC_VERSION,
				'locale' => get_locale(),
				'multisite' => is_multisite() ? 'Yes' : 'No',
				'productfeed_version' => WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION,

			);
			// Write an action/hook here in webtoffe to recieve the data.
			$resp = wp_remote_post(
				'https://feedback.webtoffee.com/wp-json/productfeed/v1/uninstall',
				array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => false,
					'body' => $data,
					'cookies' => array(),
				)
			);

			wp_send_json_success();
		}
	}
	new WT_ProductFeed_Uninstall_Feedback();

endif;
