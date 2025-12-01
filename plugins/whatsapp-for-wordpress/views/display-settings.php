<table class="form-table">
	<p><?php esc_html_e( 'Setting text and style for the floating widget.', 'ninjateam-whatsapp' ); ?></p>
	<tbody>
		<tr>
			<th scope="row"><label for="time_symbols"><?php esc_html_e( 'Time Symbols', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<input name="time_symbols[hourSymbol]" placeholder="h" type="text" id="time_symbols-hour" value="<?php echo esc_attr( $option['time_symbols'][0] ); ?>" class="small-text code" style="text-align: center">
				<span>:<span>
						<input name="time_symbols[minSymbol]" placeholder="m" type="text" id="time_symbols-minutes" value="<?php echo esc_attr( $option['time_symbols'][1] ); ?>" class="small-text code" style="text-align: center">
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="nta-wa-switch-control"><?php esc_html_e( 'Show on desktop', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div class="nta-wa-switch-control">
					<input type="checkbox" id="nta-wa-switch" name="showOnDesktop" <?php checked( $option['showOnDesktop'], 'ON' ); ?>>
					<label for="nta-wa-switch" class="green"></label>
				</div>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="nta-wa-switch-control"><?php esc_html_e( 'Show on mobile', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<div class="nta-wa-switch-control">
					<input type="checkbox" id="nta-wa-switch-mb" name="showOnMobile" <?php checked( $option['showOnMobile'], 'ON' ); ?>>
					<label for="nta-wa-switch-mb" class="green"></label>
				</div>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="displayCondition"><?php esc_html_e( 'Display on Pages', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<select name="displayCondition" id="displayCondition">
					<option <?php selected( $option['displayCondition'], 'showAllPage' ); ?> value="showAllPage"><?php esc_html_e( 'Show on all pages', 'ninjateam-whatsapp' ); ?></option>
					<option <?php selected( $option['displayCondition'], 'includePages' ); ?> value="includePages"><?php esc_html_e( 'Show on these pages...', 'ninjateam-whatsapp' ); ?></option>
					<option <?php selected( $option['displayCondition'], 'excludePages' ); ?> value="excludePages"><?php esc_html_e( 'Hide on these pages...', 'ninjateam-whatsapp' ); ?></option>
				</select>
			</td>
		</tr>
		<th scope="row">
			<!-- <label for="widget_show_on_pages">
				<?php //   __('Select pages', 'ninjateam-whatsapp') ?>
			</label> -->
		</th>
		<td class="nta-wa-pages-content include-pages <?php echo esc_attr( 'includePages' === $option['displayCondition'] ? '' : 'hide-select' ); ?>">
			<ul id="nta-wa-display-pages-list">
				<?php
				$array_includes = $option['includePages'];
				if ( ! $array_includes ) {
					$array_includes = array();
				}
				foreach ( $pages as $page ) :
					?>
					<li>
						<input 
						<?php
						if ( in_array( $page->ID, $array_includes ) ) {
										'checked="checked"';
						}
						?>
								name="includePages[]" class="includePages" type="checkbox" value="<?php echo esc_attr( $page->ID ); ?>" id="nta-wa-hide-page-<?php echo esc_attr( $page->ID ); ?>" />
						<label for="nta-wa-hide-page-<?php echo esc_attr( $page->ID ); ?>"><?php echo esc_html( $page->post_title ); ?></label>
					</li>
					<?php
				endforeach;
				?>
			</ul>
		</td>

		<td class="nta-wa-pages-content exclude-pages <?php echo esc_attr( $option['displayCondition'] === 'excludePages' ? '' : 'hide-select' ); ?>">
			<ul id="nta-wa-display-pages-list">
				<?php
				$array_excludes = $option['excludePages'];
				if ( ! $array_excludes ) {
					$array_excludes = array();
				}
				foreach ( $pages as $page ) :
					?>
					<li>
						<input 
						<?php
						if ( in_array( $page->ID, $array_excludes ) ) {
										'checked="checked"';
						}
						?>
								name="excludePages[]" class="excludePages" type="checkbox" value="<?php echo esc_attr( $page->ID ); ?>" id="nta-wa-show-page-<?php echo esc_attr( $page->ID ); ?>" />
						<label for="nta-wa-show-page-<?php echo esc_attr( $page->ID ); ?>"><?php echo esc_html( $page->post_title ); ?></label>
					</li>
					<?php
				endforeach;
				?>
			</ul>
		</td>
		</tr>
		<tr>
			<th scope="row"><label for="njt-post-selector"><?php esc_html_e( 'Display on Posts', 'ninjateam-whatsapp' ); ?></label></th>
			<td>
				<select name="displayPostCondition" id="displayPostCondition">
					<option <?php selected( $option['displayPostCondition'], 'showAllPost' ); ?> value="showAllPost"><?php esc_html_e( 'Show on all posts', 'ninjateam-whatsapp' ); ?></option>
					<option <?php selected( $option['displayPostCondition'], 'includePosts' ); ?> value="includePosts"><?php esc_html_e( 'Show on these posts...', 'ninjateam-whatsapp' ); ?></option>
					<option <?php selected( $option['displayPostCondition'], 'excludePosts' ); ?> value="excludePosts"><?php esc_html_e( 'Hide on these posts...', 'ninjateam-whatsapp' ); ?></option>
				</select>
			</td>
		</tr>
		<th scope="row"></th>
		<td class="nta-wa-post-content include-posts <?php echo esc_attr( $option['displayPostCondition'] == 'includePosts' ? '' : 'hide-select' ); ?>">
			<select name="includePosts[]" id="njt-post-show-selector" multiple>
				<?php foreach ( $option['includePosts'] as $postId ) : ?>
					<option value="<?php echo esc_attr( $postId ); ?>" selected="selected">
						<?php
						// translators: %1$s is a placeholder for the post title, %2$s is a placeholder for the post ID.
						echo esc_html( sprintf( __( '%1$s (ID: %2$s)', 'ninjateam-whatsapp' ), get_the_title( $postId ), $postId ) );
						?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
		<td class="nta-wa-post-content exclude-posts <?php echo esc_attr( $option['displayPostCondition'] == 'excludePosts' ? '' : 'hide-select' ); ?>">
			<select name="excludePosts[]" id="njt-post-hide-selector" multiple>
				<?php foreach ( $option['excludePosts'] as $postId ) : ?>
					<option value="<?php echo esc_attr( $postId ); ?>" selected="selected">
						<?php
						// translators: %1$s is a placeholder for the post title, %2$s is a placeholder for the post ID.
						echo esc_html( sprintf( __( '%1$s (ID: %2$s)', 'ninjateam-whatsapp' ), get_the_title( $postId ), $postId ) );
						?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tbody>
</table>
<button class="button button-large button-primary wa-save"><?php esc_html_e( 'Save Changes', 'ninjateam-whatsapp' ); ?><span></span></button>
