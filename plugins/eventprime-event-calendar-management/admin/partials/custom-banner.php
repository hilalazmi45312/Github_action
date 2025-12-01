<?php 
defined( 'ABSPATH' ) || exit;
$ep_functions = new Eventprime_Basic_Functions;
$activate_extensions = $ep_functions->ep_get_activate_extensions_free_paid();
$extension_url = admin_url().'edit.php?post_type=em_event&page=ep-extensions';
if( empty($activate_extensions['paid']) ) {?>
    <div class="ep-premium-banner-main emagic ep-box-w-100" style="float:left">
        <div class="ep-box-wrap" >
            <div class="ep-customize-banner-row ep-box-row">
                <div class="ep-box-col-12">
                    <div class="ep-customize-banner-main">
                    <a href="<?php echo esc_url($extension_url);?>" class="ep-customize-banner-wrap ep-d-flex ep-justify-content-between ep-align-items-center ep-p-3 ep-box-w-100 ep-bg-white ep-text-center">
                        <div class="ep-customize-banner-logo">
                            <img width="128" src="<?php echo esc_url( plugin_dir_url(EP_PLUGIN_FILE) . 'admin/partials/images/ep-logo-icon.svg'); ?>" >
                        </div>
                        <div class="ep-banner-pitch-content-wrap ep-lh-normal">
                            <div class="ep-banner-pitch-head ep-fs-2 ep-fw-bold">
                                <?php esc_html_e('Extend the power of EventPrime','eventprime-event-calendar-management');?>                                           
                            </div>
                            <div class="ep-banner-pitch-content ep-fs-5 ep-text-muted ">
                                <strong><?php esc_html_e('Free','eventprime-event-calendar-management');?></strong> <?php esc_html_e('and paid extensions now available!','eventprime-event-calendar-management');?>                                            
                            </div>
                        </div>
                        <div class="ep-banner-btn-wrap">
                                <button class="button button-primary rm-customize-banner-btn"><?php esc_html_e('Download Now','eventprime-event-calendar-management');?></button>
                        </div>
                    </a>
                    </div>
                </div>
            </div>
        </div>
    </div><?php
}?>
<style>
/*--Customization Banner--*/

#wpbody-content .ep-customize-banner-main,
#col-right .ep-customize-banner-main{
    width: 100%;
    margin-left: 0px;
}

.ep-customize-banner-main{
        width: calc(100% - 160px);
        margin-left: 160px;
}

.ep-customize-banner-wrap {
    max-width: 700px;
    margin: 30px auto;
    box-shadow: 1px 1px 3px 2px rgb(215 215 215 / 26%);
}

a.ep-customize-banner-wrap{
    text-decoration: none;
}

.ep-premium-banner-main .ep-customize-banner-wrap{
    max-width: 840px;
    margin: 30px auto;
    box-shadow: 1px 1px 3px 2px rgb(215 215 215 / 26%);
}
 /*--Customization Banner End--*/
</style>

