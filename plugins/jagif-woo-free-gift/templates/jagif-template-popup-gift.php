<div class="jagif-overlay"></div>
<div class='jagif-popup'>
	<h2 class="jagif-title">
		<?php esc_html_e('Choose your free gift','jagif-woo-free-gift'); ?>
	</h2>
	<div class="jagif-gifts">
		<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="jagif_add_gifts"/>
			<?php
			wp_nonce_field( 'jagif_add_free_gifts', '_jagif_nonce' );
			if ( ! empty( $jagif_free_products ) ):
				foreach ( $jagif_free_products as $product ):
					if ( empty( $product->detail ) ) {
						continue;
					}
					?>
					<div class="jagif-gift-item">
						<div class="jagif-heading">
							<input type="checkbox" class="jagif-checkbox" name="jagif_free_items[]"
							       id="jagif-item-"
							       value=""/>
							<label for="jagif-item-" class="jagif-title">
								<img src="" alt=""/>
							</label>

							<h3></h3>
						</div>
					</div>
				<?php endforeach; ?>
				<div class="jagif-actions">
					<button class="button jagif-button jagif-add-gifts">
						<?php
						esc_html_e('Add Gifts','jagif-woo-free-gift');
						?>
					</button>
					<button class="button jagif-button jagif-no-thanks" type="button">
						<?php
						esc_html_e('No Thanks','jagif-woo-free-gift');
						?>
					</button>
				</div>
			<?php endif; ?>
		</form>
	</div>
</div>