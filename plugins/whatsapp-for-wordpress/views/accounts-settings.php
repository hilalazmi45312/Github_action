<p><?php echo __( 'Enable automatically hide unavailable agents', 'ninjateam-whatsapp' ); ?></p>
<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="nta-wa-switch-control"><?php echo __( 'Hide offline agents', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div class="nta-wa-switch-control">
					<input type="checkbox" id="nta-wa-switch-hide-offline-agents" name="hideOfflineAgents" <?php checked( $option['hideOfflineAgents'], 'ON' ); ?>>
					<label for="nta-wa-switch-hide-offline-agents" class="green"></label>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<button class="button button-large button-primary wa-save"><?php esc_html_e( 'Save Changes', 'ninjateam-whatsapp' ); ?><span></span></button>
