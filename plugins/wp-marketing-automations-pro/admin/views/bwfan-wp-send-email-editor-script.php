<?php
$license       = BWFAN_Common::get_pro_license();
$token         = get_option( 'bwfan_u_key', 0 );
$site_url      = urlencode( home_url() );
$automation_id = sanitize_key( $_GET['edit'] );

$iframe_src = "//app.getautonami.com/get/$license/$token/$site_url/crm.automation.$automation_id";
/** Remove extra slashes */
$iframe_src = preg_replace('/([^:])(\/{2,})/', '$1/', $iframe_src);
?>

<div class="bwf-c-editor-modal-wrapper" id="bwf-modal-editor">
	<div class="bwf-c-editor-modal bwf-c-editor-modal-full-width" id="bwf-modal-editor-inner">
	</div>
</div>

<div class="bwfan_izimodal_default" style="display: none" id="modal-autonami-product-tool">
    <div class="bwfan-search-filter-modal-wrap">
        <div class="bwfan-modal-header bwfan_p15">
            <div class="modal-header-title bwfan_heading_l bwfan_head_mr"><?php esc_html_e( 'Select a Product', 'wp-marketing-automations-pro' ) ?></div>
            <div class="modal-header-search" id="bwf-c-product-tool-search">
                <span class="dashicons dashicons-search modal-search-icon"></span>
                <input type="search" id="modal-search-action-field" placeholder="<?php esc_html_e( 'Search Product', 'wp-marketing-automations-pro' ) ?>">
            </div>
            <span class="dashicons dashicons-no-alt bwfan_btn_close bwfan_modal_close" data-izimodal-close></span>
        </div>
        <div class="bwfan-modal-content">
            <div class="bwfan-modal-content-content bwfan_p15" id="modal-autonami-product-tool-content">
            </div>
        </div>
    </div>
</div>