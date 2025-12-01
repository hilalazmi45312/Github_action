<?php

$global_settings = new Eventprime_Global_Settings;
$admin_notices = new EventM_Admin_Notices;
$ep_functions = new Eventprime_Basic_Functions;
$ep_license = new EventPrime_License;
$ep_sanitizer = new EventPrime_sanitizer;
$sub_options = $global_settings->sub_options;
$options = $global_settings->ep_get_settings();
wp_enqueue_style( 'ep-toast-css' );
wp_enqueue_script( 'ep-toast-js' );
wp_enqueue_script( 'ep-toast-message-js' ); 
wp_localize_script(
            'ep-toast-message-js', 
            'eventprime_toast', 
            array(
               'error'=> esc_html__( 'Error', 'eventprime-event-calendar-management' ),
               'success'=> esc_html__( 'Success', 'eventprime-event-calendar-management' ),
               'warning'=> esc_html__( 'Warning', 'eventprime-event-calendar-management' ),
            )
        );
// save license key
if( isset( $_POST['submit'] ) && ! empty( $_POST['submit'] ) ){
    $form_data = $ep_sanitizer->sanitize($_POST);
    $options->ep_premium_license_key  = ( isset( $form_data['ep_premium_license_key'] ) && ! empty( $form_data['ep_premium_license_key'] ) ) ? $form_data['ep_premium_license_key'] : '';
    $global_settings->ep_save_settings( $options );
}
$key = 'ep_premium';
$id = $key.'_license_key';

$ep_license_obj = $ep_license->ep_get_license_detail($key, $options);
$ep_premium_license_key = $ep_license_obj->license_key;
$ep_license_status = $ep_license_obj->license_status;
$ep_license_response = $ep_license_obj->license_response;
$ep_premium_license_option_value = $ep_license_obj->license_option_value;
$bundle_id = $ep_license_obj->item_id;
$is_any_ext_activated = $ep_license->ep_get_activate_extensions();
$deactivate_license_btn = $key.'_license_deactivate';
$activate_license_btn = $key.'_license_activate';
?>
<div class="emagic">
   
    <div class="ep-box-row ep-my-4">
        <div class="ep-box-col-12">
            <div></div>
        </div>
    </div>

    <div class="ep-box-row">
        <div class="ep-box-col-9">
            <div id="ep-license-results">
                <div class="ep-license-box-main">
                    <div class="ep-license-box-head ep-d-flex ep-justify-content-between ep-mb-3 ep-border-bottom ep-pb-3">
                        <h3 class="ep-license-box-title"><?php esc_html_e('Available Extensions','eventprime-event-calendar-management');?></h3>
                    <div class="ep-license-button-wrap">
                        <button type="button" class="button button-primary ep-open-modal" data-id="ep_license-manager_modal" id="ep_license-manager"><?php esc_html_e('Add License','eventprime-event-calendar-management');?></button>
                    </div>
                    </div>
                         <?php $ep_license->ep_get_license_extension_html(); ?>
                </div>
            </div>
        </div>
   
        <div class="ep-box-col-3">
            <div class="ep-license-sidebar">
                    <?php $ep_license->ep_get_licenses_details(); ?>
            </div>
        </div>
        
        
    </div>    

    <!-- end license -->

    <!-- License Manager Modal --> 

   <div id="ep_license-manager_modal" class="ep-modal-view" style="display: none;">
       <div class="ep-modal-overlay ep-modal-overlay-fade-in close-popup" data-id="ep_license-manager_modal"></div>
       <div class="popup-content ep-modal-wrap ep-modal-xssm ep-modal-out">
           <div class="ep-modal-body">    
               <div class="ep-modal-titlebar ep-d-flex ep-items-center ep-border-bottom">
                   <h3 class="ep-modal-title ep-px-4"><?php esc_html_e( 'License Manager', 'eventprime-event-calendar-management' );?></h3>
                   <a href="#" class="ep-modal-close close-popup" data-id="ep_license-manager_modal"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z"></path></svg></a>
               </div> 

               <div class="ep-modal-content-wrap ep-box-wrap ep-p-4">
                   
                                        
                       
                   
                   
                   <div id="ep-license-wrapper" class="ep-d-flex ep-flex-column ep-gap-4">
                       
                       <div class="ep-license-error-message ep-my-4"></div>
                       <div class="ep-license-success-message ep-my-4"></div>
                       
                       <!-- License Key Input with Label -->
                       <div id="ep-license-fields" class="ep-license-field">
                           <label class="ep-d-inline-block ep-mb-2 ep-fw-bold"><?php esc_html_e('Enter License Key','eventprime-event-calendar-management');?></label>
                           <div class="ep-input-group ep-d-flex">
                               <input type="text" name="ep_license_key" id="ep_license_key" class="ep-license-input ep-form-control" placeholder="Enter License Key">
                               
                           </div>
                           <small class="ep-text-muted ep-mt-1 ep-d-block">
                                 <?php esc_html_e('Entering your license key unlocks premium features, ensures access to updates, and enables priority support. You can get the license key from your','eventprime-event-calendar-management');?> <a href="https://theeventprime.com/checkout/order-history/" target="_blank"><?php esc_html_e('account dashboard','eventprime-event-calendar-management');?></a>.
                            </small>
                       </div>

                       <!-- OR Separator -->
                       
                        <div class="ep-text-center ep-text-muted ep-text-uppercase ep-fw-bold ep-my-4 ep-fs-3"></div> 

                       <!-- Upload License File -->
                       <div class="ep-license-upload-fallback" style="display:none;">
                           <div class="ep-license-upload-wrap">
                                <label class="ep-d-block ep-mb-2 ep-fw-bold"><?php esc_html_e('Upload License File (.json)','eventprime-event-calendar-management');?></label>
                                <input type="file" id="ep-license-json-file" accept=".json" class="ep-form-control">
                           </div>
                           
                           <div class="ep-json-file-guide ep-text-end"><a href="https://theeventprime.com/checkout/order-history/" target="_blank"><?php esc_html_e('Where can I find this file?','eventprime-event-calendar-management');?></a></div>

                       </div>
  

                       <!-- Submit Button -->
                       <div class="ep-text-center ep-mt-4 ep-d-flex ep-justify-content-center ep-align-items-center">
                           <button type="button" id="ep-license-verify-submit" data-method="key" class="ep-btn button button-primary ep-px-4 ep-py-1"><?php esc_html_e('Verify','eventprime-event-calendar-management');?></button>
                       </div>

                   </div>
               </div>
           </div>
       </div>
   </div>

   <!-- License Manager Modal End --> 

</div>

<style>
    
#ep-license-wrapper{
    max-width: 600px;
    margin: 0px auto;
}

/* center OR separator text */
#ep-license-wrapper .ep-text-uppercase {
    font-size: 0.85rem;
    letter-spacing: 1px;
}

/* Add border or background on upload section (optional) */
.ep-license-upload-fallback .ep-license-upload-wrap  {
    padding: 1rem;
    border: 1px dashed #ced4da;
    border-radius: 6px;
    background-color: rgba(248, 249, 250, 0.5);
}

#ep_setting_form .ep-license-field input[type=text]{
    width: 100%;
    height: 40px;
}

.ep-license-field .ep-input-group{
    gap:4px
}
  
</style>