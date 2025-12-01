require('./divi.js');
import WFOP_Optin_Form_Popup from "./optin-form-popup";

(function ($) {
    $(window).on('et_builder_api_ready', (event, API) => {
        API.registerModules(
            [
                WFOP_Optin_Form_Popup
            ]
        );
    });
})(jQuery);