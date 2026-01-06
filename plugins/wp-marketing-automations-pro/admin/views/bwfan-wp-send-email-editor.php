<#
is_enable_dragdrop = ('editor' === selected_template) ? '' : 'bwfan-display-none';
editor = !!data.actionSavedData && !!data.actionSavedData.data && !!data.actionSavedData.data.editor ? data.actionSavedData.data.editor : {};
body = !!editor.body ? editor.body : '';
design = !!editor.design ? editor.design : '';
#>
<div class="bwfan-email-editor bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15 {{is_enable_dragdrop}}">
	<button class="button bwfan-button primary" id="bwf-launch-editor-modal"><?php esc_html_e( 'Launch Editor', 'wp-marketing-automations-pro' ) ?></button>
	<input name="bwfan[{{data.action_id}}][data][editor][body]" type="hidden" value="{{body}}" id="bwfan-editor-body"/>
	<input name="bwfan[{{data.action_id}}][data][editor][design]" type="hidden" value="{{design}}" id="bwfan-editor-design"/>
</div>