<table class="form-table" id="app-design">
	<p><?php echo __( 'Setting text and style for the floating widget.', 'ninjateam-whatsapp' ); ?></p>
	<tbody>
		<tr class="setting widget-type">
			<th scope="row"><label for=""><?php echo __( 'Widget Type', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div class="setting type">
					<div class="button-group button-large" data-setting="align">
						<button class="button btn-expandable <?php echo ( $option['widgetType'] == 'expandable' ? 'active' : '' ); ?>" value="expandable" type="button">
							<?php echo __( 'Expandable', 'ninjateam-whatsapp' ); ?>
						</button>
						<button class="button btn-simple <?php echo ( $option['widgetType'] == 'simple' ? 'active' : '' ); ?>" value="simple" type="button">
							<?php echo __( 'Simple', 'ninjateam-whatsapp' ); ?>
						</button>
					</div>
					<input name="widgetType" id="widgetType" class="hidden" value="<?php echo esc_attr( $option['widgetType'] ); ?>" />
				</div>
			</td>
		</tr>

		<tr class="setting widget-text">
			<th scope="row"><label for="title"><?php echo __( 'Widget Text', 'ninjateam-whatsapp' ); ?></label></th>
			<td><input name="title" placeholder="Start a Conversation" type="text" id="title" value="<?php echo esc_attr( $option['title'] ); ?>" class="regular-text"></td>
		</tr>

		<tr class="setting btn-label">
			<th scope="row"><label for="isShowBtnLabel"><?php echo __( 'Show Widget Label', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div class="nta-wa-switch-control" style="margin-top: 5px;">
					<input type="checkbox" id="isShowBtnLabel" name="isShowBtnLabel" <?php checked( $option['isShowBtnLabel'], 'ON' ); ?>>
					<label for="isShowBtnLabel" class="green"></label>
				</div>
			</td>
		</tr>

		<tr class="setting widget-label-text <?php echo ( $option['isShowBtnLabel'] === 'ON' ? '' : 'hidden' ); ?>">
			<th scope="row"><label for="btnLabel"><?php echo __( 'Widget Label Text', 'ninjateam-whatsapp' ); ?></label></th>
			<td><input name="btnLabel" placeholder="Need Help? <strong>Chat with us</strong>" type="text" id="btnLabel" value="<?php echo esc_attr( $option['btnLabel'] ); ?>" class="regular-text"></td>
		</tr>

		<tr class="setting widget-label-width <?php echo ( $option['isShowBtnLabel'] === 'ON' ? '' : 'hidden' ); ?>">
			<th scope="row"><label for="btnLabelWidth"><?php echo __( 'Widget Label Width(px)', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div class="range" style='--min:0; --max:500; --value:<?php echo $option['btnLabelWidth']; ?>; --text-value:"<?php echo $option['btnLabelWidth']; ?>";'>
					<input id="btnLabelWidth" name="btnLabelWidth" type="range" min="0" max="500" value="<?php echo esc_attr( $option['btnLabelWidth'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))">
					<output></output>
					<div class='range__progress'></div>
				</div>
			</td>
		</tr>

		<tr class="setting text-color">
			<th scope="row"><label for="textColor"><?php echo __( 'Widget Text Color', 'ninjateam-whatsapp' ); ?></label></th>
			<td><input type="text" id="textColor" name="textColor" value="<?php echo esc_attr( $option['textColor'] ); ?>" class="textColor" data-default-color="#fff" /></td>
		</tr>

		<tr class="setting font-size">
			<th scope="row"><label for=""><?php echo __( 'Widget Font Size', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div id="title-range-slider">
					<p><?php echo __( 'Title', 'ninjateam-whatsapp' ); ?></p>
					<div class="range title-size" style='--min:10; --max:20; --value:<?php echo $option['titleSize']; ?>; --text-value:"<?php echo $option['titleSize']; ?>"'>
						<input type="range" name="titleSize" min="10" max="20" value="<?php echo esc_attr( $option['titleSize'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))">
						<output></output>
						<div class='range__progress'></div>
					</div>
				</div>
				<div id="description-range-slider">
					<p><?php echo __( 'Description', 'ninjateam-whatsapp' ); ?></p>
					<div class="range description-size" style='--min:10; --max:20; --value:<?php echo $option['descriptionTextSize']; ?>; --text-value:"<?php echo $option['descriptionTextSize']; ?>"'>
						<input type="range" name="descriptionTextSize" min="10" max="20" value="<?php echo esc_attr( $option['descriptionTextSize'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))"/>
						<output></output>
						<div class='range__progress'></div>
					</div>
				</div>
				<div id="account-name-range-slider">
					<p><?php echo __( 'Account Name', 'ninjateam-whatsapp' ); ?></p>
					<div class="range account-name-size" style='--min:10; --max:20; --value:<?php echo $option['accountNameSize']; ?>; --text-value:"<?php echo $option['accountNameSize']; ?>"'>
						<input type="range" name="accountNameSize" min="10" max="20" value="<?php echo esc_attr( $option['accountNameSize'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))" />
						<output></output>
						<div class='range__progress'></div>
					</div>
				</div>
				<div id="regular-text-range-slider">
					<p><?php echo __( 'Regular Text', 'ninjateam-whatsapp' ); ?></p>
					<div class="range regular-text-size" style='--min:10; --max:20; --value:<?php echo $option['regularTextSize']; ?>; --text-value:"<?php echo $option['regularTextSize']; ?>"'>
						<input type="range" name="regularTextSize" min="10" max="20" value="<?php echo esc_attr( $option['regularTextSize'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))"/>
						<output></output>
						<div class='range__progress'></div>
					</div>
				</div>
			</td>
		</tr>

		<tr class="setting background-color">
			<th scope="row"><label for="backgroundColor"><?php echo __( 'Widget Background Color', 'ninjateam-whatsapp' ); ?></label></th>
			<td><input id="backgroundColor" type="text" name="backgroundColor" value="<?php echo esc_attr( $option['backgroundColor'] ); ?>" class="backgroundColor" data-default-color="#2db742" /></td>
		</tr>
		<tr class="setting widget-position">
			<th scope="row"><label for=""><?php echo __( 'Widget Position', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div class="setting align">
					<div class="button-group button-large" data-setting="align">
						<button class="button btn-left <?php echo ( $option['btnPosition'] == 'left' ? 'active' : '' ); ?>" value="left" type="button">
							<?php echo __( 'Left', 'ninjateam-whatsapp' ); ?>
						</button>
						<button class="button btn-right <?php echo ( $option['btnPosition'] == 'right' ? 'active' : '' ); ?>" value="right" type="button">
							<?php echo __( 'Right', 'ninjateam-whatsapp' ); ?>
						</button>
					</div>
					<input name="btnPosition" id="btnPosition" class="hidden" value="<?php echo esc_attr( $option['btnPosition'] ); ?>" />
				</div>
			</td>
		</tr>
		<tr class="setting widget-distance">
			<th scope="row"><label for=""><?php echo __( 'Widget Distance', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div id="left-range-slider">
					<div>Left</div>
					<div class="range" style='--min:0; --max:500; --value:<?php echo $option['btnLeftDistance']; ?>; --text-value:"<?php echo $option['btnLeftDistance']; ?>";'>
						<input id="btnLeftDistance" name="btnLeftDistance" type="range" min="0" max="500" value="<?php echo esc_attr( $option['btnLeftDistance'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))">
						<output></output>
						<div class='range__progress'></div>
					</div>
				</div>
				<div id="right-range-slider">
					<div>Right</div>
					<div class="range" style='--min:0; --max:500; --value:<?php echo $option['btnRightDistance']; ?>; --text-value:"<?php echo $option['btnRightDistance']; ?>";'>
						<input id="btnRightDistance" name="btnRightDistance" type="range" min="0" max="500" value="<?php echo esc_attr( $option['btnRightDistance'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))">
						<output></output>
						<div class='range__progress'></div>
					</div>
				</div>
				<div>
					<div>Bottom</div>
					<div class="range" style='--min:0; --max:500; --value:<?php echo $option['btnBottomDistance']; ?>; --text-value:"<?php echo $option['btnBottomDistance']; ?>";'>
						<input id="btnBottomDistance" name="btnBottomDistance" type="range" min="0" max="500" value="<?php echo esc_attr( $option['btnBottomDistance'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))">
						<output></output>
						<div class='range__progress'></div>
					</div>
				</div>
			</td>
		</tr>
		<tr class="setting widget-scrollbar">
			<th scope="row"><label for=""><?php echo __( 'Widget Scroll Bar', 'ninjateam-whatsapp' ); ?><span class="dashicons dashicons-editor-help njt-wa-tooltip"></span></label></th>
			<td>
				<div class="nta-wa-switch-control" style="margin-top: 5px;">
					<input type="checkbox" id="isShowScroll" name="isShowScroll" <?php checked( $option['isShowScroll'], 'ON' ); ?>>
					<label for="isShowScroll" class="green"></label>
				</div>
			</td>
		</tr>
		<tr class="setting widget-scrollbar-control <?php echo ( $option['isShowScroll'] === 'ON' ? '' : 'hidden' ); ?>">
			<th scope="row"><label for=""></label></th>
			<td>
				<div class="range" style='--min:300; --max:1000; --value:<?php echo $option['scrollHeight']; ?>; --text-value:"<?php echo $option['scrollHeight']; ?>";'>
					<input id="scrollHeight" name="scrollHeight" type="range" min="300" max="1000" value="<?php echo esc_attr( $option['scrollHeight'] ); ?>" oninput="this.parentNode.style.setProperty('--value',this.value); this.parentNode.style.setProperty('--text-value', JSON.stringify(this.value))">
					<output></output>
					<div class='range__progress'></div>
				</div>
			</td>
		</tr>
		<tr class="setting response-text">
			<th scope="row"><label for="responseText"><?php echo __( 'Response Time Text', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<?php wp_editor( $option['responseText'], 'responseText', $editor_settings ); ?>
			</td>
		</tr>
		<tr class="setting description">
			<th scope="row"><label for="wp-description-wrap"><?php echo __( 'Description', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<?php wp_editor( $option['description'], 'description', $editor_settings_quicktags ); ?>
			</td>
		</tr>
		<tr class="setting gdpr-content">
			<th scope="row"><label for="gdprContent"><?php echo __( 'GDPR Notice', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div class="nta-wa-switch-control" style="margin-top: 5px;">
					<input type="checkbox" id="nta-wa-switch-gdpr" name="isShowGDPR" <?php checked( $option['isShowGDPR'], 'ON' ); ?>>
					<label for="nta-wa-switch-gdpr" class="green"></label>
				</div>
				<br />
				<div id="nta-gdpr-editor" class="<?php echo ( $option['isShowGDPR'] === 'ON' ? '' : 'hidden' ); ?>">
					<?php wp_editor( $option['gdprContent'], 'gdprContent', $editor_settings_quicktags ); ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<button class="button button-large button-primary wa-save"><?php esc_html_e( 'Save Changes', 'ninjateam-whatsapp' ); ?><span></span></button>
