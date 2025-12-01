import WFOP_PRO_Component from "./abs-component";

class WFOP_Optin_Form_Popup extends WFOP_PRO_Component {
    static slug = 'et_wfop_optin_form_popup';

    constructor() {
        super();
        this.ajax = true;
        this.popup_open = 'no';
        this.c_slug = 'et_wfop_optin_form_popup';
        this.button_event();

    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        this.props.popup_open = this.popup_open;

        super.componentDidUpdate(prevProps, prevState, snapshot);

    }

    static css(props) {
        const utils = window.ET_Builder.API.Utils;
        let wfop_divi_style = [];

        if (window.hasOwnProperty(WFOP_Optin_Form_Popup.slug + '_fields')) {
            wfop_divi_style = window[WFOP_Optin_Form_Popup.slug + '_fields'](utils, props);
        }

        return [wfop_divi_style];
    }

    button_event() {
        let self = this;

        (function ($) {



            $(document.body).on('optin_popup_close',function(){

                self.props.enable_popup_bar_on_builder='no';

            });

            $(document.body).on('click', '.bwf_pp_close', function () {
                self.popup_open = 'no';
            });



        })(jQuery);
    }

    render() {
        jQuery(document).trigger('wffn_reload_popups');
        jQuery(document).trigger('wffn_reload_phone_field');
        return super.render();
    }
}


export default WFOP_Optin_Form_Popup;